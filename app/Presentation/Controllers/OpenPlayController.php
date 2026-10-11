<?php
namespace App\Presentation\Controllers;

use App\Infrastructure\Repositories\OpenPlayRepository;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Auth\Auth;
use App\Core\Database\Connection;
use Exception;

class OpenPlayController {
    private OpenPlayRepository $repo;

    public function __construct() {
        $this->repo = new OpenPlayRepository();
    }

    public function getPaginatedDataTables(Request $request): void {
        Auth::requirePermission('open_play.view');

        $start = (int)$request->get('start', 0);
        $length = (int)$request->get('length', 10);
        $search = (string)($request->get('search')['value'] ?? $request->get('search', ''));
        $facilityId = $request->get('facility_id') ? (int)$request->get('facility_id') : null;

        $role = Auth::role();
        if ($role !== 'super_admin' && $role !== 'platform_admin') {
            $orgId = Auth::organizationId();
            if ($orgId && !$facilityId) {
                // Limit to owner facility if needed
            }
        }

        $data = $this->repo->getPaginatedSessions($start, $length, $search, $facilityId);

        Response::json([
            'draw' => (int)$request->get('draw', 1),
            'recordsTotal' => $data['recordsTotal'],
            'recordsFiltered' => $data['recordsFiltered'],
            'data' => $data['data']
        ]);
    }

    public function getSessionDetails(Request $request): void {
        Auth::requirePermission('open_play.view');
        $id = (int)$request->get('id');
        if ($id <= 0) {
            Response::error('Invalid session ID.');
        }

        $session = $this->repo->getSessionById($id);
        if (!$session) {
            Response::error('Session not found.');
        }

        Response::success('Open Play session details loaded', $session);
    }

    public function createSession(Request $request): void {
        Auth::requirePermission('open_play.create');

        $data = $request->all();
        if (empty($data['facility_id']) || empty($data['court_id']) || empty($data['title']) || empty($data['session_date']) || empty($data['start_time']) || empty($data['end_time'])) {
            Response::error('Facility, court, title, session date, and start/end operating times are required.');
        }

        try {
            $this->validateFacilityAccess((int)$data['facility_id']);
            $id = $this->repo->createSession($data);
            Response::success('Open Play session created successfully.', ['id' => $id]);
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }

    public function updateSession(Request $request): void {
        Auth::requirePermission('open_play.edit');

        $id = (int)$request->get('id');
        $data = $request->all();
        if ($id <= 0 || empty($data['facility_id']) || empty($data['court_id']) || empty($data['title']) || empty($data['session_date'])) {
            Response::error('Session ID, facility, court, title, and session date are required.');
        }

        try {
            $existing = $this->repo->getSessionById($id);
            if (!$existing) throw new Exception('Session not found.');
            $this->validateFacilityAccess((int)$existing['facility_id']);
            $this->validateFacilityAccess((int)$data['facility_id']);
            $this->repo->updateSession($id, $data);
            Response::success('Open Play session updated successfully.');
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }

    private function validateFacilityAccess(int $facilityId): void {
        $facility = Connection::getInstance()->selectOne('SELECT organization_id FROM facilities WHERE id = ?', [$facilityId], 'i');
        if (!$facility || (!in_array(Auth::role(), ['super_admin', 'platform_admin'], true)
            && (!Auth::organizationId() || (int)$facility['organization_id'] !== (int)Auth::organizationId()))) {
            throw new Exception('You do not have access to the selected facility.');
        }
    }

    public function registerPlayer(Request $request): void {
        Auth::requirePermission('open_play.manage_players');

        $data = $request->all();
        if (empty($data['session_id']) || empty($data['player_name'])) {
            Response::error('Session ID and player name are required.');
        }

        try {
            $regId = $this->repo->registerPlayer($data);
            Response::success('Player registered for Open Play successfully.', ['registration_id' => $regId]);
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }

    public function checkinPlayer(Request $request): void {
        Auth::requirePermission('open_play.checkin');

        $registrationId = (int)$request->get('registration_id');
        if ($registrationId <= 0) {
            Response::error('Invalid registration ID.');
        }

        try {
            $this->repo->checkinPlayer($registrationId);
            Response::success('Player checked in successfully.');
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }

    public function getMetrics(Request $request): void {
        Auth::requirePermission('open_play.revenue');
        $facilityId = $request->get('facility_id') ? (int)$request->get('facility_id') : null;
        $metrics = $this->repo->getMetrics($facilityId);
        Response::success('Open Play metrics loaded', $metrics);
    }

    public function getRosterDataTables(Request $request): void {
        Auth::requirePermission('open_play.view');

        $sessionId = (int)$request->get('session_id');
        $start = (int)$request->get('start', 0);
        $length = (int)$request->get('length', 10);
        $search = (string)($request->get('search')['value'] ?? $request->get('search', ''));

        if ($sessionId <= 0) {
            Response::json(['draw' => (int)$request->get('draw', 1), 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => []]);
            return;
        }

        $data = $this->repo->getPaginatedRoster($sessionId, $start, $length, $search);

        Response::json([
            'draw' => (int)$request->get('draw', 1),
            'recordsTotal' => $data['recordsTotal'],
            'recordsFiltered' => $data['recordsFiltered'],
            'data' => $data['data']
        ]);
    }

    public function getPlayerSuggestions(Request $request): void {
        Auth::requirePermission('open_play.view');
        $sessionId = $request->get('session_id') ? (int)$request->get('session_id') : null;
        $suggestions = $this->repo->getPlayerSuggestions($sessionId);
        Response::success('Player suggestions loaded', $suggestions);
    }
}
