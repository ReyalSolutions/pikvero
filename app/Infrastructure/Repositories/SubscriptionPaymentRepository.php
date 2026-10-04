<?php
namespace App\Infrastructure\Repositories;

use App\Core\Database\Connection;

class SubscriptionPaymentRepository {
    private Connection $db;

    public function __construct() {
        $this->db = Connection::getInstance();
    }

    public function getPaginatedSubscriptionPayments(
        int $start = 0,
        int $length = 10,
        string $search = '',
        string $orderBy = 'sp.id',
        string $orderDir = 'DESC',
        ?int $organizationId = null,
        ?string $statusFilter = null,
        ?string $methodFilter = null,
        ?string $startDate = null,
        ?string $endDate = null
    ): array {
        $params = [];
        $whereConditions = [];

        if ($organizationId !== null && $organizationId > 0) {
            $whereConditions[] = "(s.organization_id = ? OR o.id = ? OR o.owner_id = ?)";
            $params[] = $organizationId;
            $params[] = $organizationId;
            $params[] = $organizationId;
        }

        if (!empty($statusFilter) && $statusFilter !== 'all') {
            if ($statusFilter === 'completed' || $statusFilter === 'paid') {
                $whereConditions[] = "(sp.payment_status = 'paid' OR sp.payment_status = 'completed' OR s.status = 'active')";
            } else if ($statusFilter === 'pending' || $statusFilter === 'past_due') {
                $whereConditions[] = "(sp.payment_status = 'pending' OR s.status = 'past_due')";
            } else if ($statusFilter === 'failed' || $statusFilter === 'refunded' || $statusFilter === 'cancelled') {
                $whereConditions[] = "(sp.payment_status = 'failed' OR sp.payment_status = 'refunded' OR s.status = 'cancelled')";
            }
        }

        if (!empty($methodFilter) && $methodFilter !== 'all') {
            $sMethod = "%{$methodFilter}%";
            $whereConditions[] = "sp.payment_method LIKE ?";
            $params[] = $sMethod;
        }

        if (!empty($startDate)) {
            $whereConditions[] = "DATE(COALESCE(sp.created_at, s.current_period_end)) >= ?";
            $params[] = $startDate;
        }

        if (!empty($endDate)) {
            $whereConditions[] = "DATE(COALESCE(sp.created_at, s.current_period_end)) <= ?";
            $params[] = $endDate;
        }

        if (!empty($search)) {
            $s = "%{$search}%";
            $whereConditions[] = "(sp.payment_method LIKE ? OR o.name LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR sp_plan.name LIKE ?)";
            array_push($params, $s, $s, $s, $s, $s, $s);
        }

        $whereClause = !empty($whereConditions) ? " WHERE " . implode(" AND ", $whereConditions) : "";

        // Total count
        $orgWhere = ($organizationId && $organizationId > 0) ? " WHERE (s.organization_id = {$organizationId} OR o.owner_id = {$organizationId})" : "";
        $totalSql = "SELECT COUNT(*) AS cnt FROM subscriptions s LEFT JOIN subscription_payments sp ON sp.subscription_id = s.id LEFT JOIN organizations o ON s.organization_id = o.id{$orgWhere}";
        $totalRow = $this->db->selectOne($totalSql);
        $totalRecords = (int)($totalRow['cnt'] ?? 0);

        // Filtered count
        $filteredSql = "SELECT COUNT(*) AS cnt 
                        FROM subscriptions s 
                        LEFT JOIN subscription_payments sp ON sp.subscription_id = s.id 
                        LEFT JOIN subscription_plans sp_plan ON s.plan_id = sp_plan.id 
                        LEFT JOIN organizations o ON s.organization_id = o.id 
                        LEFT JOIN users u ON o.owner_id = u.id 
                        {$whereClause}";
        $filteredRow = $this->db->selectOne($filteredSql, $params);
        $filteredRecords = (int)($filteredRow['cnt'] ?? 0);

        // Allowed Sort Columns
        $allowedSort = [
            '0' => 'sp.id',
            '1' => 'o.name',
            '2' => 'sp_plan.name',
            '3' => 'sp.amount',
            '4' => 'sp.created_at',   // "Created At" column
            '5' => 'sp.payment_method',
            '6' => 'sp.payment_status',
        ];
        $sortCol = $allowedSort[$orderBy] ?? 'sp.created_at';   // default: latest first
        $orderDir = strtoupper($orderDir) === 'ASC' ? 'ASC' : 'DESC';

        $dataSql = "SELECT COALESCE(sp.id, s.id) AS payment_id,
                           COALESCE(sp.amount, CASE WHEN s.billing_cycle = 'annual' OR s.billing_cycle = 'yearly' THEN sp_plan.yearly_price ELSE sp_plan.monthly_price END, 0.00) AS amount,
                           COALESCE(sp.payment_method, 'Card/PayMongo') AS payment_method,
                           CONCAT('SUB-', COALESCE(sp.id, s.id)) AS transaction_reference,
                           COALESCE(sp.payment_status, CASE WHEN s.status = 'active' THEN 'paid' ELSE 'pending' END) AS payment_status,
                           COALESCE(sp.created_at, NOW()) AS payment_date,
                           sp.created_at AS created_at,
                           s.id AS subscription_id, s.status AS subscription_status, s.billing_cycle, s.current_period_end,
                           sp_plan.id AS plan_id, COALESCE(sp_plan.name, 'Subscription Plan') AS plan_name, sp_plan.monthly_price AS plan_price,
                           o.id AS organization_id, COALESCE(o.name, 'Organization Tenant') AS organization_name, o.tax_id AS organization_tax_id,
                           u.id AS owner_id, u.first_name, u.last_name, u.email AS owner_email, u.phone AS owner_phone
                    FROM subscriptions s
                    LEFT JOIN subscription_payments sp ON sp.subscription_id = s.id
                    LEFT JOIN subscription_plans sp_plan ON s.plan_id = sp_plan.id
                    LEFT JOIN organizations o ON s.organization_id = o.id
                    LEFT JOIN users u ON o.owner_id = u.id
                    {$whereClause}
                    ORDER BY {$sortCol} {$orderDir}
                    LIMIT {$start}, {$length}";

        $data = $this->db->select($dataSql, $params);

        // Calculate Summary Metrics
        $summarySql = "SELECT 
                        COALESCE(SUM(CASE WHEN sp.payment_status = 'paid' OR sp.payment_status = 'completed' OR s.status = 'active' THEN COALESCE(sp.amount, CASE WHEN s.billing_cycle = 'annual' OR s.billing_cycle = 'yearly' THEN sp_plan.yearly_price ELSE sp_plan.monthly_price END, 0) ELSE 0 END), 0) AS total_collected,
                        COALESCE(SUM(CASE WHEN sp.payment_status = 'failed' OR sp.payment_status = 'refunded' OR s.status = 'cancelled' THEN COALESCE(sp.amount, CASE WHEN s.billing_cycle = 'annual' OR s.billing_cycle = 'yearly' THEN sp_plan.yearly_price ELSE sp_plan.monthly_price END, 0) ELSE 0 END), 0) AS total_refunded,
                        COUNT(CASE WHEN sp.payment_status = 'paid' OR sp.payment_status = 'completed' OR s.status = 'active' THEN 1 END) AS completed_count,
                        COUNT(CASE WHEN sp.payment_status = 'pending' OR s.status = 'past_due' THEN 1 END) AS pending_count
                       FROM subscriptions s
                       LEFT JOIN subscription_payments sp ON sp.subscription_id = s.id
                       LEFT JOIN subscription_plans sp_plan ON s.plan_id = sp_plan.id
                       LEFT JOIN organizations o ON s.organization_id = o.id{$orgWhere}";
        $summary = $this->db->selectOne($summarySql);

        return [
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data,
            'summary' => [
                'total_collected' => (float)($summary['total_collected'] ?? 0),
                'total_refunded'  => (float)($summary['total_refunded'] ?? 0),
                'net_revenue'     => (float)($summary['total_collected'] ?? 0) - (float)($summary['total_refunded'] ?? 0),
                'completed_count' => (int)($summary['completed_count'] ?? 0),
                'pending_count'   => (int)($summary['pending_count'] ?? 0),
                'total_records'   => $totalRecords
            ]
        ];
    }

    public function getSubscriptionPaymentDetail(int $paymentId, ?int $organizationId = null): ?array {
        $params = [$paymentId, $paymentId];
        $whereOrg = "";
        if ($organizationId !== null && $organizationId > 0) {
            $whereOrg = " AND (s.organization_id = ? OR o.id = ? OR o.owner_id = ?)";
            $params[] = $organizationId;
            $params[] = $organizationId;
            $params[] = $organizationId;
        }

        $sql = "SELECT COALESCE(sp.id, s.id) AS payment_id,
                       COALESCE(sp.amount, CASE WHEN s.billing_cycle = 'annual' OR s.billing_cycle = 'yearly' THEN sp_plan.yearly_price ELSE sp_plan.monthly_price END, 0.00) AS amount,
                       COALESCE(sp.payment_method, 'Card/PayMongo') AS payment_method,
                       CONCAT('SUB-', COALESCE(sp.id, s.id)) AS transaction_reference,
                       COALESCE(sp.payment_status, CASE WHEN s.status = 'active' THEN 'paid' ELSE 'pending' END) AS payment_status,
                       COALESCE(sp.created_at, NOW()) AS payment_date,
                       sp.created_at AS created_at,
                       s.id AS subscription_id, s.status AS subscription_status, s.billing_cycle, s.current_period_end,
                       sp_plan.id AS plan_id, COALESCE(sp_plan.name, 'Subscription Plan') AS plan_name, sp_plan.monthly_price AS plan_price,
                       o.id AS organization_id, COALESCE(o.name, 'Organization Tenant') AS organization_name, o.tax_id AS organization_tax_id,
                       u.id AS owner_id, u.first_name, u.last_name, u.email AS owner_email, u.phone AS owner_phone
                FROM subscriptions s
                LEFT JOIN subscription_payments sp ON sp.subscription_id = s.id
                LEFT JOIN subscription_plans sp_plan ON s.plan_id = sp_plan.id
                LEFT JOIN organizations o ON s.organization_id = o.id
                LEFT JOIN users u ON o.owner_id = u.id
                WHERE (sp.id = ? OR s.id = ?) {$whereOrg}
                ORDER BY COALESCE(sp.id, s.id) DESC
                LIMIT 1";

        return $this->db->selectOne($sql, $params);
    }
}
