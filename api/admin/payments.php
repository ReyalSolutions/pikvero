<?php
require_once __DIR__ . '/../../app/bootstrap.php';
use App\Presentation\Controllers\PaymentController;
use App\Core\Http\Request;

$controller = new PaymentController();
$controller->getPayments(new Request());
