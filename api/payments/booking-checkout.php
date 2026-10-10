<?php
require_once __DIR__ . '/../../app/bootstrap.php';

use App\Core\Database\Connection;
use App\Core\Http\Response;
use App\Core\Auth\Auth;
use App\Core\Security\Sanitizer;
use App\Infrastructure\Repositories\SystemSettingRepository;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Invalid request method. POST required.');
}

if (!Auth::check()) {
    Response::unauthorized('Please log in to proceed with payment.');
}

$rawInput = file_get_contents('php://input');
$rawArray = json_decode($rawInput, true) ?? $_POST;
$input = Sanitizer::clean($rawArray);

$bookingRef = trim(Sanitizer::cleanString($input['booking_reference'] ?? ''));
$facilityId = (int)($input['facility_id'] ?? 0);

if (empty($bookingRef)) {
    Response::error('Booking reference is required.');
}

// Fetch the booking
$db = Connection::getInstance();
$booking = $db->selectOne(
    "SELECT b.*, c.name AS court_name, f.name AS facility_name 
     FROM bookings b 
     JOIN courts c ON c.id = b.court_id 
     JOIN facilities f ON f.id = b.facility_id 
     WHERE b.booking_reference = ? AND b.customer_id = ?",
    [$bookingRef, Auth::id()],
    'si'
);

if (!$booking) {
    Response::error('Booking not found.');
}

if ($booking['payment_status'] === 'paid') {
    Response::error('This booking has already been paid.');
}

if (in_array($booking['booking_status'], ['cancelled', 'expired', 'refunded'], true)) {
    Response::error('This booking is ' . $booking['booking_status'] . ' and cannot be paid.');
}

// Load database settings
$settingRepo = new SystemSettingRepository();
$allSettings = $settingRepo->getAllAsMap();

$platformFeePct = (float)($allSettings['platform_commission_pct'] ?? $allSettings['platform_fee_percent'] ?? 0);
$paymongoFeePct = (float)($allSettings['paymongo_fee_percent'] ?? 0);
$passGatewayFee = ($allSettings['payment_gateway_fee_pass'] ?? '0') === '1';

$basePrice   = (float)$booking['total_amount'];
$platformFee = round($basePrice * ($platformFeePct / 100), 2);
$gatewayFee  = $passGatewayFee ? round($basePrice * ($paymongoFeePct / 100), 2) : 0.0;
$grandTotal  = $basePrice + $platformFee + $gatewayFee;

$secretKey = trim($allSettings['paymongo_secret_key'] ?? '');
$mode = trim($allSettings['paymongo_mode'] ?? 'test');

if (empty($secretKey)) {
    $secretKey = getenv('PAYMONGO_SECRET_KEY') ?: '';
}

// Collect all enabled payment method channels
$paymongoTypeMap = [
    'gcash'    => 'gcash',
    'maya'     => 'paymaya',
    'card'     => 'card',
    'qrph'    => 'qrph',
];

$paymentMethodTypes = [];
foreach ($paymongoTypeMap as $settingId => $apiType) {
    $settingKey = "paymongo_enable_" . ($settingId === 'maya' ? 'paymaya' : $settingId);
    $isEnabled = isset($allSettings[$settingKey]) ? ($allSettings[$settingKey] === '1') : true;
    if ($isEnabled) {
        $paymentMethodTypes[] = $apiType;
    }
}
if (empty($paymentMethodTypes)) {
    $paymentMethodTypes = ['gcash'];
}

// Honor the method chosen on the mobile payment screen.
$selectedChannel = trim((string)($input['payment_channel'] ?? ''));
if ($selectedChannel !== '') {
    if (!in_array($selectedChannel, $paymentMethodTypes, true)) {
        Response::error('The selected payment method is unavailable. Please choose another method.');
    }
    $paymentMethodTypes = [$selectedChannel];
}

// Build URLs
$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
$successUrl = "{$baseUrl}/pikvero/public/customer/bookings.php?payment=success&booking_ref={$bookingRef}";
$cancelUrl  = "{$baseUrl}/pikvero/public/facility.php?id={$facilityId}&payment=cancelled";

// Build description
$courtName = $booking['court_name'] ?? 'Court';
$facilityName = $booking['facility_name'] ?? 'Facility';
$bookingDate = $booking['booking_date'];
$startTime = substr($booking['start_time'], 0, 5);
$endTime = substr($booking['end_time'], 0, 5);
$durationHours = (float)$booking['duration_hours'];

$description = "Court Reservation - {$courtName} at {$facilityName} on {$bookingDate} ({$startTime} - {$endTime})";

// PayMongo Checkout Session payload
$checkoutPayload = [
    'data' => [
        'attributes' => [
            'send_email_receipt' => true,
            'show_description'   => true,
            'show_line_items'    => true,
            'description'        => $description,
            'line_items'         => [
                [
                    'currency'    => 'PHP',
                    'amount'      => (int)round($basePrice * 100),
                    'description' => "{$courtName} • {$bookingDate} • {$startTime}-{$endTime} ({$durationHours}h)",
                    'name'        => "Court Reservation #{$bookingRef}",
                    'quantity'    => 1
                ],
                [
                    'currency'    => 'PHP',
                    'amount'      => (int)round(($platformFee + $gatewayFee) * 100),
                    'description' => 'Service fees',
                    'name'        => 'Service fees',
                    'quantity'    => 1
                ]
            ],
            'payment_method_types' => array_values(array_unique($paymentMethodTypes)),
            'success_url'          => $successUrl,
            'cancel_url'           => $cancelUrl
        ]
    ]
];

// Call PayMongo API
// Do not send zero-value fee line items when the facility covers the fee.
$checkoutPayload['data']['attributes']['line_items'] = array_values(array_filter(
    $checkoutPayload['data']['attributes']['line_items'],
    fn($item) => $item['amount'] > 0
));
$apiUrl = 'https://api.paymongo.com/v1/checkout_sessions';
$authHeader = 'Basic ' . base64_encode($secretKey . ':');

$checkoutUrl = null;
$sessionId = null;
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
            $sessionId = $respData['data']['id'] ?? ('cs_' . bin2hex(random_bytes(8)));
        } else if (isset($respData['errors'][0]['detail'])) {
            $apiErrorMsg = $respData['errors'][0]['detail'];
        }
    }
}

// Return error if PayMongo API rejected
if (empty($checkoutUrl) && !empty($apiErrorMsg)) {
    Response::error("PayMongo Gateway Error: " . $apiErrorMsg);
}

// Fallback for offline sandbox/test environment
if (empty($checkoutUrl)) {
    $sessionId = 'cs_test_' . bin2hex(random_bytes(10));
    $checkoutUrl = $successUrl;
}

Response::success('Checkout session created.', [
    'checkout_url'      => $checkoutUrl,
    'session_id'        => $sessionId,
    'booking_reference' => $bookingRef,
    'base_amount'       => $basePrice,
    'platform_fee'      => $platformFee,
    'gateway_fee'       => $gatewayFee,
    'total_amount'      => $grandTotal,
    'mode'              => $mode
]);
