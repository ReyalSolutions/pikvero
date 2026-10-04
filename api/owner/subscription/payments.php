<?php
require_once __DIR__ . '/../../../app/bootstrap.php';
use App\Presentation\Controllers\SubscriptionController;

$controller = new SubscriptionController();
$controller->getMyPaymentHistory();
