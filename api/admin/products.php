<?php
require_once __DIR__ . '/../../app/bootstrap.php';
use App\Presentation\Controllers\ProductController;
use App\Core\Http\Request;

$request = new Request();
$controller = new ProductController();
$action = $request->get('action');

if ($request->getMethod() === 'POST') {
    if ($action === 'create') {
        $controller->createProduct($request);
    } elseif ($action === 'update') {
        $controller->updateProduct($request);
    } elseif ($action === 'delete') {
        $controller->deleteProduct($request);
    } elseif ($action === 'adjust_stock') {
        $controller->adjustStock($request);
    } elseif ($action === 'sell') {
        $controller->processSale($request);
    } else {
        $controller->createProduct($request);
    }
} else {
    if ($action === 'details') {
        $controller->getProductDetails($request);
    } elseif ($action === 'metrics') {
        $controller->getMetrics($request);
    } elseif ($action === 'sales_history') {
        $controller->getPaginatedSalesDataTables($request);
    } else {
        $controller->getPaginatedDataTables($request);
    }
}
