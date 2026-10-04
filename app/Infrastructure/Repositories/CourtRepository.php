<?php
namespace App\Infrastructure\Repositories;

use App\Core\Database\Connection;

class CourtRepository {
    private Connection $db;

    public function __construct() {
        $this->db = Connection::getInstance();
    }

    public function findById(int $id): ?array {
        $sql = "SELECT c.*, f.name AS facility_name, f.organization_id, f.address, f.city
                FROM courts c
                JOIN facilities f ON c.facility_id = f.id
                WHERE c.id = ? LIMIT 1";
        return $this->db->selectOne($sql, [$id], 'i');
    }

    public function findByFacilityId(int $facilityId): array {
        $sql = "SELECT c.*
                FROM courts c
                WHERE c.facility_id = ?
                ORDER BY c.court_number ASC, c.id ASC";
        return $this->db->select($sql, [$facilityId], 'i');
    }

    public function findByOwnerOrganization(int $orgId): array {
        $sql = "SELECT c.*, f.name AS facility_name
                FROM courts c
                JOIN facilities f ON c.facility_id = f.id
                WHERE f.organization_id = ?
                ORDER BY c.id DESC";
        $courts = $this->db->select($sql, [$orgId], 'i');
        foreach ($courts as &$c) {
            $c['images'] = $this->getCourtImages((int)$c['id']);
        }
        return $courts;
    }

    // ── Court Images Management (Up to 10 images) ────────────────────────────

    public function getCourtImages(int $courtId): array {
        $sql = "SELECT * FROM court_images WHERE court_id = ? ORDER BY id ASC";
        return $this->db->select($sql, [$courtId], 'i');
    }

    public function getCourtImageCount(int $courtId): int {
        $row = $this->db->selectOne("SELECT COUNT(*) AS cnt FROM court_images WHERE court_id = ?", [$courtId], 'i');
        return (int)($row['cnt'] ?? 0);
    }

    public function addCourtImage(int $courtId, string $imagePath): int {
        $sql = "INSERT INTO court_images (court_id, image_path) VALUES (?, ?)";
        $this->db->execute($sql, [$courtId, $imagePath], 'is');
        return $this->db->getLastInsertId();
    }

    public function deleteCourtImage(int $imageId): bool {
        return $this->db->execute("DELETE FROM court_images WHERE id = ?", [$imageId], 'i');
    }

    public function getCourtImageById(int $imageId): ?array {
        return $this->db->selectOne("SELECT ci.*, c.facility_id, f.organization_id FROM court_images ci JOIN courts c ON ci.court_id = c.id JOIN facilities f ON c.facility_id = f.id WHERE ci.id = ? LIMIT 1", [$imageId], 'i');
    }

    public function getCourtAmenities(int $courtId): array {
        $sql = "SELECT a.name, a.icon
                FROM amenities a
                JOIN court_amenities ca ON ca.amenity_id = a.id
                WHERE ca.court_id = ?";
        return $this->db->select($sql, [$courtId], 'i');
    }

    public function search(array $filters = []): array {
        $sql = "SELECT c.*, f.name AS facility_name, f.address, f.city, f.province
                FROM courts c
                JOIN facilities f ON c.facility_id = f.id
                WHERE c.status = 'active' AND f.status = 'active'";
        $params = [];
        $types = '';

        if (!empty($filters['facility_id'])) {
            $sql .= " AND c.facility_id = ?";
            $params[] = (int)$filters['facility_id'];
            $types .= 'i';
        }

        if (!empty($filters['court_id'])) {
            $sql .= " AND c.id = ?";
            $params[] = (int)$filters['court_id'];
            $types .= 'i';
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (c.name LIKE ? OR f.name LIKE ? OR f.address LIKE ? OR f.city LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $types .= 'ssss';
        }

        if (!empty($filters['court_type'])) {
            $sql .= " AND c.court_type = ?";
            $params[] = $filters['court_type'];
            $types .= 's';
        }

        if (!empty($filters['city'])) {
            $sql .= " AND f.city LIKE ?";
            $params[] = '%' . $filters['city'] . '%';
            $types .= 's';
        }

        if (!empty($filters['max_price'])) {
            $sql .= " AND c.base_price_per_hour <= ?";
            $params[] = (float)$filters['max_price'];
            $types .= 'd';
        }

        if (!empty($filters['lighting'])) {
            $sql .= " AND EXISTS (SELECT 1 FROM court_amenities ca JOIN amenities a ON ca.amenity_id = a.id WHERE ca.court_id = c.id AND (a.name LIKE '%Lighting%' OR a.name LIKE '%Night%'))";
        }

        if (!empty($filters['parking'])) {
            $sql .= " AND EXISTS (SELECT 1 FROM court_amenities ca JOIN amenities a ON ca.amenity_id = a.id WHERE ca.court_id = c.id AND a.name LIKE '%Parking%')";
        }

        if (!empty($filters['gear'])) {
            $sql .= " AND EXISTS (SELECT 1 FROM court_amenities ca JOIN amenities a ON ca.amenity_id = a.id WHERE ca.court_id = c.id AND (a.name LIKE '%Rental%' OR a.name LIKE '%Gear%'))";
        }

        if (!empty($filters['shower'])) {
            $sql .= " AND EXISTS (SELECT 1 FROM court_amenities ca JOIN amenities a ON ca.amenity_id = a.id WHERE ca.court_id = c.id AND (a.name LIKE '%Shower%' OR a.name LIKE '%Locker%'))";
        }

        if (!empty($filters['open_now'])) {
            $currentDay = (int)date('w');
            $currentTime = date('H:i:s');
            $sql .= " AND EXISTS (SELECT 1 FROM court_operating_hours coh WHERE coh.court_id = c.id AND coh.day_of_week = {$currentDay} AND coh.open_time <= '{$currentTime}' AND coh.close_time >= '{$currentTime}')";
        }

        $sql .= " ORDER BY c.id DESC";
        return $this->db->select($sql, $params, $types);
    }

    public function getMostReservedCourts(int $limit = 4): array {
        $sql = "SELECT c.*, f.name AS facility_name, f.city, f.address AS facility_address,
                       COUNT(CASE WHEN b.booking_status IN ('confirmed', 'completed') OR b.payment_status = 'paid' THEN b.id END) AS total_reservations,
                       (SELECT ROUND(AVG(r.rating), 1) FROM reviews r WHERE r.facility_id = f.id) AS avg_rating,
                       COALESCE((SELECT COUNT(r.id) FROM reviews r WHERE r.facility_id = f.id), 0) AS total_reviews
                FROM courts c
                JOIN facilities f ON c.facility_id = f.id
                LEFT JOIN bookings b ON b.court_id = c.id
                WHERE c.status = 'active' AND f.status = 'active'
                GROUP BY c.id
                HAVING total_reservations > 0
                ORDER BY total_reservations DESC, c.id DESC
                LIMIT ?";
        return $this->db->select($sql, [$limit], 'i');
    }

    public function create(array $data): int {
        $sql = "INSERT INTO courts (facility_id, name, court_number, court_type, surface_type, base_price_per_hour, status)
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $this->db->execute($sql, [
            $data['facility_id'],
            $data['name'],
            $data['court_number'] ?? 1,
            $data['court_type'] ?? 'outdoor',
            $data['surface_type'] ?? 'cushioned_acrylic',
            $data['base_price_per_hour'] ?? 350.00,
            $data['status'] ?? 'active'
        ], 'isissds');
        $courtId = $this->db->getLastInsertId();

        // Default operating hours
        for ($day = 0; $day <= 6; $day++) {
            $this->db->execute("INSERT INTO court_operating_hours (court_id, day_of_week, open_time, close_time) VALUES (?, ?, '06:00:00', '22:00:00')", [$courtId, $day], 'ii');
        }

        return $courtId;
    }

    public function update(int $courtId, array $data): bool {
        $sql = "UPDATE courts SET name = ?, court_type = ?, surface_type = ?, base_price_per_hour = ?, status = ? WHERE id = ?";
        return $this->db->execute($sql, [
            $data['name'],
            $data['court_type'],
            $data['surface_type'],
            $data['base_price_per_hour'],
            $data['status'],
            $courtId
        ], 'sssdsi');
    }

    public function getBlockedSchedules(int $courtId, string $date): array {
        $sql = "SELECT * FROM court_blocked_schedules WHERE court_id = ? AND block_date = ?";
        return $this->db->select($sql, [$courtId, $date], 'is');
    }

    public function getAllBlockedSchedules(int $courtId): array {
        $sql = "SELECT * FROM court_blocked_schedules WHERE court_id = ? ORDER BY block_date ASC, start_time ASC";
        return $this->db->select($sql, [$courtId], 'i');
    }

    public function getOperatingHours(int $courtId): array {
        $sql = "SELECT * FROM court_operating_hours WHERE court_id = ? ORDER BY day_of_week ASC";
        return $this->db->select($sql, [$courtId], 'i');
    }

    public function addBlockedSchedule(int $courtId, string $date, string $startTime, string $endTime, string $reason): int {
        $sql = "INSERT INTO court_blocked_schedules (court_id, block_date, start_time, end_time, reason) VALUES (?, ?, ?, ?, ?)";
        $this->db->execute($sql, [$courtId, $date, $startTime, $endTime, $reason], 'issss');
        return $this->db->getLastInsertId();
    }

    // ── Time-Based Pricing Rules ──────────────────────────────────────────────

    public function getPricingRules(int $courtId): array {
        $sql = "SELECT * FROM court_pricing WHERE court_id = ? ORDER BY start_time ASC";
        return $this->db->select($sql, [$courtId], 'i');
    }

    public function addPricingRule(array $data): int {
        $dayMap = ['sunday' => 0, 'monday' => 1, 'tuesday' => 2, 'wednesday' => 3, 'thursday' => 4, 'friday' => 5, 'saturday' => 6];
        $rawDayType = $data['day_type'] ?? 'all';
        $dayType = is_array($rawDayType) ? implode(',', $rawDayType) : (string)$rawDayType;
        $dayOfWeek = $dayMap[$dayType] ?? null;

        $sql = "INSERT INTO court_pricing (court_id, name, label, price_per_hour, start_time, end_time, day_type, day_of_week, is_active)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $this->db->execute($sql, [
            $data['court_id'],
            $data['name'],
            $data['label'] ?? null,
            (float)$data['price_per_hour'],
            $data['start_time'],
            $data['end_time'],
            $dayType,
            $dayOfWeek,
            $data['is_active'] ?? 1
        ], 'issdsssii');
        return $this->db->getLastInsertId();
    }

    public function updatePricingRule(int $ruleId, array $data): bool {
        $dayMap = ['sunday' => 0, 'monday' => 1, 'tuesday' => 2, 'wednesday' => 3, 'thursday' => 4, 'friday' => 5, 'saturday' => 6];
        $rawDayType = $data['day_type'] ?? 'all';
        $dayType = is_array($rawDayType) ? implode(',', $rawDayType) : (string)$rawDayType;
        $dayOfWeek = $dayMap[$dayType] ?? null;

        $sql = "UPDATE court_pricing SET name = ?, label = ?, price_per_hour = ?, start_time = ?, end_time = ?, day_type = ?, day_of_week = ?, is_active = ? WHERE id = ?";
        return $this->db->execute($sql, [
            $data['name'],
            $data['label'] ?? null,
            (float)$data['price_per_hour'],
            $data['start_time'],
            $data['end_time'],
            $dayType,
            $dayOfWeek,
            $data['is_active'] ?? 1,
            $ruleId
        ], 'ssdsssiii');
    }

    public function deletePricingRule(int $ruleId): bool {
        return $this->db->execute("DELETE FROM court_pricing WHERE id = ?", [$ruleId], 'i');
    }

    public function getPricingRuleById(int $ruleId): ?array {
        return $this->db->selectOne("SELECT cp.*, c.facility_id FROM court_pricing cp JOIN courts c ON cp.court_id = c.id WHERE cp.id = ? LIMIT 1", [$ruleId], 'i');
    }

    /**
     * Get platform public metrics dynamically from database for index.php
     * - Courts count and primary location
     * - Atomic Anti-Conflict Lock integrity rate
     * - Total court hours booked
     * - Fast mobile reservation speed SLA
     */
    public function getPlatformPublicMetrics(): array {
        // 1. Total Courts in system
        $courtRow = $this->db->selectOne("SELECT COUNT(*) AS cnt FROM courts WHERE status = 'active'");
        $totalCourts = (int)($courtRow['cnt'] ?? 0);
        if ($totalCourts === 0) {
            $courtRowAll = $this->db->selectOne("SELECT COUNT(*) AS cnt FROM courts");
            $totalCourts = (int)($courtRowAll['cnt'] ?? 0);
        }
        $courtsDisplay = ($totalCourts >= 15) ? number_format($totalCourts) . '+' : ($totalCourts > 0 ? (string)$totalCourts : '0');

        // Location context (primary province from active facilities)
        $provRow = $this->db->selectOne("SELECT province FROM facilities WHERE status = 'active' AND province IS NOT NULL AND province != '' GROUP BY province ORDER BY COUNT(*) DESC LIMIT 1");
        $primaryProvince = !empty($provRow['province']) ? $provRow['province'] : 'Bohol';

        // 2. Atomic Anti-Conflict Lock Rate
        $totalBookingsRow = $this->db->selectOne("SELECT COUNT(*) AS cnt FROM bookings WHERE booking_status IN ('confirmed', 'pending', 'awaiting_payment', 'completed')");
        $totalBookings = (int)($totalBookingsRow['cnt'] ?? 0);

        $overlapRow = $this->db->selectOne("
            SELECT COUNT(*) AS cnt 
            FROM bookings b1
            JOIN bookings b2 ON b1.court_id = b2.court_id 
              AND b1.booking_date = b2.booking_date 
              AND b1.id < b2.id
              AND b1.booking_status IN ('confirmed', 'pending', 'awaiting_payment', 'completed')
              AND b2.booking_status IN ('confirmed', 'pending', 'awaiting_payment', 'completed')
              AND (b1.start_time < b2.end_time AND b1.end_time > b2.start_time)
        ");
        $conflicts = (int)($overlapRow['cnt'] ?? 0);
        $lockDisplay = ($totalBookings > 0) ? max(0, min(100, round((($totalBookings - $conflicts) / $totalBookings) * 100))) . '%' : '100%';

        // 3. Court Hours Booked
        $hoursRow = $this->db->selectOne("SELECT COALESCE(SUM(duration_hours), 0) AS total_hours FROM bookings WHERE booking_status NOT IN ('cancelled', 'rejected')");
        $totalHours = (float)($hoursRow['total_hours'] ?? 0);
        if ($totalHours >= 1000) {
            $hoursDisplay = number_format($totalHours) . '+';
        } elseif ($totalHours >= 15) {
            $hoursDisplay = number_format($totalHours) . '+';
        } elseif ($totalHours > 0) {
            $hoursDisplay = ($totalHours == floor($totalHours)) ? number_format($totalHours) : number_format($totalHours, 1);
        } else {
            $hoursDisplay = '0';
        }

        // 4. Fast Mobile Reservation Speed
        $speedRow = $this->db->selectOne("
            SELECT AVG(TIMESTAMPDIFF(SECOND, b.created_at, p.created_at)) AS avg_sec
            FROM payments p
            JOIN bookings b ON p.booking_id = b.id
            WHERE p.status = 'completed' AND TIMESTAMPDIFF(SECOND, b.created_at, p.created_at) BETWEEN 1 AND 300
        ");
        $avgSec = (float)($speedRow['avg_sec'] ?? 0);
        $speedDisplay = ($avgSec > 0 && $avgSec >= 60) ? round($avgSec) . 's' : '< 60s';

        return [
            'courts_count'      => $totalCourts,
            'courts_display'    => $courtsDisplay,
            'primary_province'  => $primaryProvince,
            'lock_display'      => $lockDisplay,
            'total_bookings'    => $totalBookings,
            'conflicts_count'   => $conflicts,
            'hours_booked'      => $totalHours,
            'hours_display'     => $hoursDisplay,
            'avg_speed_sec'     => $avgSec,
            'speed_display'     => $speedDisplay,
        ];
    }
}

