<?php
namespace App\Presentation\Controllers;

use App\Core\Auth\Auth;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Validation\Validator;
use App\Infrastructure\Repositories\SubscriptionPlanRepository;

class SubscriptionController {
    private SubscriptionPlanRepository $repo;

    public function __construct() {
        Auth::requireAuth();
        $this->repo = new SubscriptionPlanRepository();
    }

    public function getPlans(): void {
        if (!Auth::hasPermission('subscriptions.view', 'subscriptions.manage', 'subscription.view', 'subscription.manage', 'plans.manage', 'system.manage')) {
            Response::forbidden('Permission denied: You do not have permission to view subscription plans.');
        }

        $plans = $this->repo->getAllPlans();
        Response::success('Subscription plans retrieved successfully.', $plans);
    }

    public function getPlanDetail(Request $request): void {
        if (!Auth::hasPermission('subscriptions.view', 'subscriptions.manage', 'subscription.view', 'subscription.manage', 'plans.manage', 'system.manage')) {
            Response::forbidden('Permission denied: You do not have permission to view subscription plan details.');
        }

        $id = (int)($request->get('id') ?? 0);
        if (!$id) Response::error('Plan ID is required.');

        $plan = $this->repo->findById($id);
        if (!$plan) Response::error('Subscription plan not found.');

        Response::success('Subscription plan details.', $plan);
    }

    public function createPlan(Request $request): void {
        if (!Auth::hasPermission('subscriptions.manage', 'subscription.manage', 'plans.manage', 'system.manage')) {
            Response::forbidden('Permission denied: You do not have permission to create subscription plans.');
        }

        $data = $request->all();
        $isFreeTrial = !empty($data['is_free_trial']);

        $rules = [
            'name' => 'required|min:2|max:50',
            'max_facilities' => 'required|numeric',
            'max_courts' => 'required|numeric',
            'max_staff' => 'required|numeric'
        ];
        if (!$isFreeTrial) {
            $rules['monthly_price'] = 'required|numeric';
        }

        $validator = new Validator();
        if (!$validator->validate($data, $rules)) {
            Response::error('Validation failed', $validator->errors());
        }

        $planId = $this->repo->createPlan($data);
        Response::success('Subscription plan created successfully.', ['id' => $planId]);
    }

    public function updatePlan(Request $request): void {
        if (!Auth::hasPermission('subscriptions.manage', 'subscription.manage', 'plans.manage', 'system.manage')) {
            Response::forbidden('Permission denied: You do not have permission to edit subscription plans.');
        }

        $data = $request->all();
        $id = (int)($data['id'] ?? $request->get('id') ?? 0);
        if (!$id) Response::error('Plan ID is required.');

        $existing = $this->repo->findById($id);
        if (!$existing) Response::error('Subscription plan not found.');

        $isFreeTrial = !empty($data['is_free_trial']);
        $rules = [
            'name' => 'required|min:2|max:50',
            'max_facilities' => 'required|numeric',
            'max_courts' => 'required|numeric',
            'max_staff' => 'required|numeric'
        ];
        if (!$isFreeTrial) {
            $rules['monthly_price'] = 'required|numeric';
        }

        $validator = new Validator();
        if (!$validator->validate($data, $rules)) {
            Response::error('Validation failed', $validator->errors());
        }

        $this->repo->updatePlan($id, $data);
        Response::success('Subscription plan updated successfully.');
    }

    public function deletePlan(Request $request): void {
        if (!Auth::hasPermission('subscriptions.manage', 'subscription.manage', 'plans.manage', 'system.manage')) {
            Response::forbidden('Permission denied: You do not have permission to delete subscription plans.');
        }

        $data = $request->all();
        $id = (int)($data['id'] ?? $request->get('id') ?? 0);
        if (!$id) Response::error('Plan ID is required.');

        $existing = $this->repo->findById($id);
        if (!$existing) Response::error('Subscription plan not found.');

        if ((int)$existing['active_subscribers'] > 0) {
            Response::error('Cannot delete plan: Active tenant organizations are currently subscribed to this plan.');
        }

        $this->repo->deletePlan($id);
        Response::success('Subscription plan deleted successfully.');
    }

    public function getActiveSubscriptions(): void {
        if (!Auth::hasPermission('subscriptions.view', 'subscriptions.manage', 'subscription.view', 'subscription.manage', 'plans.manage', 'system.manage')) {
            Response::forbidden('Permission denied: You do not have permission to view active subscriptions.');
        }

        if (isset($_GET['draw'])) {
            $result = $this->repo->getActiveSubscriptionsPaginated($_GET);
            header('Content-Type: application/json');
            echo json_encode($result);
            exit;
        }

        $subs = $this->repo->getActiveSubscriptions();
        Response::success('Active subscriptions retrieved.', $subs);
    }

    public function getMySubscription(): void {
        Auth::requireAuth();

        $user   = Auth::user();
        $userId = is_array($user) ? (int)($user['id'] ?? 0) : 0;
        $orgId  = is_array($user) ? (int)($user['organization_id'] ?? 0) : 0;

        if (!$orgId && $userId > 0) {
            $orgRepo = new \App\Infrastructure\Repositories\OrganizationRepository();
            $org = $orgRepo->findByOwnerId($userId);
            if ($org) $orgId = (int)$org['id'];
        }

        $summary = $this->repo->getTenantSubscriptionSummary($orgId);
        Response::success('Owner subscription summary.', $summary);
    }

    public function getMyPaymentHistory(): void {
        Auth::requireAuth();

        $user   = Auth::user();
        $userId = is_array($user) ? (int)($user['id'] ?? 0) : 0;
        $orgId  = is_array($user) ? (int)($user['organization_id'] ?? 0) : 0;

        if (!$orgId && $userId > 0) {
            $orgRepo = new \App\Infrastructure\Repositories\OrganizationRepository();
            $org = $orgRepo->findByOwnerId($userId);
            if ($org) $orgId = (int)$org['id'];
        }

        if (isset($_GET['draw'])) {
            $result = $this->repo->getTenantPaymentHistoryPaginated($orgId, $_GET);
            header('Content-Type: application/json');
            echo json_encode($result);
            exit;
        }

        $payments = $this->repo->getTenantPaymentHistoryPaginated($orgId, ['draw' => 1, 'start' => 0, 'length' => 10]);
        Response::success('Owner payment history retrieved.', $payments);
    }

    public function subscribeToPlan(Request $request): void {
        Auth::requireAuth();

        $user = Auth::user();
        $orgId = (int)($user['organization_id'] ?? 0);

        if (!$orgId) {
            $orgRepo = new \App\Infrastructure\Repositories\OrganizationRepository();
            $org = $orgRepo->findByOwnerId((int)$user['id']);
            if ($org) $orgId = (int)$org['id'];
        }

        if (!$orgId) Response::error('No organization associated with this account.');

        $data = $request->all();
        $planId = (int)($data['plan_id'] ?? 0);
        $cycle  = trim($data['billing_cycle'] ?? 'monthly');
        $ref    = trim($data['payment_ref'] ?? '');
        $method = trim($data['payment_method'] ?? 'PayMongo');

        if (!$planId && !empty($data['plan_id'])) {
            $plan = $this->repo->findBySlug(trim((string)$data['plan_id']));
            $planId = (int)($plan['id'] ?? 0);
        }
        if (!$planId) Response::error('A valid subscription plan is required.');

        $paymentNote = !empty($ref) ? "PayMongo ({$method}) Ref #{$ref}" : '';

        $res = $this->repo->subscribeTenantToPlan($orgId, $planId, $cycle, $paymentNote);
        if (!empty($res['success'])) {
            Response::success($res['message'] ?? 'Successfully subscribed to plan!');
        } else {
            Response::error($res['message'] ?? 'Failed to change subscription plan.');
        }
    }

    public function cancelSubscription(): void {
        Auth::requireAuth();

        $user   = Auth::user();
        $userId = is_array($user) ? (int)($user['id'] ?? 0) : 0;
        $orgId  = is_array($user) ? (int)($user['organization_id'] ?? 0) : 0;

        if (!$orgId && $userId > 0) {
            $orgRepo = new \App\Infrastructure\Repositories\OrganizationRepository();
            $org = $orgRepo->findByOwnerId($userId);
            if ($org) $orgId = (int)$org['id'];
        }

        if (!$orgId) Response::error('No organization profile found for this user.');

        $success = $this->repo->cancelTenantSubscription($orgId);
        if ($success) {
            Response::success('Subscription cancelled successfully. Plan perks remain accessible until period end.');
        } else {
            Response::error('No active subscription found to cancel.');
        }
    }

    public function bypassUpgradeSubscription(Request $request): void {
        if (!Auth::hasPermission('subscriptions.manage', 'plans.manage', 'system.manage') && !Auth::hasRole('super_admin', 'platform_admin')) {
            Response::forbidden('Permission denied: Required permission subscriptions.manage or system.manage');
        }

        $subId = (int)($request->get('subscription_id') ?? $request->get('id') ?? 0);
        $planId = (int)($request->get('plan_id') ?? 0);
        $cycle = (string)($request->get('billing_cycle') ?? 'monthly');

        if (!$subId || !$planId) {
            Response::error('Subscription ID and Plan ID are required.');
        }

        $res = $this->repo->bypassUpgradeSubscription($subId, $planId, $cycle);
        if (!empty($res['success'])) {
            Response::success($res['message'], $res);
        } else {
            Response::error($res['message'] ?? 'Failed to perform bypass upgrade.');
        }
    }
}
