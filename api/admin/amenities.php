<?php
require_once __DIR__ . '/../../app/bootstrap.php';
use App\Presentation\Controllers\AmenityController;
use App\Core\Http\Request;

$request = new Request();
$controller = new AmenityController();
$action = $request->get('action');

if ($request->getMethod() === 'POST') {
    if ($action === 'create') {
        $controller->create($request);
    } elseif ($action === 'update') {
        $controller->update($request);
    } elseif ($action === 'delete') {
        $controller->delete($request);
    } elseif ($action === 'save_facility_assignments') {
        $controller->saveFacilityAssignments($request);
    } else {
        $controller->create($request);
    }
} else {
    if ($action === 'all') {
        $controller->getAll($request);
    } elseif ($action === 'get_facility_assignments') {
        $controller->getFacilityAssignments($request);
    } else {
        $controller->getPaginatedDataTables($request);
    }
}
