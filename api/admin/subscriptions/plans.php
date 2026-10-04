<?php
require_once __DIR__ . '/../../../app/bootstrap.php';
use App\Presentation\Controllers\SubscriptionController;
use App\Core\Http\Request;

$controller = new SubscriptionController();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller->createPlan(new Request());
} else {
    $controller->getPlans();
}
