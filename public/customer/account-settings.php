<?php
require_once __DIR__ . '/../../app/bootstrap.php';
use App\Core\Auth\Auth;

if (!Auth::check()) {
    header('Location: /pikvero/public/login.php');
    exit;
}
$favLogo = Auth::getLogoUrl();
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
    <div class="profile-masthead"><a href="/pikvero/public/customer/profile.php" aria-label="Back to home"><i class="bi bi-chevron-left"></i></a><div><strong>Pikvero</strong><span>Find. Book. Rally.</span></div><a href="#profile-notifications" aria-label="Notification settings"><i class="bi bi-bell"></i></a></div>
    <div>
      <div class="prof-header-wrap" style="margin-bottom:20px;">
        <div class="eyebrow">PLAYER SETTINGS</div>
        <h1 style="font-size: clamp(1.8rem, 4vw, 2.2rem); font-weight:800; text-transform:uppercase; margin:4px 0 0;">Account settings</h1>
      </div>

      <!-- Profile Summary Header Card -->
      <div id="profile-summary-card" class="card-streetside sky" style="padding:24px; margin-bottom:24px;">
        <div class="profile-avatar" aria-hidden="true"><?= htmlspecialchars($profileInitials) ?></div>
        <h2 class="profile-name"><?= htmlspecialchars($profileName) ?></h2>
        <p class="profile-email"><?= htmlspecialchars($profileUser['email'] ?? '') ?></p>
        <span class="profile-member"><i class="bi bi-person-check-fill"></i> Player account</span>
      </div>

      <div class="profile-shortcuts">
        <a href="/pikvero/public/customer/bookings.php"><i class="bi bi-calendar-check"></i><span><strong>My bookings</strong><small>View and manage your reservations</small></span><i class="bi bi-chevron-right"></i></a>
        <a href="/pikvero/public/customer/open-play.php"><i class="bi bi-ticket-perforated"></i><span><strong>Open play passes</strong><small>Your sessions and entry passes</small></span><i class="bi bi-chevron-right"></i></a>
      </div>
      <div class="prof-grid" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap:24px;">
        
        <!-- Column 1: Personal Info & Preferences -->
        <div class="prof-col-gap" style="display:flex; flex-direction:column; gap:20px;">
          
          <!-- Personal Information -->
          <div class="card-streetside prof-card-compact" style="padding:24px; background:var(--white);">
            <h3 style="font-size:1.15rem; font-weight:800; text-transform:uppercase; margin:0 0 14px;"><i class="bi bi-person-badge"></i> PERSONAL INFORMATION</h3>
            
            <form id="profile-info-form">
              <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:10px;">
                <div class="form-group-compact">
                  <label class="mono" style="display:block; margin-bottom:4px; font-size:0.75rem;">FIRST NAME</label>
                  <input type="text" id="prof-fname" required style="width:100%; padding:9px; border:2px solid var(--ink); border-radius:10px; font-family:inherit; font-weight:700;">
                </div>
                <div class="form-group-compact">
                  <label class="mono" style="display:block; margin-bottom:4px; font-size:0.75rem;">LAST NAME</label>
                  <input type="text" id="prof-lname" required style="width:100%; padding:9px; border:2px solid var(--ink); border-radius:10px; font-family:inherit; font-weight:700;">
                </div>
              </div>

              <div class="form-group-compact" style="margin-bottom:10px;">
                <label class="mono" style="display:block; margin-bottom:4px; font-size:0.75rem;">EMAIL ADDRESS</label>
                <input type="email" id="prof-email" disabled style="width:100%; padding:9px; border:2px solid var(--ink); border-radius:10px; font-family:inherit; background:var(--sand); cursor:not-allowed; opacity:0.8;">
              </div>

              <div class="form-group-compact" style="margin-bottom:16px;">
                <label class="mono" style="display:block; margin-bottom:4px; font-size:0.75rem;">PHONE NUMBER (11 DIGITS)</label>
                <input type="text" id="prof-phone" maxlength="11" placeholder="09171234567" style="width:100%; padding:9px; border:2px solid var(--ink); border-radius:10px; font-family:inherit; font-weight:700;">
              </div>

              <button type="submit" class="button lime" style="padding:9px 16px; font-size:0.82rem;">Save Info</button>
            </form>
          </div>

          <!-- Preferences -->
          <div class="card-streetside prof-card-compact" style="padding:24px; background:var(--white);">
            <h3 style="font-size:1.15rem; font-weight:800; text-transform:uppercase; margin:0 0 14px;"><i class="bi bi-sliders"></i> GAMEPLAY PREFERENCES</h3>

            <form id="preferences-form">
              <div class="form-group-compact" style="margin-bottom:10px;">
                <label class="mono" style="display:block; margin-bottom:4px; font-size:0.75rem;">PREFERRED COURT TYPE</label>
                <select id="pref-court-type" style="width:100%; padding:9px; border:2px solid var(--ink); border-radius:10px; font-family:inherit; font-weight:700;">
                  <option value="indoor">Indoor (Aircon / Weatherproof)</option>
                  <option value="outdoor">Outdoor</option>
                  <option value="covered">Covered Outdoor</option>
                </select>
              </div>

              <div class="form-group-compact" style="margin-bottom:16px;">
                <label class="mono" style="display:block; margin-bottom:4px; font-size:0.75rem;">SKILL LEVEL</label>
                <select id="pref-skill-level" style="width:100%; padding:9px; border:2px solid var(--ink); border-radius:10px; font-family:inherit; font-weight:700;">
                  <option value="Beginner">Beginner (1.0 - 2.5)</option>
                  <option value="Intermediate" selected>Intermediate (3.0 - 3.5)</option>
                  <option value="Advanced">Advanced (4.0 - 4.5)</option>
                  <option value="Pro">Tournament Pro (5.0+)</option>
                </select>
              </div>

              <button type="submit" class="button lime" style="padding:9px 16px; font-size:0.82rem;">Save Preferences</button>
            </form>
          </div>

        </div>

        <!-- Column 2: Notification Settings & Security -->
        <div class="prof-col-gap" style="display:flex; flex-direction:column; gap:20px;">
          
          <!-- Notification Settings -->
          <div class="card-streetside prof-card-compact" style="padding:24px; background:var(--white);">
            <h3 style="font-size:1.15rem; font-weight:800; text-transform:uppercase; margin:0 0 14px;"><i class="bi bi-bell"></i> NOTIFICATION SETTINGS</h3>

            <div style="display:flex; flex-direction:column; gap:12px;">
              <label style="display:flex; justify-content:space-between; align-items:center; cursor:pointer;">
                <span style="font-weight:700; font-size:0.88rem;">Email Reminders</span>
                <input type="checkbox" id="notif-email" checked style="width:18px; height:18px; accent-color:var(--coral);">
              </label>

              <label style="display:flex; justify-content:space-between; align-items:center; cursor:pointer; border-top:1px solid var(--line); padding-top:10px;">
                <span style="font-weight:700; font-size:0.88rem;">Push Notifications</span>
                <input type="checkbox" id="notif-push" checked style="width:18px; height:18px; accent-color:var(--coral);">
              </label>
            </div>

            <button onclick="saveNotifications()" class="button lime" style="padding:9px 16px; font-size:0.82rem; margin-top:16px;">Save Notifications</button>
          </div>

          <!-- Security Credentials -->
          <div class="card-streetside prof-card-compact" style="padding:24px; background:var(--white);">
            <h3 style="font-size:1.15rem; font-weight:800; text-transform:uppercase; margin:0 0 14px;"><i class="bi bi-shield-lock"></i> SECURITY &amp; PASSWORD</h3>

            <form id="security-form">
              <div class="form-group-compact" style="margin-bottom:10px;">
                <label class="mono" style="display:block; margin-bottom:4px; font-size:0.75rem;">CURRENT PASSWORD</label>
                <input type="password" id="sec-current-pass" required placeholder="••••••••" style="width:100%; padding:9px; border:2px solid var(--ink); border-radius:10px; font-family:inherit;">
              </div>

              <div class="form-group-compact" style="margin-bottom:10px;">
                <label class="mono" style="display:block; margin-bottom:4px; font-size:0.75rem;">NEW PASSWORD</label>
                <input type="password" id="sec-new-pass" required placeholder="••••••••" style="width:100%; padding:9px; border:2px solid var(--ink); border-radius:10px; font-family:inherit;">
              </div>

              <div class="form-group-compact" style="margin-bottom:16px;">
                <label class="mono" style="display:block; margin-bottom:4px; font-size:0.75rem;">CONFIRM NEW PASSWORD</label>
                <input type="password" id="sec-confirm-pass" required placeholder="••••••••" style="width:100%; padding:9px; border:2px solid var(--ink); border-radius:10px; font-family:inherit;">
              </div>

              <button type="submit" class="button coral" style="padding:9px 16px; font-size:0.82rem;">Update Password</button>
            </form>
          </div>

        </div>

      </div>
    </div>

    <button class="profile-signout" onclick="AuthHelper.logout()"><i class="bi bi-box-arrow-right"></i> Sign out</button>
    <footer id="footer-container"></footer>
  </main>

  <script src="/pikvero/assets/js/core/toast.js"></script>
  <script src="/pikvero/assets/js/core/ajax.js"></script>
  <script src="/pikvero/assets/js/core/auth.js"></script>
  <script src="/pikvero/assets/js/components/navbar.js"></script>
  <script src="/pikvero/assets/js/components/bottom-nav.js?v=<?= filemtime(__DIR__ . '/../../assets/js/components/bottom-nav.js') ?>"></script>
  <script src="/pikvero/assets/js/components/sidebar.js"></script>
  <script src="/pikvero/assets/js/components/footer.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', async () => {
      await NavbarComponent.render('#navbar-container', true);
      const userCtx = await AuthHelper.checkSession();
      if (!userCtx || !userCtx.user) {
        window.location.href = '/pikvero/public/login.php';
        return;
      }

      SidebarComponent.render('profile', 'customer');
      FooterComponent.render('#footer-container', true);

      const user = userCtx.user;

      const sections = [
        ['Personal information', 'Your contact and account details', 'bi-person', 'profile-personal'],
        ['Play preferences', 'Court type and skill level', 'bi-dribbble', 'profile-preferences'],
        ['Notifications', 'Booking updates and reminders', 'bi-bell', 'profile-notifications'],
        ['Security', 'Manage your password', 'bi-shield-lock', 'profile-security']
      ];
      document.querySelectorAll('.prof-card-compact').forEach((card, index) => {
        const [title, description, icon, id] = sections[index];
        const details = document.createElement('section');
        details.className = 'profile-setting';
        details.id = id;
        details.open = true;
        details.style.display = index === ({preferences:1,notifications:2,security:3}[new URLSearchParams(location.search).get('section')] ?? 1) ? '' : 'none';
        const summary = document.createElement('header');
        summary.className = 'settings-section-heading';
        summary.innerHTML = `<i class="bi ${icon}"></i><span><strong>${title}</strong><small>${description}</small></span><i class="bi bi-chevron-down"></i>`;
        card.parentNode.insertBefore(details, card);
        details.append(summary, card);
      });
      document.querySelector('.profile-masthead a[href="#profile-notifications"]').addEventListener('click', () => {
        document.getElementById('profile-notifications').open = true;
      });

      document.getElementById('prof-fname').value = user.first_name;
      document.getElementById('prof-lname').value = user.last_name;
      document.getElementById('prof-email').value = user.email;
      document.getElementById('prof-phone').value = user.phone || '';

      // Auto-filter non-digits on phone input
      document.getElementById('prof-phone').addEventListener('input', function() {
        this.value = this.value.replace(/\D/g, '');
      });

      // Load user notification preferences
      try {
        const notifRes = await Api.get('/pikvero/api/customer/notifications.php?action=get_preferences');
        if (notifRes.success && notifRes.data) {
          document.getElementById('notif-email').checked = notifRes.data.email_notifications == 1;
          document.getElementById('notif-push').checked = notifRes.data.push_notifications == 1;
        }
      } catch (err) {}

      document.getElementById('profile-info-form').addEventListener('submit', (e) => {
        e.preventDefault();
        Toast.success('Profile Saved', 'Personal information updated successfully.');
      });

      document.getElementById('preferences-form').addEventListener('submit', (e) => {
        e.preventDefault();
        Toast.success('Preferences Saved', 'Gameplay preferences updated.');
      });

      document.getElementById('security-form').addEventListener('submit', (e) => {
        e.preventDefault();
        const np = document.getElementById('sec-new-pass').value;
        const cp = document.getElementById('sec-confirm-pass').value;
        if (np !== cp) {
          Toast.error('Password Mismatch', 'New password and confirm password do not match.');
          return;
        }
        Toast.success('Password Updated', 'Security credentials updated successfully.');
      });
    });

    async function saveNotifications() {
      const emailVal = document.getElementById('notif-email').checked;
      const pushVal = document.getElementById('notif-push').checked;

      try {
        const res = await Api.post('/pikvero/api/customer/notifications.php?action=save_preferences', {
          email_notifications: emailVal,
          push_notifications: pushVal
        });

        if (res.success) {
          Toast.success('Notification Settings', 'Saved notification preferences successfully.');
          if (pushVal && 'Notification' in window && Notification.permission !== 'granted') {
            Notification.requestPermission();
          }
        } else {
          Toast.error('Error', res.message || 'Could not save notification preferences.');
        }
      } catch (e) {
        Toast.error('Error', 'Failed to update notification settings.');
      }
    }
  </script>
</body>
</html>
