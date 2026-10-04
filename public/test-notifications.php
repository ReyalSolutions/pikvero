<?php
require_once __DIR__ . '/../app/bootstrap.php';
use App\Core\Auth\Auth;
use App\Core\Database\Connection;

$isLoggedIn = Auth::check();
$userId = $isLoggedIn ? Auth::id() : 1;
$user = $isLoggedIn ? Auth::user() : null;

$db = Connection::getInstance();
$pref = $db->selectOne("SELECT email_notifications, push_notifications, fcm_token FROM notification_preferences WHERE user_id = ? LIMIT 1", [$userId], 'i');

$emailEnabled = $pref ? ((int)($pref['email_notifications'] ?? 1) === 1) : true;
$pushEnabled = $pref ? ((int)($pref['push_notifications'] ?? 1) === 1) : true;
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Pikvero — Notification System Lab &amp; Test Bench</title>
  <?php $favLogo = class_exists(\App\Core\Auth\Auth::class) ? \App\Core\Auth\Auth::getLogoUrl() : '/pikvero/assets/images/logo.png'; ?>
  <link rel="icon" type="image/png" href="<?= htmlspecialchars($favLogo) ?>?v=<?= time() ?>">
  <link rel="shortcut icon" type="image/png" href="<?= htmlspecialchars($favLogo) ?>?v=<?= time() ?>">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="/pikvero/assets/css/streetside-theme.css?v=3">
  <link rel="stylesheet" href="/pikvero/assets/css/toast.css">
  <style>
    body {
      background-color: var(--sand, #f4f1ea);
    }
    .test-page-container {
      max-width: 1040px;
      margin: 30px auto 100px;
      padding: 0 16px;
    }
    @media (max-width: 768px) {
      .test-page-container {
        margin-top: 75px !important;
        padding: 0 10px !important;
      }
    }
    .test-card {
      background: var(--white);
      border: 3px solid var(--ink);
      border-radius: 16px;
      padding: 22px;
      margin-bottom: 22px;
      box-shadow: 5px 5px 0 var(--ink);
    }
    .status-pill {
      font-family: 'DM Mono', monospace;
      font-weight: 800;
      font-size: 0.72rem;
      padding: 4px 10px;
      border-radius: 8px;
      border: 2px solid var(--ink);
      display: inline-flex;
      align-items: center;
      gap: 5px;
    }
    .status-pill.active {
      background: var(--lime);
      color: var(--ink);
    }
    .status-pill.inactive {
      background: var(--coral);
      color: var(--white);
    }
    .console-log {
      background: var(--ink);
      color: #7dd3fc;
      font-family: 'DM Mono', monospace;
      font-size: 0.82rem;
      line-height: 1.5;
      padding: 18px;
      border-radius: 14px;
      border: 3px solid var(--ink);
      max-height: 320px;
      overflow-y: auto;
      white-space: pre-wrap;
      box-shadow: inset 3px 3px 6px rgba(0,0,0,0.6);
    }
    .hero-stat-card {
      background: var(--cream);
      border: 2px solid var(--ink);
      border-radius: 12px;
      padding: 14px 16px;
      box-shadow: 3px 3px 0 var(--ink);
    }
    .recipient-card {
      background: #f8faf9;
      border: 2px solid var(--ink);
      border-radius: 12px;
      padding: 14px;
      transition: transform 0.15s ease;
    }
    .recipient-card:hover {
      transform: translateY(-2px);
    }
    .email-preview-frame {
      width: 100%;
      height: 480px;
      border: 3px solid var(--ink);
      border-radius: 12px;
      background: #f4f6f5;
    }
  </style>
</head>
<body>

  <div id="sidebar-container"></div>
  <div id="navbar-container"></div>

  <main class="test-page-container">
    
    <!-- PAGE HERO HEADER -->
    <div class="test-card" style="background: linear-gradient(135deg, #eafc8d 0%, #ffffff 100%); margin-bottom: 22px;">
      <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:14px;">
        <div>
          <span class="badge-streetside coral" style="font-size:0.7rem; font-family:'DM Mono', monospace; padding:3px 8px;">SYSTEM DIAGNOSTICS &amp; NOTIFICATION LAB</span>
          <h1 style="font-weight:900; font-size: clamp(1.6rem, 4vw, 2.3rem); text-transform:uppercase; margin: 6px 0 2px;">🔔 PUSH &amp; EMAIL NOTIFICATION LAB</h1>
          <p style="color:#3b4e48; font-size:0.95rem; font-weight:600; margin:0; max-width:650px;">
            Test multi-recipient Push Notifications (Web Popups + FCM) and HTML Email Notifications across all 3 platform roles: <strong>Customer</strong>, <strong>Court Owner</strong>, and <strong>System Administrator</strong>.
          </p>
        </div>

        <div style="display:flex; gap:8px;">
          <a href="/pikvero/public/customer/profile.php" class="button sand" style="padding:7px 12px; font-size:0.78rem;">
            <i class="bi bi-gear-fill"></i> Account Settings
          </a>
        </div>
      </div>

      <!-- HERO STATS STRIP -->
      <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:12px; margin-top:20px;">
        <div class="hero-stat-card">
          <div style="font-size:0.68rem; font-family:'DM Mono', monospace; font-weight:800; color:#4a5c56; text-transform:uppercase;">FCM PUSH ENGINE</div>
          <div style="font-size:1rem; font-weight:900; color:var(--ink); margin-top:2px;"><i class="bi bi-phone-fill" style="color:var(--lime);"></i> Firebase Cloud Active</div>
        </div>
        <div class="hero-stat-card">
          <div style="font-size:0.68rem; font-family:'DM Mono', monospace; font-weight:800; color:#4a5c56; text-transform:uppercase;">EMAIL DISPATCH SERVICE</div>
          <div style="font-size:1rem; font-weight:900; color:var(--ink); margin-top:2px;"><i class="bi bi-envelope-check-fill" style="color:#0284c7;"></i> HTML Multi-Recipient</div>
        </div>
        <div class="hero-stat-card">
          <div style="font-size:0.68rem; font-family:'DM Mono', monospace; font-weight:800; color:#4a5c56; text-transform:uppercase;">TARGET STAKEHOLDERS</div>
          <div style="font-size:1rem; font-weight:900; color:var(--ink); margin-top:2px;"><i class="bi bi-people-fill" style="color:var(--coral);"></i> Customer, Owner, Admin</div>
        </div>
      </div>
    </div>

    <!-- USER & SETTINGS CONTROL CARD -->
    <div class="test-card">
      <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; margin-bottom:16px; border-bottom:2px solid var(--ink); padding-bottom:10px;">
        <h3 style="margin:0; font-size:1.1rem; text-transform:uppercase;"><i class="bi bi-person-badge-fill"></i> LOGGED-IN TESTER ACCOUNT</h3>
        <span class="badge-streetside lime" style="font-size:0.72rem; padding:4px 8px;">USER ID #<?= $userId ?></span>
      </div>

      <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:14px; font-size:0.88rem; margin-bottom:18px;">
        <div>
          <span style="font-size:0.7rem; font-weight:800; font-family:'DM Mono', monospace; color:#4a5c56; display:block;">ACCOUNT EMAIL</span>
          <strong><?= htmlspecialchars($user['email'] ?? 'player@pikvero.com') ?></strong>
        </div>
        <div>
          <span style="font-size:0.7rem; font-weight:800; font-family:'DM Mono', monospace; color:#4a5c56; display:block;">FULL NAME</span>
          <strong><?= htmlspecialchars(trim(($user['first_name'] ?? 'Pikvero') . ' ' . ($user['last_name'] ?? 'User'))) ?></strong>
        </div>
        <div>
          <span style="font-size:0.7rem; font-weight:800; font-family:'DM Mono', monospace; color:#4a5c56; display:block;">EMAIL NOTIFICATION PREFERENCE</span>
          <span id="pref-email-status" class="status-pill <?= $emailEnabled ? 'active' : 'inactive' ?>">
            <?= $emailEnabled ? '<i class="bi bi-check-circle-fill"></i> ENABLED' : '<i class="bi bi-x-circle-fill"></i> DISABLED' ?>
          </span>
        </div>
        <div>
          <span style="font-size:0.7rem; font-weight:800; font-family:'DM Mono', monospace; color:#4a5c56; display:block;">PUSH NOTIFICATION PREFERENCE</span>
          <span id="pref-push-status" class="status-pill <?= $pushEnabled ? 'active' : 'inactive' ?>">
            <?= $pushEnabled ? '<i class="bi bi-check-circle-fill"></i> ENABLED' : '<i class="bi bi-x-circle-fill"></i> DISABLED' ?>
          </span>
        </div>
      </div>

      <!-- PREFERENCE TOGGLES -->
      <div style="background:#f8faf9; border:2px solid var(--ink); border-radius:12px; padding:16px;">
        <div style="font-weight:800; font-size:0.8rem; text-transform:uppercase; font-family:'DM Mono', monospace; margin-bottom:10px; color:var(--ink);">
          ⚡ SIMULATE ACCOUNT NOTIFICATION PREFERENCE TOGGLES
        </div>
        <div style="display:flex; gap:20px; flex-wrap:wrap; align-items:center; margin-bottom:14px;">
          <label style="display:flex; align-items:center; gap:8px; font-weight:800; font-size:0.88rem; cursor:pointer;">
            <input type="checkbox" id="toggle-email" <?= $emailEnabled ? 'checked' : '' ?> style="width:18px; height:18px; accent-color:var(--lime);">
            Enable Email Notifications
          </label>

          <label style="display:flex; align-items:center; gap:8px; font-weight:800; font-size:0.88rem; cursor:pointer;">
            <input type="checkbox" id="toggle-push" <?= $pushEnabled ? 'checked' : '' ?> style="width:18px; height:18px; accent-color:var(--lime);">
            Enable Push Notifications
          </label>
        </div>

        <div style="display:flex; gap:10px; flex-wrap:wrap;">
          <button type="button" onclick="updatePreferences()" class="button lime" style="padding:7px 16px; font-size:0.8rem;">
            <i class="bi bi-save-fill"></i> Save Toggles to Database
          </button>
          <button type="button" onclick="requestBrowserPush()" class="button sand" style="padding:7px 16px; font-size:0.8rem;">
            <i class="bi bi-bell-fill"></i> Enable HTML5 Web Push Permission
          </button>
        </div>
      </div>
    </div>

    <!-- STAKEHOLDER RECIPIENT VISUALIZER -->
    <div class="test-card">
      <div style="margin-bottom:14px; border-bottom:2px solid var(--ink); padding-bottom:10px;">
        <h3 style="margin:0; font-size:1.1rem; text-transform:uppercase;"><i class="bi bi-diagram-3-fill"></i> MULTI-RECIPIENT STAKEHOLDER ROUTING</h3>
      </div>

      <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap:14px;">
        <div class="recipient-card">
          <div style="display:flex; align-items:center; gap:8px; margin-bottom:6px;">
            <div style="width:30px; height:30px; border-radius:8px; background:var(--lime); border:2px solid var(--ink); display:grid; place-items:center; font-weight:900;">1</div>
            <strong style="font-size:0.95rem; text-transform:uppercase;">Customer / Player</strong>
          </div>
          <p style="font-size:0.8rem; color:#4a5c56; margin:0;">
            Receives confirmation voucher &amp; pass details. Governed by their personal account settings.
          </p>
        </div>

        <div class="recipient-card">
          <div style="display:flex; align-items:center; gap:8px; margin-bottom:6px;">
            <div style="width:30px; height:30px; border-radius:8px; background:#bae6fd; border:2px solid var(--ink); display:grid; place-items:center; font-weight:900;">2</div>
            <strong style="font-size:0.95rem; text-transform:uppercase;">Court Owner</strong>
          </div>
          <p style="font-size:0.8rem; color:#4a5c56; margin:0;">
            Automatically resolved via <code>facilities.organization_id</code>. Receives booking alerts &amp; customer schedule details.
          </p>
        </div>

        <div class="recipient-card">
          <div style="display:flex; align-items:center; gap:8px; margin-bottom:6px;">
            <div style="width:30px; height:30px; border-radius:8px; background:#ffbe0b; border:2px solid var(--ink); display:grid; place-items:center; font-weight:900;">3</div>
            <strong style="font-size:0.95rem; text-transform:uppercase;">Platform System Admin</strong>
          </div>
          <p style="font-size:0.8rem; color:#4a5c56; margin:0;">
            Resolved via <code>roles.name = 'admin'</code>. Receives real-time system alerts &amp; audit tracking.
          </p>
        </div>
      </div>
    </div>

    <!-- ACTION BUTTONS GRID -->
    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap:18px; margin-bottom:22px;">
      
      <!-- TEST COURT BOOKING -->
      <div class="test-card" style="margin-bottom:0;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; border-bottom:2px solid var(--ink); padding-bottom:8px;">
          <h4 style="margin:0; font-size:1.05rem; text-transform:uppercase;"><i class="bi bi-calendar-check-fill" style="color:var(--green);"></i> COURT RESERVATIONS</h4>
          <span class="badge-streetside lime" style="font-size:0.65rem;">BOOKINGS</span>
        </div>
        <p style="font-size:0.84rem; color:#4a5c56; margin:0 0 14px; line-height:1.45;">
          Dispatches court booking notifications to Customer, Court Owner, and Platform Admin.
        </p>
        <div style="display:flex; flex-direction:column; gap:8px;">
          <div style="display:flex; gap:6px;">
            <button type="button" onclick="runNotificationTest('email_booking')" class="button sand" style="flex:1; text-align:center; justify-content:center; padding:8px 10px; font-size:0.78rem;">
              <i class="bi bi-envelope-fill"></i> Booking Email
            </button>
            <button type="button" onclick="runNotificationTest('push_booking')" class="button coral" style="flex:1; text-align:center; justify-content:center; padding:8px 10px; font-size:0.78rem;">
              <i class="bi bi-bell-fill"></i> Booking Push
            </button>
          </div>

          <div style="display:flex; gap:6px;">
            <button type="button" onclick="runNotificationTest('booking_cancellation')" class="button sand" style="flex:1; text-align:center; justify-content:center; padding:8px 10px; font-size:0.78rem; background:#fee2e2; color:#991b1b; border-color:#991b1b;">
              <i class="bi bi-x-circle-fill"></i> Cancel Trigger
            </button>
            <button type="button" onclick="runNotificationTest('booking_refund')" class="button sand" style="flex:1; text-align:center; justify-content:center; padding:8px 10px; font-size:0.78rem; background:#e0f2fe; color:#0369a1; border-color:#0284c7;">
              <i class="bi bi-cash-stack"></i> Refund Trigger
            </button>
          </div>

          <button type="button" onclick="runNotificationTest('payment_added')" class="button lime" style="width:100%; text-align:center; justify-content:center; padding:8px 12px; font-size:0.78rem;">
            <i class="bi bi-credit-card-fill"></i> Test Payment Received Receipt
          </button>

          <div style="display:flex; gap:6px;">
            <button type="button" onclick="openEmailPreviewModal('booking')" class="button mono" style="flex:1; text-align:center; justify-content:center; padding:6px 6px; font-size:0.7rem;">
              👁️ Booking
            </button>
            <button type="button" onclick="openEmailPreviewModal('booking_cancellation')" class="button mono" style="flex:1; text-align:center; justify-content:center; padding:6px 6px; font-size:0.7rem;">
              👁️ Cancel
            </button>
            <button type="button" onclick="openEmailPreviewModal('booking_refund')" class="button mono" style="flex:1; text-align:center; justify-content:center; padding:6px 6px; font-size:0.7rem;">
              👁️ Refund
            </button>
            <button type="button" onclick="openEmailPreviewModal('payment_added')" class="button mono" style="flex:1; text-align:center; justify-content:center; padding:6px 6px; font-size:0.7rem;">
              👁️ Receipt
            </button>
          </div>
        </div>
      </div>

      <!-- TEST OPEN PLAY PASS -->
      <div class="test-card" style="margin-bottom:0;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; border-bottom:2px solid var(--ink); padding-bottom:8px;">
          <h4 style="margin:0; font-size:1.05rem; text-transform:uppercase;"><i class="bi bi-ticket-perforated-fill" style="color:#0284c7;"></i> OPEN PLAY PASSES</h4>
          <span class="badge-streetside sky" style="font-size:0.65rem;">OPEN PLAY</span>
        </div>
        <p style="font-size:0.84rem; color:#4a5c56; margin:0 0 14px; line-height:1.45;">
          Dispatches Open Play pass entry notifications to Customer, Court Owner, and Platform Admin.
        </p>
        <div style="display:flex; flex-direction:column; gap:10px;">
          <button type="button" onclick="runNotificationTest('email_openplay')" class="button sand" style="width:100%; text-align:center; justify-content:center; padding:10px 12px; font-size:0.82rem;">
            <i class="bi bi-envelope-fill"></i> Test Email Notification
          </button>
          <button type="button" onclick="runNotificationTest('push_openplay')" class="button coral" style="width:100%; text-align:center; justify-content:center; padding:10px 12px; font-size:0.82rem;">
            <i class="bi bi-bell-fill"></i> Test Push Notification
          </button>
          <button type="button" onclick="openEmailPreviewModal('openplay')" class="button mono" style="width:100%; text-align:center; justify-content:center; padding:8px 12px; font-size:0.78rem;">
            <i class="bi bi-eye-fill"></i> Preview Email Template HTML
          </button>
        </div>
      </div>

      <!-- TEST GCASH PAYOUTS -->
      <div class="test-card" style="margin-bottom:0;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; border-bottom:2px solid var(--ink); padding-bottom:8px;">
          <h4 style="margin:0; font-size:1.05rem; text-transform:uppercase;"><i class="bi bi-wallet2" style="color:var(--coral);"></i> GCASH PAYOUTS</h4>
          <span class="badge-streetside coral" style="font-size:0.65rem;">WITHDRAWALS</span>
        </div>
        <p style="font-size:0.84rem; color:#4a5c56; margin:0 0 14px; line-height:1.45;">
          Tests Court Owner payout request submission &amp; Admin approval/status updates.
        </p>
        <div style="display:flex; flex-direction:column; gap:10px;">
          <button type="button" onclick="runNotificationTest('payout_request')" class="button sand" style="width:100%; text-align:center; justify-content:center; padding:10px 12px; font-size:0.82rem;">
            <i class="bi bi-send-fill"></i> Test Payout Request Alerts
          </button>
          <button type="button" onclick="runNotificationTest('payout_update')" class="button coral" style="width:100%; text-align:center; justify-content:center; padding:10px 12px; font-size:0.82rem;">
            <i class="bi bi-check-all"></i> Test Payout Status Approval
          </button>
          <div style="display:flex; gap:6px;">
            <button type="button" onclick="openEmailPreviewModal('payout_request')" class="button mono" style="flex:1; text-align:center; justify-content:center; padding:6px 8px; font-size:0.72rem;">
              👁️ Request HTML
            </button>
            <button type="button" onclick="openEmailPreviewModal('payout_status')" class="button mono" style="flex:1; text-align:center; justify-content:center; padding:6px 8px; font-size:0.72rem;">
              👁️ Status HTML
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- FULL TEST LAUNCHER -->
    <div style="margin-bottom:22px;">
      <button type="button" onclick="runNotificationTest('all')" class="button lime" style="width:100%; padding:14px; font-size:1rem; font-weight:900; text-align:center; justify-content:center; box-shadow: 4px 4px 0 var(--ink);">
        <i class="bi bi-rocket-takeoff-fill"></i> RUN FULL MULTI-RECIPIENT DISPATCH TEST (CUSTOMER + OWNER + ADMIN)
      </button>
    </div>

    <!-- LIVE CONSOLE LOG -->
    <div class="test-card">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
        <h4 style="margin:0; font-size:0.95rem; font-family:'DM Mono', monospace; text-transform:uppercase;"><i class="bi bi-terminal-fill"></i> LIVE EXECUTION LOGS</h4>
        <button type="button" onclick="clearConsole()" class="button sand" style="padding:3px 10px; font-size:0.72rem;">Clear Console</button>
      </div>
      <div id="console-output" class="console-log">Ready. Select any test action above to execute notification triggers.</div>
    </div>

  </main>

  <!-- EMAIL TEMPLATE PREVIEW MODAL -->
  <div id="email-preview-modal" style="display:none; position:fixed; inset:0; z-index:9999; background:rgba(13,33,29,0.7); backdrop-filter:blur(6px); place-items:center; padding:16px;">
    <div class="card-streetside" style="width:min(640px, 100%); padding:20px; background:var(--cream); max-height:90vh; overflow-y:auto;">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; border-bottom:2px solid var(--ink); padding-bottom:8px;">
        <h3 style="margin:0; font-size:1.1rem; text-transform:uppercase;" id="modal-email-preview-title">EMAIL HTML TEMPLATE PREVIEW</h3>
        <button onclick="document.getElementById('email-preview-modal').style.display='none'" class="button sand" style="padding:2px 8px; font-size:0.8rem;">✕</button>
      </div>
      <iframe id="email-preview-iframe" class="email-preview-frame" src="about:blank"></iframe>
      <div style="margin-top:14px; display:flex; justify-content:flex-end;">
        <button type="button" onclick="document.getElementById('email-preview-modal').style.display='none'" class="button sand" style="padding:8px 16px; font-size:0.8rem;">Close Preview</button>
      </div>
    </div>
  </div>

  <div id="footer-container"></div>

  <script src="/pikvero/assets/js/core/toast.js"></script>
  <script src="/pikvero/assets/js/core/ajax.js"></script>
  <script src="/pikvero/assets/js/core/push-notifications.js"></script>
  <script src="/pikvero/assets/js/components/navbar.js?v=2"></script>
  <script src="/pikvero/assets/js/components/bottom-nav.js"></script>
  <script src="/pikvero/assets/js/components/sidebar.js"></script>
  <script src="/pikvero/assets/js/components/footer.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', async () => {
      await NavbarComponent.render('#navbar-container', true);
      SidebarComponent.render('dashboard', 'customer');
      FooterComponent.render('#footer-container', true);
    });

    function logMessage(msg) {
      const el = document.getElementById('console-output');
      const time = new Date().toLocaleTimeString();
      el.innerText += `\n[${time}] ${msg}`;
      el.scrollTop = el.scrollHeight;
    }

    function clearConsole() {
      document.getElementById('console-output').innerText = 'Log cleared. Ready for next test.';
    }

    async function updatePreferences() {
      const emailVal = document.getElementById('toggle-email').checked;
      const pushVal = document.getElementById('toggle-push').checked;

      try {
        logMessage(`Saving notification preferences: Email=${emailVal ? 'ENABLED' : 'DISABLED'}, Push=${pushVal ? 'ENABLED' : 'DISABLED'}...`);
        const res = await Api.post('/pikvero/api/customer/notifications.php?action=save_preferences', {
          email_notifications: emailVal,
          push_notifications: pushVal
        });

        if (res && res.success) {
          Toast.success('Preferences Saved', 'Notification toggles updated in database.');
          logMessage('✓ Notification preferences updated in MySQL database.');
          
          const ePill = document.getElementById('pref-email-status');
          const pPill = document.getElementById('pref-push-status');

          ePill.className = `status-pill ${emailVal ? 'active' : 'inactive'}`;
          ePill.innerHTML = emailVal ? '<i class="bi bi-check-circle-fill"></i> ENABLED' : '<i class="bi bi-x-circle-fill"></i> DISABLED';

          pPill.className = `status-pill ${pushVal ? 'active' : 'inactive'}`;
          pPill.innerHTML = pushVal ? '<i class="bi bi-check-circle-fill"></i> ENABLED' : '<i class="bi bi-x-circle-fill"></i> DISABLED';
        } else {
          Toast.error('Error', res.message || 'Failed to save preferences.');
          logMessage('✕ Failed to update preferences in database.');
        }
      } catch (e) {
        Toast.error('Error', e.message || 'Server error.');
        logMessage(`✕ Exception while saving preferences: ${e.message}`);
      }
    }

    function requestBrowserPush() {
      if (!('Notification' in window)) {
        Toast.error('Not Supported', 'Browser does not support HTML5 Push Notifications.');
        logMessage('✕ Browser does not support HTML5 Push Notifications.');
        return;
      }
      Notification.requestPermission().then(perm => {
        Toast.info('Browser Permission', `Push Permission: ${perm}`);
        logMessage(`✓ HTML5 Web Push Notification permission: ${perm}`);
      });
    }

    async function runNotificationTest(testType) {
      logMessage(`>>> Triggering notification test for type: '${testType}'...`);
      try {
        const res = await Api.get(`/pikvero/api/test-notifications.php?type=${testType}`);
        if (res && res.success && res.data) {
          Toast.success('Test Executed', 'Check log console for details.');
          logMessage(`✓ Server execution finished (Tester User ID #${res.data.test_user_id}):`);
          
          const results = res.data.results;
          for (const key in results) {
            const item = results[key];
            const isSuccess = item.status === 'success';
            logMessage(`   ${isSuccess ? '✓' : '•'} [${key.toUpperCase()}] -> ${item.status.toUpperCase()}: ${item.message}`);
          }

          // Trigger local PushNotifier polling check immediately
          if (typeof PushNotifier !== 'undefined' && PushNotifier.poll) {
            PushNotifier.poll();
          }
        } else {
          Toast.error('Test Failed', res.message || 'Could not run notification test.');
          logMessage(`✕ Test execution failed: ${res ? res.message : 'No response'}`);
        }
      } catch (err) {
        Toast.error('Error', err.message || 'Network error.');
        logMessage(`✕ Exception: ${err.message}`);
      }
    }

    function openEmailPreviewModal(templateType) {
      const modal = document.getElementById('email-preview-modal');
      const title = document.getElementById('modal-email-preview-title');
      const iframe = document.getElementById('email-preview-iframe');

      if (templateType === 'booking') {
        title.innerText = 'PREVIEW: COURT RESERVATION HTML EMAIL';
        iframe.srcdoc = `
          <div style="padding:20px; font-family:sans-serif;">
            <div style="background:#eafc8d; border:3px solid #0d211d; padding:20px; text-align:center; border-radius:12px; margin-bottom:16px;">
              <h2 style="margin:0;">🎾 Pikvero Court Reserved!</h2>
            </div>
            <div style="background:#ffffff; border:2px solid #0d211d; border-radius:12px; padding:20px;">
              <p>Hi <strong>Pikvero Customer</strong>,</p>
              <p>Your court reservation is confirmed! Reference: <strong>PB-TEST-8842</strong></p>
              <table width="100%" style="border-collapse:collapse; margin-top:10px;">
                <tr><td style="padding:6px; border-bottom:1px dashed #ccc;">Facility:</td><td style="text-align:right; font-weight:bold;">SmashZone Pickleball Center</td></tr>
                <tr><td style="padding:6px; border-bottom:1px dashed #ccc;">Court:</td><td style="text-align:right; font-weight:bold; color:#2563eb;">Court 1 (Indoor)</td></tr>
                <tr><td style="padding:6px; border-bottom:1px dashed #ccc;">Date & Time:</td><td style="text-align:right; font-weight:bold;">2026-08-30 (18:00 - 19:00)</td></tr>
                <tr><td style="padding:6px;">Total Amount:</td><td style="text-align:right; font-weight:bold; color:#ff5e3a;">₱350.00</td></tr>
              </table>
            </div>
          </div>
        `;
      } else if (templateType === 'booking_cancellation') {
        title.innerText = 'PREVIEW: RESERVATION CANCELED HTML EMAIL';
        iframe.srcdoc = `
          <div style="padding:20px; font-family:sans-serif;">
            <div style="background:#f87171; border:3px solid #0d211d; padding:20px; text-align:center; border-radius:12px; margin-bottom:16px; color:#fff;">
              <h2 style="margin:0;">❌ Court Reservation Canceled</h2>
            </div>
            <div style="background:#ffffff; border:2px solid #0d211d; border-radius:12px; padding:20px;">
              <p>Hi <strong>Juan Dela Cruz</strong>,</p>
              <p>Your court reservation <strong>#PB-TEST-4421</strong> has been successfully canceled.</p>
              <table width="100%" style="border-collapse:collapse; margin-top:10px;">
                <tr><td style="padding:6px; border-bottom:1px dashed #ccc;">Facility:</td><td style="text-align:right; font-weight:bold;">SmashZone Pickleball Center</td></tr>
                <tr><td style="padding:6px; border-bottom:1px dashed #ccc;">Facility Address:</td><td style="text-align:right; font-weight:bold; color:#4a5c56;">CPG Avenue, Dampas District, Tagbilaran City</td></tr>
                <tr><td style="padding:6px; border-bottom:1px dashed #ccc;">Court:</td><td style="text-align:right; font-weight:bold; color:#2563eb;">Court 1 (Indoor)</td></tr>
                <tr><td style="padding:6px; border-bottom:1px dashed #ccc;">Date & Time:</td><td style="text-align:right; font-weight:bold; color:#b91c1c;">2026-08-30 (6:00 PM – 7:00 PM)</td></tr>
                <tr><td style="padding:6px;">Reason:</td><td style="text-align:right; font-weight:bold;">Personal Schedule Conflict</td></tr>
              </table>
            </div>
          </div>
        `;
      } else if (templateType === 'booking_refund') {
        title.innerText = 'PREVIEW: REFUND CONFIRMATION HTML EMAIL';
        iframe.srcdoc = `
          <div style="padding:20px; font-family:sans-serif;">
            <div style="background:#38bdf8; border:3px solid #0d211d; padding:20px; text-align:center; border-radius:12px; margin-bottom:16px;">
              <h2 style="margin:0;">💸 Refund Processed Confirmation</h2>
            </div>
            <div style="background:#ffffff; border:2px solid #0d211d; border-radius:12px; padding:20px;">
              <p>Hi <strong>Juan Dela Cruz</strong>,</p>
              <p>A refund has been processed for your court reservation <strong>#PB-TEST-4421</strong>.</p>
              <table width="100%" style="border-collapse:collapse; margin-top:10px;">
                <tr><td style="padding:6px; border-bottom:1px dashed #ccc;">Refund Amount Issued:</td><td style="text-align:right; font-weight:bold; color:#0284c7; font-size:16px;">₱350.00</td></tr>
                <tr><td style="padding:6px; border-bottom:1px dashed #ccc;">Facility:</td><td style="text-align:right; font-weight:bold;">SmashZone Pickleball Center</td></tr>
                <tr><td style="padding:6px; border-bottom:1px dashed #ccc;">Court:</td><td style="text-align:right; font-weight:bold; color:#2563eb;">Court 1 (Indoor)</td></tr>
                <tr><td style="padding:6px; border-bottom:1px dashed #ccc;">Date & Time:</td><td style="text-align:right; font-weight:bold; color:#0284c7;">2026-08-30 (6:00 PM – 7:00 PM)</td></tr>
                <tr><td style="padding:6px;">Reason:</td><td style="text-align:right; font-weight:bold;">Rainy Weather Court Closure</td></tr>
              </table>
            </div>
          </div>
        `;
      } else if (templateType === 'payment_added') {
        title.innerText = 'PREVIEW: PAYMENT RECEIVED RECEIPT HTML EMAIL';
        iframe.srcdoc = `
          <div style="padding:20px; font-family:sans-serif;">
            <div style="background:#86efac; border:3px solid #0d211d; padding:20px; text-align:center; border-radius:12px; margin-bottom:16px;">
              <h2 style="margin:0;">🧾 Official Receipt: Payment Received</h2>
            </div>
            <div style="background:#ffffff; border:2px solid #0d211d; border-radius:12px; padding:20px;">
              <p>Hi <strong>Juan Dela Cruz</strong>,</p>
              <p>Your payment for court reservation <strong>#PB-TEST-4421</strong> has been received and confirmed!</p>
              <table width="100%" style="border-collapse:collapse; margin-top:10px;">
                <tr><td style="padding:6px; border-bottom:1px dashed #ccc;">Amount Paid:</td><td style="text-align:right; font-weight:bold; color:#16a34a; font-size:16px;">₱350.00</td></tr>
                <tr><td style="padding:6px; border-bottom:1px dashed #ccc;">Payment Method:</td><td style="text-align:right; font-weight:bold;">GCash Online</td></tr>
                <tr><td style="padding:6px; border-bottom:1px dashed #ccc;">Facility:</td><td style="text-align:right; font-weight:bold;">SmashZone Pickleball Center</td></tr>
                <tr><td style="padding:6px; border-bottom:1px dashed #ccc;">Date & Time:</td><td style="text-align:right; font-weight:bold; color:#16a34a;">2026-08-30 (6:00 PM – 7:00 PM)</td></tr>
              </table>
            </div>
          </div>
        `;
      } else if (templateType === 'payout_request') {
        title.innerText = 'PREVIEW: PAYOUT REQUEST HTML EMAIL';
        iframe.srcdoc = `
          <div style="padding:20px; font-family:sans-serif;">
            <div style="background:#ffbe0b; border:3px solid #0d211d; padding:20px; text-align:center; border-radius:12px; margin-bottom:16px;">
              <h2 style="margin:0;">💸 GCash Payout Request Submitted</h2>
            </div>
            <div style="background:#ffffff; border:2px solid #0d211d; border-radius:12px; padding:20px;">
              <p>Hi <strong>Alvin Golosino</strong>,</p>
              <p>Your GCash payout withdrawal request has been received and is pending admin review.</p>
              <table width="100%" style="border-collapse:collapse; margin-top:10px;">
                <tr><td style="padding:6px; border-bottom:1px dashed #ccc;">Payout Ref:</td><td style="text-align:right; font-weight:bold;">PO-TEST-9921</td></tr>
                <tr><td style="padding:6px; border-bottom:1px dashed #ccc;">Requested Amount:</td><td style="text-align:right; font-weight:bold; color:#059669; font-size:16px;">₱1,500.00</td></tr>
                <tr><td style="padding:6px; border-bottom:1px dashed #ccc;">GCash Name:</td><td style="text-align:right; font-weight:bold;">Alvin Golosino</td></tr>
                <tr><td style="padding:6px;">GCash Mobile:</td><td style="text-align:right; font-weight:bold; color:#2563eb;">09170001234</td></tr>
              </table>
            </div>
          </div>
        `;
      } else if (templateType === 'payout_status') {
        title.innerText = 'PREVIEW: PAYOUT STATUS UPDATE HTML EMAIL';
        iframe.srcdoc = `
          <div style="padding:20px; font-family:sans-serif;">
            <div style="background:#86efac; border:3px solid #0d211d; padding:20px; text-align:center; border-radius:12px; margin-bottom:16px;">
              <h2 style="margin:0;">💸 Payout Status Update: COMPLETED</h2>
            </div>
            <div style="background:#ffffff; border:2px solid #0d211d; border-radius:12px; padding:20px;">
              <p>Hi <strong>Alvin Golosino</strong>,</p>
              <p>The status of your GCash payout request <strong>#PO-TEST-9921</strong> has been updated to <strong>COMPLETED</strong>.</p>
              <table width="100%" style="border-collapse:collapse; margin-top:10px;">
                <tr><td style="padding:6px; border-bottom:1px dashed #ccc;">Payout Ref:</td><td style="text-align:right; font-weight:bold;">PO-TEST-9921</td></tr>
                <tr><td style="padding:6px; border-bottom:1px dashed #ccc;">Amount Transferred:</td><td style="text-align:right; font-weight:bold; color:#059669; font-size:16px;">₱1,500.00</td></tr>
                <tr><td style="padding:6px;">Admin Note:</td><td style="text-align:right; font-weight:bold; color:#2563eb;">GCash Reference #9928120392 Transferred</td></tr>
              </table>
            </div>
          </div>
        `;
      } else {
        title.innerText = 'PREVIEW: OPEN PLAY PASS HTML EMAIL';
        iframe.srcdoc = `
          <div style="padding:20px; font-family:sans-serif;">
            <div style="background:#7dd3fc; border:3px solid #0d211d; padding:20px; text-align:center; border-radius:12px; margin-bottom:16px;">
              <h2 style="margin:0;">🔥 Open Play Entry Pass</h2>
            </div>
            <div style="background:#ffffff; border:2px solid #0d211d; border-radius:12px; padding:20px;">
              <p>Hi <strong>Pikvero Player</strong>,</p>
              <p>You're registered for Open Play! Pass Reference: <strong>OP-00542</strong></p>
              <table width="100%" style="border-collapse:collapse; margin-top:10px;">
                <tr><td style="padding:6px; border-bottom:1px dashed #ccc;">Session:</td><td style="text-align:right; font-weight:bold;">Friday Night Social Doubles</td></tr>
                <tr><td style="padding:6px; border-bottom:1px dashed #ccc;">Facility:</td><td style="text-align:right; font-weight:bold;">SmashZone Pickleball Center</td></tr>
                <tr><td style="padding:6px; border-bottom:1px dashed #ccc;">Date:</td><td style="text-align:right; font-weight:bold;">2026-08-30 (6:00 PM – 9:00 PM)</td></tr>
                <tr><td style="padding:6px;">Entry Fee:</td><td style="text-align:right; font-weight:bold; color:#ff5e3a;">₱70.00</td></tr>
              </table>
            </div>
          </div>
        `;
      }

      modal.style.display = 'grid';
    }
  </script>
</body>
</html>
