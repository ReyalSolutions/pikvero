<?php
require_once __DIR__ . '/../../app/bootstrap.php';
use App\Presentation\Controllers\AdminController;

$controller = new AdminController();
$controller->getRoles();
