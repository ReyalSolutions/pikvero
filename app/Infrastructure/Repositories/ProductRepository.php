<?php
namespace App\Infrastructure\Repositories;

use App\Core\Database\Connection;
use App\Core\Auth\Auth;
use Exception;

class ProductRepository {
    private Connection $db;

    public function __construct(private bool $adminScope = false) {
        $this->db = Connection::getInstance();
    }

    private function facilityScope(string $column): string {
        if (!$this->adminScope || Auth::hasRole('super_admin', 'platform_admin')) {
            return '1=1';
        }
        $userId = (int)Auth::id();
        if (Auth::hasRole('court_owner', 'owner')) {
            return "{$column} IN (SELECT f.id FROM facilities f JOIN organizations o ON o.id = f.organization_id WHERE o.owner_id = {$userId})";
        }
        // Read the actual user record; organizationId() may infer an unrelated tenant.
        return "{$column} IN (SELECT f.id FROM facilities f JOIN organization_users u ON u.organization_id = f.organization_id WHERE u.user_id = {$userId})";
    }

    private function requireProductAccess(int $id): void {
        if (!$this->getProductById($id)) {
            throw new Exception('Product not found or access denied.');
        }
    }

    private function requireFacilityAccess(array $data): void {
        if (!$this->adminScope || Auth::hasRole('super_admin', 'platform_admin')) return;
        $facilityId = (int)($data['facility_id'] ?? 0);
        $scope = $this->facilityScope('f.id');
        if ($facilityId <= 0 || !$this->db->selectOne("SELECT f.id FROM facilities f WHERE f.id = ? AND {$scope}", [$facilityId], 'i')) {
            throw new Exception('Select a facility you own or manage.');
        }
    }

    public function getPaginatedProducts(
        int $start = 0,
        int $length = 10,
        string $search = '',
        ?int $facilityId = null
    ): array {
        $scope = $this->facilityScope('p.facility_id');
        $whereClauses = ["p.status != 'archived'", $scope];
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
            ? $this->db->selectOne("SELECT COUNT(*) AS total FROM products p WHERE p.status != 'archived' AND {$scope} AND p.facility_id = ?", [$facilityId], 'i')
            : $this->db->selectOne("SELECT COUNT(*) AS total FROM products p WHERE p.status != 'archived' AND {$scope}");
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
        $scope = $this->facilityScope('p.facility_id');
        $sql = "SELECT p.*, f.name AS facility_name
                FROM products p
                LEFT JOIN facilities f ON p.facility_id = f.id
                WHERE p.id = ? AND {$scope} LIMIT 1";
        return $this->db->selectOne($sql, [$id], 'i');
    }

    public function createProduct(array $data): int {
        $this->requireFacilityAccess($data);
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
        $this->requireProductAccess($id);
        $this->requireFacilityAccess($data);
        $facilityId = !empty($data['facility_id']) ? (int)$data['facility_id'] : null;
        $current = $this->getProductById($id);
        if (($current['type'] !== ($data['type'] ?? 'sale') || (int)$current['facility_id'] !== (int)$facilityId)
            && $this->db->selectOne("SELECT id FROM product_sales WHERE product_id = ? AND rental_status = 'rented' LIMIT 1", [$id], 'i')) {
            throw new Exception('Return outstanding rentals before changing the product type or facility.');
        }
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
        $this->requireProductAccess($id);
        return $this->db->execute("UPDATE products SET status = 'archived' WHERE id = ?", [$id], 'i');
    }

    public function adjustStock(int $id, int $newQuantity): bool {
        $this->requireProductAccess($id);
        $status = $newQuantity <= 0 ? 'out_of_stock' : 'active';
        return $this->db->execute("UPDATE products SET stock_quantity = ?, status = ? WHERE id = ?", [$newQuantity, $status, $id], 'isi');
    }

    public function processSale(array $data): int {
        $productId = (int)($data['product_id'] ?? 0);
        $quantity = filter_var($data['quantity'] ?? 0, FILTER_VALIDATE_INT);
        if ($quantity === false || $quantity <= 0) throw new Exception('Enter a positive whole-number quantity.');
        $scope = $this->facilityScope('p.facility_id');
        $this->db->beginTransaction();
        try {
            $product = $this->db->selectOne("SELECT p.* FROM products p WHERE p.id = ? AND {$scope} FOR UPDATE", [$productId], 'i');
            if (!$product) throw new Exception('Product item not found or access denied.');
            if ($product['status'] === 'archived') throw new Exception('Archived products cannot be sold or rented.');
            if ((int)$product['stock_quantity'] < $quantity) throw new Exception('Insufficient available stock.');
            $unitPrice = (float)($data['unit_price'] ?? $product['price']);
            if (!is_finite($unitPrice) || $unitPrice < 0) throw new Exception('Invalid unit price.');
            $rental = $product['type'] === 'rental';
            $this->db->execute("INSERT INTO product_sales (facility_id, product_id, user_id, customer_name, quantity, unit_price, total_amount, payment_method, transaction_type, rental_status, rental_stock_deducted)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", [
                $product['facility_id'], $productId,
                !empty($data['user_id']) ? (int)$data['user_id'] : null,
                $data['customer_name'] ?? 'Walk-in Customer', $quantity, $unitPrice, $unitPrice * $quantity,
                $data['payment_method'] ?? 'cash', $product['type'], $rental ? 'rented' : null, $rental ? 1 : 0
            ], 'iiisiddsssi');
            $saleId = $this->db->getLastInsertId();
            $remaining = (int)$product['stock_quantity'] - $quantity;
            $this->db->execute("UPDATE products SET stock_quantity = ?, status = ? WHERE id = ?", [$remaining, $remaining > 0 ? 'active' : 'out_of_stock', $productId], 'isi');
            $this->db->commit();
            return $saleId;
        } catch (\Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    public function returnRental(int $saleId): array {
        if (!Auth::check() || !Auth::hasRole('court_owner', 'owner', 'super_admin', 'platform_admin')) throw new Exception('Only the facility owner may mark rentals as returned.');
        $platform = Auth::hasRole('super_admin', 'platform_admin');
        $saleScope = $platform ? '1=1' : \App\Core\Auth\TenantScope::facilitySql('s.facility_id');
        $productScope = $platform ? '1=1' : \App\Core\Auth\TenantScope::facilitySql('p.facility_id');
        $this->db->beginTransaction();
        try {
            $sale = $this->db->selectOne("SELECT s.id, s.product_id FROM product_sales s JOIN products p ON p.id = s.product_id WHERE s.id = ? AND {$saleScope} AND {$productScope}", [$saleId], 'i');
            if (!$sale) throw new Exception('Rental not found or access denied.');
            // Lock inventory before its transaction so issuing and returning use a consistent lock order.
            $product = $this->db->selectOne("SELECT p.id, p.stock_quantity FROM products p WHERE p.id = ? AND {$productScope} FOR UPDATE", [(int)$sale['product_id']], 'i');
            $sale = $this->db->selectOne("SELECT s.* FROM product_sales s WHERE s.id = ? AND {$saleScope} FOR UPDATE", [$saleId], 'i');
            if (!$product || !$sale) throw new Exception('Rental not found or access denied.');
            if ($sale['transaction_type'] !== 'rental') throw new Exception('This transaction is not a rental.');
            if ($sale['rental_status'] === 'returned') {
                $this->db->commit();
                return ['already_returned' => true, 'quantity_restored' => 0, 'stock_quantity' => (int)$product['stock_quantity']];
            }
            if ($sale['rental_status'] !== 'rented' || (int)$sale['quantity'] <= 0) throw new Exception('This rental cannot be returned.');
            $restored = (int)$sale['rental_stock_deducted'] === 1 ? (int)$sale['quantity'] : 0;
            if ($restored > 0) {
                $this->db->execute("UPDATE products SET stock_quantity = stock_quantity + ?, status = CASE WHEN status = 'archived' THEN status ELSE 'active' END WHERE id = ?", [$restored, (int)$sale['product_id']], 'ii');
            }
            $this->db->execute("UPDATE product_sales SET rental_status = 'returned', returned_at = NOW(), returned_by_user_id = ? WHERE id = ?", [(int)Auth::id(), $saleId], 'ii');
            $this->db->commit();
            return ['already_returned' => false, 'quantity_restored' => $restored, 'stock_quantity' => (int)$product['stock_quantity'] + $restored];
        } catch (\Throwable $e) {
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
        $scope = $this->facilityScope('s.facility_id');
        $whereClauses = [$scope, $this->facilityScope('p.facility_id')];
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

        $countWhere = $scope . ' AND ' . $this->facilityScope('p.facility_id');
        if ($facilityId !== null && $facilityId > 0) $countWhere .= ' AND s.facility_id = ' . (int)$facilityId;
        $totalCountRow = $this->db->selectOne("SELECT COUNT(*) AS total FROM product_sales s JOIN products p ON p.id = s.product_id WHERE {$countWhere}");
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

        $sql = "SELECT s.*, p.name AS product_name, p.category, s.transaction_type AS type, f.name AS facility_name
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
        $facilityWhere = 'AND ' . $this->facilityScope('facility_id');
        if ($facilityId && $facilityId > 0) $facilityWhere .= " AND facility_id = {$facilityId}";
        $salesWhere = $this->facilityScope('s.facility_id');
        if ($facilityId && $facilityId > 0) $salesWhere .= " AND s.facility_id = {$facilityId}";

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
            WHERE {$salesWhere}"
        );

        return [
            'total_products' => (int)($pRow['total_products'] ?? 0),
            'low_stock_count' => (int)($pRow['low_stock_count'] ?? 0),
            'total_sold_items' => (int)($sRow['total_sold_items'] ?? 0),
            'total_sales_revenue' => (float)($sRow['total_sales_revenue'] ?? 0.00)
        ];
    }
}
