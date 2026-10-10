<?php
namespace App\Application\Services;

use App\Infrastructure\Repositories\BookingRepository;
use App\Infrastructure\Repositories\CourtRepository;
use App\Infrastructure\Repositories\AuditLogRepository;
use App\Core\Database\Connection;
use Exception;

class BookingService {
    private BookingRepository $bookingRepo;
    private CourtRepository $courtRepo;
    private AvailabilityEngine $availabilityEngine;
    private AuditLogRepository $auditRepo;

    public function __construct() {
        $this->bookingRepo = new BookingRepository();
        $this->courtRepo = new CourtRepository();
        $this->availabilityEngine = new AvailabilityEngine();
        $this->auditRepo = new AuditLogRepository();
    }

    public function createBooking(int $customerId, int $courtId, string $date, string $startTime, string $endTime, ?string $notes = null, string $paymentMethod = 'cash', array $addons = []): array {
        $court = $this->courtRepo->findById($courtId);
        if (!$court) {
            throw new Exception("Court not found.");
        }

        // Validate server-side time slot availability
        $this->availabilityEngine->validateSlotAvailability($courtId, $date, $startTime, $endTime);

        // Server-side calculation of duration & amount (NEVER trust frontend amount!)
        $startTs = strtotime("{$date} {$startTime}");
        $endTs = strtotime("{$date} {$endTime}");
        $durationHours = max(1.0, round(($endTs - $startTs) / 3600.0, 2));

        $ratePerHour = (float)$court['base_price_per_hour'];
        $totalAmount = round($durationHours * $ratePerHour, 2);

        // Generate Reference Code
        $refCode = 'PB-' . date('Ymd', strtotime($date)) . '-' . sprintf('%04d', rand(1, 9999));

        // Determine statuses based on payment method
        $isOnline = ($paymentMethod === 'online');
        $bookingStatus = $isOnline ? 'awaiting_payment' : 'confirmed';
        $paymentStatus = 'unpaid'; // Pay at counter (cash) & online checkout both start as UNPAID until owner/gateway confirms payment

        $db = Connection::getInstance();
        $db->beginTransaction();

        try {
            $bookingItems = [];
            $seenProducts = [];
            if (count($addons) > 20) throw new Exception('Too many add-ons selected.');
            foreach ($addons as $addon) {
                if (!is_array($addon)) throw new Exception('Invalid add-on.');
                $productId = filter_var($addon['product_id'] ?? null, FILTER_VALIDATE_INT);
                $quantity = filter_var($addon['quantity'] ?? null, FILTER_VALIDATE_INT);
                if (!$productId || !$quantity || $quantity < 1 || $quantity > 20 || isset($seenProducts[$productId])) {
                    throw new Exception('Invalid add-on quantity.');
                }
                $seenProducts[$productId] = true;
                $product = $db->selectOne("SELECT * FROM products WHERE id = ? AND facility_id = ? AND type = 'rental' AND status = 'active' FOR UPDATE", [$productId, (int)$court['facility_id']], 'ii');
                if (!$product) throw new Exception('A selected add-on is no longer available.');
                $subtotal = round((float)$product['price'] * $quantity, 2);
                $totalAmount += $subtotal;
                $bookingItems[] = [$product['name'], $quantity, (float)$product['price'], $subtotal];
            }
            $bookingId = $this->bookingRepo->create([
                'booking_reference' => $refCode,
                'customer_id' => $customerId,
                'court_id' => $courtId,
                'facility_id' => (int)$court['facility_id'],
                'organization_id' => (int)$court['organization_id'],
                'booking_date' => $date,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'duration_hours' => $durationHours,
                'rate_per_hour' => $ratePerHour,
                'total_amount' => $totalAmount,
                'payment_status' => $paymentStatus,
                'booking_status' => $bookingStatus,
                'notes' => $notes
            ]);

            foreach ($bookingItems as $item) {
                $db->execute('INSERT INTO booking_items (booking_id, item_name, quantity, unit_price, subtotal) VALUES (?, ?, ?, ?, ?)', [$bookingId, ...$item], 'isidd');
            }
            $db->commit();

            $this->auditRepo->log($customerId, 'booking.create', 'Bookings', "Created booking #{$refCode} for " . ($court['name'] ?? "Court #{$courtId}") . " on {$date} ({$startTime} - {$endTime}) [Payment: {$paymentMethod}]");

            // Push Notification trigger for Customer, Court Owner, and Admin
            $courtName = $court['name'] ?? "Court #{$courtId}";
            \App\Infrastructure\Services\PushNotificationService::sendBookingConfirmation(
                $customerId,
                $refCode,
                $court['facility_name'] ?? 'Facility',
                $courtName,
                $date,
                $startTime,
                $endTime,
                $totalAmount,
                $paymentMethod
            );

            // Email Notification trigger based on account settings
            \App\Infrastructure\Services\EmailNotificationService::sendBookingConfirmation(
                $customerId,
                $refCode,
                $court['facility_name'] ?? 'Facility',
                $courtName,
                $date,
                $startTime,
                $endTime,
                $totalAmount,
                $paymentMethod
            );

            return $this->bookingRepo->findByReference($refCode);
        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }
    }

    public function cancelBooking(int $bookingId, int $userId, ?string $reason = null): bool {
        $booking = $this->bookingRepo->findById($bookingId);
        $refCode = $booking['booking_reference'] ?? "ID #{$bookingId}";
        $courtName = $booking['court_name'] ?? "Court";
        $facilityName = $booking['facility_name'] ?? "Facility";
        $bookingDate = $booking['booking_date'] ?? date('Y-m-d');
        $startTime = $booking['start_time'] ?? '00:00:00';
        $endTime = $booking['end_time'] ?? '00:00:00';
        $amount = (float)($booking['total_amount'] ?? 0);
        $customerId = (int)($booking['customer_id'] ?? $userId);

        $success = $this->bookingRepo->cancelBooking($bookingId, $reason, $userId);
        if (!$success) {
            $success = $this->bookingRepo->cancel($bookingId, 'cancelled');
        }
        if ($success) {
            $msg = "Cancelled booking #{$refCode} for {$courtName}";
            if (!empty($reason)) {
                $msg .= " (Reason: {$reason})";
            }
            $this->auditRepo->log($userId, 'booking.cancel', 'Bookings', $msg);

            // Trigger Push & Email Cancellation Notifications to Customer, Court Owner, and Admin
            \App\Infrastructure\Services\PushNotificationService::sendBookingCancellation(
                $customerId,
                $refCode,
                $facilityName,
                $courtName,
                $bookingDate,
                $startTime,
                $endTime,
                (string)$reason
            );

            \App\Infrastructure\Services\EmailNotificationService::sendBookingCancellation(
                $customerId,
                $refCode,
                $facilityName,
                $courtName,
                $bookingDate,
                $startTime,
                $endTime,
                $amount,
                (string)$reason
            );
        }
        return $success;
    }

    public function markPaymentSuccess(string $bookingRef, int $userId): ?array {
        $booking = $this->bookingRepo->findByReference($bookingRef);
        if (!$booking) {
            return null;
        }

        // Verify ownership
        if ((int)$booking['customer_id'] !== $userId) {
            return null;
        }

        // If already paid and confirmed, return as is
        if ($booking['payment_status'] === 'paid' && $booking['booking_status'] === 'confirmed') {
            return $booking;
        }

        $db = Connection::getInstance();
        $db->beginTransaction();

        try {
            // Update booking status
            $db->execute(
                "UPDATE bookings SET payment_status = 'paid', booking_status = 'confirmed', updated_at = NOW() WHERE id = ?",
                [(int)$booking['id']],
                'i'
            );

            // Check if payment record exists
            $existingPayment = $db->selectOne("SELECT id FROM payments WHERE booking_id = ? LIMIT 1", [(int)$booking['id']], 'i');
            if (!$existingPayment) {
                $db->execute(
                    "INSERT INTO payments (booking_id, amount, payment_method, transaction_reference, status) VALUES (?, ?, 'gcash', ?, 'completed')",
                    [(int)$booking['id'], (float)$booking['total_amount'], 'PAY-' . strtoupper(substr(md5(uniqid()), 0, 8))],
                    'ids'
                );
            } else {
                $db->execute(
                    "UPDATE payments SET status = 'completed' WHERE booking_id = ?",
                    [(int)$booking['id']],
                    'i'
                );
            }

            $db->commit();

            $this->auditRepo->log($userId, 'booking.payment_success', 'Bookings', "Online payment confirmed for booking #{$bookingRef}");

            // Push Notification trigger for Customer, Court Owner, and Admin
            \App\Infrastructure\Services\PushNotificationService::sendBookingConfirmation(
                $userId,
                $bookingRef,
                $booking['facility_name'] ?? 'Facility',
                $booking['court_name'] ?? 'Court',
                $booking['booking_date'] ?? date('Y-m-d'),
                $booking['start_time'] ?? '00:00:00',
                $booking['end_time'] ?? '00:00:00',
                (float)($booking['total_amount'] ?? 0),
                $booking['payment_method'] ?? 'online'
            );

            // Email Notification trigger based on account settings
            \App\Infrastructure\Services\EmailNotificationService::sendBookingConfirmation(
                $userId,
                $bookingRef,
                $booking['facility_name'] ?? 'Facility',
                $booking['court_name'] ?? 'Court',
                $booking['booking_date'] ?? date('Y-m-d'),
                $booking['start_time'] ?? '00:00:00',
                $booking['end_time'] ?? '00:00:00',
                (float)($booking['total_amount'] ?? 0),
                $booking['payment_method'] ?? 'online'
            );

            return $this->bookingRepo->findByReference($bookingRef);
        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }
    }
}
