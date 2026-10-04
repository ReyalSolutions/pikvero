<?php
namespace App\Infrastructure\Repositories;

use App\Core\Database\Connection;

class BookingRepository {
    private Connection $db;

    public function __construct() {
        $this->db = Connection::getInstance();
    }

    public function checkOverlapping(int $courtId, string $date, string $startTime, string $endTime): bool {
        // Query active reservations on the same court and date where start_time < requested_end AND end_time > requested_start
        $sql = "SELECT COUNT(*) AS count FROM bookings
                WHERE court_id = ?
                AND booking_date = ?
                AND booking_status IN ('confirmed', 'pending', 'awaiting_payment')
                AND (start_time < ? AND end_time > ?)";
        $row = $this->db->selectOne($sql, [$courtId, $date, $endTime, $startTime], 'isss');
        return ($row && (int)$row['count'] > 0);
    }

    public function create(array $data): int {
        $sql = "INSERT INTO bookings (booking_reference, customer_id, court_id, facility_id, organization_id, booking_date, start_time, end_time, duration_hours, rate_per_hour, total_amount, payment_status, booking_status, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $this->db->execute($sql, [
            $data['booking_reference'],
            $data['customer_id'],
            $data['court_id'],
            $data['facility_id'],
            $data['organization_id'],
            $data['booking_date'],
            $data['start_time'],
            $data['end_time'],
            $data['duration_hours'],
            $data['rate_per_hour'],
            $data['total_amount'],
            $data['payment_status'] ?? 'unpaid',
            $data['booking_status'] ?? 'confirmed',
            $data['notes'] ?? null
        ], 'siiiisssdddsss');
        return $this->db->getLastInsertId();
    }

    public function findById(int $id): ?array {
        $sql = "SELECT * FROM bookings WHERE id = ?";
        return $this->db->selectOne($sql, [$id], 'i');
    }

    public function getActiveBookingsForCourtDate(int $courtId, string $date): array {
        $sql = "SELECT b.id, b.booking_reference, b.start_time, b.end_time, b.booking_status,
                       CONCAT(u.first_name, ' ', u.last_name) AS customer_name
                FROM bookings b
                LEFT JOIN users u ON b.customer_id = u.id
                WHERE b.court_id = ? AND b.booking_date = ? AND b.booking_status IN ('confirmed', 'pending', 'awaiting_payment')
                ORDER BY b.start_time ASC";
        return $this->db->select($sql, [$courtId, $date], 'is');
    }

    public function findByReference(string $ref): ?array {
        $sql = "SELECT b.*, c.name AS court_name, f.name AS facility_name, f.address, f.city,
                       u.first_name AS customer_first, u.last_name AS customer_last, u.email AS customer_email, u.phone AS customer_phone
                FROM bookings b
                JOIN courts c ON b.court_id = c.id
                JOIN facilities f ON b.facility_id = f.id
                JOIN users u ON b.customer_id = u.id
                WHERE b.booking_reference = ? LIMIT 1";
        return $this->db->selectOne($sql, [$ref], 's');
    }

    public function getCustomerBookings(int $customerId): array {
        $sql = "SELECT b.*, c.name AS court_name, f.name AS facility_name
                FROM bookings b
                JOIN courts c ON b.court_id = c.id
                JOIN facilities f ON b.facility_id = f.id
                WHERE b.customer_id = ?
                ORDER BY b.created_at DESC, b.id DESC";
        return $this->db->select($sql, [$customerId], 'i');
    }

    public function getCustomerBookingsDataTables(
        int $customerId,
        int $start = 0,
        int $length = 10,
        string $search = '',
        string $orderColIndex = '4',
        string $orderDir = 'DESC',
        string $statusFilter = 'all',
        string $startDate = '',
        string $endDate = ''
    ): array {
        $whereClauses = ["b.customer_id = ?"];
        $params = [$customerId];
        $types = "i";

        if (!empty($search)) {
            $whereClauses[] = "(b.booking_reference LIKE ? OR c.name LIKE ? OR f.name LIKE ? OR f.city LIKE ?)";
            $searchTerm = "%{$search}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $types .= "ssss";
        }

        if (!empty($statusFilter) && $statusFilter !== 'all') {
            $whereClauses[] = "b.booking_status = ?";
            $params[] = $statusFilter;
            $types .= "s";
        }

        if (!empty($startDate)) {
            $whereClauses[] = "b.booking_date >= ?";
            $params[] = $startDate;
            $types .= "s";
        }

        if (!empty($endDate)) {
            $whereClauses[] = "b.booking_date <= ?";
            $params[] = $endDate;
            $types .= "s";
        }

        $whereSql = implode(" AND ", $whereClauses);

        $totalCountRow = $this->db->selectOne("SELECT COUNT(*) AS total FROM bookings b WHERE b.customer_id = ?", [$customerId], 'i');
        $recordsTotal = (int)($totalCountRow['total'] ?? 0);

        $filteredCountRow = $this->db->selectOne("SELECT COUNT(*) AS total FROM bookings b JOIN courts c ON b.court_id = c.id JOIN facilities f ON b.facility_id = f.id WHERE {$whereSql}", $params, $types);
        $recordsFiltered = (int)($filteredCountRow['total'] ?? 0);

        $orderCols = [
            '0' => 'b.booking_reference',
            '1' => 'c.name',
            '2' => 'b.booking_date',
            '3' => 'b.total_amount',
            '4' => 'b.created_at',
            '5' => 'b.id'
        ];
        $colName = $orderCols[$orderColIndex] ?? 'b.created_at';
        $dir = (strtoupper($orderDir) === 'ASC') ? 'ASC' : 'DESC';

        $start = max(0, $start);
        $length = max(1, min(100, $length));

        $sql = "SELECT b.*, c.name AS court_name, c.court_type, f.name AS facility_name, f.city, f.address
                FROM bookings b
                JOIN courts c ON b.court_id = c.id
                JOIN facilities f ON b.facility_id = f.id
                WHERE {$whereSql}
                ORDER BY {$colName} {$dir}
                LIMIT {$start}, {$length}";

        $rows = $this->db->select($sql, $params, $types);

        return [
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $rows
        ];
    }

    public function getOwnerBookings(int $orgId): array {
        $sql = "SELECT b.*, c.name AS court_name, f.name AS facility_name,
                       CONCAT(u.first_name, ' ', u.last_name) AS customer_name, u.email AS customer_email
                FROM bookings b
                JOIN courts c ON b.court_id = c.id
                JOIN facilities f ON b.facility_id = f.id
                JOIN users u ON b.customer_id = u.id
                WHERE b.organization_id = ?
                ORDER BY b.booking_date DESC, b.start_time DESC";
        return $this->db->select($sql, [$orgId], 'i');
    }

    public function getPaginatedBookings(int $orgId = 0, int $page = 1, int $limit = 10, string $search = '', string $status = '', string $resType = 'all'): array {
        $page = max(1, $page);
        $limit = max(1, min(1000, $limit));
        $offset = ($page - 1) * $limit;

        $unionQueries = [];
        $params = [];
        $types = "";

        // 1. Court Bookings Subquery
        if ($resType === 'all' || $resType === 'court_booking') {
            $bWhere = [];
            if ($orgId > 0) {
                $bWhere[] = "b.organization_id = ?";
                $params[] = $orgId;
                $types .= "i";
            } else {
                $bWhere[] = "1=1";
            }

            if (!empty($search)) {
                $bWhere[] = "(b.booking_reference LIKE ? OR CONCAT(u.first_name, ' ', u.last_name) LIKE ? OR u.email LIKE ? OR c.name LIKE ? OR f.name LIKE ?)";
                $searchTerm = "%{$search}%";
                $params[] = $searchTerm; $params[] = $searchTerm; $params[] = $searchTerm; $params[] = $searchTerm; $params[] = $searchTerm;
                $types .= "sssss";
            }

            if (!empty($status) && $status !== 'all') {
                $bWhere[] = "b.booking_status = ?";
                $params[] = $status;
                $types .= "s";
            }

            $bWhereSql = implode(" AND ", $bWhere);

            $unionQueries[] = "
                SELECT 
                    b.id AS id,
                    'court_booking' AS reservation_type,
                    b.booking_reference AS booking_reference,
                    b.booking_date AS booking_date,
                    b.start_time AS start_time,
                    b.end_time AS end_time,
                    b.total_amount AS total_amount,
                    b.booking_status AS booking_status,
                    b.payment_status AS payment_status,
                    c.name AS court_name,
                    f.name AS facility_name,
                    CONCAT(u.first_name, ' ', u.last_name) AS customer_name,
                    u.email AS customer_email,
                    b.created_at AS created_at,
                    COALESCE(b.notes, '') AS notes
                FROM bookings b
                JOIN courts c ON b.court_id = c.id
                JOIN facilities f ON b.facility_id = f.id
                JOIN users u ON b.customer_id = u.id
                WHERE {$bWhereSql}
            ";
        }

        // 2. Open Play Registrations Subquery
        if ($resType === 'all' || $resType === 'open_play') {
            $opWhere = [];
            if ($orgId > 0) {
                $opWhere[] = "f.organization_id = ?";
                $params[] = $orgId;
                $types .= "i";
            } else {
                $opWhere[] = "1=1";
            }

            if (!empty($search)) {
                $opWhere[] = "(r.player_name LIKE ? OR r.player_phone LIKE ? OR s.title LIKE ? OR f.name LIKE ?)";
                $searchTerm = "%{$search}%";
                $params[] = $searchTerm; $params[] = $searchTerm; $params[] = $searchTerm; $params[] = $searchTerm;
                $types .= "ssss";
            }

            if (!empty($status) && $status !== 'all') {
                if ($status === 'confirmed') {
                    $opWhere[] = "r.checkin_status != 'cancelled'";
                } else if ($status === 'completed') {
                    $opWhere[] = "r.checkin_status = 'checked_in'";
                }
            }

            $opWhereSql = implode(" AND ", $opWhere);

            $unionQueries[] = "
                SELECT 
                    r.id AS id,
                    'open_play' AS reservation_type,
                    CONCAT('OP-REG-', LPAD(r.id, 5, '0')) AS booking_reference,
                    s.session_date AS booking_date,
                    s.start_time AS start_time,
                    s.end_time AS end_time,
                    r.amount_paid AS total_amount,
                    CASE WHEN r.checkin_status = 'checked_in' THEN 'completed' ELSE 'confirmed' END AS booking_status,
                    r.payment_status AS payment_status,
                    CONCAT('🏓 ', s.title) AS court_name,
                    f.name AS facility_name,
                    r.player_name AS customer_name,
                    COALESCE(u.email, r.player_phone, 'Open Play Drop-In') AS customer_email,
                    r.created_at AS created_at,
                    CONCAT('Open Play session drop-in. Payment method: ', COALESCE(r.payment_method, 'cash')) AS notes
                FROM open_play_registrations r
                JOIN open_play_sessions s ON r.session_id = s.id
                JOIN facilities f ON s.facility_id = f.id
                LEFT JOIN users u ON r.user_id = u.id
                WHERE {$opWhereSql}
            ";
        }

        $unifiedSql = implode(" UNION ALL ", $unionQueries);

        $countSql = "SELECT COUNT(*) AS total FROM ({$unifiedSql}) AS unified";
        $countRow = $this->db->selectOne($countSql, $params, $types);
        $totalRecords = (int)($countRow['total'] ?? 0);

        $dataSql = "SELECT * FROM ({$unifiedSql}) AS unified ORDER BY created_at DESC, id DESC LIMIT ? OFFSET ?";
        $dataParams = array_merge($params, [$limit, $offset]);
        $dataTypes = $types . "ii";

        $rows = $this->db->select($dataSql, $dataParams, $dataTypes);
        $totalPages = max(1, (int)ceil($totalRecords / $limit));

        return [
            'data' => $rows,
            'total' => $totalRecords,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => $totalPages
        ];
    }

    public function getAllBookings(): array {
        $sql = "SELECT b.*, c.name AS court_name, f.name AS facility_name,
                       CONCAT(u.first_name, ' ', u.last_name) AS customer_name
                FROM bookings b
                JOIN courts c ON b.court_id = c.id
                JOIN facilities f ON b.facility_id = f.id
                JOIN users u ON b.customer_id = u.id
                ORDER BY b.id DESC";
        return $this->db->select($sql);
    }

    public function updateStatus(int $bookingId, string $newStatus, ?int $userId = null): bool {
        $booking = $this->findById($bookingId);
        if (!$booking) return false;

        $oldStatus = $booking['booking_status'];
        $sql = "UPDATE bookings SET booking_status = ? WHERE id = ?";
        $updated = $this->db->execute($sql, [$newStatus, $bookingId], 'si');

        if ($updated) {
            $this->db->execute("INSERT INTO booking_status_history (booking_id, old_status, new_status, changed_by_user_id) VALUES (?, ?, ?, ?)",
                [$bookingId, $oldStatus, $newStatus, $userId],
                'issi'
            );
        }

        return $updated;
    }

    public function cancel(int $bookingId, string $status = 'cancelled'): bool {
        return $this->updateStatus($bookingId, $status);
    }

    public function getOwnerMetrics(int $orgId): array {
        $today = date('Y-m-d');
        $month = date('Y-m');

        $todayRev = $this->db->selectOne("SELECT COALESCE(SUM(total_amount), 0) AS total FROM bookings WHERE organization_id = ? AND booking_date = ? AND booking_status = 'confirmed' AND payment_status = 'paid'", [$orgId, $today], 'is')['total'] ?? 0;
        $monthRev = $this->db->selectOne("SELECT COALESCE(SUM(total_amount), 0) AS total FROM bookings WHERE organization_id = ? AND DATE_FORMAT(booking_date, '%Y-%m') = ? AND booking_status = 'confirmed' AND payment_status = 'paid'", [$orgId, $month], 'is')['total'] ?? 0;
        $totalBookings = $this->db->selectOne("SELECT COUNT(*) AS count FROM bookings WHERE organization_id = ?", [$orgId], 'i')['count'] ?? 0;
        $todayBookings = $this->db->selectOne("SELECT COUNT(*) AS count FROM bookings WHERE organization_id = ? AND booking_date = ?", [$orgId, $today], 'is')['count'] ?? 0;

        return [
            'today_revenue' => (float)$todayRev,
            'monthly_revenue' => (float)$monthRev,
            'total_bookings' => (int)$totalBookings,
            'today_bookings' => (int)$todayBookings
        ];
    }

    public function getComprehensiveAnalytics(?int $orgId, string $startDate, string $endDate): array {
        if (empty($startDate)) $startDate = date('Y-m-01');
        if (empty($endDate)) $endDate = date('Y-m-d');

        // 1. Court Bookings Metrics
        $whereBk = "booking_date >= ? AND booking_date <= ? AND booking_status = 'confirmed' AND payment_status = 'paid'";
        $paramsBk = [$startDate, $endDate];
        $typesBk = "ss";

        if ($orgId !== null) {
            $whereBk = "organization_id = ? AND " . $whereBk;
            array_unshift($paramsBk, $orgId);
            $typesBk = "i" . $typesBk;
        }

        $courtMetrics = $this->db->selectOne(
            "SELECT COALESCE(SUM(total_amount), 0) AS revenue, COUNT(*) AS count FROM bookings WHERE {$whereBk}",
            $paramsBk,
            $typesBk
        );

        $courtRevenue = (float)($courtMetrics['revenue'] ?? 0);
        $courtBookingsCount = (int)($courtMetrics['count'] ?? 0);

        // 2. Open Play Metrics
        $whereOp = "s.session_date >= ? AND s.session_date <= ? AND LOWER(r.payment_status) IN ('paid', 'completed')";
        $paramsOp = [$startDate, $endDate];
        $typesOp = "ss";

        if ($orgId !== null) {
            $whereOp = "f.organization_id = ? AND " . $whereOp;
            array_unshift($paramsOp, $orgId);
            $typesOp = "i" . $typesOp;
        }

        $openPlayMetrics = $this->db->selectOne(
            "SELECT COALESCE(SUM(COALESCE(r.amount_paid, s.fee_per_player)), 0) AS revenue, COUNT(*) AS player_count 
             FROM open_play_registrations r
             JOIN open_play_sessions s ON r.session_id = s.id
             JOIN facilities f ON s.facility_id = f.id
             WHERE {$whereOp}",
            $paramsOp,
            $typesOp
        );

        $openPlayRevenue = (float)($openPlayMetrics['revenue'] ?? 0);
        $openPlayPlayersCount = (int)($openPlayMetrics['player_count'] ?? 0);

        // 3. Product Sales Metrics
        $whereProd = "DATE(s.sale_date) >= ? AND DATE(s.sale_date) <= ?";
        $paramsProd = [$startDate, $endDate];
        $typesProd = "ss";

        if ($orgId !== null) {
            $whereProd = "(f.organization_id = ? OR s.facility_id IS NULL) AND " . $whereProd;
            array_unshift($paramsProd, $orgId);
            $typesProd = "i" . $typesProd;
        }

        $productMetrics = $this->db->selectOne(
            "SELECT COALESCE(SUM(s.total_amount), 0) AS revenue, COALESCE(SUM(s.quantity), 0) AS items_sold 
             FROM product_sales s
             LEFT JOIN facilities f ON s.facility_id = f.id
             WHERE {$whereProd}",
            $paramsProd,
            $typesProd
        );

        $productRevenue = (float)($productMetrics['revenue'] ?? 0);
        $productsSoldCount = (int)($productMetrics['items_sold'] ?? 0);

        // Totals
        $totalRevenue = $courtRevenue + $openPlayRevenue + $productRevenue;
        $totalReservations = $courtBookingsCount + $openPlayPlayersCount;

        // 4. Daily Breakdown for Chart
        $dailyBookings = $this->db->select(
            "SELECT booking_date AS date, COALESCE(SUM(total_amount), 0) AS amount, COUNT(*) as count FROM bookings WHERE {$whereBk} GROUP BY booking_date",
            $paramsBk,
            $typesBk
        );

        $dailyOpenPlay = $this->db->select(
            "SELECT s.session_date AS date, COALESCE(SUM(COALESCE(r.amount_paid, s.fee_per_player)), 0) AS amount, COUNT(*) as count 
             FROM open_play_registrations r
             JOIN open_play_sessions s ON r.session_id = s.id
             JOIN facilities f ON s.facility_id = f.id
             WHERE {$whereOp} GROUP BY s.session_date",
            $paramsOp,
            $typesOp
        );

        $dailyProducts = $this->db->select(
            "SELECT DATE(s.sale_date) AS date, COALESCE(SUM(s.total_amount), 0) AS amount, COUNT(*) as count 
             FROM product_sales s
             LEFT JOIN facilities f ON s.facility_id = f.id
             WHERE {$whereProd} GROUP BY DATE(s.sale_date)",
            $paramsProd,
            $typesProd
        );

        // Build continuous date array
        $trendMap = [];
        $startDt = new \DateTime($startDate);
        $endDt = new \DateTime($endDate);
        $interval = new \DateInterval('P1D');
        $endDt->modify('+1 day');
        $period = new \DatePeriod($startDt, $interval, $endDt);

        foreach ($period as $dt) {
            $dStr = $dt->format('Y-m-d');
            $trendMap[$dStr] = [
                'date' => $dStr,
                'court_bookings' => 0.0,
                'open_play' => 0.0,
                'product_sales' => 0.0,
                'total' => 0.0
            ];
        }

        foreach ($dailyBookings as $r) {
            if (isset($trendMap[$r['date']])) {
                $trendMap[$r['date']]['court_bookings'] += (float)$r['amount'];
                $trendMap[$r['date']]['total'] += (float)$r['amount'];
            }
        }
        foreach ($dailyOpenPlay as $r) {
            if (isset($trendMap[$r['date']])) {
                $trendMap[$r['date']]['open_play'] += (float)$r['amount'];
                $trendMap[$r['date']]['total'] += (float)$r['amount'];
            }
        }
        foreach ($dailyProducts as $r) {
            if (isset($trendMap[$r['date']])) {
                $trendMap[$r['date']]['product_sales'] += (float)$r['amount'];
                $trendMap[$r['date']]['total'] += (float)$r['amount'];
            }
        }

        // 5. Peak Hours
        $peakHours = $this->db->select(
            "SELECT HOUR(start_time) AS hour_num, COUNT(*) AS count FROM bookings WHERE {$whereBk} GROUP BY HOUR(start_time) ORDER BY hour_num ASC",
            $paramsBk,
            $typesBk
        );

        // 6. Court Performance Ranking
        $whereCourtRank = "WHERE f.organization_id = ?";
        $paramsCourtRank = [$startDate, $endDate, $orgId];
        $typesCourtRank = "ssi";
        if ($orgId === null) {
            $whereCourtRank = "";
            $paramsCourtRank = [$startDate, $endDate];
            $typesCourtRank = "ss";
        }

        $courtPerformance = $this->db->select(
            "SELECT c.name AS court_name, COALESCE(SUM(b.total_amount), 0) AS revenue, COUNT(b.id) AS total_bookings
             FROM courts c
             JOIN facilities f ON c.facility_id = f.id
             LEFT JOIN bookings b ON b.court_id = c.id 
               AND b.booking_date >= ? AND b.booking_date <= ?
               AND b.booking_status = 'confirmed' AND b.payment_status = 'paid'
             {$whereCourtRank}
             GROUP BY c.id
             ORDER BY revenue DESC
             LIMIT 10",
            $paramsCourtRank,
            $typesCourtRank
        );

        return [
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate
            ],
            'summary' => [
                'total_revenue' => $totalRevenue,
                'court_revenue' => $courtRevenue,
                'open_play_revenue' => $openPlayRevenue,
                'product_revenue' => $productRevenue,
                'total_reservations' => $totalReservations,
                'court_bookings_count' => $courtBookingsCount,
                'open_play_players_count' => $openPlayPlayersCount,
                'products_sold_count' => $productsSoldCount
            ],
            'trend' => array_values($trendMap),
            'peak_hours' => $peakHours,
            'court_performance' => $courtPerformance
        ];
    }

    public function getOwnerReports(int $orgId): array {
        $sqlDaily = "SELECT booking_date AS date, COALESCE(SUM(total_amount), 0) AS revenue, COUNT(*) AS bookings_count
                     FROM bookings
                     WHERE organization_id = ? AND booking_status = 'confirmed'
                     GROUP BY booking_date
                     ORDER BY booking_date DESC LIMIT 7";
        $dailyData = array_reverse($this->db->select($sqlDaily, [$orgId], 'i'));

        $metrics = $this->getOwnerMetrics($orgId);

        return [
            'metrics'     => $metrics,
            'daily_trend' => $dailyData
        ];
    }

    public function searchCustomers(string $query, int $limit = 10): array {
        $term = '%' . $query . '%';
        $sql  = "SELECT id, first_name, last_name, email, phone
                 FROM users
                 WHERE (first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR CONCAT(first_name,' ',last_name) LIKE ?)
                 ORDER BY first_name ASC
                 LIMIT ?";
        return $this->db->select($sql, [$term, $term, $term, $term, $limit], 'ssssi');
    }

    public function getBookingDetail(int $bookingId): ?array {
        $sql = "SELECT b.*, 
                       c.name AS court_name, c.court_type, c.surface_type, c.base_price_per_hour AS court_base_price,
                       f.name AS facility_name, f.address AS facility_address, f.city AS facility_city, f.phone AS facility_phone,
                       CONCAT(u.first_name, ' ', u.last_name) AS customer_name, u.email AS customer_email, u.phone AS customer_phone
                FROM bookings b
                JOIN courts c ON b.court_id = c.id
                JOIN facilities f ON b.facility_id = f.id
                JOIN users u ON b.customer_id = u.id
                WHERE b.id = ? LIMIT 1";
        return $this->db->selectOne($sql, [$bookingId], 'i');
    }

    public function updatePaymentStatus(int $bookingId, string $paymentStatus, ?string $note = null): bool {
        if ($note) {
            $sql = "UPDATE bookings SET payment_status = ?, notes = CASE WHEN notes IS NULL OR notes = '' THEN ? ELSE CONCAT(notes, ' | ', ?) END WHERE id = ?";
            return $this->db->execute($sql, [$paymentStatus, $note, $note, $bookingId], 'sssi');
        }
        $sql = "UPDATE bookings SET payment_status = ? WHERE id = ?";
        return $this->db->execute($sql, [$paymentStatus, $bookingId], 'si');
    }

    public function cancelBooking(int $bookingId, ?string $reason = null, ?int $userId = null): bool {
        $booking = $this->findById($bookingId);
        if (!$booking) return false;

        $oldStatus = $booking['booking_status'];
        $note = $reason ? "[Cancellation Reason: {$reason}]" : "[Cancelled by Owner]";

        $sql = "UPDATE bookings SET booking_status = 'cancelled', notes = CASE WHEN notes IS NULL OR notes = '' THEN ? ELSE CONCAT(notes, ' | ', ?) END WHERE id = ?";
        $updated = $this->db->execute($sql, [$note, $note, $bookingId], 'ssi');

        if ($updated) {
            $this->db->execute(
                "INSERT INTO booking_status_history (booking_id, old_status, new_status, changed_by_user_id) VALUES (?, ?, 'cancelled', ?)",
                [$bookingId, $oldStatus, $userId],
                'isi'
            );
        }
        return $updated;
    }

    public function refundBooking(int $bookingId, ?string $reason = null, ?int $userId = null): bool {
        $booking = $this->findById($bookingId);
        if (!$booking) return false;

        $oldStatus = $booking['booking_status'];
        $note = $reason ? "[Refund Issued: {$reason}]" : "[Refund Issued by Owner]";

        $sql = "UPDATE bookings SET booking_status = 'refunded', payment_status = 'refunded', notes = CASE WHEN notes IS NULL OR notes = '' THEN ? ELSE CONCAT(notes, ' | ', ?) END WHERE id = ?";
        $updated = $this->db->execute($sql, [$note, $note, $bookingId], 'ssi');

        if ($updated) {
            $this->db->execute(
                "INSERT INTO booking_status_history (booking_id, old_status, new_status, changed_by_user_id) VALUES (?, ?, 'refunded', ?)",
                [$bookingId, $oldStatus, $userId],
                'isi'
            );
        }
        return $updated;
    }
}
