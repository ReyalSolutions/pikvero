<?php
require_once __DIR__ . '/../../../app/bootstrap.php';
use App\Presentation\Controllers\OwnerController;
use App\Core\Http\Request;

$controller = new OwnerController();
$controller->updateCourt(new Request());
