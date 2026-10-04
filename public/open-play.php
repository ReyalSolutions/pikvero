<?php
/**
 * Pikvero — Open Play Social Pickleball Sessions
 * Premium high-impact Streetside UI with bg-search.png hero design
 */
require_once __DIR__ . '/../app/bootstrap.php';
use App\Core\Auth\Auth;

// Calculate dynamic base URL and base path
$reqUri   = $_SERVER['REQUEST_URI'] ?? '/';
$basePath = (strpos($reqUri, '/pikvero') === 0) ? '/pikvero' : '';

$isLoggedIn = class_exists(Auth::class) ? Auth::check() : false;
$user = $isLoggedIn ? Auth::user() : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
  <title>Pikvero — Open Play Social Pickleball Sessions</title>
  <meta name="description" content="View and join social pickleball open play sessions across Bohol. Rotating doubles, fixed per-player pass, all skill levels welcome.">
  
  <link rel="icon" type="image/png" href="<?= $basePath ?>/assets/images/logo.png">
  <link rel="shortcut icon" type="image/png" href="<?= $basePath ?>/assets/images/logo.png">
  
  <!-- Google Fonts: Plus Jakarta Sans, Outfit, DM Mono -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@500;700&family=Outfit:wght@400;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="<?= $basePath ?>/assets/css/toast.css">
  
  <style>
    :root {
      --ink: #0c1a15;
      --dark-green: #071711;
      --coral: #ff5733;
      --coral-hover: #e04422;
      --lime: #d6f827;
      --lime-hover: #c4e61b;
      --sand: #f8fafc;
      --border-soft: rgba(0, 0, 0, 0.08);
      --card-bg: rgba(255, 255, 255, 0.92);
      --emerald: #10b981;
      --sky: #0284c7;
    }

    *, *::before, *::after {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      font-family: 'Plus Jakarta Sans', sans-serif;
      color: var(--ink);
      min-height: 100vh;
      overflow-x: hidden;
      position: relative;
      background: #f8fafc url('<?= $basePath ?>/assets/images/bg-search.png') no-repeat center top;
      background-size: cover;
      background-attachment: fixed;
    }

    body::before {
      content: "";
      position: fixed;
      inset: 0;
      background: linear-gradient(180deg, 
        rgba(255, 255, 255, 0.35) 0%, 
        rgba(255, 255, 255, 0.15) 35%, 
        rgba(255, 255, 255, 0.35) 100%
      );
      pointer-events: none;
      z-index: 0;
    }

    .op-main-container {
      position: relative;
      z-index: 1;
      width: 100%;
      max-width: 1220px;
      margin: 0 auto;
      padding: 104px 20px 80px;
      display: flex;
      flex-direction: column;
    }

    /* ── 1. Hero Glass Banner Card ─────────────────────────────────────────── */
    .op-hero-card {
      position: relative;
      background-color: #ffffff;
      background-image: 
        linear-gradient(90deg, 
          rgba(255, 255, 255, 0.98) 0%, 
          rgba(255, 255, 255, 0.94) 40%, 
          rgba(255, 255, 255, 0.55) 60%, 
          rgba(255, 255, 255, 0.15) 80%, 
          rgba(255, 255, 255, 0.04) 100%
        ),
        url('<?= $basePath ?>/assets/images/bg-search.png');
      background-position: right 40% center;
      background-size: cover;
      background-repeat: no-repeat;
      backdrop-filter: blur(16px);
      -webkit-backdrop-filter: blur(16px);
      border: 1.5px solid rgba(255, 255, 255, 0.95);
      border-radius: 28px;
      box-shadow: 0 18px 45px -10px rgba(15, 23, 42, 0.12);
      padding: 34px 40px;
      margin-bottom: 22px;
      display: flex;
      flex-direction: column;
      overflow: hidden;
    }

    .op-hero-top-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
      flex-wrap: wrap;
      margin-bottom: 16px;
    }

    .op-eyebrow-badge {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: var(--lime);
      color: var(--ink);
      border-radius: 9999px;
      padding: 6px 14px;
      font-family: 'DM Mono', monospace;
      font-size: 0.78rem;
      font-weight: 800;
      letter-spacing: 0.04em;
      text-transform: uppercase;
      box-shadow: 0 2px 10px rgba(214, 248, 39, 0.45);
    }

    .op-eyebrow-badge .pulse-dot {
      width: 8px;
      height: 8px;
      border-radius: 50%;
      background: #10b981;
      box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.3);
      animation: pulseDot 2s infinite ease-in-out;
    }

    @keyframes pulseDot {
      0%, 100% { transform: scale(0.95); opacity: 0.85; }
      50% { transform: scale(1.3); opacity: 1; }
    }

    .btn-hero-cta {
      background: var(--coral);
      color: #ffffff;
      padding: 9px 20px;
      border-radius: 9999px;
      font-family: 'Outfit', sans-serif;
      font-weight: 800;
      font-size: 0.88rem;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 7px;
      box-shadow: 0 4px 14px rgba(255, 87, 51, 0.35);
      transition: all 0.2s ease;
    }

    .btn-hero-cta:hover {
      background: var(--coral-hover);
      transform: translateY(-1px);
      box-shadow: 0 6px 18px rgba(255, 87, 51, 0.5);
    }

    .btn-hero-cta.logged-in {
      background: var(--lime);
      color: var(--ink);
      box-shadow: 0 4px 14px rgba(214, 248, 39, 0.4);
    }

    .btn-hero-cta.logged-in:hover {
      background: var(--lime-hover);
      box-shadow: 0 6px 18px rgba(214, 248, 39, 0.6);
    }

    .op-hero-title {
      font-family: 'Outfit', sans-serif;
      font-size: clamp(2.2rem, 4.4vw, 3.4rem);
      line-height: 1.02;
      letter-spacing: -0.04em;
      text-transform: uppercase;
      margin-bottom: 10px;
    }

    .op-hero-title .title-dark {
      color: var(--ink);
      font-weight: 900;
    }

    .op-hero-title .title-coral {
      color: var(--coral);
      font-weight: 900;
    }

    .op-hero-desc {
      font-size: 0.95rem;
      line-height: 1.55;
      color: #334155;
      max-width: 620px;
      margin-bottom: 20px;
      font-weight: 500;
    }

    .op-benefits-row {
      display: flex;
      align-items: center;
      gap: 10px;
      flex-wrap: wrap;
    }

    .benefit-pill {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: rgba(255, 255, 255, 0.95);
      border: 1.5px solid rgba(0, 0, 0, 0.08);
      padding: 6px 14px;
      border-radius: 9999px;
      font-family: 'DM Mono', monospace;
      font-size: 0.76rem;
      font-weight: 800;
      color: #1e293b;
      letter-spacing: 0.02em;
      text-transform: uppercase;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.03);
    }

    .benefit-pill i {
      color: var(--coral);
      font-size: 0.88rem;
    }

    /* ── 2. Floating Filter & Search Capsule ───────────────────────────────── */
    .op-filter-capsule {
      background: rgba(255, 255, 255, 0.92);
      backdrop-filter: blur(14px);
      -webkit-backdrop-filter: blur(14px);
      border: 1.5px solid rgba(255, 255, 255, 0.95);
      border-radius: 9999px;
      box-shadow: 0 12px 30px -8px rgba(0, 0, 0, 0.08);
      padding: 6px 8px 6px 20px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 14px;
      margin-bottom: 22px;
      flex-wrap: wrap;
    }

    .op-search-wrap {
      flex: 1;
      min-width: 240px;
      display: flex;
      align-items: center;
      gap: 10px;
      position: relative;
    }

    .op-search-wrap i.search-icon {
      color: #64748b;
      font-size: 1.05rem;
    }

    .op-search-input {
      width: 100%;
      border: none;
      background: transparent;
      font-family: 'Plus Jakarta Sans', sans-serif;
      font-size: 0.92rem;
      font-weight: 500;
      color: var(--ink);
      outline: none;
    }

    .op-search-input::placeholder {
      color: #94a3b8;
    }

    .op-clear-btn {
      display: none;
      background: none;
      border: none;
      font-size: 1.3rem;
      color: #94a3b8;
      cursor: pointer;
      padding: 0 6px;
      line-height: 1;
    }

    .op-date-pills-wrap {
      display: flex;
      align-items: center;
      gap: 8px;
      flex-wrap: wrap;
    }

    .op-date-pill {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 8px 16px;
      border-radius: 9999px;
      font-family: 'Plus Jakarta Sans', sans-serif;
      font-size: 0.82rem;
      font-weight: 700;
      background: #ffffff;
      color: #1e293b;
      border: 1.5px solid rgba(0, 0, 0, 0.12);
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
      cursor: pointer;
      transition: all 0.2s ease;
      white-space: nowrap;
      user-select: none;
    }

    .op-date-pill:hover {
      background: #f1f5f9;
      transform: translateY(-1px);
    }

    .op-date-pill.active {
      background: var(--lime);
      color: var(--ink);
      border-color: var(--lime);
      box-shadow: 0 4px 12px rgba(214, 248, 39, 0.4);
      font-weight: 800;
    }

    .op-date-pill.active i {
      color: #0c1a15;
    }

    /* ── 3. Results Header Row ─────────────────────────────────────────────── */
    .op-results-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 14px;
      flex-wrap: wrap;
      margin-bottom: 20px;
      padding: 0 6px;
    }

    .op-count-badge {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      font-family: 'DM Mono', monospace;
      font-weight: 800;
      font-size: 0.92rem;
      color: var(--ink);
      letter-spacing: 0.04em;
      text-transform: uppercase;
    }

    .op-count-badge .live-dot {
      width: 10px;
      height: 10px;
      border-radius: 50%;
      background: #10b981;
      box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.25);
      animation: pulseDot 2s infinite ease-in-out;
    }

    .op-showing-select-wrap {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      font-size: 0.8rem;
      color: #475569;
      font-weight: 800;
      letter-spacing: 0.05em;
      text-transform: uppercase;
    }

    .op-showing-select-wrap select {
      background: #ffffff;
      border: 1.5px solid rgba(203, 213, 225, 0.9);
      border-radius: 9999px;
      padding: 6px 16px;
      font-family: 'Plus Jakarta Sans', sans-serif;
      font-size: 0.84rem;
      font-weight: 700;
      color: var(--ink);
      outline: none;
      cursor: pointer;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
    }

    /* ── 4. Sessions Grid & Empty State ────────────────────────────────────── */
    .op-sessions-container {
      width: 100%;
    }

    .op-empty-card {
      background: rgba(255, 255, 255, 0.78);
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      border-radius: 28px;
      border: 1.5px solid rgba(255, 255, 255, 0.95);
      box-shadow: 0 20px 45px -12px rgba(15, 23, 42, 0.08);
      padding: 64px 24px;
      text-align: center;
    }

    .op-calendar-icon-box {
      width: 74px;
      height: 74px;
      border-radius: 20px;
      background: linear-gradient(135deg, #fff1f2 0%, #ffe4e6 100%);
      border: 2px solid #fecdd3;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      color: var(--coral);
      margin-bottom: 20px;
      box-shadow: 0 8px 24px rgba(255, 87, 51, 0.15);
    }

    .op-empty-title {
      font-family: 'Outfit', sans-serif;
      font-weight: 900;
      font-size: 1.45rem;
      color: var(--ink);
      margin-bottom: 8px;
      text-transform: uppercase;
      letter-spacing: -0.02em;
    }

    .op-empty-desc {
      font-size: 0.92rem;
      color: #64748b;
      max-width: 460px;
      margin: 0 auto 22px;
      line-height: 1.55;
    }

    .btn-book-regular {
      background: var(--coral);
      color: #ffffff;
      padding: 12px 28px;
      border-radius: 9999px;
      font-family: 'Outfit', sans-serif;
      font-weight: 800;
      font-size: 0.92rem;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      box-shadow: 0 4px 14px rgba(255, 87, 51, 0.4);
      transition: all 0.2s ease;
    }

    .btn-book-regular:hover {
      background: var(--coral-hover);
      transform: translateY(-1px);
      box-shadow: 0 6px 18px rgba(255, 87, 51, 0.55);
    }

    /* Populated Sessions Grid */
    .op-sessions-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
      gap: 24px;
    }

    .op-session-card {
      background: rgba(255, 255, 255, 0.94);
      backdrop-filter: blur(12px);
      border-radius: 20px;
      border: 1.5px solid rgba(255, 255, 255, 0.95);
      box-shadow: 0 10px 30px -8px rgba(0, 0, 0, 0.08);
      padding: 22px;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      transition: all 0.25s ease;
    }

    .op-session-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 16px 36px -10px rgba(0, 0, 0, 0.14);
    }

    .op-card-top-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 10px;
    }

    .op-badge-tag {
      font-family: 'DM Mono', monospace;
      font-size: 0.68rem;
      font-weight: 800;
      padding: 3px 8px;
      border-radius: 6px;
      text-transform: uppercase;
    }

    .op-badge-tag.coral { background: #ffe4e6; color: var(--coral); }
    .op-badge-tag.lime { background: #ecfccb; color: #4d7c0f; }
    .op-badge-tag.sky { background: #e0f2fe; color: var(--sky); }

    .op-card-session-title {
      font-family: 'Outfit', sans-serif;
      font-weight: 800;
      font-size: 1.25rem;
      color: var(--ink);
      text-transform: uppercase;
      margin-bottom: 6px;
    }

    .op-card-venue-row {
      display: flex;
      align-items: center;
      gap: 6px;
      font-size: 0.84rem;
      color: #64748b;
      margin-bottom: 14px;
      font-weight: 600;
    }

    .op-card-venue-row i { color: var(--coral); }

    .op-sched-box {
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      border-radius: 12px;
      padding: 10px 14px;
      margin-bottom: 14px;
      font-size: 0.82rem;
    }

    .op-sched-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 4px;
    }

    .op-sched-row:last-child { margin-bottom: 0; }

    .op-capacity-wrap {
      margin-bottom: 18px;
    }

    .op-capacity-header {
      display: flex;
      justify-content: space-between;
      font-size: 0.76rem;
      font-weight: 700;
      font-family: 'DM Mono', monospace;
      color: #64748b;
      margin-bottom: 6px;
    }

    .op-progress-track {
      width: 100%;
      height: 8px;
      background: #e2e8f0;
      border-radius: 999px;
      overflow: hidden;
    }

    .op-progress-fill {
      height: 100%;
      border-radius: 999px;
      transition: width 0.3s;
    }

    .op-card-footer {
      display: flex;
      align-items: center;
      justify-content: space-between;
      border-top: 1.5px solid #f1f5f9;
      padding-top: 14px;
      margin-top: auto;
    }

    .op-price-wrap span {
      display: block;
      font-family: 'DM Mono', monospace;
      font-size: 0.62rem;
      font-weight: 700;
      color: #94a3b8;
    }

    .op-price-wrap strong {
      font-family: 'Outfit', sans-serif;
      font-size: 1.25rem;
      font-weight: 900;
      color: var(--ink);
    }

    .btn-join-pass {
      background: var(--coral);
      color: #fff;
      border: none;
      border-radius: 9999px;
      padding: 8px 18px;
      font-family: 'Outfit', sans-serif;
      font-weight: 800;
      font-size: 0.85rem;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      box-shadow: 0 4px 12px rgba(255, 87, 51, 0.35);
      transition: all 0.2s;
    }

    .btn-join-pass:hover {
      background: var(--coral-hover);
      transform: translateY(-1px);
    }

    /* ── 5. Host Facility Banner ───────────────────────────────────────────── */
    .op-host-card {
      background: rgba(255, 255, 255, 0.88);
      backdrop-filter: blur(14px);
      border-radius: 20px;
      border: 1.5px solid rgba(255, 255, 255, 0.95);
      box-shadow: 0 12px 30px -8px rgba(0, 0, 0, 0.06);
      padding: 24px 30px;
      margin-top: 34px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 20px;
      flex-wrap: wrap;
    }

    .host-title {
      font-family: 'Outfit', sans-serif;
      font-weight: 800;
      font-size: 1.1rem;
      color: var(--ink);
      text-transform: uppercase;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .host-desc {
      font-size: 0.86rem;
      color: #475569;
      margin-top: 4px;
    }

    .btn-host-cta {
      background: #0c1a15;
      color: #ffffff;
      border-radius: 12px;
      padding: 10px 20px;
      font-family: 'Outfit', sans-serif;
      font-weight: 800;
      font-size: 0.86rem;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      box-shadow: 2px 2px 0 #000;
      transition: all 0.15s;
    }

    .btn-host-cta:hover {
      background: #1e293b;
      transform: translateY(-1px);
      box-shadow: 3px 3px 0 #000;
    }

    /* Responsive */
    @media (max-width: 768px) {
      .op-main-container {
        padding: 94px 14px 60px;
      }
      .op-hero-card {
        padding: 22px 20px;
      }
      .op-hero-title {
        font-size: 2.1rem;
      }
      .op-filter-capsule {
        border-radius: 20px;
        padding: 12px;
      }
      .op-date-pills-wrap {
        width: 100%;
        overflow-x: auto;
        padding-bottom: 4px;
      }
      .op-host-card {
        flex-direction: column;
        align-items: flex-start;
      }
      .btn-host-cta {
        width: 100%;
        justify-content: center;
      }
    }
  </style>
</head>
<body>

  <!-- GLOBAL REUSABLE HEADER -->
  <?php require_once __DIR__ . '/../includes/header.php'; ?>

  <!-- MAIN PAGE CONTENT -->
  <main class="op-main-container">

    <!-- 1. HERO GLASS BANNER CARD -->
    <section class="op-hero-card">
      <div class="op-hero-top-row">
        <div class="op-eyebrow-badge">
          <span class="pulse-dot"></span> SOCIAL PICKLEBALL &bull; BOHOL
        </div>
        
        <?php if ($isLoggedIn): ?>
          <a href="<?= $basePath ?>/public/customer/open-play" class="btn-hero-cta logged-in">
            <i class="bi bi-ticket-perforated-fill"></i> My Open Play Passes
          </a>
        <?php else: ?>
          <a href="<?= $basePath ?>/public/login?redirect=<?= urlencode($basePath . '/public/open-play') ?>" class="btn-hero-cta">
            <i class="bi bi-box-arrow-in-right"></i> Log In to Join Pass
          </a>
        <?php endif; ?>
      </div>

      <h1 class="op-hero-title">
        <span class="title-dark">OPEN PLAY</span>
        <span class="title-coral">SESSIONS</span>
      </h1>

      <p class="op-hero-desc">
        Drop-in social games with fair paddle-stack rotation. No partner required — pay a fixed pass, hit the court, and meet local pickleball players!
      </p>

      <div class="op-benefits-row">
        <span class="benefit-pill"><i class="bi bi-arrow-repeat"></i> Rotating Doubles</span>
        <span class="benefit-pill"><i class="bi bi-tag-fill"></i> Fixed Entry Pass</span>
        <span class="benefit-pill"><i class="bi bi-bar-chart-fill"></i> All Skill Levels</span>
        <span class="benefit-pill"><i class="bi bi-lightning-charge-fill"></i> Instant Digital Pass</span>
      </div>
    </section>

    <!-- 2. FLOATING FILTER & SEARCH CAPSULE -->
    <section class="op-filter-capsule">
      <div class="op-search-wrap">
        <i class="bi bi-search search-icon"></i>
        <input type="text" id="op-search-input" class="op-search-input" placeholder="Search session title, venue, or city..." autocomplete="off">
        <button type="button" id="op-search-clear" class="op-clear-btn" onclick="clearOpSearch()" aria-label="Clear Search">&times;</button>
      </div>

      <div class="op-date-pills-wrap">
        <button type="button" class="op-date-pill active" onclick="filterByDate('all', this)">
          <i class="bi bi-calendar3"></i> All Dates
        </button>
        <button type="button" class="op-date-pill" onclick="filterByDate('today', this)">
          <i class="bi bi-calendar-event"></i> Today
        </button>
        <button type="button" class="op-date-pill" onclick="filterByDate('tomorrow', this)">
          <i class="bi bi-calendar-plus"></i> Tomorrow
        </button>
        <button type="button" class="op-date-pill" onclick="filterByDate('weekend', this)">
          <i class="bi bi-sun"></i> Weekend
        </button>
      </div>
    </section>

    <!-- 3. RESULTS STATUS ROW -->
    <div class="op-results-header">
      <div class="op-count-badge" id="op-count-badge">
        <span class="live-dot"></span> <span id="op-count-text">0 SESSIONS FOUND</span>
      </div>

      <div class="op-showing-select-wrap">
        <label for="op-showing-select">SHOWING</label>
        <select id="op-showing-select" onchange="onShowingSelectChange(this)">
          <option value="all">All Upcoming</option>
          <option value="today">Today</option>
          <option value="tomorrow">Tomorrow</option>
          <option value="weekend">Weekend</option>
        </select>
      </div>
    </div>

    <!-- 4. SESSIONS CONTAINER -->
    <div class="op-sessions-container" id="sessions-grid">
      <!-- Empty state card by default / loading state -->
      <div class="op-empty-card">
        <div class="op-calendar-icon-box">
          <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="4" width="18" height="18" rx="4" ry="4"></rect>
            <line x1="16" y1="2" x2="16" y2="6"></line>
            <line x1="8" y1="2" x2="8" y2="6"></line>
            <line x1="3" y1="10" x2="21" y2="10"></line>
            <line x1="10" y1="14" x2="14" y2="18"></line>
            <line x1="14" y1="14" x2="10" y2="18"></line>
          </svg>
        </div>
        <h3 class="op-empty-title">NO OPEN PLAY SESSIONS FOUND</h3>
        <p class="op-empty-desc">
          There are currently no Open Play sessions scheduled on the calendar. Check back soon for new games!
        </p>
        <a href="<?= $basePath ?>/public/search" class="btn-book-regular">
          <i class="bi bi-search"></i> Book A Regular Court
        </a>
      </div>
    </div>

    <!-- 5. HOST FACILITY BANNER -->
    <aside class="op-host-card">
      <div>
        <div class="host-title">
          <i class="bi bi-building-add" style="color:var(--coral);"></i> Are You A Court Owner or Facility Manager?
        </div>
        <div class="host-desc">
          Host regular Open Play sessions on Pikvero to fill courts during open hours and grow your recurring player base.
        </div>
      </div>
      <a href="<?= $basePath ?>/public/register?type=owner" class="btn-host-cta">
        Host Open Play Sessions &rarr;
      </a>
    </aside>

  </main>

  <!-- GLOBAL DESKTOP FOOTER -->
  <?php require_once __DIR__ . '/../includes/footer.php'; ?>

  <!-- jQuery & Scripts -->
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="<?= $basePath ?>/assets/js/core/toast.js"></script>
  <script>
    window.APP_BASE_PATH = <?= json_encode($basePath) ?>;
    const isLoggedIn = <?= $isLoggedIn ? 'true' : 'false' ?>;
    let allSessions = [];
    let currentFilter = 'all';
    let searchQuery = '';

    document.addEventListener('DOMContentLoaded', () => {
      // Search input listener
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
      const showingSelect = document.getElementById('op-showing-select');
      if (showingSelect) showingSelect.value = filterKey;
      applyFilters();
    }

    function onShowingSelectChange(select) {
      const val = select.value;
      currentFilter = val;
      document.querySelectorAll('.op-date-pill').forEach(btn => {
        btn.classList.toggle('active', btn.getAttribute('onclick').includes(`'${val}'`));
      });
      applyFilters();
    }

    async function loadAvailableSessions() {
      const base = window.APP_BASE_PATH || '';
      try {
        const res = await $.getJSON(base + '/api/customer/open-play.php', { action: 'list' });
        if (res && res.success && Array.isArray(res.data)) {
          allSessions = res.data;
        } else {
          allSessions = [];
        }
      } catch (err) {
        allSessions = [];
      }
      applyFilters();
    }

    function applyFilters() {
      const todayStr = new Date().toISOString().split('T')[0];
      const tomorrowObj = new Date();
      tomorrowObj.setDate(tomorrowObj.getDate() + 1);
      const tomorrowStr = tomorrowObj.toISOString().split('T')[0];

      let filtered = allSessions.filter(s => {
        if (currentFilter === 'today' && s.session_date !== todayStr) return false;
        if (currentFilter === 'tomorrow' && s.session_date !== tomorrowStr) return false;
        if (currentFilter === 'weekend') {
          const sDate = new Date(s.session_date + 'T00:00:00');
          const day = sDate.getDay();
          if (day !== 0 && day !== 6) return false;
        }

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
      const countText = document.getElementById('op-count-text');
      const count = sessions ? sessions.length : 0;

      if (countText) {
        countText.textContent = `${count} ${count === 1 ? 'SESSION' : 'SESSIONS'} FOUND`;
      }

      if (count === 0) {
        const base = window.APP_BASE_PATH || '';
        grid.innerHTML = `
          <div class="op-empty-card">
            <div class="op-calendar-icon-box">
              <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="4" width="18" height="18" rx="4" ry="4"></rect>
                <line x1="16" y1="2" x2="16" y2="6"></line>
                <line x1="8" y1="2" x2="8" y2="6"></line>
                <line x1="3" y1="10" x2="21" y2="10"></line>
                <line x1="10" y1="14" x2="14" y2="18"></line>
                <line x1="14" y1="14" x2="10" y2="18"></line>
              </svg>
            </div>
            <h3 class="op-empty-title">NO OPEN PLAY SESSIONS FOUND</h3>
            <p class="op-empty-desc">
              There are currently no Open Play sessions scheduled on the calendar. Check back soon for new games!
            </p>
            <a href="${base}/public/search" class="btn-book-regular">
              <i class="bi bi-search"></i> Book A Regular Court
            </a>
          </div>
        `;
        return;
      }

      grid.innerHTML = `
        <div class="op-sessions-grid">
          ${sessions.map(s => {
            const regCount = parseInt(s.registered_players || 0, 10);
            const maxCap = parseInt(s.max_players || 16, 10);
            const slotsLeft = Math.max(0, maxCap - regCount);
            const isFull = slotsLeft <= 0 || s.status === 'full';
            const isRegistered = parseInt(s.is_user_registered || 0) > 0;
            const pctFilled = Math.min(100, Math.round((regCount / maxCap) * 100));

            let statusBadgeCls = 'lime';
            let statusBadgeText = `${slotsLeft} SLOTS LEFT`;

            if (isRegistered) {
              statusBadgeCls = 'sky';
              statusBadgeText = '<i class="bi bi-check-circle-fill"></i> REGISTERED';
            } else if (isFull) {
              statusBadgeCls = 'coral';
              statusBadgeText = 'SESSION FULL';
            } else if (slotsLeft <= 3) {
              statusBadgeCls = 'coral';
              statusBadgeText = `<i class="bi bi-fire"></i> ONLY ${slotsLeft} LEFT`;
            }

            let progColor = 'var(--emerald)';
            if (pctFilled >= 90) progColor = 'var(--coral)';
            else if (pctFilled >= 60) progColor = '#f59e0b';

            const formattedDate = formatHumanDate(s.session_date);
            const formattedTime = format12HourTime(s.start_time, s.end_time);
            const durationHrs = getDurationHours(s.start_time, s.end_time);
            const base = window.APP_BASE_PATH || '';

            let actionBtnHtml = `
              <button type="button" onclick="handleReserveClick(${s.id})" ${isFull ? 'disabled' : ''} class="btn-join-pass" ${isFull ? 'style="background:#cbd5e1; box-shadow:none; cursor:not-allowed;"' : ''}>
                ${isFull ? 'Session Full' : '<i class="bi bi-ticket-fill"></i> Join Pass &rarr;'}
              </button>
            `;

            if (isRegistered) {
              actionBtnHtml = `
                <a href="${base}/public/customer/open-play" class="btn-join-pass" style="background:#0284c7; box-shadow:0 4px 12px rgba(2,132,199,0.35);">
                  <i class="bi bi-ticket-detailed-fill"></i> View Pass
                </a>
              `;
            }

            return `
              <div class="op-session-card">
                <div>
                  <div class="op-card-top-row">
                    <span class="op-badge-tag coral">OPEN PLAY</span>
                    <span class="op-badge-tag ${statusBadgeCls}">${statusBadgeText}</span>
                  </div>

                  <h3 class="op-card-session-title">${escapeHtml(s.title || 'Social Open Play')}</h3>

                  <div class="op-card-venue-row">
                    <i class="bi bi-geo-alt-fill"></i>
                    <span>${escapeHtml(s.facility_name || 'SmashZone Center')} &bull; ${escapeHtml(s.city || 'Tagbilaran City')}</span>
                  </div>

                  <div class="op-sched-box">
                    <div class="op-sched-row">
                      <div><i class="bi bi-calendar-event"></i> <strong>${formattedDate}</strong></div>
                      ${durationHrs ? `<span style="font-family:'DM Mono', monospace; font-size:0.7rem; font-weight:700; color:#64748b;">${durationHrs}h Play</span>` : ''}
                    </div>
                    <div class="op-sched-row" style="color:#0f172a; font-family:'DM Mono', monospace; font-weight:700;">
                      <div><i class="bi bi-clock"></i> ${formattedTime}</div>
                    </div>
                  </div>

                  <div class="op-capacity-wrap">
                    <div class="op-capacity-header">
                      <span><i class="bi bi-people-fill"></i> PLAYERS</span>
                      <span>${regCount} / ${maxCap} (${pctFilled}%)</span>
                    </div>
                    <div class="op-progress-track">
                      <div class="op-progress-fill" style="width:${pctFilled}%; background:${progColor};"></div>
                    </div>
                  </div>
                </div>

                <div class="op-card-footer">
                  <div class="op-price-wrap">
                    <span>ENTRY PASS</span>
                    <strong>₱${parseFloat(s.fee_per_player || 70).toFixed(2)}</strong>
                  </div>
                  ${actionBtnHtml}
                </div>
              </div>
            `;
          }).join('')}
        </div>
      `;
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
      const base = window.APP_BASE_PATH || '';
      if (!isLoggedIn) {
        if (typeof Toast !== 'undefined') {
          Toast.error('Login Required', 'Please log in to your account to reserve or join an Open Play session.');
        } else {
          alert('Please log in to your account to reserve or join an Open Play session.');
        }
        setTimeout(() => {
          window.location.href = base + '/public/login?redirect=' + encodeURIComponent(window.location.pathname);
        }, 1000);
        return;
      }

      window.location.href = `${base}/public/customer/open-play?join_session_id=${sessionId}`;
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
