<?php
require_once __DIR__ . '/../../../app/bootstrap.php';
use App\Presentation\Controllers\PayoutController;
use App\Core\Http\Request;

$controller = new PayoutController();
$controller->requestPayout(new Request());
