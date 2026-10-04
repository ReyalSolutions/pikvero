<?php
require_once __DIR__ . '/../../app/bootstrap.php';
use App\Presentation\Controllers\CalendarController;
use App\Core\Http\Request;

$request = new Request();
$controller = new CalendarController();
$controller->getEvents($request);
