<?php
require_once __DIR__ . '/../../app/bootstrap.php';
use App\Presentation\Controllers\OpenPlayController;
use App\Core\Http\Request;

$request = new Request();
$controller = new OpenPlayController();
$action = $request->get('action');

if ($request->getMethod() === 'POST') {
    if ($action === 'create') {
        $controller->createSession($request);
    } elseif ($action === 'update') {
        $controller->updateSession($request);
    } elseif ($action === 'register_player') {
        $controller->registerPlayer($request);
    } elseif ($action === 'checkin') {
        $controller->checkinPlayer($request);
    } else {
        $controller->createSession($request);
    }
} else {
    if ($action === 'details') {
        $controller->getSessionDetails($request);
    } elseif ($action === 'metrics') {
        $controller->getMetrics($request);
    } elseif ($action === 'roster_dt') {
        $controller->getRosterDataTables($request);
    } elseif ($action === 'player_suggestions') {
        $controller->getPlayerSuggestions($request);
    } else {
        $controller->getPaginatedDataTables($request);
    }
}
