<?php
require_once __DIR__ . '/../../app/bootstrap.php';
use App\Presentation\Controllers\AuthController;
use App\Core\Http\Request;

$controller = new AuthController();
$controller->register(new Request());
