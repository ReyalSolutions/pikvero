<?php
require_once __DIR__ . '/../../app/bootstrap.php';

use App\Presentation\Controllers\FeedbackReportController;
use App\Core\Http\Request;

$request = new Request();
$controller = new FeedbackReportController();

if ($request->getMethod() === 'POST') {
    $controller->updateReportStatus($request);
} else {
    $controller->listAdminReports($request);
}
