<?php
require_once __DIR__ . '/../../app/bootstrap.php';

use App\Core\Database\Connection;
use App\Core\Http\Response;
use App\Core\Security\Sanitizer;
use App\Core\Auth\Auth;
use App\Infrastructure\Repositories\UserRepository;
use App\Infrastructure\Repositories\SubscriptionPlanRepository;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Invalid request method. POST required.');
}

$rawInput = file_get_contents('php://input');
$rawArray = json_decode($rawInput, true) ?? $_POST;

// Sanitize all incoming payload data recursively
$input = Sanitizer::clean($rawArray);

$username = Sanitizer::cleanString($input['username'] ?? '');
$fname    = Sanitizer::cleanString($input['fname'] ?? '');
$lname    = Sanitizer::cleanString($input['lname'] ?? '');
$email    = Sanitizer::cleanEmail($input['email'] ?? '');
$phone    = Sanitizer::cleanPhone($input['phone'] ?? '');
$pass     = Sanitizer::cleanPassword($rawArray['pass'] ?? '');

$orgname  = Sanitizer::cleanString($input['orgname'] ?? '');
$taxid    = Sanitizer::cleanString($input['taxid'] ?? '') ?: null;

$facname  = Sanitizer::cleanString($input['facname'] ?? '');
$address  = Sanitizer::cleanString($input['address'] ?? '');
$city     = Sanitizer::cleanString($input['city'] ?? '');
$province = Sanitizer::cleanString($input['province'] ?? '');

$courtname = Sanitizer::cleanString($input['courtname'] ?? '');
$courttype = strtolower(Sanitizer::cleanString($input['courttype'] ?? 'indoor'));
$price     = (float)($input['price'] ?? 500);

$subplan   = strtolower(Sanitizer::cleanString($input['subplan'] ?? 'starter'));
$paymethod = strtolower(Sanitizer::cleanString($input['paymethod'] ?? 'gcash'));
$paymentData = $input['paymentData'] ?? [];
$docData     = $input['docData'] ?? null;

if (empty($fname) || empty($lname) || empty($email) || empty($orgname) || empty($facname) || empty($courtname)) {
    Response::error('Missing required onboarding information. Please complete all fields.');
}

$db = Connection::getInstance();
$ownerRole = $db->selectOne("SELECT id FROM roles WHERE name = 'court_owner' LIMIT 1");
if (!$ownerRole) {
    Response::error('Court owner role is not configured.');
}
$ownerRoleId = (int)$ownerRole['id'];

// Check if user already exists
$existingUser = $db->selectOne("SELECT * FROM users WHERE email = ? OR username = ? LIMIT 1", [$email, $username]);
$userId = null;

$db->beginTransaction();

try {
    if ($existingUser) {
        if ((int)(Auth::id() ?? 0) !== (int)$existingUser['id'] && !password_verify($pass, $existingUser['password_hash'])) {
            throw new \RuntimeException('Please sign in to your existing account before completing owner onboarding.');
        }
        $userId = (int)$existingUser['id'];
        $sql = "UPDATE users SET first_name = ?, last_name = ?, phone = ?, role_id = ?, status = 'active', updated_at = NOW() WHERE id = ?";
        $db->execute($sql, [$fname, $lname, $phone, $ownerRoleId, $userId], 'sssii');
    } else {
        $passwordHash = !empty($pass) ? password_hash($pass, PASSWORD_BCRYPT) : password_hash('PikveroOwner2026!', PASSWORD_BCRYPT);
        if (empty($username)) {
            $username = strtolower($fname . $lname . rand(100, 999));
        }
        $sql = "INSERT INTO users (username, first_name, last_name, email, password_hash, phone, role_id, status, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'active', NOW(), NOW())";
        $db->execute($sql, [$username, $fname, $lname, $email, $passwordHash, $phone, $ownerRoleId], 'ssssssi');
        $userId = $db->getLastInsertId();

    }
    $db->execute("INSERT IGNORE INTO user_roles (user_id, role_id) VALUES (?, ?)", [$userId, $ownerRoleId], 'ii');
    $referrals = new \App\Infrastructure\Repositories\ReferralRepository();
    $referrals->ensureCode($userId);
    $referrals->attach($userId, (string)($input['referral_code'] ?? ''));

    // 2. Create Organization
    $sqlOrg = "INSERT INTO organizations (name, tax_id, phone, email, owner_id, status, created_at)
               VALUES (?, ?, ?, ?, ?, 'active', NOW())";
    $db->execute($sqlOrg, [$orgname, $taxid, $phone, $email, $userId]);
    $organizationId = $db->getLastInsertId();

    // Map Organization to User
    $db->execute("INSERT IGNORE INTO organization_users (organization_id, user_id, role) VALUES (?, ?, 'owner')", [$organizationId, $userId]);

    // 3. Save Verification Document if uploaded
    $docPath = null;
    if ($docData && !empty($docData['base64'])) {
        $uploadDir = __DIR__ . '/../../public/uploads/verification_docs/';
        if (!file_exists($uploadDir)) {
            @mkdir($uploadDir, 0777, true);
        }
        $ext = strtolower(pathinfo($docData['name'] ?? 'doc.pdf', PATHINFO_EXTENSION));
        if (!in_array($ext,['pdf','png','jpg','jpeg'],true)) throw new \RuntimeException('Invalid document format.');
        $fileName = 'owner_' . $userId . '_' . time() . '.' . $ext;
        $fullPath = $uploadDir . $fileName;
        
        $base64Parts = explode(',', $docData['base64']);
        $encoded=end($base64Parts);
        if (!is_string($encoded) || strlen($encoded)>7*1024*1024) throw new \RuntimeException('Document exceeds the size limit.');
        $rawBytes = base64_decode($encoded,true);
        $allowedMime=['pdf'=>'application/pdf','png'=>'image/png','jpg'=>'image/jpeg','jpeg'=>'image/jpeg'];
        if ($rawBytes===false || strlen($rawBytes)>5*1024*1024 || (new \finfo(FILEINFO_MIME_TYPE))->buffer($rawBytes)!==$allowedMime[$ext]) throw new \RuntimeException('Invalid document content.');
        if ($rawBytes) {
            file_put_contents($fullPath, $rawBytes);
            $docPath = '/pikvero/public/uploads/verification_docs/' . $fileName;
        }
    }

    // 4. Create Facility
    $sqlFac = "INSERT INTO facilities (organization_id, name, address, city, province, phone, email, image_url, status, created_at)
               VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())";
    $db->execute($sqlFac, [$organizationId, $facname, $address, $city, $province, $phone, $email, $docPath]);
    $facilityId = $db->getLastInsertId();

    // 5. Create Initial Court
    $courtTypeEnum = in_array($courttype, ['indoor', 'outdoor', 'covered']) ? $courttype : 'indoor';
    $sqlCourt = "INSERT INTO courts (facility_id, name, court_number, court_type, surface_type, base_price_per_hour, status, created_at)
                 VALUES (?, ?, 1, ?, 'cushioned_acrylic', ?, 'active', NOW())";
    $db->execute($sqlCourt, [$facilityId, $courtname, $courtTypeEnum, $price]);
    $courtId = $db->getLastInsertId();

    // 6. Create Subscription
    $plan = $db->selectOne("SELECT * FROM subscription_plans WHERE slug = ? OR name LIKE ? LIMIT 1", [$subplan, "%{$subplan}%"]);
    if (!$plan) throw new \RuntimeException('Selected subscription plan not found.');
    $planId = (int)$plan['id'];
    $verifiedCheckout = \App\Application\Services\PackagePaymentVerifier::verify((string)($paymentData['reference'] ?? ''),$planId,'monthly');
    $trialEligible = (int)($plan['is_free_trial'] ?? 0) === 1 && !(new SubscriptionPlanRepository())->hasUsedFreeTrial(0, $userId);
    if ((int)($plan['is_free_trial'] ?? 0) === 1 && !$trialEligible && (int)$verifiedCheckout['amount_centavos'] === 0) {
        throw new \RuntimeException('The free trial has already been used. Select a paid package.');
    }
    $subscriptionMonths = $trialEligible
        ? max(1, (int)($plan['trial_duration_months'] ?? 1))
        : 1;

    $sqlSub = "INSERT INTO subscriptions (organization_id, plan_id, status, current_period_end, billing_cycle)
               VALUES (?, ?, 'active', DATE_ADD(NOW(), INTERVAL ? MONTH), 'monthly')";
    $db->execute($sqlSub, [$organizationId, $planId, $subscriptionMonths]);
    $subscriptionId = $db->getLastInsertId();

    // 7. Create Subscription Payment Record
    $amountPaid = (int)$verifiedCheckout['amount_centavos']/100;
    $payMethodStr = isset($paymentData['payMethod']) ? $paymentData['payMethod'] : $paymethod;

    if (!$trialEligible && $plan && (float)$plan['monthly_price'] > 0 && $amountPaid < (float)$plan['monthly_price']) {
        throw new \RuntimeException('Your free trial has already been used. Complete payment at the regular plan price.');
    }
    if ($trialEligible) {
        $amountPaid = 0;
        $payMethodStr = 'Free Trial Promo';
    }

    $sqlPay = "INSERT INTO subscription_payments (subscription_id, amount, payment_method, payment_status, created_at)
               VALUES (?, ?, ?, 'completed', NOW())";
    $db->execute($sqlPay, [$subscriptionId, $amountPaid, $payMethodStr]);
    $referrals->syncPayments();

    $ownerUser = (new UserRepository())->findById($userId);
    if (!$ownerUser) {
        throw new \RuntimeException('Unable to load the new owner account.');
    }
    $ownerUser['role_id'] = $ownerRoleId;
    $ownerUser['role_name'] = 'court_owner';
    $ownerUser['organization_id'] = $organizationId;
    \App\Application\Services\PackagePaymentVerifier::consume((string)$paymentData['reference']);
    $db->commit();

    // Set PHP Session for auto-login
    Auth::login($ownerUser);

    Response::success('Owner onboarding completed & registered in database successfully!', [
        'user_id'         => $userId,
        'organization_id' => $organizationId,
        'facility_id'     => $facilityId,
        'court_id'        => $courtId,
        'subscription_id' => $subscriptionId,
        'redirect'        => (strpos($_SERVER['REQUEST_URI'] ?? '', '/pikvero') === 0 ? '/pikvero' : '') . '/public/admin/dashboard.php'
    ]);

} catch (\Exception $e) {
    $db->rollback();
    \App\Application\Services\SecurityMonitor::log('onboarding_rejected','Owner onboarding or package validation rejected.');
    try { \App\Application\Services\SecurityMonitor::attempt('package_rejection',5); } catch (\RuntimeException $limit) { Response::error($limit->getMessage(),[],429); }
    error_log('Owner onboarding rejected: '.$e->getMessage());
    Response::error('Onboarding could not be completed. Check your details and complete a valid package checkout.');
}
