<?php
namespace App\Presentation\Controllers;

use App\Core\Database\Connection;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Auth\Auth;

class CalendarController {
    private Connection $db;

    public function __construct() {
        $this->db = Connection::getInstance();
    }

    public function getEvents(Request $request): void {
        Auth::requirePermission('calendar.view');

        $startStr = $request->get('start');
        $endStr = $request->get('end');
        $facilityId = $request->get('facility_id') ? (int)$request->get('facility_id') : null;
        $eventType = $request->get('event_type', 'all');

        $role = Auth::role();
        $orgId = null;
        if ($role !== 'super_admin' && $role !== 'platform_admin') {
            $orgId = Auth::organizationId();
        }

        $startDate = $startStr ? date('Y-m-d', strtotime($startStr)) : date('Y-m-01');
        $endDate = $endStr ? date('Y-m-d', strtotime($endStr)) : date('Y-m-t');

        $events = [];

        // 1. Fetch Court Bookings
        if ($eventType === 'all' || $eventType === 'booking') {
            $bookingWhere = ["b.booking_date BETWEEN ? AND ?", "b.booking_status != 'cancelled'"];
            $bParams = [$startDate, $endDate];
            $bTypes = "ss";

            if ($facilityId && $facilityId > 0) {
                $bookingWhere[] = "c.facility_id = ?";
                $bParams[] = $facilityId;
                $bTypes .= "i";
            } elseif ($orgId && $orgId > 0) {
                $bookingWhere[] = "(f.organization_id = ? OR b.organization_id = ?)";
                $bParams[] = $orgId;
                $bParams[] = $orgId;
                $bTypes .= "ii";
            }

            $bWhereSql = implode(" AND ", $bookingWhere);

            $sql = "SELECT b.*, c.name AS court_name, f.name AS facility_name, f.city,
                           CONCAT(u.first_name, ' ', u.last_name) AS customer_name,
                           u.phone AS customer_phone
                    FROM bookings b
                    JOIN courts c ON b.court_id = c.id
                    JOIN facilities f ON c.facility_id = f.id
                    LEFT JOIN users u ON b.customer_id = u.id
                    WHERE {$bWhereSql}
                    ORDER BY b.booking_date ASC, b.start_time ASC";

            $bookings = $this->db->select($sql, $bParams, $bTypes);

            foreach ($bookings as $b) {
                $startIso = $b['booking_date'] . 'T' . $b['start_time'];
                $endIso = $b['booking_date'] . 'T' . $b['end_time'];
                $st = strtolower($b['booking_status'] ?? 'confirmed');

                $bgColor = '#0b4d40'; // Green
                $borderColor = '#dfff4f'; // Lime border

                if ($st === 'pending' || $st === 'awaiting_payment') {
                  $bgColor = '#a16207'; // Amber
                } elseif ($st === 'completed') {
                  $bgColor = '#0284c7'; // Sky
                }

                $custName = trim($b['customer_name'] ?? '');
                if (empty($custName)) $custName = 'Guest Player';

                $events[] = [
                    'id' => 'booking_' . $b['id'],
                    'title' => $b['court_name'] . ' — ' . $custName,
                    'start' => $startIso,
                    'end' => $endIso,
                    'backgroundColor' => $bgColor,
                    'borderColor' => $borderColor,
                    'textColor' => '#ffffff',
                    'className' => 'cal-event-booking',
                    'extendedProps' => [
                        'type' => 'booking',
                        'booking_id' => $b['id'],
                        'booking_reference' => $b['booking_reference'] ?? ('BK-' . $b['id']),
                        'court_name' => $b['court_name'],
                        'facility_name' => $b['facility_name'],
                        'customer_name' => $custName,
                        'customer_phone' => $b['customer_phone'] ?? 'N/A',
                        'total_price' => (float)($b['total_amount'] ?? 0),
                        'payment_status' => $b['payment_status'] ?? 'paid',
                        'status' => $b['booking_status'] ?? 'confirmed',
                        'booking_date' => $b['booking_date'],
                        'start_time' => (string)$b['start_time'],
                        'end_time' => (string)$b['end_time']
                    ]
                ];
            }
        }

        // 2. Fetch Open Play Sessions
        if ($eventType === 'all' || $eventType === 'open_play') {
            $opWhere = ["s.session_date BETWEEN ? AND ?", "s.status != 'cancelled'"];
            $opParams = [$startDate, $endDate];
            $opTypes = "ss";

            if ($facilityId && $facilityId > 0) {
                $opWhere[] = "s.facility_id = ?";
                $opParams[] = $facilityId;
                $opTypes .= "i";
            } elseif ($orgId && $orgId > 0) {
                $opWhere[] = "f.organization_id = ?";
                $opParams[] = $orgId;
                $opTypes .= "i";
            }

            $opWhereSql = implode(" AND ", $opWhere);

            $sql = "SELECT s.*, f.name AS facility_name, f.city,
                           (SELECT COUNT(*) FROM open_play_registrations r WHERE r.session_id = s.id) AS registered_players,
                           (SELECT COUNT(*) FROM open_play_registrations r WHERE r.session_id = s.id AND r.checkin_status = 'checked_in') AS checked_in_count
                    FROM open_play_sessions s
                    JOIN facilities f ON s.facility_id = f.id
                    WHERE {$opWhereSql}
                    ORDER BY s.session_date ASC, s.start_time ASC";

            $sessions = $this->db->select($sql, $opParams, $opTypes);

            foreach ($sessions as $s) {
                $startIso = $s['session_date'] . 'T' . $s['start_time'];
                $endIso = $s['session_date'] . 'T' . $s['end_time'];
                $regCount = (int)($s['registered_players'] ?? 0);
                $maxCap = (int)($s['max_players'] ?? 16);

                $bgColor = '#ea580c'; // Coral / Orange for Open Play
                $borderColor = '#0d211d';

                if ($s['status'] === 'full' || $regCount >= $maxCap) {
                  $bgColor = '#be123c'; // Dark Red
                }

                $events[] = [
                    'id' => 'openplay_' . $s['id'],
                    'title' => '🏓 ' . $s['title'] . ' (' . $regCount . '/' . $maxCap . ')',
                    'start' => $startIso,
                    'end' => $endIso,
                    'backgroundColor' => $bgColor,
                    'borderColor' => $borderColor,
                    'textColor' => '#ffffff',
                    'className' => 'cal-event-openplay',
                    'extendedProps' => [
                        'type' => 'open_play',
                        'session_id' => $s['id'],
                        'title' => $s['title'],
                        'facility_name' => $s['facility_name'],
                        'fee_per_player' => (float)($s['fee_per_player'] ?? 70.00),
                        'max_players' => $maxCap,
                        'registered_players' => $regCount,
                        'checked_in_count' => (int)($s['checked_in_count'] ?? 0),
                        'status' => $s['status'] ?? 'open',
                        'session_date' => $s['session_date'],
                        'start_time' => (string)$s['start_time'],
                        'end_time' => (string)$s['end_time']
                    ]
                ];
            }
        }

        Response::json($events);
    }
}
