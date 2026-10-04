<?php
require_once __DIR__ . '/../../app/bootstrap.php';

use App\Core\Database\Connection;
use App\Core\Http\Response;
use App\Core\Security\Sanitizer;

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
$taxid    = Sanitizer::cleanString($input['taxid'] ?? '');

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

// Check if user already exists
$existingUser = $db->selectOne("SELECT * FROM users WHERE email = ? OR username = ? LIMIT 1", [$email, $username]);
$userId = null;

$db->beginTransaction();

try {
    if ($existingUser) {
        $userId = (int)$existingUser['id'];
        $sql = "UPDATE users SET first_name = ?, last_name = ?, phone = ?, role_id = 3, status = 'active', updated_at = NOW() WHERE id = ?";
        $db->execute($sql, [$fname, $lname, $phone, $userId], 'sssi');
    } else {
        $passwordHash = !empty($pass) ? password_hash($pass, PASSWORD_BCRYPT) : password_hash('PikveroOwner2026!', PASSWORD_BCRYPT);
        if (empty($username)) {
            $username = strtolower($fname . $lname . rand(100, 999));
        }
        $sql = "INSERT INTO users (username, first_name, last_name, email, password_hash, phone, role_id, status, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, 3, 'active', NOW(), NOW())";
        $db->execute($sql, [$username, $fname, $lname, $email, $passwordHash, $phone], 'ssssss');
        $userId = $db->getLastInsertId();

        // Assign user role (Court Owner = 3)
        $db->execute("INSERT IGNORE INTO user_roles (user_id, role_id) VALUES (?, 3)", [$userId]);
    }

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
        $ext = pathinfo($docData['name'] ?? 'doc.pdf', PATHINFO_EXTENSION);
        $fileName = 'owner_' . $userId . '_' . time() . '.' . $ext;
        $fullPath = $uploadDir . $fileName;
        
        $base64Parts = explode(',', $docData['base64']);
        $rawBytes = base64_decode(end($base64Parts));
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
    $planId = $plan ? (int)$plan['id'] : 1;

    $sqlSub = "INSERT INTO subscriptions (organization_id, plan_id, status, current_period_end, billing_cycle)
               VALUES (?, ?, 'active', DATE_ADD(NOW(), INTERVAL 1 MONTH), 'monthly')";
    $db->execute($sqlSub, [$organizationId, $planId]);
    $subscriptionId = $db->getLastInsertId();

    // 7. Create Subscription Payment Record
    $amountPaid = isset($paymentData['totalAmount']) ? (float)$paymentData['totalAmount'] : ($plan ? (float)$plan['monthly_price'] : 999.00);
    $payMethodStr = isset($paymentData['payMethod']) ? $paymentData['payMethod'] : $paymethod;

    $sqlPay = "INSERT INTO subscription_payments (subscription_id, amount, payment_method, payment_status, created_at)
               VALUES (?, ?, ?, 'completed', NOW())";
    $db->execute($sqlPay, [$subscriptionId, $amountPaid, $payMethodStr]);

    $db->commit();

    // Set PHP Session for auto-login
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_role_id'] = 3;
    $_SESSION['user_name'] = "{$fname} {$lname}";
    $_SESSION['user_email'] = $email;
    $_SESSION['organization_id'] = $organizationId;

    Response::success('Owner onboarding completed & registered in database successfully!', [
        'user_id'         => $userId,
        'organization_id' => $organizationId,
        'facility_id'     => $facilityId,
        'court_id'        => $courtId,
        'subscription_id' => $subscriptionId,
        'redirect'        => '/pikvero/public/admin/dashboard.php'
    ]);

} catch (\Exception $e) {
    $db->rollback();
    Response::error('Failed to save onboarding records to database: ' . $e->getMessage());
}
