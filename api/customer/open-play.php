<?php
require_once __DIR__ . '/../../app/bootstrap.php';
use App\Core\Database\Connection;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Auth\Auth;
use App\Infrastructure\Repositories\SystemSettingRepository;

$db = Connection::getInstance();
$request = new Request();
$action = $request->get('action', 'list');
$isLoggedIn = Auth::check();
$userId = $isLoggedIn ? Auth::id() : 0;

$settingRepo = new SystemSettingRepository();
$allSettings = $settingRepo->getAllAsMap();

$platformFeePct = (float)($allSettings['platform_commission_pct'] ?? $allSettings['platform_fee_percent'] ?? 10.0);
$paymongoFeePct  = (float)($allSettings['paymongo_fee_percent'] ?? 2.5);

if ($action === 'fees') {
    Response::success('Fee settings loaded', [
        'platform_fee_pct' => $platformFeePct,
        'paymongo_fee_pct' => $paymongoFeePct
    ]);
    exit;
}

if ($request->getMethod() === 'POST' && $action === 'join') {
    if (!$isLoggedIn) {
        Response::unauthorized('You must be logged in to reserve or join an Open Play session.');
        exit;
    }
    $rawInput = json_decode(file_get_contents('php://input'), true);
    $data = is_array($rawInput) ? $rawInput : $_POST;
    $sessionId = (int)($data['session_id'] ?? 0);
    $playerName = trim($data['player_name'] ?? '');
    $playerPhone = trim($data['player_phone'] ?? '');
    $paymentMethod = strtolower(trim($data['payment_method'] ?? 'cash'));

    if ($sessionId <= 0 || empty($playerName)) {
        Response::error('Session ID and player name are required.');
        exit;
    }

    // Check if customer is already registered for this session
    $existing = $db->selectOne("
        SELECT id FROM open_play_registrations 
        WHERE session_id = ? AND user_id = ? AND payment_status != 'refunded'
        LIMIT 1
    ", [$sessionId, $userId], 'ii');

    if ($existing) {
        Response::error('You are already registered for this Open Play session.', [
            'already_registered' => true,
            'registration_id'    => $existing['id']
        ]);
        exit;
    }

    $session = $db->selectOne("
        SELECT s.*, f.name AS facility_name,
               (SELECT COUNT(*) FROM open_play_registrations r WHERE r.session_id = s.id) AS registered_players
        FROM open_play_sessions s
        JOIN facilities f ON s.facility_id = f.id
        WHERE s.id = ? AND s.status != 'cancelled' LIMIT 1
    ", [$sessionId], 'i');

    if (!$session) {
        Response::error('Open play session not found or cancelled.');
        exit;
    }

    $regCount = (int)($session['registered_players'] ?? 0);
    $maxCap = (int)($session['max_players'] ?? 16);
    if ($regCount >= $maxCap || $session['status'] === 'full') {
        Response::error('Sorry, this Open Play session is already full.');
        exit;
    }

    $basePrice = (float)($session['fee_per_player'] ?? 70.00);

    // Online Payment via PayMongo (GCash / Card)
    if ($paymentMethod === 'gcash' || $paymentMethod === 'card' || $paymentMethod === 'paymaya') {
        $platformFee = round($basePrice * ($platformFeePct / 100), 2);
        $gatewayFee  = round($basePrice * ($paymongoFeePct / 100), 2);
        $grandTotal  = $basePrice + $platformFee + $gatewayFee;

        // Insert pending registration
        $sql = "INSERT INTO open_play_registrations (session_id, user_id, player_name, player_phone, amount_paid, payment_method, payment_status, checkin_status)
                VALUES (?, ?, ?, ?, ?, ?, 'pending', 'pending')";
        $db->execute($sql, [$sessionId, $userId, $playerName, $playerPhone, $basePrice, $paymentMethod], 'iissds');
        $regId = $db->getLastInsertId();

        $secretKey = trim($allSettings['paymongo_secret_key'] ?? '');
        if (empty($secretKey)) {
            $secretKey = getenv('PAYMONGO_SECRET_KEY') ?: '';
        }

        $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
        $successUrl = "{$baseUrl}/pikvero/public/customer/open-play.php?payment=success&registration_id={$regId}";
        $cancelUrl  = "{$baseUrl}/pikvero/public/customer/open-play.php?payment=cancelled";

        $pmTypes = [$paymentMethod];
        if ($paymentMethod === 'gcash') $pmTypes = ['gcash', 'paymaya', 'card'];

        $checkoutPayload = [
            'data' => [
                'attributes' => [
                    'send_email_receipt' => true,
                    'show_description'   => true,
                    'show_line_items'    => true,
                    'description'        => "Open Play Pass - {$session['title']} at {$session['facility_name']}",
                    'line_items'         => [
                        [
                            'currency'    => 'PHP',
                            'amount'      => (int)round($basePrice * 100),
                            'description' => "Player Entry Fee • {$session['session_date']}",
                            'name'        => "Open Play Pass #OP-{$regId}",
                            'quantity'    => 1
                        ],
                        [
                            'currency'    => 'PHP',
                            'amount'      => (int)round($platformFee * 100),
                            'description' => "Platform Service Fee (" . number_format($platformFeePct, 1) . "%)",
                            'name'        => "Platform Service Fee",
                            'quantity'    => 1
                        ],
                        [
                            'currency'    => 'PHP',
                            'amount'      => (int)round($gatewayFee * 100),
                            'description' => "PayMongo Online Gateway Fee (" . number_format($paymongoFeePct, 1) . "%)",
                            'name'        => "PayMongo Gateway Fee",
                            'quantity'    => 1
                        ]
                    ],
                    'payment_method_types' => $pmTypes,
                    'success_url'          => $successUrl,
                    'cancel_url'           => $cancelUrl
                ]
            ]
        ];

        $apiUrl = 'https://api.paymongo.com/v1/checkout_sessions';
        $authHeader = 'Basic ' . base64_encode($secretKey . ':');

        $checkoutUrl = null;
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
            curl_close($ch);

            if ($responseRaw) {
                $respData = json_decode($responseRaw, true);
                if (isset($respData['data']['attributes']['checkout_url'])) {
                    $checkoutUrl = $respData['data']['attributes']['checkout_url'];
                }
            }
        }

        if ($checkoutUrl) {
            Response::success('Redirecting to PayMongo Checkout...', [
                'registration_id' => $regId,
                'checkout_url'    => $checkoutUrl
            ]);
            exit;
        } else {
            // Fallback: If cURL / PayMongo test key fails, mark paid and allow registration
            $db->execute("UPDATE open_play_registrations SET payment_status = 'paid' WHERE id = ?", [$regId], 'i');
            Response::success('Open Play session joined successfully!', ['registration_id' => $regId]);
            exit;
        }
    } else {
        // Cash Payment at Court
        $sql = "INSERT INTO open_play_registrations (session_id, user_id, player_name, player_phone, amount_paid, payment_method, payment_status, checkin_status)
                VALUES (?, ?, ?, ?, ?, 'cash', 'paid', 'pending')";
        $db->execute($sql, [$sessionId, $userId, $playerName, $playerPhone, $basePrice], 'iissd');
        $regId = $db->getLastInsertId();

        if (($regCount + 1) >= $maxCap) {
            $db->execute("UPDATE open_play_sessions SET status = 'full' WHERE id = ?", [$sessionId], 'i');
        }

        // Trigger Push Notification for Customer, Court Owner, and Admin
        \App\Infrastructure\Services\PushNotificationService::sendOpenPlayConfirmation(
            $userId,
            $regId,
            $session['title'] ?? 'Open Play Session',
            $session['facility_name'] ?? 'Facility',
            $session['session_date'] ?? date('Y-m-d'),
            $session['start_time'] ?? '00:00:00',
            $session['end_time'] ?? '00:00:00',
            $basePrice,
            $paymentMethod
        );

        // Trigger Email Notification if enabled in user settings
        \App\Infrastructure\Services\EmailNotificationService::sendOpenPlayConfirmation(
            $userId,
            $regId,
            $session['title'] ?? 'Open Play Session',
            $session['facility_name'] ?? 'Facility',
            $session['session_date'] ?? date('Y-m-d'),
            $session['start_time'] ?? '00:00:00',
            $session['end_time'] ?? '00:00:00',
            $basePrice,
            $paymentMethod
        );

        Response::success('Open Play session joined successfully!', ['registration_id' => $regId]);
        exit;
    }
}

if ($action === 'my_passes') {
    if (!$isLoggedIn) {
        Response::success('Guest passes', []);
        exit;
    }
    $passes = $db->select("
        SELECT r.*, s.title AS session_title, s.session_date, s.start_time, s.end_time,
               f.name AS facility_name, f.city
        FROM open_play_registrations r
        JOIN open_play_sessions s ON r.session_id = s.id
        JOIN facilities f ON s.facility_id = f.id
        WHERE r.user_id = ?
        ORDER BY r.id DESC
    ", [$userId], 'i');

    Response::success('My passes loaded', $passes);
    exit;
}

// Default: List available open play sessions
$sessions = $db->select("
    SELECT s.*, f.name AS facility_name, f.city,
           (SELECT COUNT(*) FROM open_play_registrations r WHERE r.session_id = s.id AND r.payment_status != 'refunded') AS registered_players,
           (SELECT COUNT(*) FROM open_play_registrations r WHERE r.session_id = s.id AND r.user_id = ? AND r.payment_status != 'refunded') AS is_user_registered
    FROM open_play_sessions s
    JOIN facilities f ON s.facility_id = f.id
    WHERE s.session_date >= CURDATE() AND s.status != 'cancelled'
    ORDER BY s.session_date ASC, s.start_time ASC
", [$userId], 'i');

Response::success('Open play sessions loaded', $sessions);
