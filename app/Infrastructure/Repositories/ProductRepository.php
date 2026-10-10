<?php
namespace App\Infrastructure\Repositories;

use App\Core\Database\Connection;
use Exception;

class ProductRepository {
    private Connection $db;

    public function __construct() {
        $this->db = Connection::getInstance();
    }

    public function getPaginatedProducts(
        int $start = 0,
        int $length = 10,
        string $search = '',
        ?int $facilityId = null
    ): array {
        $whereClauses = ["p.status != 'archived'"];
        $params = [];
        $types = "";

        if ($facilityId !== null && $facilityId > 0) {
            $whereClauses[] = "p.facility_id = ?";
            $params[] = $facilityId;
            $types .= "i";
        }

        if (!empty($search)) {
            $whereClauses[] = "(p.name LIKE ? OR p.category LIKE ?)";
            $searchTerm = "%{$search}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $types .= "ss";
        }

        $whereSql = implode(" AND ", $whereClauses);

        $totalCountRow = $facilityId !== null && $facilityId > 0
            ? $this->db->selectOne("SELECT COUNT(*) AS total FROM products p WHERE p.status != 'archived' AND p.facility_id = ?", [$facilityId], 'i')
            : $this->db->selectOne("SELECT COUNT(*) AS total FROM products p WHERE p.status != 'archived'");
        $recordsTotal = (int)($totalCountRow['total'] ?? 0);

        $filteredCountRow = $this->db->selectOne("
            SELECT COUNT(*) AS total 
            FROM products p
            WHERE {$whereSql}
        ", $params, $types);
        $recordsFiltered = (int)($filteredCountRow['total'] ?? 0);

        $start = max(0, $start);
        $length = max(1, min(100, $length));

        $sql = "SELECT p.*, f.name AS facility_name,
                       (SELECT COALESCE(SUM(s.quantity), 0) FROM product_sales s WHERE s.product_id = p.id) AS total_sold,
                       (SELECT COALESCE(SUM(s.total_amount), 0) FROM product_sales s WHERE s.product_id = p.id) AS total_revenue
                FROM products p
                LEFT JOIN facilities f ON p.facility_id = f.id
                WHERE {$whereSql}
                ORDER BY p.id DESC
                LIMIT {$start}, {$length}";

        $rows = $this->db->select($sql, $params, $types);

        return [
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $rows
        ];
    }

    public function getProductById(int $id): ?array {
        $sql = "SELECT p.*, f.name AS facility_name
                FROM products p
                LEFT JOIN facilities f ON p.facility_id = f.id
                WHERE p.id = ? LIMIT 1";
        return $this->db->selectOne($sql, [$id], 'i');
    }

    public function createProduct(array $data): int {
        $sql = "INSERT INTO products (facility_id, name, category, type, price, stock_quantity, description, image_url, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $this->db->execute($sql, [
            !empty($data['facility_id']) ? (int)$data['facility_id'] : null,
            $data['name'],
            $data['category'] ?? 'Equipment',
            $data['type'] ?? 'sale',
            (float)($data['price'] ?? 0.00),
            (int)($data['stock_quantity'] ?? 0),
            $data['description'] ?? null,
            $data['image_url'] ?? null,
            $data['status'] ?? 'active'
        ], 'isssdisss');
        return $this->db->getLastInsertId();
    }

    public function updateProduct(int $id, array $data): bool {
        $facilityId = !empty($data['facility_id']) ? (int)$data['facility_id'] : null;
        $sql = "UPDATE products SET 
                facility_id = ?,
                name = ?, 
                category = ?, 
                type = ?, 
                price = ?, 
                stock_quantity = ?, 
                description = ?, 
                image_url = ?, 
                status = ? 
                WHERE id = ?";
        return $this->db->execute($sql, [
            $facilityId,
            $data['name'],
            $data['category'] ?? 'Equipment',
            $data['type'] ?? 'sale',
            (float)($data['price'] ?? 0.00),
            (int)($data['stock_quantity'] ?? 0),
            $data['description'] ?? null,
            $data['image_url'] ?? null,
            $data['status'] ?? 'active',
            $id
        ], 'isssdisssi');
    }

    public function deleteProduct(int $id): bool {
        return $this->db->execute("UPDATE products SET status = 'archived' WHERE id = ?", [$id], 'i');
    }

    public function adjustStock(int $id, int $newQuantity): bool {
        $status = $newQuantity <= 0 ? 'out_of_stock' : 'active';
        return $this->db->execute("UPDATE products SET stock_quantity = ?, status = ? WHERE id = ?", [$newQuantity, $status, $id], 'isi');
    }

    public function processSale(array $data): int {
        $productId = (int)$data['product_id'];
        $product = $this->getProductById($productId);
        if (!$product) {
            throw new Exception("Product item not found.");
        }

        $quantity = (int)($data['quantity'] ?? 1);
        if ($quantity <= 0) {
            throw new Exception("Invalid sale quantity.");
        }

        if ($product['stock_quantity'] < $quantity && $product['type'] === 'sale') {
            throw new Exception("Insufficient stock available for {$product['name']}. Available: {$product['stock_quantity']}");
        }

        $unitPrice = (float)($data['unit_price'] ?? $product['price']);
        $totalAmount = $unitPrice * $quantity;

        $this->db->beginTransaction();
        try {
            // Record sale transaction
            $sql = "INSERT INTO product_sales (facility_id, product_id, user_id, customer_name, quantity, unit_price, total_amount, payment_method)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $this->db->execute($sql, [
                $product['facility_id'],
                $productId,
                !empty($data['user_id']) ? (int)$data['user_id'] : null,
                $data['customer_name'] ?? 'Walk-in Customer',
                $quantity,
                $unitPrice,
                $totalAmount,
                $data['payment_method'] ?? 'cash'
            ], 'iiisidds');
            $saleId = $this->db->getLastInsertId();

            // Deduct stock if physical product for sale
            if ($product['type'] === 'sale') {
                $newStock = max(0, $product['stock_quantity'] - $quantity);
                $newStatus = $newStock <= 0 ? 'out_of_stock' : 'active';
                $this->db->execute("UPDATE products SET stock_quantity = ?, status = ? WHERE id = ?", [$newStock, $newStatus, $productId], 'isi');
            }

            $this->db->commit();
            return $saleId;
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    public function getPaginatedSales(
        int $start = 0,
        int $length = 10,
        string $search = '',
        ?int $facilityId = null
    ): array {
        $whereClauses = ["1=1"];
        $params = [];
        $types = "";

        if ($facilityId !== null && $facilityId > 0) {
            $whereClauses[] = "s.facility_id = ?";
            $params[] = $facilityId;
            $types .= "i";
        }

        if (!empty($search)) {
            $whereClauses[] = "(s.customer_name LIKE ? OR p.name LIKE ? OR s.payment_method LIKE ?)";
            $searchTerm = "%{$search}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $types .= "sss";
        }

        $whereSql = implode(" AND ", $whereClauses);

        $totalCountRow = $this->db->selectOne("SELECT COUNT(*) AS total FROM product_sales s");
        $recordsTotal = (int)($totalCountRow['total'] ?? 0);

        $filteredCountRow = $this->db->selectOne("
            SELECT COUNT(*) AS total 
            FROM product_sales s
            JOIN products p ON s.product_id = p.id
            WHERE {$whereSql}
        ", $params, $types);
        $recordsFiltered = (int)($filteredCountRow['total'] ?? 0);

        $start = max(0, $start);
        $length = max(1, min(100, $length));

        $sql = "SELECT s.*, p.name AS product_name, p.category, p.type, f.name AS facility_name
                FROM product_sales s
                JOIN products p ON s.product_id = p.id
                LEFT JOIN facilities f ON s.facility_id = f.id
                WHERE {$whereSql}
                ORDER BY s.sale_date DESC, s.id DESC
                LIMIT {$start}, {$length}";

        $rows = $this->db->select($sql, $params, $types);

        return [
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $rows
        ];
    }

    public function getMetrics(?int $facilityId = null): array {
        $facilityWhere = ($facilityId && $facilityId > 0) ? "AND (facility_id = {$facilityId} OR facility_id IS NULL)" : "";

        $pRow = $this->db->selectOne("
            SELECT COUNT(*) AS total_products,
                   SUM(CASE WHEN stock_quantity <= 3 AND status != 'archived' THEN 1 ELSE 0 END) AS low_stock_count
            FROM products
            WHERE status != 'archived' {$facilityWhere}
        ");

        $sRow = $this->db->selectOne("
            SELECT COALESCE(SUM(s.quantity), 0) AS total_sold_items,
                   COALESCE(SUM(s.total_amount), 0) AS total_sales_revenue
            FROM product_sales s
            " . ($facilityId && $facilityId > 0 ? "WHERE s.facility_id = {$facilityId}" : "")
        );

        return [
            'total_products' => (int)($pRow['total_products'] ?? 0),
            'low_stock_count' => (int)($pRow['low_stock_count'] ?? 0),
            'total_sold_items' => (int)($sRow['total_sold_items'] ?? 0),
            'total_sales_revenue' => (float)($sRow['total_sales_revenue'] ?? 0.00)
        ];
    }
}
