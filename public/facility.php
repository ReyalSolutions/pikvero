<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Pikvero — Facility Details &amp; Court Booking</title>
  <meta name="description" content="View facility details, select a pickleball court, pick an available time slot, and make an instant reservation on Pikvero.">
  <?php $favLogo = class_exists(\App\Core\Auth\Auth::class) ? \App\Core\Auth\Auth::getLogoUrl() : '/pikvero/assets/images/logo.png'; ?>
  <link rel="icon" type="image/png" href="<?= htmlspecialchars($favLogo) ?>?v=<?= time() ?>">
  <link rel="shortcut icon" type="image/png" href="<?= htmlspecialchars($favLogo) ?>?v=<?= time() ?>">
  <link rel="apple-touch-icon" href="<?= htmlspecialchars($favLogo) ?>?v=<?= time() ?>">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="/pikvero/assets/css/streetside-theme.css?v=3">
  <link rel="stylesheet" href="/pikvero/assets/css/toast.css">
  <link rel="stylesheet" href="/pikvero/assets/css/modal.css?v=3">
  <style>
    @keyframes shimmer {
      0%   { background-position: -600px 0; }
      100% { background-position: 600px 0; }
    }
    .skeleton {
      background: linear-gradient(90deg, #e8e8e8 25%, #f5f5f5 50%, #e8e8e8 75%);
      background-size: 600px 100%;
      animation: shimmer 1.4s infinite linear;
      border-radius: 12px;
    }
    .skeleton-header  { height: 100px; }
    .skeleton-court   { height: 90px; }
    .skeleton-slot    { height: 70px; }
    .skeleton-info    { height: 160px; }

    .court-item {
      cursor: pointer;
      padding: 16px;
      transition: all 0.2s ease;
      position: relative;
    }
    .court-item:hover {
      background: var(--cream) !important;
      transform: translateY(-2px);
    }
    .court-item.selected {
      background: #eafc8d !important;
      border: 3px solid var(--ink) !important;
      box-shadow: 6px 6px 0 var(--ink) !important;
      transform: translateY(-2px);
    }
    .court-item.selected .court-selected-indicator {
      display: flex !important;
    }

    .slot-btn {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 3px;
      padding: 10px 6px;
      font-size: 0.78rem;
      line-height: 1.3;
      transition: all 0.15s ease;
    }
    .slot-btn.selected-slot {
      background: var(--ink) !important;
      color: var(--lime) !important;
      border: 3px solid var(--ink) !important;
      box-shadow: 4px 4px 0 var(--lime) !important;
      font-weight: 900 !important;
      transform: translateY(-3px) scale(1.03);
      z-index: 5;
    }
    .slot-btn.selected-slot strong {
      color: var(--lime) !important;
    }
    .slot-btn.booked {
      opacity: 0.45;
      cursor: not-allowed;
      text-decoration: line-through;
    }

    .amenity-tag {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      padding: 4px 9px;
      border: 1.5px solid var(--ink);
      border-radius: 8px;
      font-size: 0.78rem;
      font-weight: 700;
    }

    @media (max-width: 768px) {
      .facility-page-container {
        padding: 12px 10px 85px !important;
        max-width: 100vw !important;
        overflow-x: hidden !important;
        box-sizing: border-box !important;
      }
      body:not(.customer-portal) .facility-page-container {
        margin-top: 75px !important;
        padding-top: 10px !important;
        padding-bottom: 40px !important;
      }

      /* Compact Facility Header Card */
      #facility-header-card {
        padding: 12px 14px !important;
        margin-bottom: 12px !important;
        border-radius: 12px !important;
        box-shadow: 2px 2px 0 var(--ink) !important;
      }
      #facility-header-card h1 {
        font-size: 1.35rem !important;
      }
      #facility-header-card p {
        font-size: 0.78rem !important;
      }
      #facility-header-card .button {
        padding: 6px 14px !important;
        font-size: 0.76rem !important;
        width: 100% !important;
        justify-content: center !important;
      }

      /* Compact Gallery Strip */
      #facility-gallery {
        grid-template-columns: repeat(2, 1fr) !important;
        gap: 6px !important;
        margin-bottom: 14px !important;
      }
      #facility-gallery .card-streetside {
        padding: 8px 10px !important;
        border-radius: 8px !important;
      }
      #facility-gallery i {
        font-size: 1.2rem !important;
      }
      #facility-gallery .mono {
        font-size: 0.65rem !important;
        margin-top: 2px !important;
      }

      /* Compact Courts & Time Slots Grid */
      .fac-main-grid {
        grid-template-columns: 1fr !important;
        gap: 14px !important;
        margin-bottom: 16px !important;
      }
      .fac-main-grid h3 {
        font-size: 0.92rem !important;
        margin-bottom: 8px !important;
      }

      .court-item {
        padding: 10px 12px !important;
        border-radius: 10px !important;
        box-shadow: 2px 2px 0 var(--ink) !important;
      }
      .court-item h4 {
        font-size: 0.95rem !important;
      }

      /* Time Slots Header & Date Picker */
      .time-slots-header-wrap {
        flex-direction: column !important;
        align-items: flex-start !important;
        gap: 8px !important;
      }
      #selected-court-title-display {
        font-size: 0.85rem !important;
        line-height: 1.3 !important;
        word-break: break-word !important;
      }
      #selected-court-title-display .badge-streetside {
        font-size: 0.68rem !important;
        padding: 3px 8px !important;
        margin-left: 0 !important;
        margin-top: 4px !important;
        display: inline-flex !important;
      }
      #booking-date-picker {
        width: 100% !important;
        box-sizing: border-box !important;
        padding: 6px 10px !important;
        font-size: 0.78rem !important;
      }

      /* Ultra-responsive Time Slots Grid (No Overflow) */
      #time-slots-grid {
        display: grid !important;
        grid-template-columns: repeat(2, 1fr) !important;
        gap: 8px !important;
        width: 100% !important;
        box-sizing: border-box !important;
      }

      .slot-btn {
        width: 100% !important;
        box-sizing: border-box !important;
        padding: 8px 4px !important;
        min-height: 52px !important;
        font-size: 0.72rem !important;
        border-radius: 8px !important;
        text-align: center !important;
        overflow: hidden !important;
        justify-content: center !important;
      }
      .slot-btn strong {
        font-size: 0.74rem !important;
        white-space: nowrap !important;
        display: block !important;
      }
      .slot-btn span {
        font-size: 0.68rem !important;
        white-space: nowrap !important;
        display: block !important;
      }

      /* Multi slot summary bar */
      #multi-slot-summary-bar {
        width: 100% !important;
        box-sizing: border-box !important;
        padding: 10px 12px !important;
        margin-top: 10px !important;
        border-radius: 10px !important;
        box-shadow: 2px 2px 0 var(--lime) !important;
      }
      #multi-slot-summary-bar > div {
        flex-direction: column !important;
        align-items: stretch !important;
        gap: 8px !important;
      }
      #multi-slot-summary-bar button {
        flex: 1 !important;
        padding: 7px 12px !important;
        font-size: 0.76rem !important;
        justify-content: center !important;
      }

      /* Info Cards (About, Amenities, Operating Hours) */
      .fac-info-grid {
        grid-template-columns: 1fr !important;
        gap: 12px !important;
      }
      .fac-info-card {
        padding: 12px 14px !important;
        border-radius: 10px !important;
        box-shadow: 2px 2px 0 var(--ink) !important;
      }
      .fac-info-card h4 {
        font-size: 0.95rem !important;
        margin-bottom: 6px !important;
      }

      /* Pro Shop & Marketplace Grid Mobile Enhancements */
      #facility-marketplace-wrap {
        margin-top: 16px !important;
        margin-bottom: 16px !important;
      }
      .marketplace-header-wrap {
        flex-direction: column !important;
        align-items: flex-start !important;
        gap: 6px !important;
      }
      .marketplace-header-wrap h3 {
        font-size: 1.1rem !important;
      }
      #facility-products-grid {
        display: grid !important;
        grid-template-columns: repeat(2, 1fr) !important;
        gap: 8px !important;
        width: 100% !important;
        box-sizing: border-box !important;
      }
      .product-card-compact {
        padding: 10px 10px !important;
        border-radius: 10px !important;
        box-shadow: 2px 2px 0 var(--ink) !important;
      }
      .product-card-compact h4 {
        font-size: 0.88rem !important;
        margin-bottom: 3px !important;
        line-height: 1.2 !important;
      }
      .product-card-compact p {
        font-size: 0.72rem !important;
        margin-bottom: 6px !important;
      }
      .product-card-footer {
        flex-direction: column !important;
        align-items: stretch !important;
        gap: 4px !important;
      }
      .product-card-footer button {
        width: 100% !important;
        padding: 5px 8px !important;
        font-size: 0.70rem !important;
        justify-content: center !important;
      }

      /* Empty State Mobile Tweaks */
      .facility-empty-card {
        padding: 24px 16px !important;
        border-width: 2px !important;
        box-shadow: 4px 4px 0 var(--ink) !important;
      }
      .facility-empty-icon-box {
        width: 62px !important;
        height: 62px !important;
        margin-bottom: 14px !important;
      }
      .facility-empty-icon-box i {
        font-size: 1.8rem !important;
      }
      .facility-empty-title {
        font-size: 1.15rem !important;
      }
      .facility-empty-desc {
        font-size: 0.84rem !important;
        margin-bottom: 16px !important;
      }
      .facility-empty-pills {
        gap: 6px !important;
        margin-bottom: 18px !important;
      }
      .facility-empty-actions {
        flex-direction: column !important;
        width: 100% !important;
        gap: 8px !important;
      }
      .facility-empty-actions .button {
        width: 100% !important;
        justify-content: center !important;
      }
      .facility-empty-host-notice {
        flex-direction: column !important;
        text-align: center !important;
        justify-content: center !important;
        width: 100% !important;
        box-sizing: border-box !important;
      }
    }

    /* Empty State Card (Streetside Neo-Brutalist) */
    .facility-empty-card {
      background: var(--white);
      border: 3px solid var(--ink);
      border-radius: 16px;
      box-shadow: 6px 6px 0 var(--ink);
      padding: 38px 28px;
      text-align: center;
      position: relative;
      overflow: hidden;
      margin-bottom: 8px;
    }
    .facility-empty-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 6px;
      background: repeating-linear-gradient(
        -45deg,
        var(--coral),
        var(--coral) 12px,
        var(--ink) 12px,
        var(--ink) 24px
      );
    }
    .facility-empty-icon-box {
      width: 80px;
      height: 80px;
      border-radius: 20px;
      background: #ffeae6;
      border: 2.5px solid var(--ink);
      box-shadow: 4px 4px 0 var(--coral);
      display: inline-flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 18px;
    }
    .facility-empty-icon-box i {
      font-size: 2.4rem;
      color: var(--coral);
    }
    .facility-empty-title {
      font-family: 'DM Sans', sans-serif;
      font-size: clamp(1.25rem, 3.2vw, 1.75rem);
      font-weight: 900;
      text-transform: uppercase;
      letter-spacing: 0.02em;
      color: var(--ink);
      margin: 0 0 10px;
    }
    .facility-empty-desc {
      font-size: 0.95rem;
      line-height: 1.6;
      color: #3f514b;
      max-width: 580px;
      margin: 0 auto 22px;
    }
    .facility-empty-pills {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      gap: 10px;
      margin-bottom: 26px;
    }
    .facility-empty-actions {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      gap: 12px;
      margin-bottom: 24px;
    }
    .facility-empty-actions .button {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      font-weight: 800;
      text-decoration: none;
      transition: all 0.15s ease;
    }
    .facility-empty-host-notice {
      background: var(--cream);
      border: 1.5px dashed var(--ink);
      border-radius: 12px;
      padding: 12px 18px;
      max-width: 620px;
      margin: 0 auto;
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 10px;
      text-align: left;
      font-size: 0.8rem;
    }
  </style>
<link rel="stylesheet" href="/pikvero/assets/css/facility-mobile.css?v=<?= filemtime(__DIR__.'/../assets/css/facility-mobile.css') ?>">
<meta name="theme-color" content="#003d2d">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="Pikvero">
<link rel="apple-touch-icon" href="/pikvero/assets/images/pwa/icon-180.png">
<script defer src="/pikvero/assets/js/components/pwa.js?v=20261011-shortcut"></script>
</head>
<body class="facility-mobile">

  <?php require_once __DIR__ . '/../includes/header.php'; ?>

  <div class="facility-page-container" style="padding:110px max(4vw, 20px) 40px;">

    <header class="facility-mobile-bar"><button type="button" id="facility-view-back" aria-label="Back"><i class="bi bi-chevron-left"></i></button><strong id="facility-view-title">Facility details</strong><button type="button" id="facility-favorite-btn" aria-label="Save facility"><i class="bi bi-heart"></i></button></header>
    <!-- Facility Header Banner -->
    <div id="facility-header-card" class="card-streetside sky" style="margin-bottom:24px; padding:24px;">
      <div class="skeleton skeleton-header"></div>
    </div>

    <nav class="facility-detail-tabs" aria-label="Facility sections"><button data-fac-section="overview" class="active">Overview</button><button data-fac-section="courts">Courts</button><button data-fac-section="reviews">Reviews</button><button data-fac-section="about">About</button></nav>
    <!-- Facility Amenities Gallery Strip -->
    <div id="facility-gallery" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:12px; margin-bottom:28px;">
      <!-- Loaded from DB amenities -->
      <div class="skeleton skeleton-info" style="height:80px;"></div>
      <div class="skeleton skeleton-info" style="height:80px;"></div>
      <div class="skeleton skeleton-info" style="height:80px;"></div>
    </div>

    <!-- Courts & Availability Grid -->
    <div class="fac-main-grid" id="fac-courts-main-grid" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(300px, 1fr)); gap:24px; margin-bottom:32px;">

      <!-- Full-Width Empty State Card (Visible when 0 courts) -->
      <div id="courts-empty-state-wrap" class="empty-state-wrap" style="display:none; grid-column:1 / -1; width:100%;"></div>

      <!-- Left: Court Selection -->
      <div id="court-selection-col">
        <h3 class="mono" style="margin-bottom:12px; color:var(--green);">
          <i class="bi bi-layers-fill"></i> SELECT A COURT
        </h3>
        <div id="courts-list" style="display:flex; flex-direction:column; gap:12px;">
          <div class="skeleton skeleton-court"></div>
          <div class="skeleton skeleton-court"></div>
          <div class="skeleton skeleton-court"></div>
        </div>
      </div>

      <!-- Right: Time Slots Picker -->
      <div id="time-slots-col">
        <div class="time-slots-header-wrap" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px; flex-wrap:wrap; gap:10px;">
          <h3 class="mono" id="selected-court-title-display" style="margin:0; color:var(--green);">
            <i class="bi bi-clock-history"></i> AVAILABLE TIME SLOTS
          </h3>
          <input type="date" id="booking-date-picker"
            style="padding:7px 10px; border:2px solid var(--ink); border-radius:10px; font-weight:700; font-family:inherit;">
        </div>
        <div id="time-slots-grid" style="display:grid; grid-template-columns:repeat(auto-fill, minmax(140px, 1fr)); gap:12px;">
          <div class="skeleton skeleton-slot"></div>
          <div class="skeleton skeleton-slot"></div>
          <div class="skeleton skeleton-slot"></div>
          <div class="skeleton skeleton-slot"></div>
        </div>

        <!-- MULTI-SLOT SELECTION SUMMARY BAR -->
        <div id="multi-slot-summary-bar" style="display:none; margin-top:16px; padding:16px 20px; background:var(--ink); color:var(--white); border:2px solid var(--ink); border-radius:14px; box-shadow:4px 4px 0 var(--lime); transition:all 0.25s ease;">
          <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
            <div>
              <div style="font-family:'DM Mono', monospace; font-size:0.78rem; font-weight:800; color:var(--lime); text-transform:uppercase;">
                <i class="bi bi-clock-fill"></i> <span id="summary-hours-count">1 Hour</span> Selected
              </div>
              <div style="font-size:1.15rem; font-weight:900; margin-top:2px;">
                Total: <span id="summary-total-price" style="color:var(--lime);">₱350.00</span>
              </div>
            </div>
            <div style="display:flex; gap:8px;">
              <button type="button" onclick="clearSelectedSlots()" class="button sand" style="padding:8px 14px; font-size:0.78rem;">Clear</button>
              <button type="button" onclick="confirmMultiSlotBookingModal()" class="button coral" style="padding:9px 20px; font-size:0.84rem;">
                Continue <i class="bi bi-arrow-right"></i>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- FACILITY PRO SHOP & MARKETPLACE SECTION -->
    <div id="facility-marketplace-wrap" style="margin-bottom:32px;">
      <div class="marketplace-header-wrap" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; flex-wrap:wrap; gap:10px;">
        <div>
          <span class="eyebrow" style="background:var(--lime);"><i class="bi bi-shop"></i> PRO SHOP &amp; MARKETPLACE</span>
          <h3 class="mono" style="margin:4px 0 0; color:var(--green); font-size:clamp(1.2rem, 3vw, 1.5rem);">
            EQUIPMENT, RENTALS &amp; MERCHANDISE
          </h3>
        </div>
        <span class="mono" style="font-size:0.75rem; color:#4a5c56; font-weight:700;">
          <i class="bi bi-bag-check-fill" style="color:var(--coral);"></i> Available at Court Desk
        </span>
      </div>

      <div id="facility-products-grid" style="display:grid; grid-template-columns:repeat(auto-fill, minmax(220px, 1fr)); gap:16px;">
        <div class="skeleton skeleton-info" style="height:120px;"></div>
        <div class="skeleton skeleton-info" style="height:120px;"></div>
        <div class="skeleton skeleton-info" style="height:120px;"></div>
      </div>
    </div>

    <!-- Detailed Info Cards -->
    <div class="fac-info-grid" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:20px;">

      <!-- About & Amenities -->
      <div class="card-streetside fac-info-card" style="padding:20px; background:var(--white);">
        <h4 style="font-size:1.1rem; font-weight:800; text-transform:uppercase; margin:0 0 8px;">
          <i class="bi bi-info-circle"></i> ABOUT FACILITY
        </h4>
        <p id="fac-about-text" style="font-size:0.85rem; color:#3b4e48; line-height:1.5; margin-bottom:16px;">
          <span class="skeleton" style="display:block; height:60px;"></span>
        </p>
        <h4 style="font-size:1.1rem; font-weight:800; text-transform:uppercase; margin:0 0 8px;">
          <i class="bi bi-stars"></i> AMENITIES
        </h4>
        <div id="fac-amenities-list" style="display:flex; gap:6px; flex-wrap:wrap;">
          <span class="skeleton" style="display:inline-block; width:90px; height:28px;"></span>
          <span class="skeleton" style="display:inline-block; width:80px; height:28px;"></span>
          <span class="skeleton" style="display:inline-block; width:110px; height:28px;"></span>
        </div>
      </div>

      <!-- Pricing & Rules -->
      <div class="card-streetside fac-info-card" style="padding:20px; background:var(--white);">
        <h4 style="font-size:1.1rem; font-weight:800; text-transform:uppercase; margin:0 0 8px;">
          <i class="bi bi-cash-stack"></i> PRICING &amp; RATES
        </h4>
        <div id="fac-pricing-list" style="font-size:0.85rem; color:#3b4e48; margin-bottom:16px;">
          <span class="skeleton" style="display:block; height:56px;"></span>
        </div>
        <h4 style="font-size:1.1rem; font-weight:800; text-transform:uppercase; margin:0 0 8px;">
          <i class="bi bi-shield-exclamation"></i> COURT RULES
        </h4>
        <p style="font-size:0.85rem; color:#3b4e48; line-height:1.4; margin:0;">
          Non-marking athletic footwear required. Arrive 10 minutes prior to scheduled slot time.
        </p>
      </div>

      <!-- Reviews & Cancellation -->
      <div class="card-streetside fac-reviews-card" style="padding:20px; background:var(--white);">
        <h4 style="font-size:1.1rem; font-weight:800; text-transform:uppercase; margin:0 0 8px;">
          <i class="bi bi-star-fill" style="color:#f59e0b;"></i> REVIEWS &amp; RATING
        </h4>
        <div style="font-size:0.9rem; font-weight:800; margin-bottom:4px;">&#11088; 4.8 / 5.0 (Player Reviews)</div>
        <p style="font-size:0.82rem; color:#4a5c56; font-style:italic; margin-bottom:16px;">"Excellent court surface and bright night lighting!" — Maria S.</p>
        <h4 style="font-size:1.1rem; font-weight:800; text-transform:uppercase; margin:0 0 8px;">
          <i class="bi bi-arrow-counterclockwise"></i> CANCELLATION POLICY
        </h4>
        <p id="fac-cancellation" style="font-size:0.85rem; color:#3b4e48; line-height:1.4; margin:0;">
          Free cancellation up to 24 hours prior to slot start time.
        </p>
      </div>
    </div>
  </div>

  <div id="footer-container"></div>

  <!-- jQuery CDN -->
  <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
  <script src="/pikvero/assets/js/core/toast.js"></script>
  <script src="/pikvero/assets/js/core/modal.js"></script>
  <script src="/pikvero/assets/js/core/ajax.js"></script>
  <script src="/pikvero/assets/js/core/auth.js"></script>
  <script src="/pikvero/assets/js/components/navbar.js?v=2"></script>
  <script src="/pikvero/assets/js/components/footer.js"></script>
  <script>
    // ── State ────────────────────────────────────────────────────────────────
    var selectedCourtId = null;

    // Read facility & court IDs from URL params first, then sessionStorage
    (function () {
      var params = new URLSearchParams(window.location.search);
      if (params.get('id')) sessionStorage.setItem('selected_facility_id', params.get('id'));
      if (params.get('court_id')) sessionStorage.setItem('selected_court_id', params.get('court_id'));
      if (params.get('id') || params.get('court_id')) {
        window.history.replaceState({}, document.title, window.location.pathname);
      }
    })();

    var facilityId   = sessionStorage.getItem('selected_facility_id') || 1;
    var initialCourtId = sessionStorage.getItem('selected_court_id');

    // ── Court type badge map ─────────────────────────────────────────────────
    var COURT_BADGE = {
      indoor:  { label: 'INDOOR',   cls: 'lime'  },
      outdoor: { label: 'OUTDOOR',  cls: 'sky'   },
      covered: { label: 'COVERED',  cls: 'green' }
    };

    let cardCarouselIntervals = {};

    function startCardCarouselAutoplay(carouselId, totalCount) {
      if (totalCount <= 1) return;
      if (cardCarouselIntervals[carouselId]) {
        clearInterval(cardCarouselIntervals[carouselId]);
      }
      cardCarouselIntervals[carouselId] = setInterval(() => {
        navCardCarousel(null, carouselId, 1, totalCount, false);
      }, 3500);
    }

    // Card Image Carousel Generator
    function buildCardCarouselHtml(idPrefix, item, height) {
      const carouselId = idPrefix + '-' + item.id;
      const images = (item.images && item.images.length > 0) ? item.images : [];
      const h = height || '180px';

      if (images.length === 0) {
        const defaultImg = item.image_path || item.cover_image || '/pikvero/assets/images/logo.png';
        return `
          <div style="position:relative; height:${h}; width:100%; border-bottom:2px solid var(--ink); overflow:hidden; background:#111; border-top-left-radius:12px; border-top-right-radius:12px;">
            <img src="${defaultImg}" style="width:100%; height:100%; object-fit:cover;" onerror="this.onerror=null; this.src='/pikvero/assets/images/logo.png';">
          </div>
        `;
      }

      const slidesHtml = images.map(img => `
        <div style="flex:0 0 100%; height:100%; width:100%;">
          <img src="${img.image_path}" style="width:100%; height:100%; object-fit:cover;" onerror="this.onerror=null; this.src='/pikvero/assets/images/logo.png';">
        </div>
      `).join('');

      const dotsHtml = images.length > 1 ? `
        <div style="position:absolute; bottom:8px; left:50%; transform:translateX(-50%); display:flex; gap:4px; z-index:5;">
          ${images.map((_, idx) => `<span class="dot-${carouselId} ${idx === 0 ? 'active' : ''}" style="width:6px; height:6px; border-radius:50%; background:${idx === 0 ? 'var(--lime)' : 'rgba(255,255,255,0.6)'}; transition:all 0.2s;"></span>`).join('')}
        </div>
      ` : '';

      const navBtnsHtml = images.length > 1 ? `
        <button onclick="navCardCarousel(event, '${carouselId}', -1, ${images.length})" style="position:absolute; left:6px; top:50%; transform:translateY(-50%); background:rgba(13,33,29,0.75); color:#fff; border:1px solid var(--ink); border-radius:50%; width:28px; height:28px; display:flex; align-items:center; justify-content:center; cursor:pointer; font-weight:800; font-size:0.9rem; z-index:6;">&lsaquo;</button>
        <button onclick="navCardCarousel(event, '${carouselId}', 1, ${images.length})" style="position:absolute; right:6px; top:50%; transform:translateY(-50%); background:rgba(13,33,29,0.75); color:#fff; border:1px solid var(--ink); border-radius:50%; width:28px; height:28px; display:flex; align-items:center; justify-content:center; cursor:pointer; font-weight:800; font-size:0.9rem; z-index:6;">&rsaquo;</button>
      ` : '';

      if (images.length > 1) {
        setTimeout(() => startCardCarouselAutoplay(carouselId, images.length), 100);
      }

      const counterHtml = images.length > 0 ? `<span class="facility-photo-counter">1/${images.length}</span>` : '';

      return `
        <div class="facility-photo-carousel" data-carousel-id="${carouselId}" data-photo-count="${images.length}" style="position:relative; height:${h}; width:100%; border-bottom:2px solid var(--ink); overflow:hidden; background:#111; border-top-left-radius:12px; border-top-right-radius:12px;">
          <div id="track-${carouselId}" style="display:flex; height:100%; width:100%; transition:transform 0.35s ease;" data-index="0">
            ${slidesHtml}
          </div>
          ${navBtnsHtml}
          ${dotsHtml}
          ${counterHtml}
        </div>
      `;
    }

    function navCardCarousel(e, carouselId, direction, totalCount, isManual = true) {
      if (e) { e.preventDefault(); e.stopPropagation(); }
      const track = document.getElementById('track-' + carouselId);
      if (!track) return;

      let currentIndex = parseInt(track.getAttribute('data-index') || '0', 10);
      currentIndex += direction;

      if (currentIndex < 0) currentIndex = totalCount - 1;
      if (currentIndex >= totalCount) currentIndex = 0;

      track.setAttribute('data-index', currentIndex);
      track.style.transform = `translateX(-${currentIndex * 100}%)`;
      const counter = track.parentElement.querySelector('.facility-photo-counter');
      if (counter) counter.textContent = `${currentIndex + 1}/${totalCount}`;

      const dots = document.querySelectorAll('.dot-' + carouselId);
      dots.forEach((dot, idx) => {
        dot.style.background = (idx === currentIndex) ? 'var(--lime)' : 'rgba(255,255,255,0.6)';
        dot.style.transform = (idx === currentIndex) ? 'scale(1.3)' : 'scale(1)';
      });

      if (isManual && totalCount > 1) {
        startCardCarouselAutoplay(carouselId, totalCount);
      }
    }

    // ── Smooth Scroll Helper ──────────────────────────────────────────────────
    function scrollToCourts() {
      var el = document.getElementById('fac-courts-main-grid');
      if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    // ── Build Streetside Neo-Brutalist Empty State for Facility Courts ───────
    function buildFacilityCourtsEmptyStateHtml(facility) {
      var facilityName = (facility && facility.name) ? $('<div>').text(facility.name).html() : 'This Facility';
      var cityName = (facility && facility.city) ? $('<div>').text(facility.city).html() : 'Your Area';
      var phone = (facility && facility.phone) ? $('<div>').text(facility.phone).html() : '';
      var cityQuery = encodeURIComponent(facility && facility.city ? facility.city : '');

      var phoneHtml = phone ? (
        '<a href="tel:' + phone + '" class="badge-streetside sand" style="text-decoration:none; font-size:0.75rem; padding:5px 12px; display:inline-flex; align-items:center; gap:6px;">'
        + '<i class="bi bi-telephone-fill" style="color:var(--coral);"></i> Host Desk: <strong>' + phone + '</strong>'
        + '</a>'
      ) : '';

      return '<div class="facility-empty-card">'
        + '<div style="display:flex; justify-content:center; margin-bottom:12px;">'
        +   '<span class="badge-streetside coral" style="font-size:0.72rem; padding:4px 12px; font-weight:800; letter-spacing:0.04em;">'
        +     '<i class="bi bi-cone-striped"></i> VENUE NOTICE &bull; NO ACTIVE COURTS'
        +   '</span>'
        + '</div>'
        + '<div class="facility-empty-icon-box">'
        +   '<i class="bi bi-calendar-x-fill"></i>'
        + '</div>'
        + '<h3 class="facility-empty-title">NO COURTS CURRENTLY AVAILABLE</h3>'
        + '<p class="facility-empty-desc">'
        +   '<strong>' + facilityName + '</strong> has no active courts listed right now or court schedules are being updated. Online reservations for this location are temporarily unavailable.'
        + '</p>'
        + '<div class="facility-empty-pills">'
        +   '<span class="badge-streetside lime" style="font-size:0.75rem; padding:5px 12px; display:inline-flex; align-items:center; gap:6px;">'
        +     '<i class="bi bi-geo-alt-fill" style="color:var(--coral);"></i> ' + cityName
        +   '</span>'
        +   '<span class="badge-streetside sky" style="font-size:0.75rem; padding:5px 12px; display:inline-flex; align-items:center; gap:6px;">'
        +     '<i class="bi bi-clock-history"></i> Check Back Later'
        +   '</span>'
        +   phoneHtml
        + '</div>'
        + '<div class="facility-empty-actions">'
        +   (cityQuery ? '<a href="/pikvero/public/search.php?city=' + cityQuery + '" class="button coral" style="padding:10px 20px; font-size:0.86rem;"><i class="bi bi-geo-fill"></i> Other Courts in ' + cityName + '</a>' : '')
        +   '<a href="/pikvero/public/search.php" class="button sand" style="padding:10px 20px; font-size:0.86rem;"><i class="bi bi-grid-fill"></i> Browse All Courts</a>'
        +   '<a href="/pikvero/public/index.php" class="button lime" style="padding:10px 20px; font-size:0.86rem; color:var(--ink);"><i class="bi bi-house-door-fill"></i> Back to Home</a>'
        + '</div>'
        + '<div class="facility-empty-host-notice">'
        +   '<div>'
        +     '<i class="bi bi-shield-lock-fill" style="color:var(--green); margin-right:6px;"></i>'
        +     '<strong>Facility Host or Manager?</strong> Log into your host portal to configure courts and publish schedules.'
        +   '</div>'
        +   '<a href="/pikvero/public/login.php" class="mono" style="font-weight:800; color:var(--green); text-decoration:underline; white-space:nowrap;">Host Login &rarr;</a>'
        + '</div>'
        + '</div>';
    }

    // ── Render facility header ───────────────────────────────────────────────
    function renderHeader(facility, courts) {
      var addr = [facility.address, facility.city, facility.province].filter(Boolean).join(', ');
      var phone = facility.phone
        ? '<span style="margin-left:12px;"><i class="bi bi-telephone-fill"></i> ' + facility.phone + '</span>'
        : '';
      
      var facilityCarouselHtml = '';
      var heroItem = $.extend(true, {}, facility);
      if (!heroItem.images || heroItem.images.length === 0) {
        var firstCourtWithImages = (courts || []).find(function(c) { return c.images && c.images.length > 0; });
        heroItem.images = firstCourtWithImages
          ? firstCourtWithImages.images
          : [{ image_path: '/pikvero/assets/images/bg-search.png' }];
      }
      facilityCarouselHtml = buildCardCarouselHtml('fac-header', heroItem, '260px');

      var courtCountBadge = (courts && courts.length > 0)
        ? '<span class="mono" style="font-weight:800;color:var(--green);">' + courts.length + ' COURT' + (courts.length !== 1 ? 'S' : '') + '</span>'
        : '<span class="badge-streetside coral" style="font-size:0.68rem; padding:3px 8px; font-weight:800;"><i class="bi bi-exclamation-triangle-fill"></i> NO COURTS ACTIVE</span>';

      var headerActionButton = (courts && courts.length > 0)
        ? '<button onclick="scrollToCourts()" class="button coral" style="padding:10px 20px;font-size:0.88rem;">'
          + '<i class="bi bi-calendar-check"></i> Book a Court Now'
          + '</button>'
        : '<a href="/pikvero/public/search.php" class="button sand" style="padding:10px 20px;font-size:0.88rem;text-decoration:none;display:inline-flex;align-items:center;gap:6px;">'
          + '<i class="bi bi-search"></i> Explore Other Venues'
          + '</a>';

      $('#facility-header-card').html(
        (facilityCarouselHtml ? '<div style="margin:-24px -24px 20px -24px;">' + facilityCarouselHtml + '</div>' : '')
        + '<div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:14px;">'
          + '<div>'
          + '<div style="display:flex;gap:8px;align-items:center;margin-bottom:4px;">'
          +   '<span class="badge-streetside lime">' + facility.city + '</span>'
          +   courtCountBadge
          + '</div>'
          + '<h1 style="font-size:clamp(1.8rem,4vw,2.4rem);font-weight:800;text-transform:uppercase;margin:4px 0 4px;">' + facility.name + '</h1>'
          + '<p style="font-size:0.9rem;color:#1a3d34;margin:0;"><i class="bi bi-geo-alt-fill"></i> ' + addr + phone + '</p>'
          + '</div>'
          + headerActionButton
        + '</div>'
      );

      // Update page title
      document.title = 'Pikvero — ' + facility.name;
    }

    // ── Render gallery strip from amenities ──────────────────────────────────
    var GALLERY_COLORS = ['sand', 'cream', 'lime', 'sky'];
    function renderGallery(amenities) {
      if (!amenities || amenities.length === 0) {
        $('#facility-gallery').hide();
        return;
      }
      var html = $.map(amenities.slice(0, 6), function(a, i) {
        var bg = GALLERY_COLORS[i % GALLERY_COLORS.length];
        return '<div class="card-streetside" style="padding:20px;background:var(--' + bg + ');text-align:center;">'
          + '<i class="bi ' + (a.icon || 'bi-check-circle') + '" style="font-size:2rem;color:var(--ink);"></i>'
          + '<div class="mono" style="font-size:0.72rem;font-weight:700;margin-top:6px;text-transform:uppercase;">' + a.name + '</div>'
          + '</div>';
      }).join('');
      $('#facility-gallery').html(html).show();
    }

    // ── Render courts list ───────────────────────────────────────────────────
    function renderCourts(courts) {
      if (!courts || courts.length === 0) {
        $('#courts-list').empty();
        return;
      }
      var html = $.map(courts, function(c) {
        var badge = COURT_BADGE[c.court_type] || { label: (c.court_type || 'COURT').toUpperCase(), cls: 'dark' };
        var priceValue = parseFloat(c.base_price_per_hour || 0);
        var price = Number.isInteger(priceValue) ? priceValue.toFixed(0) : priceValue.toFixed(2);
        var courtTypeLabel = c.court_type ? c.court_type.charAt(0).toUpperCase() + c.court_type.slice(1) : 'Court';
        var surfaceLabel = c.surface_type ? c.surface_type.replace(/_/g,' ').replace(/\b\w/g, function(ch) { return ch.toUpperCase(); }) : 'Standard';
        var courtCarouselHtml = buildCardCarouselHtml('facility-court', c, '150px');

        return '<div class="card-streetside court-item" id="court-card-' + c.id + '" data-court-type="' + c.court_type + '" data-court-name="' + c.name + '" onclick="selectCourt(' + c.id + ')" style="padding:0; overflow:hidden;">'
          + '<div class="court-selected-indicator" style="display:none; background:var(--ink); color:var(--lime); padding:6px 12px; font-family:\'DM Mono\', monospace; font-size:0.75rem; font-weight:800; text-transform:uppercase; letter-spacing:0.06em; border-bottom:2px solid var(--ink); justify-content:space-between; align-items:center;">'
          +   '<span><i class="bi bi-check-circle-fill" style="color:var(--lime); margin-right:6px;"></i> SELECTED COURT</span>'
          +   '<span style="font-size:0.65rem; background:var(--lime); color:var(--ink); padding:2px 6px; border-radius:4px; font-weight:900;">ACTIVE VIEW</span>'
          + '</div>'
          + courtCarouselHtml
          + '<div style="padding:14px;">'
          + '<div style="display:flex;justify-content:space-between;align-items:center;">'
          +   '<h4 style="font-size:1.05rem;font-weight:800;text-transform:uppercase;margin:0;">' + c.name + '</h4>'
          +   '<span class="badge-streetside ' + badge.cls + '" style="font-size:0.62rem;">' + badge.label + '</span>'
          + '</div>'
          + '<div style="display:flex;justify-content:space-between;align-items:center;margin-top:8px;font-size:0.85rem;">'
          +   '<span style="color:#4a5c56;">' + courtTypeLabel + ' · ' + surfaceLabel + '</span>'
          +   '<strong style="color:var(--green);">&#8369;' + price + ' / hour</strong>'
          + '</div>'
          + '</div>'
          + '</div>';
      }).join('');
      $('#courts-list').html(html);
    }

    // ── Render about & amenities panel ───────────────────────────────────────
    function renderAbout(facility, amenities) {
      var desc = facility.description
        ? facility.description
        : facility.name + ' is a premier pickleball facility located in ' + facility.city + '. Reserve a court online for instant confirmation.';
      $('#fac-about-text').text(desc);

      if (amenities && amenities.length > 0) {
        var amenityColors = ['lime', 'green', 'sky', 'sand', 'coral'];
        var html = $.map(amenities, function(a, i) {
          var cls = amenityColors[i % amenityColors.length];
          return '<span class="badge-streetside ' + cls + '" style="font-size:0.78rem;">'
            + '<i class="bi ' + (a.icon || 'bi-check-circle') + '"></i> ' + a.name
            + '</span>';
        }).join(' ');
        $('#fac-amenities-list').html(html);
      } else {
        $('#fac-amenities-list').html('<span style="font-size:0.82rem;color:#4a5c56;">No amenities listed.</span>');
      }
    }

    // ── Render pricing from courts data ──────────────────────────────────────
    function renderPricing(courts) {
      if (!courts || courts.length === 0) {
        $('#fac-pricing-list').html(
          '<div style="display:inline-flex; align-items:center; gap:8px; padding:8px 14px; background:var(--sand); border:2px solid var(--ink); border-radius:10px; font-size:0.8rem; font-weight:800; color:var(--ink); box-shadow:2px 2px 0 var(--ink);">'
          + '<i class="bi bi-tag-fill" style="color:var(--coral);"></i> Pricing will be posted once court schedules are published.'
          + '</div>'
        );
        return;
      }
      var html = '<ul style="font-size:0.85rem;color:#3b4e48;padding-left:18px;margin:0;line-height:1.8;">';
      $.each(courts, function(i, c) {
        var price = parseFloat(c.base_price_per_hour || 0).toFixed(2);
        var label = c.name + ' (' + (c.court_type || '') + ')';
        html += '<li>' + label + ': <strong>&#8369;' + price + ' / hr</strong></li>';
      });
      html += '</ul>';
      $('#fac-pricing-list').html(html);
    }


    // ── Render facility products & marketplace items ──────────────────────────
    function renderProducts(products) {
      var $grid = $('#facility-products-grid');
      products = (products || []).filter(p => Number(p.facility_id) === Number(facilityId));
      if (!products || products.length === 0) {
        $grid.empty();
        $('#facility-marketplace-wrap').hide();
        return;
      }
      $('#facility-marketplace-wrap').show();

      var html = $.map(products, function(p) {
        var isRental = p.type === 'rental';
        var badgeCls = isRental ? 'sky' : 'lime';
        var badgeLabel = isRental ? 'COURT RENTAL' : (p.category || 'FOR SALE').toUpperCase();
        var price = parseFloat(p.price || 0).toFixed(2);
        var stock = parseInt(p.stock_quantity || 0, 10);

        var catIcons = {
          'paddle': 'bi-trophy-fill',
          'ball': 'bi-dribbble',
          'grip': 'bi-hand-index-thumb-fill',
          'bag': 'bi-bag-check-fill',
          'rental': 'bi-clock-history'
        };
        var catKey = ((p.category || '') + ' ' + (p.type || '')).toLowerCase();
        var iconCls = 'bi-box-seam-fill';
        for (var k in catIcons) {
          if (catKey.includes(k)) { iconCls = catIcons[k]; break; }
        }

        return '<div class="card-streetside product-card-compact" style="padding:14px; background:var(--white); display:flex; flex-direction:column; justify-content:space-between; border-radius:12px;">'
          + '<div>'
          + '<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px; flex-wrap:wrap; gap:4px;">'
          +   '<span class="badge-streetside ' + badgeCls + '" style="font-size:0.62rem; padding:2px 6px;"><i class="bi ' + iconCls + '"></i> ' + badgeLabel + '</span>'
          +   '<span class="mono" style="font-size:0.65rem; color:' + (stock > 0 ? '#15803d' : '#b91c1c') + '; font-weight:800;">' + (stock > 0 ? stock + ' available' : 'Out of stock') + '</span>'
          + '</div>'
          + '<h4 style="font-size:0.98rem; font-weight:800; text-transform:uppercase; margin:0 0 4px; line-height:1.25;">' + p.name + '</h4>'
          + '<p style="font-size:0.78rem; color:#4a5c56; margin-bottom:10px; line-height:1.35;">' + (p.description || '') + '</p>'
          + '</div>'
          + '<div class="product-card-footer" style="display:flex; justify-content:space-between; align-items:center; border-top:1.5px dashed var(--ink); padding-top:8px; margin-top:6px; flex-wrap:wrap; gap:6px;">'
          + '<div>'
          +   '<span style="font-size:0.60rem; font-weight:800; font-family:\'DM Mono\', monospace; display:block; color:#4a5c56;">PRICE</span>'
          +   '<strong style="font-size:1.1rem; color:var(--coral);">&#8369;' + price + '</strong>'
          + '</div>'
          + '<button type="button" onclick="Toast.info(\'In-Facility Product\', \'Ask court desk staff to purchase or rent item.\')" class="button coral" style="padding:6px 12px; font-size:0.75rem;">'
          +   '<i class="bi bi-cart-plus-fill"></i> Inquire'
          + '</button>'
          + '</div>'
          + '</div>';
      }).join('');

      $grid.html(html);
    }

    // ── Select a court and load time slots ───────────────────────────────────
    function selectCourt(courtId) {
      selectedCourtId = courtId;
      sessionStorage.setItem('selected_court_id', courtId);

      // Highlight selected
      $('.court-item').removeClass('selected');
      const $selectedCard = $('#court-card-' + courtId);
      $selectedCard.addClass('selected');

      const courtName = $selectedCard.attr('data-court-name') || 'Selected Court';

      const badgeHtml = `
        <span class="badge-streetside lime" style="display:inline-flex; align-items:center; gap:6px; font-family:'DM Sans', sans-serif; font-size:0.85rem; font-weight:900; padding:5px 14px; border:2px solid var(--ink); box-shadow:2.5px 2.5px 0 var(--ink); color:var(--ink); text-transform:uppercase; letter-spacing:0.04em; margin-left:6px; vertical-align:middle;">
          <i class="bi bi-geo-fill" style="color:var(--coral);"></i> ${courtName}
        </span>
      `;

      $('#selected-court-title-display').html(`
        <span style="display:inline-flex; align-items:center; gap:6px; flex-wrap:wrap;">
          <i class="bi bi-clock-history"></i> AVAILABLE TIME SLOTS &bull; ${badgeHtml}
        </span>
      `);

      // Load slots for selected date
      var date = $('#booking-date-picker').val();
      loadTimeSlots(courtId, date);
    }

    // ── Show skeleton slots ──────────────────────────────────────────────────
    function showSlotSkeletons() {
      var skels = '';
      for (var i = 0; i < 6; i++) skels += '<div class="skeleton skeleton-slot"></div>';
      $('#time-slots-grid').html(skels);
    }

    function formatTime12h(timeStr) {
      if (!timeStr) return '';
      if (String(timeStr).includes('AM') || String(timeStr).includes('PM')) return timeStr;
      const parts = String(timeStr).trim().split(':');
      let h = parseInt(parts[0], 10);
      if (isNaN(h)) return timeStr;
      const m = parts[1] ? parts[1].substring(0, 2) : '00';
      const suffix = h >= 12 ? 'PM' : 'AM';
      h = h % 12;
      if (h === 0) h = 12;
      return `${h}:${m} ${suffix}`;
    }

    let selectedSlotsMap = {};

    function toggleSlotSelection(slotKey, courtId, date, startTime, endTime, price) {
      if (selectedSlotsMap[slotKey]) {
        delete selectedSlotsMap[slotKey];
      } else {
        selectedSlotsMap[slotKey] = {
          slotKey: slotKey,
          courtId: courtId,
          date: date,
          startTime: startTime,
          endTime: endTime,
          price: parseFloat(price)
        };
      }
      updateSlotsUI();
    }

    function clearSelectedSlots() {
      selectedSlotsMap = {};
      updateSlotsUI();
    }

    function updateSlotsUI() {
      const keys = Object.keys(selectedSlotsMap);

      $('.slot-btn.available').each(function() {
        const key = $(this).attr('data-slot-key');
        const start = $(this).attr('data-slot-start');
        const end = $(this).attr('data-slot-end');
        const price = $(this).attr('data-slot-price');

        if (selectedSlotsMap[key]) {
          $(this).addClass('selected-slot').removeClass('lime').addClass('dark');
          $(this).html(`<strong><i class="bi bi-check-circle-fill" style="color:var(--lime); margin-right:3px;"></i> ${start}–${end}</strong><span style="font-size:0.68rem; color:var(--lime); font-weight:800;">✓ ₱${price}</span>`);
        } else {
          $(this).removeClass('selected-slot').removeClass('dark').addClass('lime');
          $(this).html(`<strong>${start}–${end}</strong><span style="font-size:0.68rem; opacity:0.85;">₱${price}</span>`);
        }
      });

      if (keys.length === 0) {
        $('#multi-slot-summary-bar').slideUp(150);
        return;
      }

      const sortedSlots = keys.map(k => selectedSlotsMap[k]).sort((a, b) => a.startTime.localeCompare(b.startTime));

      const totalPrice = sortedSlots.reduce((sum, s) => sum + s.price, 0);
      const totalHours = sortedSlots.length;

      $('#summary-hours-count').text(`${totalHours} Hour${totalHours > 1 ? 's' : ''}`);
      $('#summary-total-price').text(`₱${totalPrice.toFixed(2)}`);

      $('#multi-slot-summary-bar').slideDown(150);
    }

    // ── Load time slots via jQuery AJAX ──────────────────────────────────────
    function loadTimeSlots(courtId, date) {
      var $grid = $('#time-slots-grid');
      showSlotSkeletons();

      $.ajax({
        url: '/pikvero/api/customer/availability.php',
        method: 'GET',
        data: { court_id: courtId, date: date },
        dataType: 'json',
        success: function(res) {
          var slots = [];
          if (res.success && res.data) {
            slots = res.data.slots || (Array.isArray(res.data) ? res.data : []);
          }

          if (slots.length === 0) {
            $grid.html(
              '<div class="card-streetside sand" style="grid-column:1/-1;padding:20px;text-align:center;">'
              + '<i class="bi bi-calendar-x" style="font-size:1.6rem;color:#aaa;display:block;margin-bottom:8px;"></i>'
              + '<p style="margin:0;font-size:0.85rem;">No available slots for this date.</p>'
              + '</div>'
            );
            return;
          }

          var availableKeys = new Set(slots.filter(function(slot) { return slot.available; }).map(function(slot) {
            return String(slot.start_time).substring(0, 5) + '-' + String(slot.end_time).substring(0, 5);
          }));
          Object.keys(selectedSlotsMap).forEach(function(key) {
            if (!availableKeys.has(key)) delete selectedSlotsMap[key];
          });

          var html = $.map(slots, function(slot) {
            var price = parseFloat(slot.price || slot.price_per_hour || 350).toFixed(2);
            var rawStart = String(slot.start_time).substring(0, 5);
            var rawEnd   = String(slot.end_time).substring(0, 5);
            var displayStart = slot.display_start || formatTime12h(rawStart);
            var displayEnd   = slot.display_end || formatTime12h(rawEnd);
            var slotKey = rawStart + '-' + rawEnd;
            var isSelected = !!selectedSlotsMap[slotKey];

            if (slot.available) {
              return '<button class="button lime slot-btn available ' + (isSelected ? 'selected-slot dark' : '') + '"'
                + ' data-slot-key="' + slotKey + '"'
                + ' data-slot-start="' + displayStart + '"'
                + ' data-slot-end="' + displayEnd + '"'
                + ' data-slot-price="' + price + '"'
                + ' onclick="toggleSlotSelection(\'' + slotKey + '\',' + courtId + ',\'' + date + '\',\'' + slot.start_time + '\',\'' + slot.end_time + '\',' + price + ')">'
                + '<strong>' + (isSelected ? '<i class="bi bi-check-circle-fill" style="color:var(--lime); margin-right:3px;"></i> ' : '') + displayStart + '–' + displayEnd + '</strong>'
                + '<span style="font-size:0.68rem;opacity:0.85;">' + (isSelected ? '✓ ' : '') + '&#8369;' + price + '</span>'
                + '</button>';
            } else {
              var isOpenPlay = slot.status === 'open_play';
              var reason = $('<span>').text(isOpenPlay ? 'Closed · Open Play' : (slot.reason || 'Booked')).html();
              return '<button disabled data-slot-start="' + displayStart + '" data-slot-key="' + slotKey + '" class="button sand slot-btn booked' + (isOpenPlay ? ' open-play-closed' : '') + '">'
                + '<strong>' + displayStart + '–' + displayEnd + '</strong>'
                + '<span style="font-size:0.68rem;">' + reason + '</span>'
                + '</button>';
            }
          }).join('');

          $grid.html(html);
          updateSlotsUI();
        },
        error: function() {
          $grid.html(
            '<div class="card-streetside sand" style="grid-column:1/-1;padding:16px;text-align:center;">'
            + '<p style="margin:0;font-size:0.85rem;color:var(--coral);">Failed to load time slots. Please try again.</p>'
            + '</div>'
          );
        }
      });
    }

    // ── Confirm Multi-Slot booking modal ──────────────────────────────────────
    function confirmMultiSlotBookingModal() {
      const keys = Object.keys(selectedSlotsMap);
      if (keys.length === 0) {
        Toast.error('No Slot Selected', 'Please select at least one available time slot.');
        return;
      }

      AuthHelper.checkSession().then(function(user) {
        if (!user || !user.user) {
          Toast.error('Login Required', 'Please login to reserve court time slots.');
          setTimeout(function() {
            window.location.href = '/pikvero/public/login.php';
          }, 1000);
          return;
        }

        const sortedSlots = keys.map(k => selectedSlotsMap[k]).sort((a, b) => a.startTime.localeCompare(b.startTime));
        const courtId = sortedSlots[0].courtId;
        const date = sortedSlots[0].date;
        const earliestStart = sortedSlots[0].startTime;
        const latestEnd = sortedSlots[sortedSlots.length - 1].endTime;
        const totalHours = sortedSlots.length;
        const totalPrice = sortedSlots.reduce((sum, s) => sum + s.price, 0);

        const $selectedCard = $('#court-card-' + courtId);
        const courtName = $selectedCard.attr('data-court-name') || 'Selected Court';

        const itemizedListHtml = sortedSlots.map(s => `
          <div style="display:flex; justify-content:space-between; font-size:0.82rem; margin-bottom:4px; padding:4px 8px; background:var(--sand); border-radius:6px;">
            <span><i class="bi bi-clock"></i> ${formatTime12h(s.startTime)} - ${formatTime12h(s.endTime)}</span>
            <strong>₱${s.price.toFixed(2)}</strong>
          </div>
        `).join('');

        if (matchMedia('(max-width:768px)').matches && window.showMobileBookingSummary) {
          window.showMobileBookingSummary({ courtId, courtName, date, earliestStart, latestEnd, totalHours, totalPrice, itemizedListHtml });
          return;
        }

        // Payment method selector cards
        const paymentMethodHtml = `
          <div style="margin-top:16px; margin-bottom:4px;">
            <strong style="font-size:0.88rem;"><i class="bi bi-wallet2"></i> Payment Method</strong>
          </div>
          <div id="payment-method-selector" style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:12px;">
            <div class="pm-card selected" data-method="cash" onclick="selectPaymentMethod(this, 'cash')"
              style="cursor:pointer; padding:14px 10px; border:3px solid var(--ink); border-radius:12px; text-align:center; background:var(--lime); box-shadow:3px 3px 0 var(--ink); transition:all 0.2s ease;">
              <i class="bi bi-cash-coin" style="font-size:1.5rem; display:block; margin-bottom:4px; color:var(--ink);"></i>
              <div style="font-weight:800; font-size:0.82rem; text-transform:uppercase; color:var(--ink);">Cash</div>
              <div style="font-size:0.7rem; color:#3b4e48; margin-top:2px;">Pay at Counter</div>
            </div>
            <div class="pm-card" data-method="online" onclick="selectPaymentMethod(this, 'online')"
              style="cursor:pointer; padding:14px 10px; border:2px solid #ccc; border-radius:12px; text-align:center; background:var(--white); transition:all 0.2s ease;">
              <i class="bi bi-phone" style="font-size:1.5rem; display:block; margin-bottom:4px; color:#888;"></i>
              <div style="font-weight:800; font-size:0.82rem; text-transform:uppercase; color:#888;">Online Payment</div>
              <div style="font-size:0.7rem; color:#999; margin-top:2px;">GCash, Maya, Card</div>
            </div>
          </div>
        `;

        Modal.confirm({
          title: 'Booking Summary',
          message:
            '<div style="font-size:0.9rem;">'
            + '<p style="margin-bottom:8px;"><strong>Court:</strong> ' + courtName + '</p>'
            + '<p style="margin-bottom:8px;"><strong>Date:</strong> ' + date + '</p>'
            + '<p style="margin-bottom:8px;"><strong>Time Slot Range:</strong> ' + formatTime12h(earliestStart) + ' &ndash; ' + formatTime12h(latestEnd) + ' (' + totalHours + ' Hour' + (totalHours > 1 ? 's' : '') + ')</p>'
            + '<div style="margin-bottom:12px;"><strong>Itemized Hours:</strong><div style="margin-top:6px;">' + itemizedListHtml + '</div></div>'
            + '<p style="margin-bottom:0; font-size:1.05rem;"><strong>Total Amount:</strong> <span style="color:var(--green); font-weight:900;">&#8369;' + totalPrice.toFixed(2) + '</span></p>'
            + paymentMethodHtml
            + '</div>',
          confirmText: 'Proceed to payment',
          onConfirm: function() {
            // Read selected payment method from the modal
            const selectedMethod = document.querySelector('#payment-method-selector .pm-card.selected');
            const paymentMethod = selectedMethod ? selectedMethod.getAttribute('data-method') : 'cash';

            if (paymentMethod === 'online') {
              // Open PLACE ORDER Modal with details and order summary
              openPlaceOrderModal(courtId, courtName, date, earliestStart, latestEnd, totalHours, totalPrice, itemizedListHtml);
            } else {
              // Cash: immediate confirmation
              $.ajax({
                url: '/pikvero/api/customer/bookings.php',
                method: 'POST',
                contentType: 'application/json',
                dataType: 'json',
                data: JSON.stringify({
                  court_id:       courtId,
                  date:           date,
                  start_time:     earliestStart,
                  end_time:       latestEnd,
                  payment_method: 'cash'
                }),
                success: function(res) {
                  if (res.success) {
                    const bookingRef = res.data.booking_reference;
                    const toastTitle = 'Reservation Confirmed! 🎾';
                    const toastMsg = 'Booking #' + bookingRef + ' (Pay at Counter — Status: Unpaid)';

                    Toast.success(toastTitle, toastMsg, 6000);
                    Toast.setFlash(toastTitle, toastMsg, 'success', 6000);

                    setTimeout(() => { window.location.href = '/pikvero/public/customer/bookings.php'; }, 1500);
                  } else {
                    Toast.error('Booking Failed', res.message || 'Could not create reservation.');
                  }
                },
                error: function(xhr) {
                  var msg = 'Booking failed. Please try again.';
                  try { msg = JSON.parse(xhr.responseText).message || msg; } catch(e) {}
                  Toast.error('Booking Failed', msg);
                }
              });
            }
          }
        });
      });
    }

    // ── Open Place Order Modal for Online Payments ────────────────────────────
    function openPlaceOrderModal(courtId, courtName, date, earliestStart, latestEnd, totalHours, totalPrice, itemizedListHtml, extras = {}) {
      const platformFeePct = (!isNaN(window.platformFeePct) && window.platformFeePct !== null) ? window.platformFeePct : 10.0;
      const paymongoFeePct  = (!isNaN(window.paymongoFeePct) && window.paymongoFeePct !== null) ? window.paymongoFeePct : 2.5;

      const basePrice   = totalPrice;
      const platformFee = Math.round(basePrice * (platformFeePct / 100) * 100) / 100;
      const gatewayFee  = Math.round(basePrice * (paymongoFeePct / 100) * 100) / 100;
      const grandTotal  = basePrice + platformFee + gatewayFee;

      const orderSummaryHtml = `
        <div style="font-size:0.9rem;">
          <div style="background:var(--sand); border:2px solid var(--ink); border-radius:12px; padding:16px; margin-bottom:14px; box-shadow:3px 3px 0 var(--ink);">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; border-bottom:1.5px dashed var(--ink); padding-bottom:8px;">
              <span style="font-weight:900; font-family:'DM Mono', monospace; color:var(--ink); font-size:0.82rem; text-transform:uppercase;">
                <i class="bi bi-receipt"></i> ORDER SUMMARY
              </span>
              <span class="badge-streetside lime" style="font-size:0.7rem;">PayMongo Online</span>
            </div>
            
            <p style="margin-bottom:6px;"><strong>Court:</strong> ${courtName}</p>
            <p style="margin-bottom:6px;"><strong>Date:</strong> ${date}</p>
            <p style="margin-bottom:6px;"><strong>Time Range:</strong> ${formatTime12h(earliestStart)} &ndash; ${formatTime12h(latestEnd)} (${totalHours} Hour${totalHours > 1 ? 's' : ''})</p>
            
            <div style="margin:10px 0 12px;">
              <strong style="font-size:0.82rem;">Itemized Hours:</strong>
              <div style="margin-top:6px;">${itemizedListHtml}</div>
            </div>

            <!-- Fee Breakdown -->
            <div style="border-top:1px dashed #bbb; padding-top:10px; margin-top:10px; font-size:0.82rem; display:flex; flex-direction:column; gap:4px;">
              <div style="display:flex; justify-content:space-between; color:#4a5c56;">
                <span>Court Rate Subtotal:</span>
                <strong>&#8369;${basePrice.toFixed(2)}</strong>
              </div>
              <div style="display:flex; justify-content:space-between; color:#4a5c56;">
                <span>Service fees:</span>
                <strong>&#8369;${(platformFee + gatewayFee).toFixed(2)}</strong>
              </div>
            </div>

            <div style="display:flex; justify-content:space-between; align-items:center; background:var(--ink); color:var(--white); padding:12px 16px; border-radius:10px; margin-top:12px;">
              <span style="font-weight:800; font-size:0.88rem;">TOTAL AMOUNT DUE:</span>
              <span style="font-size:1.2rem; font-weight:900; color:var(--lime);">&#8369;${grandTotal.toFixed(2)}</span>
            </div>
          </div>

          <div style="padding:12px; background:#e8f4f0; border:1px solid #b2dfdb; border-radius:10px; font-size:0.8rem; color:#1b4d3e; display:flex; gap:10px; align-items:center;">
            <i class="bi bi-shield-lock-fill" style="font-size:1.4rem; color:var(--green); flex-shrink:0;"></i>
            <div>
              <strong>Pay via PayMongo Gateway</strong><br>
              <span>You will be redirected to complete your payment using <strong>GCash, Maya, or Card</strong>.</span>
            </div>
          </div>
        </div>
      `;

      setTimeout(function() {
        Modal.confirm({
          title: 'Booking Summary',
          message: orderSummaryHtml,
          confirmText: 'Place Order & Pay ₱' + grandTotal.toFixed(2),
          onConfirm: function() {
            // Step 1: Create the booking with payment_method: 'online'
            $.ajax({
              url: '/pikvero/api/customer/bookings.php',
              method: 'POST',
              contentType: 'application/json',
              dataType: 'json',
              data: JSON.stringify({
                court_id:       courtId,
                date:           date,
                start_time:     earliestStart,
                end_time:       latestEnd,
                payment_method: 'online',
                notes: extras.notes || '',
                addons: extras.addons || []
              }),
              success: function(res) {
                if (!res.success) {
                  Toast.error('Booking Failed', res.message || 'Could not create booking.');
                  return;
                }

                // Step 2: Create PayMongo checkout session
                Toast.success('Order Placed!', 'Redirecting to payment gateway...');
                $.ajax({
                  url: '/pikvero/api/payments/booking-checkout.php',
                  method: 'POST',
                  contentType: 'application/json',
                  dataType: 'json',
                  data: JSON.stringify({
                    booking_reference: res.data.booking_reference,
                    facility_id: facilityId
                  }),
                  success: function(payRes) {
                    if (payRes.success && payRes.data && payRes.data.checkout_url) {
                      window.location.href = payRes.data.checkout_url;
                    } else {
                      Toast.error('Payment Error', payRes.message || 'Could not create checkout session.');
                    }
                  },
                  error: function(xhr) {
                    var msg = 'Payment gateway error. Please try again.';
                    try { msg = JSON.parse(xhr.responseText).message || msg; } catch(e) {}
                    Toast.error('Payment Error', msg);
                  }
                });
              },
              error: function(xhr) {
                var msg = 'Booking failed. Please try again.';
                try { msg = JSON.parse(xhr.responseText).message || msg; } catch(e) {}
                Toast.error('Booking Failed', msg);
              }
            });
          }
        });
      }, 200);
    }

    // ── Payment method card selector ─────────────────────────────────────────
    function selectPaymentMethod(el, method) {
      const cards = document.querySelectorAll('#payment-method-selector .pm-card');
      cards.forEach(function(card) {
        card.classList.remove('selected');
        card.style.border = '2px solid #ccc';
        card.style.background = 'var(--white)';
        card.style.boxShadow = 'none';
        card.querySelector('i').style.color = '#888';
        card.querySelectorAll('div')[0].style.color = '#888';
        card.querySelectorAll('div')[1].style.color = '#999';
      });

      el.classList.add('selected');
      el.style.border = '3px solid var(--ink)';
      el.style.background = 'var(--lime)';
      el.style.boxShadow = '3px 3px 0 var(--ink)';
      el.querySelector('i').style.color = 'var(--ink)';
      el.querySelectorAll('div')[0].style.color = 'var(--ink)';
      el.querySelectorAll('div')[1].style.color = '#3b4e48';

      // Update confirm button text
      const confirmBtn = document.querySelector('.modal-box-custom .btn-confirm');
      if (confirmBtn) {
        confirmBtn.textContent = method === 'online' ? 'Proceed to Payment' : 'Confirm Reservation';
      }
    }

    // ── Scroll helper ────────────────────────────────────────────────────────
    function scrollToCourts() {
      var el = document.getElementById('courts-list');
      if (el) el.scrollIntoView({ behavior: 'smooth' });
    }

    // ── Load facility detail via jQuery AJAX ─────────────────────────────────
    function loadFacility() {
      $.ajax({
        url: '/pikvero/api/customer/facilities.php',
        method: 'GET',
        data: { id: facilityId },
        dataType: 'json',
        success: function(res) {
          if (!res.success || !res.data) {
            $('#facility-header-card').html(
              '<p style="color:var(--coral);font-weight:700;">Facility not found or unavailable.</p>'
            );
            return;
          }

          var facility  = res.data.facility;
          var courts    = res.data.courts   || [];
          var amenities = res.data.amenities || [];
          var products  = res.data.products  || [];
          window.facilityBookingData = { facility, courts, products, paymentMethods: res.data.payment_methods || [] };

          if (res.data.fees) {
            window.platformFeePct = parseFloat(res.data.fees.platform_fee_percent);
            window.paymongoConfiguredFeePct = parseFloat(res.data.fees.paymongo_fee_percent);
            window.passGatewayFee = res.data.fees.pass_gateway_fee === true;
            window.paymongoFeePct = window.passGatewayFee ? window.paymongoConfiguredFeePct : 0;
          }

          renderHeader(facility, courts);
          renderGallery(amenities);
          renderAbout(facility, amenities);
          renderPricing(courts);
          renderCourts(courts);
          renderProducts(products);

          // Auto-select the pre-chosen court or first court, or show enhanced empty state
          if (courts.length > 0) {
            $('#courts-empty-state-wrap').hide();
            $('#court-selection-col').show();
            $('#time-slots-col').show();

            var target = initialCourtId
              ? $.grep(courts, function(c) { return String(c.id) === String(initialCourtId); })[0]
              : null;
            selectCourt(target ? target.id : courts[0].id);
          } else {
            $('#court-selection-col').hide();
            $('#time-slots-col').hide();
            $('#courts-empty-state-wrap').html(buildFacilityCourtsEmptyStateHtml(facility)).show();
          }
        },
        error: function() {
          $('#facility-header-card').html(
            '<p style="color:var(--coral);font-weight:700;">Failed to load facility data. Please try again.</p>'
          );
        }
      });
    }

    // ── Init on DOM ready ────────────────────────────────────────────────────
    $(document).ready(function () {
      NavbarComponent.render();
      FooterComponent.render();

      // Set up date picker
      var today = new Date().toISOString().split('T')[0];
      $('#booking-date-picker').val(today).attr('min', today);

      $('#booking-date-picker').on('change', function() {
        if (selectedCourtId) loadTimeSlots(selectedCourtId, $(this).val());
      });

      loadFacility();
    });
  </script>
<script src="/pikvero/assets/js/components/facility-mobile.js?v=<?= filemtime(__DIR__.'/../assets/js/components/facility-mobile.js') ?>"></script>
</body>
</html>
