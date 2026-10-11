<?php
namespace App\Infrastructure\Repositories;

use App\Core\Database\Connection;

class SubscriptionPlanRepository {
    private Connection $db;

    public function __construct() {
        $this->db = Connection::getInstance();
    }

    public function getAllPlans(): array {
        $plans = $this->db->select("
            SELECT sp.*,
                   (SELECT COUNT(*) FROM subscriptions s WHERE s.plan_id = sp.id AND s.status = 'active') AS active_subscribers
            FROM subscription_plans sp
            ORDER BY sp.monthly_price ASC, sp.id ASC
        ");

        foreach ($plans as &$p) {
            $p['features'] = $this->getPlanFeatures((int)$p['id']);
        }
        return $plans;
    }

    public function findById(int $id): ?array {
        $plan = $this->db->selectOne("
            SELECT sp.*,
                   (SELECT COUNT(*) FROM subscriptions s WHERE s.plan_id = sp.id AND s.status = 'active') AS active_subscribers
            FROM subscription_plans sp
            WHERE sp.id = ? LIMIT 1
        ", [$id], 'i');

        if ($plan) {
            $plan['features'] = $this->getPlanFeatures((int)$plan['id']);
        }
        return $plan;
    }

    public function findBySlug(string $slug): ?array {
        return $this->db->selectOne("SELECT * FROM subscription_plans WHERE slug = ? LIMIT 1", [$slug], 's');
    }

    public function getPlanFeatures(int $planId): array {
        return $this->db->select("SELECT id, feature FROM subscription_plan_features WHERE plan_id = ? ORDER BY id ASC", [$planId], 'i');
    }

    public function createPlan(array $data): int {
        $slug = !empty($data['slug']) ? strtolower(trim($data['slug'])) : strtolower(str_replace(' ', '-', trim($data['name'])));
        $isFreeTrial = !empty($data['is_free_trial']) ? 1 : 0;
        $trialMonths = isset($data['trial_duration_months']) ? max(1, (int)$data['trial_duration_months']) : 1;
        $trialStart  = !empty($data['trial_start_date']) ? $data['trial_start_date'] : null;
        $trialEnd    = !empty($data['trial_end_date']) ? $data['trial_end_date'] : null;
        $monthlyPrice = (float)($data['monthly_price'] ?? 0);
        $yearlyPrice  = (isset($data['yearly_price']) && (float)$data['yearly_price'] > 0 ? (float)$data['yearly_price'] : round($monthlyPrice * 12 * 0.70, 2));

        $sql = "INSERT INTO subscription_plans (name, slug, monthly_price, yearly_price, is_free_trial, trial_duration_months, trial_start_date, trial_end_date, max_facilities, max_courts, max_staff, description)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $this->db->execute($sql, [
            trim($data['name']),
            $slug,
            $monthlyPrice,
            $yearlyPrice,
            $isFreeTrial,
            $trialMonths,
            $trialStart,
            $trialEnd,
            (int)$data['max_facilities'],
            (int)$data['max_courts'],
            (int)$data['max_staff'],
            $data['description'] ?? null
        ], 'ssddiissiiis');

        $planId = $this->db->getLastInsertId();

        if (!empty($data['features']) && is_array($data['features'])) {
            $this->setPlanFeatures($planId, $data['features']);
        }

        return $planId;
    }

    public function updatePlan(int $id, array $data): bool {
        $slug = !empty($data['slug']) ? strtolower(trim($data['slug'])) : strtolower(str_replace(' ', '-', trim($data['name'])));
        $isFreeTrial = !empty($data['is_free_trial']) ? 1 : 0;
        $trialMonths = isset($data['trial_duration_months']) ? max(1, (int)$data['trial_duration_months']) : 1;
        $trialStart  = !empty($data['trial_start_date']) ? $data['trial_start_date'] : null;
        $trialEnd    = !empty($data['trial_end_date']) ? $data['trial_end_date'] : null;
        $monthlyPrice = (float)($data['monthly_price'] ?? 0);
        $yearlyPrice  = (isset($data['yearly_price']) && (float)$data['yearly_price'] > 0 ? (float)$data['yearly_price'] : round($monthlyPrice * 12 * 0.70, 2));

        $sql = "UPDATE subscription_plans 
                SET name = ?, slug = ?, monthly_price = ?, yearly_price = ?, is_free_trial = ?, trial_duration_months = ?, trial_start_date = ?, trial_end_date = ?, max_facilities = ?, max_courts = ?, max_staff = ?, description = ?
                WHERE id = ?";
        $res = $this->db->execute($sql, [
            trim($data['name']),
            $slug,
            $monthlyPrice,
            $yearlyPrice,
            $isFreeTrial,
            $trialMonths,
            $trialStart,
            $trialEnd,
            (int)$data['max_facilities'],
            (int)$data['max_courts'],
            (int)$data['max_staff'],
            $data['description'] ?? null,
            $id
        ], 'ssddiissiiisi');

        if (isset($data['features']) && is_array($data['features'])) {
            $this->setPlanFeatures($id, $data['features']);
        }

        return $res;
    }

    public function deletePlan(int $id): bool {
        $this->db->execute("DELETE FROM subscription_plan_features WHERE plan_id = ?", [$id], 'i');
        return $this->db->execute("DELETE FROM subscription_plans WHERE id = ?", [$id], 'i');
    }

    public function setPlanFeatures(int $planId, array $features): void {
        $this->db->execute("DELETE FROM subscription_plan_features WHERE plan_id = ?", [$planId], 'i');

        foreach ($features as $f) {
            $featureStr = is_array($f) ? ($f['feature'] ?? '') : (string)$f;
            $featureStr = trim($featureStr);
            if (!empty($featureStr)) {
                $this->db->execute("INSERT INTO subscription_plan_features (plan_id, feature) VALUES (?, ?)", [$planId, $featureStr], 'is');
            }
        }
    }

    public function getActiveSubscriptions(): array {
        $sql = "SELECT s.*, o.name AS organization_name, sp.name AS plan_name, sp.monthly_price
                FROM subscriptions s
                JOIN organizations o ON s.organization_id = o.id
                JOIN subscription_plans sp ON s.plan_id = sp.id
                ORDER BY s.id DESC";
        return $this->db->select($sql);
    }

    public function getActiveSubscriptionsPaginated(array $params): array {
        $draw   = (int)($params['draw'] ?? 1);
        $start  = (int)($params['start'] ?? 0);
        $length = (int)($params['length'] ?? 10);
        $search = trim($params['search']['value'] ?? '');

        // Base total count
        $totalRow = $this->db->selectOne("SELECT COUNT(*) AS total FROM subscriptions");
        $recordsTotal = (int)($totalRow['total'] ?? 0);

        $whereClause = " WHERE 1=1";
        $queryParams = [];
        $types = "";

        if (!empty($search)) {
            $whereClause .= " AND (o.name LIKE ? OR sp.name LIKE ? OR s.status LIKE ?)";
            $searchTerm = "%{$search}%";
            $queryParams = [$searchTerm, $searchTerm, $searchTerm];
            $types = "sss";
        }

        // Filtered count
        $countSql = "SELECT COUNT(*) AS filtered 
                     FROM subscriptions s 
                     JOIN organizations o ON s.organization_id = o.id 
                     JOIN subscription_plans sp ON s.plan_id = sp.id" . $whereClause;
        $filteredRow = !empty($types) ? $this->db->selectOne($countSql, $queryParams, $types) : $this->db->selectOne($countSql);
        $recordsFiltered = (int)($filteredRow['filtered'] ?? 0);

        // Sorting mapping
        $columns = [
            0 => 'o.name',
            1 => 'sp.name',
            2 => 'sp.monthly_price',
            3 => 's.status',
            4 => 's.current_period_end'
        ];
        $colIdx = (int)($params['order'][0]['column'] ?? 0);
        $orderDir = strtolower($params['order'][0]['dir'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';
        $orderByCol = $columns[$colIdx] ?? 's.id';

        // Query data
        $dataSql = "SELECT s.*, o.name AS organization_name, sp.name AS plan_name, sp.monthly_price, sp.yearly_price,
                           (SELECT id FROM subscription_payments WHERE subscription_id = s.id ORDER BY id DESC LIMIT 1) AS last_payment_id
                    FROM subscriptions s
                    JOIN organizations o ON s.organization_id = o.id
                    JOIN subscription_plans sp ON s.plan_id = sp.id
                    {$whereClause}
                    ORDER BY {$orderByCol} {$orderDir}
                    LIMIT ?, ?";
        
        $queryParams[] = $start;
        $queryParams[] = $length;
        $types .= "ii";

        $rows = $this->db->select($dataSql, $queryParams, $types);

        return [
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $rows
        ];
    }

    public function getTenantPaymentHistoryPaginated(int $orgId, array $params): array {
        $draw   = (int)($params['draw'] ?? 1);
        $start  = (int)($params['start'] ?? 0);
        $length = (int)($params['length'] ?? 10);
        $search = trim($params['search']['value'] ?? '');

        // Fetch subscription IDs associated with this tenant organization
        $sub = $this->db->selectOne("SELECT id FROM subscriptions WHERE organization_id = ? ORDER BY id DESC LIMIT 1", [$orgId], 'i');
        if (!$sub) {
            return [
                'draw' => $draw,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => []
            ];
        }
        $subId = (int)$sub['id'];

        $whereClause = "WHERE subscription_id = ?";
        $queryParams = [$subId];
        $types = 'i';

        if (!empty($search)) {
            $whereClause .= " AND (payment_method LIKE ? OR payment_status LIKE ? OR amount LIKE ?)";
            $searchTerm = "%{$search}%";
            $queryParams[] = $searchTerm;
            $queryParams[] = $searchTerm;
            $queryParams[] = $searchTerm;
            $types .= 'sss';
        }

        $countRow = $this->db->selectOne("SELECT COUNT(*) AS total FROM subscription_payments WHERE subscription_id = ?", [$subId], 'i');
        $recordsTotal = (int)($countRow['total'] ?? 0);

        $filteredCountRow = $this->db->selectOne("SELECT COUNT(*) AS total FROM subscription_payments {$whereClause}", $queryParams, $types);
        $recordsFiltered = (int)($filteredCountRow['total'] ?? 0);

        $start  = max(0, $start);
        $length = max(1, min(100, $length));

        $sql = "SELECT id, subscription_id, amount, payment_method, payment_status, created_at
                FROM subscription_payments
                {$whereClause}
                ORDER BY id DESC
                LIMIT {$start}, {$length}";

        $rows = $this->db->select($sql, $queryParams, $types);

        return [
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $rows
        ];
    }

    public function hasUsedFreeTrial(int $orgId = 0, int $ownerId = 0): bool {
        if ($orgId <= 0 && $ownerId <= 0) return false;
        if ($ownerId <= 0) {
            $org = $this->db->selectOne("SELECT owner_id FROM organizations WHERE id = ?", [$orgId], 'i');
            $ownerId = (int)($org['owner_id'] ?? 0);
        }
        $used = $this->db->selectOne("
            SELECT sp.id FROM subscription_payments sp
            JOIN subscriptions s ON s.id = sp.subscription_id
            JOIN organizations o ON o.id = s.organization_id
            JOIN subscription_plans p ON p.id = s.plan_id
            WHERE (o.id = ? OR o.owner_id = ?)
              AND sp.payment_status IN ('paid', 'completed')
              AND (LOWER(sp.payment_method) LIKE '%free_trial%'
                   OR LOWER(sp.payment_method) LIKE '%free trial%'
                   OR (sp.amount = 0 AND p.is_free_trial = 1))
            LIMIT 1
        ", [$orgId, $ownerId], 'ii');
        return !empty($used);
    }

    public function getTenantSubscriptionSummary(int $orgId = 0, bool $allowFallback = true): array {
        $sub = null;

        if ($orgId > 0) {
            $sub = $this->db->selectOne("
                SELECT s.*, sp.name AS plan_name, sp.slug AS plan_slug, sp.monthly_price, sp.yearly_price,
                       sp.max_facilities, sp.max_courts, sp.max_staff, sp.description AS plan_description,
                       sp.is_free_trial, sp.trial_duration_months, sp.trial_start_date, sp.trial_end_date
                FROM subscriptions s
                JOIN subscription_plans sp ON s.plan_id = sp.id
                WHERE s.organization_id = ?
                ORDER BY s.id DESC LIMIT 1
            ", [$orgId], 'i');

            if (!$sub && $allowFallback) {
                $starter = $this->db->selectOne("SELECT id FROM subscription_plans ORDER BY id ASC LIMIT 1");
                if ($starter) {
                    $this->subscribeTenantToPlan($orgId, (int)$starter['id'], 'monthly');
                    $sub = $this->db->selectOne("
                        SELECT s.*, sp.name AS plan_name, sp.slug AS plan_slug, sp.monthly_price, sp.yearly_price,
                               sp.max_facilities, sp.max_courts, sp.max_staff, sp.description AS plan_description,
                               sp.is_free_trial, sp.trial_duration_months, sp.trial_start_date, sp.trial_end_date
                        FROM subscriptions s
                        JOIN subscription_plans sp ON s.plan_id = sp.id
                        WHERE s.organization_id = ?
                        ORDER BY s.id DESC LIMIT 1
                    ", [$orgId], 'i');
                }
            }
        }

        if (!$sub && $allowFallback) {
            $sub = $this->db->selectOne("
                SELECT s.*, sp.name AS plan_name, sp.slug AS plan_slug, sp.monthly_price, sp.yearly_price,
                       sp.max_facilities, sp.max_courts, sp.max_staff, sp.description AS plan_description,
                       sp.is_free_trial, sp.trial_duration_months, sp.trial_start_date, sp.trial_end_date
                FROM subscriptions s
                JOIN subscription_plans sp ON s.plan_id = sp.id
                ORDER BY s.id DESC LIMIT 1
            ");
        }

        if ($sub) {
            $sub['features'] = $this->getPlanFeatures((int)$sub['plan_id']);
            $targetOrgId = (int)($sub['organization_id'] ?? $orgId);
        } else {
            $targetOrgId = $orgId;
        }

        $facCount = (int)($this->db->selectOne("SELECT COUNT(*) AS cnt FROM facilities WHERE organization_id = ?", [$targetOrgId], 'i')['cnt'] ?? 0);
        $courtCount = (int)($this->db->selectOne("SELECT COUNT(*) AS cnt FROM courts c JOIN facilities f ON c.facility_id = f.id WHERE f.organization_id = ?", [$targetOrgId], 'i')['cnt'] ?? 0);
        $staffCount = (int)($this->db->selectOne("SELECT COUNT(*) AS cnt FROM users u JOIN roles r ON u.role_id = r.id WHERE r.name IN ('facility_manager', 'receptionist', 'court_staff')")['cnt'] ?? 0);

        $payments = [];
        if ($sub) {
            $payments = $this->db->select("
                SELECT * FROM subscription_payments WHERE subscription_id = ? ORDER BY id DESC LIMIT 10
            ", [(int)$sub['id']], 'i');

            $lastPaymentMethod = strtolower($payments[0]['payment_method'] ?? '');
            $sub['current_billing_cycle'] = !empty($sub['billing_cycle']) ? $sub['billing_cycle'] : ((str_contains($lastPaymentMethod, 'annual') || str_contains($lastPaymentMethod, 'yearly')) ? 'yearly' : 'monthly');
        }

        return [
            'subscription' => $sub,
            'has_used_free_trial' => $this->hasUsedFreeTrial($orgId),
            'usage' => [
                'facilities' => $facCount,
                'courts' => $courtCount,
                'staff' => $staffCount
            ],
            'payments' => $payments
        ];
    }

    public function subscribeTenantToPlan(int $orgId, int $planId, string $billingCycle = 'monthly', string $customPaymentNote = ''): array {
        $plan = $this->findById($planId);
        if (!$plan) {
            return ['success' => false, 'message' => 'Subscription plan tier not found.'];
        }

        $existing = $this->db->selectOne("SELECT * FROM subscriptions WHERE organization_id = ? ORDER BY id DESC LIMIT 1", [$orgId], 'i');
        
        $prevCycle = 'monthly';
        if ($existing) {
            $lastPm = $this->db->selectOne("SELECT payment_method FROM subscription_payments WHERE subscription_id = ? ORDER BY id DESC LIMIT 1", [(int)$existing['id']], 'i');
            $pmStr = strtolower($lastPm['payment_method'] ?? '');
            $prevCycle = !empty($existing['billing_cycle']) ? $existing['billing_cycle'] : ((str_contains($pmStr, 'annual') || str_contains($pmStr, 'yearly')) ? 'yearly' : 'monthly');
        }

        // Rule 1: Cannot downgrade from an active Yearly subscription to Monthly billing
        if ($prevCycle === 'yearly' && $billingCycle === 'monthly' && $existing && $existing['status'] === 'active') {
            return [
                'success' => false,
                'message' => 'Downgrading from an active Annual Plan to Monthly billing is not available until your current term ends.'
            ];
        }

        $isYearly = ($billingCycle === 'yearly');
        $isFree   = ((int)($plan['is_free_trial'] ?? 0) === 1) && !$this->hasUsedFreeTrial($orgId);
        if ((int)($plan['is_free_trial'] ?? 0) === 1 && !$isFree && (float)$plan['monthly_price'] <= 0) {
            return ['success'=>false,'message'=>'The free trial has already been used. Select a paid package.'];
        }

        $monthsToAdd = $isFree ? max(1, (int)($plan['trial_duration_months'] ?? 1)) : ($isYearly ? 12 : 1);
        
        // Extend period from existing future current_period_end if active
        $baseTimestamp = time();
        if ($existing && !empty($existing['current_period_end'])) {
            $existingEndTs = strtotime($existing['current_period_end']);
            if ($existingEndTs && $existingEndTs > time()) {
                $baseTimestamp = $existingEndTs;
            }
        }
        $periodEnd = date('Y-m-d', strtotime("+{$monthsToAdd} months", $baseTimestamp));

        if ($existing) {
            $this->db->execute("
                UPDATE subscriptions SET plan_id = ?, status = 'active', billing_cycle = ?, current_period_end = ? WHERE id = ?
            ", [$planId, $billingCycle, $periodEnd, (int)$existing['id']], 'issi');
            $subId = (int)$existing['id'];
        } else {
            $this->db->execute("
                INSERT INTO subscriptions (organization_id, plan_id, status, billing_cycle, current_period_end) VALUES (?, ?, 'active', ?, ?)
            ", [$orgId, $planId, $billingCycle, $periodEnd], 'iiss');
            $subId = $this->db->getLastInsertId();
        }

        $fullAmount = $isFree ? 0.00 : ($isYearly ? (float)($plan['yearly_price'] ?? round($plan['monthly_price'] * 12 * 0.70, 2)) : (float)$plan['monthly_price']);

        // Rule 2: If upgrading from Monthly to Yearly, deduct the monthly payment already paid
        $creditDeduction = 0.00;
        $paymentNote = $isFree ? 'Free Trial Promo' : ($isYearly ? 'Annual PayMongo/Card' : 'Monthly Card');

        if (!empty($customPaymentNote)) {
            $paymentNote = $customPaymentNote;
        }
        if ($isFree && stripos($paymentNote, 'free trial') === false) {
            $paymentNote = 'Free Trial Promo | ' . $paymentNote;
        }

        if ($prevCycle === 'monthly' && $isYearly && $existing) {
            $lastPayment = $this->db->selectOne("SELECT amount FROM subscription_payments WHERE subscription_id = ? AND payment_status = 'paid' ORDER BY id DESC LIMIT 1", [$subId], 'i');
            $paidAmount = (float)($lastPayment['amount'] ?? 0);
            if ($paidAmount > 0) {
                $creditDeduction = $paidAmount;
                $paymentNote = "Annual Upgrade (₱" . number_format($creditDeduction, 2) . " Credit Applied)";
            }
        }

        $finalAmount = max(0.00, round($fullAmount - $creditDeduction, 2));

        $this->db->execute("
            INSERT INTO subscription_payments (subscription_id, amount, payment_method, payment_status)
            VALUES (?, ?, ?, 'paid')
        ", [$subId, $finalAmount, $paymentNote], 'ids');
        (new ReferralRepository())->syncPayments();

        return [
            'success' => true,
            'message' => 'Subscription plan successfully updated!',
            'subscription_id' => $subId,
            'amount_charged' => $finalAmount
        ];
    }

    public function cancelTenantSubscription(int $orgId): bool {
        $sub = $this->db->selectOne("SELECT id FROM subscriptions WHERE organization_id = ? ORDER BY id DESC LIMIT 1", [$orgId], 'i');
        if (!$sub) return false;

        return $this->db->execute("UPDATE subscriptions SET status = 'cancelled' WHERE id = ?", [(int)$sub['id']], 'i');
    }

    public function bypassUpgradeSubscription(int $subId, int $planId, string $billingCycle = 'monthly'): array {
        $plan = $this->db->selectOne("SELECT * FROM subscription_plans WHERE id = ?", [$planId], 'i');
        if (!$plan) {
            return ['success' => false, 'message' => 'Target subscription plan not found.'];
        }

        $sub = $this->db->selectOne("SELECT * FROM subscriptions WHERE id = ?", [$subId], 'i');
        if (!$sub) {
            return ['success' => false, 'message' => 'Subscription record not found.'];
        }

        $intervalSql = ($billingCycle === 'yearly' || $billingCycle === 'annual') ? "INTERVAL 1 YEAR" : "INTERVAL 1 MONTH";
        $updateSql = "UPDATE subscriptions 
                      SET plan_id = ?, billing_cycle = ?, status = 'active', current_period_end = DATE_ADD(CURRENT_DATE(), {$intervalSql}) 
                      WHERE id = ?";
        $this->db->execute($updateSql, [$planId, $billingCycle, $subId], 'isi');

        // Log complimentary admin bypass payment record
        $note = "Admin Bypass Upgrade (" . strtoupper($billingCycle) . " - No Charge)";
        $this->db->execute("
            INSERT INTO subscription_payments (subscription_id, amount, payment_method, payment_status, created_at)
            VALUES (?, 0.00, ?, 'paid', NOW())
        ", [$subId, $note], 'is');

        $paymentId = $this->db->getLastInsertId();

        return [
            'success' => true,
            'message' => 'Subscription plan successfully upgraded without payment (Admin Bypass)!',
            'subscription_id' => $subId,
            'payment_id' => $paymentId,
            'plan_name' => $plan['name']
        ];
    }
}
