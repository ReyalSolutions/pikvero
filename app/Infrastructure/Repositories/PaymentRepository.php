<?php
namespace App\Infrastructure\Repositories;

use App\Core\Database\Connection;

class PaymentRepository {
    private Connection $db;

    public function __construct() {
        $this->db = Connection::getInstance();
    }

    private function getBaseUnionSql(?int $organizationId = null): string {
        $orgWhereBooking = ($organizationId !== null && $organizationId > 0) ? " WHERE (b.organization_id = {$organizationId} OR f.organization_id = {$organizationId})" : "";
        $orgWhereOpenPlay = ($organizationId !== null && $organizationId > 0) ? " WHERE f.organization_id = {$organizationId}" : "";

        return "
            SELECT 
                'court_booking' AS record_type,
                COALESCE(p.id, b.id) AS payment_id,
                COALESCE(p.amount, b.total_amount) AS amount,
                COALESCE(p.payment_method, 'cash') AS payment_method,
                COALESCE(p.transaction_reference, CONCAT('REF-#', b.booking_reference)) AS transaction_reference,
                CASE 
                  WHEN p.status IS NOT NULL THEN p.status
                  WHEN b.payment_status = 'paid' THEN 'completed'
                  WHEN b.payment_status = 'refunded' THEN 'failed'
                  ELSE 'pending'
                END AS payment_status,
                COALESCE(p.created_at, b.created_at) AS payment_date,
                COALESCE(p.created_at, b.created_at) AS created_at,
                b.id AS booking_id, 
                b.booking_reference, 
                b.booking_date, 
                b.start_time, 
                b.end_time, 
                COALESCE(b.duration_hours, 1.00) AS duration_hours,
                COALESCE(b.rate_per_hour, b.total_amount) AS rate_per_hour,
                b.total_amount AS booking_total, 
                b.payment_status AS booking_payment_status,
                u.id AS customer_id, 
                COALESCE(NULLIF(TRIM(CONCAT(COALESCE(u.first_name,''), ' ', COALESCE(u.last_name,''))), ''), 'Guest Player') AS customer_name,
                u.first_name, 
                u.last_name, 
                u.email AS customer_email, 
                u.phone AS customer_phone,
                f.id AS facility_id, 
                f.name AS facility_name,
                f.address AS facility_address,
                f.city AS facility_city,
                c.id AS court_id, 
                c.name AS court_name,
                f.organization_id AS organization_id,
                NULL AS session_title
            FROM bookings b
            LEFT JOIN payments p ON p.booking_id = b.id
            LEFT JOIN users u ON b.customer_id = u.id
            LEFT JOIN facilities f ON b.facility_id = f.id
            LEFT JOIN courts c ON b.court_id = c.id
            {$orgWhereBooking}

            UNION ALL

            SELECT 
                'open_play' AS record_type,
                opr.id AS payment_id,
                COALESCE(ops.fee_per_player, opr.amount_paid) AS amount,
                COALESCE(opr.payment_method, 'cash') AS payment_method,
                CONCAT('OP-REG-', LPAD(opr.id, 5, '0')) AS transaction_reference,
                CASE 
                  WHEN opr.payment_status = 'paid' THEN 'completed'
                  WHEN opr.payment_status = 'refunded' THEN 'failed'
                  ELSE 'pending'
                END AS payment_status,
                opr.created_at AS payment_date,
                opr.created_at AS created_at,
                opr.id AS booking_id,
                CONCAT('OP-', LPAD(opr.id, 5, '0')) AS booking_reference,
                ops.session_date AS booking_date,
                ops.start_time AS start_time,
                ops.end_time AS end_time,
                1.00 AS duration_hours,
                COALESCE(ops.fee_per_player, opr.amount_paid) AS rate_per_hour,
                COALESCE(ops.fee_per_player, opr.amount_paid) AS booking_total,
                opr.payment_status AS booking_payment_status,
                u.id AS customer_id,
                COALESCE(NULLIF(TRIM(opr.player_name), ''), TRIM(CONCAT(COALESCE(u.first_name,''), ' ', COALESCE(u.last_name,''))), 'Guest Player') AS customer_name,
                u.first_name,
                u.last_name,
                COALESCE(u.email, '') AS customer_email,
                COALESCE(opr.player_phone, u.phone, '') AS customer_phone,
                f.id AS facility_id,
                f.name AS facility_name,
                f.address AS facility_address,
                f.city AS facility_city,
                NULL AS court_id,
                'Open Play Court' AS court_name,
                f.organization_id AS organization_id,
                ops.title AS session_title
            FROM open_play_registrations opr
            JOIN open_play_sessions ops ON opr.session_id = ops.id
            JOIN facilities f ON ops.facility_id = f.id
            LEFT JOIN users u ON opr.user_id = u.id
            {$orgWhereOpenPlay}
        ";
    }

    public function getPaginatedPayments(
        int $start = 0,
        int $length = 10,
        string $search = '',
        string $orderBy = '0',
        string $orderDir = 'DESC',
        ?int $organizationId = null,
        ?string $statusFilter = null,
        ?string $methodFilter = null,
        ?string $startDate = null,
        ?string $endDate = null
    ): array {
        $unionSql = $this->getBaseUnionSql($organizationId);

        $whereConditions = [];
        $params = [];

        if (!empty($statusFilter) && $statusFilter !== 'all') {
            if ($statusFilter === 'completed') {
                $whereConditions[] = "payment_status = 'completed'";
            } else if ($statusFilter === 'pending') {
                $whereConditions[] = "payment_status = 'pending'";
            } else if ($statusFilter === 'failed') {
                $whereConditions[] = "payment_status = 'failed'";
            }
        }

        if (!empty($methodFilter) && $methodFilter !== 'all') {
            if ($methodFilter === 'gcash' || $methodFilter === 'paymongo' || $methodFilter === 'maya') {
                $whereConditions[] = "payment_method IN ('gcash', 'paymaya', 'paymongo', 'maya')";
            } else {
                $whereConditions[] = "payment_method = ?";
                $params[] = $methodFilter;
            }
        }

        if (!empty($startDate)) {
            $whereConditions[] = "DATE(created_at) >= ?";
            $params[] = $startDate;
        }

        if (!empty($endDate)) {
            $whereConditions[] = "DATE(created_at) <= ?";
            $params[] = $endDate;
        }

        if (!empty($search)) {
            $s = "%{$search}%";
            $whereConditions[] = "(transaction_reference LIKE ? OR booking_reference LIKE ? OR customer_name LIKE ? OR customer_email LIKE ? OR facility_name LIKE ? OR session_title LIKE ?)";
            array_push($params, $s, $s, $s, $s, $s, $s);
        }

        $whereClause = !empty($whereConditions) ? " WHERE " . implode(" AND ", $whereConditions) : "";

        // Total count
        $totalSql = "SELECT COUNT(*) AS cnt FROM ({$unionSql}) AS ap";
        $totalRow = $this->db->selectOne($totalSql);
        $totalRecords = (int)($totalRow['cnt'] ?? 0);

        // Filtered count
        $filteredSql = "SELECT COUNT(*) AS cnt FROM ({$unionSql}) AS ap {$whereClause}";
        $filteredRow = $this->db->selectOne($filteredSql, $params);
        $filteredRecords = (int)($filteredRow['cnt'] ?? 0);

        // Sort Column Mapping
        $allowedSort = [
            '0' => 'booking_reference',
            '1' => 'customer_name',
            '2' => 'amount',
            '3' => 'payment_status',
            '4' => 'created_at',
            '5' => 'payment_method'
        ];
        $sortCol = $allowedSort[$orderBy] ?? 'created_at';
        $orderDir = strtoupper($orderDir) === 'ASC' ? 'ASC' : 'DESC';

        $dataSql = "SELECT * FROM ({$unionSql}) AS ap {$whereClause} ORDER BY {$sortCol} {$orderDir} LIMIT {$start}, {$length}";
        $data = $this->db->select($dataSql, $params);

        // Summary Metrics
        $summarySql = "SELECT 
                        COALESCE(SUM(CASE WHEN payment_status = 'completed' THEN amount ELSE 0 END), 0) AS total_collected,
                        COALESCE(SUM(CASE WHEN payment_status = 'failed' THEN amount ELSE 0 END), 0) AS total_refunded,
                        COUNT(CASE WHEN payment_status = 'completed' THEN 1 END) AS completed_count,
                        COUNT(CASE WHEN payment_status = 'pending' THEN 1 END) AS pending_count
                       FROM ({$unionSql}) AS ap {$whereClause}";
        $summary = $this->db->selectOne($summarySql, $params);

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

    public function getPaymentDetail(int $paymentId, ?int $organizationId = null, ?string $recordType = null): ?array {
        $unionSql = $this->getBaseUnionSql($organizationId);
        if ($recordType) {
            $sql = "SELECT * FROM ({$unionSql}) AS ap WHERE (payment_id = ? OR booking_id = ?) AND record_type = ? LIMIT 1";
            return $this->db->selectOne($sql, [$paymentId, $paymentId, $recordType]);
        }
        $sql = "SELECT * FROM ({$unionSql}) AS ap WHERE payment_id = ? OR booking_id = ? LIMIT 1";
        return $this->db->selectOne($sql, [$paymentId, $paymentId]);
    }

    public function refundPayment(int $paymentId, string $reason = '', ?int $organizationId = null): bool {
        $detail = $this->getPaymentDetail($paymentId, $organizationId);
        if (!$detail) {
            return false;
        }

        $this->db->beginTransaction();
        try {
            if (($detail['record_type'] ?? '') === 'open_play') {
                $this->db->execute("UPDATE open_play_registrations SET payment_status = 'refunded' WHERE id = ?", [$detail['payment_id']], 'i');
            } else {
                $existingPayment = $this->db->selectOne("SELECT id FROM payments WHERE booking_id = ? OR id = ? LIMIT 1", [$detail['booking_id'], $paymentId]);
                if ($existingPayment) {
                    $this->db->execute("UPDATE payments SET status = 'failed' WHERE id = ?", [$existingPayment['id']], 'i');
                } else {
                    $this->db->execute("INSERT INTO payments (booking_id, amount, payment_method, transaction_reference, status, created_at) VALUES (?, ?, ?, ?, 'failed', NOW())", [
                        $detail['booking_id'],
                        $detail['amount'],
                        $detail['payment_method'],
                        $detail['transaction_reference']
                    ], 'idss');
                }

                $this->db->execute("UPDATE bookings SET payment_status = 'refunded', booking_status = 'cancelled' WHERE id = ?", [$detail['booking_id']], 'i');
            }

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
}
