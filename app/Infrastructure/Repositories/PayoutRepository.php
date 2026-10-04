<?php
namespace App\Infrastructure\Repositories;

use App\Core\Database\Connection;
use Exception;

class PayoutRepository {
    private Connection $db;

    public function __construct() {
        $this->db = Connection::getInstance();
        $this->ensureTableStructure();
        $this->ensurePayoutItemsBackfilled();
    }

    /**
     * Ensure payouts table contains all required columns
     */
    private function ensureTableStructure(): void {
        try {
            $sql = "CREATE TABLE IF NOT EXISTS `payouts` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `organization_id` INT NOT NULL,
                `reference_no` VARCHAR(50) NOT NULL UNIQUE,
                `requested_by_user_id` INT NULL,
                `gcash_account_name` VARCHAR(150) NOT NULL DEFAULT '',
                `gcash_account_number` VARCHAR(50) NOT NULL DEFAULT '',
                `amount` DECIMAL(10,2) NOT NULL,
                `status` ENUM('pending', 'approved', 'completed', 'rejected') DEFAULT 'pending',
                `notes` TEXT NULL,
                `admin_notes` TEXT NULL,
                `processed_by_user_id` INT NULL,
                `processed_at` DATETIME NULL,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (`organization_id`) REFERENCES `organizations`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
            $this->db->execute($sql);
        } catch (Exception $e) {}

        try {
            $sqlItems = "CREATE TABLE IF NOT EXISTS `payout_items` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `payout_id` INT NOT NULL,
                `booking_id` INT NOT NULL,
                `record_type` VARCHAR(50) NOT NULL DEFAULT 'court_booking',
                `amount` DECIMAL(10,2) NOT NULL,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (`payout_id`) REFERENCES `payouts`(`id`) ON DELETE CASCADE,
                UNIQUE KEY `unique_payout_item` (`booking_id`, `record_type`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
            $this->db->execute($sqlItems);
        } catch (Exception $e) {}

        try { $this->db->execute("ALTER TABLE `payout_items` DROP FOREIGN KEY `payout_items_ibfk_2`"); } catch (Exception $e) {}
        try { $this->db->execute("ALTER TABLE `payout_items` DROP INDEX `unique_payout_booking`"); } catch (Exception $e) {}
        try { $this->db->execute("ALTER TABLE `payout_items` ADD UNIQUE KEY `unique_payout_item` (`booking_id`, `record_type`)"); } catch (Exception $e) {}

        $columns = $this->db->select("SHOW COLUMNS FROM `payouts`");
        $colNames = array_column($columns, 'Field');

        $columnsItems = $this->db->select("SHOW COLUMNS FROM `payout_items`");
        $colNamesItems = array_column($columnsItems, 'Field');
        if (!in_array('record_type', $colNamesItems)) {
            try { $this->db->execute("ALTER TABLE `payout_items` ADD COLUMN `record_type` VARCHAR(50) NOT NULL DEFAULT 'court_booking'"); } catch (Exception $e) {}
        }

        $columns = $this->db->select("SHOW COLUMNS FROM `payouts`");
        $colNames = array_column($columns, 'Field');

        if (!in_array('reference_no', $colNames)) {
            try { $this->db->execute("ALTER TABLE `payouts` ADD COLUMN `reference_no` VARCHAR(50) NULL"); } catch (Exception $e) {}
        }
        if (!in_array('requested_by_user_id', $colNames)) {
            try { $this->db->execute("ALTER TABLE `payouts` ADD COLUMN `requested_by_user_id` INT NULL"); } catch (Exception $e) {}
        }
        if (!in_array('gcash_account_name', $colNames)) {
            try { $this->db->execute("ALTER TABLE `payouts` ADD COLUMN `gcash_account_name` VARCHAR(150) NOT NULL DEFAULT ''"); } catch (Exception $e) {}
        }
        if (!in_array('gcash_account_number', $colNames)) {
            try { $this->db->execute("ALTER TABLE `payouts` ADD COLUMN `gcash_account_number` VARCHAR(50) NOT NULL DEFAULT ''"); } catch (Exception $e) {}
        }
        if (!in_array('notes', $colNames)) {
            try { $this->db->execute("ALTER TABLE `payouts` ADD COLUMN `notes` TEXT NULL"); } catch (Exception $e) {}
        }
        if (!in_array('admin_notes', $colNames)) {
            try { $this->db->execute("ALTER TABLE `payouts` ADD COLUMN `admin_notes` TEXT NULL"); } catch (Exception $e) {}
        }
        if (!in_array('receipt_image', $colNames)) {
            try { $this->db->execute("ALTER TABLE `payouts` ADD COLUMN `receipt_image` VARCHAR(255) NULL"); } catch (Exception $e) {}
        }
        if (!in_array('processed_by_user_id', $colNames)) {
            try { $this->db->execute("ALTER TABLE `payouts` ADD COLUMN `processed_by_user_id` INT NULL"); } catch (Exception $e) {}
        }
        if (!in_array('processed_at', $colNames)) {
            try { $this->db->execute("ALTER TABLE `payouts` ADD COLUMN `processed_at` DATETIME NULL"); } catch (Exception $e) {}
        }
        if (!in_array('payout_fee', $colNames)) {
            try { $this->db->execute("ALTER TABLE `payouts` ADD COLUMN `payout_fee` DECIMAL(10,2) NOT NULL DEFAULT 15.00"); } catch (Exception $e) {}
        }
        if (!in_array('net_amount', $colNames)) {
            try { $this->db->execute("ALTER TABLE `payouts` ADD COLUMN `net_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00"); } catch (Exception $e) {}
        }
        if (!in_array('updated_at', $colNames)) {
            try { $this->db->execute("ALTER TABLE `payouts` ADD COLUMN `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP"); } catch (Exception $e) {}
        }
        try {
            $this->db->execute("ALTER TABLE `payouts` MODIFY COLUMN `status` ENUM('pending', 'approved', 'completed', 'rejected') DEFAULT 'pending'");
        } catch (Exception $e) {}
        try {
            $this->db->execute("UPDATE `payouts` SET `payout_fee` = 15.00, `net_amount` = GREATEST(0, `amount` - 15.00) WHERE `net_amount` = 0 OR `net_amount` IS NULL");
        } catch (Exception $e) {}
    }

    /**
     * Ensure all non-rejected payouts have associated payout_items attached
     */
    public function ensurePayoutItemsBackfilled(): void {
        try {
            // First purge any payout_items associated with rejected payouts
            $this->db->execute("DELETE FROM payout_items WHERE payout_id IN (SELECT id FROM payouts WHERE status = 'rejected')");

            $allPayouts = $this->db->select("
                SELECT po.id, po.amount, po.organization_id, po.created_at AS payout_created_at, COALESCE(SUM(pi.amount), 0) AS current_attached
                FROM payouts po
                LEFT JOIN payout_items pi ON po.id = pi.payout_id
                WHERE po.status != 'rejected'
                GROUP BY po.id, po.amount, po.organization_id, po.created_at
                ORDER BY po.created_at ASC
            ");

            foreach ($allPayouts as $po) {
                $payoutId = (int)$po['id'];
                $targetAmt = (float)$po['amount'];
                $currentAttached = (float)$po['current_attached'];
                $orgId = (int)$po['organization_id'];
                $createdAt = $po['payout_created_at'];

                if ($currentAttached >= $targetAmt - 0.01) {
                    continue;
                }

                $unionSql = $this->getGcashRevenueUnionSql($orgId);

                // Fetch unclaimed GCash bookings created on or before the payout request timestamp
                $unclaimedSql = "SELECT gr.record_type, gr.booking_id, gr.amount
                                 FROM ({$unionSql}) AS gr
                                 WHERE gr.created_at <= '{$createdAt}'
                                   AND CONCAT(gr.record_type, '_', gr.booking_id) NOT IN (
                                     SELECT CONCAT(COALESCE(pi.record_type, 'court_booking'), '_', pi.booking_id)
                                     FROM payout_items pi 
                                     JOIN payouts p ON pi.payout_id = p.id 
                                     WHERE p.status != 'rejected'
                                 )
                                 ORDER BY gr.created_at ASC";

                $unclaimed = $this->db->select($unclaimedSql);
                $accum = $currentAttached;

                foreach ($unclaimed as $ub) {
                    $amt = (float)$ub['amount'];
                    try {
                        $this->db->execute("INSERT IGNORE INTO payout_items (payout_id, booking_id, record_type, amount, created_at) VALUES (?, ?, ?, ?, NOW())", [
                            $payoutId,
                            $ub['booking_id'],
                            $ub['record_type'],
                            $amt
                        ]);
                    } catch (Exception $e) {}

                    $accum += $amt;
                    if ($accum >= $targetAmt - 0.01) {
                        break;
                    }
                }
            }
        } catch (Exception $e) {}
    }

    private function getGcashRevenueUnionSql(?int $organizationId = null): string {
        $orgWhereBooking = ($organizationId !== null && $organizationId > 0) ? " AND (b.organization_id = {$organizationId} OR f.organization_id = {$organizationId})" : "";
        $orgWhereOpenPlay = ($organizationId !== null && $organizationId > 0) ? " AND f.organization_id = {$organizationId}" : "";

        return "
            SELECT 
                'court_booking' AS record_type,
                b.id AS booking_id,
                b.booking_reference,
                COALESCE(p.amount, b.total_amount) AS amount,
                COALESCE(p.payment_method, 'gcash') AS payment_method,
                COALESCE(p.transaction_reference, CONCAT('REF-#', b.booking_reference)) AS transaction_reference,
                COALESCE(p.created_at, b.created_at) AS created_at,
                u.first_name,
                u.last_name,
                u.email AS customer_email,
                c.name AS court_name,
                f.name AS facility_name,
                f.organization_id
            FROM bookings b
            LEFT JOIN payments p ON p.booking_id = b.id
            LEFT JOIN users u ON b.customer_id = u.id
            LEFT JOIN facilities f ON b.facility_id = f.id
            LEFT JOIN courts c ON b.court_id = c.id
            WHERE (LOWER(COALESCE(p.payment_method, '')) LIKE '%gcash%' OR LOWER(COALESCE(p.payment_method, '')) LIKE '%paymongo%' OR LOWER(COALESCE(b.notes, '')) LIKE '%gcash%')
              AND (p.status = 'completed' OR b.payment_status = 'paid')
              AND b.payment_status != 'refunded'
              AND b.booking_status != 'cancelled'
              {$orgWhereBooking}

            UNION ALL

            SELECT 
                'open_play' AS record_type,
                opr.id AS booking_id,
                CONCAT('OP-', LPAD(opr.id, 5, '0')) AS booking_reference,
                COALESCE(ops.fee_per_player, opr.amount_paid) AS amount,
                COALESCE(opr.payment_method, 'gcash') AS payment_method,
                CONCAT('OP-REG-', LPAD(opr.id, 5, '0')) AS transaction_reference,
                opr.created_at AS created_at,
                u.first_name,
                u.last_name,
                COALESCE(u.email, '') AS customer_email,
                ops.title AS court_name,
                f.name AS facility_name,
                f.organization_id
            FROM open_play_registrations opr
            JOIN open_play_sessions ops ON opr.session_id = ops.id
            JOIN facilities f ON ops.facility_id = f.id
            LEFT JOIN users u ON opr.user_id = u.id
            WHERE LOWER(COALESCE(opr.payment_method, '')) IN ('gcash', 'paymaya', 'paymongo', 'card', 'maya')
              AND opr.payment_status = 'paid'
              {$orgWhereOpenPlay}
        ";
    }

    /**
     * Get summary metrics for GCash revenue, requested payouts, completed payouts, and available balance
     */
    public function getPayoutMetrics(?int $organizationId = null): array {
        $unionSql = $this->getGcashRevenueUnionSql($organizationId);

        // 1. Total Paid GCash Bookings & Open Play Revenue
        $gcashSql = "SELECT COALESCE(SUM(amount), 0) AS total_gcash_revenue FROM ({$unionSql}) AS gr";
        $gcashRow = $this->db->selectOne($gcashSql);
        $totalGcashRevenue = (float)($gcashRow['total_gcash_revenue'] ?? 0);

        // 2. Payout totals
        $payoutParams = [];
        $orgWherePayouts = "";
        if ($organizationId !== null && $organizationId > 0) {
            $orgWherePayouts = " WHERE po.organization_id = ?";
            $payoutParams[] = $organizationId;
        }

        $payoutSql = "SELECT 
                        COALESCE(SUM(CASE WHEN po.status = 'pending' THEN COALESCE(po.net_amount, GREATEST(0, po.amount - 15.00)) ELSE 0 END), 0) AS total_pending,
                        COALESCE(SUM(CASE WHEN po.status = 'approved' THEN COALESCE(po.net_amount, GREATEST(0, po.amount - 15.00)) ELSE 0 END), 0) AS total_approved,
                        COALESCE(SUM(CASE WHEN po.status = 'completed' THEN COALESCE(po.net_amount, GREATEST(0, po.amount - 15.00)) ELSE 0 END), 0) AS total_completed,
                        COALESCE(SUM(CASE WHEN po.status = 'rejected' THEN po.amount ELSE 0 END), 0) AS total_rejected,
                        COUNT(po.id) AS total_requests
                      FROM payouts po
                      {$orgWherePayouts}";

        $payoutRow = $this->db->selectOne($payoutSql, $payoutParams);

        $totalPending   = (float)($payoutRow['total_pending'] ?? 0);
        $totalApproved  = (float)($payoutRow['total_approved'] ?? 0);
        $totalCompleted = (float)($payoutRow['total_completed'] ?? 0);
        $totalRejected  = (float)($payoutRow['total_rejected'] ?? 0);
        $totalRequests  = (int)($payoutRow['total_requests'] ?? 0);

        // 3. Unclaimed GCash Bookings & Open Play Available Balance
        $unclaimedSql = "SELECT COALESCE(SUM(gr.amount), 0) AS unclaimed_balance
                         FROM ({$unionSql}) AS gr
                         WHERE CONCAT(gr.record_type, '_', gr.booking_id) NOT IN (
                             SELECT CONCAT(COALESCE(pi.record_type, 'court_booking'), '_', pi.booking_id)
                             FROM payout_items pi 
                             JOIN payouts po ON pi.payout_id = po.id 
                             WHERE po.status != 'rejected'
                         )";

        $unclaimedRow = $this->db->selectOne($unclaimedSql);
        $availableBalance = (float)($unclaimedRow['unclaimed_balance'] ?? 0);

        return [
            'total_gcash_revenue' => $totalGcashRevenue,
            'total_pending'       => $totalPending,
            'total_approved'      => $totalApproved,
            'total_completed'     => $totalCompleted,
            'total_rejected'      => $totalRejected,
            'available_balance'   => $availableBalance,
            'total_requests'      => $totalRequests
        ];
    }

    /**
     * Get paginated list of successful GCash paid court bookings & open play registrations
     */
    public function getPaginatedGcashBookings(
        int $start = 0,
        int $length = 10,
        string $search = '',
        string $orderBy = 'created_at',
        string $orderDir = 'DESC',
        ?int $organizationId = null,
        string $claimFilter = 'all'
    ): array {
        $unionSql = $this->getGcashRevenueUnionSql($organizationId);

        $whereConditions = [];
        $params = [];

        if (!empty($search)) {
            $s = "%{$search}%";
            $whereConditions[] = "(gr.booking_reference LIKE ? OR gr.transaction_reference LIKE ? OR gr.first_name LIKE ? OR gr.last_name LIKE ? OR gr.customer_email LIKE ? OR gr.facility_name LIKE ? OR gr.court_name LIKE ?)";
            array_push($params, $s, $s, $s, $s, $s, $s, $s);
        }

        if ($claimFilter === 'claimed' || $claimFilter === 'completed') {
            $whereConditions[] = "po.status = 'completed'";
        } elseif ($claimFilter === 'unclaimed') {
            $whereConditions[] = "po.reference_no IS NULL";
        } elseif (in_array($claimFilter, ['pending', 'approved'], true)) {
            $whereConditions[] = "po.status = ?";
            $params[] = $claimFilter;
        }

        $whereClause = !empty($whereConditions) ? " WHERE " . implode(" AND ", $whereConditions) : "";

        $fromJoin = "FROM ({$unionSql}) AS gr
                     LEFT JOIN payout_items pi ON pi.booking_id = gr.booking_id AND COALESCE(pi.record_type, 'court_booking') = gr.record_type
                     LEFT JOIN payouts po ON pi.payout_id = po.id AND po.status != 'rejected'";

        // Total count
        $totalRow = $this->db->selectOne("SELECT COUNT(*) AS cnt {$fromJoin}");
        $totalRecords = (int)($totalRow['cnt'] ?? 0);

        // Filtered count & total amount & distinct attached payouts
        $filteredRow = $this->db->selectOne("SELECT COUNT(*) AS cnt, COALESCE(SUM(gr.amount), 0) AS total_amount, COUNT(DISTINCT po.id) AS distinct_payouts {$fromJoin} {$whereClause}", $params);
        $filteredRecords = (int)($filteredRow['cnt'] ?? 0);
        $totalAmount     = (float)($filteredRow['total_amount'] ?? 0);
        $distinctPayouts = (int)($filteredRow['distinct_payouts'] ?? 0);

        $payoutFeePerTx  = 15.00;
        $totalFee        = $distinctPayouts > 0 ? ($distinctPayouts * $payoutFeePerTx) : ($totalAmount > 0 ? $payoutFeePerTx : 0.0);
        $netAmount       = max(0.0, $totalAmount - $totalFee);

        $orderDir = strtoupper($orderDir) === 'ASC' ? 'ASC' : 'DESC';

        $dataSql = "SELECT gr.*,
                           po.reference_no AS payout_reference_no,
                           po.status AS payout_status
                    {$fromJoin}
                    {$whereClause}
                    ORDER BY gr.created_at {$orderDir}
                    LIMIT {$start}, {$length}";

        $data = $this->db->select($dataSql, $params);

        return [
            'recordsTotal'    => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'totalAmount'     => $totalAmount,
            'totalFee'        => $totalFee,
            'netAmount'       => $netAmount,
            'data'            => $data
        ];
    }

    /**
     * Get paginated list of payout requests
     */
    public function getPaginatedPayouts(
        int $start = 0,
        int $length = 10,
        string $search = '',
        string $orderBy = 'po.created_at',
        string $orderDir = 'DESC',
        ?int $organizationId = null,
        string $statusFilter = 'all'
    ): array {
        $params = [];
        $whereConditions = [];

        if ($organizationId !== null && $organizationId > 0) {
            $whereConditions[] = "po.organization_id = ?";
            $params[] = $organizationId;
        }

        if (!empty($statusFilter) && $statusFilter !== 'all') {
            $whereConditions[] = "po.status = ?";
            $params[] = $statusFilter;
        }

        if (!empty($search)) {
            $s = "%{$search}%";
            $whereConditions[] = "(po.reference_no LIKE ? OR po.gcash_account_name LIKE ? OR po.gcash_account_number LIKE ? OR o.name LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)";
            array_push($params, $s, $s, $s, $s, $s, $s);
        }

        $whereClause = !empty($whereConditions) ? " WHERE " . implode(" AND ", $whereConditions) : "";

        // Total count
        $orgWhere = ($organizationId && $organizationId > 0) ? " WHERE organization_id = {$organizationId}" : "";
        $totalSql = "SELECT COUNT(*) AS cnt FROM payouts{$orgWhere}";
        $totalRow = $this->db->selectOne($totalSql);
        $totalRecords = (int)($totalRow['cnt'] ?? 0);

        // Filtered count
        $filteredSql = "SELECT COUNT(*) AS cnt 
                        FROM payouts po
                        LEFT JOIN organizations o ON po.organization_id = o.id
                        LEFT JOIN users u ON po.requested_by_user_id = u.id
                        {$whereClause}";
        $filteredRow = $this->db->selectOne($filteredSql, $params);
        $filteredRecords = (int)($filteredRow['cnt'] ?? 0);

        // Allowed Sort Columns
        $allowedSort = [
            '0' => 'po.reference_no',
            '1' => 'o.name',
            '2' => 'po.gcash_account_name',
            '3' => 'po.amount',
            '4' => 'po.status',
            '5' => 'po.created_at',
            '6' => 'po.created_at'
        ];
        $sortCol = $allowedSort[$orderBy] ?? 'po.created_at';
        $orderDir = strtoupper($orderDir) === 'ASC' ? 'ASC' : 'DESC';

        $dataSql = "SELECT po.*,
                           o.name AS organization_name, o.tax_id AS organization_tax_id,
                           u.first_name AS requester_first_name, u.last_name AS requester_last_name, u.email AS requester_email, u.phone AS requester_phone,
                           pu.first_name AS processor_first_name, pu.last_name AS processor_last_name
                    FROM payouts po
                    LEFT JOIN organizations o ON po.organization_id = o.id
                    LEFT JOIN users u ON po.requested_by_user_id = u.id
                    LEFT JOIN users pu ON po.processed_by_user_id = pu.id
                    {$whereClause}
                    ORDER BY {$sortCol} {$orderDir}
                    LIMIT {$start}, {$length}";

        $data = $this->db->select($dataSql, $params);
        $metrics = $this->getPayoutMetrics($organizationId);

        return [
            'recordsTotal'    => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data'            => $data,
            'summary'         => $metrics
        ];
    }

    /**
     * Get unclaimed booking packages/options for exact payout selection
     */
    public function getUnclaimedBookingOptions(?int $organizationId = null): array {
        $unionSql = $this->getGcashRevenueUnionSql($organizationId);
        $unclaimedSql = "SELECT gr.record_type, gr.booking_id, gr.booking_reference, gr.amount, gr.created_at, gr.court_name
                         FROM ({$unionSql}) AS gr
                         WHERE CONCAT(gr.record_type, '_', gr.booking_id) NOT IN (
                             SELECT CONCAT(COALESCE(pi.record_type, 'court_booking'), '_', pi.booking_id)
                             FROM payout_items pi 
                             JOIN payouts po ON pi.payout_id = po.id 
                             WHERE po.status != 'rejected'
                         )
                         ORDER BY gr.created_at ASC";

        $unclaimed = $this->db->select($unclaimedSql);
        $packages = [];
        $accum = 0.0;
        $count = 0;
        $payoutFee = 15.00;

        foreach ($unclaimed as $bk) {
            $count++;
            $amt = (float)$bk['amount'];
            $accum += $amt;
            $net = max(0.0, $accum - $payoutFee);

            $label = "{$count} Booking" . ($count > 1 ? "s" : "") . " — Gross: ₱" . number_format($accum, 2) . " (Net: ₱" . number_format($net, 2) . ")";

            $packages[] = [
                'count'        => $count,
                'gross_amount' => $accum,
                'payout_fee'   => $payoutFee,
                'net_amount'   => $net,
                'label'        => $label
            ];
        }

        return [
            'unclaimed_items' => $unclaimed,
            'valid_packages'  => $packages
        ];
    }

    /**
     * Create a new payout request allocating unclaimed GCash bookings
     */
    public function createPayoutRequest(
        int $organizationId,
        int $userId,
        string $accountName,
        string $accountNumber,
        float $amount,
        string $notes = ''
    ): array {
        if ($amount <= 0) {
            throw new Exception("Payout request amount must be greater than ₱0.00.");
        }

        // Fetch un-payouted GCash paid court bookings & open play registrations
        $unionSql = $this->getGcashRevenueUnionSql($organizationId);
        $unclaimedBookingsSql = "SELECT gr.record_type, gr.booking_id, gr.booking_reference, gr.amount
                                 FROM ({$unionSql}) AS gr
                                 WHERE CONCAT(gr.record_type, '_', gr.booking_id) NOT IN (
                                     SELECT CONCAT(COALESCE(pi.record_type, 'court_booking'), '_', pi.booking_id)
                                     FROM payout_items pi 
                                     JOIN payouts po ON pi.payout_id = po.id 
                                     WHERE po.status != 'rejected'
                                 )
                                 ORDER BY gr.created_at ASC";

        $unclaimedBookings = $this->db->select($unclaimedBookingsSql);

        $totalUnclaimed = 0.0;
        foreach ($unclaimedBookings as $ub) {
            $totalUnclaimed += (float)$ub['amount'];
        }

        if ($amount > $totalUnclaimed + 0.01) {
            throw new Exception("Requested amount (₱" . number_format($amount, 2) . ") exceeds available unclaimed GCash balance (₱" . number_format($totalUnclaimed, 2) . ").");
        }

        // Match exact cumulative full booking amounts
        $allocatedBookings = [];
        $accumulated = 0.0;
        $validCumulativeTotals = [];

        foreach ($unclaimedBookings as $bk) {
            $bkAmt = (float)$bk['amount'];
            $allocatedBookings[] = [
                'booking_id'  => (int)$bk['booking_id'],
                'record_type' => (string)$bk['record_type'],
                'amount'      => $bkAmt
            ];
            $accumulated += $bkAmt;
            $validCumulativeTotals[] = number_format($accumulated, 2);

            if (abs($accumulated - $amount) < 0.01) {
                break;
            }
            if ($accumulated > $amount) {
                break;
            }
        }

        if (abs($accumulated - $amount) >= 0.01) {
            $validStr = !empty($validCumulativeTotals) ? "₱" . implode(", ₱", $validCumulativeTotals) : "₱0.00";
            throw new Exception("Payout amount (₱" . number_format($amount, 2) . ") must match exact full booking amounts. Partial booking payouts are not allowed. Valid exact payout options: {$validStr}.");
        }

        // PayMongo GCash Payout Fee (₱15.00 flat fee per payout disbursement)
        $payoutFee = 15.00;
        $netAmount = max(0.0, $amount - $payoutFee);

        // Create payout record
        $refNo = 'PO-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));

        $sql = "INSERT INTO payouts (organization_id, reference_no, requested_by_user_id, gcash_account_name, gcash_account_number, amount, payout_fee, net_amount, payout_method, status, notes, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'gcash', 'pending', ?, NOW())";

        $this->db->execute($sql, [
            $organizationId,
            $refNo,
            $userId,
            $accountName,
            $accountNumber,
            $amount,
            $payoutFee,
            $netAmount,
            $notes
        ]);

        $payoutId = $this->db->getLastInsertId();

        // Attach allocated bookings into payout_items
        foreach ($allocatedBookings as $item) {
            try {
                $this->db->execute("INSERT INTO payout_items (payout_id, booking_id, record_type, amount, created_at) VALUES (?, ?, ?, ?, NOW())", [
                    $payoutId,
                    $item['booking_id'],
                    $item['record_type'],
                    $item['amount']
                ]);
            } catch (Exception $e) {}
        }

        return [
            'payout_id'          => $payoutId,
            'reference_no'       => $refNo,
            'amount'             => $amount,
            'allocated_bookings' => count($allocatedBookings)
        ];
    }

    /**
     * Update payout request status (Approve, Complete, Reject)
     */
    public function updatePayoutStatus(
        int $payoutId,
        string $status,
        string $adminNotes = '',
        ?int $adminUserId = null,
        ?int $organizationId = null,
        ?string $receiptImage = null
    ): bool {
        $allowedStatuses = ['pending', 'approved', 'completed', 'rejected'];
        if (!in_array($status, $allowedStatuses)) {
            throw new Exception("Invalid payout status supplied.");
        }

        $params = [];
        $whereOrg = "";
        if ($organizationId !== null && $organizationId > 0) {
            $whereOrg = " AND organization_id = ?";
            $params[] = $organizationId;
        }

        $payout = $this->db->selectOne("SELECT * FROM payouts WHERE id = ? {$whereOrg}", array_merge([$payoutId], $params));
        if (!$payout) {
            throw new Exception("Payout request not found or access denied.");
        }

        if (!empty($receiptImage)) {
            $updateSql = "UPDATE payouts SET status = ?, admin_notes = ?, receipt_image = ?, processed_by_user_id = ?, processed_at = NOW() WHERE id = ?";
            $this->db->execute($updateSql, [$status, $adminNotes, $receiptImage, $adminUserId, $payoutId]);
        } else {
            $updateSql = "UPDATE payouts SET status = ?, admin_notes = ?, processed_by_user_id = ?, processed_at = NOW() WHERE id = ?";
            $this->db->execute($updateSql, [$status, $adminNotes, $adminUserId, $payoutId]);
        }

        // If rejected, release allocated bookings
        if ($status === 'rejected') {
            $this->db->execute("DELETE FROM payout_items WHERE payout_id = ?", [$payoutId]);
        }

        return true;
    }

    /**
     * Get single payout request details with proof of covered bookings
     */
    public function getPayoutDetail(int $payoutId, ?int $organizationId = null): ?array {
        $params = [$payoutId];
        $whereOrg = "";
        if ($organizationId !== null && $organizationId > 0) {
            $whereOrg = " AND po.organization_id = ?";
            $params[] = $organizationId;
        }

        $sql = "SELECT po.*,
                       o.name AS organization_name, o.tax_id AS organization_tax_id,
                       u.first_name AS requester_first_name, u.last_name AS requester_last_name, u.email AS requester_email, u.phone AS requester_phone,
                       pu.first_name AS processor_first_name, pu.last_name AS processor_last_name
                FROM payouts po
                LEFT JOIN organizations o ON po.organization_id = o.id
                LEFT JOIN users u ON po.requested_by_user_id = u.id
                LEFT JOIN users pu ON po.processed_by_user_id = pu.id
                WHERE po.id = ? {$whereOrg}
                LIMIT 1";

        $payout = $this->db->selectOne($sql, $params);
        if (!$payout) return null;

        // Fetch associated covered GCash bookings & open play online payments (proof of revenue)
        $unionSql = $this->getGcashRevenueUnionSql($payout['organization_id'] ?? null);
        $itemsSql = "SELECT pi.amount AS item_amount, pi.record_type, gr.*
                     FROM payout_items pi
                     JOIN ({$unionSql}) AS gr ON pi.booking_id = gr.booking_id AND COALESCE(pi.record_type, 'court_booking') = gr.record_type
                     WHERE pi.payout_id = ?
                     ORDER BY gr.created_at ASC";

        $payout['items'] = $this->db->select($itemsSql, [$payoutId]);

        // If payout_items is empty for this payout (e.g. for rejected payouts or legacy records),
        // dynamically fetch covered GCash revenue items matching the payout timestamp/amount
        if (empty($payout['items'])) {
            $createdAt = $payout['created_at'] ?? date('Y-m-d H:i:s');
            $allRevenueSql = "SELECT gr.*, gr.amount AS item_amount
                              FROM ({$unionSql}) AS gr
                              WHERE gr.created_at <= '{$createdAt}'
                              ORDER BY gr.created_at ASC";
            $allRevenue = $this->db->select($allRevenueSql);

            $items = [];
            $accum = 0.0;
            $targetAmt = (float)$payout['amount'];

            foreach ($allRevenue as $bk) {
                $bkAmt = (float)$bk['amount'];
                $items[] = $bk;
                $accum += $bkAmt;

                // Only backfill into payout_items DB table if payout is NOT rejected
                if ($payout['status'] !== 'rejected') {
                    try {
                        $this->db->execute("INSERT IGNORE INTO payout_items (payout_id, booking_id, record_type, amount, created_at) VALUES (?, ?, ?, ?, NOW())", [
                            $payoutId,
                            $bk['booking_id'],
                            $bk['record_type'],
                            $bkAmt
                        ]);
                    } catch (Exception $e) {}
                }

                if ($accum >= $targetAmt - 0.01) {
                    break;
                }
            }
            $payout['items'] = $items;
        }

        return $payout;
    }
}
