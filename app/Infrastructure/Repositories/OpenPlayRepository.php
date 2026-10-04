<?php
namespace App\Infrastructure\Repositories;

use App\Core\Database\Connection;
use Exception;

class OpenPlayRepository {
    private Connection $db;

    public function __construct() {
        $this->db = Connection::getInstance();
    }

    public function getPaginatedSessions(
        int $start = 0,
        int $length = 10,
        string $search = '',
        ?int $facilityId = null
    ): array {
        $whereClauses = ["1=1"];
        $params = [];
        $types = "";

        if ($facilityId !== null && $facilityId > 0) {
            $whereClauses[] = "s.facility_id = ?";
            $params[] = $facilityId;
            $types .= "i";
        }

        if (!empty($search)) {
            $whereClauses[] = "(s.title LIKE ? OR f.name LIKE ?)";
            $searchTerm = "%{$search}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $types .= "ss";
        }

        $whereSql = implode(" AND ", $whereClauses);

        $totalCountRow = $this->db->selectOne("SELECT COUNT(*) AS total FROM open_play_sessions s");
        $recordsTotal = (int)($totalCountRow['total'] ?? 0);

        $filteredCountRow = $this->db->selectOne("
            SELECT COUNT(*) AS total 
            FROM open_play_sessions s
            JOIN facilities f ON s.facility_id = f.id
            WHERE {$whereSql}
        ", $params, $types);
        $recordsFiltered = (int)($filteredCountRow['total'] ?? 0);

        $start = max(0, $start);
        $length = max(1, min(100, $length));

        $sql = "SELECT s.*, f.name AS facility_name, f.city,
                       (SELECT COUNT(*) FROM open_play_registrations r WHERE r.session_id = s.id) AS registered_players,
                       (SELECT COUNT(*) FROM open_play_registrations r WHERE r.session_id = s.id AND r.checkin_status = 'checked_in') AS checked_in_count,
                       (SELECT COALESCE(SUM(r.amount_paid), 0) FROM open_play_registrations r WHERE r.session_id = s.id AND r.payment_status = 'paid') AS total_revenue
                FROM open_play_sessions s
                JOIN facilities f ON s.facility_id = f.id
                WHERE {$whereSql}
                ORDER BY s.session_date DESC, s.start_time ASC
                LIMIT {$start}, {$length}";

        $rows = $this->db->select($sql, $params, $types);

        return [
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $rows
        ];
    }

    public function getSessionById(int $id): ?array {
        $sql = "SELECT s.*, f.name AS facility_name, f.city, f.address,
                       (SELECT COUNT(*) FROM open_play_registrations r WHERE r.session_id = s.id) AS registered_players,
                       (SELECT COUNT(*) FROM open_play_registrations r WHERE r.session_id = s.id AND r.checkin_status = 'checked_in') AS checked_in_count,
                       (SELECT COALESCE(SUM(r.amount_paid), 0) FROM open_play_registrations r WHERE r.session_id = s.id AND r.payment_status = 'paid') AS total_revenue
                FROM open_play_sessions s
                JOIN facilities f ON s.facility_id = f.id
                WHERE s.id = ? LIMIT 1";
        $session = $this->db->selectOne($sql, [$id], 'i');
        if ($session) {
            $session['registrations'] = $this->getSessionRegistrations($id);
        }
        return $session;
    }

    public function getSessionRegistrations(int $sessionId): array {
        $sql = "SELECT r.*, u.email AS user_email
                FROM open_play_registrations r
                LEFT JOIN users u ON r.user_id = u.id
                WHERE r.session_id = ?
                ORDER BY r.id DESC";
        return $this->db->select($sql, [$sessionId], 'i');
    }

    public function createSession(array $data): int {
        $sql = "INSERT INTO open_play_sessions (facility_id, title, session_date, start_time, end_time, fee_per_player, max_players, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $this->db->execute($sql, [
            (int)$data['facility_id'],
            $data['title'],
            $data['session_date'],
            $data['start_time'],
            $data['end_time'],
            (float)($data['fee_per_player'] ?? 70.00),
            (int)($data['max_players'] ?? 16),
            $data['status'] ?? 'open'
        ], 'issssdis');
        return $this->db->getLastInsertId();
    }

    public function updateSession(int $id, array $data): bool {
        $sql = "UPDATE open_play_sessions SET 
                title = ?, 
                session_date = ?, 
                start_time = ?, 
                end_time = ?, 
                fee_per_player = ?, 
                max_players = ?, 
                status = ? 
                WHERE id = ?";
        return $this->db->execute($sql, [
            $data['title'],
            $data['session_date'],
            $data['start_time'],
            $data['end_time'],
            (float)($data['fee_per_player'] ?? 70.00),
            (int)($data['max_players'] ?? 16),
            $data['status'] ?? 'open',
            $id
        ], 'ssssdisi');
    }

    public function registerPlayer(array $data): int {
        $sessionId = (int)$data['session_id'];
        $session = $this->getSessionById($sessionId);
        if (!$session) {
            throw new Exception("Open Play session not found.");
        }
        if ($session['status'] === 'full' || $session['registered_players'] >= $session['max_players']) {
            throw new Exception("This session has reached maximum player capacity.");
        }

        $sql = "INSERT INTO open_play_registrations (session_id, user_id, player_name, player_phone, payment_status, amount_paid, checkin_status)
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $this->db->execute($sql, [
            $sessionId,
            !empty($data['user_id']) ? (int)$data['user_id'] : null,
            $data['player_name'],
            $data['player_phone'] ?? null,
            $data['payment_status'] ?? 'paid',
            (float)($data['amount_paid'] ?? $session['fee_per_player']),
            $data['checkin_status'] ?? 'registered'
        ], 'iisssds');

        $regId = $this->db->getLastInsertId();

        // Check if session is now full
        $cntRow = $this->db->selectOne("SELECT COUNT(*) AS cnt FROM open_play_registrations WHERE session_id = ?", [$sessionId], 'i');
        if ((int)$cntRow['cnt'] >= (int)$session['max_players']) {
            $this->db->execute("UPDATE open_play_sessions SET status = 'full' WHERE id = ?", [$sessionId], 'i');
        }

        return $regId;
    }

    public function checkinPlayer(int $registrationId): bool {
        $sql = "UPDATE open_play_registrations 
                SET checkin_status = 'checked_in', checked_in_at = NOW() 
                WHERE id = ?";
        return $this->db->execute($sql, [$registrationId], 'i');
    }

    public function getPaginatedRoster(
        int $sessionId,
        int $start = 0,
        int $length = 10,
        string $search = ''
    ): array {
        $whereClauses = ["r.session_id = ?"];
        $params = [$sessionId];
        $types = "i";

        if (!empty($search)) {
            $whereClauses[] = "(r.player_name LIKE ? OR r.player_phone LIKE ?)";
            $searchTerm = "%{$search}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $types .= "ss";
        }

        $whereSql = implode(" AND ", $whereClauses);

        $totalCountRow = $this->db->selectOne("SELECT COUNT(*) AS total FROM open_play_registrations r WHERE r.session_id = ?", [$sessionId], 'i');
        $recordsTotal = (int)($totalCountRow['total'] ?? 0);

        $filteredCountRow = $this->db->selectOne("
            SELECT COUNT(*) AS total 
            FROM open_play_registrations r
            WHERE {$whereSql}
        ", $params, $types);
        $recordsFiltered = (int)($filteredCountRow['total'] ?? 0);

        $start = max(0, $start);
        $length = max(1, min(100, $length));

        $sql = "SELECT r.*, u.email AS user_email
                FROM open_play_registrations r
                LEFT JOIN users u ON r.user_id = u.id
                WHERE {$whereSql}
                ORDER BY r.id DESC
                LIMIT {$start}, {$length}";

        $rows = $this->db->select($sql, $params, $types);

        return [
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $rows
        ];
    }

    public function getMetrics(?int $facilityId = null): array {
        $facilityWhere = ($facilityId && $facilityId > 0) ? "WHERE facility_id = {$facilityId}" : "";

        $activeRow = $this->db->selectOne("SELECT COUNT(*) AS cnt FROM open_play_sessions {$facilityWhere}");
        $totalSessions = (int)($activeRow['cnt'] ?? 0);

        $playersRow = $this->db->selectOne("
            SELECT COUNT(*) AS total_registered,
                   SUM(CASE WHEN r.checkin_status = 'checked_in' THEN 1 ELSE 0 END) AS total_checked_in,
                   COALESCE(SUM(CASE WHEN r.payment_status = 'paid' THEN r.amount_paid ELSE 0 END), 0) AS total_revenue
            FROM open_play_registrations r
            JOIN open_play_sessions s ON r.session_id = s.id
            " . ($facilityId && $facilityId > 0 ? "WHERE s.facility_id = {$facilityId}" : "")
        );

        return [
            'total_sessions' => $totalSessions,
            'total_registered' => (int)($playersRow['total_registered'] ?? 0),
            'total_checked_in' => (int)($playersRow['total_checked_in'] ?? 0),
            'total_revenue' => (float)($playersRow['total_revenue'] ?? 0.00)
        ];
    }

    public function getPlayerSuggestions(?int $sessionId = null): array {
        if ($sessionId && $sessionId > 0) {
            $sql = "SELECT DISTINCT 
                        r.user_id, 
                        TRIM(r.player_name) AS player_name, 
                        COALESCE(r.player_phone, u.phone, '') AS player_phone
                    FROM open_play_registrations r
                    LEFT JOIN users u ON r.user_id = u.id
                    WHERE r.session_id = ? AND r.player_name IS NOT NULL AND TRIM(r.player_name) != ''
                    ORDER BY r.player_name ASC";
            return $this->db->select($sql, [$sessionId], 'i');
        }

        $sql = "SELECT DISTINCT 
                    u.id AS user_id, 
                    TRIM(CONCAT(COALESCE(u.first_name,''), ' ', COALESCE(u.last_name,''))) AS player_name, 
                    COALESCE(u.phone, '') AS player_phone
                FROM users u
                WHERE u.first_name IS NOT NULL AND TRIM(u.first_name) != ''
                
                UNION
                
                SELECT DISTINCT 
                    r.user_id, 
                    TRIM(r.player_name) AS player_name, 
                    COALESCE(r.player_phone, '') AS player_phone
                FROM open_play_registrations r
                WHERE r.player_name IS NOT NULL AND TRIM(r.player_name) != ''
                
                ORDER BY player_name ASC
                LIMIT 150";
        return $this->db->select($sql);
    }
}
