<?php
require_once __DIR__ . '/../../app/bootstrap.php';
use App\Core\Auth\Auth;

if (!Auth::check()) {
    header('Location: /pikvero/public/login.php');
    exit;
}
if (empty($_SESSION['profile_csrf'])) $_SESSION['profile_csrf'] = bin2hex(random_bytes(24));
$profileUpdated = !empty($_SESSION['player_profile_updated']);
unset($_SESSION['player_profile_updated']);
$favLogo = Auth::getLogoUrl();
require_once __DIR__ . '/../../includes/player-profile-data.php';
$profileExtra = playerProfileData((int)Auth::id());
$profileUser = Auth::user() ?? [];
$profileName = trim(($profileUser['first_name'] ?? '') . ' ' . ($profileUser['last_name'] ?? ''));
$profileInitials = strtoupper(substr($profileUser['first_name'] ?? '', 0, 1) . substr($profileUser['last_name'] ?? '', 0, 1));
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Pikvero — My Account &amp; Profile Settings</title>
  <link rel="icon" type="image/png" href="<?= htmlspecialchars($favLogo) ?>?v=<?= time() ?>">
  <link rel="shortcut icon" type="image/png" href="<?= htmlspecialchars($favLogo) ?>?v=<?= time() ?>">
  <link rel="apple-touch-icon" href="<?= htmlspecialchars($favLogo) ?>?v=<?= time() ?>">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="/pikvero/assets/css/streetside-theme.css?v=<?= filemtime(__DIR__ . '/../../assets/css/streetside-theme.css') ?>">
  <link rel="stylesheet" href="/pikvero/assets/css/toast.css?v=<?= filemtime(__DIR__ . '/../../assets/css/toast.css') ?>">
  <style>
    @media (max-width: 768px) {
      .portal-main {
        padding: 12px 10px 85px !important;
      }
      .prof-header-wrap {
        margin-bottom: 10px !important;
      }
      .prof-header-wrap h1 {
        font-size: 1.35rem !important;
      }
      #profile-summary-card {
        padding: 10px 12px !important;
        margin-bottom: 10px !important;
        border-radius: 12px !important;
        box-shadow: 2px 2px 0 var(--ink) !important;
      }
      #profile-summary-card .brand-mark {
        width: 36px !important;
        height: 36px !important;
        font-size: 1rem !important;
      }
      #profile-summary-card h2 {
        font-size: 1.05rem !important;
      }
      #profile-summary-card div {
        font-size: 0.75rem !important;
      }
      .prof-grid {
        gap: 8px !important;
        grid-template-columns: 1fr !important;
      }
      .prof-col-gap {
        gap: 8px !important;
      }
      .prof-card-compact {
        padding: 10px 12px !important;
        border-radius: 12px !important;
        box-shadow: 2px 2px 0 var(--ink) !important;
      }
      .prof-card-compact h3 {
        font-size: 0.95rem !important;
        margin-bottom: 8px !important;
      }
      .prof-card-compact label {
        font-size: 0.62rem !important;
        margin-bottom: 2px !important;
      }
      .prof-card-compact input[type="text"],
      .prof-card-compact input[type="email"],
      .prof-card-compact input[type="password"],
      .prof-card-compact select {
        padding: 5px 8px !important;
        font-size: 0.75rem !important;
        border-radius: 6px !important;
      }
      .form-group-compact {
        margin-bottom: 6px !important;
      }
      .prof-card-compact .button {
        padding: 6px 14px !important;
        font-size: 0.75rem !important;
      }
    }
  </style>
<link rel="stylesheet" href="/pikvero/assets/css/player-profile.css?v=<?= filemtime(__DIR__ . '/../../assets/css/player-profile.css') ?>">
<link rel="manifest" href="/pikvero/manifest.webmanifest">
<meta name="theme-color" content="#003d2d">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="Pikvero">
<link rel="apple-touch-icon" href="/pikvero/assets/images/pwa/icon-180.png">
<script defer src="/pikvero/assets/js/components/pwa.js?v=20261010"></script>
</head>
<body class="customer-portal player-profile">

  <aside id="sidebar-container"></aside>
  <header id="navbar-container"></header>

  <main class="portal-main">
    <div class="profile-masthead"><a href="/pikvero/public/customer/dashboard.php" aria-label="Back to home"><i class="bi bi-chevron-left"></i></a><div><strong>Pikvero</strong><span>Find. Book. Rally.</span></div><a href="/pikvero/public/notifications.php" aria-label="Your notifications"><i class="bi bi-bell"></i></a></div>
    <div>
      <div class="prof-header-wrap" style="margin-bottom:20px;">
        <div class="eyebrow">PLAYER SETTINGS</div>
        <h1 style="font-size: clamp(1.8rem, 4vw, 2.2rem); font-weight:800; text-transform:uppercase; margin:4px 0 0;">ACCOUNT PROFILE &amp; PREFERENCES</h1>
      </div>

      <!-- Profile Summary Header Card -->
      <div id="profile-summary-card" class="card-streetside sky" style="padding:24px; margin-bottom:24px;">
        <div class="profile-avatar-wrap"><div class="profile-avatar" aria-hidden="true"><?php if (!empty($profileExtra['image_url'])): ?><img src="<?= htmlspecialchars($profileExtra['image_url']) ?>" alt=""><?php else: ?><?= htmlspecialchars($profileInitials) ?><?php endif; ?></div><a class="profile-camera" href="/pikvero/public/customer/edit-profile.php#profile-photo" aria-label="Update profile photo"><i class="bi bi-camera-fill"></i></a></div>
        <h2 class="profile-name"><?= htmlspecialchars($profileName) ?></h2>
        <p class="profile-email"><?= htmlspecialchars($profileUser['email'] ?? '') ?></p>
        <span class="profile-member"><i class="bi bi-person-check-fill"></i> Player account</span>
      </div>

      <div class="profile-shortcuts">
        <a href="/pikvero/public/customer/bookings.php"><i class="bi bi-calendar-check"></i><span><strong>My bookings</strong><small>View and manage your reservations</small></span><i class="bi bi-chevron-right"></i></a>
        <a href="/pikvero/public/customer/open-play.php"><i class="bi bi-ticket-perforated"></i><span><strong>Open play passes</strong><small>Your sessions and entry passes</small></span><i class="bi bi-chevron-right"></i></a>
        <a href="/pikvero/public/customer/referrals.php"><i class="bi bi-gift"></i><span><strong>Referral bonuses</strong><small>Refer an owner, earn ₱150 and claim your bonus</small></span><i class="bi bi-chevron-right"></i></a>
      </div>
      <div class="profile-shortcuts">
        <a href="/pikvero/public/customer/edit-profile.php"><i class="bi bi-person"></i><span><strong>Personal information</strong><small>Your contact and account details</small></span><i class="bi bi-chevron-right"></i></a>
        <a href="#preferences-modal" data-profile-modal="preferences-modal"><i class="bi bi-dribbble"></i><span><strong>Play preferences</strong><small>Your playing level</small></span><i class="bi bi-chevron-right"></i></a>
        <a href="#notifications-modal" data-profile-modal="notifications-modal"><i class="bi bi-bell"></i><span><strong>Notifications</strong><small>Booking updates and reminders</small></span><i class="bi bi-chevron-right"></i></a>
        <a href="#password-modal" data-profile-modal="password-modal"><i class="bi bi-shield-lock"></i><span><strong>Update password</strong><small>Manage your password</small></span><i class="bi bi-chevron-right"></i></a>
      </div>
    </div>

<h2 class="profile-support-title">Support</h2><div class="profile-shortcuts"><a href="#help-modal" data-profile-modal="help-modal"><i class="bi bi-question-circle"></i><span><strong>Help Center</strong></span><i class="bi bi-chevron-right"></i></a><a href="#contact-modal" data-profile-modal="contact-modal"><i class="bi bi-chat-heart"></i><span><strong>Contact Us</strong></span><i class="bi bi-chevron-right"></i></a><a href="#terms-modal" data-profile-modal="terms-modal"><i class="bi bi-file-text"></i><span><strong>Terms of Service</strong></span><i class="bi bi-chevron-right"></i></a><a href="#privacy-modal" data-profile-modal="privacy-modal"><i class="bi bi-shield-lock"></i><span><strong>Privacy Policy</strong></span><i class="bi bi-chevron-right"></i></a><a href="#bug-modal" data-profile-modal="bug-modal"><i class="bi bi-bug"></i><span><strong>Report a bug</strong></span><i class="bi bi-chevron-right"></i></a></div>
    <button class="profile-signout" onclick="AuthHelper.logout()"><i class="bi bi-box-arrow-right"></i> Sign out</button>
    <footer id="footer-container"></footer>
  </main>

<dialog id="password-modal" class="profile-dialog" aria-labelledby="password-modal-title"><div class="profile-dialog-heading"><h2 id="password-modal-title">Update password</h2><button type="button" data-close-dialog aria-label="Close Update password">&times;</button></div><form id="profile-password-form"><label for="password-current">Current password</label><input id="password-current" type="password" autocomplete="current-password" required><label for="password-new">New password</label><input id="password-new" type="password" autocomplete="new-password" minlength="8" required><small>Use at least 8 characters.</small><label for="password-confirm">Confirm new password</label><input id="password-confirm" type="password" autocomplete="new-password" minlength="8" required><button class="profile-save" type="submit">Update password</button></form></dialog><dialog id="help-modal" class="profile-dialog" aria-labelledby="help-modal-title"><div class="profile-dialog-heading"><h2 id="help-modal-title">Help Center</h2><button type="button" data-close-dialog aria-label="Close Help Center">&times;</button></div><div class="profile-support-content"><h3>Book a court</h3><p>Open Explore, choose a court, select an available time and complete checkout. Find your reservation in My bookings.</p><h3>Manage a reservation</h3><p>Open My bookings to view your voucher or receipt. If cancellation is available, use the cancel action on your reservation. Facility policies apply.</p><h3>Join open play</h3><p>Browse Open Play, choose a session with available places and confirm your registration. Your entry pass appears under My Passes.</p><h3>Need more help?</h3><p>Use Contact Us for account or booking questions, or Report a bug for technical problems. Include your booking reference when relevant. Never share your password or card details.</p></div></dialog><dialog id="terms-modal" class="profile-dialog" aria-labelledby="terms-modal-title"><div class="profile-dialog-heading"><h2 id="terms-modal-title">Terms of Service</h2><button type="button" data-close-dialog aria-label="Close Terms of Service">&times;</button></div><div class="profile-support-content"><h4 style="font-weight:800;">1. Acceptance of Terms</h4>
      <p style="font-size:0.88rem; color:#3b4e48; line-height:1.5;">By accessing or using Pikvero, you agree to comply with and be bound by these Terms of Service. Pikvero provides an online reservation platform connecting players and court owners.</p>

      <h4 style="font-weight:800; margin-top:20px;">2. Court Reservations &amp; Cancellation Policy</h4>
      <p style="font-size:0.88rem; color:#3b4e48; line-height:1.5;">All reservations are subject to facility availability and atomic double-booking checks. Cancellations must adhere to individual facility operating policies.</p>
</div></dialog><dialog id="privacy-modal" class="profile-dialog" aria-labelledby="privacy-modal-title"><div class="profile-dialog-heading"><h2 id="privacy-modal-title">Privacy Policy</h2><button type="button" data-close-dialog aria-label="Close Privacy Policy">&times;</button></div><div class="profile-support-content"><h4 style="font-weight:800;">1. Information We Collect</h4>
      <p style="font-size:0.88rem; color:#3b4e48; line-height:1.5;">We collect name, email address, 11-digit phone number, and reservation history required to process court bookings and multi-tenant authentication.</p>

      <h4 style="font-weight:800; margin-top:20px;">2. Security &amp; Tenant Isolation</h4>
      <p style="font-size:0.88rem; color:#3b4e48; line-height:1.5;">Personal data and payment transaction records are secured using MySQLi prepared statements, password hashing, and server-side multi-tenant isolation rules.</p>
</div></dialog><dialog id="contact-modal" class="profile-dialog" aria-labelledby="contact-modal-title"><div class="profile-dialog-heading"><h2 id="contact-modal-title">Contact Us</h2><button type="button" data-close-dialog aria-label="Close Contact Us">&times;</button></div><p class="profile-dialog-description">Tell us how we can help with your account or booking. Do not include passwords or payment card details.</p><form id="profile-contact-form"><label for="contact-subject">Subject</label><input id="contact-subject" minlength="4" maxlength="200" required><label for="contact-description">Message</label><textarea id="contact-description" minlength="10" maxlength="10000" rows="5" required></textarea><button class="profile-save" type="submit">Send message</button></form></dialog><dialog id="bug-modal" class="profile-dialog" aria-labelledby="bug-modal-title"><div class="profile-dialog-heading"><h2 id="bug-modal-title">Report a bug</h2><button type="button" data-close-dialog aria-label="Close Report a bug">&times;</button></div><p class="profile-dialog-description">Describe what happened, the steps to reproduce it, and what you expected. Do not include passwords or payment card details.</p><form id="profile-bug-form"><label for="bug-subject">Subject</label><input id="bug-subject" minlength="4" maxlength="200" required><label for="bug-description">Steps and details</label><textarea id="bug-description" minlength="10" maxlength="10000" rows="5" required></textarea><button class="profile-save" type="submit">Submit report</button></form></dialog>
  <dialog id="preferences-modal" class="profile-dialog" aria-labelledby="preferences-title">
    <div class="profile-dialog-heading"><h2 id="preferences-title">Play preferences</h2><button type="button" data-close-dialog aria-label="Close play preferences">&times;</button></div>
    <form id="profile-preferences-form"><label for="modal-playing-level">Playing level</label><select id="modal-playing-level" required><?php foreach (['Beginner','Intermediate','Advanced','Pro'] as $level): ?><option value="<?= htmlspecialchars($level) ?>" <?= ($profileExtra['playing_level'] ?? '') === $level ? 'selected' : '' ?>><?= htmlspecialchars($level) ?></option><?php endforeach; ?></select><button class="profile-save" type="submit">Save preferences</button></form>
  </dialog>
  <dialog id="notifications-modal" class="profile-dialog" aria-labelledby="notifications-title">
    <div class="profile-dialog-heading"><h2 id="notifications-title">Notifications</h2><button type="button" data-close-dialog aria-label="Close notifications">&times;</button></div>
    <form id="profile-notifications-form"><label class="profile-toggle">Email reminders<input id="modal-email-notifications" type="checkbox"></label><label class="profile-toggle">Push notifications<input id="modal-push-notifications" type="checkbox"></label><button class="profile-save" type="submit" id="save-profile-notifications" disabled>Save notifications</button></form>
  </dialog>
  <script src="/pikvero/assets/js/core/toast.js"></script>
  <script src="/pikvero/assets/js/core/ajax.js"></script>
  <script src="/pikvero/assets/js/core/auth.js"></script>
  <script data-notification-badge src="/pikvero/assets/js/components/notification-badge.js?v=<?= filemtime(__DIR__.'/../../assets/js/components/notification-badge.js') ?>"></script>
  <script src="/pikvero/assets/js/components/navbar.js"></script>
  <script src="/pikvero/assets/js/components/bottom-nav.js?v=<?= filemtime(__DIR__ . '/../../assets/js/components/bottom-nav.js') ?>"></script>
  <script src="/pikvero/assets/js/components/sidebar.js"></script>
  <script src="/pikvero/assets/js/components/footer.js"></script>
  <script>document.addEventListener('DOMContentLoaded',()=>{SidebarComponent.render('profile','customer');
    <?php if ($profileUpdated): ?>Toast.success('Profile updated', 'Your changes have been saved.', 4500);<?php endif; ?>});</script>
<script>window.PROFILE_CSRF = <?= json_encode($_SESSION['profile_csrf']) ?>;</script><script src="/pikvero/assets/js/components/profile-settings.js?v=<?= filemtime(__DIR__ . '/../../assets/js/components/profile-settings.js') ?>"></script>
</body></html>
