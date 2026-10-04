<?php
namespace App\Infrastructure\Repositories;

use App\Core\Database\Connection;

class FeedbackReportRepository {
    private Connection $db;

    public function __construct() {
        $this->db = Connection::getInstance();
    }

    public function create(
        int $userId,
        string $reportType,
        string $title,
        string $description,
        string $category = 'general',
        string $priority = 'medium'
    ): int {
        $sql = "INSERT INTO feedback_reports (user_id, report_type, title, description, category, priority, status)
                VALUES (?, ?, ?, ?, ?, ?, 'pending')";
        $this->db->execute($sql, [$userId, $reportType, $title, $description, $category, $priority], 'isssss');
        return $this->db->getLastInsertId();
    }

    public function getByUserId(int $userId): array {
        $sql = "SELECT fr.*, 
                       CONCAT(u.first_name, ' ', u.last_name) AS submitter_name,
                       u.email AS submitter_email,
                       CONCAT(adm.first_name, ' ', adm.last_name) AS resolver_name
                FROM feedback_reports fr
                JOIN users u ON fr.user_id = u.id
                LEFT JOIN users adm ON fr.resolved_by = adm.id
                WHERE fr.user_id = ?
                ORDER BY fr.created_at DESC";
        return $this->db->select($sql, [$userId], 'i');
    }

    public function getAll(array $filters = []): array {
        $where = ["1=1"];
        $params = [];
        $types = "";

        if (!empty($filters['report_type']) && $filters['report_type'] !== 'all') {
            $where[] = "fr.report_type = ?";
            $params[] = $filters['report_type'];
            $types .= "s";
        }

        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $where[] = "fr.status = ?";
            $params[] = $filters['status'];
            $types .= "s";
        }

        if (!empty($filters['priority']) && $filters['priority'] !== 'all') {
            $where[] = "fr.priority = ?";
            $params[] = $filters['priority'];
            $types .= "s";
        }

        if (!empty($filters['role']) && $filters['role'] !== 'all') {
            $where[] = "r.name = ?";
            $params[] = $filters['role'];
            $types .= "s";
        }

        if (!empty($filters['search'])) {
            $s = '%' . trim($filters['search']) . '%';
            $where[] = "(fr.title LIKE ? OR fr.description LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)";
            $params[] = $s;
            $params[] = $s;
            $params[] = $s;
            $params[] = $s;
            $params[] = $s;
            $types .= "sssss";
        }

        $whereClause = implode(" AND ", $where);

        $sql = "SELECT fr.*,
                       CONCAT(u.first_name, ' ', u.last_name) AS submitter_name,
                       u.email AS submitter_email,
                       u.phone AS submitter_phone,
                       r.name AS submitter_role,
                       r.display_name AS role_display_name,
                       o.name AS organization_name,
                       CONCAT(adm.first_name, ' ', adm.last_name) AS resolver_name
                FROM feedback_reports fr
                JOIN users u ON fr.user_id = u.id
                JOIN roles r ON u.role_id = r.id
                LEFT JOIN organizations o ON o.owner_id = u.id
                LEFT JOIN users adm ON fr.resolved_by = adm.id
                WHERE {$whereClause}
                ORDER BY 
                    CASE fr.priority 
                        WHEN 'critical' THEN 1 
                        WHEN 'high' THEN 2 
                        WHEN 'medium' THEN 3 
                        WHEN 'low' THEN 4 
                        ELSE 5 
                    END ASC,
                    fr.created_at DESC";

        return $this->db->select($sql, $params, $types);
    }

    public function getById(int $id): ?array {
        $sql = "SELECT fr.*,
                       CONCAT(u.first_name, ' ', u.last_name) AS submitter_name,
                       u.email AS submitter_email,
                       u.phone AS submitter_phone,
                       r.name AS submitter_role,
                       r.display_name AS role_display_name,
                       o.name AS organization_name,
                       CONCAT(adm.first_name, ' ', adm.last_name) AS resolver_name
                FROM feedback_reports fr
                JOIN users u ON fr.user_id = u.id
                JOIN roles r ON u.role_id = r.id
                LEFT JOIN organizations o ON o.owner_id = u.id
                LEFT JOIN users adm ON fr.resolved_by = adm.id
                WHERE fr.id = ?
                LIMIT 1";
        return $this->db->selectOne($sql, [$id], 'i');
    }

    public function updateStatus(
        int $id,
        string $status,
        ?string $adminResponse,
        ?string $priority = null,
        ?int $resolvedBy = null
    ): bool {
        $setParts = ["status = ?", "admin_response = ?"];
        $params = [$status, $adminResponse];
        $types = "ss";

        if ($priority !== null) {
            $setParts[] = "priority = ?";
            $params[] = $priority;
            $types .= "s";
        }

        if (in_array($status, ['resolved', 'declined'])) {
            $setParts[] = "resolved_by = ?";
            $params[] = $resolvedBy;
            $types .= "i";

            $setParts[] = "resolved_at = NOW()";
        }

        $params[] = $id;
        $types .= "i";

        $sql = "UPDATE feedback_reports SET " . implode(", ", $setParts) . " WHERE id = ?";
        return $this->db->execute($sql, $params, $types) > 0;
    }

    public function getStats(): array {
        $total = (int)($this->db->selectOne("SELECT COUNT(*) AS c FROM feedback_reports")['c'] ?? 0);
        $bugs = (int)($this->db->selectOne("SELECT COUNT(*) AS c FROM feedback_reports WHERE report_type = 'bug'")['c'] ?? 0);
        $features = (int)($this->db->selectOne("SELECT COUNT(*) AS c FROM feedback_reports WHERE report_type = 'feature_request'")['c'] ?? 0);
        $pending = (int)($this->db->selectOne("SELECT COUNT(*) AS c FROM feedback_reports WHERE status = 'pending'")['c'] ?? 0);
        $inProgress = (int)($this->db->selectOne("SELECT COUNT(*) AS c FROM feedback_reports WHERE status IN ('under_review', 'in_progress')")['c'] ?? 0);
        $resolved = (int)($this->db->selectOne("SELECT COUNT(*) AS c FROM feedback_reports WHERE status = 'resolved'")['c'] ?? 0);
        $critical = (int)($this->db->selectOne("SELECT COUNT(*) AS c FROM feedback_reports WHERE priority = 'critical' AND status != 'resolved'")['c'] ?? 0);

        return [
            'total' => $total,
            'bugs' => $bugs,
            'features' => $features,
            'pending' => $pending,
            'in_progress' => $inProgress,
            'resolved' => $resolved,
            'critical' => $critical
        ];
    }
}
