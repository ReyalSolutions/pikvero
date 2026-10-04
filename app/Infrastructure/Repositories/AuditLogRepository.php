<?php
namespace App\Infrastructure\Repositories;

use App\Core\Database\Connection;

class AuditLogRepository {
    private Connection $db;

    public function __construct() {
        $this->db = Connection::getInstance();
    }

    public function log(?int $userId, string $action, string $module, ?string $description = null): void {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

        $sql = "INSERT INTO audit_logs (user_id, action, module, description, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?)";
        $this->db->execute($sql, [$userId, $action, $module, $description, $ip, substr($ua, 0, 255)], 'isssss');
    }

    public function getAll(): array {
        $sql = "SELECT a.*, u.email, CONCAT(u.first_name, ' ', u.last_name) AS user_name
                FROM audit_logs a
                LEFT JOIN users u ON a.user_id = u.id
                ORDER BY a.id DESC LIMIT 100";
        return $this->db->select($sql);
    }

    public function getPaginatedLogs(int $start = 0, int $length = 10, string $search = '', string $orderBy = '0', string $orderDir = 'DESC'): array {
        $params = [];
        $whereSql = "";

        if (!empty($search)) {
            $whereSql = " WHERE a.action LIKE ? OR a.module LIKE ? OR a.description LIKE ? OR a.ip_address LIKE ? OR CONCAT(u.first_name, ' ', u.last_name) LIKE ? OR u.email LIKE ? ";
            $s = "%{$search}%";
            $params = [$s, $s, $s, $s, $s, $s];
        }

        // Count Total Records
        $totalRow = $this->db->selectOne("SELECT COUNT(*) AS cnt FROM audit_logs");
        $totalRecords = (int)($totalRow['cnt'] ?? 0);

        // Count Filtered Records
        $filteredRow = $this->db->selectOne("SELECT COUNT(*) AS cnt FROM audit_logs a LEFT JOIN users u ON a.user_id = u.id {$whereSql}", $params);
        $filteredRecords = (int)($filteredRow['cnt'] ?? 0);

        // Allowed Sort Columns (0: Checkbox, 1: Action, 2: Module, 3: Description, 4: User, 5: IP/Date)
        $allowedSort = [
            '1' => 'a.action',
            '2' => 'a.module',
            '3' => 'a.description',
            '4' => 'u.first_name',
            '5' => 'a.id'
        ];
        $sortCol = $allowedSort[$orderBy] ?? 'a.id';
        $orderDir = strtoupper($orderDir) === 'ASC' ? 'ASC' : 'DESC';

        $sql = "SELECT a.id, a.action, a.module, a.description, a.ip_address, a.created_at, u.email, CONCAT(u.first_name, ' ', u.last_name) AS user_name
                FROM audit_logs a
                LEFT JOIN users u ON a.user_id = u.id
                {$whereSql} ORDER BY {$sortCol} {$orderDir} LIMIT {$start}, {$length}";

        $data = $this->db->select($sql, $params);

        return [
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data
        ];
    }

    public function deleteByIds(array $ids): int {
        if (empty($ids)) return 0;
        $validIds = array_map('intval', array_filter($ids, 'is_numeric'));
        if (empty($validIds)) return 0;

        $placeholders = implode(',', array_fill(0, count($validIds), '?'));
        $sql = "DELETE FROM audit_logs WHERE id IN ({$placeholders})";
        $this->db->execute($sql, $validIds);
        return count($validIds);
    }
}
