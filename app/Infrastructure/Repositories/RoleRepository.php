<?php
namespace App\Infrastructure\Repositories;

use App\Core\Database\Connection;

class RoleRepository {
    private Connection $db;

    public function __construct() {
        $this->db = Connection::getInstance();
    }

    public function getAllRolesWithCounts(bool $excludeAdminRoles = false): array {
        $whereSql = $excludeAdminRoles ? " WHERE r.name NOT IN ('super_admin', 'platform_admin', 'finance_admin', 'support_staff') " : "";
        $sql = "SELECT r.*,
                       COUNT(DISTINCT u.id) AS user_count,
                       COUNT(DISTINCT rp.permission_id) AS permission_count
                FROM roles r
                LEFT JOIN users u ON u.role_id = r.id
                LEFT JOIN role_permissions rp ON rp.role_id = r.id
                {$whereSql}
                GROUP BY r.id
                ORDER BY r.id ASC";
        return $this->db->select($sql);
    }

    public function findById(int $id): ?array {
        return $this->db->selectOne("SELECT * FROM roles WHERE id = ?", [$id]);
    }

    public function createRole(string $name, string $displayName): int {
        $sql = "INSERT INTO roles (name, display_name) VALUES (?, ?)";
        $this->db->execute($sql, [$name, $displayName], 'ss');
        return $this->db->getLastInsertId();
    }

    public function updateRole(int $id, string $displayName): bool {
        $sql = "UPDATE roles SET display_name = ? WHERE id = ?";
        return $this->db->execute($sql, [$displayName, $id], 'si');
    }

    public function getRolePermissions(int $roleId): array {
        $sql = "SELECT p.id FROM permissions p
                JOIN role_permissions rp ON rp.permission_id = p.id
                WHERE rp.role_id = ?";
        $rows = $this->db->select($sql, [$roleId]);
        return array_map('intval', array_column($rows, 'id'));
    }

    public function getAllPermissionsGrouped(): array {
        $permissions = $this->db->select("SELECT * FROM permissions ORDER BY name ASC");
        $grouped = [];

        foreach ($permissions as $p) {
            $parts = explode('.', $p['name']);
            $module = count($parts) > 1 ? ucfirst($parts[0]) : 'General';
            if (!isset($grouped[$module])) {
                $grouped[$module] = [];
            }
            $grouped[$module][] = $p;
        }

        return $grouped;
    }

    public function getAllPermissionsWithRoles(): array {
        $sql = "SELECT p.*, GROUP_CONCAT(r.display_name SEPARATOR ', ') AS assigned_roles
                FROM permissions p
                LEFT JOIN role_permissions rp ON rp.permission_id = p.id
                LEFT JOIN roles r ON rp.role_id = r.id
                GROUP BY p.id
                ORDER BY p.name ASC";
        return $this->db->select($sql);
    }

    public function assignPermissionsToRole(int $roleId, array $permissionIds): void {
        $this->db->beginTransaction();
        try {
            // Delete existing role permissions
            $this->db->execute("DELETE FROM role_permissions WHERE role_id = ?", [$roleId], 'i');

            // Insert new role permissions
            if (!empty($permissionIds)) {
                $validIds = array_map('intval', array_filter($permissionIds, 'is_numeric'));
                foreach ($validIds as $pId) {
                    $this->db->execute("INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)", [$roleId, $pId], 'ii');
                }
            }
            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    public function getUserCountForRole(int $id): int {
        $row = $this->db->selectOne("SELECT COUNT(*) AS cnt FROM users WHERE role_id = ?", [$id]);
        return (int)($row['cnt'] ?? 0);
    }

    public function deleteRole(int $id): bool {
        return $this->db->execute("DELETE FROM roles WHERE id = ?", [$id], 'i');
    }
}
