<?php
namespace App\Presentation\Controllers;

use App\Infrastructure\Repositories\ProductRepository;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Auth\Auth;
use Exception;

class ProductController {
    private ProductRepository $repo;

    public function __construct() {
        $this->repo = new ProductRepository();
    }

    public function getPaginatedDataTables(Request $request): void {
        Auth::requirePermission('products.view');

        $start = (int)$request->get('start', 0);
        $length = (int)$request->get('length', 10);
        $search = (string)($request->get('search')['value'] ?? $request->get('search', ''));
        $facilityId = $request->get('facility_id') ? (int)$request->get('facility_id') : null;

        $data = $this->repo->getPaginatedProducts($start, $length, $search, $facilityId);

        Response::json([
            'draw' => (int)$request->get('draw', 1),
            'recordsTotal' => $data['recordsTotal'],
            'recordsFiltered' => $data['recordsFiltered'],
            'data' => $data['data']
        ]);
    }

    public function getProductDetails(Request $request): void {
        Auth::requirePermission('products.view');
        $id = (int)$request->get('id');
        if ($id <= 0) {
            Response::error('Invalid product ID.');
        }

        $product = $this->repo->getProductById($id);
        if (!$product) {
            Response::error('Product not found.');
        }

        Response::success('Product details loaded', $product);
    }

    public function createProduct(Request $request): void {
        Auth::requirePermission('products.add');

        $data = $request->all();
        if (empty($data['name']) || !isset($data['price'])) {
            Response::error('Product name and unit price are required.');
        }

        try {
            $id = $this->repo->createProduct($data);
            Response::success('Product item created successfully.', ['id' => $id]);
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }

    public function updateProduct(Request $request): void {
        Auth::requirePermission('products.edit');

        $id = (int)$request->get('id');
        $data = $request->all();
        if ($id <= 0 || empty($data['name'])) {
            Response::error('Product ID and name are required.');
        }

        try {
            $this->repo->updateProduct($id, $data);
            Response::success('Product item updated successfully.');
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }

    public function deleteProduct(Request $request): void {
        Auth::requirePermission('products.delete');

        $id = (int)$request->get('id');
        if ($id <= 0) {
            Response::error('Invalid product ID.');
        }

        try {
            $this->repo->deleteProduct($id);
            Response::success('Product item archived successfully.');
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }

    public function adjustStock(Request $request): void {
        Auth::requirePermission('products.inventory');

        $id = (int)$request->get('id');
        $newQuantity = (int)$request->get('stock_quantity', 0);
        if ($id <= 0) {
            Response::error('Invalid product ID.');
        }

        try {
            $this->repo->adjustStock($id, $newQuantity);
            Response::success('Inventory stock updated successfully.');
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }

    public function processSale(Request $request): void {
        Auth::requirePermission('products.sell');

        $data = $request->all();
        if (empty($data['product_id']) || empty($data['quantity'])) {
            Response::error('Product ID and quantity are required for sales transaction.');
        }

        try {
            $saleId = $this->repo->processSale($data);
            Response::success('Product sale transaction processed successfully.', ['sale_id' => $saleId]);
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }

    public function getMetrics(Request $request): void {
        Auth::requirePermission('products.revenue');
        $facilityId = $request->get('facility_id') ? (int)$request->get('facility_id') : null;
        $metrics = $this->repo->getMetrics($facilityId);
        Response::success('Product sales metrics loaded', $metrics);
    }

    public function getPaginatedSalesDataTables(Request $request): void {
        Auth::requirePermission('products.view');

        $start = (int)$request->get('start', 0);
        $length = (int)$request->get('length', 10);
        $search = (string)($request->get('search')['value'] ?? $request->get('search', ''));
        $facilityId = $request->get('facility_id') ? (int)$request->get('facility_id') : null;

        $data = $this->repo->getPaginatedSales($start, $length, $search, $facilityId);

        Response::json([
            'draw' => (int)$request->get('draw', 1),
            'recordsTotal' => $data['recordsTotal'],
            'recordsFiltered' => $data['recordsFiltered'],
            'data' => $data['data']
        ]);
    }
}
