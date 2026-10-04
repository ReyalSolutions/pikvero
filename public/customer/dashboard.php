<?php
require_once __DIR__ . '/../../app/bootstrap.php';
use App\Core\Auth\Auth;

if (!Auth::check()) {
    header('Location: /pikvero/public/login.php');
    exit;
}
$favLogo = Auth::getLogoUrl();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Pikvero — Player Dashboard</title>
  <link rel="icon" type="image/png" href="<?= htmlspecialchars($favLogo) ?>?v=<?= time() ?>">
  <link rel="shortcut icon" type="image/png" href="<?= htmlspecialchars($favLogo) ?>?v=<?= time() ?>">
  <link rel="apple-touch-icon" href="<?= htmlspecialchars($favLogo) ?>?v=<?= time() ?>">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="/pikvero/assets/css/streetside-theme.css">
  <link rel="stylesheet" href="/pikvero/assets/css/toast.css">
  <style>
    @media (max-width: 768px) {
      .portal-main {
        padding: 12px 10px 85px !important;
      }
      .dash-header-wrap {
        margin-bottom: 10px !important;
      }
      .dash-title {
        font-size: 1.35rem !important;
      }
      .dash-card-compact {
        padding: 10px 12px !important;
        margin-bottom: 10px !important;
        border-radius: 12px !important;
        box-shadow: 2px 2px 0 var(--ink) !important;
      }
      .dash-card-compact h3 {
        font-size: 1.05rem !important;
      }
      .dash-booking-card {
        padding: 8px 10px !important;
        margin-bottom: 8px !important;
        border-radius: 10px !important;
        box-shadow: 2px 2px 0 var(--ink) !important;
      }
    }
  </style>
</head>
<body>

  <div id="sidebar-container"></div>
  <div id="navbar-container"></div>

  <main class="portal-main">
    <div>
      <div class="dash-header-wrap" style="margin-bottom:14px;">
        <div class="eyebrow">CUSTOMER PORTAL</div>
        <h1 class="dash-title" style="font-size: clamp(1.5rem, 4vw, 2.1rem); font-weight:800; text-transform:uppercase; margin:2px 0 0;">PLAYER DASHBOARD</h1>
      </div>

      <!-- Customer Overview Card -->
      <div id="customer-profile-card" class="card-streetside sky dash-card-compact" style="margin-bottom:14px; padding:14px 16px;">
        <!-- Loaded via JS -->
      </div>

      <!-- Open Play Banner Widget -->
      <div class="card-streetside coral dash-card-compact" style="margin-bottom:14px; padding:14px 16px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
        <div>
          <div style="display:flex; align-items:center; gap:6px; margin-bottom:2px;">
            <span class="badge-streetside lime" style="font-size:0.65rem; padding:2px 6px;">SOCIAL PICKLEBALL</span>
            <span class="badge-streetside sand" style="font-size:0.65rem; padding:2px 6px;">NO PARTNER NEEDED</span>
          </div>
          <h3 style="font-size:1.1rem; font-weight:800; text-transform:uppercase; margin:0;">OPEN PLAY SESSIONS AVAILABLE</h3>
          <div style="font-size:0.78rem; color:#4a5c56; margin-top:2px;">Join drop-in social games starting at ₱70.00/head.</div>
        </div>
        <a href="/pikvero/public/customer/open-play.php" class="button lime" style="padding:7px 14px; font-size:0.78rem;">
          <i class="bi bi-dribbble"></i> Browse Sessions &raquo;
        </a>
      </div>

      <!-- Quick Action -->
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; flex-wrap:wrap; gap:8px;">
        <h3 class="mono" style="margin:0; color:var(--green); font-size:0.78rem;">RECENT RESERVATIONS</h3>
        <div style="display:flex; gap:6px;">
          <a href="/pikvero/public/customer/bookings.php" class="button sand" style="padding:5px 10px; font-size:0.72rem;">
            <i class="bi bi-list-ul"></i> View All
          </a>
          <a href="/pikvero/public/search.php" class="button coral" style="padding:5px 10px; font-size:0.72rem;">
            <i class="bi bi-plus-lg"></i> Book New Slot
          </a>
        </div>
      </div>

      <div id="my-bookings-list">
        <!-- Loaded via JS -->
      </div>
    </div>

    <div id="footer-container"></div>
  </main>

  <script src="/pikvero/assets/js/core/toast.js"></script>
  <script src="/pikvero/assets/js/core/ajax.js"></script>
  <script src="/pikvero/assets/js/core/auth.js"></script>
  <script src="/pikvero/assets/js/components/navbar.js"></script>
  <script src="/pikvero/assets/js/components/bottom-nav.js"></script>
  <script src="/pikvero/assets/js/components/sidebar.js"></script>
  <script src="/pikvero/assets/js/components/footer.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', async () => {
      await NavbarComponent.render('#navbar-container', true);
      SidebarComponent.render('dashboard', 'customer');
      FooterComponent.render('#footer-container', true);

      const userCtx = await AuthHelper.checkSession();
      if (!userCtx || !userCtx.user) {
        window.location.href = '/pikvero/public/login.php';
        return;
      }

      document.getElementById('customer-profile-card').innerHTML = `
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
          <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
            <div class="brand-mark" style="width:38px; height:38px; font-size:1.1rem; background:var(--coral); color:var(--white);">${userCtx.user.first_name.charAt(0)}</div>
            <div>
              <h2 style="font-size:1.15rem; font-weight:800; margin:0;">${userCtx.user.first_name} ${userCtx.user.last_name}</h2>
              <p style="margin:1px 0 0; font-size:0.78rem; color:#1f3b33;">${userCtx.user.email} &bull; Member since 2026</p>
            </div>
          </div>
          <button onclick="AuthHelper.logout()" class="button coral" style="padding:5px 12px; font-size:0.75rem;">
            <i class="bi bi-box-arrow-right"></i> Logout Session
          </button>
        </div>
      `;

      loadBookings();
    });

    async function loadBookings() {
      try {
        const res = await Api.get('/pikvero/api/customer/bookings.php');
        const container = document.getElementById('my-bookings-list');

        if (res.success && res.data.length > 0) {
          const totalCount = res.data.length;
          const latest5 = res.data.slice(0, 5);

          let cardsHtml = latest5.map(b => {
            const rawSt = (b.booking_status || b.status || 'confirmed').toLowerCase();
            let bBadgeCls = 'sand';
            if (rawSt === 'confirmed' || rawSt === 'completed') bBadgeCls = 'lime';
            else if (rawSt === 'pending') bBadgeCls = 'sky';
            else if (rawSt === 'cancelled') bBadgeCls = 'coral';

            const rawPaySt = (b.payment_status || 'unpaid').toLowerCase();
            let pBadgeCls = 'sand';
            let pLabel = rawPaySt.toUpperCase();
            if (rawPaySt === 'paid' || rawPaySt === 'completed') {
              pBadgeCls = 'lime';
              pLabel = 'PAID';
            } else if (rawPaySt === 'refunded') {
              pBadgeCls = 'coral';
              pLabel = 'REFUNDED';
            } else {
              pLabel = 'UNPAID';
            }

            return `
              <div class="card-streetside dash-booking-card" style="margin-bottom:8px; padding:10px 12px;">
                <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:6px; margin-bottom:4px;">
                  <span class="mono" style="font-weight:800; font-size:0.82rem; color:var(--green);">${b.booking_reference || ('REF-#' + b.id)}</span>
                  <div style="display:flex; gap:4px; align-items:center;">
                    <span class="badge-streetside ${bBadgeCls}" style="font-size:0.62rem; padding:2px 6px;">${rawSt.toUpperCase()}</span>
                    <span class="badge-streetside ${pBadgeCls}" style="font-size:0.60rem; padding:2px 6px;">${pLabel}</span>
                  </div>
                </div>
                <h4 style="font-size:0.95rem; font-weight:800; text-transform:uppercase; margin:2px 0 3px;">${b.court_name || 'Court'} <span style="font-weight:600; color:#4a5c56; font-size:0.82rem;">(${b.facility_name || 'Facility'})</span></h4>
                <div style="font-size:0.78rem; color:#3b4e48; display:flex; gap:10px; flex-wrap:wrap; margin-top:3px;">
                  <span><i class="bi bi-calendar"></i> ${b.booking_date || ''}</span>
                  <span><i class="bi bi-clock"></i> ${(b.start_time || '').substring(0,5)}-${(b.end_time || '').substring(0,5)}</span>
                  <span style="font-weight:800; color:var(--ink);"><i class="bi bi-cash"></i> ₱${parseFloat(b.total_amount || 0).toFixed(2)}</span>
                </div>
              </div>
            `;
          }).join('');

          if (totalCount > 5) {
            cardsHtml += `
              <div style="text-align:center; margin-top:14px; margin-bottom:12px;">
                <a href="/pikvero/public/customer/bookings.php" class="button sand" style="padding:7px 18px; font-size:0.78rem; font-weight:800;">
                  <i class="bi bi-list-stars"></i> View All My Reservations (${totalCount})
                </a>
              </div>
            `;
          }

          container.innerHTML = cardsHtml;
        } else {
          container.innerHTML = `
            <div class="card-streetside sand dash-card-compact" style="max-width:480px; margin:16px auto; text-align:center; padding:20px 16px;">
              <div class="brand-mark" style="width:42px; height:42px; font-size:1.3rem; margin:0 auto 10px; background:var(--lime);">
                <i class="bi bi-ticket-perforated"></i>
              </div>
              <h4 style="font-size:1.05rem; font-weight:800; text-transform:uppercase; margin:0 0 4px;">NO BOOKINGS FOUND</h4>
              <p style="font-size:0.80rem; color:#3b4e48; margin-bottom:14px; line-height:1.35;">
                You haven't reserved any court slots yet. Explore local pickleball courts and book your time slot now!
              </p>
              <a href="/pikvero/public/search.php" class="button coral" style="padding:8px 16px; font-size:0.78rem;">
                <i class="bi bi-search"></i> Book a Court Now
              </a>
            </div>
          `;
        }
      } catch (e) { console.error(e); }
    }
  </script>
</body>
</html>
