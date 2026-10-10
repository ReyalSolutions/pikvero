<?php
require_once __DIR__ . '/../../app/bootstrap.php';
require_once __DIR__ . '/../../includes/player-profile-data.php';
use App\Core\Auth\Auth;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Database\Connection;
Auth::requireAuth();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') Response::error('Use POST to save preferences.');
$input = (new Request())->all();
if (!hash_equals($_SESSION['profile_csrf'] ?? '', (string)($input['csrf'] ?? '')) || empty($_SESSION['profile_csrf'])) Response::error('Please reload and try again.');
$level = $input['playing_level'] ?? '';
if (!in_array($level, ['Beginner','Intermediate','Advanced','Pro'], true)) Response::error('Select a playing level.');
playerProfileData((int)Auth::id());
Connection::getInstance()->execute('INSERT INTO player_profiles (user_id,playing_level) VALUES (?,?) ON DUPLICATE KEY UPDATE playing_level=VALUES(playing_level)', [(int)Auth::id(),$level], 'is');
Response::success('Play preferences saved.');
