<?php
require_once __DIR__ . '/../app/bootstrap.php';
use App\Core\Auth\Auth;

$isLoggedIn = Auth::check();
$user = $isLoggedIn ? Auth::user() : [];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
  <title>Pikvero — Open Play Social Pickleball Sessions</title>
  <meta name="description" content="View and join social pickleball open play sessions across Bohol. Rotating doubles, fixed per-player pass, all skill levels welcome.">
  <?php $favLogo = class_exists(\App\Core\Auth\Auth::class) ? \App\Core\Auth\Auth::getLogoUrl() : '/pikvero/assets/images/logo.png'; ?>
  <link rel="icon" type="image/png" href="<?= htmlspecialchars($favLogo) ?>?v=<?= time() ?>">
  <link rel="shortcut icon" type="image/png" href="<?= htmlspecialchars($favLogo) ?>?v=<?= time() ?>">
  <link rel="apple-touch-icon" href="<?= htmlspecialchars($favLogo) ?>?v=<?= time() ?>">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="/pikvero/assets/css/streetside-theme.css?v=<?= time() ?>">
  <link rel="stylesheet" href="/pikvero/assets/css/modal.css?v=<?= time() ?>">
  <link rel="stylesheet" href="/pikvero/assets/css/toast.css">
  <style>
    *, *::before, *::after {
      box-sizing: border-box !important;
    }
    html, body {
      max-width: 100% !important;
      overflow-x: hidden !important;
      position: relative;
      margin: 0;
      padding: 0;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }
    #page-content {
      flex: 1;
      padding: 105px max(4vw, 18px) 40px;
    }

    /* Hero Banner */
    .op-hero-banner {
      background: var(--white);
      border: 2.5px solid var(--ink);
      border-radius: 18px;
      padding: 24px 26px;
      box-shadow: 5px 5px 0 var(--ink);
      margin-bottom: 20px;
      position: relative;
      overflow: hidden;
    }
    .op-hero-banner::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 5px;
      background: repeating-linear-gradient(-45deg, var(--lime), var(--lime) 10px, var(--ink) 10px, var(--ink) 20px);
    }
    .op-hero-header-row {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      flex-wrap: wrap;
      gap: 14px;
    }
    .op-hero-title {
      font-size: clamp(1.6rem, 3.8vw, 2.4rem);
      font-weight: 900;
      text-transform: uppercase;
      margin: 6px 0 6px;
      letter-spacing: -0.03em;
      line-height: 1.05;
      color: var(--ink);
    }
    .op-hero-desc {
      font-size: 0.92rem;
      color: #2e443e;
      margin: 0 0 14px;
      line-height: 1.45;
      max-width: 620px;
    }
    .op-hero-tags {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
    }
    .op-hero-tags .badge-streetside {
      font-size: 0.74rem;
      padding: 4px 10px;
      font-weight: 800;
    }

    /* Filter & Search Bar */
    .op-filter-strip {
      background: var(--sand);
      border: 2px solid var(--ink);
      border-radius: 14px;
      padding: 12px 14px;
      box-shadow: 3.5px 3.5px 0 var(--ink);
      margin-bottom: 20px;
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
    }
    .op-search-box {
      position: relative;
      flex: 1;
      min-width: 240px;
      display: flex;
      align-items: center;
    }
    .op-search-box i.search-icon {
      position: absolute;
      left: 12px;
      color: var(--ink);
      font-size: 0.88rem;
    }
    .op-search-input {
      width: 100%;
      padding: 8px 32px 8px 34px;
      border: 2px solid var(--ink);
      border-radius: 10px;
      background: var(--white);
      font-family: inherit;
      font-size: 0.82rem;
      font-weight: 700;
      outline: none;
      transition: all 0.15s ease;
    }
    .op-search-input:focus {
      box-shadow: 2px 2px 0 var(--coral);
      border-color: var(--ink);
    }
    .op-search-clear {
      position: absolute;
      right: 10px;
      background: none;
      border: none;
      font-size: 1.1rem;
      line-height: 1;
      cursor: pointer;
      color: #666;
      display: none;
    }
    .op-date-pills {
      display: flex;
      align-items: center;
      gap: 6px;
      overflow-x: auto;
      scrollbar-width: none;
      -webkit-overflow-scrolling: touch;
      padding: 2px 0;
    }
    .op-date-pills::-webkit-scrollbar {
      display: none;
    }
    .op-date-pill {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      padding: 6px 12px;
      font-size: 0.74rem;
      font-family: 'DM Mono', monospace;
      font-weight: 800;
      border: 1.5px solid var(--ink);
      border-radius: 999px;
      background: var(--white);
      color: var(--ink);
      cursor: pointer;
      box-shadow: 1.5px 1.5px 0 var(--ink);
      white-space: nowrap;
      transition: all 0.12s ease;
    }
    .op-date-pill:hover,
    .op-date-pill.active {
      background: var(--lime);
      transform: translate(-1px, -1px);
      box-shadow: 2.5px 2.5px 0 var(--ink);
    }

    /* Results Header / Meta */
    .op-results-meta {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 14px;
      font-size: 0.78rem;
      font-weight: 800;
      color: #3f514b;
    }

    /* Sessions Grid */
    .op-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(310px, 1fr));
      gap: 18px;
      margin-bottom: 30px;
    }

    /* Session Card */
    .op-card {
      background: var(--white);
      border: 2.5px solid var(--ink);
      border-radius: 16px;
      box-shadow: 4px 4px 0 var(--ink);
      padding: 18px 16px;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      transition: transform 0.15s ease, box-shadow 0.15s ease;
      position: relative;
    }
    .op-card:hover {
      transform: translateY(-2px);
      box-shadow: 6px 6px 0 var(--ink);
    }
    .op-card-top {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 10px;
      flex-wrap: wrap;
      gap: 6px;
    }
    .op-card-title {
      font-size: 1.15rem;
      font-weight: 850;
      text-transform: uppercase;
      margin: 0 0 6px;
      letter-spacing: -0.02em;
      line-height: 1.2;
      color: var(--ink);
    }
    .op-card-venue {
      font-size: 0.80rem;
      color: #4a5c56;
      margin-bottom: 12px;
      line-height: 1.35;
      font-weight: 600;
    }
    .op-card-venue a {
      color: var(--ink);
      text-decoration: underline;
      font-weight: 700;
    }
    .op-card-venue i {
      color: var(--coral);
    }

    /* Schedule Box */
    .op-schedule-box {
      background: var(--sand);
      border: 1.5px solid var(--ink);
      border-radius: 10px;
      padding: 8px 12px;
      margin-bottom: 12px;
      font-size: 0.80rem;
      display: flex;
      flex-direction: column;
      gap: 4px;
    }
    .op-sched-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 8px;
    }
    .op-sched-row i {
      color: var(--coral);
      margin-right: 4px;
    }

    /* Capacity Progress Bar */
    .op-capacity-wrap {
      margin-bottom: 14px;
    }
    .op-capacity-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      font-size: 0.72rem;
      font-family: 'DM Mono', monospace;
      font-weight: 800;
      margin-bottom: 4px;
      color: var(--ink);
    }
    .op-progress-track {
      height: 7px;
      background: #e2e8e5;
      border: 1.5px solid var(--ink);
      border-radius: 999px;
      overflow: hidden;
      position: relative;
    }
    .op-progress-fill {
      height: 100%;
      border-radius: 999px;
      transition: width 0.3s ease;
    }

    /* Card Footer */
    .op-card-footer {
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-top: 1.5px dashed var(--ink);
      padding-top: 12px;
      margin-top: 4px;
      gap: 10px;
    }
    .op-price-block span {
      display: block;
      font-size: 0.62rem;
      font-family: 'DM Mono', monospace;
      font-weight: 800;
      color: #556b63;
      line-height: 1;
      margin-bottom: 2px;
    }
    .op-price-block strong {
      font-size: 1.3rem;
      font-weight: 900;
      font-family: 'DM Mono', monospace;
      color: var(--coral);
      line-height: 1;
    }
    .op-action-btn {
      flex: 1;
      max-width: 170px;
      padding: 9px 12px;
      font-size: 0.82rem;
      font-weight: 800;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
      text-decoration: none;
      white-space: nowrap;
      border-radius: 10px;
    }

    /* "How Open Play Works" 3-Card Strip */
    .op-how-section {
      background: var(--cream);
      border: 2px solid var(--ink);
      border-radius: 18px;
      padding: 22px 20px;
      box-shadow: 4px 4px 0 var(--ink);
      margin-bottom: 24px;
    }
    .op-how-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 14px;
      margin-top: 14px;
    }
    .op-how-card {
      background: var(--white);
      border: 1.5px solid var(--ink);
      border-radius: 12px;
      padding: 14px 12px;
      box-shadow: 2px 2px 0 var(--ink);
    }
    .op-how-num {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 28px;
      height: 28px;
      border-radius: 8px;
      border: 1.5px solid var(--ink);
      font-size: 0.82rem;
      font-family: 'DM Mono', monospace;
      font-weight: 900;
      margin-bottom: 8px;
      box-shadow: 1.5px 1.5px 0 var(--ink);
    }
    .op-how-card h4 {
      font-size: 0.92rem;
      font-weight: 800;
      text-transform: uppercase;
      margin: 0 0 4px;
    }
    .op-how-card p {
      font-size: 0.78rem;
      color: #3a4d46;
      margin: 0;
      line-height: 1.35;
    }

    /* Host Callout Banner */
    .op-host-banner {
      background: var(--white);
      border: 2px dashed var(--ink);
      border-radius: 14px;
      padding: 16px 18px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 12px;
      margin-bottom: 10px;
    }

    /* Neo-Brutalist Empty State */
    .op-empty-card {
      background: var(--white);
      border: 2.5px solid var(--ink);
      border-radius: 16px;
      box-shadow: 5px 5px 0 var(--ink);
      padding: 34px 20px;
      text-align: center;
      grid-column: 1 / -1;
    }
    .op-empty-icon {
      width: 64px;
      height: 64px;
      border-radius: 18px;
      background: #ffeae6;
      border: 2px solid var(--ink);
      box-shadow: 3px 3px 0 var(--coral);
      display: inline-flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 14px;
      font-size: 1.8rem;
      color: var(--coral);
    }

    /* Mobile Responsive Optimizations */
    @media (max-width: 768px) {
      #page-content {
        padding: 78px 10px 24px !important;
      }

      /* Compact Hero Banner */
      .op-hero-banner {
        padding: 13px 12px 14px !important;
        border-radius: 12px !important;
        border-width: 2px !important;
        box-shadow: 3px 3px 0 var(--ink) !important;
        margin-bottom: 12px !important;
      }
      .op-hero-banner::before {
        height: 4px !important;
      }
      .op-hero-title {
        font-size: 1.28rem !important;
        margin: 3px 0 4px !important;
        letter-spacing: -0.02em !important;
      }
      .op-hero-desc {
        font-size: 0.78rem !important;
        margin-bottom: 8px !important;
        line-height: 1.35 !important;
        color: #3b5048 !important;
      }
      .op-hero-tags {
        display: flex !important;
        flex-wrap: nowrap !important;
        overflow-x: auto !important;
        -webkit-overflow-scrolling: touch !important;
        scrollbar-width: none !important;
        gap: 5px !important;
        padding-bottom: 3px !important;
        margin-bottom: 2px !important;
      }
      .op-hero-tags::-webkit-scrollbar {
        display: none !important;
      }
      .op-hero-tags .badge-streetside {
        font-size: 0.65rem !important;
        padding: 3px 8px !important;
        white-space: nowrap !important;
        flex-shrink: 0 !important;
      }
      .op-hero-header-row {
        flex-direction: column !important;
        align-items: stretch !important;
        gap: 8px !important;
      }
      .op-hero-actions {
        width: 100% !important;
        margin-top: 2px !important;
      }
      .op-hero-actions a {
        width: 100% !important;
        justify-content: center !important;
        padding: 7px 12px !important;
        font-size: 0.76rem !important;
        border-radius: 8px !important;
      }

      /* Compact Filter & Search Bar */
      .op-filter-strip {
        padding: 8px 8px !important;
        border-radius: 10px !important;
        border-width: 1.5px !important;
        box-shadow: 2.5px 2.5px 0 var(--ink) !important;
        margin-bottom: 10px !important;
        gap: 6px !important;
      }
      .op-search-box {
        min-width: 100% !important;
        width: 100% !important;
      }
      .op-search-box i.search-icon {
        left: 9px !important;
        font-size: 0.80rem !important;
      }
      .op-search-input {
        padding: 6px 26px 6px 28px !important;
        font-size: 0.76rem !important;
        border-radius: 7px !important;
        border-width: 1.5px !important;
      }
      .op-search-clear {
        right: 8px !important;
        font-size: 1.0rem !important;
      }
      .op-date-pills {
        width: 100% !important;
        display: flex !important;
        flex-wrap: nowrap !important;
        overflow-x: auto !important;
        -webkit-overflow-scrolling: touch !important;
        scrollbar-width: none !important;
        gap: 5px !important;
        padding: 2px 1px !important;
      }
      .op-date-pills::-webkit-scrollbar {
        display: none !important;
      }
      .op-date-pill {
        padding: 4px 9px !important;
        font-size: 0.67rem !important;
        white-space: nowrap !important;
        flex-shrink: 0 !important;
        border-radius: 999px !important;
        box-shadow: 1px 1px 0 var(--ink) !important;
      }
      .op-date-pill:hover,
      .op-date-pill.active {
        box-shadow: 1.5px 1.5px 0 var(--ink) !important;
      }

      /* Results Header / Meta */
      .op-results-meta {
        margin-bottom: 8px !important;
        font-size: 0.70rem !important;
      }

      /* Compact Cards */
      .op-grid {
        grid-template-columns: 1fr !important;
        gap: 10px !important;
        margin-bottom: 16px !important;
      }
      .op-card {
        padding: 11px 11px 10px !important;
        border-radius: 11px !important;
        border-width: 2px !important;
        box-shadow: 2.5px 2.5px 0 var(--ink) !important;
      }
      .op-card-top {
        margin-bottom: 6px !important;
      }
      .op-card-top .badge-streetside {
        font-size: 0.62rem !important;
        padding: 2px 6px !important;
      }
      .op-card-title {
        font-size: 0.98rem !important;
        line-height: 1.2 !important;
        margin-bottom: 3px !important;
      }
      .op-card-venue {
        font-size: 0.72rem !important;
        margin-bottom: 7px !important;
        line-height: 1.25 !important;
      }
      .op-schedule-box {
        padding: 6px 8px !important;
        font-size: 0.72rem !important;
        border-radius: 8px !important;
        border-width: 1px !important;
        margin-bottom: 7px !important;
        gap: 3px !important;
      }
      .op-sched-row {
        gap: 6px !important;
      }
      .op-capacity-wrap {
        margin-bottom: 7px !important;
      }
      .op-capacity-header {
        font-size: 0.64rem !important;
        margin-bottom: 3px !important;
      }
      .op-progress-track {
        height: 5px !important;
        border-width: 1px !important;
      }
      .op-card-footer {
        padding-top: 7px !important;
        margin-top: 2px !important;
        gap: 8px !important;
      }
      .op-price-block span {
        font-size: 0.58rem !important;
        margin-bottom: 1px !important;
      }
      .op-price-block strong {
        font-size: 1.15rem !important;
      }
      .op-action-btn {
        max-width: none !important;
        padding: 7px 12px !important;
        font-size: 0.76rem !important;
        border-radius: 8px !important;
      }

      /* Compact How Section - Sleek Row Stacking */
      .op-how-section {
        padding: 12px 10px !important;
        border-radius: 11px !important;
        border-width: 1.5px !important;
        box-shadow: 2.5px 2.5px 0 var(--ink) !important;
        margin-bottom: 12px !important;
      }
      .op-how-section h3 {
        font-size: 0.95rem !important;
      }
      .op-how-grid {
        display: flex !important;
        flex-direction: column !important;
        gap: 6px !important;
        margin-top: 8px !important;
      }
      .op-how-card {
        display: flex !important;
        align-items: flex-start !important;
        gap: 8px !important;
        padding: 7px 8px !important;
        border-radius: 8px !important;
        border-width: 1px !important;
        box-shadow: 1px 1px 0 var(--ink) !important;
      }
      .op-how-num {
        width: 22px !important;
        height: 22px !important;
        min-width: 22px !important;
        font-size: 0.70rem !important;
        border-radius: 6px !important;
        margin-bottom: 0 !important;
        box-shadow: 1px 1px 0 var(--ink) !important;
      }
      .op-how-card-body {
        flex: 1 !important;
      }
      .op-how-card h4 {
        font-size: 0.78rem !important;
        margin: 0 0 1px !important;
      }
      .op-how-card p {
        font-size: 0.69rem !important;
        line-height: 1.25 !important;
      }

      /* Compact Host Banner */
      .op-host-banner {
        padding: 10px 10px !important;
        border-radius: 10px !important;
        margin-bottom: 6px !important;
        flex-direction: column !important;
        align-items: stretch !important;
        text-align: center !important;
        gap: 8px !important;
      }
      .op-host-banner .host-title {
        font-size: 0.82rem !important;
      }
      .op-host-banner .host-desc {
        font-size: 0.70rem !important;
      }
      .op-host-banner a {
        width: 100% !important;
        justify-content: center !important;
        padding: 7px 12px !important;
        font-size: 0.74rem !important;
        border-radius: 8px !important;
      }
    }
  </style>
</head>
<body>

  <?php require_once __DIR__ . '/../includes/header.php'; ?>

  <div id="page-content">
    <div style="max-width:1160px; margin:0 auto;">

      <!-- 1. HERO BANNER -->
      <div class="op-hero-banner">
        <div class="op-hero-header-row">
          <div>
            <span class="badge-streetside lime" style="font-size:0.70rem; padding:3px 9px; letter-spacing:0.04em;">
              <span class="pulse-dot"></span> SOCIAL PICKLEBALL &bull; BOHOL
            </span>
            <h1 class="op-hero-title">OPEN PLAY SESSIONS</h1>
            <p class="op-hero-desc">
              Drop-in social games with fair paddle-stack rotation. No partner required — pay a fixed pass, hit the court, and meet local pickleball players!
            </p>
            <div class="op-hero-tags">
              <span class="badge-streetside sand"><i class="bi bi-arrow-repeat"></i> Rotating Doubles</span>
              <span class="badge-streetside sand"><i class="bi bi-tag-fill"></i> Fixed Entry Pass</span>
              <span class="badge-streetside sand"><i class="bi bi-check2-all"></i> All Skill Levels</span>
              <span class="badge-streetside sand"><i class="bi bi-lightning-charge-fill"></i> Instant Digital Pass</span>
            </div>
          </div>
          <div class="op-hero-actions">
            <?php if ($isLoggedIn): ?>
              <a href="/pikvero/public/customer/open-play.php" class="button lime" style="display:inline-flex; align-items:center; gap:6px;">
                <i class="bi bi-ticket-perforated-fill"></i> My Open Play Passes
              </a>
            <?php else: ?>
              <a href="/pikvero/public/login.php?redirect=<?= urlencode('/pikvero/public/open-play.php') ?>" class="button coral" style="display:inline-flex; align-items:center; gap:6px;">
                <i class="bi bi-box-arrow-in-right"></i> Log In to Join Pass
              </a>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- 2. FILTER & SEARCH STRIP -->
      <div class="op-filter-strip">
        <div class="op-search-box">
          <i class="bi bi-search search-icon"></i>
          <input type="text" id="op-search-input" class="op-search-input" placeholder="Search session title, venue, or city..." autocomplete="off">
          <button type="button" id="op-search-clear" class="op-search-clear" onclick="clearOpSearch()">&times;</button>
        </div>

        <div class="op-date-pills">
          <button type="button" class="op-date-pill active" onclick="filterByDate('all', this)"><i class="bi bi-calendar3"></i> All Dates</button>
          <button type="button" class="op-date-pill" onclick="filterByDate('today', this)"><i class="bi bi-calendar-event"></i> Today</button>
          <button type="button" class="op-date-pill" onclick="filterByDate('tomorrow', this)"><i class="bi bi-calendar-plus"></i> Tomorrow</button>
          <button type="button" class="op-date-pill" onclick="filterByDate('weekend', this)"><i class="bi bi-sun-fill"></i> Weekend</button>
        </div>
      </div>

      <!-- RESULTS COUNT META -->
      <div class="op-results-meta">
        <span id="op-count-badge" class="mono" style="font-size:0.75rem; letter-spacing:0.04em;">LOADING SESSIONS...</span>
        <span id="op-active-filter-label" class="mono" style="font-size:0.70rem; color:#666;"></span>
      </div>

      <!-- 3. SESSIONS GRID -->
      <div id="sessions-grid" class="op-grid">
        <!-- Loaded dynamically via JS -->
      </div>

      <!-- 4. HOW OPEN PLAY WORKS -->
      <div class="op-how-section">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px;">
          <div>
            <span class="eyebrow" style="background:var(--lime); font-size:0.68rem;"><i class="bi bi-question-circle-fill"></i> SOCIAL FORMAT</span>
            <h3 style="font-size:1.15rem; font-weight:800; text-transform:uppercase; margin:4px 0 0;">HOW OPEN PLAY WORKS</h3>
          </div>
          <span style="font-size:0.75rem; color:#4a5c56; font-weight:700;">Simple 3-step community play</span>
        </div>
        <div class="op-how-grid">
          <div class="op-how-card">
            <div class="op-how-num" style="background:var(--lime);">1</div>
            <div class="op-how-card-body">
              <h4>Claim Entry Pass</h4>
              <p>Reserve a spot online for instant confirmation or select pay cash upon arrival at the court desk.</p>
            </div>
          </div>
          <div class="op-how-card">
            <div class="op-how-num" style="background:var(--sky);">2</div>
            <div class="op-how-card-body">
              <h4>Paddle-Stack Rotation</h4>
              <p>Place your paddle in the queue rack. Standard round-robin matches ensure every player gets fair court time.</p>
            </div>
          </div>
          <div class="op-how-card">
            <div class="op-how-num" style="background:var(--coral); color:var(--white);">3</div>
            <div class="op-how-card-body">
              <h4>Rally &amp; Socialize</h4>
              <p>Play 11 or 15-point doubles games, switch partners between games, and connect with local pickleballers.</p>
            </div>
          </div>
        </div>
      </div>

      <!-- 5. HOST FACILITY BANNER -->
      <div class="op-host-banner">
        <div>
          <div class="host-title" style="font-weight:900; font-size:0.92rem; text-transform:uppercase; color:var(--ink);">
            <i class="bi bi-building-add" style="color:var(--coral); margin-right:6px;"></i> Are You A Court Owner or Facility Manager?
          </div>
          <div class="host-desc" style="font-size:0.78rem; color:#4a5c56; margin-top:2px;">
            Host regular Open Play sessions on Pikvero to fill courts during open hours and grow your recurring player base.
          </div>
        </div>
        <a href="/pikvero/public/register.php?type=owner" class="button dark" style="padding:8px 16px; font-size:0.78rem; text-decoration:none;">
          Host Open Play Sessions &rarr;
        </a>
      </div>

    </div>
  </div>

  <div id="footer-container"></div>

  <script src="/pikvero/assets/js/core/toast.js"></script>
  <script src="/pikvero/assets/js/core/ajax.js"></script>
  <script src="/pikvero/assets/js/core/auth.js"></script>
  <script src="/pikvero/assets/js/components/navbar.js?v=<?= time() ?>"></script>
  <script src="/pikvero/assets/js/components/sidebar.js"></script>
  <script src="/pikvero/assets/js/components/footer.js?v=<?= time() ?>"></script>
  <script>
    const isLoggedIn = <?= $isLoggedIn ? 'true' : 'false' ?>;
    let allSessions = [];
    let currentFilter = 'all';
    let searchQuery = '';

    document.addEventListener('DOMContentLoaded', async () => {
      NavbarComponent.render('#navbar-container', false);
      FooterComponent.render('#footer-container', false);

      const userCtx = await AuthHelper.checkSession();
      if (userCtx && userCtx.user && typeof SidebarComponent !== 'undefined') {
        let portalType = 'customer';
        if (userCtx.role === 'court_owner') portalType = 'owner';
        else if (userCtx.role !== 'customer') portalType = 'admin';
        SidebarComponent.render('open_play', portalType);
      }

      // Search input handler
      const $searchInput = document.getElementById('op-search-input');
      const $clearBtn = document.getElementById('op-search-clear');
      if ($searchInput) {
        $searchInput.addEventListener('input', (e) => {
          searchQuery = e.target.value.trim().toLowerCase();
          if ($clearBtn) $clearBtn.style.display = searchQuery ? 'block' : 'none';
          applyFilters();
        });
      }

      loadAvailableSessions();
    });

    function clearOpSearch() {
      const $input = document.getElementById('op-search-input');
      const $clearBtn = document.getElementById('op-search-clear');
      if ($input) $input.value = '';
      if ($clearBtn) $clearBtn.style.display = 'none';
      searchQuery = '';
      applyFilters();
    }

    function filterByDate(filterKey, el) {
      currentFilter = filterKey;
      document.querySelectorAll('.op-date-pill').forEach(btn => btn.classList.remove('active'));
      if (el) el.classList.add('active');
      applyFilters();
    }

    async function loadAvailableSessions() {
      const grid = document.getElementById('sessions-grid');
      grid.innerHTML = `
        <div style="grid-column:1/-1; text-align:center; padding:45px 20px; color:#4a5c56;">
          <div class="spinner-border spinner-border-sm" role="status" style="margin-bottom:8px;"></div>
          <div style="font-family:'DM Mono', monospace; font-size:0.85rem; font-weight:800;">LOADING OPEN PLAY SESSIONS...</div>
        </div>
      `;

      try {
        const res = await Api.get('/pikvero/api/customer/open-play.php', { action: 'list' });
        if (res.success && res.data) {
          allSessions = res.data || [];
          applyFilters();
        } else {
          allSessions = [];
          applyFilters();
        }
      } catch (err) {
        console.error('Error loading sessions:', err);
        allSessions = [];
        applyFilters();
      }
    }

    function applyFilters() {
      const todayStr = new Date().toISOString().split('T')[0];
      const tomorrowObj = new Date();
      tomorrowObj.setDate(tomorrowObj.getDate() + 1);
      const tomorrowStr = tomorrowObj.toISOString().split('T')[0];

      let filtered = allSessions.filter(s => {
        // Date filter
        if (currentFilter === 'today' && s.session_date !== todayStr) return false;
        if (currentFilter === 'tomorrow' && s.session_date !== tomorrowStr) return false;
        if (currentFilter === 'weekend') {
          const sDate = new Date(s.session_date + 'T00:00:00');
          const day = sDate.getDay();
          // Saturday (6) or Sunday (0)
          if (day !== 0 && day !== 6) return false;
        }

        // Search query filter
        if (searchQuery) {
          const text = ((s.title || '') + ' ' + (s.facility_name || '') + ' ' + (s.city || '')).toLowerCase();
          if (!text.includes(searchQuery)) return false;
        }

        return true;
      });

      renderSessions(filtered);
    }

    function renderSessions(sessions) {
      const grid = document.getElementById('sessions-grid');
      const countBadge = document.getElementById('op-count-badge');
      const filterLabel = document.getElementById('op-active-filter-label');

      if (countBadge) {
        countBadge.textContent = sessions.length + ' SESSION' + (sessions.length === 1 ? '' : 'S') + ' FOUND';
      }

      if (filterLabel) {
        let label = (currentFilter === 'all') ? 'Showing all upcoming' : ('Filtered by ' + currentFilter);
        if (searchQuery) label += ` &bull; keyword "${escapeHtml(searchQuery)}"`;
        filterLabel.innerHTML = label;
      }

      if (sessions.length === 0) {
        grid.innerHTML = `
          <div class="op-empty-card">
            <div class="op-empty-icon">
              <i class="bi bi-calendar2-x-fill"></i>
            </div>
            <h3 style="font-family:'DM Sans', sans-serif; font-size:1.35rem; font-weight:900; text-transform:uppercase; margin:0 0 8px; color:var(--ink);">
              NO OPEN PLAY SESSIONS FOUND
            </h3>
            <p style="font-size:0.86rem; color:#4a5c56; max-width:440px; margin:0 auto 18px; line-height:1.45;">
              ${searchQuery || currentFilter !== 'all' ? 'No scheduled sessions match your current filter criteria. Try clearing search or selecting All Dates.' : 'There are currently no Open Play sessions scheduled on the calendar. Check back soon for new games!'}
            </p>
            <div style="display:flex; justify-content:center; gap:8px; flex-wrap:wrap;">
              ${searchQuery || currentFilter !== 'all' ? '<button type="button" onclick="resetAllFilters()" class="button sand" style="padding:8px 16px; font-size:0.80rem;"><i class="bi bi-arrow-counterclockwise"></i> Reset Filters</button>' : ''}
              <a href="/pikvero/public/search.php" class="button coral" style="padding:8px 16px; font-size:0.80rem; text-decoration:none;">
                <i class="bi bi-search"></i> Book A Regular Court
              </a>
            </div>
          </div>
        `;
        return;
      }

      grid.innerHTML = sessions.map(s => {
        const regCount = parseInt(s.registered_players || 0, 10);
        const maxCap = parseInt(s.max_players || 16, 10);
        const slotsLeft = Math.max(0, maxCap - regCount);
        const isFull = slotsLeft <= 0 || s.status === 'full';
        const isRegistered = parseInt(s.is_user_registered || 0) > 0;
        const pctFilled = Math.min(100, Math.round((regCount / maxCap) * 100));

        let statusBadgeCls = 'lime';
        let statusBadgeText = slotsLeft + ' SLOTS LEFT';

        if (isRegistered) {
          statusBadgeCls = 'sky';
          statusBadgeText = '<i class="bi bi-check-circle-fill"></i> REGISTERED';
        } else if (isFull) {
          statusBadgeCls = 'coral';
          statusBadgeText = 'SESSION FULL';
        } else if (slotsLeft <= 3) {
          statusBadgeCls = 'coral';
          statusBadgeText = '<i class="bi bi-fire"></i> ONLY ' + slotsLeft + ' LEFT';
        }

        // Progress color
        let progColor = 'var(--green)';
        if (pctFilled >= 90) progColor = 'var(--coral)';
        else if (pctFilled >= 60) progColor = '#f59e0b';

        // Format dates
        const formattedDate = formatHumanDate(s.session_date);
        const formattedTime = format12HourTime(s.start_time, s.end_time);
        const durationHrs = getDurationHours(s.start_time, s.end_time);

        let actionBtnHtml = `
          <button type="button" onclick="handleReserveClick(${s.id})" ${isFull ? 'disabled' : ''} class="button ${isFull ? 'sand' : 'coral'} op-action-btn">
            ${isFull ? 'Session Full' : '<i class="bi bi-ticket-fill"></i> Join Pass &rarr;'}
          </button>
        `;

        if (isRegistered) {
          actionBtnHtml = `
            <a href="/pikvero/public/customer/open-play.php" class="button sky op-action-btn" style="background:#e0f2fe; color:#0369a1; border-color:#0284c7;">
              <i class="bi bi-ticket-detailed-fill"></i> View Pass
            </a>
          `;
        }

        return `
          <div class="op-card">
            <div>
              <div class="op-card-top">
                <span class="badge-streetside coral" style="font-size:0.65rem; padding:2px 7px;">OPEN PLAY</span>
                <span class="badge-streetside ${statusBadgeCls}" style="font-size:0.65rem; padding:2px 8px; font-weight:800;">
                  ${statusBadgeText}
                </span>
              </div>

              <h3 class="op-card-title">${escapeHtml(s.title)}</h3>
              
              <div class="op-card-venue">
                <i class="bi bi-geo-alt-fill"></i>
                <a href="/pikvero/public/facility.php?id=${s.facility_id}">${escapeHtml(s.facility_name)}</a>
                &bull; <span>${escapeHtml(s.city || 'Bohol')}</span>
              </div>

              <div class="op-schedule-box">
                <div class="op-sched-row">
                  <div><i class="bi bi-calendar-event"></i> <strong>${formattedDate}</strong></div>
                  ${durationHrs ? `<span class="badge-streetside sand" style="font-size:0.60rem; padding:1px 5px;">${durationHrs}h Play</span>` : ''}
                </div>
                <div class="op-sched-row" style="color:#2f463f; font-family:'DM Mono', monospace; font-weight:700;">
                  <div><i class="bi bi-clock-history"></i> ${formattedTime}</div>
                </div>
              </div>

              <div class="op-capacity-wrap">
                <div class="op-capacity-header">
                  <span><i class="bi bi-people-fill"></i> CAPACITY</span>
                  <span>${regCount} / ${maxCap} Players (${pctFilled}%)</span>
                </div>
                <div class="op-progress-track">
                  <div class="op-progress-fill" style="width:${pctFilled}%; background:${progColor};"></div>
                </div>
              </div>
            </div>

            <div>
              <div class="op-card-footer">
                <div class="op-price-block">
                  <span>ENTRY PASS</span>
                  <strong>₱${parseFloat(s.fee_per_player || 70).toFixed(2)}</strong>
                </div>
                ${actionBtnHtml}
              </div>
            </div>
          </div>
        `;
      }).join('');
    }

    function resetAllFilters() {
      currentFilter = 'all';
      searchQuery = '';
      const $input = document.getElementById('op-search-input');
      const $clearBtn = document.getElementById('op-search-clear');
      if ($input) $input.value = '';
      if ($clearBtn) $clearBtn.style.display = 'none';
      document.querySelectorAll('.op-date-pill').forEach(btn => {
        btn.classList.toggle('active', btn.getAttribute('onclick').includes("'all'"));
      });
      applyFilters();
    }

    function formatHumanDate(dateStr) {
      if (!dateStr) return '';
      try {
        const parts = dateStr.split('-');
        if (parts.length === 3) {
          const d = new Date(parts[0], parts[1] - 1, parts[2]);
          const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
          const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
          return `${days[d.getDay()]}, ${months[d.getMonth()]} ${d.getDate()}`;
        }
      } catch (e) {}
      return dateStr;
    }

    function format12HourTime(startStr, endStr) {
      function to12(t) {
        if (!t) return '';
        const parts = t.split(':');
        let h = parseInt(parts[0], 10);
        const m = parts[1] || '00';
        const ampm = h >= 12 ? 'PM' : 'AM';
        h = h % 12;
        if (h === 0) h = 12;
        return `${h}:${m} ${ampm}`;
      }
      return `${to12(startStr)} – ${to12(endStr)}`;
    }

    function getDurationHours(startStr, endStr) {
      if (!startStr || !endStr) return null;
      try {
        const sH = parseInt(startStr.split(':')[0], 10) + parseInt(startStr.split(':')[1] || '0', 10)/60;
        const eH = parseInt(endStr.split(':')[0], 10) + parseInt(endStr.split(':')[1] || '0', 10)/60;
        const diff = eH - sH;
        return diff > 0 ? diff.toFixed(diff % 1 === 0 ? 0 : 1) : null;
      } catch(e) { return null; }
    }

    function handleReserveClick(sessionId) {
      if (!isLoggedIn) {
        Toast.error('Login Required', 'Please log in to your account to reserve or join an Open Play session.');
        setTimeout(() => {
          window.location.href = '/pikvero/public/login.php?redirect=' + encodeURIComponent(window.location.pathname);
        }, 1200);
        return;
      }

      window.location.href = `/pikvero/public/customer/open-play.php?join_session_id=${sessionId}`;
    }

    function escapeHtml(str) {
      if (!str) return '';
      return String(str).replace(/[&<>"']/g, m => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
      })[m]);
    }
  </script>
</body>
</html>
