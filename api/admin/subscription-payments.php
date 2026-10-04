<?php
require_once __DIR__ . '/../../app/bootstrap.php';
use App\Presentation\Controllers\SubscriptionPaymentController;
use App\Core\Http\Request;

$controller = new SubscriptionPaymentController();
$controller->getSubscriptionPayments(new Request());
