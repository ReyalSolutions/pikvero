<?php
/**
 * Pikvero — Search & Filter Pickleball Courts
 * Premium high-impact Streetside UI with bg-search.png hero design
 */
require_once __DIR__ . '/../app/bootstrap.php';
$playerSearch = !empty($playerSearch) || \App\Core\Auth\Auth::check();

// Calculate dynamic base URL and base path
$reqUri   = $_SERVER['REQUEST_URI'] ?? '/';
$basePath = (strpos($reqUri, '/pikvero') === 0) ? '/pikvero' : '';

$availableCities = [];
try {
    $facilityRepo = new \App\Infrastructure\Repositories\FacilityRepository();
    $availableCities = $facilityRepo->getAllCities();
} catch (\Throwable $e) {
    $availableCities = ['Tagbilaran City', 'Panglao', 'Cebu City', 'Ubay'];
}
if (empty($availableCities)) {
    $availableCities = ['Tagbilaran City', 'Panglao', 'Cebu City', 'Ubay'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
  <title>Pikvero — Search &amp; Filter Pickleball Courts</title>
  <meta name="description" content="Compare verified facilities, check surfaces and amenities, and secure your court slot with instant anti-conflict lock.">
  
  <link rel="icon" type="image/png" href="<?= $basePath ?>/assets/images/logo.png">
  <link rel="shortcut icon" type="image/png" href="<?= $basePath ?>/assets/images/logo.png">
  
  <!-- Google Fonts: Plus Jakarta Sans, Outfit, DM Mono -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@500;700&family=Outfit:wght@400;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  
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
      --card-bg: #ffffff;
      --emerald: #10b981;
      --cyan: #06b6d4;
    }

    *, *::before, *::after {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      font-family: 'Plus Jakarta Sans', sans-serif;
      color: var(--ink);
      background: #fdfbf7;
      min-height: 100vh;
      overflow-x: hidden;
      position: relative;
    }

    /* ── Hero Section with bg-search.png ───────────────────────────────────── */
    .search-hero-section {
      background: #f8fafc url('<?= $basePath ?>/assets/images/bg-search.png') no-repeat center right;
      background-size: cover;
      position: relative;
      min-height: 480px;
      display: flex;
      flex-direction: column;
      justify-content: flex-end;
      border-bottom: 1.5px solid var(--border-soft);
      overflow: hidden;
    }

    .search-hero-section::before {
      content: "";
      position: absolute;
      inset: 0;
      background: linear-gradient(90deg, 
        rgba(255, 255, 255, 0.97) 0%, 
        rgba(255, 255, 255, 0.92) 35%, 
        rgba(255, 255, 255, 0.52) 58%, 
        rgba(255, 255, 255, 0.12) 82%, 
        transparent 100%
      );
      pointer-events: none;
      z-index: 0;
    }

    .search-hero-inner {
      position: relative;
      z-index: 1;
      width: 100%;
      max-width: 1324px;
      margin: 0 auto;
      padding: 110px 24px 34px;
      display: flex;
      flex-direction: column;
    }

    /* Eyebrow badge */
    .search-eyebrow {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      font-family: 'DM Mono', monospace;
      font-size: 0.8rem;
      font-weight: 700;
      color: #1b382b;
      letter-spacing: 0.06em;
      text-transform: uppercase;
      margin-bottom: 10px;
    }

    .search-eyebrow i {
      color: #10b981;
      font-size: 0.95rem;
    }

    /* Kinetic Headline */
    .search-hero-title {
      font-family: 'Outfit', sans-serif;
      text-transform: uppercase;
      line-height: 0.96;
      letter-spacing: -0.04em;
      margin-bottom: 14px;
    }

    .search-hero-title .title-ink {
      display: block;
      font-size: clamp(2.3rem, 5vw, 4rem);
      font-weight: 900;
      color: var(--ink);
    }

    .search-hero-title .title-coral {
      display: block;
      font-size: clamp(2.6rem, 5.8vw, 4.6rem);
      font-weight: 900;
      color: var(--coral);
      letter-spacing: -0.05em;
    }

    /* Description */
    .search-hero-desc {
      font-size: 0.96rem;
      line-height: 1.55;
      color: #334155;
      max-width: 540px;
      margin-bottom: 22px;
      font-weight: 500;
    }

    /* Quick Filter Chips */
    .quick-chips-bar {
      display: flex;
      align-items: center;
      gap: 10px;
      flex-wrap: wrap;
      margin-bottom: 24px;
    }

    .quick-chip-pill {
      display: inline-flex;
      align-items: center;
      gap: 7px;
      padding: 7px 16px;
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
      user-select: none;
      white-space: nowrap;
    }

    .quick-chip-pill i {
      font-size: 0.92rem;
    }

    .quick-chip-pill:hover {
      background: #f1f5f9;
      transform: translateY(-1px);
    }

    .quick-chip-pill.active {
      background: #0c1a15;
      color: #ffffff;
      border-color: #0c1a15;
      box-shadow: 0 4px 12px rgba(12, 26, 21, 0.25);
    }

    .quick-chip-pill.active i {
      color: var(--lime);
    }

    /* ── Floating Search Bar Widget ────────────────────────────────────────── */
    .search-widget-container {
      width: 100%;
      position: relative;
    }

    .search-widget-pill {
      background: rgba(255, 255, 255, 0.94);
      backdrop-filter: blur(16px);
      -webkit-backdrop-filter: blur(16px);
      border: 1.5px solid rgba(255, 255, 255, 0.95);
      border-radius: 9999px;
      box-shadow: 0 16px 40px -10px rgba(0, 0, 0, 0.12);
      padding: 7px 9px 7px 22px;
      display: flex;
      align-items: center;
      gap: 12px;
      transition: all 0.3s ease;
    }

    .search-widget-pill:focus-within {
      box-shadow: 0 20px 45px -10px rgba(255, 87, 51, 0.22);
      border-color: rgba(255, 87, 51, 0.4);
    }

    .search-input-box {
      flex: 1;
      display: flex;
      align-items: center;
      gap: 12px;
      position: relative;
    }

    .search-input-box i.search-icon {
      font-size: 1.15rem;
      color: #64748b;
    }

    .search-input-box input {
      width: 100%;
      border: none;
      background: transparent;
      font-family: 'Plus Jakarta Sans', sans-serif;
      font-size: 0.94rem;
      font-weight: 500;
      color: var(--ink);
      outline: none;
    }

    .search-input-box input::placeholder {
      color: #94a3b8;
    }

    .clear-input-btn {
      display: none;
      background: none;
      border: none;
      font-size: 1.3rem;
      color: #94a3b8;
      cursor: pointer;
      padding: 2px 6px;
      line-height: 1;
    }

    .clear-input-btn:hover {
      color: var(--ink);
    }

    .widget-divider {
      width: 1px;
      height: 28px;
      background: rgba(0, 0, 0, 0.12);
      flex-shrink: 0;
    }

    .btn-filters-toggle {
      background: #ffffff;
      color: var(--ink);
      border: 1.5px solid #0c1a15;
      border-radius: 12px;
      padding: 9px 18px;
      font-family: 'Outfit', sans-serif;
      font-weight: 800;
      font-size: 0.88rem;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      cursor: pointer;
      box-shadow: 1.5px 1.5px 0 #000;
      transition: all 0.15s ease;
      white-space: nowrap;
    }

    .btn-filters-toggle:hover {
      transform: translateY(-1px);
      box-shadow: 2.5px 2.5px 0 #000;
    }

    .btn-filters-toggle.is-active {
      background: #0c1a15;
      color: #ffffff;
    }

    .filter-count-badge {
      background: var(--coral);
      color: #fff;
      font-size: 0.72rem;
      padding: 2px 7px;
      border-radius: 9999px;
      font-weight: 800;
    }

    .btn-search-action {
      background: var(--coral);
      color: #ffffff;
      border: none;
      border-radius: 12px;
      padding: 10px 24px;
      font-family: 'Outfit', sans-serif;
      font-weight: 800;
      font-size: 0.92rem;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      cursor: pointer;
      box-shadow: 0 4px 14px rgba(255, 87, 51, 0.4);
      transition: all 0.2s ease;
      white-space: nowrap;
    }

    .btn-search-action:hover {
      background: var(--coral-hover);
      transform: translateY(-1px);
      box-shadow: 0 6px 18px rgba(255, 87, 51, 0.55);
    }

    /* Expandable Filters Drawer */
    .filters-drawer-panel {
      display: none;
      background: #ffffff;
      border: 1.5px solid var(--border-soft);
      border-radius: 20px;
      box-shadow: 0 18px 40px -8px rgba(0, 0, 0, 0.14);
      padding: 22px;
      margin-top: 14px;
      animation: fadeInDown 0.25s ease forwards;
    }

    .filters-drawer-panel.is-expanded {
      display: block;
    }

    @keyframes fadeInDown {
      from { opacity: 0; transform: translateY(-8px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    .filters-grid-fields {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
      gap: 14px;
      margin-bottom: 16px;
    }

    .filter-field-group label {
      display: block;
      font-size: 0.75rem;
      font-weight: 700;
      font-family: 'DM Mono', monospace;
      text-transform: uppercase;
      color: #64748b;
      margin-bottom: 6px;
    }

    .filter-field-group select,
    .filter-field-group input {
      width: 100%;
      padding: 9px 12px;
      border-radius: 10px;
      border: 1.5px solid #cbd5e1;
      background: #fff;
      font-family: 'Plus Jakarta Sans', sans-serif;
      font-size: 0.86rem;
      color: var(--ink);
      outline: none;
      transition: border-color 0.2s;
    }

    .filter-field-group select:focus,
    .filter-field-group input:focus {
      border-color: var(--coral);
    }

    .amenities-checkbox-row {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
      padding-top: 12px;
      border-top: 1px dashed #cbd5e1;
      margin-bottom: 16px;
    }

    .amenity-toggle-pill {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 6px 14px;
      border-radius: 9999px;
      background: #f1f5f9;
      color: #334155;
      font-size: 0.8rem;
      font-weight: 700;
      cursor: pointer;
      user-select: none;
      transition: all 0.15s;
    }

    .amenity-toggle-pill input {
      display: none;
    }

    .amenity-toggle-pill.is-checked {
      background: var(--ink);
      color: var(--lime);
    }

    .drawer-actions-bar {
      display: flex;
      justify-content: flex-end;
      gap: 10px;
    }

    .btn-reset-filters {
      background: #f1f5f9;
      color: #475569;
      border: none;
      border-radius: 10px;
      padding: 8px 18px;
      font-weight: 700;
      font-size: 0.82rem;
      cursor: pointer;
    }

    .btn-apply-filters {
      background: var(--coral);
      color: #fff;
      border: none;
      border-radius: 10px;
      padding: 8px 20px;
      font-weight: 800;
      font-size: 0.84rem;
      cursor: pointer;
    }

    /* ── Results Container & Controls ──────────────────────────────────────── */
    .results-main-wrap {
      max-width: 1324px;
      margin: 0 auto;
      padding: 32px 24px 80px;
    }

    .results-controls-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
      flex-wrap: wrap;
      margin-bottom: 26px;
      padding-bottom: 16px;
      border-bottom: 1.5px solid rgba(0, 0, 0, 0.06);
    }

    .results-count-badge {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      font-family: 'DM Mono', monospace;
      font-weight: 800;
      font-size: 0.95rem;
      color: var(--ink);
      letter-spacing: 0.04em;
      text-transform: uppercase;
    }

    .live-dot {
      width: 10px;
      height: 10px;
      border-radius: 50%;
      background: #10b981;
      box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.25);
      animation: pulseDot 2s infinite ease-in-out;
    }

    @keyframes pulseDot {
      0%, 100% { transform: scale(0.95); opacity: 0.85; }
      50% { transform: scale(1.3); opacity: 1; }
    }

    .results-actions-right {
      display: flex;
      align-items: center;
      gap: 14px;
    }

    .sort-select-wrap {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      font-size: 0.86rem;
      color: #64748b;
      font-weight: 600;
    }

    .sort-select-wrap select {
      background: #ffffff;
      border: 1.5px solid #cbd5e1;
      border-radius: 10px;
      padding: 7px 12px;
      font-family: 'Plus Jakarta Sans', sans-serif;
      font-size: 0.84rem;
      font-weight: 700;
      color: var(--ink);
      outline: none;
      cursor: pointer;
    }

    .view-toggle-btns {
      display: inline-flex;
      background: #ffffff;
      border: 1.5px solid #e2e8f0;
      border-radius: 12px;
      padding: 3px;
      gap: 2px;
    }

    .btn-view-toggle {
      background: transparent;
      border: none;
      width: 34px;
      height: 34px;
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1rem;
      color: #64748b;
      cursor: pointer;
      transition: all 0.15s;
    }

    .btn-view-toggle.active {
      background: var(--coral);
      color: #ffffff;
    }

    /* ── Courts Grid & Cards ───────────────────────────────────────────────── */
    .courts-results-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
      gap: 28px;
    }

    .courts-results-grid.list-view {
      grid-template-columns: 1fr !important;
    }

    .court-card {
      background: var(--card-bg);
      border-radius: 22px;
      border: 1.5px solid var(--border-soft);
      box-shadow: 0 10px 30px -8px rgba(0, 0, 0, 0.06);
      overflow: hidden;
      display: flex;
      flex-direction: column;
      transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    }

    .court-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.12);
      border-color: rgba(0, 0, 0, 0.12);
    }

    /* Image Carousel */
    .card-carousel-wrap {
      position: relative;
      width: 100%;
      height: 220px;
      background: #0f241c;
      overflow: hidden;
    }

    .carousel-track {
      width: 100%;
      height: 100%;
      display: flex;
      transition: transform 0.4s ease;
    }

    .carousel-slide {
      min-width: 100%;
      height: 100%;
      position: relative;
    }

    .carousel-slide img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
    }

    /* Floating Badges */
    .card-floating-badges {
      position: absolute;
      top: 14px;
      left: 14px;
      right: 14px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      z-index: 2;
      pointer-events: none;
    }

    .pill-badge {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      padding: 4px 10px;
      border-radius: 9999px;
      font-family: 'DM Mono', monospace;
      font-size: 0.68rem;
      font-weight: 800;
      letter-spacing: 0.04em;
      text-transform: uppercase;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
    }

    .badge-type {
      background: rgba(255, 255, 255, 0.95);
      color: #0f172a;
      border: 1px solid rgba(0, 0, 0, 0.1);
    }

    .badge-verified {
      background: var(--emerald);
      color: #ffffff;
      border: 1px solid rgba(255, 255, 255, 0.3);
    }

    /* Carousel Nav Arrows */
    .carousel-nav-btn {
      position: absolute;
      top: 50%;
      transform: translateY(-50%);
      width: 32px;
      height: 32px;
      border-radius: 50%;
      background: rgba(255, 255, 255, 0.9);
      border: none;
      color: #0c1a15;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.1rem;
      cursor: pointer;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
      z-index: 2;
      transition: all 0.2s;
      opacity: 0;
    }

    .court-card:hover .carousel-nav-btn {
      opacity: 1;
    }

    .carousel-nav-btn.prev { left: 12px; }
    .carousel-nav-btn.next { right: 12px; }

    .carousel-nav-btn:hover {
      background: #ffffff;
      transform: translateY(-50%) scale(1.08);
    }

    /* Carousel Dots */
    .carousel-dots {
      position: absolute;
      bottom: 10px;
      left: 50%;
      transform: translateX(-50%);
      display: flex;
      gap: 6px;
      z-index: 2;
    }

    .carousel-dot {
      width: 6px;
      height: 6px;
      border-radius: 50%;
      background: rgba(255, 255, 255, 0.5);
      transition: all 0.2s;
    }

    .carousel-dot.active {
      width: 18px;
      border-radius: 999px;
      background: #ffffff;
    }

    /* Card Content Body */
    .card-body {
      padding: 18px 20px 20px;
      display: flex;
      flex-direction: column;
      flex-grow: 1;
    }

    .card-location-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
      margin-bottom: 8px;
    }

    .card-location {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      font-size: 0.84rem;
      color: #475569;
      font-weight: 600;
    }

    .card-location i {
      color: var(--coral);
      font-size: 0.95rem;
    }

    .btn-favorite {
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      width: 32px;
      height: 32px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #94a3b8;
      cursor: pointer;
      font-size: 0.95rem;
      transition: all 0.15s;
    }

    .btn-favorite:hover,
    .btn-favorite.active {
      background: #fff1f2;
      border-color: #fecdd3;
      color: #e11d48;
    }

    .card-title {
      font-family: 'Outfit', sans-serif;
      font-weight: 800;
      font-size: 1.25rem;
      letter-spacing: -0.02em;
      text-transform: uppercase;
      color: var(--ink);
      margin-bottom: 12px;
    }

    /* Amenities Row */
    .card-amenities-row {
      display: flex;
      align-items: center;
      gap: 6px;
      flex-wrap: wrap;
      margin-bottom: 18px;
    }

    .amenity-chip {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      background: #f1f5f9;
      border: 1px solid #e2e8f0;
      padding: 3px 8px;
      border-radius: 6px;
      font-family: 'DM Mono', monospace;
      font-size: 0.68rem;
      font-weight: 700;
      text-transform: uppercase;
      color: #334155;
    }

    .amenity-chip.more-badge {
      background: #e2e8f0;
      color: #475569;
    }

    /* Card Footer */
    .card-footer-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      border-top: 1.5px solid #f1f5f9;
      padding-top: 14px;
      margin-top: auto;
    }

    .card-rate-col {
      display: flex;
      flex-direction: column;
    }

    .rate-label {
      font-family: 'DM Mono', monospace;
      font-size: 0.62rem;
      font-weight: 700;
      text-transform: uppercase;
      color: #94a3b8;
      letter-spacing: 0.05em;
    }

    .rate-amount {
      font-family: 'Outfit', sans-serif;
      font-weight: 900;
      font-size: 1.25rem;
      color: var(--ink);
      line-height: 1.1;
    }

    .rate-unit {
      font-size: 0.76rem;
      font-weight: 600;
      color: #64748b;
    }

    .btn-book-court {
      background: var(--coral);
      color: #ffffff;
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
      transition: all 0.2s ease;
    }

    .btn-book-court:hover {
      background: var(--coral-hover);
      transform: translateY(-1px);
      box-shadow: 0 6px 16px rgba(255, 87, 51, 0.5);
    }

    /* Skeleton Loading State */
    .skeleton-card {
      height: 380px;
      border-radius: 22px;
      background: linear-gradient(90deg, #f1f5f9 25%, #f8fafc 50%, #f1f5f9 75%);
      background-size: 600px 100%;
      animation: shimmer 1.4s infinite linear;
      border: 1.5px solid var(--border-soft);
    }

    @keyframes shimmer {
      0% { background-position: -600px 0; }
      100% { background-position: 600px 0; }
    }

    /* Empty state */
    .empty-state-card {
      grid-column: 1 / -1;
      text-align: center;
      background: #ffffff;
      border: 2px dashed #cbd5e1;
      border-radius: 24px;
      padding: 60px 24px;
      margin: 20px 0;
    }

    .empty-state-icon {
      width: 64px;
      height: 64px;
      border-radius: 50%;
      background: #fff1f2;
      color: var(--coral);
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: 1.8rem;
      margin-bottom: 16px;
    }

    /* Responsive */
    @media (max-width: 768px) {
      .search-hero-section {
        min-height: auto;
      }
      .search-hero-inner {
        padding: 94px 16px 26px;
      }
      .search-hero-title .title-ink {
        font-size: 2.2rem;
      }
      .search-hero-title .title-coral {
        font-size: 2.5rem;
      }
      .search-widget-pill {
        flex-wrap: wrap;
        border-radius: 20px;
        padding: 12px;
        gap: 10px;
      }
      .widget-divider {
        display: none;
      }
      .btn-filters-toggle,
      .btn-search-action {
        width: 100%;
        justify-content: center;
      }
      .courts-results-grid {
        grid-template-columns: 1fr;
      }
    }
  </style>
  <link rel="stylesheet" href="<?= $basePath ?>/assets/css/search-mobile.css?v=<?= filemtime(__DIR__ . '/../assets/css/search-mobile.css') ?>">
  <?php if (!empty($playerSearch)): ?>
  <link rel="stylesheet" href="<?= $basePath ?>/assets/css/streetside-theme.css?v=<?= filemtime(__DIR__ . '/../assets/css/streetside-theme.css') ?>">
  <link rel="stylesheet" href="<?= $basePath ?>/assets/css/player-pages.css?v=1">
  <?php endif; ?>
<link rel="manifest" href="/pikvero/manifest.webmanifest">
<meta name="theme-color" content="#003d2d">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="Pikvero">
<link rel="apple-touch-icon" href="/pikvero/assets/images/pwa/icon-180.png">
<script defer src="/pikvero/assets/js/components/pwa.js?v=20261010"></script>
</head>
<body class="search-page <?= !empty($playerSearch) ? 'customer-portal player-court-search' : '' ?>">

  <!-- GLOBAL REUSABLE HEADER -->
  <?php if (empty($playerSearch)) require_once __DIR__ . '/../includes/header.php'; ?>

  <!-- HERO SECTION WITH BG-SEARCH.PNG -->
  <section class="search-hero-section">
    <div class="search-hero-inner">

      <!-- Eyebrow Tag -->
      <div class="search-eyebrow">
        <i class="bi bi-geo-alt-fill"></i> BOHOL &amp; PHILIPPINES
      </div>

      <!-- Headline -->
      <h1 class="search-hero-title">
        <span class="title-ink">Find your</span>
        <span class="title-coral">court</span>
      </h1>

      <!-- Description -->
      <p class="search-hero-desc">
        Compare local courts and book a time that works for you.
      </p>

      <!-- Quick Filter Chips -->
      <div class="quick-chips-bar" id="quick-chips-container">
        <button type="button" class="quick-chip-pill active" data-filter="all" onclick="selectQuickFilter('all', this)">
          <i class="bi bi-grid-fill"></i> All Cities
        </button>
        <button type="button" class="quick-chip-pill" data-filter="popular" onclick="selectQuickFilter('popular', this)">
          <i class="bi bi-star"></i> Popular
        </button>
        <button type="button" class="quick-chip-pill" data-filter="indoor" onclick="selectQuickFilter('indoor', this)">
          <i class="bi bi-house-door"></i> Indoor
        </button>
        <button type="button" class="quick-chip-pill" data-filter="outdoor" onclick="selectQuickFilter('outdoor', this)">
          <i class="bi bi-sun"></i> Outdoor
        </button>
        <button type="button" class="quick-chip-pill" data-filter="open_now" onclick="selectQuickFilter('open_now', this)">
          <i class="bi bi-clock"></i> Open Now
        </button>
      </div>

      <!-- Floating Search Bar Widget -->
      <div class="search-widget-container">
        <form id="filter-form" autocomplete="off" onsubmit="event.preventDefault(); loadCourts();">
          <div class="search-widget-pill">
            <div class="search-input-box">
              <i class="bi bi-search search-icon"></i>
              <input type="text" id="filter-search" aria-label="Search courts or facilities" placeholder="Search courts or facilities" autocomplete="off">
              <button type="button" id="clear-search-btn" class="clear-input-btn" aria-label="Clear Search" onclick="clearSearchInput()">&times;</button>
            </div>

            <div class="widget-divider"></div>

            <button type="button" id="toggle-filter-btn" class="btn-filters-toggle" aria-expanded="false" aria-controls="filter-drawer" onclick="toggleFilterDrawer()">
              <i class="bi bi-sliders"></i>
              <span>Filters</span>
              <span id="active-filter-badge" class="filter-count-badge" style="display:none;">0</span>
            </button>

            <button type="submit" class="btn-search-action">
              <i class="bi bi-search"></i>
              <span>Search</span>
            </button>
          </div>

          <!-- Expandable Filters Drawer -->
          <div id="filter-drawer" class="filters-drawer-panel">
            <div class="filters-grid-fields">
              <div class="filter-field-group">
                <label><i class="bi bi-building"></i> Facility</label>
                <select id="filter-facility-id" onchange="loadCourts()">
                  <option value="">All Facilities</option>
                </select>
              </div>

              <div class="filter-field-group">
                <label><i class="bi bi-geo-alt"></i> Location / City</label>
                <input type="text" id="filter-city" list="cities-datalist" placeholder="e.g. Tagbilaran City">
                <datalist id="cities-datalist">
                  <?php foreach ($availableCities as $c): ?>
                    <option value="<?= htmlspecialchars($c) ?>"></option>
                  <?php endforeach; ?>
                </datalist>
              </div>

              <div class="filter-field-group">
                <label><i class="bi bi-layers"></i> Court Type</label>
                <select id="filter-type" onchange="loadCourts()">
                  <option value="">All Types (Indoor/Outdoor)</option>
                  <option value="indoor">Indoor (Aircon)</option>
                  <option value="outdoor">Outdoor</option>
                  <option value="covered">Covered Flex</option>
                </select>
              </div>

              <div class="filter-field-group">
                <label><i class="bi bi-tag"></i> Max Rate / Hr (₱)</label>
                <input type="number" id="filter-price" placeholder="e.g. 500" min="0" step="50">
              </div>

              <div class="filter-field-group">
                <label><i class="bi bi-calendar-event"></i> Play Date</label>
                <input type="date" id="filter-date" onchange="loadCourts()">
              </div>
            </div>

            <!-- Amenities Checkbox Row -->
            <div class="amenities-checkbox-row">
              <label class="amenity-toggle-pill" id="pill-lighting">
                <input type="checkbox" id="filter-lighting" onchange="toggleAmenityPill(this)">
                <i class="bi bi-moon-stars"></i> Night Lighting
              </label>
              <label class="amenity-toggle-pill" id="pill-parking">
                <input type="checkbox" id="filter-parking" onchange="toggleAmenityPill(this)">
                <i class="bi bi-p-square"></i> Parking
              </label>
              <label class="amenity-toggle-pill" id="pill-gear">
                <input type="checkbox" id="filter-gear" onchange="toggleAmenityPill(this)">
                <i class="bi bi-bag-check"></i> Gear Rental
              </label>
              <label class="amenity-toggle-pill" id="pill-shower">
                <input type="checkbox" id="filter-shower" onchange="toggleAmenityPill(this)">
                <i class="bi bi-droplet"></i> Shower
              </label>
              <label class="amenity-toggle-pill" id="pill-open">
                <input type="checkbox" id="filter-open" onchange="toggleAmenityPill(this)">
                <i class="bi bi-clock"></i> Open Now
              </label>
            </div>

            <!-- Drawer Bottom Actions -->
            <div class="drawer-actions-bar">
              <button type="button" class="btn-reset-filters" onclick="resetSearchFilters()">
                <i class="bi bi-arrow-counterclockwise"></i> Reset All
              </button>
              <button type="submit" class="btn-apply-filters">
                <i class="bi bi-check2-circle"></i> Apply Filters
              </button>
            </div>
          </div>
        </form>
      </div>

    </div>
  </section>

  <!-- RESULTS SECTION -->
  <main class="results-main-wrap">
    
    <!-- Controls Header -->
    <div class="results-controls-row">
      <div class="results-count-badge" id="results-meta">
        <span class="live-dot"></span> <span id="results-count-text">LOADING COURTS...</span>
      </div>

      <div class="results-actions-right">
        <div class="sort-select-wrap">
          <label for="sort-by">Sort by:</label>
          <select id="sort-by" onchange="applySorting()">
            <option value="nearest">Nearest</option>
            <option value="price_asc">Price: Low to High</option>
            <option value="price_desc">Price: High to Low</option>
            <option value="name">Name (A-Z)</option>
          </select>
        </div>

        <div class="view-toggle-btns">
          <button type="button" class="btn-view-toggle active" id="btn-view-grid" onclick="setViewMode('grid')" aria-label="Grid View">
            <i class="bi bi-grid-fill"></i>
          </button>
          <button type="button" class="btn-view-toggle" id="btn-view-list" onclick="setViewMode('list')" aria-label="List View">
            <i class="bi bi-list-ul"></i>
          </button>
        </div>
      </div>
    </div>

    <!-- Courts Cards Grid -->
    <div class="courts-results-grid" id="courts-results-grid">
      <div class="skeleton-card"></div>
      <div class="skeleton-card"></div>
      <div class="skeleton-card"></div>
    </div>

  </main>

  <!-- GLOBAL DESKTOP FOOTER -->
  <?php if (empty($playerSearch)): ?>
  <?php require_once __DIR__ . '/../includes/footer.php'; ?>
  <?php else: ?>
  <script src="<?= $basePath ?>/assets/js/components/bottom-nav.js?v=<?= filemtime(__DIR__ . '/../assets/js/components/bottom-nav.js') ?>"></script>
  <script>BottomNavComponent.render('explore');</script>
  <?php endif; ?>

  <!-- jQuery CDN -->
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script>
    window.APP_BASE_PATH = <?= json_encode($basePath) ?>;

    const surfaceLabelMap = {
      cushioned_acrylic: 'Cushioned Acrylic',
      asphalt:           'Asphalt Surface',
      polyurethane:      'Polyurethane Flex',
      concrete:          'Concrete'
    };

    let allLoadedCourts = [];
    let currentViewMode = 'grid';
    let cardCarouselIntervals = {};
    let searchTimer = null;

    // ── Helper: Escape HTML ──────────────────────────────────────────────────
    function escapeHtml(text) {
      if (!text) return '';
      return $('<div>').text(text).html();
    }

    // ── Toggle Filters Drawer ────────────────────────────────────────────────
    function toggleFilterDrawer() {
      const $drawer = $('#filter-drawer');
      const $btn = $('#toggle-filter-btn');
      const isExpanded = $drawer.hasClass('is-expanded');
      if (isExpanded) {
        $drawer.removeClass('is-expanded');
        $btn.removeClass('is-active').attr('aria-expanded', 'false');
      } else {
        $drawer.addClass('is-expanded');
        $btn.addClass('is-active').attr('aria-expanded', 'true');
      }
    }

    // ── Clear Search Input ───────────────────────────────────────────────────
    function clearSearchInput() {
      $('#filter-search').val('').focus();
      $('#clear-search-btn').hide();
      loadCourts();
    }

    // ── Toggle Amenity Pill ──────────────────────────────────────────────────
    function toggleAmenityPill(input) {
      const $label = $(input).closest('.amenity-toggle-pill');
      if (input.checked) {
        $label.addClass('is-checked');
      } else {
        $label.removeClass('is-checked');
      }
      updateActiveFilterCount();
      loadCourts();
    }

    // ── Quick Filter Chips ───────────────────────────────────────────────────
    function selectQuickFilter(type, btn) {
      $('.quick-chip-pill').removeClass('active');
      $(btn).addClass('active');

      if (type === 'all') {
        $('#filter-type').val('');
        $('#filter-city').val('');
        $('#filter-open').prop('checked', false);
        $('#pill-open').removeClass('is-checked');
        $('#sort-by').val('nearest');
      } else if (type === 'popular') {
        $('#sort-by').val('nearest');
      } else if (type === 'indoor') {
        $('#filter-type').val('indoor');
      } else if (type === 'outdoor') {
        $('#filter-type').val('outdoor');
      } else if (type === 'open_now') {
        $('#filter-open').prop('checked', true);
        $('#pill-open').addClass('is-checked');
      }

      updateActiveFilterCount();
      loadCourts();
    }

    // ── Reset Search Filters ─────────────────────────────────────────────────
    function resetSearchFilters() {
      $('#filter-form')[0].reset();
      const today = new Date().toISOString().split('T')[0];
      $('#filter-date').val(today);
      $('#filter-search').val('');
      $('#filter-facility-id').val('');
      $('#filter-city').val('');
      $('#filter-type').val('');
      $('#filter-price').val('');
      
      $('.amenity-toggle-pill').removeClass('is-checked');
      $('.amenity-toggle-pill input').prop('checked', false);
      
      $('.quick-chip-pill').removeClass('active');
      $('.quick-chip-pill[data-filter="all"]').addClass('active');

      $('#clear-search-btn').hide();
      updateActiveFilterCount();
      loadCourts();
    }

    // ── Update Filter Count Badge ────────────────────────────────────────────
    function updateActiveFilterCount() {
      let count = 0;
      if ($('#filter-facility-id').val()) count++;
      if ($('#filter-city').val().trim()) count++;
      if ($('#filter-type').val()) count++;
      if ($('#filter-price').val().trim()) count++;
      if ($('#filter-lighting').is(':checked')) count++;
      if ($('#filter-parking').is(':checked')) count++;
      if ($('#filter-gear').is(':checked')) count++;
      if ($('#filter-shower').is(':checked')) count++;
      if ($('#filter-open').is(':checked')) count++;

      const $badge = $('#active-filter-badge');
      if (count > 0) {
        $badge.text(count).show();
      } else {
        $badge.hide();
      }

      if ($('#filter-search').val().trim().length > 0) {
        $('#clear-search-btn').show();
      } else {
        $('#clear-search-btn').hide();
      }
    }

    // ── Card Image Carousel ──────────────────────────────────────────────────
    function buildCardCarouselHtml(idPrefix, item) {
      const carouselId = idPrefix + '-' + item.id;
      const images = (item.images && item.images.length > 0) ? item.images : [];
      const courtType = (item.court_type || 'PRO').toUpperCase();
      const isIndoor = courtType === 'INDOOR';
      const typeLabel = isIndoor ? '<i class="bi bi-house-door"></i> INDOOR' : '<i class="bi bi-brightness-high"></i> OUTDOOR';

      const floatingBadgeHtml = `
        <div class="card-floating-badges">
          <span class="pill-badge badge-type">${typeLabel}</span>
          <span class="pill-badge badge-verified"><i class="bi bi-patch-check-fill"></i> VERIFIED</span>
        </div>
      `;

      if (images.length === 0) {
        const defaultImg = item.image_path || item.cover_image || (window.APP_BASE_PATH || '') + '/assets/images/facilities/facility_1_1787966757_ad0abf1b.jpg';
        return `
          <div class="card-carousel-wrap">
            ${floatingBadgeHtml}
            <div style="width:100%; height:100%;">
              <img src="${defaultImg}" style="width:100%; height:100%; object-fit:cover;" onerror="this.onerror=null; this.src='${(window.APP_BASE_PATH || '')}/assets/images/logo.png';">
            </div>
          </div>
        `;
      }

      const slidesHtml = images.map(img => `
        <div class="carousel-slide">
          <img src="${img.image_path}" onerror="this.onerror=null; this.src='${(window.APP_BASE_PATH || '')}/assets/images/logo.png';">
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

      return `
        <div class="card-carousel-wrap">
          ${floatingBadgeHtml}
          <div id="track-${carouselId}" class="carousel-track" data-index="0">
            ${slidesHtml}
          </div>
          ${navBtnsHtml}
          ${dotsHtml}
        </div>
      `;
    }

    function navCardCarousel(e, carouselId, direction, totalCount) {
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
        dot.classList.toggle('active', idx === currentIndex);
      });
    }

    // ── Toggle Favorite ──────────────────────────────────────────────────────
    function toggleFavorite(e, courtId) {
      e.preventDefault();
      e.stopPropagation();
      const btn = $(e.currentTarget);
      btn.toggleClass('active');
      const icon = btn.find('i');
      if (btn.hasClass('active')) {
        icon.removeClass('bi-heart').addClass('bi-heart-fill');
      } else {
        icon.removeClass('bi-heart-fill').addClass('bi-heart');
      }
    }

    // ── Set View Mode (Grid vs List) ─────────────────────────────────────────
    function setViewMode(mode) {
      currentViewMode = mode;
      $('.btn-view-toggle').removeClass('active');
      if (mode === 'grid') {
        $('#btn-view-grid').addClass('active');
        $('#courts-results-grid').removeClass('list-view');
      } else {
        $('#btn-view-list').addClass('active');
        $('#courts-results-grid').addClass('list-view');
      }
    }

    // ── Apply Sorting ────────────────────────────────────────────────────────
    function applySorting() {
      const sortBy = $('#sort-by').val();
      if (!allLoadedCourts || allLoadedCourts.length === 0) return;

      let sorted = [...allLoadedCourts];
      if (sortBy === 'price_asc') {
        sorted.sort((a, b) => parseFloat(a.base_price_per_hour || 0) - parseFloat(b.base_price_per_hour || 0));
      } else if (sortBy === 'price_desc') {
        sorted.sort((a, b) => parseFloat(b.base_price_per_hour || 0) - parseFloat(a.base_price_per_hour || 0));
      } else if (sortBy === 'name') {
        sorted.sort((a, b) => (a.name || '').localeCompare(b.name || ''));
      }
      renderCourts(sorted, false);
    }

    // ── Render Courts ────────────────────────────────────────────────────────
    function renderCourts(courts, updateCache = true) {
      if (updateCache) {
        allLoadedCourts = courts || [];
      }
      const $grid = $('#courts-results-grid');
      const count = courts ? courts.length : 0;
      
      $('#results-count-text').text(`${count} ${count === 1 ? 'COURT' : 'COURTS'} AVAILABLE`);
      if (document.body.classList.contains('player-court-search') && matchMedia('(max-width:768px)').matches && window.renderExploreCourts) {
        window.renderExploreCourts(courts || []);
        return;
      }

      if (!courts || courts.length === 0) {
        $grid.html(`
          <div class="empty-state-card">
            <div class="empty-state-icon"><i class="bi bi-search"></i></div>
            <h3 style="font-family:'Outfit',sans-serif; font-size:1.4rem; font-weight:800; margin-bottom:8px;">NO MATCHING COURTS FOUND</h3>
            <p style="color:#64748b; font-size:0.9rem; max-width:460px; margin:0 auto 20px;">
              Try adjusting your search keywords, expanding city filters, or resetting filter options to see available venues.
            </p>
            <button type="button" onclick="resetSearchFilters()" class="btn-search-action" style="padding:8px 20px; font-size:0.86rem;">
              <i class="bi bi-arrow-counterclockwise"></i> Reset Filters
            </button>
          </div>
        `);
        return;
      }

      const html = courts.map(c => {
        const price = parseFloat(c.base_price_per_hour || 0).toFixed(2);
        const surface = surfaceLabelMap[c.surface_type] || c.surface_type || 'Cushioned Acrylic';
        const facility = c.facility_name || 'SmashZone Center';
        const city = c.city || 'Tagbilaran City';

        // Amenities
        let amenityChips = '';
        if (c.amenities && c.amenities.length > 0) {
          const firstTwo = c.amenities.slice(0, 2);
          firstTwo.forEach(a => {
            amenityChips += `<span class="amenity-chip"><i class="bi ${a.icon || 'bi-lightning-charge'}"></i> ${escapeHtml(a.name)}</span>`;
          });
          if (c.amenities.length > 2) {
            amenityChips += `<span class="amenity-chip more-badge">+${c.amenities.length - 2}</span>`;
          }
        } else {
          amenityChips = `
            <span class="amenity-chip"><i class="bi bi-lightbulb-fill"></i> LED LIGHTING</span>
            <span class="amenity-chip"><i class="bi bi-car-front-fill"></i> PARKING</span>
            <span class="amenity-chip more-badge">+2</span>
          `;
        }

        const carouselHtml = buildCardCarouselHtml('court', c);

        return `
          <div class="court-card">
            ${carouselHtml}
            <div class="card-body">
              <div class="card-location-row">
                <div class="card-location">
                  <i class="bi bi-geo-alt-fill"></i>
                  <span>${escapeHtml(facility)} &bull; ${escapeHtml(city)}</span>
                </div>
                <button type="button" class="btn-favorite" onclick="toggleFavorite(event, ${c.id})" aria-label="Favorite">
                  <i class="bi bi-heart"></i>
                </button>
              </div>

              <h3 class="card-title">${escapeHtml(c.name)}</h3>

              <div class="card-amenities-row">
                <span class="amenity-chip"><i class="bi bi-brightness-high"></i> ${escapeHtml(surface)}</span>
                ${amenityChips}
              </div>

              <div class="card-footer-row">
                <div class="card-rate-col">
                  <span class="rate-label">STARTING RATE</span>
                  <div class="rate-amount">&#8369;${price}<span class="rate-unit">/hr</span></div>
                </div>
                <button type="button" onclick="openFacilityBooking(${c.facility_id || 1}, ${c.id})" class="btn-book-court">
                  Book Court &rarr;
                </button>
              </div>
            </div>
          </div>
        `;
      }).join('');

      $grid.html(html);
    }

    // ── Navigate to booking ───────────────────────────────────────────────────
    function openFacilityBooking(facilityId, courtId) {
      sessionStorage.setItem('selected_facility_id', facilityId);
      sessionStorage.setItem('selected_court_id', courtId);
      const base = window.APP_BASE_PATH || '';
      window.location.href = base + '/public/facility?id=' + facilityId + '&court_id=' + courtId;
    }

    // ── Load Facilities Filter Dropdown ───────────────────────────────────────
    function populateFacilityFilter() {
      const base = window.APP_BASE_PATH || '';
      $.get(base + '/api/customer/facilities.php', function(res) {
        if (res && res.success && res.data) {
          let options = '<option value="">All Facilities</option>';
          res.data.forEach(fac => {
            options += `<option value="${fac.id}">${escapeHtml(fac.name)} (${escapeHtml(fac.city || 'Bohol')})</option>`;
          });
          $('#filter-facility-id').html(options);
        }
      });
    }

    // ── Load Courts via AJAX with Fallback ────────────────────────────────────
    function loadCourts() {
      const base = window.APP_BASE_PATH || '';
      const params = {
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
      Object.keys(params).forEach(k => { if (!params[k]) delete params[k]; });

      $('#courts-results-grid').html(`
        <div class="skeleton-card"></div>
        <div class="skeleton-card"></div>
        <div class="skeleton-card"></div>
      `);
      $('#results-count-text').text('SEARCHING COURTS...');

      $.ajax({
        url: base + '/api/customer/courts.php',
        method: 'GET',
        data: params,
        dataType: 'json',
        success: function(res) {
          if (res && res.success && Array.isArray(res.data) && res.data.length > 0) {
            renderCourts(res.data);
          } else {
            // If empty result or database offline, fallback to realistic mock courts
            fallbackMockCourts(params);
          }
        },
        error: function() {
          // Graceful fallback for offline database or connection delay
          fallbackMockCourts(params);
        }
      });
    }

    // ── Resilient Fallback Mock Courts ────────────────────────────────────────
    function fallbackMockCourts(params) {
      const base = window.APP_BASE_PATH || '';
      const defaultCourts = [
        {
          id: 15,
          facility_id: 1,
          name: 'COURT 1 - PRO',
          court_number: 1,
          court_type: 'outdoor',
          surface_type: 'cushioned_acrylic',
          base_price_per_hour: 450.00,
          facility_name: 'Reyal Solutions',
          city: 'Ubay',
          images: [
            { image_path: base + '/assets/images/facilities/facility_1_1787966757_ad0abf1b.jpg' },
            { image_path: base + '/assets/images/facilities/facility_1_1787966757_7941d551.jpg' }
          ],
          amenities: [
            { name: 'Cushioned Acrylic', icon: 'bi-brightness-high' },
            { name: 'LED Lighting', icon: 'bi-lightbulb-fill' },
            { name: 'Parking', icon: 'bi-car-front-fill' }
          ]
        },
        {
          id: 16,
          facility_id: 1,
          name: 'COURT 2 - OPEN PLAY',
          court_number: 2,
          court_type: 'indoor',
          surface_type: 'cushioned_acrylic',
          base_price_per_hour: 450.00,
          facility_name: 'SmashZone Center',
          city: 'Tagbilaran City',
          images: [
            { image_path: base + '/assets/images/facilities/facility_1_1787966757_7941d551.jpg' },
            { image_path: base + '/assets/images/facilities/facility_1_1787966757_094eac0b.jpg' }
          ],
          amenities: [
            { name: 'Air-Conditioned', icon: 'bi-snow' },
            { name: 'LED Lighting', icon: 'bi-lightbulb-fill' },
            { name: 'Rest Area', icon: 'bi-people-fill' }
          ]
        },
        {
          id: 17,
          facility_id: 1,
          name: 'COURT 3 - TRAINING ZONE',
          court_number: 3,
          court_type: 'outdoor',
          surface_type: 'polyurethane',
          base_price_per_hour: 400.00,
          facility_name: 'SmashZone Center',
          city: 'Tagbilaran City',
          images: [
            { image_path: base + '/assets/images/facilities/facility_1_1787966757_094eac0b.jpg' },
            { image_path: base + '/assets/images/facilities/facility_1_1787966757_ad0abf1b.jpg' }
          ],
          amenities: [
            { name: 'Equipment Rental', icon: 'bi-bag-check' },
            { name: 'Night Lighting', icon: 'bi-moon-stars' },
            { name: 'Parking', icon: 'bi-car-front-fill' }
          ]
        }
      ];

      // Filter locally if query passed
      let filtered = defaultCourts;
      if (params.search) {
        const q = params.search.toLowerCase();
        filtered = filtered.filter(c => c.name.toLowerCase().includes(q) || c.facility_name.toLowerCase().includes(q) || c.city.toLowerCase().includes(q));
      }
      if (params.city) {
        const cQ = params.city.toLowerCase();
        filtered = filtered.filter(c => c.city.toLowerCase().includes(cQ));
      }
      if (params.court_type) {
        filtered = filtered.filter(c => c.court_type.toLowerCase() === params.court_type.toLowerCase());
      }
      renderCourts(filtered);
    }

    // ── Document Ready ────────────────────────────────────────────────────────
    $(document).ready(function() {
      populateFacilityFilter();

      // Pre-fill query parameters
      const urlParams = new URLSearchParams(window.location.search);
      if (urlParams.get('search') || urlParams.get('q')) {
        $('#filter-search').val(urlParams.get('search') || urlParams.get('q'));
      }
      if (urlParams.get('city')) {
        $('#filter-city').val(urlParams.get('city'));
      }
      if (urlParams.get('court_type')) {
        $('#filter-type').val(urlParams.get('court_type'));
      }

      // Default date to today
      const today = new Date().toISOString().split('T')[0];
      $('#filter-date').val(today);

      // Debounced search on typing
      $('#filter-search, #filter-city, #filter-price').on('input', function() {
        updateActiveFilterCount();
        clearTimeout(searchTimer);
        searchTimer = setTimeout(loadCourts, 300);
      });

      updateActiveFilterCount();
      loadCourts();
    });
  </script>
  <?php if (!empty($playerSearch)): ?>
  <link rel="stylesheet" href="/pikvero/assets/css/explore-mobile.css?v=<?= filemtime(__DIR__.'/../assets/css/explore-mobile.css') ?>">
  <link rel="stylesheet" href="/pikvero/assets/js/vendor/leaflet.css">
  <script src="/pikvero/assets/js/vendor/leaflet.js"></script>
  <script src="/pikvero/assets/js/components/explore-mobile.js?v=<?= filemtime(__DIR__.'/../assets/js/components/explore-mobile.js') ?>"></script>
  <?php endif; ?>
</body>
</html>
