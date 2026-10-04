<?php
require_once __DIR__ . '/../../../app/bootstrap.php';
use App\Presentation\Controllers\SubscriptionController;
use App\Core\Http\Request;

$controller = new SubscriptionController();
$controller->bypassUpgradeSubscription(new Request());
