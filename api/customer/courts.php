<?php
require_once __DIR__ . '/../../app/bootstrap.php';
use App\Presentation\Controllers\CustomerController;
use App\Core\Http\Request;

$controller = new CustomerController();
$controller->searchCourts(new Request());
