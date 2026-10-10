<?php
require_once __DIR__ . '/../../app/bootstrap.php';
use App\Core\Auth\Auth;
if (!Auth::check()) {header('Location: /pikvero/public/login.php');exit;}
if (empty($_SESSION['profile_csrf'])) $_SESSION['profile_csrf']=bin2hex(random_bytes(24));
if (!Auth::hasRole('super_admin','platform_admin','developer','court_owner','owner') && !Auth::can('system.manage')) {header('Location: /pikvero/public/403.php');exit;}
$pageTitle='Pikvero — Announcements'; $headExtras=[];
require_once __DIR__ . '/../../includes/head.php';
?>
<link rel="stylesheet" href="/pikvero/assets/css/inbox.css?v=<?= filemtime(__DIR__.'/../../assets/css/inbox.css') ?>"><link rel="stylesheet" href="/pikvero/assets/css/toast.css"></head><body><div id="sidebar-container"></div><div id="navbar-container"></div><main class="portal-main inbox-main">
<h1>Announcements</h1><p><?php echo Auth::hasRole('court_owner','owner') ? 'Share updates with players who have booked or joined sessions at your facilities.' : 'Share platform updates with all logged-in users.'; ?></p>
<form id="announcement-form" class="inbox-card"><label for="announcement-title">Title</label><input id="announcement-title" required minlength="4" maxlength="200"><label for="announcement-message">Message</label><textarea id="announcement-message" required minlength="10" maxlength="10000" rows="5"></textarea><button class="inbox-save" type="submit">Publish announcement</button></form>
<h2>Published announcements</h2><p id="inbox-status" role="status">Loading…</p><div id="inbox-list"></div></main>
<?php require_once __DIR__ . '/../../includes/scripts.php'; ?>
<script>window.INBOX_MANAGE=true;window.INBOX_CSRF=<?= json_encode($_SESSION['profile_csrf']) ?>;document.addEventListener('DOMContentLoaded',()=>{NavbarComponent.render('#navbar-container',true);SidebarComponent.render('announcements','admin');});</script><script src="/pikvero/assets/js/components/inbox.js?v=<?= filemtime(__DIR__.'/../../assets/js/components/inbox.js') ?>"></script></body></html>