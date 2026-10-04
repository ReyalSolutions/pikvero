<?php
namespace App\Presentation\Controllers;

use App\Infrastructure\Repositories\PayoutRepository;
use App\Infrastructure\Repositories\AuditLogRepository;
use App\Core\Http\Response;
use App\Core\Http\Request;
use App\Core\Auth\Auth;
use Exception;

class PayoutController {
    private PayoutRepository $payoutRepo;
    private AuditLogRepository $auditRepo;

    public function __construct() {
        $this->payoutRepo = new PayoutRepository();
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

    /**
     * GET /api/admin/payouts.php
     * Paginated list of payouts + summary metrics
     */
    public function getPayouts(Request $request): void {
        $this->verifyPermission('payouts.view', 'payouts.request', 'payouts.manage', 'system.manage');

        $draw   = (int)($request->get('draw') ?? 1);
        $start  = (int)($request->get('start') ?? 0);
        $length = (int)($request->get('length') ?? 10);
        if ($length < 1) $length = 10;
        if ($length > 100) $length = 100;

        $searchArray   = $request->get('search');
        $search        = is_array($searchArray) ? (string)($searchArray['value'] ?? '') : (string)($request->get('search') ?? '');
        $orderArray    = $request->get('order');
        $orderColIndex = is_array($orderArray) ? (string)($orderArray[0]['column'] ?? '6') : '6';
        $orderDir      = is_array($orderArray) ? (string)($orderArray[0]['dir'] ?? 'DESC') : 'DESC';

        $statusFilter  = (string)($request->get('status') ?? 'all');
        $orgId         = $this->resolveOrganizationScope();

        $res = $this->payoutRepo->getPaginatedPayouts(
            $start,
            $length,
            $search,
            $orderColIndex,
            $orderDir,
            $orgId,
            $statusFilter
        );

        header('Content-Type: application/json');
        echo json_encode([
            'draw'            => $draw,
            'recordsTotal'    => $res['recordsTotal'],
            'recordsFiltered' => $res['recordsFiltered'],
            'data'            => $res['data'],
            'summary'         => $res['summary']
        ]);
        exit;
    }

    /**
     * GET /api/admin/payouts/gcash-bookings.php
     * Paginated list of successful GCash bookings contributing to revenue
     */
    public function getGcashBookings(Request $request): void {
        $this->verifyPermission('payouts.view', 'payouts.request', 'payouts.manage', 'system.manage');

        $draw   = (int)($request->get('draw') ?? 1);
        $start  = (int)($request->get('start') ?? 0);
        $length = (int)($request->get('length') ?? 10);
        if ($length < 1) $length = 10;
        if ($length > 100) $length = 100;

        $searchArray   = $request->get('search');
        $search        = is_array($searchArray) ? (string)($searchArray['value'] ?? '') : (string)($request->get('search') ?? '');
        $orderArray    = $request->get('order');
        $orderColIndex = is_array($orderArray) ? (string)($orderArray[0]['column'] ?? '3') : '3';
        $orderDir      = is_array($orderArray) ? (string)($orderArray[0]['dir'] ?? 'DESC') : 'DESC';

        $claimFilter = (string)($request->get('claim_filter') ?? 'all');
        $orgId = $this->resolveOrganizationScope();

        $res = $this->payoutRepo->getPaginatedGcashBookings(
            $start,
            $length,
            $search,
            $orderColIndex,
            $orderDir,
            $orgId,
            $claimFilter
        );

        header('Content-Type: application/json');
        echo json_encode([
            'draw'            => $draw,
            'recordsTotal'    => $res['recordsTotal'],
            'recordsFiltered' => $res['recordsFiltered'],
            'totalAmount'     => $res['totalAmount'] ?? 0,
            'totalFee'        => $res['totalFee'] ?? 0,
            'netAmount'       => $res['netAmount'] ?? 0,
            'data'            => $res['data']
        ]);
        exit;
    }

    /**
     * GET /api/admin/payouts/options.php
     * Fetch unclaimed booking options & packages
     */
    public function getUnclaimedOptions(Request $request): void {
        $this->verifyPermission('payouts.view', 'payouts.request', 'payouts.manage', 'system.manage');
        $orgId = $this->resolveOrganizationScope();
        $res = $this->payoutRepo->getUnclaimedBookingOptions($orgId);

        Response::json([
            'success' => true,
            'data'    => $res
        ]);
    }

    /**
     * POST /api/admin/payouts/request.php
     * Submit new GCash payout request
     */
    public function requestPayout(Request $request): void {
        $this->verifyPermission('payouts.request', 'payouts.manage', 'system.manage');

        $orgId = $this->resolveOrganizationScope();
        if (!$orgId || $orgId <= 0) {
            Response::error('Organization context is required to request a payout.');
        }

        $input         = $request->all();
        $accountName   = trim($input['account_name'] ?? $input['gcash_account_name'] ?? '');
        $accountNumber = trim($input['account_number'] ?? $input['gcash_account_number'] ?? '');
        $amount        = (float)($input['amount'] ?? 0);
        $notes         = trim($input['notes'] ?? '');

        if (empty($accountName) || strlen($accountName) < 2) {
            Response::error('GCash account name must be at least 2 characters.');
        }
        
        $phoneClean = preg_replace('/[\s\-]/', '', $accountNumber);
        if (empty($accountNumber) || (!preg_match('/^(09|\+639)\d{9}$/', $phoneClean) && !preg_match('/^\d{11}$/', $phoneClean))) {
            Response::error('Please enter a valid 11-digit GCash mobile number (e.g. 09171234567).');
        }

        if ($amount <= 0) {
            Response::error('Requested payout amount must be greater than ₱0.00.');
        }

        try {
            $userId = Auth::id();
            $res = $this->payoutRepo->createPayoutRequest(
                $orgId,
                $userId,
                $accountName,
                $accountNumber,
                $amount,
                $notes
            );

            $this->auditRepo->log(
                $userId,
                'payout.request',
                'Payout',
                "Requested GCash payout of ₱" . number_format($amount, 2) . " (Ref: {$res['reference_no']}) to account {$accountName} ({$accountNumber})"
            );

            // Trigger Push & Email Notifications to Court Owner and System Admins
            $payoutId = (int)($res['id'] ?? 0);
            $refNo = $res['reference_no'] ?? 'PO-NEW';
            \App\Infrastructure\Services\PushNotificationService::sendPayoutRequestNotification(
                $userId,
                $payoutId,
                $refNo,
                $amount,
                $accountName,
                $accountNumber
            );
            \App\Infrastructure\Services\EmailNotificationService::sendPayoutRequestConfirmation(
                $userId,
                $payoutId,
                $refNo,
                $amount,
                $accountName,
                $accountNumber
            );

            Response::success('GCash payout request submitted successfully!', $res);
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }

    /**
     * POST /api/admin/payouts/update-status.php
     * Update payout request status (Approve, Complete, Reject)
     */
    public function updatePayoutStatus(Request $request): void {
        $this->verifyPermission('payouts.manage', 'system.manage');

        $input      = $request->all();
        $payoutId   = (int)($input['payout_id'] ?? $input['id'] ?? 0);
        $status     = strtolower(trim($input['status'] ?? 'approved'));
        $adminNotes = trim($input['admin_notes'] ?? $input['notes'] ?? '');

        if (!$payoutId) {
            Response::error('Payout ID is required.');
        }

        $receiptImagePath = null;
        if (isset($_FILES['receipt_image']) && $_FILES['receipt_image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['receipt_image'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
            if (!in_array($ext, $allowedExts)) {
                Response::error('Invalid file type. Please upload a JPG, PNG, WEBP image or PDF receipt.');
            }

            $uploadDir = __DIR__ . '/../../../public/storage/uploads/payout_receipts';
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0777, true);
            }

            $fileName = 'receipt_PO_' . $payoutId . '_' . time() . '.' . $ext;
            $targetPath = $uploadDir . '/' . $fileName;

            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                $receiptImagePath = '/pikvero/public/storage/uploads/payout_receipts/' . $fileName;
            }
        }

        try {
            $adminUserId = Auth::id();
            $orgId = $this->resolveOrganizationScope();

            // Fetch payout details prior to update for notification dispatch
            $detail = $this->payoutRepo->getPayoutDetail($payoutId, null);

            if ($status === 'completed' && empty($receiptImagePath) && empty($detail['receipt_image'])) {
                Response::error('Please upload a GCash transfer receipt / payment proof when marking a payout as Completed.');
            }

            $this->payoutRepo->updatePayoutStatus($payoutId, $status, $adminNotes, $adminUserId, $orgId, $receiptImagePath);

            $this->auditRepo->log(
                $adminUserId,
                'payout.update_status',
                'Payout',
                "Updated payout ID #{$payoutId} status to '{$status}'"
            );

            if ($detail) {
                $ownerUserId = (int)($detail['requested_by'] ?? $detail['user_id'] ?? 0);
                if ($ownerUserId <= 0 && !empty($detail['owner_id'])) {
                    $ownerUserId = (int)$detail['owner_id'];
                }
                $refNo   = $detail['reference_no'] ?? ("PO-" . sprintf('%05d', $payoutId));
                $amount  = (float)($detail['amount'] ?? 0);
                $accName = $detail['gcash_account_name'] ?? $detail['account_name'] ?? '';
                $accNum  = $detail['gcash_account_number'] ?? $detail['account_number'] ?? '';

                if ($ownerUserId > 0) {
                    \App\Infrastructure\Services\PushNotificationService::sendPayoutStatusUpdateNotification(
                        $ownerUserId,
                        $payoutId,
                        $refNo,
                        $amount,
                        $status,
                        $adminNotes
                    );
                    \App\Infrastructure\Services\EmailNotificationService::sendPayoutStatusUpdate(
                        $ownerUserId,
                        $payoutId,
                        $refNo,
                        $amount,
                        $status,
                        $adminNotes,
                        $accName,
                        $accNum
                    );
                }
            }

            Response::success("Payout request status updated to " . strtoupper($status) . " successfully!");
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }

    /**
     * GET /api/admin/payouts/detail.php
     * Single payout request details
     */
    public function getPayoutDetail(Request $request): void {
        $this->verifyPermission('payouts.view', 'payouts.request', 'payouts.manage', 'system.manage');

        $payoutId = (int)($request->get('id') ?? $request->get('payout_id') ?? 0);
        if (!$payoutId) {
            Response::error('Payout ID is required.');
        }

        $orgId = $this->resolveOrganizationScope();
        $detail = $this->payoutRepo->getPayoutDetail($payoutId, $orgId);

        if (!$detail) {
            Response::error('Payout request transaction not found.');
        }

        Response::success('Payout details retrieved successfully.', $detail);
    }
}
