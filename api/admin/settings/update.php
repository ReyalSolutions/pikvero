<?php
require_once __DIR__ . '/../../../app/bootstrap.php';
use App\Presentation\Controllers\AdminController;
use App\Core\Http\Request;

$controller = new AdminController();
$controller->updateSettings(new Request());
