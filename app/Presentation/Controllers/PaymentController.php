<?php
namespace App\Presentation\Controllers;

use App\Infrastructure\Repositories\PaymentRepository;
use App\Infrastructure\Repositories\AuditLogRepository;
use App\Core\Http\Response;
use App\Core\Http\Request;
use App\Core\Auth\Auth;

class PaymentController {
    private PaymentRepository $paymentRepo;
    private AuditLogRepository $auditRepo;

    public function __construct() {
        $this->paymentRepo = new PaymentRepository();
        $this->auditRepo = new AuditLogRepository();
    }

    private function verifyPermission(string ...$requiredPermissions): void {
        if (!Auth::check()) {
            Response::unauthorized('Authentication required.');
        }

        if (Auth::hasRole('super_admin', 'platform_admin')) {
            return;
        }

        if (!empty($requiredPermissions)) {
            if (Auth::hasPermission(...$requiredPermissions)) {
                return;
            }
            Response::forbidden('Permission denied. Required permission: ' . implode(', ', $requiredPermissions));
        }

        if (Auth::hasRole('court_owner', 'facility_manager', 'receptionist', 'finance_admin')) {
            return;
        }

        Response::forbidden('Access denied.');
    }

    private function resolveOrganizationScope(): ?int {
        if (Auth::hasRole('super_admin', 'platform_admin')) {
            return null; // Platform wide view
        }

        $userId = Auth::id();
        if (!$userId) return null;

        $orgId = Auth::organizationId();
        if (!$orgId || $orgId <= 0) {
            $db = \App\Core\Database\Connection::getInstance();
            $org = $db->selectOne("SELECT id FROM organizations WHERE owner_id = ? LIMIT 1", [$userId]);
            if ($org) {
                $orgId = (int)$org['id'];
            }
        }
        return $orgId;
    }

    public function getPayments(Request $request): void {
        $this->verifyPermission('payments.view', 'payment.view', 'payments.manage', 'system.manage');

        $draw = (int)($request->get('draw') ?? 1);
        $start = (int)($request->get('start') ?? 0);
        $length = (int)($request->get('length') ?? 10);
        if ($length < 1) $length = 10;
        if ($length > 100) $length = 100;

        $searchArray = $request->get('search');
        $search = is_array($searchArray) ? (string)($searchArray['value'] ?? '') : (string)($request->get('search') ?? '');

        $orderArray = $request->get('order');
        $orderColIndex = is_array($orderArray) ? (string)($orderArray[0]['column'] ?? '0') : '0';
        $orderDir = is_array($orderArray) ? (string)($orderArray[0]['dir'] ?? 'DESC') : 'DESC';

        $statusFilter = (string)($request->get('status') ?? 'all');
        $methodFilter = (string)($request->get('method') ?? 'all');
        $startDate    = (string)($request->get('start_date') ?? '');
        $endDate      = (string)($request->get('end_date') ?? '');

        $orgId = $this->resolveOrganizationScope();

        $res = $this->paymentRepo->getPaginatedPayments(
            $start,
            $length,
            $search,
            $orderColIndex,
            $orderDir,
            $orgId,
            $statusFilter,
            $methodFilter,
            $startDate,
            $endDate
        );

        header('Content-Type: application/json');
        echo json_encode([
            'draw' => $draw,
            'recordsTotal' => $res['recordsTotal'],
            'recordsFiltered' => $res['recordsFiltered'],
            'data' => $res['data'],
            'summary' => $res['summary']
        ]);
        exit;
    }

    public function getPaymentDetail(Request $request): void {
        $this->verifyPermission('payments.view', 'payment.view', 'payments.manage', 'system.manage');

        $paymentId = (int)($request->get('id') ?? 0);
        if (!$paymentId) {
            Response::error('Payment ID is required.');
        }

        $orgId = $this->resolveOrganizationScope();
        $detail = $this->paymentRepo->getPaymentDetail($paymentId, $orgId);

        if (!$detail) {
            Response::error('Payment record not found.');
        }

        Response::success('Payment details retrieved.', $detail);
    }

    public function processRefund(Request $request): void {
        $this->verifyPermission('payment.refund', 'payments.manage', 'system.manage');

        $data = $request->all();
        $paymentId = (int)($data['payment_id'] ?? 0);
        $reason = trim($data['reason'] ?? 'Customer request');

        if (!$paymentId) {
            Response::error('Payment ID is required for refund processing.');
        }

        $orgId = $this->resolveOrganizationScope();
        $detail = $this->paymentRepo->getPaymentDetail($paymentId, $orgId);

        if (!$detail) {
            Response::error('Payment transaction not found or unauthorized.');
        }

        if ($detail['payment_status'] === 'failed' || $detail['booking_payment_status'] === 'refunded') {
            Response::error('This transaction has already been refunded or marked as failed.');
        }

        $success = $this->paymentRepo->refundPayment($paymentId, $reason, $orgId);

        if ($success) {
            $formattedAmount = number_format((float)$detail['amount'], 2);
            $this->auditRepo->log(
                Auth::id(),
                'payment.refund',
                'Payment',
                "Processed refund of ₱{$formattedAmount} for booking ref #{$detail['booking_reference']} (Payment ID #{$paymentId}). Reason: {$reason}"
            );

            $customerId = (int)($detail['customer_id'] ?? $detail['user_id'] ?? 0);
            $bookingRef = $detail['booking_reference'] ?? "ID #{$paymentId}";
            $facilityName = $detail['facility_name'] ?? 'Facility';
            $courtName = $detail['court_name'] ?? 'Court';
            $bookingDate = $detail['booking_date'] ?? date('Y-m-d');
            $startTime = $detail['start_time'] ?? '00:00:00';
            $endTime = $detail['end_time'] ?? '00:00:00';
            $refundAmount = (float)($detail['amount'] ?? 0);

            if ($customerId > 0) {
                \App\Infrastructure\Services\PushNotificationService::sendRefundNotification(
                    $customerId,
                    $bookingRef,
                    $facilityName,
                    $courtName,
                    $refundAmount,
                    (string)$reason
                );
                \App\Infrastructure\Services\EmailNotificationService::sendRefundConfirmation(
                    $customerId,
                    $bookingRef,
                    $facilityName,
                    $courtName,
                    $bookingDate,
                    $startTime,
                    $endTime,
                    $refundAmount,
                    (string)$reason
                );
            }

            Response::success("Refund of ₱{$formattedAmount} processed successfully for booking ref #{$detail['booking_reference']}.");
        } else {
            Response::error('Failed to process payment refund. Please try again.');
        }
    }
}
