<?php
require_once __DIR__ . '/../../app/bootstrap.php';
use App\Presentation\Controllers\AuthController;

$controller = new AuthController();
$controller->me();
