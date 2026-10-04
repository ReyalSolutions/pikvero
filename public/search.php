<?php
require_once __DIR__ . '/../app/bootstrap.php';
$facilityRepo = new \App\Infrastructure\Repositories\FacilityRepository();
$availableCities = $facilityRepo->getAllCities();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
  <title>Pikvero — Search &amp; Filter Pickleball Courts</title>
  <meta name="description" content="Search and filter pickleball courts across Bohol and the Philippines. Filter by city, court surface, lighting, and hourly rate.">
  <?php $favLogo = class_exists(\App\Core\Auth\Auth::class) ? \App\Core\Auth\Auth::getLogoUrl() : '/pikvero/assets/images/logo.png'; ?>
  <link rel="icon" type="image/png" href="<?= htmlspecialchars($favLogo) ?>?v=<?= time() ?>">
  <link rel="shortcut icon" type="image/png" href="<?= htmlspecialchars($favLogo) ?>?v=<?= time() ?>">
  <link rel="apple-touch-icon" href="<?= htmlspecialchars($favLogo) ?>?v=<?= time() ?>">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="/pikvero/assets/css/streetside-theme.css?v=3">
  <link rel="stylesheet" href="/pikvero/assets/css/toast.css">
  <style>
    /* ── Shimmer & Skeleton ────────────────────────────────────────────────── */
    @keyframes shimmer {
      0%   { background-position: -600px 0; }
      100% { background-position: 600px 0; }
    }
    .skeleton {
      background: linear-gradient(90deg, #e4e2d6 25%, #f2efe4 50%, #e4e2d6 75%);
      background-size: 600px 100%;
      animation: shimmer 1.3s infinite linear;
      border-radius: 14px;
      border: 2px solid rgba(13,33,29,0.12);
    }
    .skeleton-court { height: 320px; }

    /* ── Live Pulse ────────────────────────────────────────────────────────── */
    @keyframes pulse-dot {
      0% { transform: scale(0.95); opacity: 0.8; }
      50% { transform: scale(1.3); opacity: 1; }
      100% { transform: scale(0.95); opacity: 0.8; }
    }
    .pulse-dot {
      display: inline-block;
      width: 8px;
      height: 8px;
      background: #10b981;
      border-radius: 50%;
      margin-right: 4px;
      box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.3);
      animation: pulse-dot 2s infinite ease-in-out;
      vertical-align: middle;
    }

    /* ── Search Container & Layout ─────────────────────────────────────────── */
    .search-page-container {
      max-width: 1280px;
      margin: 0 auto;
      padding: 104px max(3vw, 18px) 60px;
      min-height: calc(100vh - 200px);
    }

    /* ── Header Wrap ───────────────────────────────────────────────────────── */
    .search-hero-bar {
      margin-bottom: 20px;
    }
    .search-hero-eyebrow {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      margin-bottom: 6px;
      font-size: 0.74rem;
      font-weight: 700;
      color: var(--green);
      letter-spacing: 0.08em;
    }
    .search-hero-title {
      font-size: clamp(1.75rem, 3.8vw, 2.5rem);
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: -0.03em;
      line-height: 1.15;
      margin: 0 0 6px;
      color: var(--ink);
    }
    .search-hero-desc {
      font-size: 0.92rem;
      color: #3b4e48;
      max-width: 600px;
      margin: 0 0 16px;
      line-height: 1.45;
    }

    /* ── Quick City Filter Chips ───────────────────────────────────────────── */
    .city-chips-bar {
      display: flex;
      align-items: center;
      gap: 8px;
      flex-wrap: wrap;
      margin-bottom: 18px;
    }
    .city-chip-pill {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      padding: 6px 14px;
      font-size: 0.76rem;
      font-family: 'DM Mono', monospace;
      font-weight: 700;
      border: 1.5px solid var(--ink);
      border-radius: 999px;
      background: var(--white);
      color: var(--ink);
      cursor: pointer;
      box-shadow: 2px 2px 0 var(--ink);
      transition: all 0.15s ease;
      white-space: nowrap;
      user-select: none;
    }
    .city-chip-pill:hover {
      transform: translateY(-1px);
      box-shadow: 3px 3px 0 var(--ink);
      background: var(--sand);
    }
    .city-chip-pill.is-active {
      background: var(--ink);
      color: var(--lime);
      border-color: var(--ink);
      box-shadow: 2px 2px 0 rgba(13,33,29,0.35);
    }
    .city-chip-pill.is-active i {
      color: var(--coral);
    }

    /* ── Sleek Filter Box ──────────────────────────────────────────────────── */
    .filter-box-main {
      background: var(--sand);
      border: 2px solid var(--ink);
      border-radius: 18px;
      box-shadow: 4px 4px 0 var(--ink);
      padding: 16px;
      margin-bottom: 22px;
      transition: box-shadow 0.2s ease;
    }
    .filter-box-main:hover {
      box-shadow: 5px 5px 0 var(--ink);
    }

    /* Primary Search Row */
    .filter-primary-row {
      display: flex;
      gap: 10px;
      align-items: center;
      flex-wrap: wrap;
    }
    .search-input-wrap {
      flex: 1;
      min-width: 220px;
      position: relative;
    }
    .search-input-wrap input {
      width: 100%;
      padding: 10px 14px 10px 38px;
      border: 2px solid var(--ink);
      border-radius: 10px;
      font-family: inherit;
      font-weight: 700;
      font-size: 0.88rem;
      background: var(--white);
      color: var(--ink);
      outline: none;
      box-shadow: inset 1px 1px 2px rgba(13,33,29,0.08);
      transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }
    .search-input-wrap input:focus {
      border-color: var(--ink);
      box-shadow: 0 0 0 2px var(--lime), 2px 2px 0 var(--ink);
    }
    .search-input-wrap .search-icon {
      position: absolute;
      left: 12px;
      top: 50%;
      transform: translateY(-50%);
      color: var(--coral);
      font-size: 0.95rem;
      pointer-events: none;
    }
    .search-input-wrap .clear-btn {
      position: absolute;
      right: 10px;
      top: 50%;
      transform: translateY(-50%);
      background: transparent;
      border: none;
      color: #7b8e88;
      cursor: pointer;
      font-size: 1rem;
      padding: 0;
      display: none;
    }
    .search-input-wrap .clear-btn:hover {
      color: var(--coral);
    }

    /* Filter Toggle Button */
    .filter-toggle-btn {
      display: inline-flex;
      align-items: center;
      gap: 7px;
      padding: 10px 16px;
      font-size: 0.82rem;
      font-weight: 800;
      white-space: nowrap;
      border-radius: 10px;
      cursor: pointer;
    }
    .filter-count-badge {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      background: var(--coral);
      color: var(--white);
      font-size: 0.65rem;
      font-weight: 800;
      min-width: 18px;
      height: 18px;
      padding: 0 5px;
      border-radius: 999px;
    }

    /* Detailed Expandable Drawer */
    .filter-drawer-panel {
      margin-top: 14px;
      padding-top: 14px;
      border-top: 2px dashed rgba(13,33,29,0.2);
      display: none;
    }
    .filter-drawer-panel.is-expanded {
      display: block;
      animation: fadeIn 0.2s ease-in-out;
    }
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(-4px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .filter-grid-fields {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
      gap: 12px;
      margin-bottom: 14px;
    }
    .filter-field-group label {
      display: block;
      margin-bottom: 4px;
      font-size: 0.70rem;
      font-family: 'DM Mono', monospace;
      font-weight: 700;
      color: #3b4e48;
      text-transform: uppercase;
      letter-spacing: 0.04em;
    }
    .filter-field-group select,
    .filter-field-group input {
      width: 100%;
      padding: 8px 10px;
      border: 1.5px solid var(--ink);
      border-radius: 8px;
      font-family: inherit;
      font-weight: 700;
      font-size: 0.82rem;
      background: var(--white);
      color: var(--ink);
      outline: none;
      transition: all 0.15s ease;
    }
    .filter-field-group select:focus,
    .filter-field-group input:focus {
      box-shadow: 0 0 0 2px var(--lime), 2px 2px 0 var(--ink);
    }

    /* Tactile Feature Checkbox Pills */
    .filter-pills-row {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
      align-items: center;
      margin-bottom: 14px;
      padding-top: 10px;
      border-top: 1.5px dashed rgba(13,33,29,0.15);
    }
    .filter-pill-toggle {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 6px 12px;
      border: 1.5px solid var(--ink);
      border-radius: 999px;
      background: var(--white);
      color: var(--ink);
      font-size: 0.75rem;
      font-weight: 700;
      cursor: pointer;
      transition: all 0.15s ease;
      user-select: none;
      box-shadow: 1.5px 1.5px 0 var(--ink);
    }
    .filter-pill-toggle input {
      display: none;
    }
    .filter-pill-toggle:hover {
      transform: translateY(-1px);
      box-shadow: 2.5px 2.5px 0 var(--ink);
    }
    .filter-pill-toggle.is-checked {
      background: var(--ink);
      color: var(--lime);
      border-color: var(--ink);
      box-shadow: 1.5px 1.5px 0 rgba(13,33,29,0.3);
    }
    .filter-pill-toggle.is-checked i {
      color: var(--lime);
    }

    /* Actions Bar */
    .filter-bottom-actions {
      display: flex;
      justify-content: flex-end;
      align-items: center;
      gap: 10px;
      flex-wrap: wrap;
      padding-top: 8px;
    }

    /* ── Results Header / Meta ─────────────────────────────────────────────── */
    .results-meta-bar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 10px;
      margin-bottom: 16px;
      padding-bottom: 6px;
    }
    .results-count-text {
      font-family: 'DM Mono', monospace;
      font-size: 0.82rem;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      color: var(--ink);
      display: flex;
      align-items: center;
      gap: 6px;
    }
    .results-active-tag {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      background: var(--lime);
      color: var(--ink);
      padding: 2px 8px;
      border: 1px solid var(--ink);
      border-radius: 999px;
      font-size: 0.68rem;
    }

    /* ── Courts Results Grid ───────────────────────────────────────────────── */
    #courts-results-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(310px, 1fr));
      gap: 22px;
    }

    /* ── Sleek Court Result Card ───────────────────────────────────────────── */
    .search-court-card {
      padding: 0;
      overflow: hidden;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      background: var(--white);
      border: 2px solid var(--ink);
      border-radius: 18px;
      box-shadow: 4px 4px 0 var(--ink);
      transition: transform 0.2s ease, box-shadow 0.2s ease;
      position: relative;
    }
    .search-court-card:hover {
      transform: translateY(-3px);
      box-shadow: 6px 6px 0 var(--ink);
    }

    /* Media Carousel */
    .search-court-carousel {
      position: relative;
      height: 185px;
      width: 100%;
      border-bottom: 2px solid var(--ink);
      overflow: hidden;
      background: #0d211d;
    }
    .carousel-track {
      display: flex;
      height: 100%;
      width: 100%;
      transition: transform 0.35s ease;
    }
    .carousel-slide {
      flex: 0 0 100%;
      height: 100%;
      width: 100%;
    }
    .carousel-slide img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
    }

    /* Floating Badges on Image */
    .court-card-badges-float {
      position: absolute;
      top: 10px;
      left: 10px;
      right: 10px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      pointer-events: none;
      z-index: 4;
    }
    .court-card-badges-float > * {
      pointer-events: auto;
    }

    /* Carousel Nav Buttons */
    .carousel-nav-btn {
      position: absolute;
      top: 50%;
      transform: translateY(-50%);
      background: rgba(13, 33, 29, 0.78);
      color: #fff;
      border: 1.5px solid var(--ink);
      border-radius: 50%;
      width: 28px;
      height: 28px;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      font-weight: 800;
      font-size: 0.92rem;
      z-index: 5;
      transition: background 0.15s ease, transform 0.15s ease;
      box-shadow: 1px 1px 0 rgba(0,0,0,0.3);
    }
    .carousel-nav-btn:hover {
      background: var(--ink);
      transform: translateY(-50%) scale(1.08);
    }
    .carousel-nav-btn.prev { left: 8px; }
    .carousel-nav-btn.next { right: 8px; }

    /* Carousel Dots */
    .carousel-dots {
      position: absolute;
      bottom: 8px;
      left: 50%;
      transform: translateX(-50%);
      display: flex;
      gap: 5px;
      z-index: 5;
    }
    .carousel-dot {
      width: 6px;
      height: 6px;
      border-radius: 50%;
      background: rgba(255, 255, 255, 0.55);
      transition: all 0.2s ease;
    }
    .carousel-dot.active {
      background: var(--lime);
      transform: scale(1.35);
    }

    /* Card Body */
    .search-card-body {
      padding: 16px 18px 14px;
      flex: 1;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
    }
    .court-card-facility-line {
      display: flex;
      align-items: center;
      gap: 5px;
      font-size: 0.78rem;
      font-weight: 700;
      color: #2a473f;
      margin-bottom: 4px;
      line-height: 1.3;
    }
    .court-card-facility-line i {
      color: var(--coral);
      font-size: 0.85rem;
      flex-shrink: 0;
    }
    .court-card-name {
      font-size: 1.22rem;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: -0.02em;
      line-height: 1.22;
      margin: 2px 0 8px;
      color: var(--ink);
    }

    /* Amenity / Surface Chips */
    .court-card-tags-row {
      display: flex;
      flex-wrap: wrap;
      gap: 5px;
      margin-bottom: 12px;
    }
    .court-card-tag {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      font-size: 0.65rem;
      font-family: 'DM Mono', monospace;
      font-weight: 700;
      text-transform: uppercase;
      padding: 3px 7px;
      border: 1px solid var(--ink);
      border-radius: 6px;
      background: var(--sand);
      color: var(--ink);
    }
    .court-card-amenity {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      font-size: 0.62rem;
      font-weight: 700;
      text-transform: uppercase;
      padding: 2.5px 6px;
      border: 1px solid var(--ink);
      border-radius: 5px;
      background: var(--ink);
      color: var(--white);
    }
    .court-card-amenity i {
      color: var(--lime);
      font-size: 0.7rem;
    }

    /* Card Footer */
    .search-card-footer {
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-top: 2px solid var(--ink);
      padding-top: 12px;
      margin-top: auto;
      gap: 10px;
    }
    .court-rate-label {
      display: block;
      font-size: 0.62rem;
      font-family: 'DM Mono', monospace;
      color: #4a5c56;
      font-weight: 800;
      letter-spacing: 0.04em;
    }
    .court-rate-val {
      font-size: 1.3rem;
      font-family: 'DM Mono', monospace;
      font-weight: 800;
      color: var(--ink);
      line-height: 1;
    }
    .court-rate-unit {
      font-size: 0.72rem;
      font-weight: 600;
      color: #4a5c56;
    }

    /* ── Empty State ───────────────────────────────────────────────────────── */
    .empty-state-wrap {
      grid-column: 1 / -1;
      width: 100%;
      display: flex;
      justify-content: center;
      padding: 18px 0;
    }
    .empty-state-card {
      max-width: 520px;
      width: 100%;
      text-align: center;
      padding: 36px 24px;
      border: 2px solid var(--ink);
      border-radius: 20px;
      box-shadow: 6px 6px 0 var(--ink);
      position: relative;
      background: var(--sand);
      transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .empty-state-card:hover {
      transform: translateY(-2px);
      box-shadow: 8px 8px 0 var(--ink);
    }
    .empty-state-icon-wrap {
      width: 68px;
      height: 68px;
      border-radius: 50%;
      border: 2px solid var(--ink);
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: 1.9rem;
      margin-bottom: 14px;
      box-shadow: 3px 3px 0 var(--ink);
    }
    .empty-state-title {
      font-size: 1.3rem;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: -0.02em;
      line-height: 1.2;
      margin: 10px 0 8px;
      color: var(--ink);
    }
    .empty-state-desc {
      font-size: 0.88rem;
      color: #3b4e48;
      line-height: 1.5;
      margin: 0 auto 20px;
      max-width: 420px;
    }
    .empty-state-actions {
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 10px;
      flex-wrap: wrap;
    }

    /* ── Mobile Responsive Overrides ───────────────────────────────────────── */
    @media (max-width: 768px) {
      .search-page-container {
        padding: 78px 12px 75px !important;
      }
      body:not(.customer-portal) .search-page-container {
        margin-top: 0 !important;
        padding-top: 78px !important;
        padding-bottom: 75px !important;
      }
      .search-hero-title {
        font-size: 1.55rem !important;
        margin-bottom: 4px;
      }
      .search-hero-desc {
        font-size: 0.84rem !important;
        margin-bottom: 12px;
      }
      .city-chips-bar {
        gap: 6px;
        margin-bottom: 14px;
        overflow-x: auto;
        padding-bottom: 4px;
        flex-wrap: nowrap;
        -webkit-overflow-scrolling: touch;
      }
      .city-chip-pill {
        padding: 5px 12px;
        font-size: 0.72rem;
        flex-shrink: 0;
      }

      /* Compact Filter Box */
      .filter-box-main {
        padding: 12px;
        border-radius: 14px;
        box-shadow: 3px 3px 0 var(--ink);
        margin-bottom: 16px;
      }
      .filter-primary-row {
        gap: 8px;
      }
      .search-input-wrap input {
        padding: 8px 12px 8px 34px;
        font-size: 0.82rem;
      }
      .search-input-wrap .search-icon {
        left: 10px;
        font-size: 0.88rem;
      }
      .filter-toggle-btn {
        padding: 8px 12px;
        font-size: 0.78rem;
      }

      /* Drawer on mobile */
      .filter-drawer-panel {
        margin-top: 10px;
        padding-top: 10px;
      }
      .filter-grid-fields {
        grid-template-columns: 1fr 1fr;
        gap: 8px;
        margin-bottom: 10px;
      }
      .filter-field-group label {
        font-size: 0.62rem;
        margin-bottom: 2px;
      }
      .filter-field-group select,
      .filter-field-group input {
        padding: 6px 8px;
        font-size: 0.76rem;
        border-radius: 6px;
      }
      .filter-pills-row {
        gap: 6px;
        padding-top: 8px;
        margin-bottom: 10px;
      }
      .filter-pill-toggle {
        padding: 5px 9px;
        font-size: 0.68rem;
      }
      .filter-bottom-actions button {
        padding: 7px 14px;
        font-size: 0.76rem;
      }

      /* Courts Grid & Cards */
      #courts-results-grid {
        grid-template-columns: 1fr;
        gap: 14px;
      }
      .search-court-card {
        border-radius: 14px;
        box-shadow: 3px 3px 0 var(--ink);
      }
      .search-court-carousel {
        height: 155px;
      }
      .search-card-body {
        padding: 12px 14px;
      }
      .court-card-facility-line {
        font-size: 0.74rem;
      }
      .court-card-name {
        font-size: 1.12rem;
      }
      .court-rate-val {
        font-size: 1.2rem;
      }
      .search-card-footer {
        padding-top: 10px;
      }
      .search-card-footer button {
        padding: 7px 14px;
        font-size: 0.78rem;
      }
    }
  </style>
</head>
<body>

  <?php require_once __DIR__ . '/../includes/header.php'; ?>

  <div class="search-page-container">
    <!-- Search Header Section -->
    <div class="search-hero-bar">
      <div class="search-hero-eyebrow">
        <span class="pulse-dot"></span> BOHOL PICKLEBALL &bull; LIVE COURT MARKETPLACE
      </div>
      <h1 class="search-hero-title">FIND &amp; FILTER COURTS</h1>
      <p class="search-hero-desc">
        Compare verified facilities, check surfaces and amenities, and secure your court slot with instant anti-conflict lock.
      </p>

      <!-- Quick City Filter Chips -->
      <div class="city-chips-bar" id="city-chips-container">
        <button type="button" class="city-chip-pill is-active" data-city="" onclick="selectQuickCity('')">
          <i class="bi bi-grid-fill"></i> All Cities
        </button>
        <?php foreach ($availableCities as $c): ?>
          <button type="button" class="city-chip-pill" data-city="<?= htmlspecialchars($c, ENT_QUOTES) ?>" onclick="selectQuickCity('<?= htmlspecialchars($c, ENT_QUOTES) ?>')">
            <i class="bi bi-geo-alt-fill"></i> <?= htmlspecialchars($c) ?>
          </button>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Main Filter Box -->
    <div class="filter-box-main">
      <form id="filter-form" autocomplete="off">
        <!-- Row 1: Primary Search + Filters Toggle -->
        <div class="filter-primary-row">
          <div class="search-input-wrap">
            <i class="bi bi-search search-icon"></i>
            <input type="text" id="filter-search" placeholder="Search court, facility, or keyword...">
            <button type="button" id="clear-search-btn" class="clear-btn" aria-label="Clear search">
              <i class="bi bi-x-circle-fill"></i>
            </button>
          </div>

          <button type="button" id="toggle-filter-btn" class="button sand filter-toggle-btn" aria-expanded="false">
            <i class="bi bi-sliders2"></i>
            <span>Filters</span>
            <span id="active-filter-badge" class="filter-count-badge" style="display:none;">0</span>
          </button>

          <button type="submit" class="button coral" style="padding:10px 18px; font-size:0.82rem;">
            <i class="bi bi-search"></i> Search
          </button>
        </div>

        <!-- Row 2: Detailed Filters Drawer -->
        <div id="filter-drawer" class="filter-drawer-panel">
          <div class="filter-grid-fields">
            <!-- Facility Select -->
            <div class="filter-field-group">
              <label><i class="bi bi-building"></i> Facility</label>
              <select id="filter-facility-id">
                <option value="">All Facilities</option>
              </select>
            </div>

            <!-- City Input -->
            <div class="filter-field-group">
              <label><i class="bi bi-geo-alt"></i> Location / City</label>
              <input type="text" id="filter-city" list="cities-datalist" placeholder="e.g. <?= !empty($availableCities) ? htmlspecialchars($availableCities[0]) : 'Tagbilaran' ?>">
              <datalist id="cities-datalist">
                <?php foreach ($availableCities as $c): ?>
                  <option value="<?= htmlspecialchars($c, ENT_QUOTES) ?>"><?= htmlspecialchars($c) ?></option>
                <?php endforeach; ?>
              </datalist>
            </div>

            <!-- Court Type Select -->
            <div class="filter-field-group">
              <label><i class="bi bi-layers"></i> Court Type</label>
              <select id="filter-type">
                <option value="">All Types (Indoor/Outdoor)</option>
                <option value="indoor">Indoor (Aircon)</option>
                <option value="outdoor">Outdoor</option>
                <option value="covered">Covered Flex</option>
              </select>
            </div>

            <!-- Max Price Input -->
            <div class="filter-field-group">
              <label><i class="bi bi-tag"></i> Max Rate / Hr (₱)</label>
              <input type="number" id="filter-price" placeholder="e.g. 500" min="0" step="50">
            </div>

            <!-- Reservation Date -->
            <div class="filter-field-group">
              <label><i class="bi bi-calendar-event"></i> Play Date</label>
              <input type="date" id="filter-date">
            </div>
          </div>

          <!-- Feature & Amenity Toggle Pills -->
          <div class="filter-pills-row">
            <label class="filter-pill-toggle" id="pill-lighting">
              <input type="checkbox" id="filter-lighting">
              <i class="bi bi-moon-stars"></i> Night Lighting
            </label>
            <label class="filter-pill-toggle" id="pill-parking">
              <input type="checkbox" id="filter-parking">
              <i class="bi bi-p-square"></i> Parking
            </label>
            <label class="filter-pill-toggle" id="pill-gear">
              <input type="checkbox" id="filter-gear">
              <i class="bi bi-bag-check"></i> Gear Rental
            </label>
            <label class="filter-pill-toggle" id="pill-shower">
              <input type="checkbox" id="filter-shower">
              <i class="bi bi-droplet"></i> Shower
            </label>
            <label class="filter-pill-toggle" id="pill-open">
              <input type="checkbox" id="filter-open">
              <i class="bi bi-clock"></i> Open Now
            </label>
          </div>

          <!-- Filter Action Buttons -->
          <div class="filter-bottom-actions">
            <button type="button" id="btn-reset" class="button dark" style="padding:8px 16px; font-size:0.78rem;">
              <i class="bi bi-arrow-counterclockwise"></i> Reset All
            </button>
            <button type="submit" id="btn-apply" class="button coral" style="padding:8px 20px; font-size:0.78rem;">
              <i class="bi bi-check2-circle"></i> Apply Filters
            </button>
          </div>
        </div>
      </form>
    </div>

    <!-- Results Meta Status -->
    <div class="results-meta-bar">
      <div id="results-meta" class="results-count-text">
        <span>LOADING COURTS...</span>
      </div>
      <div id="active-tags-summary" style="display:flex; gap:6px; flex-wrap:wrap;"></div>
    </div>

    <!-- Courts Results Grid -->
    <div id="courts-results-grid">
      <!-- Skeletons on initial load -->
      <div class="skeleton skeleton-court"></div>
      <div class="skeleton skeleton-court"></div>
      <div class="skeleton skeleton-court"></div>
      <div class="skeleton skeleton-court"></div>
      <div class="skeleton skeleton-court"></div>
      <div class="skeleton skeleton-court"></div>
    </div>
  </div>

  <div id="footer-container"></div>

  <!-- jQuery CDN -->
  <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
  <script src="/pikvero/assets/js/core/toast.js"></script>
  <script src="/pikvero/assets/js/core/ajax.js"></script>
  <script src="/pikvero/assets/js/core/auth.js"></script>
  <script src="/pikvero/assets/js/components/navbar.js?v=2"></script>
  <script src="/pikvero/assets/js/components/bottom-nav.js"></script>
  <script src="/pikvero/assets/js/components/sidebar.js"></script>
  <script src="/pikvero/assets/js/components/footer.js"></script>
  <script>
    // ── Court type badge map ──────────────────────────────────────────────────
    var COURT_TYPE_BADGE = {
      indoor:  { label: 'INDOOR AIRCON', cls: 'lime'  },
      outdoor: { label: 'OUTDOOR',       cls: 'sky'   },
      covered: { label: 'COVERED FLEX',  cls: 'green' }
    };

    var surfaceLabelMap = {
      cushioned_acrylic: 'Cushioned Acrylic',
      asphalt:           'Asphalt Surface',
      polyurethane:      'Polyurethane Flex',
      concrete:          'Reinforced Concrete'
    };

    let cardCarouselIntervals = {};

    function startCardCarouselAutoplay(carouselId, totalCount) {
      if (totalCount <= 1) return;
      if (cardCarouselIntervals[carouselId]) {
        clearInterval(cardCarouselIntervals[carouselId]);
      }
      cardCarouselIntervals[carouselId] = setInterval(() => {
        navCardCarousel(null, carouselId, 1, totalCount, false);
      }, 3800);
    }

    // Helper: Escape HTML strings for safety
    function escapeHtml(text) {
      if (!text) return '';
      return $('<div>').text(text).html();
    }

    // Card Image Carousel Generator
    function buildCardCarouselHtml(idPrefix, item) {
      const carouselId = idPrefix + '-' + item.id;
      const images = (item.images && item.images.length > 0) ? item.images : [];
      const badge = COURT_TYPE_BADGE[item.court_type] || { label: (item.court_type || 'COURT').toUpperCase(), cls: 'lime' };

      const floatingBadgeHtml = `
        <div class="court-card-badges-float">
          <span class="badge-streetside ${badge.cls}" style="font-size:0.65rem; padding:3px 8px; box-shadow:2px 2px 0 var(--ink);">
            ${badge.label}
          </span>
          <span class="badge-streetside sand" style="font-size:0.65rem; padding:3px 7px; box-shadow:2px 2px 0 var(--ink);">
            <i class="bi bi-patch-check-fill" style="color:var(--green);"></i> VERIFIED
          </span>
        </div>
      `;

      if (images.length === 0) {
        const defaultImg = item.image_path || item.cover_image || '/pikvero/assets/images/logo.png';
        return `
          <div class="search-court-carousel">
            ${floatingBadgeHtml}
            <div style="width:100%; height:100%;">
              <img src="${defaultImg}" style="width:100%; height:100%; object-fit:cover;" onerror="this.onerror=null; this.src='/pikvero/assets/images/logo.png';">
            </div>
          </div>
        `;
      }

      const slidesHtml = images.map(img => `
        <div class="carousel-slide">
          <img src="${img.image_path}" onerror="this.onerror=null; this.src='/pikvero/assets/images/logo.png';">
        </div>
      `).join('');

      const dotsHtml = images.length > 1 ? `
        <div class="carousel-dots">
          ${images.map((_, idx) => `<span class="carousel-dot dot-${carouselId} ${idx === 0 ? 'active' : ''}"></span>`).join('')}
        </div>
      ` : '';

      const navBtnsHtml = images.length > 1 ? `
        <button type="button" class="carousel-nav-btn prev" onclick="navCardCarousel(event, '${carouselId}', -1, ${images.length})">&lsaquo;</button>
        <button type="button" class="carousel-nav-btn next" onclick="navCardCarousel(event, '${carouselId}', 1, ${images.length})">&rsaquo;</button>
      ` : '';

      if (images.length > 1) {
        setTimeout(() => startCardCarouselAutoplay(carouselId, images.length), 100);
      }

      return `
        <div class="search-court-carousel">
          ${floatingBadgeHtml}
          <div id="track-${carouselId}" class="carousel-track" data-index="0">
            ${slidesHtml}
          </div>
          ${navBtnsHtml}
          ${dotsHtml}
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

      const dots = document.querySelectorAll('.dot-' + carouselId);
      dots.forEach((dot, idx) => {
        if (idx === currentIndex) {
          dot.classList.add('active');
        } else {
          dot.classList.remove('active');
        }
      });

      if (isManual && totalCount > 1) {
        startCardCarouselAutoplay(carouselId, totalCount);
      }
    }

    // ── Quick City Chip Selection ─────────────────────────────────────────────
    function selectQuickCity(cityName) {
      $('#filter-city').val(cityName);
      
      // Update UI active state on chips
      $('#city-chips-container .city-chip-pill').each(function() {
        var chipCity = $(this).attr('data-city') !== undefined ? $(this).attr('data-city') : $(this).text().trim();
        var isMatch = (!cityName && (!chipCity || $(this).text().indexOf('All Cities') !== -1)) ||
                      (cityName && chipCity.toLowerCase() === cityName.toLowerCase());
        if (isMatch) {
          $(this).addClass('is-active');
        } else {
          $(this).removeClass('is-active');
        }
      });

      updateActiveFilterCount();
      loadCourts();
    }

    // ── Update Active Filters Counter ─────────────────────────────────────────
    function updateActiveFilterCount() {
      var count = 0;
      if ($('#filter-facility-id').val()) count++;
      if ($('#filter-city').val().trim()) count++;
      if ($('#filter-type').val()) count++;
      if ($('#filter-price').val().trim()) count++;
      if ($('#filter-lighting').is(':checked')) count++;
      if ($('#filter-parking').is(':checked')) count++;
      if ($('#filter-gear').is(':checked')) count++;
      if ($('#filter-shower').is(':checked')) count++;
      if ($('#filter-open').is(':checked')) count++;

      var $badge = $('#active-filter-badge');
      if (count > 0) {
        $badge.text(count).show();
      } else {
        $badge.hide();
      }

      // Sync active state on city chips if city input changed manually
      var currentCity = $('#filter-city').val().trim().toLowerCase();
      $('#city-chips-container .city-chip-pill').each(function() {
        var chipCity = ($(this).attr('data-city') || '').toLowerCase();
        var isMatch = (!currentCity && (!chipCity || $(this).text().indexOf('All Cities') !== -1)) ||
                      (currentCity && (chipCity === currentCity || chipCity.indexOf(currentCity) !== -1 || currentCity.indexOf(chipCity) !== -1));
        if (isMatch) {
          $(this).addClass('is-active');
        } else {
          $(this).removeClass('is-active');
        }
      });

      // Update clear icon visibility in search input
      if ($('#filter-search').val().trim().length > 0) {
        $('#clear-search-btn').show();
      } else {
        $('#clear-search-btn').hide();
      }
    }

    // ── Reset search filters helper ──────────────────────────────────────────
    function resetSearchFilters() {
      $('#filter-form')[0].reset();
      var today = new Date().toISOString().split('T')[0];
      $('#filter-date').val(today);
      $('#filter-search').val('');
      $('#filter-facility-id').val('');
      $('#filter-city').val('');
      $('#filter-type').val('');
      $('#filter-price').val('');
      
      // Reset checkbox styling
      $('.filter-pill-toggle').removeClass('is-checked');
      $('.filter-pill-toggle input').prop('checked', false);

      selectQuickCity('');
    }

    // ── Render court result cards ─────────────────────────────────────────────
    function renderCourts(courts) {
      var $grid = $('#courts-results-grid');
      var $meta = $('#results-meta');

      if (!courts || courts.length === 0) {
        $meta.html('<span>0 COURTS FOUND</span>');
        $grid.css('grid-template-columns', '1fr');
        $grid.html(
          '<div class="empty-state-wrap">'
          + '<div class="empty-state-card">'
          + '<div class="empty-state-icon-wrap" style="background:var(--lime); color:var(--ink);">'
          + '<i class="bi bi-search"></i>'
          + '</div>'
          + '<div style="margin-bottom:8px;"><span class="badge-streetside dark" style="font-size:0.68rem; letter-spacing:0.06em;">SEARCH RESULTS</span></div>'
          + '<h3 class="empty-state-title">NO MATCHING COURTS FOUND</h3>'
          + '<p class="empty-state-desc">Try adjusting your filters, clearing keywords, or selecting a different city to discover available venues.</p>'
          + '<div class="empty-state-actions">'
          + '<button type="button" onclick="resetSearchFilters()" class="button coral" style="padding:9px 18px; font-size:0.84rem;">'
          + '<i class="bi bi-arrow-counterclockwise"></i> Reset Filters'
          + '</button>'
          + '<a href="/pikvero/public/register.php?type=owner" class="button dark" style="padding:9px 18px; font-size:0.84rem;">'
          + '<i class="bi bi-building-add"></i> List Your Facility'
          + '</a>'
          + '</div>'
          + '</div>'
          + '</div>'
        );
        return;
      }

      $grid.css('grid-template-columns', '');
      var countText = courts.length + ' ' + (courts.length === 1 ? 'COURT' : 'COURTS') + ' AVAILABLE';
      $meta.html('<span><i class="bi bi-check-circle-fill" style="color:#10b981;"></i> ' + countText + '</span>');

      var html = $.map(courts, function(c) {
        var price = parseFloat(c.base_price_per_hour || 0).toFixed(2);
        var surface = surfaceLabelMap[c.surface_type] || c.surface_type || 'Standard Surface';
        var facility = c.facility_name || 'Pickleball Facility';
        var locationParts = [c.city || 'Bohol'];
        if (c.address) locationParts.unshift(c.address);
        var locationStr = locationParts.join(', ');

        var amenityHtml = '';
        if (c.amenities && c.amenities.length > 0) {
          amenityHtml = $.map(c.amenities.slice(0, 3), function(a) {
            return '<span class="court-card-amenity"><i class="bi ' + (a.icon || 'bi-check-circle') + '"></i> ' + escapeHtml(a.name) + '</span>';
          }).join(' ');
        }

        var carouselHtml = buildCardCarouselHtml('search-court', c);

        return '<div class="search-court-card">'
          + carouselHtml
          + '<div class="search-card-body">'
          + '<div>'
          + '<div class="court-card-facility-line">'
          +   '<i class="bi bi-geo-alt-fill"></i>'
          +   '<span>' + escapeHtml(facility) + ' &bull; ' + escapeHtml(c.city || 'Bohol') + '</span>'
          + '</div>'
          + '<h3 class="court-card-name">' + escapeHtml(c.name) + '</h3>'
          + '<div class="court-card-tags-row">'
          +   '<span class="court-card-tag"><i class="bi bi-layers-fill"></i> ' + escapeHtml(surface) + '</span>'
          +   (amenityHtml ? amenityHtml : '')
          + '</div>'
          + '</div>'
          + '<div class="search-card-footer">'
          +   '<div>'
          +     '<span class="court-rate-label">STARTING RATE</span>'
          +     '<span class="court-rate-val">&#8369;' + price + '</span><span class="court-rate-unit">/hr</span>'
          +   '</div>'
          +   '<button onclick="openFacilityBooking(' + c.facility_id + ',' + c.id + ')" class="button coral" style="padding:8px 16px; font-size:0.82rem; display:inline-flex; align-items:center; gap:6px;">'
          +     'Book Court &rarr;'
          +   '</button>'
          + '</div>'
          + '</div>'
          + '</div>';
      }).join('');

      $grid.html(html);
    }

    // ── Show skeleton while loading ───────────────────────────────────────────
    function showSkeletons(count) {
      count = count || 6;
      var skels = '';
      for (var i = 0; i < count; i++) {
        skels += '<div class="skeleton skeleton-court"></div>';
      }
      $('#courts-results-grid').html(skels);
      $('#results-meta').html('<span>FINDING COURTS...</span>');
    }

    // ── Load facilities dropdown ──────────────────────────────────────────────
    function populateFacilityFilter() {
      $.get('/pikvero/api/customer/facilities.php', function(res) {
        if (res && res.success && res.data) {
          var options = '<option value="">All Facilities</option>';
          $.each(res.data, function(idx, fac) {
            options += '<option value="' + fac.id + '">' + escapeHtml(fac.name) + ' (' + escapeHtml(fac.city || 'Bohol') + ')</option>';
          });
          $('#filter-facility-id').html(options);
          
          var urlParams = new URLSearchParams(window.location.search);
          if (urlParams.get('facility_id')) {
            $('#filter-facility-id').val(urlParams.get('facility_id'));
            updateActiveFilterCount();
          }
        }
      });
    }

    // ── Load cities dynamically from database API ────────────────────────────
    function populateCitiesFilter() {
      $.get('/pikvero/api/customer/facilities.php', { action: 'cities' }, function(res) {
        if (res && res.success && Array.isArray(res.data) && res.data.length > 0) {
          var currentVal = $('#filter-city').val().trim();
          var chipsHtml = '<button type="button" class="city-chip-pill' + (!currentVal ? ' is-active' : '') + '" data-city="" onclick="selectQuickCity(\'\')"><i class="bi bi-grid-fill"></i> All Cities</button>';
          var datalistHtml = '';

          res.data.forEach(function(c) {
            var isActive = currentVal && (currentVal.toLowerCase() === c.toLowerCase());
            chipsHtml += '<button type="button" class="city-chip-pill' + (isActive ? ' is-active' : '') + '" data-city="' + escapeHtml(c) + '" onclick="selectQuickCity(\'' + escapeHtml(c) + '\')"><i class="bi bi-geo-alt-fill"></i> ' + escapeHtml(c) + '</button>';
            datalistHtml += '<option value="' + escapeHtml(c) + '">' + escapeHtml(c) + '</option>';
          });

          $('#city-chips-container').html(chipsHtml);
          $('#cities-datalist').html(datalistHtml);
        }
      });
    }

    // ── Load courts from API ──────────────────────────────────────────────────
    function loadCourts() {
      var params = {
        search:      $('#filter-search').val().trim(),
        facility_id: $('#filter-facility-id').val(),
        city:        $('#filter-city').val().trim(),
        court_type:  $('#filter-type').val(),
        max_price:   $('#filter-price').val().trim(),
        lighting:    $('#filter-lighting').is(':checked') ? 1 : '',
        parking:     $('#filter-parking').is(':checked') ? 1 : '',
        gear:        $('#filter-gear').is(':checked') ? 1 : '',
        shower:      $('#filter-shower').is(':checked') ? 1 : '',
        open_now:    $('#filter-open').is(':checked') ? 1 : ''
      };

      // Remove empty params
      $.each(params, function(k, v) { if (!v) delete params[k]; });

      showSkeletons(6);

      $.ajax({
        url: '/pikvero/api/customer/courts.php',
        method: 'GET',
        data: params,
        dataType: 'json',
        success: function(res) {
          if (res.success) {
            renderCourts(res.data);
          } else {
            renderCourts([]);
          }
        },
        error: function(xhr, status, err) {
          console.error('Court search failed:', status, err);
          $('#courts-results-grid').css('grid-template-columns', '1fr').html(
            '<div class="empty-state-wrap">'
            + '<div class="empty-state-card">'
            + '<div class="empty-state-icon-wrap" style="background:var(--coral); color:var(--white);">'
            + '<i class="bi bi-exclamation-triangle-fill"></i>'
            + '</div>'
            + '<div style="margin-bottom:8px;"><span class="badge-streetside dark" style="font-size:0.68rem; letter-spacing:0.06em;">SERVER ERROR</span></div>'
            + '<h3 class="empty-state-title">UNABLE TO LOAD COURTS</h3>'
            + '<p class="empty-state-desc">There was a problem communicating with the server. Please verify your connection and try again.</p>'
            + '<div class="empty-state-actions">'
            + '<button type="button" onclick="loadCourts()" class="button coral" style="padding:9px 18px; font-size:0.84rem;">'
            + '<i class="bi bi-arrow-clockwise"></i> Try Again'
            + '</button>'
            + '</div>'
            + '</div>'
            + '</div>'
          );
          $('#results-meta').html('<span>CONNECTION ERROR</span>');
        }
      });
    }

    // ── Navigate to booking ───────────────────────────────────────────────────
    function openFacilityBooking(facilityId, courtId) {
      sessionStorage.setItem('selected_facility_id', facilityId);
      sessionStorage.setItem('selected_court_id', courtId);
      window.location.href = '/pikvero/public/facility.php?id=' + facilityId + '&court_id=' + courtId;
    }

    // ── Init on DOM ready ─────────────────────────────────────────────────────
    $(document).ready(async function () {
      NavbarComponent.render();
      FooterComponent.render();

      const userCtx = await AuthHelper.checkSession();
      if (userCtx && userCtx.user && typeof SidebarComponent !== 'undefined') {
        let portalType = 'customer';
        if (userCtx.role === 'court_owner') portalType = 'owner';
        else if (userCtx.role !== 'customer') portalType = 'admin';
        SidebarComponent.render('explore', portalType);
      }

      populateFacilityFilter();
      populateCitiesFilter();

      // Pre-fill from URL query params
      var urlParams = new URLSearchParams(window.location.search);
      if (urlParams.get('search') || urlParams.get('q')) {
        $('#filter-search').val(urlParams.get('search') || urlParams.get('q'));
      }
      if (urlParams.get('city')) {
        var qCity = urlParams.get('city');
        $('#filter-city').val(qCity);
        // Highlight matching city chip if any
        selectQuickCity(qCity);
      }
      if (urlParams.get('court_type')) {
        $('#filter-type').val(urlParams.get('court_type'));
      }
      if (urlParams.get('max_price')) {
        $('#filter-price').val(urlParams.get('max_price'));
      }

      // Default date to today
      var today = new Date().toISOString().split('T')[0];
      $('#filter-date').val(today);

      // Toggle drawer behavior
      $('#toggle-filter-btn').on('click', function() {
        var $drawer = $('#filter-drawer');
        var isExpanded = $drawer.hasClass('is-expanded');
        if (isExpanded) {
          $drawer.removeClass('is-expanded');
          $(this).attr('aria-expanded', 'false');
        } else {
          $drawer.addClass('is-expanded');
          $(this).attr('aria-expanded', 'true');
        }
      });

      // Clear search button
      $('#clear-search-btn').on('click', function() {
        $('#filter-search').val('').focus();
        $(this).hide();
        loadCourts();
      });

      // Checkbox Pill styling toggle
      $('.filter-pill-toggle input').on('change', function() {
        var $label = $(this).closest('.filter-pill-toggle');
        if ($(this).is(':checked')) {
          $label.addClass('is-checked');
        } else {
          $label.removeClass('is-checked');
        }
        updateActiveFilterCount();
        loadCourts();
      });

      // Filter form submit
      $('#filter-form').on('submit', function(e) {
        e.preventDefault();
        updateActiveFilterCount();
        loadCourts();
      });

      // Live search on select change
      $('#filter-facility-id, #filter-type, #filter-date').on('change', function() {
        updateActiveFilterCount();
        loadCourts();
      });

      // Debounced live typing on inputs
      var searchTimer = null;
      $('#filter-search, #filter-city, #filter-price').on('input', function() {
        updateActiveFilterCount();
        clearTimeout(searchTimer);
        searchTimer = setTimeout(loadCourts, 320);
      });

      // Reset button
      $('#btn-reset').on('click', function() {
        resetSearchFilters();
        updateActiveFilterCount();
        loadCourts();
      });

      updateActiveFilterCount();

      // Initial load
      loadCourts();
    });
  </script>
</body>
</html>
