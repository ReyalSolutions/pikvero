<?php
require_once __DIR__ . '/../../app/bootstrap.php';
use App\Presentation\Controllers\CustomerController;
use App\Core\Http\Request;

$request = new Request();
$controller = new CustomerController();

if ($request->getMethod() === 'POST') {
    if ($request->get('action') === 'cancel') {
        $controller->cancelBooking($request);
    } else {
        $controller->createBooking($request);
    }
} else {
    $controller->getMyBookings($request);
}
