<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
// Run against the local development database: php tests/admin-tenant-isolation.php
// Only reads business records; denied requests may create normal security log entries.
ini_set('session.save_path', sys_get_temp_dir());
require_once __DIR__ . '/../app/bootstrap.php';

use App\Core\Auth\Auth;
use App\Core\Auth\Session;
use App\Core\Database\Connection;
use App\Infrastructure\Repositories\OpenPlayRepository;
use App\Infrastructure\Repositories\UserRepository;
use App\Infrastructure\Repositories\SubscriptionPlanRepository;

function check(bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
}

if (($argv[1] ?? '') === 'request') {
    $input = json_decode(base64_decode($argv[2]), true);
    $user = Connection::getInstance()->selectOne('SELECT u.*, r.name AS role_name FROM users u JOIN roles r ON r.id = u.role_id WHERE u.id = ?', [$input['user']], 'i');
    Session::set('user_id', (int)$input['user']);
    Session::set('user_role', $user['role_name'] ?? 'court_owner');
    Session::set('user_data', $user ?? ['id' => $input['user'], 'role_name' => 'court_owner']);
    Session::set('organization_id', $input['org'] ?? 2147483647);
    $_GET = $input['query'] ?? [];
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['HTTP_ACCEPT'] = 'application/json';
    $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
    ob_start();
    register_shutdown_function(function () {
        $body = ob_get_clean();
        $status = http_response_code() ?: 200;
        Session::destroy();
        echo json_encode(['status' => $status, 'body' => json_decode($body, true)]);
    });
    $paths = [
        'calendar' => 'api/admin/calendar.php',
        'open-play' => 'api/admin/open-play.php',
        'amenities' => 'api/admin/amenities.php',
        'users' => 'api/admin/users.php',
        'user-detail' => 'api/admin/users/detail.php',
        'booking-detail' => 'api/owner/bookings/detail.php',
        'court-detail' => 'api/owner/courts/detail.php',
        'payments' => 'api/admin/payments.php',
        'subscription-payments' => 'api/admin/subscription-payments.php',
        'logs' => 'api/admin/logs.php',
        'settings' => 'api/admin/settings.php',
        'receipt' => 'public/product-receipt.php',
        'booking-print' => 'public/admin/bookings/print.php',
        'subscriptions' => 'api/admin/subscriptions/active.php',
    ];
    require __DIR__ . '/../' . $paths[$input['endpoint']];
    exit;
}

function request(int $user, string $endpoint, array $query = []): array {
    $payload = base64_encode(json_encode(['user' => $user, 'endpoint' => $endpoint, 'query' => $query]));
    $process = proc_open([PHP_BINARY, __FILE__, 'request', $payload], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    $output = stream_get_contents($pipes[1]);
    $error = stream_get_contents($pipes[2]);
    fclose($pipes[1]); fclose($pipes[2]);
    $exit = proc_close($process);
    $result = json_decode($output, true);
    check($exit === 0 && is_array($result) && is_array($result['body']), "Invalid response from {$endpoint}: {$error}");
    return $result;
}

try {
    $db = Connection::getInstance();
    $owners = $db->select("SELECT DISTINCT o.owner_id FROM organizations o JOIN users u ON u.id = o.owner_id JOIN roles r ON r.id = u.role_id WHERE r.name = 'court_owner'");
    check(count($owners) >= 2, 'This regression check needs two development owners.');
    $requests = 0;
    foreach ($owners as $owner) {
        $uid = (int)$owner['owner_id'];
        Session::set('user_id', $uid); Session::set('user_role', 'court_owner');
        Session::set('organization_id', 2147483647);
        $orgId = Auth::organizationId();
        check((bool)$db->selectOne('SELECT id FROM organizations WHERE id = ? AND owner_id = ?', [$orgId, $uid], 'ii'), 'Organization fallback selected another owner.');
        $summary = (new SubscriptionPlanRepository())->getTenantSubscriptionSummary($orgId);
        check(!$summary['subscription'] || (int)$summary['subscription']['organization_id'] === $orgId, 'Subscription summary borrowed another tenant.');
        $subscriptions = request($uid, 'subscriptions', ['draw' => 1]);
        check($subscriptions['status'] === 200, 'Own subscription list unavailable.');
        foreach ($subscriptions['body']['data'] as $subscription) {
            check((bool)$db->selectOne('SELECT id FROM organizations WHERE id = ? AND owner_id = ?', [(int)$subscription['organization_id'], $uid], 'ii'), 'Foreign active subscription exposed.');
        }
        $requests++;
        $foreign = $db->selectOne('SELECT f.id, f.organization_id FROM facilities f JOIN organizations o ON o.id = f.organization_id WHERE o.owner_id != ? LIMIT 1', [$uid], 'i');
        if (!$foreign) continue;
        $foreignFacility = (int)$foreign['id'];
        $calendar = request($uid, 'calendar', ['facility_id' => $foreignFacility, 'start' => '2020-01-01', 'end' => '2040-12-31']);
        check($calendar['status'] === 200 && $calendar['body'] === [], 'Foreign calendar data exposed.');
        $sessions = request($uid, 'open-play', ['facility_id' => $foreignFacility]);
        check($sessions['status'] === 200 && $sessions['body']['recordsTotal'] === 0 && $sessions['body']['data'] === [], 'Foreign Open Play data exposed.');
        $amenities = request($uid, 'amenities', ['action' => 'get_facility_assignments', 'facility_id' => $foreignFacility]);
        check($amenities['status'] === 403, 'Foreign facility assignments exposed.');
        $requests += 3;
        foreach (['logs', 'settings'] as $endpoint) {
            check(request($uid, $endpoint)['status'] === 403, "Owner can access platform {$endpoint}.");
            $requests++;
        }
        $otherOwner = $db->selectOne('SELECT owner_id FROM organizations WHERE owner_id != ? LIMIT 1', [$uid], 'i');
        check((new UserRepository(true))->getUserFullSystemInfo((int)$otherOwner['owner_id']) === null, 'Another owner account exposed.');
        check(request($uid, 'user-detail', ['id' => (int)$otherOwner['owner_id']])['status'] >= 400, 'Foreign owner account detail exposed.');
        $requests++;
        $ownSessions = request($uid, 'open-play');
        check($ownSessions['status'] === 200, 'Own session list unavailable.');
        foreach ($ownSessions['body']['data'] as $sessionRow) {
            check((bool)$db->selectOne('SELECT f.id FROM facilities f JOIN organizations o ON o.id = f.organization_id WHERE f.id = ? AND o.owner_id = ?', [(int)$sessionRow['facility_id'], $uid], 'ii'), 'Unfiltered session list exposes another facility.');
        }
        $requests++;
        $foreignBooking = $db->selectOne('SELECT b.id FROM bookings b JOIN facilities f ON f.id = b.facility_id JOIN organizations o ON o.id = f.organization_id WHERE o.owner_id != ? LIMIT 1', [$uid], 'i');
        if ($foreignBooking) {
            check(request($uid, 'booking-detail', ['booking_id' => (int)$foreignBooking['id']])['status'] === 403, 'Foreign booking exposed.');
            $requests++;
        }
        $foreignSale = $db->selectOne('SELECT s.id FROM product_sales s JOIN facilities f ON f.id = s.facility_id JOIN organizations o ON o.id = f.organization_id WHERE o.owner_id != ? AND (s.user_id IS NULL OR s.user_id != ?) LIMIT 1', [$uid, $uid], 'ii');
        if ($foreignSale) {
            check(request($uid, 'receipt', ['sale_id' => (int)$foreignSale['id']])['status'] === 403, 'Foreign receipt exposed.');
            $requests++;
        }
        $session = $db->selectOne('SELECT id FROM open_play_sessions WHERE facility_id = ? LIMIT 1', [$foreignFacility], 'i');
        if ($session) {
            check((new OpenPlayRepository(true))->getSessionById((int)$session['id']) === null, 'Foreign session detail exposed.');
            foreach (['roster_dt', 'player_suggestions'] as $action) {
                $response = request($uid, 'open-play', ['action' => $action, 'session_id' => (int)$session['id']]);
                check($response['status'] >= 400, 'Foreign player roster exposed.');
                $requests++;
            }
        }
    }
    Session::set('user_id', 2147483647); Session::set('user_role', 'court_owner'); Session::set('organization_id', 1);
    check(Auth::organizationId() === null, 'Unassigned owner inherits a tenant.');
    check((new SubscriptionPlanRepository())->getTenantSubscriptionSummary(0)['subscription'] === null, 'Unassigned owner inherits another subscription.');
    foreach (['calendar', 'payments', 'subscription-payments'] as $endpoint) {
        check(request(2147483647, $endpoint)['status'] === 403, 'Unassigned owner receives unscoped data.');
        $requests++;
    }
    Session::destroy();
    echo 'PASS: ' . count($owners) . " owners; {$requests} API checks; forged tenant selection, foreign facilities, owner accounts, rosters and unassigned accounts checked.\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'FAIL: ' . $e->getMessage() . "\n");
    exit(1);
}
