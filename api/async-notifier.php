<?php
// Ignore client disconnect so background notification tasks finish reliably
ignore_user_abort(true);
set_time_limit(300);

// Close HTTP connection immediately to caller
if (function_exists('fastcgi_finish_request')) {
    @fastcgi_finish_request();
} else {
    header("Connection: close");
    header("Content-Length: 2");
    echo "OK";
    @ob_flush();
    @flush();
}

require_once __DIR__ . '/../app/bootstrap.php';

use App\Infrastructure\Services\EmailNotificationService;
use App\Infrastructure\Services\PushNotificationService;

$raw = file_get_contents('php://input');
if (empty($raw)) exit;

$data = json_decode($raw, true);
if (!$data || empty($data['action']) || empty($data['params'])) exit;

$action = $data['action'];
$p = $data['params'];

try {
    switch ($action) {
        case 'booking_confirmation':
            PushNotificationService::sendBookingConfirmationNow(
                (int)($p['user_id'] ?? 0),
                (string)($p['booking_ref'] ?? ''),
                (string)($p['facility_name'] ?? ''),
                (string)($p['court_name'] ?? ''),
                (string)($p['booking_date'] ?? ''),
                (string)($p['start_time'] ?? ''),
                (string)($p['end_time'] ?? ''),
                (float)($p['amount'] ?? 0),
                (string)($p['payment_method'] ?? 'cash')
            );
            EmailNotificationService::sendBookingConfirmationNow(
                (int)($p['user_id'] ?? 0),
                (string)($p['booking_ref'] ?? ''),
                (string)($p['facility_name'] ?? ''),
                (string)($p['court_name'] ?? ''),
                (string)($p['booking_date'] ?? ''),
                (string)($p['start_time'] ?? ''),
                (string)($p['end_time'] ?? ''),
                (float)($p['amount'] ?? 0),
                (string)($p['payment_method'] ?? 'cash')
            );
            break;

        case 'open_play_confirmation':
            PushNotificationService::sendOpenPlayConfirmationNow(
                (int)($p['user_id'] ?? 0),
                (int)($p['registration_id'] ?? 0),
                (string)($p['session_title'] ?? ''),
                (string)($p['facility_name'] ?? ''),
                (string)($p['session_date'] ?? ''),
                (string)($p['start_time'] ?? ''),
                (string)($p['end_time'] ?? ''),
                (float)($p['amount'] ?? 0),
                (string)($p['payment_method'] ?? 'cash')
            );
            EmailNotificationService::sendOpenPlayConfirmationNow(
                (int)($p['user_id'] ?? 0),
                (int)($p['registration_id'] ?? 0),
                (string)($p['session_title'] ?? ''),
                (string)($p['facility_name'] ?? ''),
                (string)($p['session_date'] ?? ''),
                (string)($p['start_time'] ?? ''),
                (string)($p['end_time'] ?? ''),
                (float)($p['amount'] ?? 0),
                (string)($p['payment_method'] ?? 'cash')
            );
            break;

        case 'payout_request':
            PushNotificationService::sendPayoutRequestNotificationNow(
                (int)($p['owner_user_id'] ?? 0),
                (int)($p['payout_id'] ?? 0),
                (string)($p['reference_no'] ?? ''),
                (float)($p['amount'] ?? 0),
                (string)($p['gcash_name'] ?? ''),
                (string)($p['gcash_number'] ?? '')
            );
            EmailNotificationService::sendPayoutRequestConfirmationNow(
                (int)($p['owner_user_id'] ?? 0),
                (int)($p['payout_id'] ?? 0),
                (string)($p['reference_no'] ?? ''),
                (float)($p['amount'] ?? 0),
                (string)($p['gcash_name'] ?? ''),
                (string)($p['gcash_number'] ?? ''),
                (string)($p['org_name'] ?? '')
            );
            break;

        case 'payout_status':
            PushNotificationService::sendPayoutStatusUpdateNotificationNow(
                (int)($p['owner_user_id'] ?? 0),
                (int)($p['payout_id'] ?? 0),
                (string)($p['reference_no'] ?? ''),
                (float)($p['amount'] ?? 0),
                (string)($p['status'] ?? ''),
                (string)($p['admin_notes'] ?? '')
            );
            EmailNotificationService::sendPayoutStatusUpdateNow(
                (int)($p['owner_user_id'] ?? 0),
                (int)($p['payout_id'] ?? 0),
                (string)($p['reference_no'] ?? ''),
                (float)($p['amount'] ?? 0),
                (string)($p['status'] ?? ''),
                (string)($p['admin_notes'] ?? ''),
                (string)($p['gcash_name'] ?? ''),
                (string)($p['gcash_number'] ?? '')
            );
            break;

        case 'booking_cancellation':
            PushNotificationService::sendBookingCancellationNow(
                (int)($p['user_id'] ?? 0),
                (string)($p['booking_ref'] ?? ''),
                (string)($p['facility_name'] ?? ''),
                (string)($p['court_name'] ?? ''),
                (string)($p['booking_date'] ?? ''),
                (string)($p['start_time'] ?? ''),
                (string)($p['end_time'] ?? ''),
                (string)($p['reason'] ?? '')
            );
            EmailNotificationService::sendBookingCancellationNow(
                (int)($p['user_id'] ?? 0),
                (string)($p['booking_ref'] ?? ''),
                (string)($p['facility_name'] ?? ''),
                (string)($p['court_name'] ?? ''),
                (string)($p['booking_date'] ?? ''),
                (string)($p['start_time'] ?? ''),
                (string)($p['end_time'] ?? ''),
                (float)($p['amount'] ?? 0),
                (string)($p['reason'] ?? '')
            );
            break;

        case 'booking_refund':
            PushNotificationService::sendRefundNotificationNow(
                (int)($p['customer_id'] ?? 0),
                (string)($p['booking_ref'] ?? ''),
                (string)($p['facility_name'] ?? ''),
                (string)($p['court_name'] ?? ''),
                (float)($p['refund_amount'] ?? 0),
                (string)($p['reason'] ?? '')
            );
            EmailNotificationService::sendRefundConfirmationNow(
                (int)($p['customer_id'] ?? 0),
                (string)($p['booking_ref'] ?? ''),
                (string)($p['facility_name'] ?? ''),
                (string)($p['court_name'] ?? ''),
                (string)($p['booking_date'] ?? ''),
                (string)($p['start_time'] ?? ''),
                (string)($p['end_time'] ?? ''),
                (float)($p['refund_amount'] ?? 0),
                (string)($p['reason'] ?? '')
            );
            break;

        case 'payment_added':
            PushNotificationService::sendPaymentAddedNotificationNow(
                (int)($p['customer_id'] ?? 0),
                (string)($p['booking_ref'] ?? ''),
                (string)($p['facility_name'] ?? ''),
                (string)($p['court_name'] ?? ''),
                (float)($p['amount'] ?? 0),
                (string)($p['payment_method'] ?? 'cash')
            );
            EmailNotificationService::sendPaymentAddedReceiptNow(
                (int)($p['customer_id'] ?? 0),
                (string)($p['booking_ref'] ?? ''),
                (string)($p['facility_name'] ?? ''),
                (string)($p['court_name'] ?? ''),
                (string)($p['booking_date'] ?? ''),
                (string)($p['start_time'] ?? ''),
                (string)($p['end_time'] ?? ''),
                (float)($p['amount'] ?? 0),
                (string)($p['payment_method'] ?? 'cash')
            );
            break;
    }
} catch (\Throwable $e) {
    error_log("Async Notifier Exception: " . $e->getMessage());
}
