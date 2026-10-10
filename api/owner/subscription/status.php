<?php
require_once __DIR__ . '/../../../app/bootstrap.php';

use App\Core\Auth\Auth;
use App\Core\Http\Response;
use App\Infrastructure\Repositories\SubscriptionPlanRepository;
use App\Infrastructure\Repositories\OrganizationRepository;

Auth::requireAuth();

$user   = Auth::user();
$userId = is_array($user) ? (int)($user['id'] ?? 0) : 0;
$orgId  = is_array($user) ? (int)($user['organization_id'] ?? 0) : 0;

if (!$orgId && $userId > 0) {
    $orgRepo = new OrganizationRepository();
    $org = $orgRepo->findByOwnerId($userId);
    if ($org) {
        $orgId = (int)$org['id'];
    }
}

$repo = new SubscriptionPlanRepository();
$summary = $repo->getTenantSubscriptionSummary($orgId, false);
$sub = $summary['subscription'] ?? null;
$plans = $repo->getAllPlans();
$hasUsedFreeTrial = $repo->hasUsedFreeTrial($orgId, $userId);
foreach ($plans as &$plan) {
    $plan['trial_eligible'] = (int)($plan['is_free_trial'] ?? 0) === 1 && !$hasUsedFreeTrial;
}
unset($plan);

$hasSubscription = !empty($sub);
$status = strtolower($sub['status'] ?? 'none');
$endDate = $sub['end_date'] ?? ($sub['current_period_end'] ?? null);
$isExpired = false;
$daysRemaining = 0;

if ($endDate) {
    $endTimestamp = strtotime($endDate);
    $now = time();
    $daysRemaining = (int)ceil(($endTimestamp - $now) / 86400);
    if ($endTimestamp < $now) {
        $isExpired = true;
        $status = 'expired';
    }
} else if ($hasSubscription && !empty($sub['is_free_trial']) && !empty($sub['trial_end_date'])) {
    $endTimestamp = strtotime($sub['trial_end_date']);
    $now = time();
    $daysRemaining = (int)ceil(($endTimestamp - $now) / 86400);
    if ($endTimestamp < $now) {
        $isExpired = true;
        $status = 'expired';
    }
}

if (!$hasSubscription) {
    $status = 'none';
}

$isPastDue   = ($status === 'past_due');
$isCancelled = ($status === 'cancelled');

$isInactive = $hasSubscription && !in_array($status, ['active', 'trial', 'trialing'], true);
$requiresAction = (!$hasSubscription || $isExpired || $isPastDue || $isCancelled || $isInactive);

$actionReason = '';
if (!$hasSubscription) {
    $actionReason = 'No active organization subscription found. Please select a plan to activate facility management.';
} else if ($isExpired) {
    $actionReason = "Your subscription expired on {$endDate}. Renew now to maintain active court management and player bookings.";
} else if ($isPastDue) {
    $actionReason = 'Your subscription payment is past due. Please settle your invoice to avoid service interruption.';
} else if ($isCancelled) {
    $actionReason = 'Your subscription has been cancelled. Subscribe to a new plan to continue using Pikvero.';
} else if ($isInactive) {
    $actionReason = 'Your subscription is not active. Please select or renew a plan to continue.';
}

$usage = $summary['usage'] ?? ['facilities' => 0, 'courts' => 0, 'staff' => 0];
$maxFac = (int)($sub['max_facilities'] ?? 0);
$maxCourts = (int)($sub['max_courts'] ?? 0);
$maxStaff = (int)($sub['max_staff'] ?? 0);

$limitReached = [
    'facilities' => ($maxFac > 0 && $usage['facilities'] >= $maxFac),
    'courts'     => ($maxCourts > 0 && $usage['courts'] >= $maxCourts),
    'staff'      => ($maxStaff > 0 && $usage['staff'] >= $maxStaff),
];

Response::success('Subscription status evaluated.', [
    'has_subscription'    => $hasSubscription,
    'subscription_status' => $status,
    'subscription'        => $sub,
    'end_date'            => $endDate,
    'days_remaining'      => $daysRemaining,
    'is_expired'          => $isExpired,
    'is_past_due'         => $isPastDue,
    'is_cancelled'        => $isCancelled,
    'requires_action'     => $requiresAction,
    'action_reason'       => $actionReason,
    'usage'               => $usage,
    'limits'              => $limitReached,
    'plans'               => $plans
]);
