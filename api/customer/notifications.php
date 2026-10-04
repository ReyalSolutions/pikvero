<?php
require_once __DIR__ . '/../../app/bootstrap.php';
use App\Core\Database\Connection;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Auth\Auth;

$db = Connection::getInstance();
$request = new Request();
$action = $request->get('action', 'get_preferences');

if (!Auth::check()) {
    Response::success('Guest notifications', [
        'preferences' => [
            'email_notifications' => 0,
            'sms_notifications' => 0,
            'push_notifications' => 0
        ],
        'unread_count' => 0,
        'notifications' => []
    ]);
    exit;
}

$userId = Auth::id();

if ($action === 'get_preferences') {
    $pref = $db->selectOne(
        "SELECT email_notifications, sms_notifications, push_notifications FROM notification_preferences WHERE user_id = ? LIMIT 1",
        [$userId],
        'i'
    );

    if (!$pref) {
        $pref = [
            'email_notifications' => 1,
            'sms_notifications' => 1,
            'push_notifications' => 1
        ];
    }

    Response::success('Notification preferences', [
        'email_notifications' => (int)$pref['email_notifications'],
        'sms_notifications'   => (int)$pref['sms_notifications'],
        'push_notifications'  => (int)$pref['push_notifications']
    ]);
    exit;
}

if ($request->getMethod() === 'POST' && $action === 'save_preferences') {
    $rawInput = json_decode(file_get_contents('php://input'), true);
    $data = is_array($rawInput) ? $rawInput : $_POST;

    $push = isset($data['push_notifications']) ? ((bool)$data['push_notifications'] ? 1 : 0) : 1;
    $email = isset($data['email_notifications']) ? ((bool)$data['email_notifications'] ? 1 : 0) : 1;

    $db->execute("
        INSERT INTO notification_preferences (user_id, email_notifications, push_notifications)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE email_notifications = VALUES(email_notifications), push_notifications = VALUES(push_notifications)
    ", [$userId, $email, $push], 'iii');

    Response::success('Notification preferences updated successfully.', [
        'push_notifications'  => $push,
        'email_notifications' => $email
    ]);
    exit;
}

if ($action === 'get_unread') {
    $notifications = $db->select(
        "SELECT id, title, message, type, created_at FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY id DESC LIMIT 10",
        [$userId],
        'i'
    );

    // Mark as read after fetching for popup
    if (!empty($notifications)) {
        $ids = array_column($notifications, 'id');
        $inClause = implode(',', array_map('intval', $ids));
        $db->execute("UPDATE notifications SET is_read = 1 WHERE id IN ({$inClause})");
    }

    Response::success('Unread notifications', [
        'notifications' => $notifications
    ]);
    exit;
}

if ($request->getMethod() === 'POST' && $action === 'save_fcm_token') {
    $rawInput = json_decode(file_get_contents('php://input'), true);
    $data = is_array($rawInput) ? $rawInput : $_POST;
    $fcmToken = trim($data['fcm_token'] ?? '');

    if (!empty($fcmToken)) {
        $db->execute("
            INSERT INTO notification_preferences (user_id, fcm_token)
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE fcm_token = VALUES(fcm_token)
        ", [$userId, $fcmToken], 'is');
    }

    Response::success('FCM token saved.');
    exit;
}

Response::error('Invalid notification action.');
