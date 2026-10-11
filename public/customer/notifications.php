<?php
require_once __DIR__ . '/../../app/bootstrap.php';
use App\Core\Auth\Auth;
if (!Auth::check()) {header('Location: /pikvero/public/login.php');exit;}
$isPlayerInbox = !Auth::hasRole('court_owner','owner','super_admin','platform_admin','developer');
$inboxBack = $isPlayerInbox ? '/pikvero/public/customer/profile.php' : '/pikvero/public/admin/dashboard.php';
if (empty($_SESSION['profile_csrf'])) $_SESSION['profile_csrf']=bin2hex(random_bytes(24));
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Notifications — Pikvero</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="/pikvero/assets/css/streetside-theme.css?v=<?= filemtime(__DIR__.'/../../assets/css/streetside-theme.css') ?>">
<link rel="stylesheet" href="/pikvero/assets/css/toast.css?v=<?= filemtime(__DIR__.'/../../assets/css/toast.css') ?>">
<link rel="stylesheet" href="/pikvero/assets/css/inbox.css?v=<?= filemtime(__DIR__.'/../../assets/css/inbox.css') ?>"><meta name="theme-color" content="#003d2d">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="Pikvero">
<link rel="apple-touch-icon" href="/pikvero/assets/images/pwa/icon-180.png">
<script defer src="/pikvero/assets/js/components/pwa.js?v=20261011-shortcut"></script>
</head><body class="<?= $isPlayerInbox ? 'customer-portal ' : '' ?>inbox-page">
<header class="inbox-header"><a href="<?= htmlspecialchars($inboxBack) ?>" aria-label="Back to your portal"><i class="bi bi-chevron-left"></i></a><h1>Notifications</h1></header><main class="inbox-main">
<div class="inbox-tabs" role="group" aria-label="Inbox view"><button data-view="notifications" aria-pressed="true">Updates</button><button data-view="announcements" aria-pressed="false">Announcements</button></div>
<p id="inbox-status" role="status">Loading your notifications…</p><div id="inbox-list"></div></main>
<script src="/pikvero/assets/js/core/toast.js"></script><script src="/pikvero/assets/js/core/ajax.js"></script><script src="/pikvero/assets/js/components/bottom-nav.js?v=<?= filemtime(__DIR__.'/../../assets/js/components/bottom-nav.js') ?>"></script><script>window.INBOX_CSRF=<?= json_encode($_SESSION['profile_csrf']) ?>;<?php if ($isPlayerInbox): ?>BottomNavComponent.render('profile');<?php endif; ?></script><script src="/pikvero/assets/js/components/inbox.js?v=<?= filemtime(__DIR__.'/../../assets/js/components/inbox.js') ?>"></script></body></html>