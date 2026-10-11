<?php
namespace App\Infrastructure\Repositories;

use App\Core\Database\Connection;

class UserRepository {
    private Connection $db;

    public function __construct() {
        $this->db = Connection::getInstance();
    }

    public function findByEmail(string $email): ?array {
        $sql = "SELECT * FROM users WHERE email = ? LIMIT 1";
        return $this->db->selectOne($sql, [$email]);
    }

    public function findByUsername(string $username): ?array {
        $sql = "SELECT * FROM users WHERE username = ? LIMIT 1";
        return $this->db->selectOne($sql, [$username]);
    }

    public function findByPhone(string $phone): ?array {
        $sql = "SELECT * FROM users WHERE phone = ? LIMIT 1";
        return $this->db->selectOne($sql, [$phone]);
    }

    public function findByEmailOrUsername(string $identifier): ?array {
        $sql = "SELECT u.*, r.name AS role_name, r.display_name AS role_display, o.id AS organization_id
                FROM users u
                JOIN roles r ON u.role_id = r.id
                LEFT JOIN organizations o ON o.owner_id = u.id
                WHERE (u.email = ? OR u.username = ?) AND u.deleted_at IS NULL LIMIT 1";
        return $this->db->selectOne($sql, [$identifier, $identifier]);
    }

    public function findById(int $id): ?array {
        $sql = "SELECT u.id, u.username, u.first_name, u.last_name, u.email, u.phone, u.status, u.created_at, r.name AS role_name, r.display_name AS role_display, o.id AS organization_id
                FROM users u
                JOIN roles r ON u.role_id = r.id
                LEFT JOIN organizations o ON o.owner_id = u.id
                WHERE u.id = ? AND u.deleted_at IS NULL LIMIT 1";
        return $this->db->selectOne($sql, [$id], 'i');
    }

    public function getUserFullSystemInfo(int $id): ?array {
        $sql = "SELECT u.id, u.username, u.first_name, u.last_name, u.email, u.phone, u.status, u.created_at, 
                       r.name AS role_name, r.display_name AS role_display, 
                       o.name AS organization_name, o.tax_id AS organization_tax_id, o.status AS organization_status,
                       o.email AS organization_email, o.phone AS organization_phone,
                       (SELECT COUNT(*) FROM bookings b WHERE b.customer_id = u.id) AS total_bookings_count,
                       (SELECT COALESCE((SELECT COUNT(*) FROM facilities f WHERE f.organization_id = o.id), 0)) AS total_facilities_count
                FROM users u
                JOIN roles r ON u.role_id = r.id
                LEFT JOIN organizations o ON o.owner_id = u.id
                WHERE u.id = ? AND u.deleted_at IS NULL LIMIT 1";
        return $this->db->selectOne($sql, [$id], 'i');
    }


    public function create(array $data): int {
        $sql = "INSERT INTO users (username, first_name, last_name, email, password_hash, phone, role_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $this->db->execute($sql, [
            $data['username'] ?? null,
            $data['first_name'],
            $data['last_name'],
            $data['email'],
            $data['password_hash'],
            $data['phone'] ?? null,
            $data['role_id'],
            $data['status'] ?? 'active'
        ], 'ssssssis');
        $id = $this->db->getLastInsertId();
        (new ReferralRepository())->ensureCode($id);
        return $id;
    }

    public function getRoleIdByName(string $name): ?int {
        $role = $this->db->selectOne("SELECT id FROM roles WHERE name = ? LIMIT 1", [$name]);
        return $role ? (int)$role['id'] : null;
    }

    public function getAllUsers(): array {
        $sql = "SELECT u.id, u.username, u.first_name, u.last_name, u.email, u.phone, u.status, u.role_id, u.created_at, r.name AS role_name, r.display_name AS role_display
                FROM users u
                JOIN roles r ON u.role_id = r.id
                WHERE u.deleted_at IS NULL
                ORDER BY u.id DESC";
        return $this->db->select($sql);
    }

    public function getPaginatedUsers(int $start = 0, int $length = 10, string $search = '', string $orderBy = 'u.id', string $orderDir = 'DESC', bool $excludeAdminRoles = false): array {
        $params = [];
        $whereClause = " WHERE u.deleted_at IS NULL ";

        if ($excludeAdminRoles) {
            $whereClause .= " AND r.name NOT IN ('super_admin', 'platform_admin', 'finance_admin', 'support_staff') ";
        }

        if (!empty($search)) {
            $whereClause .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.username LIKE ? OR u.email LIKE ? OR u.phone LIKE ? OR r.display_name LIKE ? OR u.status LIKE ?) ";
            $s = "%{$search}%";
            $params = [$s, $s, $s, $s, $s, $s, $s];
        }

        // Count Total Records
        $totalSql = "SELECT COUNT(*) AS cnt FROM users u JOIN roles r ON u.role_id = r.id WHERE u.deleted_at IS NULL " . ($excludeAdminRoles ? " AND r.name NOT IN ('super_admin', 'platform_admin', 'finance_admin', 'support_staff') " : "");
        $totalRow = $this->db->selectOne($totalSql);
        $totalRecords = (int)($totalRow['cnt'] ?? 0);

        // Count Filtered Records
        $filteredRow = $this->db->selectOne("SELECT COUNT(*) AS cnt FROM users u JOIN roles r ON u.role_id = r.id {$whereClause}", $params);
        $filteredRecords = (int)($filteredRow['cnt'] ?? 0);

        // Allowed Sort Columns
        $allowedSort = [
            '0' => 'u.first_name',
            '1' => 'u.username',
            '2' => 'r.display_name',
            '3' => 'u.status'
        ];
        $sortCol = $allowedSort[$orderBy] ?? 'u.id';
        $orderDir = strtoupper($orderDir) === 'ASC' ? 'ASC' : 'DESC';

        $sql = "SELECT u.id, u.username, u.first_name, u.last_name, u.email, u.phone, u.status, u.role_id, u.created_at, r.name AS role_name, r.display_name AS role_display
                FROM users u
                JOIN roles r ON u.role_id = r.id
                {$whereClause} ORDER BY {$sortCol} {$orderDir} LIMIT {$start}, {$length}";

        $data = $this->db->select($sql, $params);

        return [
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data
        ];
    }

    public function updateUser(int $id, array $data): bool {
        $sql = "UPDATE users SET first_name = ?, last_name = ?, username = ?, email = ?, phone = ? WHERE id = ?";
        return $this->db->execute($sql, [
            $data['first_name'],
            $data['last_name'],
            $data['username'] ?? null,
            $data['email'],
            $data['phone'] ?? null,
            $id
        ], 'sssssi');
    }

    public function updatePassword(int $id, string $passwordHash): bool {
        $sql = "UPDATE users SET password_hash = ? WHERE id = ?";
        return $this->db->execute($sql, [$passwordHash, $id], 'si');
    }

    public function updateStatus(int $id, string $status): bool {
        $sql = "UPDATE users SET status = ? WHERE id = ?";
        return $this->db->execute($sql, [$status, $id], 'si');
    }

    public function deleteUser(int $id): bool {
        $sql = "UPDATE users SET status = 'deleted', deleted_at = NOW() WHERE id = ?";
        return $this->db->execute($sql, [$id], 'i');
    }
}
