<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
ini_set('session.save_path', sys_get_temp_dir());
require_once __DIR__ . '/../app/bootstrap.php';

use App\Core\Auth\Session;
use App\Core\Database\Connection;
use App\Infrastructure\Repositories\ProductRepository;

function checkRental(bool $ok, string $message): void { if (!$ok) throw new RuntimeException($message); }
function deniedRental(callable $action): void {
    $denied = false;
    try { $action(); } catch (Exception $e) { $denied = true; }
    checkRental($denied, 'An invalid rental operation succeeded.');
}
if (($argv[1] ?? '') === 'api-return') {
    $userId = (int)$argv[2];
    $user = Connection::getInstance()->selectOne('SELECT u.*, r.name AS role_name FROM users u JOIN roles r ON r.id = u.role_id WHERE u.id = ?', [$userId], 'i');
    Session::set('user_id', $userId); Session::set('user_role', $user['role_name']); Session::set('user_data', $user);
    $_GET = ['action' => 'return_rental']; $_POST = ['sale_id' => (int)$argv[3]];
    $_SERVER['REQUEST_METHOD'] = 'POST'; $_SERVER['HTTP_ACCEPT'] = 'application/json';
    ob_start();
    register_shutdown_function(function () {
        $body = ob_get_clean(); $status = http_response_code() ?: 200;
        Session::destroy(); echo json_encode(['status' => $status, 'body' => json_decode($body, true)]);
    });
    require __DIR__ . '/../api/admin/products.php';
    exit;
}
function apiReturnRental(int $userId, int $saleId): array {
    $process = proc_open([PHP_BINARY, __FILE__, 'api-return', (string)$userId, (string)$saleId], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    $output = stream_get_contents($pipes[1]); $error = stream_get_contents($pipes[2]);
    fclose($pipes[1]); fclose($pipes[2]); $exit = proc_close($process);
    $response = json_decode($output, true);
    checkRental($exit === 0 && is_array($response), 'Rental API failed: ' . $error);
    return $response;
}
$db = Connection::getInstance();
$productIds = [];
try {
    $owners = $db->select("SELECT f.id AS facility_id, o.owner_id FROM facilities f JOIN organizations o ON o.id = f.organization_id JOIN users u ON u.id = o.owner_id JOIN roles r ON r.id = u.role_id WHERE r.name = 'court_owner' ORDER BY o.owner_id, f.id");
    checkRental(count($owners) > 1, 'Need two owners in the development database.');
    $owner = $owners[0];
    $other = null;
    foreach ($owners as $candidate) if ($candidate['owner_id'] !== $owner['owner_id']) { $other = $candidate; break; }
    checkRental((bool)$other, 'Need two different owners.');
    Session::set('user_id', (int)$owner['owner_id']); Session::set('user_role', 'court_owner');
    $repo = new ProductRepository(true);
    $productId = $repo->createProduct(['facility_id' => $owner['facility_id'], 'name' => 'Regression rental ' . bin2hex(random_bytes(5)), 'type' => 'rental', 'price' => 50, 'stock_quantity' => 3]);
    $productIds[] = $productId;
    $saleId = $repo->processSale(['product_id' => $productId, 'quantity' => 2]);
    checkRental((int)$repo->getProductById($productId)['stock_quantity'] === 1, 'Issuing a rental did not deduct stock.');
    $sale = $db->selectOne('SELECT * FROM product_sales WHERE id = ?', [$saleId], 'i');
    checkRental($sale['transaction_type'] === 'rental' && $sale['rental_status'] === 'rented' && (int)$sale['rental_stock_deducted'] === 1, 'Rental status was not recorded.');
    deniedRental(fn() => $repo->processSale(['product_id' => $productId, 'quantity' => 2]));
    deniedRental(fn() => $repo->processSale(['product_id' => $productId, 'quantity' => '1.5']));
    deniedRental(fn() => $repo->updateProduct($productId, ['facility_id' => $owner['facility_id'], 'name' => 'Invalid type change', 'type' => 'sale']));

    Session::set('user_id', (int)$other['owner_id']);
    deniedRental(fn() => $repo->returnRental($saleId));
    checkRental(apiReturnRental((int)$other['owner_id'], $saleId)['body']['success'] === false, 'API allowed a return from another owner.');
    checkRental((int)$db->selectOne('SELECT stock_quantity FROM products WHERE id = ?', [$productId], 'i')['stock_quantity'] === 1, 'Another owner changed inventory.');
    Session::set('user_id', (int)$owner['owner_id']); Session::set('user_role', 'receptionist');
    deniedRental(fn() => $repo->returnRental($saleId));
    Session::set('user_role', 'court_owner');
    $apiReturn = apiReturnRental((int)$owner['owner_id'], $saleId);
    checkRental($apiReturn['status'] === 200 && $apiReturn['body']['success'], 'Owner return API failed.');
    $returned = $apiReturn['body']['data'];
    checkRental($returned['quantity_restored'] === 2 && $returned['stock_quantity'] === 3, 'Return restored the wrong quantity.');
    $returnedSale = $db->selectOne('SELECT * FROM product_sales WHERE id = ?', [$saleId], 'i');
    checkRental($returnedSale['rental_status'] === 'returned' && $returnedSale['returned_at'] && (int)$returnedSale['returned_by_user_id'] === (int)$owner['owner_id'], 'Return audit fields missing.');
    $repeat = $repo->returnRental($saleId);
    checkRental($repeat['already_returned'] && $repeat['quantity_restored'] === 0 && $repeat['stock_quantity'] === 3, 'Repeated return inflated stock.');

    $all = $repo->processSale(['product_id' => $productId, 'quantity' => 3]);
    checkRental($repo->getProductById($productId)['status'] === 'out_of_stock', 'Last rented unit did not mark stock unavailable.');
    $repo->returnRental($all);
    checkRental($repo->getProductById($productId)['status'] === 'active', 'Returning units did not make stock available.');
    $archived = $repo->processSale(['product_id' => $productId, 'quantity' => 1]);
    $repo->deleteProduct($productId);
    $repo->returnRental($archived);
    checkRental($repo->getProductById($productId)['status'] === 'archived', 'Return unexpectedly reactivated an archived product.');

    $db->execute("INSERT INTO product_sales (facility_id,product_id,quantity,unit_price,total_amount,transaction_type,rental_status,rental_stock_deducted) VALUES (?,?,1,50,50,'rental','rented',0)", [(int)$owner['facility_id'], $productId], 'ii');
    $legacyId = $db->getLastInsertId();
    checkRental($repo->returnRental($legacyId)['quantity_restored'] === 0, 'Legacy rental inflated stock that was never deducted.');
    $history = $repo->getPaginatedSales(0, 100, '', (int)$owner['facility_id']);
    checkRental(count(array_filter($history['data'], fn($row) => (int)$row['id'] === $saleId && $row['rental_status'] === 'returned')) === 1, 'Returned rental absent from history.');

    $soldProduct = $repo->createProduct(['facility_id' => $owner['facility_id'], 'name' => 'Regression sale ' . bin2hex(random_bytes(5)), 'type' => 'sale', 'price' => 100, 'stock_quantity' => 1]);
    $productIds[] = $soldProduct;
    $sold = $repo->processSale(['product_id' => $soldProduct, 'quantity' => 1]);
    deniedRental(fn() => $repo->returnRental($sold));
    checkRental((int)$repo->getProductById($soldProduct)['stock_quantity'] === 0, 'Sale was wrongly restocked as a rental.');
    Session::destroy();
    echo "PASS: issue/return stock, duplicate returns, insufficient stock, cross-owner denial, owner-only returns, legacy rentals, archived items and sales.\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'FAIL: ' . $e->getMessage() . "\n"); $failed = true;
} finally {
    foreach ($productIds as $productId) {
        $db->execute('DELETE FROM product_sales WHERE product_id = ?', [$productId], 'i');
        $db->execute('DELETE FROM products WHERE id = ?', [$productId], 'i');
    }
}
if (!empty($failed)) exit(1);
