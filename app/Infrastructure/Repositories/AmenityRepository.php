<?php
namespace App\Infrastructure\Repositories;

use App\Core\Database\Connection;
use Exception;

class AmenityRepository {
    private Connection $db;

    public function __construct() {
        $this->db = Connection::getInstance();
    }

    public function getAllWithCounts(): array {
        $sql = "SELECT a.*, 
                       (SELECT COUNT(DISTINCT fa.facility_id) FROM facility_amenities fa WHERE fa.amenity_id = a.id) AS assigned_facilities_count
                FROM amenities a
                ORDER BY a.id ASC";
        return $this->db->select($sql);
    }

    public function getPaginatedDataTables(
        int $start = 0,
        int $length = 10,
        string $search = '',
        string $orderColIndex = '0',
        string $orderDir = 'ASC'
    ): array {
        $whereClauses = ["1=1"];
        $params = [];
        $types = "";

        if (!empty($search)) {
            $whereClauses[] = "(a.name LIKE ? OR a.icon LIKE ?)";
            $searchTerm = "%{$search}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $types .= "ss";
        }

        $whereSql = implode(" AND ", $whereClauses);

        $totalCountRow = $this->db->selectOne("SELECT COUNT(*) AS total FROM amenities a");
        $recordsTotal = (int)($totalCountRow['total'] ?? 0);

        $filteredCountRow = $this->db->selectOne("SELECT COUNT(*) AS total FROM amenities a WHERE {$whereSql}", $params, $types);
        $recordsFiltered = (int)($filteredCountRow['total'] ?? 0);

        $orderCols = [
            '0' => 'a.id',
            '1' => 'a.name',
            '2' => 'a.icon',
            '3' => 'assigned_facilities_count'
        ];
        $colName = $orderCols[$orderColIndex] ?? 'a.id';
        $dir = (strtoupper($orderDir) === 'DESC') ? 'DESC' : 'ASC';

        $start = max(0, $start);
        $length = max(1, min(100, $length));

        $sql = "SELECT a.*, 
                       (SELECT COUNT(DISTINCT fa.facility_id) FROM facility_amenities fa WHERE fa.amenity_id = a.id) AS assigned_facilities_count
                FROM amenities a
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

    public function getById(int $id): ?array {
        return $this->db->selectOne("SELECT * FROM amenities WHERE id = ? LIMIT 1", [$id], 'i');
    }

    public function create(array $data): int {
        $sql = "INSERT INTO amenities (name, icon) VALUES (?, ?)";
        $this->db->execute($sql, [
            $data['name'],
            $data['icon'] ?? 'bi-check-circle'
        ], 'ss');
        return $this->db->getLastInsertId();
    }

    public function update(int $id, array $data): bool {
        $sql = "UPDATE amenities SET name = ?, icon = ? WHERE id = ?";
        return $this->db->execute($sql, [
            $data['name'],
            $data['icon'] ?? 'bi-check-circle',
            $id
        ], 'ssi');
    }

    public function delete(int $id): bool {
        $this->db->execute("DELETE FROM facility_amenities WHERE amenity_id = ?", [$id], 'i');
        $this->db->execute("DELETE FROM court_amenities WHERE amenity_id = ?", [$id], 'i');
        return $this->db->execute("DELETE FROM amenities WHERE id = ?", [$id], 'i');
    }

    public function getFacilityAssignments(int $facilityId): array {
        $rows = $this->db->select("SELECT amenity_id FROM facility_amenities WHERE facility_id = ?", [$facilityId], 'i');
        return array_column($rows, 'amenity_id');
    }

    public function saveFacilityAssignments(int $facilityId, array $amenityIds): bool {
        $this->db->beginTransaction();
        try {
            // Delete old facility amenity assignments
            $this->db->execute("DELETE FROM facility_amenities WHERE facility_id = ?", [$facilityId], 'i');

            // Insert new assignments
            foreach ($amenityIds as $amId) {
                $amId = (int)$amId;
                if ($amId > 0) {
                    $this->db->execute("INSERT IGNORE INTO facility_amenities (facility_id, amenity_id) VALUES (?, ?)", [$facilityId, $amId], 'ii');
                }
            }

            // Sync to all courts belonging to this facility
            $courts = $this->db->select("SELECT id FROM courts WHERE facility_id = ?", [$facilityId], 'i');
            foreach ($courts as $c) {
                $courtId = (int)$c['id'];
                $this->db->execute("DELETE FROM court_amenities WHERE court_id = ?", [$courtId], 'i');
                foreach ($amenityIds as $amId) {
                    $amId = (int)$amId;
                    if ($amId > 0) {
                        $this->db->execute("INSERT IGNORE INTO court_amenities (court_id, amenity_id) VALUES (?, ?)", [$courtId, $amId], 'ii');
                    }
                }
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
}
