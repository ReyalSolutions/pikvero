<?php
require_once __DIR__ . '/../app/bootstrap.php';
use App\Core\Auth\Auth;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Infrastructure\Services\EmailNotificationService;
use App\Infrastructure\Services\PushNotificationService;

$request = new Request();
$type = $request->get('type', 'all');

$userId = Auth::check() ? Auth::id() : 1;

$results = [];

// Sample test details
$sampleRef = 'PB-TEST-' . rand(1000, 9999);
$sampleFacility = 'SmashZone Pickleball Center';
$sampleCourt = 'Court 1 (Indoor Professional)';
$sampleDate = date('Y-m-d');
$sampleStart = '18:00:00';
$sampleEnd = '19:00:00';
$sampleAmount = 350.00;
$sampleMethod = 'GCash Online';
$sampleSessionTitle = 'Friday Night Social Doubles Open Play';
$sampleRegId = rand(100, 999);

if ($type === 'email_booking' || $type === 'all') {
    $emailRes = EmailNotificationService::sendBookingConfirmation(
        $userId,
        $sampleRef,
        $sampleFacility,
        $sampleCourt,
        $sampleDate,
        $sampleStart,
        $sampleEnd,
        $sampleAmount,
        $sampleMethod
    );
    $results['email_booking'] = [
        'status'  => $emailRes ? 'success' : 'skipped_or_failed',
        'message' => $emailRes ? "Email notification dispatched for booking #{$sampleRef} -> Recipient List: Customer, Court Owner (alvin100golosino@gmail.com), Admin (alvin100golosino@gmail.com, admin@picklehub.com)." : "Email notification skipped (check account settings)."
    ];
}

if ($type === 'email_openplay' || $type === 'all') {
    $emailOpRes = EmailNotificationService::sendOpenPlayConfirmation(
        $userId,
        $sampleRegId,
        $sampleSessionTitle,
        $sampleFacility,
        $sampleDate,
        $sampleStart,
        $sampleEnd,
        70.00,
        'Cash at Court'
    );
    $results['email_openplay'] = [
        'status'  => $emailOpRes ? 'success' : 'skipped_or_failed',
        'message' => $emailOpRes ? "Email notification dispatched for Open Play Pass #OP-{$sampleRegId} -> Recipient List: Customer, Court Owner (alvin100golosino@gmail.com), Admin (alvin100golosino@gmail.com, admin@picklehub.com)." : "Email notification skipped."
    ];
}

if ($type === 'push_booking' || $type === 'all') {
    $pushRes = PushNotificationService::sendBookingConfirmation(
        $userId,
        $sampleRef,
        $sampleFacility,
        $sampleCourt,
        $sampleDate,
        $sampleStart,
        $sampleEnd,
        $sampleAmount,
        $sampleMethod
    );
    $results['push_booking'] = [
        'status'  => $pushRes ? 'success' : 'skipped_or_failed',
        'message' => $pushRes ? "Push notification dispatched for booking #{$sampleRef} to Customer, Court Owner, and Admin devices." : "Push notification skipped (check notification preferences)."
    ];
}

if ($type === 'push_openplay' || $type === 'all') {
    $pushOpRes = PushNotificationService::sendOpenPlayConfirmation(
        $userId,
        $sampleRegId,
        $sampleSessionTitle,
        $sampleFacility,
        $sampleDate,
        $sampleStart,
        $sampleEnd,
        70.00,
        'Cash at Court'
    );
    $results['push_openplay'] = [
        'status'  => $pushOpRes ? 'success' : 'skipped_or_failed',
        'message' => $pushOpRes ? "Push notification dispatched for Open Play Pass #OP-{$sampleRegId} to Customer, Court Owner, and Admin devices." : "Push notification skipped."
    ];
}

$samplePayoutRef = 'PO-TEST-' . rand(1000, 9999);
$samplePayoutAmount = 1500.00;
$sampleGcashName = 'Alvin Golosino';
$sampleGcashNum = '09170001234';

if ($type === 'payout_request' || $type === 'all') {
    $payoutPushRes = PushNotificationService::sendPayoutRequestNotification(
        $userId,
        rand(10, 99),
        $samplePayoutRef,
        $samplePayoutAmount,
        $sampleGcashName,
        $sampleGcashNum
    );
    $payoutEmailRes = EmailNotificationService::sendPayoutRequestConfirmation(
        $userId,
        rand(10, 99),
        $samplePayoutRef,
        $samplePayoutAmount,
        $sampleGcashName,
        $sampleGcashNum
    );
    $results['payout_request'] = [
        'status'  => ($payoutPushRes || $payoutEmailRes) ? 'success' : 'skipped_or_failed',
        'message' => "Payout Request notifications (#{$samplePayoutRef}) dispatched via Push & Email to Court Owner (alvin100golosino@gmail.com) and Platform Admins."
    ];
}

if ($type === 'payout_update' || $type === 'all') {
    $payoutStatusPush = PushNotificationService::sendPayoutStatusUpdateNotification(
        $userId,
        rand(10, 99),
        $samplePayoutRef,
        $samplePayoutAmount,
        'COMPLETED',
        'GCash Transfer Reference Ref #9928120392 Approved'
    );
    $payoutStatusEmail = EmailNotificationService::sendPayoutStatusUpdate(
        $userId,
        rand(10, 99),
        $samplePayoutRef,
        $samplePayoutAmount,
        'COMPLETED',
        'GCash Transfer Reference Ref #9928120392 Approved',
        $sampleGcashName,
        $sampleGcashNum
    );
    $results['payout_update'] = [
        'status'  => ($payoutStatusPush || $payoutStatusEmail) ? 'success' : 'skipped_or_failed',
        'message' => "Payout Status Update notifications (#{$samplePayoutRef} -> COMPLETED) dispatched via Push & Email to Court Owner and Admins."
    ];
}

if ($type === 'booking_cancellation' || $type === 'all') {
    $cancelPush = PushNotificationService::sendBookingCancellation(
        $userId,
        $sampleRef,
        $sampleFacility,
        $sampleCourt,
        $sampleDate,
        $sampleStart,
        $sampleEnd,
        'Personal Schedule Conflict'
    );
    $cancelEmail = EmailNotificationService::sendBookingCancellation(
        $userId,
        $sampleRef,
        $sampleFacility,
        $sampleCourt,
        $sampleDate,
        $sampleStart,
        $sampleEnd,
        350.00,
        'Personal Schedule Conflict'
    );
    $results['booking_cancellation'] = [
        'status'  => ($cancelPush || $cancelEmail) ? 'success' : 'skipped_or_failed',
        'message' => "Booking Cancellation notifications (#{$sampleRef}) dispatched via Push & Email to Customer, Court Owner (alvin100golosino@gmail.com), and Platform Admins."
    ];
}

if ($type === 'booking_refund' || $type === 'all') {
    $refundPush = PushNotificationService::sendRefundNotification(
        $userId,
        $sampleRef,
        $sampleFacility,
        $sampleCourt,
        350.00,
        'Rainy Weather Court Closure'
    );
    $refundEmail = EmailNotificationService::sendRefundConfirmation(
        $userId,
        $sampleRef,
        $sampleFacility,
        $sampleCourt,
        $sampleDate,
        $sampleStart,
        $sampleEnd,
        350.00,
        'Rainy Weather Court Closure'
    );
    $results['booking_refund'] = [
        'status'  => ($refundPush || $refundEmail) ? 'success' : 'skipped_or_failed',
        'message' => "Refund notifications (#{$sampleRef} -> ₱350.00) dispatched via Push & Email to Customer, Court Owner (alvin100golosino@gmail.com), and Platform Admins."
    ];
}

if ($type === 'payment_added' || $type === 'all') {
    $payPush = PushNotificationService::sendPaymentAddedNotification(
        $userId,
        $sampleRef,
        $sampleFacility,
        $sampleCourt,
        350.00,
        'GCash Online'
    );
    $payEmail = EmailNotificationService::sendPaymentAddedReceipt(
        $userId,
        $sampleRef,
        $sampleFacility,
        $sampleCourt,
        $sampleDate,
        $sampleStart,
        $sampleEnd,
        350.00,
        'GCash Online'
    );
    $results['payment_added'] = [
        'status'  => ($payPush || $payEmail) ? 'success' : 'skipped_or_failed',
        'message' => "Payment Received notifications (#{$sampleRef} -> ₱350.00) dispatched via Push & Email to Customer, Court Owner, and Platform Admins."
    ];
}

Response::success('Notification test execution finished.', [
    'test_user_id' => $userId,
    'results'      => $results
]);
