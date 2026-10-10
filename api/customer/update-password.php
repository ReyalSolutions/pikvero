<?php
require_once __DIR__ . '/../../app/bootstrap.php';
use App\Core\Auth\Auth;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Database\Connection;
Auth::requireAuth();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') Response::error('Use POST to update your password.');
$data = (new Request())->all();
if (empty($_SESSION['profile_csrf']) || !hash_equals($_SESSION['profile_csrf'], (string)($data['csrf'] ?? ''))) Response::error('Please reload and try again.');
$current = (string)($data['current_password'] ?? ''); $new = (string)($data['new_password'] ?? '');
if (strlen($new) < 8) Response::error('Use at least 8 characters for your new password.');
if ($new !== (string)($data['confirm_password'] ?? '')) Response::error('The new passwords do not match.');
$db = Connection::getInstance();
$user = $db->selectOne('SELECT password_hash FROM users WHERE id = ? AND deleted_at IS NULL', [(int)Auth::id()], 'i');
if (!$user || !password_verify($current, $user['password_hash'])) Response::error('Your current password is incorrect.');
if (password_verify($new, $user['password_hash'])) Response::error('Choose a different password.');
if (!$db->execute('UPDATE users SET password_hash=?, updated_at=NOW() WHERE id=?', [password_hash($new,PASSWORD_DEFAULT),(int)Auth::id()], 'si')) Response::error('Unable to update your password. Please try again.');
Response::success('Password updated successfully.');
