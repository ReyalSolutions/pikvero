<?php
namespace App\Presentation\Controllers;

use App\Infrastructure\Repositories\SubscriptionPaymentRepository;
use App\Core\Http\Response;
use App\Core\Http\Request;
use App\Core\Auth\Auth;

class SubscriptionPaymentController {
    private SubscriptionPaymentRepository $subRepo;

    public function __construct() {
        $this->subRepo = new SubscriptionPaymentRepository();
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

    public function getSubscriptionPayments(Request $request): void {
        $this->verifyPermission('subscription_payments.view', 'subscription_payments.manage', 'system.manage');

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

        $res = $this->subRepo->getPaginatedSubscriptionPayments(
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

    public function getSubscriptionPaymentDetail(Request $request): void {
        $this->verifyPermission('subscription_payments.view', 'subscription_payments.manage', 'system.manage');

        $paymentId = (int)($request->get('id') ?? $request->get('payment_id') ?? $request->get('sub_id') ?? 0);
        if (!$paymentId) {
            Response::error('Payment or Subscription ID is required.');
        }

        $orgId = $this->resolveOrganizationScope();
        $detail = $this->subRepo->getSubscriptionPaymentDetail($paymentId, $orgId);

        if (!$detail) {
            Response::error('Subscription payment record not found.');
        }

        Response::success('Subscription payment details retrieved.', $detail);
    }
}
