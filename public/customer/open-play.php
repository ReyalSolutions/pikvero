<?php
require_once __DIR__ . '/../../app/bootstrap.php';
use App\Core\Auth\Auth;

if (!Auth::check()) {
    header('Location: /pikvero/public/login.php');
    exit;
}

$user = Auth::user();
$paymentNotice = false;
$paymentStatus = $_GET['payment'] ?? null;
$regId = (int)($_GET['registration_id'] ?? 0);
if ($paymentStatus === 'success' && $regId > 0 && Auth::check()) {
    $db = \App\Core\Database\Connection::getInstance();
    $db->execute("UPDATE open_play_registrations SET payment_status = 'paid' WHERE id = ? AND user_id = ?", [$regId, Auth::id()], 'ii');
    $paymentNotice = true;

    $regDetails = $db->selectOne("SELECT r.*, s.title, f.name AS facility_name, s.session_date FROM open_play_registrations r JOIN open_play_sessions s ON r.session_id = s.id JOIN facilities f ON s.facility_id = f.id WHERE r.id = ? LIMIT 1", [$regId], 'i');
    if ($regDetails) {
        \App\Infrastructure\Services\PushNotificationService::sendOpenPlayConfirmation(
            Auth::id(),
            $regId,
            $regDetails['title'] ?? 'Open Play Session',
            $regDetails['facility_name'] ?? 'Facility',
            $regDetails['session_date'] ?? date('Y-m-d'),
            $regDetails['start_time'] ?? '00:00:00',
            $regDetails['end_time'] ?? '00:00:00',
            (float)($regDetails['amount_paid'] ?? 70.00),
            $regDetails['payment_method'] ?? 'online'
        );

        \App\Infrastructure\Services\EmailNotificationService::sendOpenPlayConfirmation(
            Auth::id(),
            $regId,
            $regDetails['title'] ?? 'Open Play Session',
            $regDetails['facility_name'] ?? 'Facility',
            $regDetails['session_date'] ?? date('Y-m-d'),
            $regDetails['start_time'] ?? '00:00:00',
            $regDetails['end_time'] ?? '00:00:00',
            (float)($regDetails['amount_paid'] ?? 70.00),
            $regDetails['payment_method'] ?? 'online'
        );
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Pikvero — Open Play Social Pickleball Sessions</title>
  <?php $favLogo = class_exists(\App\Core\Auth\Auth::class) ? \App\Core\Auth\Auth::getLogoUrl() : '/pikvero/assets/images/logo.png'; ?>
  <link rel="icon" type="image/png" href="<?= htmlspecialchars($favLogo) ?>?v=<?= time() ?>">
  <link rel="shortcut icon" type="image/png" href="<?= htmlspecialchars($favLogo) ?>?v=<?= time() ?>">
  <link rel="apple-touch-icon" href="<?= htmlspecialchars($favLogo) ?>?v=<?= time() ?>">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="/pikvero/assets/css/streetside-theme.css?v=<?= filemtime(__DIR__ . '/../../assets/css/streetside-theme.css') ?>">
  <link rel="stylesheet" href="/pikvero/assets/css/modal.css">
  <link rel="stylesheet" href="/pikvero/assets/css/toast.css">
  <style>
    .op-card {
      background: var(--white);
      border: 2px solid var(--ink);
      border-radius: 14px;
      box-shadow: 4px 4px 0 var(--ink);
      padding: 20px;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      transition: transform 0.15s ease;
    }
    .op-card:hover {
      transform: translateY(-2px);
    }

    /* Payment Method Selector Cards */
    .pm-card-option {
      border: 2px solid var(--ink);
      border-radius: 10px;
      padding: 10px 12px;
      background: var(--white);
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 10px;
      transition: all 0.15s ease;
      user-select: none;
    }
    .pm-card-option:hover {
      background: var(--sand);
    }
    .pm-card-option.selected {
      border: 2.5px solid var(--ink) !important;
      box-shadow: 3px 3px 0 var(--ink) !important;
    }
    .pm-card-option.selected[data-method="gcash"] {
      background: #e0f2fe !important;
    }
    .pm-card-option.selected[data-method="cash"] {
      background: #fef9c3 !important;
    }
    .pm-card-option.selected[data-method="card"] {
      background: #f0fdf4 !important;
    }
    .pm-card-option .dot-inner {
      background: transparent;
    }
    .pm-card-option.selected .dot-inner {
      background: var(--ink) !important;
    }

    .modal-overlay {
      overflow-y: auto !important;
      -webkit-overflow-scrolling: touch;
      padding: 36px 12px !important;
      align-items: flex-start !important;
    }

    .op-modal-card {
      max-height: none !important;
      overflow: visible !important;
      margin: 10px auto !important;
    }
    /* Mobile Responsive Optimizations */
    @media (max-width: 992px) {
      .op-header-actions {
        display: none !important;
      }
      .portal-main, #page-content {
        padding: 12px 10px 85px !important;
      }
      .op-card {
        padding: 10px 12px !important;
        box-shadow: 2px 2px 0 var(--ink) !important;
        border-radius: 12px !important;
      }
      .op-modal-card {
        width: 96% !important;
        max-width: 100% !important;
        max-height: none !important;
        overflow: visible !important;
        padding: 14px 12px !important;
        border-radius: 12px !important;
      }
      .op-modal-footer-btns {
        flex-direction: column-reverse !important;
        gap: 8px !important;
      }
      .op-modal-footer-btns .button {
        width: 100% !important;
        justify-content: center !important;
      }
      .op-header-wrap {
        margin-bottom: 10px !important;
        flex-direction: row !important;
        align-items: center !important;
      }
      .op-header-wrap h1 {
        font-size: 1.35rem !important;
      }
      .op-tabs-wrap {
        display: flex !important;
        flex-direction: row !important;
        gap: 6px !important;
        margin-bottom: 10px !important;
        padding-bottom: 6px !important;
      }
      .op-tab-btn {
        flex: 1 !important;
        width: auto !important;
        justify-content: center !important;
        padding: 6px 8px !important;
        font-size: 0.75rem !important;
      }
      .op-grid {
        gap: 8px !important;
        grid-template-columns: 1fr !important;
      }
      .op-card h3 {
        font-size: 1.05rem !important;
        margin-bottom: 2px !important;
      }
      .op-card-date-box {
        padding: 6px 8px !important;
        font-size: 0.78rem !important;
        margin-bottom: 8px !important;
        border-radius: 6px !important;
      }
      .op-card .button {
        padding: 6px 12px !important;
        font-size: 0.78rem !important;
      }
    }
  </style>
<link rel="stylesheet" href="/pikvero/assets/css/player-pages.css?v=2">
<meta name="theme-color" content="#003d2d">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="Pikvero">
<link rel="apple-touch-icon" href="/pikvero/assets/images/pwa/icon-180.png">
<script defer src="/pikvero/assets/js/components/pwa.js?v=20261011-shortcut"></script>
</head>
<body class="customer-portal player-open-play" style="min-height:100vh; display:flex; flex-direction:column;">

  <div id="navbar-container"></div>

  <main id="page-content" style="flex:1; width:100%; max-width:1200px; margin:0 auto; padding:110px max(4vw, 20px) 40px; box-sizing:border-box;">
    <div class="op-header-wrap" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; flex-wrap:wrap; gap:10px;">
      <div>
        <div class="eyebrow">SOCIAL PICKLEBALL</div>
        <h1 style="font-size: clamp(1.5rem, 4vw, 2.2rem); font-weight:800; text-transform:uppercase; margin:2px 0 0;">Open play</h1>
      </div>
      <div class="op-header-actions" style="display:flex; gap:8px;">
        <a href="/pikvero/public/customer/bookings.php" class="button sand" style="padding:7px 14px; font-size:0.78rem;">
          <i class="bi bi-calendar-check"></i> My bookings
        </a>
      </div>
    </div>

    <!-- TABS -->
    <div class="op-tabs-wrap" style="display:flex; gap:10px; margin-bottom:16px; border-bottom:2px solid var(--ink); padding-bottom:8px;">
      <button type="button" onclick="switchTab('available')" id="tab-btn-available" class="button lime op-tab-btn" style="padding:7px 16px; font-size:0.80rem;">
        <i class="bi bi-dribbble"></i> Available
      </button>
      <button type="button" onclick="switchTab('my-passes')" id="tab-btn-my-passes" class="button sand op-tab-btn" style="padding:7px 16px; font-size:0.80rem;">
        <i class="bi bi-ticket-perforated-fill"></i> My Passes
      </button>
    </div>

    <!-- TAB 1: AVAILABLE SESSIONS -->
    <div id="tab-available" style="display:block;">
      <div id="sessions-grid" class="op-grid" style="display:grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap:20px;">
        <!-- Loaded via JS -->
      </div>
    </div>

    <!-- TAB 2: MY PASSES -->
    <div id="tab-my-passes" style="display:none;">
      <div id="my-passes-grid" class="op-grid" style="display:grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap:20px;">
        <!-- Loaded via JS -->
      </div>
    </div>

    <div id="footer-container"></div>
  </main>

  <!-- JOIN SESSION MODAL -->
  <div class="modal-overlay" id="join-modal" style="display:none;">
    <div class="card-streetside op-modal-card" style="width:min(480px, 96%); padding:24px; background:var(--white);">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px; border-bottom:2px solid var(--ink); padding-bottom:10px;">
        <h3 style="font-size:1.15rem; font-weight:800; text-transform:uppercase; margin:0;" id="join-modal-title">JOIN OPEN PLAY SESSION</h3>
        <button onclick="closeModal('join-modal')" class="button sand" style="padding:2px 8px; font-size:0.8rem;">✕</button>
      </div>

      <form onsubmit="submitJoinSession(event)">
        <input type="hidden" id="join-session-id">

        <div style="background:var(--cream); border:2px solid var(--ink); border-radius:10px; padding:12px; margin-bottom:16px;" id="join-session-preview">
          <!-- Loaded via JS -->
        </div>

        <div style="margin-bottom:14px;">
          <label class="mono" style="display:block; margin-bottom:4px; font-size:0.75rem;">PLAYER NAME *</label>
          <input type="text" id="join-player-name" required value="<?= htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?>" style="width:100%; padding:9px 12px; border:2px solid var(--ink); border-radius:8px; font-weight:700; font-family:inherit;">
        </div>

        <div style="margin-bottom:14px;">
          <label class="mono" style="display:block; margin-bottom:4px; font-size:0.75rem;">CONTACT PHONE *</label>
          <input type="text" id="join-player-phone" required value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="09171234567" style="width:100%; padding:9px 12px; border:2px solid var(--ink); border-radius:8px; font-weight:700; font-family:inherit;">
        </div>

        <div style="margin-bottom:16px;">
          <label class="mono" style="display:block; margin-bottom:6px; font-size:0.75rem; font-weight:800;">SELECT PAYMENT METHOD *</label>
          <input type="hidden" id="join-payment-method" value="gcash">

          <div style="display:flex; flex-direction:column; gap:8px;" id="pm-card-group">
            <!-- Card 1: GCash / PayMongo -->
            <div class="pm-card-option selected" data-method="gcash" onclick="selectPaymentMethod('gcash')">
              <div class="brand-mark" style="width:36px; height:36px; font-size:1.1rem; background:#0284c7; color:#fff; border-radius:8px; flex-shrink:0;">
                <i class="bi bi-wallet2"></i>
              </div>
              <div style="flex:1;">
                <div style="font-weight:800; font-size:0.85rem; text-transform:uppercase;">PayMongo GCash / Maya</div>
                <div style="font-size:0.72rem; color:#4a5c56;">Instant online entry pass confirmation</div>
              </div>
              <div class="pm-radio-dot" style="width:18px; height:18px; border-radius:50%; border:2px solid var(--ink); display:flex; align-items:center; justify-content:center;">
                <div class="dot-inner" style="width:9px; height:9px; border-radius:50%;"></div>
              </div>
            </div>

            <!-- Card 2: Cash -->
            <div class="pm-card-option" data-method="cash" onclick="selectPaymentMethod('cash')">
              <div class="brand-mark" style="width:36px; height:36px; font-size:1.1rem; background:#ca8a04; color:#fff; border-radius:8px; flex-shrink:0;">
                <i class="bi bi-cash-stack"></i>
              </div>
              <div style="flex:1;">
                <div style="font-weight:800; font-size:0.85rem; text-transform:uppercase;">Pay at Court (Cash)</div>
                <div style="font-size:0.72rem; color:#4a5c56;">Pay directly at court arrival</div>
              </div>
              <div class="pm-radio-dot" style="width:18px; height:18px; border-radius:50%; border:2px solid var(--ink); display:flex; align-items:center; justify-content:center;">
                <div class="dot-inner" style="width:9px; height:9px; border-radius:50%;"></div>
              </div>
            </div>

            <!-- Card 3: Credit/Debit Card -->
            <div class="pm-card-option" data-method="card" onclick="selectPaymentMethod('card')">
              <div class="brand-mark" style="width:36px; height:36px; font-size:1.1rem; background:#16a34a; color:#fff; border-radius:8px; flex-shrink:0;">
                <i class="bi bi-credit-card-2-front-fill"></i>
              </div>
              <div style="flex:1;">
                <div style="font-weight:800; font-size:0.85rem; text-transform:uppercase;">Credit / Debit Card</div>
                <div style="font-size:0.72rem; color:#4a5c56;">Visa / Mastercard via PayMongo</div>
              </div>
              <div class="pm-radio-dot" style="width:18px; height:18px; border-radius:50%; border:2px solid var(--ink); display:flex; align-items:center; justify-content:center;">
                <div class="dot-inner" style="width:9px; height:9px; border-radius:50%;"></div>
              </div>
            </div>
          </div>
        </div>

        <div id="join-fee-breakdown" style="background:#f4f3eb; border:2px solid var(--ink); border-radius:10px; padding:12px; margin-bottom:18px; font-size:0.8rem;">
          <!-- Loaded via JS -->
        </div>

        <div class="op-modal-footer-btns" style="display:flex; justify-content:flex-end; gap:8px;">
          <button type="button" onclick="closeModal('join-modal')" class="button sand" style="padding:9px 16px; font-size:0.82rem;">Cancel</button>
          <button type="submit" class="button coral" id="join-submit-btn" style="padding:9px 20px; font-size:0.82rem;"><i class="bi bi-check-circle-fill"></i> Confirm &amp; Join Session</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Already Registered Modal -->
  <div id="already-registered-modal" class="modal-overlay" style="display:none;">
    <div class="card-streetside op-modal-card" style="width:min(450px, 94%); text-align:center; padding:28px 22px; background:var(--white);">
      <div class="brand-mark" style="width:56px; height:56px; font-size:1.8rem; margin:0 auto 14px; background:var(--lime); color:var(--ink);">
        <i class="bi bi-person-check-fill"></i>
      </div>
      <h3 style="font-size:1.25rem; font-weight:800; text-transform:uppercase; margin:0 0 8px; color:var(--ink);">YOU ARE ALREADY REGISTERED!</h3>
      <p style="font-size:0.88rem; color:#223d35; margin-bottom:22px; line-height:1.5;" id="already-registered-text">
        You have already joined this Open Play session. Check <strong>"My Open Play Passes"</strong> to view or print your entry pass receipt.
      </p>
      <div style="display:flex; justify-content:center; gap:10px; flex-wrap:wrap;">
        <button type="button" onclick="closeModal('already-registered-modal')" class="button sand" style="padding:9px 18px; font-size:0.85rem;">Close</button>
        <button type="button" onclick="closeModal('already-registered-modal'); switchTab('my-passes');" class="button lime" style="padding:9px 20px; font-size:0.85rem;"><i class="bi bi-ticket-detailed-fill"></i> View My Entry Pass</button>
      </div>
    </div>
  </div>

  <div id="sidebar-container"></div>
  <script src="/pikvero/assets/js/core/toast.js"></script>
  <script src="/pikvero/assets/js/core/ajax.js"></script>
  <script src="/pikvero/assets/js/core/auth.js"></script>
  <script src="/pikvero/assets/js/components/navbar.js"></script>
  <script src="/pikvero/assets/js/components/bottom-nav.js?v=<?= filemtime(__DIR__ . '/../../assets/js/components/bottom-nav.js') ?>"></script>
  <script src="/pikvero/assets/js/components/sidebar.js"></script>
  <script src="/pikvero/assets/js/components/footer.js"></script>
  <script>
    let availableSessions = [];
    let feeSettings = { platform_fee_pct: 10, paymongo_fee_pct: 2.5 };
    let currentSelectedSession = null;
    const isPaymentSuccessNotice = <?= $paymentNotice ? 'true' : 'false' ?>;

    document.addEventListener('DOMContentLoaded', async () => {
      await NavbarComponent.render('#navbar-container', false);
      SidebarComponent.render('open_play', 'customer');
      FooterComponent.render('#footer-container', false);
      await loadFeeSettings();

      if (isPaymentSuccessNotice) {
        Toast.success('Payment Confirmed!', 'Your Open Play pass payment was completed via PayMongo GCash.');
        switchTab('my-passes');
      } else {
        const urlParams = new URLSearchParams(window.location.search);
        await loadAvailableSessions();
        const autoJoinId = urlParams.get('join_session_id');
        if (autoJoinId) {
          openJoinModal(autoJoinId);
        }
      }
    });

    async function loadFeeSettings() {
      try {
        const res = await Api.get('/pikvero/api/customer/open-play.php', { action: 'fees' });
        if (res.success && res.data) {
          feeSettings = res.data;
        }
      } catch (err) { console.error(err); }
    }

    function switchTab(tab) {
      if (tab === 'available') {
        document.getElementById('tab-available').style.display = 'block';
        document.getElementById('tab-my-passes').style.display = 'none';
        document.getElementById('tab-btn-available').className = 'button lime op-tab-btn';
        document.getElementById('tab-btn-my-passes').className = 'button sand op-tab-btn';
        loadAvailableSessions();
      } else {
        document.getElementById('tab-available').style.display = 'none';
        document.getElementById('tab-my-passes').style.display = 'block';
        document.getElementById('tab-btn-available').className = 'button sand op-tab-btn';
        document.getElementById('tab-btn-my-passes').className = 'button lime op-tab-btn';
        loadMyPasses();
      }
    }

    function showOpenPlaySkeleton(grid) {
      grid.setAttribute('aria-busy', 'true');
      grid.innerHTML = '<span class="op-loading-label" role="status">Loading sessions</span>' + Array.from({length: 4}, () => '<div class="op-loading-card" aria-hidden="true"><div class="op-loading-image op-skeleton"></div><div class="op-loading-lines"><span class="op-skeleton"></span><span class="op-skeleton"></span><span class="op-skeleton"></span><span class="op-skeleton"></span></div></div>').join('');
    }

    async function loadAvailableSessions() {
      const grid = document.getElementById('sessions-grid');
      showOpenPlaySkeleton(grid);

      try {
        const res = await Api.get('/pikvero/api/customer/open-play.php', { action: 'list' });
        if (!res.success || !res.data) throw new Error('Unable to load sessions');
        if (res.success && res.data) {
          availableSessions = res.data;
          if (matchMedia('(max-width:768px)').matches && window.renderMobileOpenPlay) {
            window.renderMobileOpenPlay(availableSessions);
            return;
          }
          if (availableSessions.length === 0) {
            grid.innerHTML = `
              <div class="card-streetside sand" style="grid-column:1/-1; padding:30px; text-align:center;">
                <p style="margin:0; font-size:0.95rem; font-weight:800;">No Open Play sessions currently scheduled.</p>
                <div style="font-size:0.8rem; color:#555; margin-top:4px;">Check back soon for new social pickleball games!</div>
              </div>
            `;
            return;
          }

          grid.innerHTML = availableSessions.map(s => {
            const slotsLeft = s.max_players - s.registered_players;
            const isFull = slotsLeft <= 0 || s.status === 'full';
            const isRegistered = parseInt(s.is_user_registered || 0) > 0;

            let actionBtnHtml = `<button type="button" onclick="openJoinModal(${s.id})" ${isFull ? 'disabled' : ''} class="button ${isFull ? 'sand' : 'coral'}" style="padding:6px 14px; font-size:0.78rem;">
                    ${isFull ? 'FULL' : '<i class="bi bi-ticket-detailed-fill"></i> Join Game'}
                  </button>`;

            if (isRegistered) {
              actionBtnHtml = `<button type="button" onclick="showAlreadyRegisteredModal('${escapeHtml(s.title)}')" class="button sand" style="padding:6px 12px; font-size:0.78rem; background:#e0f2fe; color:#0369a1; border:1.5px solid #0284c7;">
                    <i class="bi bi-check-circle-fill"></i> JOINED
                  </button>`;
            }

            return `
              <div class="op-card">
                <div>
                  <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                    <span class="badge-streetside coral" style="font-size:0.62rem; padding:2px 6px;">OPEN PLAY</span>
                    <span class="badge-streetside ${isRegistered ? 'sky' : (isFull ? 'coral' : 'lime')}" style="font-size:0.62rem; padding:2px 6px;">${isRegistered ? 'REGISTERED' : (isFull ? 'FULL' : slotsLeft + ' SLOTS LEFT')}</span>
                  </div>

                  <h3 style="font-size:1.1rem; font-weight:800; text-transform:uppercase; margin:0 0 4px;">${escapeHtml(s.title)}</h3>
                  <div style="font-size:0.78rem; color:#4a5c56; margin-bottom:8px;">
                    <i class="bi bi-building"></i> ${escapeHtml(s.facility_name)} (${escapeHtml(s.city || 'Bohol')})
                  </div>

                  <div style="font-size:0.78rem; margin-bottom:8px;"><i class="bi bi-grid"></i> Court: ${escapeHtml(s.court_name || 'Not assigned')}</div>
                  <div class="op-card-date-box" style="background:var(--sand); border:1.5px solid var(--ink); border-radius:8px; padding:6px 10px; font-size:0.78rem; margin-bottom:10px; font-weight:700;">
                    <i class="bi bi-calendar-event"></i> ${s.session_date} &bull; <i class="bi bi-clock"></i> ${(s.start_time||'').substring(0,5)}-${(s.end_time||'').substring(0,5)}
                  </div>
                </div>

                <div style="display:flex; justify-content:space-between; align-items:center; border-top:1px dashed var(--ink); padding-top:8px;">
                  <div>
                    <span style="font-size:0.62rem; font-weight:800; font-family:'DM Mono', monospace; display:block; color:#4a5c56;">ENTRY FEE</span>
                    <span style="font-size:1.15rem; font-weight:900; color:var(--coral);">₱${parseFloat(s.fee_per_player||70).toFixed(2)}</span>
                  </div>

                  ${actionBtnHtml}
                </div>
              </div>
            `;
          }).join('');
        }
      } catch (err) {
        console.error(err);
        grid.innerHTML = '<div class="open-play-mobile-empty" role="status">Unable to load sessions. <button type="button" onclick="loadAvailableSessions()">Retry</button></div>';
      } finally { grid.setAttribute('aria-busy', 'false'); }
    }

    async function loadMyPasses() {
      const grid = document.getElementById('my-passes-grid');
      showOpenPlaySkeleton(grid);

      try {
        const res = await Api.get('/pikvero/api/customer/open-play.php', { action: 'my_passes' });
        if (!res.success || !res.data) throw new Error('Unable to load passes');
        if (res.success && res.data) {
          const passes = res.data;
          if (passes.length === 0) {
            grid.innerHTML = `
              <div class="card-streetside sand" style="grid-column:1/-1; padding:30px; text-align:center;">
                <p style="margin:0; font-size:0.95rem; font-weight:800;">You haven't joined any Open Play sessions yet.</p>
              </div>
            `;
            return;
          }

          grid.innerHTML = passes.map(p => {
            const expired = p.is_expired === true;
            const pm = (p.payment_method || 'cash').toLowerCase();
            let pmBadge = '<span class="badge-streetside sand" style="font-size:0.60rem; padding:2px 6px; background:#fef3c7; color:#92400e; border:1px solid #f59e0b;"><i class="bi bi-cash-stack"></i> CASH AT COURT</span>';
            if (pm === 'gcash' || pm === 'paymaya' || pm === 'paymongo') {
              pmBadge = '<span class="badge-streetside sky" style="font-size:0.60rem; padding:2px 6px; background:#e0f2fe; color:#0369a1; border:1px solid #0284c7;"><i class="bi bi-wallet2"></i> GCASH / PAYMONGO</span>';
            } else if (pm === 'card') {
              pmBadge = '<span class="badge-streetside lime" style="font-size:0.60rem; padding:2px 6px; background:#f0fdf4; color:#15803d; border:1px solid #16a34a;"><i class="bi bi-credit-card-fill"></i> CARD</span>';
            }

            return `
            <div class="op-card" style="background:#fff3f0; padding:10px 12px;">
              <div>
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px; flex-wrap:wrap; gap:4px;">
                  <span class="badge-streetside lime" style="font-size:0.62rem; padding:2px 6px;">PASS #${p.id}</span>
                  <div style="display:flex; gap:4px; align-items:center;">
                    ${pmBadge}
                    <span class="badge-streetside ${expired ? 'op-pass-expired' : 'green'}" style="font-size:0.60rem; padding:2px 6px;">${expired ? 'EXPIRED' : escapeHtml((p.payment_status === 'paid' ? 'CONFIRMED' : p.payment_status || 'pending').toUpperCase())}</span>
                  </div>
                </div>

                <h3 style="font-size:1.05rem; font-weight:800; text-transform:uppercase; margin:0 0 3px;">${p.session_title}</h3>
                <div style="font-size:0.78rem; color:#4a5c56; margin-bottom:6px;"><i class="bi bi-building"></i> ${p.facility_name} &bull; <i class="bi bi-calendar"></i> ${p.session_date}</div>
                <div style="font-size:0.78rem; margin-bottom:6px;"><i class="bi bi-grid"></i> Court: ${escapeHtml(p.court_name || 'Not assigned')}</div>
              </div>

              <div style="display:flex; justify-content:space-between; align-items:center; border-top:1px dashed var(--ink); padding-top:6px; margin-top:4px;">
                <span style="font-size:0.8rem; font-weight:800;">Paid: ₱${parseFloat(p.amount_paid||0).toFixed(2)}</span>
                <button type="button" class="op-pass-open" onclick="showOpenPlayPass({id:${Number(p.id)}})"><i class="bi bi-qr-code"></i> View My Pass</button>
                <a href="/pikvero/public/open-play-receipt.php?registration_id=${p.id}" target="_blank" class="button lime" style="padding:5px 12px; font-size:0.75rem; text-decoration:none;">
                  <i class="bi bi-printer-fill"></i> Receipt
                </a>
              </div>
            </div>
            `;
          }).join('');
        }
      } catch (err) {
        console.error(err);
        grid.innerHTML = '<div role="status">Unable to load passes. <button type="button" onclick="loadMyPasses()">Retry</button></div>';
      } finally { grid.setAttribute('aria-busy', 'false'); }
    }

    function openModal(id) {
      const el = document.getElementById(id);
      if (el) { el.classList.add('active'); el.style.display = 'flex'; }
    }

    function closeModal(id) {
      const el = document.getElementById(id);
      if (el) { el.classList.remove('active'); el.style.display = 'none'; }
    }

    function showAlreadyRegisteredModal(sessionTitle) {
      document.getElementById('already-registered-text').innerHTML = `You have already joined <strong>"${escapeHtml(sessionTitle)}"</strong>. Check <strong>"My Open Play Passes"</strong> to view or print your entry pass receipt.`;
      openModal('already-registered-modal');
    }

    function selectPaymentMethod(method) {
      document.getElementById('join-payment-method').value = method;
      document.querySelectorAll('.pm-card-option').forEach(card => {
        if (card.dataset.method === method) {
          card.classList.add('selected');
        } else {
          card.classList.remove('selected');
        }
      });
      updateFeeBreakdown();
    }

    function openJoinModal(sessionId) {
      currentSelectedSession = availableSessions.find(s => s.id == sessionId);
      if (!currentSelectedSession) return;
      if (window.showOpenPlayJoin) { window.showOpenPlayJoin(currentSelectedSession); return; }

      if (parseInt(currentSelectedSession.is_user_registered || 0) > 0) {
        showAlreadyRegisteredModal(currentSelectedSession.title);
        return;
      }

      document.getElementById('join-session-id').value = currentSelectedSession.id;
      document.getElementById('join-session-preview').innerHTML = `
        <div style="font-weight:800; font-size:0.95rem; text-transform:uppercase;">${escapeHtml(currentSelectedSession.title)}</div>
        <div style="font-size:0.78rem; color:#4a5c56;">${escapeHtml(currentSelectedSession.facility_name)} &bull; ${currentSelectedSession.session_date} (${(currentSelectedSession.start_time||'').substring(0,5)} - ${(currentSelectedSession.end_time||'').substring(0,5)})</div>
        <div style="font-size:0.78rem; margin-top:4px;">Court: ${escapeHtml(currentSelectedSession.court_name || 'Not assigned')}</div>
      `;

      selectPaymentMethod('gcash');
      openModal('join-modal');
    }

    function updateFeeBreakdown() {
      if (!currentSelectedSession) return;
      const baseFee = parseFloat(currentSelectedSession.fee_per_player || 70.00);
      const pmMethod = document.getElementById('join-payment-method').value;
      const bd = document.getElementById('join-fee-breakdown');
      const submitBtn = document.getElementById('join-submit-btn');

      if (pmMethod === 'cash') {
        bd.innerHTML = `
          <div style="display:flex; justify-content:space-between; font-weight:800; font-size:0.95rem;">
            <span>TOTAL DUE AT COURT:</span>
            <span style="color:var(--coral);">₱${baseFee.toFixed(2)}</span>
          </div>
          <div style="font-size:0.72rem; color:#555; margin-top:2px;">Pay directly in cash at court arrival.</div>
        `;
        submitBtn.innerHTML = `<i class="bi bi-check-circle-fill"></i> Confirm &amp; Join Session`;
      } else {
        const platformFee = Math.round(baseFee * (feeSettings.platform_fee_pct / 100) * 100) / 100;
        const gatewayFee = Math.round(baseFee * (feeSettings.paymongo_fee_pct / 100) * 100) / 100;
        const total = baseFee + platformFee + gatewayFee;

        bd.innerHTML = `
          <div style="display:flex; justify-content:space-between; margin-bottom:4px;">
            <span>Entry Pass Base Fee:</span>
            <strong>₱${baseFee.toFixed(2)}</strong>
          </div>
          <div style="display:flex; justify-content:space-between; margin-bottom:4px;">
            <span>Platform Service Fee (${feeSettings.platform_fee_pct}%):</span>
            <strong>₱${platformFee.toFixed(2)}</strong>
          </div>
          <div style="display:flex; justify-content:space-between; margin-bottom:6px;">
            <span>PayMongo Gateway Fee (${feeSettings.paymongo_fee_pct}%):</span>
            <strong>₱${gatewayFee.toFixed(2)}</strong>
          </div>
          <div style="border-top:1.5px dashed var(--ink); padding-top:6px; display:flex; justify-content:space-between; font-weight:900; font-size:0.95rem;">
            <span>PAYMONGO GRAND TOTAL:</span>
            <span style="color:var(--coral);">₱${total.toFixed(2)}</span>
          </div>
        `;
        submitBtn.innerHTML = `<i class="bi bi-wallet2"></i> Proceed to PayMongo GCash (₱${total.toFixed(2)})`;
      }
    }

    async function submitJoinSession(e) {
      e.preventDefault();
      const payload = {
        session_id: document.getElementById('join-session-id').value,
        player_name: document.getElementById('join-player-name').value.trim(),
        player_phone: document.getElementById('join-player-phone').value.trim(),
        payment_method: document.getElementById('join-payment-method').value
      };

      try {
        const res = await Api.post('/pikvero/api/customer/open-play.php?action=join', payload);
        if (res.success) {
          if (res.data && res.data.checkout_url) {
            Toast.success('Redirecting...', 'Forwarding to PayMongo GCash payment page...');
            window.location.href = res.data.checkout_url;
          } else {
            Toast.success('Session Joined!', 'Your Open Play pass has been generated.');
            closeModal('join-modal');
            switchTab('my-passes');
          }
        } else if (res.data && res.data.already_registered) {
          closeModal('join-modal');
          showAlreadyRegisteredModal(currentSelectedSession ? currentSelectedSession.title : 'this session');
        } else {
          Toast.error('Reservation Error', res.message || 'Could not join session.');
        }
      } catch (err) {
        console.error(err);
        closeModal('join-modal');
        showAlreadyRegisteredModal(currentSelectedSession ? currentSelectedSession.title : 'this session');
      }
    }

    function escapeHtml(str) {
      if (!str) return '';
      return String(str).replace(/[&<>"']/g, m => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
      })[m]);
    }
  </script>
  <link rel="stylesheet" href="/pikvero/assets/css/open-play-mobile.css?v=<?= filemtime(__DIR__.'/../../assets/css/open-play-mobile.css') ?>">
  <script src="/pikvero/assets/js/components/open-play-mobile.js?v=<?= filemtime(__DIR__.'/../../assets/js/components/open-play-mobile.js') ?>"></script>
  <script src="/pikvero/assets/js/components/open-play-details.js?v=<?= filemtime(__DIR__.'/../../assets/js/components/open-play-details.js') ?>"></script>
  <script>window.openPlayPlayer = <?= json_encode(['name'=>trim(($user['first_name']??'').' '.($user['last_name']??'')), 'email'=>$user['email']??''], JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;</script>
  <script src="/pikvero/assets/js/components/open-play-join.js?v=<?= filemtime(__DIR__.'/../../assets/js/components/open-play-join.js') ?>"></script>
  <script src="/pikvero/assets/js/components/open-play-payment.js?v=<?= filemtime(__DIR__.'/../../assets/js/components/open-play-payment.js') ?>"></script>
  <script src="/pikvero/assets/js/vendor/qrcode.min.js"></script>
  <script src="/pikvero/assets/js/components/open-play-pass.js?v=<?= filemtime(__DIR__.'/../../assets/js/components/open-play-pass.js') ?>"></script>
</body>
</html>
