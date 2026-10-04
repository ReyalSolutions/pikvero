<?php
require_once __DIR__ . '/../../app/bootstrap.php';
use App\Presentation\Controllers\CustomerController;
use App\Core\Http\Request;

$request = new Request();
$controller = new CustomerController();

if ($request->get('action') === 'cities') {
    $controller->getCities();
} elseif ($request->get('id')) {
    $controller->getFacilityDetail($request);
} else {
    $controller->getFacilities();
}
