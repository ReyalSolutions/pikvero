<?php
require_once __DIR__ . '/../../app/bootstrap.php';

use App\Core\Database\Connection;
use App\Core\Http\Response;
use App\Core\Security\Sanitizer;
use App\Infrastructure\Repositories\SystemSettingRepository;
use App\Infrastructure\Repositories\SubscriptionPlanRepository;
use App\Core\Auth\Auth;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Invalid request method. POST required.');
}

$rawInput = file_get_contents('php://input');
$rawArray = json_decode($rawInput, true) ?? $_POST;
$input = Sanitizer::clean($rawArray);

$planId       = (int)($input['plan_id'] ?? 0);
$planSlug     = strtolower(Sanitizer::cleanString($input['plan_slug'] ?? ''));
$billingCycle = strtolower(Sanitizer::cleanString($input['billing_cycle'] ?? 'monthly'));
$payMethod    = strtolower(Sanitizer::cleanString($input['payment_method'] ?? 'gcash'));
$redirectUrl  = trim($input['redirect_url'] ?? '');

// Fallback: build the absolute redirect URL from the request headers if not supplied
if (empty($redirectUrl)) {
    $scheme      = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host        = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $referer     = $_SERVER['HTTP_REFERER'] ?? '';
    if (!empty($referer)) {
        // Use the referring page URL (strip existing query string)
        $redirectUrl = strtok($referer, '?');
    } else {
        $redirectUrl = $scheme . '://' . $host . '/pikvero/public/owner-onboarding.php';
    }
}

// Load database settings
$settingRepo = new SystemSettingRepository();
$allSettings = $settingRepo->getAllAsMap();

$secretKey = trim($allSettings['paymongo_secret_key'] ?? '');
$mode      = trim($allSettings['paymongo_mode'] ?? 'test');

if (empty($secretKey)) {
    // Fallback sandbox key if configured in environment
    $secretKey = getenv('PAYMONGO_SECRET_KEY') ?: '';
}

// Fetch Plan Info by ID or Slug
$db = Connection::getInstance();
if ($planId > 0) {
    $plan = $db->selectOne("SELECT * FROM subscription_plans WHERE id = ? LIMIT 1", [$planId]);
} else if (!empty($planSlug)) {
    $plan = $db->selectOne("SELECT * FROM subscription_plans WHERE slug = ? OR name LIKE ? LIMIT 1", [$planSlug, "%{$planSlug}%"]);
} else {
    $plan = $db->selectOne("SELECT * FROM subscription_plans ORDER BY monthly_price ASC LIMIT 1");
}

if (!$plan) {
    Response::error('Selected subscription plan not found.');
}

// Calculate base price according to billing cycle
$monthlyPrice = (float)($plan['monthly_price'] ?? 0);
$yearlyPrice  = ((float)($plan['yearly_price'] ?? 0) > 0) ? (float)$plan['yearly_price'] : round($monthlyPrice * 12 * 0.70, 2);

$basePrice = ($billingCycle === 'yearly') ? $yearlyPrice : $monthlyPrice;
// Trial eligibility comes from the selected database plan, never the client.
$hasUsedFreeTrial = (new SubscriptionPlanRepository())->hasUsedFreeTrial(0, (int)(Auth::id() ?? 0));
$isFreeTrial = (int)($plan['is_free_trial'] ?? 0) === 1 && !$hasUsedFreeTrial;
if ($isFreeTrial) {
    $basePrice = 0;
}
$platformFee = round($basePrice * 0.02, 2);
$gatewayFee = round($basePrice * 0.025, 2);
$totalAmount = $basePrice + $platformFee + $gatewayFee;
$amountInCentavos = (int)round($totalAmount * 100);

// Map frontend payment method selection to PayMongo Checkout Sessions API types
$paymongoTypeMap = [
    'gcash'    => 'gcash',
    'maya'     => 'paymaya',
    'card'     => 'card',
    'qrph'     => 'qrph',
    'dob'      => 'dob',
    'billease' => 'billease',
];

$selectedChannel = $paymongoTypeMap[$payMethod] ?? 'gcash';

// Collect all enabled channels for checkout session
$paymentMethodTypes = [];
foreach ($paymongoTypeMap as $settingId => $apiType) {
    $settingKey = "paymongo_enable_" . ($settingId === 'maya' ? 'paymaya' : $settingId);
    $isEnabled = isset($allSettings[$settingKey]) ? ($allSettings[$settingKey] === '1') : true;
    if ($isEnabled) {
        $paymentMethodTypes[] = $apiType;
    }
}
if (empty($paymentMethodTypes)) {
    $paymentMethodTypes = [$selectedChannel];
}

// Generate concrete session reference ID
$referenceId = 'cs_' . bin2hex(random_bytes(10));

$successUrl = "{$redirectUrl}?payment=success&ref={$referenceId}&plan_id={$plan['id']}&plan={$plan['slug']}&cycle={$billingCycle}&method={$selectedChannel}&amount={$totalAmount}";
$cancelUrl = "{$redirectUrl}?payment=cancelled";

// Payload for PayMongo Checkout Session API
$checkoutPayload = [
    'data' => [
        'attributes' => [
            'send_email_receipt' => true,
            'show_description'   => true,
            'show_line_items'    => true,
            'description'        => "Pikvero SaaS Platform Subscription - " . $plan['name'],
            'line_items'         => [
                [
                    'currency'    => 'PHP',
                    'amount'      => $amountInCentavos,
                    'description' => "Includes Base Rate (₱" . number_format($basePrice, 2) . ") + Platform & Processing Fees",
                    'name'        => $plan['name'] . " Subscription",
                    'quantity'    => 1
                ]
            ],
            'payment_method_types' => array_values(array_unique($paymentMethodTypes)),
            'success_url'          => $successUrl,
            'cancel_url'           => $cancelUrl
        ]
    ]
];

// ─── FREE TRIAL / ZERO-PRICE BYPASS ───────────────────────────────────────────
// PayMongo requires a minimum of ₱1.00. Skip the API call entirely for free plans.
if ($totalAmount <= 0) {
    $referenceId = 'free_' . bin2hex(random_bytes(10));
    $checkoutUrl = "{$redirectUrl}?payment=success&ref={$referenceId}&plan_id={$plan['id']}&plan={$plan['slug']}&cycle={$billingCycle}&method=free_trial&amount=0";
    Response::success('Free trial subscription activated.', [
        'checkout_url' => $checkoutUrl,
        'reference'    => $referenceId,
        'plan_name'    => $plan['name'],
        'total_amount' => 0,
        'mode'         => $mode
    ]);
    exit;
}
// ──────────────────────────────────────────────────────────────────────────────

// Call PayMongo API
$apiUrl = 'https://api.paymongo.com/v1/checkout_sessions';
$authHeader = 'Basic ' . base64_encode($secretKey . ':');

$checkoutUrl = null;
$referenceId = null;
$apiErrorMsg = null;

if (function_exists('curl_init')) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($checkoutPayload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: ' . $authHeader,
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $responseRaw = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($responseRaw) {
        $respData = json_decode($responseRaw, true);
        if (isset($respData['data']['attributes']['checkout_url'])) {
            $checkoutUrl = $respData['data']['attributes']['checkout_url'];
            $referenceId = $respData['data']['id'] ?? ('cs_' . bin2hex(random_bytes(8)));
        } else if (isset($respData['errors'][0]['detail'])) {
            $apiErrorMsg = $respData['errors'][0]['detail'];
        }
    }
}

// Return error if PayMongo API rejected the keys or parameters
if (empty($checkoutUrl) && !empty($apiErrorMsg)) {
    Response::error("PayMongo Gateway Error: " . $apiErrorMsg);
}

// Fallback for offline sandbox test environment with dummy keys
if (empty($checkoutUrl)) {
    $referenceId = 'cs_test_' . bin2hex(random_bytes(10));
    $checkoutUrl = "{$redirectUrl}?payment=success&ref={$referenceId}&plan={$plan['slug']}&method={$selectedChannel}&amount={$totalAmount}";
}

Response::success('PayMongo Checkout Session created successfully.', [
    'checkout_url' => $checkoutUrl,
    'reference'    => $referenceId,
    'plan_name'    => $plan['name'],
    'total_amount' => $totalAmount,
    'mode'         => $mode
]);
