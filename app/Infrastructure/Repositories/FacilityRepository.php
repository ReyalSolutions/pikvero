<?php
namespace App\Infrastructure\Repositories;

use App\Core\Database\Connection;

class FacilityRepository {
    private Connection $db;

    public function __construct() {
        $this->db = Connection::getInstance();
    }

    public function getAllActive(): array {
        $sql = "SELECT f.*, o.name AS organization_name,
                       (SELECT COUNT(*) FROM courts c WHERE c.facility_id = f.id AND c.status = 'active') AS total_courts,
                       (SELECT MIN(base_price_per_hour) FROM courts c WHERE c.facility_id = f.id AND c.status = 'active') AS min_price
                FROM facilities f
                JOIN organizations o ON f.organization_id = o.id
                WHERE f.status = 'active'
                ORDER BY f.id DESC";
        return $this->db->select($sql);
    }

    public function findById(int $id): ?array {
        $sql = "SELECT f.*, o.name AS organization_name, o.owner_id
                FROM facilities f
                JOIN organizations o ON f.organization_id = o.id
                WHERE f.id = ? LIMIT 1";
        return $this->db->selectOne($sql, [$id], 'i');
    }

    public function findByOrganizationId(int $orgId): array {
        $sql = "SELECT f.*, o.name AS organization_name,
                       (SELECT COUNT(*) FROM courts c WHERE c.facility_id = f.id) AS total_courts,
                       (SELECT COUNT(*) FROM courts c WHERE c.facility_id = f.id AND c.status = 'active') AS active_courts,
                       (SELECT MIN(base_price_per_hour) FROM courts c WHERE c.facility_id = f.id AND c.status = 'active') AS min_price,
                       (SELECT MAX(base_price_per_hour) FROM courts c WHERE c.facility_id = f.id AND c.status = 'active') AS max_price
                FROM facilities f
                LEFT JOIN organizations o ON f.organization_id = o.id
                WHERE f.organization_id = ?
                ORDER BY f.id DESC";
        $facilities = $this->db->select($sql, [$orgId], 'i');
        foreach ($facilities as &$f) {
            $f['images'] = $this->getFacilityImages((int)$f['id']);
        }
        return $facilities;
    }

    public function getByOrganizationId(int $orgId): array {
        return $this->findByOrganizationId($orgId);
    }

    // ── Facility Images Management (Up to 10 photos) ──────────────────────────

    public function getFacilityImages(int $facilityId): array {
        $sql = "SELECT * FROM facility_images WHERE facility_id = ? ORDER BY is_primary DESC, id ASC";
        return $this->db->select($sql, [$facilityId], 'i');
    }

    public function getFacilityImageCount(int $facilityId): int {
        $row = $this->db->selectOne("SELECT COUNT(*) AS cnt FROM facility_images WHERE facility_id = ?", [$facilityId], 'i');
        return (int)($row['cnt'] ?? 0);
    }

    public function addFacilityImage(int $facilityId, string $imagePath): int {
        $sql = "INSERT INTO facility_images (facility_id, image_path) VALUES (?, ?)";
        $this->db->execute($sql, [$facilityId, $imagePath], 'is');
        return $this->db->getLastInsertId();
    }

    public function deleteFacilityImage(int $imageId): bool {
        return $this->db->execute("DELETE FROM facility_images WHERE id = ?", [$imageId], 'i');
    }

    public function getFacilityImageById(int $imageId): ?array {
        return $this->db->selectOne("SELECT fi.*, f.organization_id FROM facility_images fi JOIN facilities f ON fi.facility_id = f.id WHERE fi.id = ? LIMIT 1", [$imageId], 'i');
    }

    public function create(array $data): int {
        $sql = "INSERT INTO facilities (organization_id, name, description, address, city, province, phone, email, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $this->db->execute($sql, [
            $data['organization_id'],
            $data['name'],
            $data['description'] ?? null,
            $data['address'],
            $data['city'],
            $data['province'] ?? 'Bohol',
            $data['phone'] ?? null,
            $data['email'] ?? null,
            $data['status'] ?? 'active'
        ], 'issssssss');
        return $this->db->getLastInsertId();
    }

    public function getFacilityAmenities(int $facilityId): array {
        $sql = "SELECT a.name, a.icon
                FROM amenities a
                JOIN facility_amenities fa ON fa.amenity_id = a.id
                WHERE fa.facility_id = ?";
        return $this->db->select($sql, [$facilityId], 'i');
    }

    public function update(int $id, array $data): bool {
        $sql = "UPDATE facilities SET 
                name = ?, 
                address = ?, 
                city = ?, 
                description = ?, 
                phone = ?, 
                email = ?, 
                status = ? 
                WHERE id = ?";
        return $this->db->execute($sql, [
            $data['name'],
            $data['address'],
            $data['city'],
            $data['description'] ?? null,
            $data['phone'] ?? null,
            $data['email'] ?? null,
            $data['status'] ?? 'active',
            $id
        ], 'sssssssi');
    }

    /**
     * Get popular cities dynamically from active facilities
     * Ordered by active courts count, bookings count, facility count
     */
    public function getPopularCities(int $limit = 6): array {
        $sql = "
            SELECT f.city, 
                   COUNT(DISTINCT f.id) AS facility_count, 
                   COUNT(DISTINCT c.id) AS court_count,
                   COUNT(DISTINCT b.id) AS booking_count
            FROM facilities f
            LEFT JOIN courts c ON f.id = c.facility_id AND c.status = 'active'
            LEFT JOIN bookings b ON f.id = b.facility_id AND b.booking_status NOT IN ('cancelled', 'rejected')
            WHERE f.status = 'active' AND f.city IS NOT NULL AND TRIM(f.city) != ''
            GROUP BY f.city
            ORDER BY booking_count DESC, court_count DESC, facility_count DESC, f.city ASC
            LIMIT ?
        ";
        $cities = $this->db->select($sql, [$limit], 'i');
        if (empty($cities)) {
            $sqlFallback = "
                SELECT f.city, COUNT(DISTINCT f.id) AS facility_count
                FROM facilities f
                WHERE f.city IS NOT NULL AND TRIM(f.city) != ''
                GROUP BY f.city
                ORDER BY facility_count DESC, f.city ASC
                LIMIT ?
            ";
            $cities = $this->db->select($sqlFallback, [$limit], 'i');
        }
        return array_values(array_filter(array_column($cities, 'city')));
    }

    /**
     * Get all unique cities dynamically from facilities in database
     */
    public function getAllCities(): array {
        $sql = "
            SELECT DISTINCT f.city
            FROM facilities f
            WHERE f.status = 'active' AND f.city IS NOT NULL AND TRIM(f.city) != ''
            ORDER BY f.city ASC
        ";
        $cities = $this->db->select($sql);
        if (empty($cities)) {
            $sqlFallback = "
                SELECT DISTINCT f.city
                FROM facilities f
                WHERE f.city IS NOT NULL AND TRIM(f.city) != ''
                ORDER BY f.city ASC
            ";
            $cities = $this->db->select($sqlFallback);
        }
        return array_values(array_filter(array_column($cities, 'city')));
    }
}
