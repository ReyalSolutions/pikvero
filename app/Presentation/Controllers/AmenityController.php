<?php
namespace App\Presentation\Controllers;

use App\Infrastructure\Repositories\AmenityRepository;
use App\Infrastructure\Repositories\FacilityRepository;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Auth\Auth;
use Exception;

class AmenityController {
    private AmenityRepository $amenityRepo;
    private FacilityRepository $facilityRepo;

    public function __construct() {
        $this->amenityRepo = new AmenityRepository();
        $this->facilityRepo = new FacilityRepository();
    }

    public function getPaginatedDataTables(Request $request): void {
        Auth::requirePermission('amenities.view');

        $start = (int)$request->get('start', 0);
        $length = (int)$request->get('length', 10);
        $search = (string)($request->get('search')['value'] ?? $request->get('search', ''));
        $orderColIndex = (string)($request->get('order')[0]['column'] ?? '0');
        $orderDir = (string)($request->get('order')[0]['dir'] ?? 'ASC');

        $data = $this->amenityRepo->getPaginatedDataTables($start, $length, $search, $orderColIndex, $orderDir);

        Response::json([
            'draw' => (int)$request->get('draw', 1),
            'recordsTotal' => $data['recordsTotal'],
            'recordsFiltered' => $data['recordsFiltered'],
            'data' => $data['data']
        ]);
    }

    public function getAll(Request $request): void {
        Auth::requirePermission('amenities.view');
        $amenities = $this->amenityRepo->getAllWithCounts();
        Response::success('Amenities loaded', $amenities);
    }

    public function getFacilityAssignments(Request $request): void {
        Auth::requirePermission('amenities.view');
        $facilityId = (int)$request->get('facility_id');
        if ($facilityId <= 0) {
            Response::error('Facility ID is required.');
        }

        $assignedIds = $this->amenityRepo->getFacilityAssignments($facilityId);
        $allAmenities = $this->amenityRepo->getAllWithCounts();

        Response::success('Facility assignments loaded', [
            'facility_id' => $facilityId,
            'assigned_ids' => $assignedIds,
            'amenities' => $allAmenities
        ]);
    }

    public function create(Request $request): void {
        Auth::requirePermission('amenities.manage');

        $name = trim((string)$request->get('name'));
        $icon = trim((string)$request->get('icon', 'bi-check-circle'));

        if (empty($name)) {
            Response::error('Amenity name is required.');
        }

        try {
            $id = $this->amenityRepo->create([
                'name' => $name,
                'icon' => $icon
            ]);
            Response::success('Amenity created successfully.', ['id' => $id]);
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }

    public function update(Request $request): void {
        Auth::requirePermission('amenities.manage');

        $id = (int)$request->get('id');
        $name = trim((string)$request->get('name'));
        $icon = trim((string)$request->get('icon', 'bi-check-circle'));

        if ($id <= 0 || empty($name)) {
            Response::error('Amenity ID and name are required.');
        }

        try {
            $this->amenityRepo->update($id, [
                'name' => $name,
                'icon' => $icon
            ]);
            Response::success('Amenity updated successfully.');
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }

    public function delete(Request $request): void {
        Auth::requirePermission('amenities.manage');

        $id = (int)$request->get('id');
        if ($id <= 0) {
            Response::error('Invalid amenity ID.');
        }

        try {
            $this->amenityRepo->delete($id);
            Response::success('Amenity deleted successfully.');
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }

    public function saveFacilityAssignments(Request $request): void {
        Auth::requirePermission('amenities.manage');

        $facilityId = (int)$request->get('facility_id');
        $amenityIds = $request->get('amenity_ids', []);
        if (!is_array($amenityIds)) {
            $amenityIds = explode(',', (string)$amenityIds);
        }

        if ($facilityId <= 0) {
            Response::error('Facility ID is required.');
        }

        try {
            $this->amenityRepo->saveFacilityAssignments($facilityId, $amenityIds);
            Response::success('Facility amenities assigned successfully.');
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }
}
