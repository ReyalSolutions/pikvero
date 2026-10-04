<?php
require_once __DIR__ . '/../../app/bootstrap.php';
use App\Presentation\Controllers\OwnerController;

$controller = new OwnerController();
$controller->getSettings();
