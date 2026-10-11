<?php
require_once __DIR__ . '/../app/bootstrap.php';
use App\Core\Auth\Auth;
use App\Core\Auth\Session;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Infrastructure\Repositories\ReferralRepository;
Auth::requireAuth();
$admin = in_array(Auth::role(), ['super_admin','platform_admin','finance_admin'], true);
$userId = (int)Auth::id();
$request = new Request();
$repo = new ReferralRepository();
header('Cache-Control: no-store');
try {
    $account = \App\Core\Database\Connection::getInstance()->selectOne("SELECT r.name AS role FROM users u JOIN roles r ON r.id=u.role_id WHERE u.id=? AND u.status='active' AND u.deleted_at IS NULL", [$userId], 'i');
    if (!$account) Response::forbidden('An active account is required.');
    $admin = in_array($account['role'], ['super_admin','platform_admin','finance_admin'], true);
    if (!in_array($request->getMethod(), ['GET','POST'], true)) Response::error('Method not allowed.', [], 405);
    if ($request->isPost()) {
        $token = (string)$request->get('csrf_token', '');
        if (!$token || !hash_equals((string)Session::get('referral_csrf', ''), $token)) Response::error('Please reload the page and try again.', [], 403);
        $action = (string)$request->get('action');
        if ($action === 'attach') {
            $repo->attach($userId, (string)$request->get('referral_code',''));
        } else {
            $repo->transition((int)$request->get('id'), $action, $userId, $admin, (string)$request->get('reference',''));
        }
        Response::success('Referral record updated.');
    } else {
        if ($admin) $repo->syncPayments();
        $token = Session::get('referral_csrf');
        if (!$token) { $token=bin2hex(random_bytes(24)); Session::set('referral_csrf',$token); }
        $manage = $admin && $request->get('scope') !== 'mine';
        Response::success('Referral rewards', ['code'=>$repo->ensureCode($userId), 'admin'=>$manage, 'csrf_token'=>$token, 'rewards'=>$repo->list($userId,$manage)]);
    }
} catch (\mysqli_sql_exception $e) {
    error_log('Referral database error: ' . $e->getMessage());
    Response::error('Unable to process the referral record. Please try again.', [], 500);
} catch (\RuntimeException $e) { Response::error($e->getMessage()); }
catch (\Throwable $e) { error_log('Referral error: ' . $e->getMessage()); Response::error('Unable to process the referral request.', [], 500); }
