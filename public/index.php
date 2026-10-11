<?php
require_once __DIR__ . '/../app/bootstrap.php';

$courtRepo = new \App\Infrastructure\Repositories\CourtRepository();
$mostBookedList = $courtRepo->getMostReservedCourts(1);
$heroCourt = !empty($mostBookedList) ? $mostBookedList[0] : null;
$platformMetrics = $courtRepo->getPlatformPublicMetrics();

$facilityRepo = new \App\Infrastructure\Repositories\FacilityRepository();
$popularCities = $facilityRepo->getPopularCities(6);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Pikvero — Play Local | Streetside Pickleball Court Booking</title>
  <meta name="description" content="Pikvero connects players with top local pickleball courts in Bohol and across the Philippines. Real-time availability, instant online reservations, and multi-tenant court management.">
  <?php $favLogo = class_exists(\App\Core\Auth\Auth::class) ? \App\Core\Auth\Auth::getLogoUrl() : '/pikvero/assets/images/logo.png'; ?>
  <link rel="icon" type="image/png" href="<?= htmlspecialchars($favLogo) ?>?v=<?= time() ?>">
  <link rel="shortcut icon" type="image/png" href="<?= htmlspecialchars($favLogo) ?>?v=<?= time() ?>">
  <link rel="apple-touch-icon" href="<?= htmlspecialchars($favLogo) ?>?v=<?= time() ?>">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="/pikvero/assets/css/streetside-theme.css?v=<?= time() ?>">
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
    }
    @keyframes marquee-scroll {
      0% { transform: translateX(0); }
      100% { transform: translateX(-50%); }
    }
    .marquee-ticker-wrap {
      background: var(--lime);
      border-block: 2px solid var(--ink);
      padding: 8px 0;
      overflow: hidden !important;
      width: 100% !important;
      max-width: 100% !important;
      position: relative;
    }
    .marquee-ticker-track {
      display: inline-flex;
      align-items: center;
      white-space: nowrap;
      animation: marquee-scroll 24s linear infinite;
      font: 800 0.80rem 'DM Mono', monospace;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      gap: 12px;
      width: max-content;
    }
    @keyframes shimmer {
      0%   { background-position: -600px 0; }
      100% { background-position: 600px 0; }
    }
    .skeleton {
      background: linear-gradient(90deg, #e8e8e8 25%, #f5f5f5 50%, #e8e8e8 75%);
      background-size: 600px 100%;
      animation: shimmer 1.4s infinite linear;
      border-radius: 10px;
    }
    .skeleton-card { height: 160px; border-radius: 16px; }
    .skeleton-loc  { height: 110px; border-radius: 16px; }

    /* Featured Facilities grid — columns set dynamically by JS based on item count */
    .fac-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
      gap: 24px;
    }

    /* Popular Locations grid — columns set dynamically by JS based on item count */
    .loc-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
      gap: 16px;
    }

    /* Featured Courts grid — columns set dynamically by JS based on item count */
    .court-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
      gap: 20px;
    }

    /* Streetside Empty State Styling */
    .empty-state-wrap {
      grid-column: 1 / -1;
      width: 100%;
      display: flex;
      justify-content: center;
      padding: 12px 0;
    }
    .empty-state-card {
      max-width: 540px;
      width: 100%;
      text-align: center;
      padding: 38px 26px;
      border: 2px solid var(--ink);
      border-radius: 20px;
      box-shadow: 5px 5px 0 var(--ink);
      position: relative;
      background: var(--white);
      transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .empty-state-card:hover {
      transform: translateY(-2px);
      box-shadow: 7px 7px 0 var(--ink);
    }
    .empty-state-card.sand {
      background: var(--sand);
    }
    .empty-state-card.cream {
      background: var(--cream);
    }
    .empty-state-icon-wrap {
      width: 64px;
      height: 64px;
      border-radius: 50%;
      border: 2px solid var(--ink);
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: 1.85rem;
      margin-bottom: 16px;
      box-shadow: 2.5px 2.5px 0 var(--ink);
    }
    .empty-state-title {
      font-size: 1.28rem;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: -0.02em;
      line-height: 1.25;
      margin: 10px 0 8px;
      color: var(--ink);
    }
    .empty-state-desc {
      font-size: 0.88rem;
      color: #3b4e48;
      line-height: 1.55;
      margin: 0 auto 22px;
      max-width: 440px;
    }
    .empty-state-actions {
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 10px;
      flex-wrap: wrap;
    }

    /* ── Base Section Standards ────────────────────────────────────────────── */
    .homepage-section {
      padding: 60px max(4vw, 22px);
    }
    .homepage-section-title {
      font-size: clamp(1.8rem, 4vw, 2.6rem);
      font-weight: 800;
      text-transform: uppercase;
      margin: 4px 0 0;
      letter-spacing: -0.02em;
      line-height: 1.15;
    }
    .homepage-section-desc {
      font-size: 0.92rem;
      color: #3b4e48;
      line-height: 1.5;
    }

    /* Hero Section Responsive Layout */
    .hero-section {
      position: relative;
      min-height: 560px;
      padding: 120px max(4vw, 22px) 50px;
      display: grid;
      grid-template-columns: minmax(0, 1.15fr) minmax(0, 0.85fr);
      align-items: center;
      gap: 36px;
      width: 100%;
      max-width: 100%;
      overflow-x: hidden;
      background: radial-gradient(circle at 85% 15%, rgba(223, 255, 79, .55), transparent 28%),
                  linear-gradient(125deg, var(--cream) 0% 55%, #b4f1fb 55%);
    }
    @media (max-width: 992px) {
      .hero-section {
        grid-template-columns: minmax(0, 1fr) !important;
        padding-top: 86px !important;
        padding-bottom: 32px !important;
        gap: 20px !important;
        width: 100% !important;
        max-width: 100% !important;
      }
    }

    /* Live pulse effect */
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

    /* Hero Quick Search & Chips */
    .hero-search-box {
      background: var(--sand);
      border: 2px solid var(--ink);
      border-radius: 16px;
      padding: 6px;
      box-shadow: 4px 4px 0 var(--ink);
      max-width: 540px;
      margin-bottom: 12px;
      width: 100%;
      box-sizing: border-box;
    }
    .hero-search-input-group {
      display: flex;
      align-items: center;
      background: var(--white);
      border: 2px solid var(--ink);
      border-radius: 12px;
      padding: 4px 6px 4px 12px;
      gap: 8px;
      width: 100%;
      box-sizing: border-box;
    }
    .hero-search-icon {
      color: var(--coral);
      font-size: 1.05rem;
      flex-shrink: 0;
    }
    .hero-search-input-group input {
      flex: 1;
      min-width: 0;
      border: none;
      background: transparent;
      outline: none;
      font-family: inherit;
      font-weight: 700;
      font-size: 0.90rem;
      color: var(--ink);
      padding: 8px 0;
    }
    .hero-search-btn {
      flex-shrink: 0;
      padding: 8px 18px;
      font-size: 0.86rem;
      border-radius: 9px;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      white-space: nowrap;
    }
    .hero-city-chips-wrap {
      display: flex;
      align-items: center;
      gap: 8px;
      margin-bottom: 18px;
      max-width: 100%;
      overflow: hidden;
    }
    .city-chips-label {
      font-size: 0.74rem;
      color: #4a5c56;
      font-weight: 800;
      flex-shrink: 0;
    }
    .hero-city-chips {
      display: flex;
      align-items: center;
      gap: 6px;
      overflow-x: auto;
      white-space: nowrap;
      padding: 2px 2px 4px;
      scrollbar-width: none;
      -webkit-overflow-scrolling: touch;
      flex: 1;
    }
    .hero-city-chips::-webkit-scrollbar {
      display: none;
    }
    .city-chip {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      padding: 5px 12px;
      font-size: 0.76rem;
      font-family: 'DM Mono', monospace;
      font-weight: 700;
      border: 1.5px solid var(--ink);
      border-radius: 999px;
      background: var(--white);
      color: var(--ink);
      text-decoration: none;
      cursor: pointer;
      transition: all 0.15s ease;
      box-shadow: 1.5px 1.5px 0 var(--ink);
      flex-shrink: 0;
    }
    .city-chip:hover {
      background: var(--lime);
      transform: translate(-1px, -1px);
      box-shadow: 2px 2px 0 var(--ink);
      color: var(--ink);
    }
    .city-chip i {
      color: var(--coral);
      font-size: 0.82rem;
    }

    /* Trust items row */
    .hero-trust-bar {
      display: flex;
      gap: 16px;
      flex-wrap: wrap;
      margin-top: 18px;
      padding-top: 14px;
      border-top: 1px dashed rgba(13, 33, 29, 0.25);
    }
    .hero-trust-item {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      font-size: 0.78rem;
      font-weight: 700;
      color: #1a3d34;
    }
    .hero-trust-item i {
      font-size: 0.90rem;
      color: var(--green);
    }

    /* Metrics Strip */
    .metrics-bar {
      background: var(--ink);
      color: var(--white);
      border-block: 2px solid var(--ink);
      padding: 20px max(4vw, 20px);
    }
    .metrics-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 16px;
      max-width: 1200px;
      margin: 0 auto;
    }
    @media (max-width: 840px) {
      .metrics-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
      }
    }
    .metric-card {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 8px 12px;
      border-left: 3px solid var(--lime);
    }
    .metric-number {
      font-family: 'DM Mono', monospace;
      font-size: 1.75rem;
      font-weight: 800;
      color: var(--lime);
      line-height: 1;
    }
    .metric-label {
      font-size: 0.75rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.04em;
      color: #cfd8d4;
      line-height: 1.25;
      margin-top: 3px;
    }

    /* Step Cards */
    .step-card {
      background: var(--white);
      border: 2px solid var(--ink);
      border-radius: 18px;
      padding: 24px 20px;
      box-shadow: 4px 4px 0 var(--ink);
      position: relative;
      transition: transform 0.2s ease, box-shadow 0.2s ease;
      display: flex;
      flex-direction: column;
      align-items: center;
      text-align: center;
    }
    .step-card:hover {
      transform: translateY(-3px);
      box-shadow: 6px 6px 0 var(--ink);
    }
    .step-num-pill {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 48px;
      height: 48px;
      border-radius: 14px;
      border: 2px solid var(--ink);
      font-size: 1.3rem;
      font-family: 'DM Mono', monospace;
      font-weight: 900;
      margin-bottom: 14px;
      box-shadow: 3px 3px 0 var(--ink);
    }

    /* Testimonial Cards */
    .testimonial-card {
      background: var(--white);
      border: 2px solid var(--ink);
      border-radius: 18px;
      padding: 24px;
      box-shadow: 4px 4px 0 var(--ink);
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .testimonial-card:hover {
      transform: translateY(-3px);
      box-shadow: 6px 6px 0 var(--ink);
    }
    .author-badge {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-top: 16px;
      padding-top: 12px;
      border-top: 1.5px dashed rgba(13, 33, 29, 0.2);
    }
    .author-avatar {
      width: 42px;
      height: 42px;
      border-radius: 50%;
      border: 2px solid var(--ink);
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 800;
      font-family: 'DM Mono', monospace;
      font-size: 1rem;
      flex-shrink: 0;
      box-shadow: 2px 2px 0 var(--ink);
    }

    /* Court Owner Section */
    .owner-section-grid {
      display: grid;
      grid-template-columns: 1.15fr 0.85fr;
      align-items: center;
      gap: 36px;
      max-width: 1100px;
      margin: 0 auto;
    }
    @media (max-width: 860px) {
      .owner-section-grid {
        grid-template-columns: 1fr;
        text-align: center;
      }
    }
    .owner-feature-item {
      display: flex;
      align-items: center;
      gap: 10px;
      background: rgba(255, 255, 255, 0.16);
      border: 1.5px solid rgba(255, 255, 255, 0.35);
      padding: 8px 14px;
      border-radius: 12px;
      font-size: 0.84rem;
      font-weight: 700;
      color: var(--white);
    }
    @media (max-width: 860px) {
      .owner-feature-item {
        justify-content: center;
      }
    }

    /* Card Micro-polish */
    .court-card-verified {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      font-size: 0.68rem;
      font-family: 'DM Mono', monospace;
      font-weight: 700;
      color: #065f46;
      background: #d1fae5;
      border: 1px solid #10b981;
      border-radius: 6px;
      padding: 2px 7px;
    }

    /* ── Mobile Compact Responsive Polish (<= 768px) ────────────────────────── */
    @media (max-width: 768px) {
      /* Mobile Navbar overrides */
      .nav-links,
      .navbar-auth-desktop {
        display: none !important;
      }
      .hamburger-toggle-btn {
        display: inline-flex !important;
      }
      .nav-streetside {
        top: 8px !important;
        height: 54px !important;
        padding: 0 12px !important;
      }

      /* Hero Section */
      .hero-section {
        padding: 72px 14px 22px !important;
        gap: 16px !important;
        min-height: auto !important;
      }
      .hero-eyebrow {
        font-size: 0.68rem !important;
        gap: 5px !important;
      }
      .hero-headline {
        font-size: 1.95rem !important;
        line-height: 0.98 !important;
        letter-spacing: -0.04em !important;
        margin: 6px 0 8px !important;
      }
      .hero-subtext {
        font-size: 0.82rem !important;
        line-height: 1.38 !important;
        margin-bottom: 12px !important;
        color: #273b35 !important;
      }
      .hero-search-box {
        padding: 5px !important;
        border-radius: 14px !important;
        margin-bottom: 10px !important;
        box-shadow: 3px 3px 0 var(--ink) !important;
      }
      .hero-search-input-group {
        padding: 3px 4px 3px 10px !important;
        border-radius: 10px !important;
        gap: 6px !important;
      }
      .hero-search-input-group input {
        padding: 6px 0 !important;
        font-size: 0.80rem !important;
      }
      .hero-search-btn {
        padding: 6px 12px !important;
        font-size: 0.78rem !important;
        border-radius: 8px !important;
      }
      .hero-city-chips-wrap {
        margin-bottom: 12px !important;
      }
      .hero-city-chips .city-chip {
        padding: 4px 10px !important;
        font-size: 0.70rem !important;
      }
      .hero-action-btns {
        display: grid !important;
        grid-template-columns: 1.1fr 0.9fr !important;
        gap: 8px !important;
        margin-bottom: 12px !important;
        width: 100% !important;
      }
      .hero-action-btns a {
        padding: 9px 10px !important;
        font-size: 0.78rem !important;
        justify-content: center !important;
        text-align: center !important;
        border-radius: 10px !important;
        box-shadow: 2px 2px 0 var(--ink) !important;
      }
      .hero-btn-primary {
        grid-column: 1 / -1 !important;
        padding: 10px 14px !important;
        font-size: 0.84rem !important;
      }
      .hero-trust-bar {
        gap: 6px 10px !important;
        margin-top: 10px !important;
        padding-top: 10px !important;
      }
      .hero-trust-item {
        font-size: 0.68rem !important;
        gap: 4px !important;
      }

      /* Hero Featured Card */
      #hero-featured-card .card-streetside {
        padding: 16px 14px !important;
        min-height: auto !important;
        border-radius: 16px !important;
        box-shadow: 4px 4px 0 var(--ink) !important;
      }
      .hero-card-header {
        gap: 6px !important;
      }
      .hero-card-header .badge-streetside,
      .hero-card-header .mono {
        font-size: 0.65rem !important;
        padding: 3px 7px !important;
      }
      #hero-featured-card .trophy-icon-wrap {
        width: 48px !important;
        height: 48px !important;
        margin: 0 auto 8px !important;
      }
      #hero-featured-card .trophy-icon-wrap i {
        font-size: 1.5rem !important;
      }
      #hero-featured-card h3 {
        font-size: 1.25rem !important;
        margin: 4px 0 2px !important;
      }
      #hero-featured-card p {
        font-size: 0.78rem !important;
        margin-bottom: 8px !important;
        line-height: 1.35 !important;
      }
      #hero-featured-card .hero-card-footer {
        padding: 8px 10px !important;
        border-radius: 10px !important;
        gap: 8px !important;
      }

      /* Metrics Bar */
      .metrics-bar {
        padding: 12px 10px !important;
      }
      .metrics-grid {
        grid-template-columns: repeat(2, 1fr) !important;
        gap: 8px !important;
      }
      .metric-card {
        padding: 6px 8px !important;
        border-left: 2.5px solid var(--lime) !important;
        gap: 8px !important;
      }
      .metric-number {
        font-size: 1.35rem !important;
      }
      .metric-label {
        font-size: 0.65rem !important;
        margin-top: 2px !important;
      }

      /* Sections & Headers */
      .homepage-section {
        padding: 32px 14px !important;
      }
      .homepage-section-header {
        margin-bottom: 16px !important;
      }
      .homepage-section-title {
        font-size: 1.45rem !important;
        margin: 2px 0 0 !important;
      }
      .homepage-section-desc {
        font-size: 0.82rem !important;
      }

      /* Courts Grid & Cards */
      #featured-courts-grid {
        grid-template-columns: 1fr !important;
        gap: 14px !important;
        margin-bottom: 18px !important;
      }
      .card-carousel-wrap {
        height: 165px !important;
      }
      .card-body-streetside {
        padding: 14px 12px !important;
      }
      .card-body-streetside h3 {
        font-size: 1.18rem !important;
        margin-bottom: 4px !important;
      }
      .card-body-streetside p {
        font-size: 0.78rem !important;
        margin-bottom: 12px !important;
      }
      .card-body-streetside .button {
        padding: 8px 14px !important;
        font-size: 0.78rem !important;
      }

      /* Locations Grid */
      #popular-locations-grid {
        grid-template-columns: repeat(2, 1fr) !important;
        gap: 8px !important;
      }
      .location-card-link {
        padding: 14px 12px !important;
        border-radius: 12px !important;
        box-shadow: 3px 3px 0 var(--ink) !important;
        gap: 6px !important;
      }
      .location-card-link h4 {
        font-size: 1.05rem !important;
        margin: 2px 0 !important;
      }
      .location-card-link .loc-icon {
        width: 36px !important;
        height: 36px !important;
        font-size: 1.15rem !important;
        border-radius: 10px !important;
      }

      /* How it Works Step Cards */
      .step-cards-grid {
        grid-template-columns: 1fr !important;
        gap: 10px !important;
      }
      .step-card {
        flex-direction: row !important;
        align-items: flex-start !important;
        text-align: left !important;
        padding: 12px 14px !important;
        gap: 12px !important;
        border-radius: 14px !important;
        box-shadow: 3px 3px 0 var(--ink) !important;
      }
      .step-num-pill {
        width: 36px !important;
        height: 36px !important;
        font-size: 1.05rem !important;
        border-radius: 10px !important;
        margin-bottom: 0 !important;
        flex-shrink: 0 !important;
        box-shadow: 2px 2px 0 var(--ink) !important;
      }
      .step-card h3 {
        font-size: 1.0rem !important;
        margin: 0 0 3px !important;
      }
      .step-card p {
        font-size: 0.78rem !important;
        line-height: 1.38 !important;
      }

      /* Facilities Grid */
      #featured-facilities-grid {
        grid-template-columns: 1fr !important;
        gap: 14px !important;
      }

      /* Packages Grid */
      #popular-packages-grid {
        grid-template-columns: 1fr !important;
        gap: 10px !important;
      }
      .package-card-streetside {
        padding: 16px 14px !important;
        border-radius: 14px !important;
        box-shadow: 3px 3px 0 var(--ink) !important;
      }
      .package-card-streetside h3 {
        font-size: 1.25rem !important;
        margin: 4px 0 !important;
      }
      .package-card-streetside p {
        font-size: 0.80rem !important;
        margin-bottom: 12px !important;
      }

      /* Testimonials Grid */
      .testimonials-grid {
        grid-template-columns: 1fr !important;
        gap: 10px !important;
      }
      .testimonial-card {
        padding: 14px !important;
        border-radius: 14px !important;
        box-shadow: 3px 3px 0 var(--ink) !important;
      }
      .testimonial-card p {
        font-size: 0.84rem !important;
        line-height: 1.45 !important;
        margin-bottom: 10px !important;
      }
      .author-badge {
        margin-top: 10px !important;
        padding-top: 10px !important;
        gap: 10px !important;
      }
      .author-avatar {
        width: 36px !important;
        height: 36px !important;
        font-size: 0.88rem !important;
      }

      /* Owner Section */
      .owner-cta-section {
        padding: 34px 14px !important;
      }
      .owner-section-grid {
        gap: 20px !important;
      }
      .owner-cta-title {
        font-size: 1.55rem !important;
        line-height: 1.1 !important;
        margin: 6px 0 10px !important;
      }
      .owner-cta-desc {
        font-size: 0.86rem !important;
        margin-bottom: 16px !important;
      }
      .owner-feature-item {
        padding: 5px 8px !important;
        font-size: 0.74rem !important;
        border-radius: 8px !important;
      }
      .owner-cta-buttons {
        gap: 8px !important;
      }
      .owner-cta-buttons a {
        padding: 10px 18px !important;
        font-size: 0.84rem !important;
      }
      .owner-callout-card {
        padding: 18px 14px !important;
        border-radius: 14px !important;
        box-shadow: 4px 4px 0 var(--ink) !important;
      }
      .owner-callout-card h4 {
        font-size: 1.15rem !important;
      }
    }
  </style>
<link rel="manifest" href="/pikvero/manifest.webmanifest">
<meta name="theme-color" content="#003d2d">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="Pikvero">
<link rel="apple-touch-icon" href="/pikvero/assets/images/pwa/icon-180.png">
<script defer src="/pikvero/assets/js/components/pwa.js?v=20261010"></script>
</head>
<body>

  <?php require_once __DIR__ . '/../includes/header.php'; ?>

  <!-- 1. HERO SEARCH SECTION -->
  <section class="hero-section">
    <div>
      <div class="eyebrow hero-eyebrow" style="display:inline-flex; align-items:center; gap:8px;">
        <span class="pulse-dot"></span> BOHOL &amp; PHILIPPINES &bull; LIVE RESERVATIONS
      </div>
      <h1 class="hero-headline" style="font-size:clamp(2.8rem, 6vw, 5.6rem); line-height:0.92; letter-spacing:-0.06em; text-transform:uppercase; margin:16px 0 20px;">
        RESERVE <br><em style="color:var(--coral); font-style:normal;">COURTS.</em> <br>PLAY LOCAL.
      </h1>
      <p class="hero-subtext" style="max-width:520px; color:#294039; font-size:1.05rem; margin-bottom:24px; line-height:1.5;">
        Pikvero connects players with top local pickleball courts. Experience real-time court availability, instant online reservations, and automated booking protection.
      </p>

      <!-- Search Input Box -->
      <form action="/pikvero/public/search.php" method="GET" class="hero-search-box">
        <div class="hero-search-input-group">
          <i class="bi bi-geo-alt-fill hero-search-icon"></i>
          <input id="hero-search-city" type="text" name="city" placeholder="Search city or location (e.g. Tagbilaran)" autocomplete="off">
          <button type="submit" class="button coral hero-search-btn">
            <i class="bi bi-search"></i> <span>Find Courts</span>
          </button>
        </div>
      </form>

      <!-- Quick City Chips (Dynamic from Database) -->
      <div class="hero-city-chips-wrap" <?= empty($popularCities) ? 'style="display:none;"' : '' ?>>
        <span class="city-chips-label mono">POPULAR:</span>
        <div class="hero-city-chips" id="hero-city-chips-container">
          <?php foreach ($popularCities as $pCity): ?>
            <button type="button" class="city-chip" onclick="quickSearchCity('<?= htmlspecialchars($pCity, ENT_QUOTES) ?>')">
              <i class="bi bi-geo-alt-fill"></i> <?= htmlspecialchars($pCity) ?>
            </button>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="hero-action-btns">
        <a href="/pikvero/public/search.php" class="button coral hero-btn-primary">
          <i class="bi bi-calendar-check"></i> Explore All Courts &rarr;
        </a>
        <a href="/pikvero/public/pricing.php" class="button lime hero-btn-secondary">
          <i class="bi bi-tag-fill"></i> Court Pricing
        </a>
        <a href="/pikvero/public/register.php?type=owner" class="button dark hero-btn-tertiary">
          <i class="bi bi-building-add"></i> List Facility
        </a>
      </div>

      <!-- Trust Bar -->
      <div class="hero-trust-bar">
        <div class="hero-trust-item"><i class="bi bi-patch-check-fill"></i> Verified Courts</div>
        <div class="hero-trust-item"><i class="bi bi-shield-lock-fill"></i> Anti-Conflict Lock</div>
        <div class="hero-trust-item"><i class="bi bi-lightning-charge-fill"></i> Instant Booking Slip</div>
      </div>
    </div>

    <!-- Hero Card Showcase: Most Booked Court from Database -->
    <div id="hero-featured-card" style="position:relative;">
      <?php if ($heroCourt && (int)($heroCourt['total_reservations'] ?? 0) > 0): ?>
        <?php
          $hcPrice = number_format((float)($heroCourt['base_price_per_hour'] ?? 0), 2);
          $hcSurface = ucwords(str_replace('_', ' ', $heroCourt['surface_type'] ?? 'cushioned_acrylic'));
          $hcTypeBadge = ($heroCourt['court_type'] === 'indoor') ? 'Indoor Aircon' : (($heroCourt['court_type'] === 'covered') ? 'Covered Flex' : 'LED Floodlights');
          $hcRating = $heroCourt['avg_rating'] !== null ? number_format((float)$heroCourt['avg_rating'], 1) : null;
          $hcTotalReviews = (int)($heroCourt['total_reviews'] ?? 0);
          $hcBookings = (int)($heroCourt['total_reservations'] ?? 0);
          $hcBookingLabel = $hcBookings . ($hcBookings === 1 ? ' Booking' : ' Bookings');
          $hcFacility = $heroCourt['facility_name'] ?? '';
          $hcCity = $heroCourt['city'] ?? '';
          $hcCourtName = $heroCourt['name'] ?? '';
          $hcBookingUrl = '/pikvero/public/facility.php?id=' . ($heroCourt['facility_id'] ?? 1) . '&court_id=' . ($heroCourt['id'] ?? 1);
        ?>
        <div class="card-streetside sky hero-showcase-card" style="border-radius:24px; padding:24px 20px; display:flex; flex-direction:column; justify-content:space-between; min-height:390px; box-shadow:8px 8px 0 var(--ink);">
          <div class="hero-card-header" style="display:flex; justify-content:space-between; align-items:center; gap:8px; flex-wrap:wrap;">
            <span class="badge-streetside lime" style="display:inline-flex; align-items:center; gap:6px;">
              <span class="pulse-dot"></span> LIVE ON-COURT
            </span>
            <span class="mono" style="font-size:0.75rem; font-weight:800; background:var(--ink); color:var(--white); padding:4px 9px; border-radius:6px;">MOST BOOKED COURT</span>
          </div>
          <div style="text-align:center; margin:18px 0 14px;">
            <div class="trophy-icon-wrap" style="width:64px; height:64px; margin:0 auto 12px; background:var(--white); border:2px solid var(--ink); border-radius:50%; display:flex; align-items:center; justify-content:center; box-shadow:3px 3px 0 var(--ink);">
              <i class="bi bi-trophy-fill" style="font-size:2rem; color:var(--coral);"></i>
            </div>
            <h3 style="font-size:1.65rem; font-weight:800; text-transform:uppercase; margin:8px 0 4px; letter-spacing:-0.02em; line-height:1.15;"><?= htmlspecialchars($hcFacility) ?></h3>
            <p style="font-size:0.86rem; color:#1a3d34; font-weight:600; margin:0 0 10px;"><i class="bi bi-geo-alt-fill" style="color:var(--coral);"></i> <?= htmlspecialchars($hcCity) ?> &bull; <?= htmlspecialchars($hcCourtName) ?></p>
            <div style="display:inline-flex; gap:6px; flex-wrap:wrap; justify-content:center;">
              <span class="badge-streetside sand" style="font-size:0.68rem;"><i class="bi bi-layers-fill"></i> <?= htmlspecialchars($hcSurface) ?></span>
              <span class="badge-streetside sand" style="font-size:0.68rem;"><i class="bi bi-brightness-high"></i> <?= htmlspecialchars($hcTypeBadge) ?></span>
              <span class="badge-streetside sand" style="font-size:0.68rem;"><i class="bi bi-calendar-check"></i> <?= htmlspecialchars($hcBookingLabel) ?></span>
              <?php if ($hcRating !== null && $hcTotalReviews > 0): ?>
                <span class="badge-streetside sand" style="font-size:0.68rem;">&#9733; <?= htmlspecialchars($hcRating) ?> (<?= $hcTotalReviews ?> <?= $hcTotalReviews === 1 ? 'Review' : 'Reviews' ?>)</span>
              <?php endif; ?>
            </div>
          </div>
          <div class="hero-card-footer" style="display:flex; justify-content:space-between; align-items:center; border-top:2px solid var(--ink); padding:12px 14px; margin-top:6px; flex-wrap:wrap; gap:10px; background:rgba(255,255,255,0.45); border-radius:14px;">
            <div>
              <span class="mono" style="display:block; font-size:0.65rem; color:#3b4e48; font-weight:800;">STARTING RATE</span>
              <strong style="font-size:1.35rem; font-family:'DM Mono', monospace; color:var(--ink);">&#8369;<?= htmlspecialchars($hcPrice) ?><span style="font-size:0.75rem; font-weight:500;">/hr</span></strong>
            </div>
            <a href="<?= htmlspecialchars($hcBookingUrl) ?>" class="button lime" style="padding:9px 16px; font-size:0.82rem; display:inline-flex; align-items:center; gap:6px;">
              <i class="bi bi-calendar-plus"></i> Book Time Slot
            </a>
          </div>
        </div>
      <?php else: ?>
        <div class="card-streetside sand hero-showcase-card" style="border-radius:24px; padding:26px 18px; display:flex; flex-direction:column; justify-content:space-between; min-height:370px; box-shadow:8px 8px 0 var(--ink); text-align:center;">
          <div class="hero-card-header" style="display:flex; justify-content:space-between; align-items:center; gap:8px; flex-wrap:wrap;">
            <span class="badge-streetside dark" style="font-size:0.70rem; letter-spacing:0.04em;">
              <i class="bi bi-trophy"></i> FEATURED SHOWCASE
            </span>
            <span class="mono" style="font-size:0.70rem; font-weight:800; background:var(--ink); color:var(--white); padding:3px 8px; border-radius:6px;">NO POPULAR COURTS YET</span>
          </div>
          <div style="margin:20px 0 16px;">
            <div class="trophy-icon-wrap" style="width:60px; height:60px; margin:0 auto 12px; background:var(--white); border:2px solid var(--ink); border-radius:50%; display:flex; align-items:center; justify-content:center; box-shadow:3px 3px 0 var(--ink);">
              <i class="bi bi-calendar2-x" style="font-size:1.85rem; color:var(--coral);"></i>
            </div>
            <h3 style="font-size:1.45rem; font-weight:800; text-transform:uppercase; margin:6px 0 6px; letter-spacing:-0.02em; line-height:1.2;">NO POPULAR COURTS YET</h3>
            <p style="font-size:0.84rem; color:#4a5c56; line-height:1.45; max-width:340px; margin:0 auto 12px;">
              No court reservations have been recorded yet. Once players start booking time slots, the most popular court will be showcased here live.
            </p>
          </div>
          <div class="hero-card-footer" style="display:flex; justify-content:center; align-items:center; gap:8px; border-top:2px dashed var(--ink); padding-top:14px; flex-wrap:wrap;">
            <a href="/pikvero/public/search.php" class="button coral" style="padding:9px 16px; font-size:0.82rem; display:inline-flex; align-items:center; gap:6px;">
              <i class="bi bi-search"></i> Browse Courts
            </a>
            <a href="/pikvero/public/register.php?type=owner" class="button dark" style="padding:9px 16px; font-size:0.82rem; display:inline-flex; align-items:center; gap:6px;">
              <i class="bi bi-building-add"></i> List Your Facility
            </a>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </section>

  <!-- Marquee Ticker -->
  <div class="marquee-ticker-wrap">
    <div class="marquee-ticker-track">
      <span>&#9733; INSTANT RESERVATIONS</span> &bull; <span>ZERO DOUBLE BOOKING</span> &bull; <span>&#9733; MULTI-TENANT SAAS</span> &bull; <span>TOURNAMENT GRADE COURTS</span> &bull; <span>&#9733; NIGHT LIGHTING AVAILABLE</span> &bull; <span>AUTOMATED BOOKING SLIPS</span> &bull; <span>&#9733; INSTANT RESERVATIONS</span> &bull; <span>ZERO DOUBLE BOOKING</span> &bull; <span>&#9733; MULTI-TENANT SAAS</span> &bull; <span>TOURNAMENT GRADE COURTS</span> &bull; <span>&#9733; NIGHT LIGHTING AVAILABLE</span> &bull; <span>AUTOMATED BOOKING SLIPS</span>
    </div>
  </div>

  <!-- PLATFORM METRICS STRIP -->
  <section class="metrics-bar">
    <div class="metrics-grid">
      <div class="metric-card">
        <div>
          <div class="metric-number"><?= htmlspecialchars($platformMetrics['courts_display'] ?? '0') ?></div>
          <div class="metric-label">Courts in <?= htmlspecialchars($platformMetrics['primary_province'] ?? 'Bohol') ?> &amp; Beyond</div>
        </div>
      </div>
      <div class="metric-card">
        <div>
          <div class="metric-number"><?= htmlspecialchars($platformMetrics['lock_display'] ?? '100%') ?></div>
          <div class="metric-label">Atomic Anti-Conflict Lock</div>
        </div>
      </div>
      <div class="metric-card">
        <div>
          <div class="metric-number"><?= htmlspecialchars($platformMetrics['hours_display'] ?? '0') ?></div>
          <div class="metric-label">Court Hours Booked</div>
        </div>
      </div>
      <div class="metric-card">
        <div>
          <div class="metric-number"><?= htmlspecialchars($platformMetrics['speed_display'] ?? '< 60s') ?></div>
          <div class="metric-label">Fast Mobile Reservation</div>
        </div>
      </div>
    </div>
  </section>

  <!-- 2. FEATURED COURTS SECTION -->
  <section class="homepage-section" style="background:var(--white);">
    <div class="homepage-section-header" style="margin-bottom:28px; display:flex; justify-content:space-between; align-items:flex-end; flex-wrap:wrap; gap:12px;">
      <div>
        <div class="eyebrow">MOST RESERVED VENUES</div>
        <h2 class="homepage-section-title">TOP RESERVED COURTS</h2>
      </div>
      <a href="/pikvero/public/search.php" class="mono" style="text-decoration:underline; font-weight:700; color:var(--ink);">VIEW ALL COURTS &rarr;</a>
    </div>
    <div id="featured-courts-grid" class="court-grid" style="margin-bottom:28px;">
      <div class="skeleton skeleton-card"></div>
      <div class="skeleton skeleton-card"></div>
      <div class="skeleton skeleton-card"></div>
      <div class="skeleton skeleton-card"></div>
    </div>
    <div style="text-align:center;">
      <a href="/pikvero/public/search.php" class="button coral" style="padding:11px 24px; font-size:0.88rem; display:inline-flex; align-items:center; gap:8px;">
        <i class="bi bi-grid-fill"></i> View All Courts &rarr;
      </a>
    </div>
  </section>

  <!-- 3. POPULAR LOCATIONS SECTION -->
  <section class="homepage-section" style="background:var(--sand); border-top:2px solid var(--ink);">
    <div class="homepage-section-header" style="margin-bottom:28px;">
      <div class="eyebrow">METRO &amp; PROVINCIAL HUBS</div>
      <h2 class="homepage-section-title">POPULAR PLAY LOCATIONS</h2>
    </div>
    <div id="popular-locations-grid" class="loc-grid">
      <div class="skeleton skeleton-loc"></div>
      <div class="skeleton skeleton-loc"></div>
      <div class="skeleton skeleton-loc"></div>
      <div class="skeleton skeleton-loc"></div>
    </div>
  </section>

  <!-- 4. HOW IT WORKS SECTION -->
  <section class="homepage-section" style="background:var(--cream); border-top:2px solid var(--ink);">
    <div class="homepage-section-header" style="text-align:center; max-width:640px; margin:0 auto 36px;">
      <div class="eyebrow">SIMPLE 3-STEP RESERVATION</div>
      <h2 class="homepage-section-title">HOW PIKVERO WORKS</h2>
      <p class="homepage-section-desc">Book court hours in under a minute with complete transparency and zero reservation friction.</p>
    </div>
    <div class="step-cards-grid" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(260px, 1fr)); gap:20px;">
      <div class="step-card">
        <div class="step-num-pill" style="background:var(--lime); color:var(--ink);">1</div>
        <div>
          <h3 style="font-size:1.20rem; font-weight:800; text-transform:uppercase; margin:0 0 6px; letter-spacing:-0.02em;">1. SEARCH COURTS</h3>
          <p style="font-size:0.86rem; color:#3b4e48; margin:0; line-height:1.45;">Browse pickleball facilities by city, court surface (cushioned acrylic or concrete), night lighting, and hourly rates.</p>
        </div>
      </div>
      <div class="step-card">
        <div class="step-num-pill" style="background:var(--sky); color:var(--ink);">2</div>
        <div>
          <h3 style="font-size:1.20rem; font-weight:800; text-transform:uppercase; margin:0 0 6px; letter-spacing:-0.02em;">2. SELECT TIME SLOT</h3>
          <p style="font-size:0.86rem; color:#3b4e48; margin:0; line-height:1.45;">Pick your preferred date and time slot with real-time atomic locking to ensure you never get double-booked.</p>
        </div>
      </div>
      <div class="step-card">
        <div class="step-num-pill" style="background:var(--coral); color:var(--white);">3</div>
        <div>
          <h3 style="font-size:1.20rem; font-weight:800; text-transform:uppercase; margin:0 0 6px; letter-spacing:-0.02em;">3. CONFIRM &amp; PLAY</h3>
          <p style="font-size:0.86rem; color:#3b4e48; margin:0; line-height:1.45;">Receive instant reservation details and reference code. Present your pass at the venue and start playing!</p>
        </div>
      </div>
    </div>
  </section>

  <!-- 5. FEATURED FACILITIES SECTION -->
  <section class="homepage-section" style="border-top:2px solid var(--ink); background:var(--white);">
    <div class="homepage-section-header" style="display:flex; justify-content:space-between; align-items:flex-end; margin-bottom:28px; flex-wrap:wrap; gap:12px;">
      <div>
        <div class="eyebrow">FEATURED VENUES</div>
        <h2 class="homepage-section-title">TOP PICKLEBALL CENTERS</h2>
      </div>
      <a href="/pikvero/public/search.php" class="mono" style="text-decoration:underline; font-weight:700; color:var(--ink);">VIEW ALL &rarr;</a>
    </div>
    <div id="featured-facilities-grid" class="fac-grid">
      <div class="skeleton skeleton-card"></div>
      <div class="skeleton skeleton-card"></div>
      <div class="skeleton skeleton-card"></div>
    </div>
  </section>

  <!-- 6. POPULAR PACKAGES SECTION -->
  <section class="homepage-section" style="background:var(--sand); border-top:2px solid var(--ink);">
    <div class="homepage-section-header" style="margin-bottom:28px;">
      <div class="eyebrow">PLAYER SAVINGS</div>
      <h2 class="homepage-section-title">POPULAR HOUR PACKAGES</h2>
    </div>
    <div id="popular-packages-grid" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:18px;">
      <div class="skeleton skeleton-card"></div>
      <div class="skeleton skeleton-card"></div>
    </div>
  </section>

  <!-- 7. TESTIMONIALS SECTION -->
  <section class="homepage-section" style="background:var(--white); border-top:2px solid var(--ink);">
    <div class="homepage-section-header" style="margin-bottom:28px; text-align:center; max-width:600px; margin-left:auto; margin-right:auto;">
      <div class="eyebrow">COMMUNITY FEEDBACK</div>
      <h2 class="homepage-section-title">WHAT PLAYERS &amp; OPERATORS SAY</h2>
      <p class="homepage-section-desc">Real feedback from active pickleball community members across Bohol.</p>
    </div>
    <div class="testimonials-grid" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(290px, 1fr)); gap:18px;">
      <!-- Review 1 -->
      <div class="testimonial-card">
        <div>
          <div style="color:#f59e0b; font-size:1.1rem; margin-bottom:10px; letter-spacing:2px;">&#9733;&#9733;&#9733;&#9733;&#9733;</div>
          <p style="font-size:0.90rem; color:#2d3f3a; line-height:1.5; margin:0 0 14px;">
            "Booking tournament practice slots on Pikvero takes less than 30 seconds. No more back-and-forth messaging or arriving only to find a booked court."
          </p>
        </div>
        <div class="author-badge">
          <div class="author-avatar" style="background:var(--lime); color:var(--ink);">JD</div>
          <div>
            <strong style="display:block; font-size:0.86rem; text-transform:uppercase; font-weight:800;">Juan Dela Cruz</strong>
            <span class="mono" style="font-size:0.72rem; color:var(--green); font-weight:700;">Club Player &bull; Tagbilaran</span>
          </div>
        </div>
      </div>
      <!-- Review 2 -->
      <div class="testimonial-card">
        <div>
          <div style="color:#f59e0b; font-size:1.1rem; margin-bottom:10px; letter-spacing:2px;">&#9733;&#9733;&#9733;&#9733;&#9733;</div>
          <p style="font-size:0.90rem; color:#2d3f3a; line-height:1.5; margin:0 0 14px;">
            "Our group plays every weekend in Panglao. The instant confirmation pass on mobile makes scheduling hassle-free and super organized."
          </p>
        </div>
        <div class="author-badge">
          <div class="author-avatar" style="background:var(--sky); color:var(--ink);">SR</div>
          <div>
            <strong style="display:block; font-size:0.86rem; text-transform:uppercase; font-weight:800;">Sofia Reyes</strong>
            <span class="mono" style="font-size:0.72rem; color:var(--green); font-weight:700;">Weekend Social Player</span>
          </div>
        </div>
      </div>
      <!-- Review 3 -->
      <div class="testimonial-card">
        <div>
          <div style="color:#f59e0b; font-size:1.1rem; margin-bottom:10px; letter-spacing:2px;">&#9733;&#9733;&#9733;&#9733;&#9733;</div>
          <p style="font-size:0.90rem; color:#2d3f3a; line-height:1.5; margin:0 0 14px;">
            "The schedule blockouts and instant double booking prevention saved our court operations. Customer disputes dropped to absolute zero."
          </p>
        </div>
        <div class="author-badge">
          <div class="author-avatar" style="background:var(--coral); color:var(--white);">MV</div>
          <div>
            <strong style="display:block; font-size:0.86rem; text-transform:uppercase; font-weight:800;">Marcus Vance</strong>
            <span class="mono" style="font-size:0.72rem; color:var(--green); font-weight:700;">Facility Owner &bull; SmashZone</span>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- 8. BECOME A COURT OWNER CTA SECTION -->
  <section class="homepage-section owner-cta-section" style="background:var(--coral); color:var(--white); border-top:2px solid var(--ink);">
    <div class="owner-section-grid">
      <div>
        <div class="eyebrow" style="background:var(--ink); color:var(--white); margin-bottom:10px; padding:4px 8px; border-radius:4px; display:inline-block;">OWNER SAAS PLATFORM</div>
        <h2 class="owner-cta-title" style="font-size:clamp(1.9rem, 4.5vw, 3.0rem); font-weight:800; text-transform:uppercase; margin:8px 0 14px; line-height:1.08;">
          LIST YOUR PICKLEBALL FACILITY ON PIKVERO
        </h2>
        <p class="owner-cta-desc" style="font-size:0.98rem; opacity:0.95; margin-bottom:20px; line-height:1.48; max-width:540px;">
          Empower your sports complex with automated court scheduling, peak/off-peak rate rules, instant customer reservations, and revenue analytics.
        </p>
        <div style="display:flex; flex-wrap:wrap; gap:8px; margin-bottom:22px;">
          <div class="owner-feature-item"><i class="bi bi-check-circle-fill" style="color:var(--lime);"></i> Real-Time Schedule Locks</div>
          <div class="owner-feature-item"><i class="bi bi-check-circle-fill" style="color:var(--lime);"></i> Custom Peak Pricing Rules</div>
          <div class="owner-feature-item"><i class="bi bi-check-circle-fill" style="color:var(--lime);"></i> Multi-Court Management</div>
          <div class="owner-feature-item"><i class="bi bi-check-circle-fill" style="color:var(--lime);"></i> Instant SMS / QR Slips</div>
        </div>
        <div class="owner-cta-buttons" style="display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
          <a href="/pikvero/public/register.php?type=owner" class="button lime" style="padding:12px 24px; font-size:0.90rem;">
            <i class="bi bi-building-add"></i> List Your Facility &rarr;
          </a>
          <a href="/pikvero/public/pricing.php" class="button dark" style="padding:12px 20px; font-size:0.86rem;">
            View Owner Pricing
          </a>
        </div>
      </div>
      <div style="display:flex; justify-content:center;">
        <div class="card-streetside sand owner-callout-card" style="padding:24px; max-width:380px; width:100%; text-align:center; color:var(--ink);">
          <div style="width:52px; height:52px; border-radius:12px; background:var(--lime); border:2px solid var(--ink); display:inline-flex; align-items:center; justify-content:center; font-size:1.6rem; margin-bottom:12px; box-shadow:3px 3px 0 var(--ink);">
            <i class="bi bi-speedometer2"></i>
          </div>
          <h4 style="font-size:1.25rem; font-weight:800; text-transform:uppercase; margin:0 0 6px;">GET STARTED IN 5 MINUTES</h4>
          <p style="font-size:0.84rem; color:#3b4e48; line-height:1.45; margin-bottom:16px;">
            Set up operating hours, configure court types, and begin accepting reservations immediately.
          </p>
          <a href="/pikvero/public/register.php?type=owner" class="button coral" style="width:100%; padding:10px; font-size:0.86rem; text-align:center; display:block;">
            Register Facility Now
          </a>
        </div>
      </div>
    </div>
  </section>

  <!-- 9. FOOTER SECTION -->
  <div id="footer-container"></div>

  <!-- jQuery CDN -->
  <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
  <script src="/pikvero/assets/js/core/toast.js"></script>
  <script src="/pikvero/assets/js/core/ajax.js"></script>
  <script src="/pikvero/assets/js/core/auth.js"></script>
  <script src="/pikvero/assets/js/components/navbar.js?v=<?= time() ?>"></script>
  <script src="/pikvero/assets/js/components/footer.js?v=<?= time() ?>"></script>
  <script>
    // Court type badge helper
    function getCourtBadge(courtType) {
      var map = {
        indoor:  { label: 'INDOOR AIRCON', cls: 'lime'  },
        outdoor: { label: 'OUTDOOR',       cls: 'sky'   },
        covered: { label: 'COVERED FLEX',  cls: 'green' }
      };
      return map[courtType] || { label: (courtType ? courtType.toUpperCase() : 'COURT'), cls: 'lime' };
    }

    // Surface label formatter
    function getSurfaceLabel(surface) {
      if (!surface) return 'Standard Surface';
      var map = {
        'cushioned_acrylic': 'Cushioned Acrylic',
        'acrylic': 'Cushioned Acrylic',
        'asphalt': 'Hard Asphalt',
        'concrete': 'Reinforced Concrete',
        'polyurethane': 'Pro Polyurethane',
        'wood': 'Hardwood Parquet'
      };
      return map[surface] || surface.replace(/_/g, ' ').replace(/\b\w/g, function(l) { return l.toUpperCase(); });
    }

    // Quick search city trigger
    function quickSearchCity(cityName) {
      var $input = $('#hero-search-city');
      if ($input.length) {
        $input.val(cityName);
        $input.closest('form').submit();
      } else {
        window.location.href = '/pikvero/public/search.php?city=' + encodeURIComponent(cityName);
      }
    }

    // Render dynamic hero city chips from database
    function renderHeroCityChips(cities) {
      var $wrap = $('.hero-city-chips-wrap');
      var $container = $('#hero-city-chips-container');
      if (!cities || cities.length === 0) {
        $wrap.hide();
        return;
      }
      var html = $.map(cities, function(cityName) {
        var safeCity = escapeHtml(cityName);
        return '<button type="button" class="city-chip" onclick="quickSearchCity(\'' + escapeHtml(cityName).replace(/'/g, "\\'") + '\')">'
          + '<i class="bi bi-geo-alt-fill"></i> ' + safeCity
          + '</button>';
      }).join('');
      $container.html(html);
      $wrap.show();
    }

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
    function buildCardCarouselHtml(idPrefix, item) {
      const carouselId = idPrefix + '-' + item.id;
      const images = (item.images && item.images.length > 0) ? item.images : [];

      // Use taller image height for clear visibility
      const imgHeight = (idPrefix === 'facility' || idPrefix === 'court') ? '240px' : '200px';

      if (images.length === 0) {
        const defaultImg = item.image_path || item.cover_image || '/pikvero/assets/images/logo.png';
        return `
          <div class="card-carousel-wrap" style="position:relative; height:${imgHeight}; width:100%; border-bottom:2px solid var(--ink); overflow:hidden; background:#111; border-top-left-radius:14px; border-top-right-radius:14px;">
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
        <div style="position:absolute; bottom:10px; left:50%; transform:translateX(-50%); display:flex; gap:5px; z-index:5;">
          ${images.map((_, idx) => `<span class="dot-${carouselId} ${idx === 0 ? 'active' : ''}" style="width:7px; height:7px; border-radius:50%; background:${idx === 0 ? 'var(--lime)' : 'rgba(255,255,255,0.65)'}; transition:all 0.2s;"></span>`).join('')}
        </div>
      ` : '';

      const navBtnsHtml = images.length > 1 ? `
        <button onclick="navCardCarousel(event, '${carouselId}', -1, ${images.length})" style="position:absolute; left:8px; top:50%; transform:translateY(-50%); background:rgba(13,33,29,0.78); color:#fff; border:1px solid var(--ink); border-radius:50%; width:30px; height:30px; display:flex; align-items:center; justify-content:center; cursor:pointer; font-weight:800; font-size:1rem; z-index:6;">&lsaquo;</button>
        <button onclick="navCardCarousel(event, '${carouselId}', 1, ${images.length})" style="position:absolute; right:8px; top:50%; transform:translateY(-50%); background:rgba(13,33,29,0.78); color:#fff; border:1px solid var(--ink); border-radius:50%; width:30px; height:30px; display:flex; align-items:center; justify-content:center; cursor:pointer; font-weight:800; font-size:1rem; z-index:6;">&rsaquo;</button>
      ` : '';

      if (images.length > 1) {
        setTimeout(() => startCardCarouselAutoplay(carouselId, images.length), 100);
      }

      return `
        <div class="card-carousel-wrap" style="position:relative; height:${imgHeight}; width:100%; border-bottom:2px solid var(--ink); overflow:hidden; background:#111; border-top-left-radius:14px; border-top-right-radius:14px;">
          <div id="track-${carouselId}" style="display:flex; height:100%; width:100%; transition:transform 0.35s ease;" data-index="0">
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
        dot.style.background = (idx === currentIndex) ? 'var(--lime)' : 'rgba(255,255,255,0.65)';
        dot.style.transform = (idx === currentIndex) ? 'scale(1.3)' : 'scale(1)';
      });

      if (isManual && totalCount > 1) {
        startCardCarouselAutoplay(carouselId, totalCount);
      }
    }

    // Helper: Build Neo-brutalist empty state card
    function buildEmptyStateHtml(opts) {
      var icon = opts.icon || 'bi-info-circle-fill';
      var iconBg = opts.iconBg || 'var(--lime)';
      var iconColor = opts.iconColor || 'var(--ink)';
      var badge = opts.badge || '';
      var badgeCls = opts.badgeCls || 'dark';
      var title = opts.title || 'NO DATA AVAILABLE';
      var message = opts.message || '';
      var cardTheme = opts.cardTheme ? ' ' + opts.cardTheme : '';
      var primaryBtn = opts.primaryBtn || null;
      var secondaryBtn = opts.secondaryBtn || null;

      var badgeHtml = badge 
        ? '<div style="margin-bottom:8px;"><span class="badge-streetside ' + badgeCls + '" style="font-size:0.68rem; letter-spacing:0.06em;">' + badge + '</span></div>'
        : '';

      var btnsHtml = '';
      if (primaryBtn || secondaryBtn) {
        btnsHtml += '<div class="empty-state-actions">';
        if (primaryBtn) {
          btnsHtml += '<a href="' + primaryBtn.href + '" class="button ' + (primaryBtn.cls || 'coral') + '" style="padding:9px 18px; font-size:0.84rem;">'
            + (primaryBtn.icon ? '<i class="bi ' + primaryBtn.icon + '"></i> ' : '')
            + primaryBtn.text
            + '</a>';
        }
        if (secondaryBtn) {
          btnsHtml += '<a href="' + secondaryBtn.href + '" class="button ' + (secondaryBtn.cls || 'sand') + '" style="padding:9px 18px; font-size:0.84rem;">'
            + (secondaryBtn.icon ? '<i class="bi ' + secondaryBtn.icon + '"></i> ' : '')
            + secondaryBtn.text
            + '</a>';
        }
        btnsHtml += '</div>';
      }

      return '<div class="empty-state-wrap">'
        + '<div class="empty-state-card' + cardTheme + '">'
        + '<div class="empty-state-icon-wrap" style="background:' + iconBg + '; color:' + iconColor + ';">'
        + '<i class="bi ' + icon + '"></i>'
        + '</div>'
        + badgeHtml
        + '<h3 class="empty-state-title">' + title + '</h3>'
        + '<p class="empty-state-desc">' + message + '</p>'
        + btnsHtml
        + '</div>'
        + '</div>';
    }

    // Render: Featured Courts
    function renderFeaturedCourts(courts) {
      var $grid = $('#featured-courts-grid');
      if (!courts || courts.length === 0) {
        $grid.css('grid-template-columns', '1fr');
        $grid.html(buildEmptyStateHtml({
          icon: 'bi-trophy',
          iconBg: 'var(--coral)',
          iconColor: 'var(--white)',
          badge: 'POPULAR COURTS',
          badgeCls: 'dark',
          title: 'NO POPULAR COURTS YET',
          message: 'No courts have recorded bookings yet to qualify as popular. Browse all active courts across Bohol and be the first to book a session!',
          cardTheme: 'sand',
          primaryBtn: {
            href: '/pikvero/public/search.php',
            text: 'Explore All Courts',
            icon: 'bi-search',
            cls: 'coral'
          },
          secondaryBtn: {
            href: '/pikvero/public/register.php?type=owner',
            text: 'List Your Facility',
            icon: 'bi-building-add',
            cls: 'dark'
          }
        }));
        return;
      }

      // Dynamically set columns based on count so 1 or 2 courts look good
      var count = courts.length;
      if (count === 1) {
        $grid.css('grid-template-columns', 'minmax(0, 640px)');
        $grid.css('justify-content', 'center');
      } else if (count === 2) {
        $grid.css('grid-template-columns', 'repeat(2, minmax(0, 1fr))');
        $grid.css('max-width', '900px');
        $grid.css('margin', '0 auto 32px');
      } else {
        $grid.css('grid-template-columns', 'repeat(auto-fill, minmax(280px, 1fr))');
      }

      var html = $.map(courts, function(c) {
        var badge = getCourtBadge(c.court_type);
        var surface = getSurfaceLabel(c.surface_type);
        var price = parseFloat(c.base_price_per_hour || 0).toFixed(2);
        var parts = [];
        if (c.facility_name) parts.push(c.facility_name);
        if (c.city) parts.push(c.city);
        var loc = parts.join(' &bull; ');

        var carouselHtml = buildCardCarouselHtml('court', c);
        var targetUrl = '/pikvero/public/facility.php?id=' + (c.facility_id || 1) + '&court_id=' + c.id;

        var bookings = parseInt(c.total_reservations || 0, 10);
        var bookingsBadge = bookings > 0
          ? '<span class="badge-streetside sand" style="font-size:0.65rem;"><i class="bi bi-calendar-check"></i> ' + bookings + (bookings === 1 ? ' Booking' : ' Bookings') + '</span>'
          : '';

        var rating = c.avg_rating ? parseFloat(c.avg_rating).toFixed(1) : null;
        var totalReviews = parseInt(c.total_reviews || 0, 10);
        var ratingBadge = (rating && totalReviews > 0)
          ? '<span class="badge-streetside sand" style="font-size:0.65rem;">&#9733; ' + rating + '</span>'
          : '';

        return '<div class="card-streetside" style="padding:0; overflow:hidden; display:flex; flex-direction:column; background:var(--white); border-radius:16px;">'
          + carouselHtml
          + '<div class="card-body-streetside" style="padding:22px; flex:1; display:flex; flex-direction:column; justify-content:space-between;">'
          + '<div>'
          + '<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; gap:8px; flex-wrap:wrap;">'
          + '<div style="display:flex; align-items:center; gap:6px; flex-wrap:wrap;">'
          + '<span class="badge-streetside ' + badge.cls + '" style="font-size:0.68rem;">' + badge.label + '</span>'
          + '<span class="court-card-verified"><i class="bi bi-patch-check-fill"></i> VERIFIED</span>'
          + bookingsBadge
          + ratingBadge
          + '</div>'
          + '<span class="badge-streetside sand" style="font-size:0.65rem;"><i class="bi bi-layers-fill"></i> ' + surface + '</span>'
          + '</div>'
          + '<h3 style="font-size:1.35rem; font-weight:800; text-transform:uppercase; margin:0 0 6px; letter-spacing:-0.02em;">' + escapeHtml(c.name) + '</h3>'
          + '<p style="font-size:0.86rem; color:#4a5c56; margin-bottom:18px; line-height:1.4;"><i class="bi bi-geo-alt-fill" style="color:var(--coral);"></i> ' + escapeHtml(loc) + '</p>'
          + '</div>'
          + '<div style="display:flex; justify-content:space-between; align-items:center; border-top:2px solid var(--ink); padding-top:14px; margin-top:6px; flex-wrap:wrap; gap:10px;">'
          + '<div>'
          + '<span class="mono" style="display:block; font-size:0.65rem; color:#526961; font-weight:800;">PRICE / HOUR</span>'
          + '<strong style="font-size:1.35rem; font-family:\'DM Mono\', monospace; color:var(--ink);">&#8369;' + price + '</strong>'
          + '</div>'
          + '<a href="' + targetUrl + '" class="button coral" style="padding:10px 18px; font-size:0.84rem; display:inline-flex; align-items:center; gap:6px;">Book Court &rarr;</a>'
          + '</div>'
          + '</div>'
          + '</div>';
      }).join('');
      $grid.html(html);
    }

    // Render: Popular Locations
    function renderLocations(locations) {
      var $grid = $('#popular-locations-grid');
      if (!locations || locations.length === 0) {
        $grid.css('grid-template-columns', '1fr');
        $grid.html(buildEmptyStateHtml({
          icon: 'bi-geo-alt-fill',
          iconBg: 'var(--coral)',
          iconColor: 'var(--white)',
          badge: 'COMMUNITY NETWORK',
          badgeCls: 'lime',
          title: 'NO PLAY LOCATIONS FOUND',
          message: 'We are expanding to new cities and sports complexes across Bohol and beyond. Own or operate a court? Put your location on the pickleball map!',
          cardTheme: '',
          primaryBtn: {
            href: '/pikvero/public/register.php?type=owner',
            text: 'List Your Facility',
            icon: 'bi-building-add',
            cls: 'dark'
          },
          secondaryBtn: {
            href: '/pikvero/public/search.php',
            text: 'Explore All Courts',
            icon: 'bi-compass',
            cls: 'coral'
          }
        }));
        return;
      }

      // Dynamically adjust columns based on count
      var count = locations.length;
      if (count === 1) {
        $grid.css('grid-template-columns', 'minmax(0, 400px)');
        $grid.css('justify-content', 'center');
      } else if (count === 2) {
        $grid.css('grid-template-columns', 'repeat(2, minmax(0, 1fr))');
        $grid.css('max-width', '640px');
        $grid.css('margin', '0 auto');
      } else if (count === 3) {
        $grid.css('grid-template-columns', 'repeat(3, minmax(0, 1fr))');
      } else {
        $grid.css('grid-template-columns', 'repeat(auto-fill, minmax(200px, 1fr))');
      }

      var html = $.map(locations, function(loc) {
        var n = parseInt(loc.court_count, 10) || 0;
        var label = n > 0 ? (n + ' Court' + (n > 1 ? 's' : '') + ' Active') : 'Active Courts';
        return '<a href="/pikvero/public/search.php?city=' + encodeURIComponent(loc.city) + '"'
          + ' class="card-streetside location-card-link" style="padding:22px; background:var(--white); text-decoration:none; color:inherit; display:flex; flex-direction:column; align-items:flex-start; gap:10px; border-radius:16px; transition:transform 0.2s ease, box-shadow 0.2s ease;">'
          + '<div class="loc-icon" style="width:44px; height:44px; border-radius:12px; border:2px solid var(--ink); background:var(--sky); display:flex; align-items:center; justify-content:center; box-shadow:2.5px 2.5px 0 var(--ink);">'
          + '<i class="bi bi-geo-alt-fill" style="font-size:1.35rem; color:var(--ink);"></i>'
          + '</div>'
          + '<div>'
          + '<h4 style="font-size:1.3rem; font-weight:800; text-transform:uppercase; margin:4px 0 3px; line-height:1.15; letter-spacing:-0.02em;">' + loc.city + '</h4>'
          + '<span class="badge-streetside green" style="font-size:0.65rem;">' + label + '</span>'
          + '</div>'
          + '<span class="mono" style="font-size:0.75rem; font-weight:800; color:var(--coral); margin-top:auto; padding-top:10px; display:inline-flex; align-items:center; gap:4px;">EXPLORE COURTS &rarr;</span>'
          + '</a>';
      }).join('');
      $grid.html(html);
    }

    // Render: Featured Facilities
    function renderFeaturedFacilities(facilities) {
      var $grid = $('#featured-facilities-grid');
      if (!facilities || facilities.length === 0) {
        $grid.css('grid-template-columns', '1fr');
        $grid.html(buildEmptyStateHtml({
          icon: 'bi-building-check',
          iconBg: 'var(--lime)',
          iconColor: 'var(--ink)',
          badge: 'PARTNER VENUES',
          badgeCls: 'dark',
          title: 'NO FACILITIES LISTED YET',
          message: 'Partner venues are getting their court profiles and schedules ready. Run a pickleball center? Partner with Pikvero today.',
          cardTheme: 'sand',
          primaryBtn: {
            href: '/pikvero/public/register.php?type=owner',
            text: 'Partner With Us',
            icon: 'bi-building-add',
            cls: 'lime'
          },
          secondaryBtn: {
            href: '/pikvero/public/search.php',
            text: 'Browse Courts',
            icon: 'bi-search',
            cls: 'sand'
          }
        }));
        return;
      }

      // Dynamically set columns based on count so 1 or 2 facilities look good
      var count = facilities.length;
      if (count === 1) {
        $grid.css('grid-template-columns', 'minmax(0, 640px)');
        $grid.css('justify-content', 'center');
      } else if (count === 2) {
        $grid.css('grid-template-columns', 'repeat(2, minmax(0, 1fr))');
        $grid.css('max-width', '900px');
        $grid.css('margin', '0 auto');
      } else {
        $grid.css('grid-template-columns', 'repeat(auto-fill, minmax(300px, 1fr))');
      }

      var html = $.map(facilities, function(f) {
        var price = f.min_price ? parseFloat(f.min_price).toFixed(2) : '350.00';
        var desc  = f.description || f.address || '';
        var carouselHtml = buildCardCarouselHtml('facility', f);

        return '<div class="card-streetside" style="padding:0; overflow:hidden; display:flex; flex-direction:column; background:var(--white); border-radius:16px;">'
          + carouselHtml
          + '<div class="card-body-streetside" style="padding:22px; flex:1; display:flex; flex-direction:column; justify-content:space-between;">'
          + '<div>'
          + '<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; flex-wrap:wrap; gap:6px;">'
          + '<span class="badge-streetside green" style="font-size:0.7rem;"><i class="bi bi-geo-alt-fill"></i> ' + f.city + '</span>'
          + '<span class="mono" style="font-weight:800; font-size:0.78rem; background:var(--sand); padding:3px 8px; border:1px solid var(--ink); border-radius:6px;">' + (f.total_courts || 0) + ' Courts</span>'
          + '</div>'
          + '<h3 style="font-size:1.4rem; font-weight:800; text-transform:uppercase; margin:0 0 8px; letter-spacing:-0.02em;">' + f.name + '</h3>'
          + '<p style="font-size:0.86rem; color:#4a5c56; margin-bottom:16px; line-height:1.5;">' + desc + '</p>'
          + '</div>'
          + '<div style="display:flex; justify-content:space-between; align-items:center; border-top:2px solid var(--ink); padding-top:14px; margin-top:6px; flex-wrap:wrap; gap:10px;">'
          + '<div>'
          + '<span class="mono" style="display:block; font-size:0.65rem; color:#526961; font-weight:800;">STARTING RATE</span>'
          + '<strong style="font-size:1.3rem; font-family:\'DM Mono\', monospace; color:var(--ink);">&#8369;' + price + '<span style="font-size:0.72rem; font-weight:500;">/hr</span></strong>'
          + '</div>'
          + '<button onclick="selectFacilityBooking(' + f.id + ')" class="button coral" style="padding:10px 18px; font-size:0.82rem; display:inline-flex; align-items:center; gap:6px;">View Courts &rarr;</button>'
          + '</div>'
          + '</div>'
          + '</div>';
      }).join('');
      $grid.html(html);
    }

    // Render: Popular Hour Packages
    function renderPopularPackages(packages) {
      var $grid = $('#popular-packages-grid');
      if (!packages || packages.length === 0) {
        $grid.css('grid-template-columns', '1fr');
        $grid.html(buildEmptyStateHtml({
          icon: 'bi-ticket-perforated-fill',
          iconBg: 'var(--lime)',
          iconColor: 'var(--ink)',
          badge: 'SAVINGS & PASSES',
          badgeCls: 'dark',
          title: 'NO HOUR PACKAGES CURRENTLY AVAILABLE',
          message: 'Facility managers are curating seasonal passes and multi-hour packages. In the meantime, you can reserve individual court hours with live availability!',
          cardTheme: '',
          primaryBtn: {
            href: '/pikvero/public/search.php',
            text: 'Reserve Court Hours',
            icon: 'bi-calendar-check',
            cls: 'coral'
          },
          secondaryBtn: {
            href: '/pikvero/public/pricing.php',
            text: 'View Hourly Rates',
            icon: 'bi-tag-fill',
            cls: 'sand'
          }
        }));
        return;
      }

      $grid.css('grid-template-columns', 'repeat(auto-fit, minmax(280px, 1fr))');
      var cardThemes = ['lime', 'sky', 'green', 'sand'];

      var html = $.map(packages, function(pkg, idx) {
        var theme = cardThemes[idx % cardThemes.length];
        var badge = pkg.discount_badge || (pkg.total_hours ? pkg.total_hours + ' HOURS CREDIT' : 'PLAYER PASS');
        var price = parseFloat(pkg.price || 0).toFixed(2);
        var validityDays = pkg.validity_days || 30;
        var desc = pkg.description || ('Flexible ' + (pkg.total_hours || 'multi') + ' court hours usable across all participating facilities. Valid for ' + validityDays + ' days.');

        return '<div class="card-streetside package-card-streetside ' + theme + '" style="padding:26px; display:flex; flex-direction:column; justify-content:space-between; position:relative; border-radius:16px;">'
          + '<div>'
          + '<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; gap:8px;">'
          + '<span class="badge-streetside dark" style="font-size:0.68rem; letter-spacing:0.04em;">' + badge + '</span>'
          + '<span class="mono" style="font-size:0.7rem; font-weight:800; color:#1a3d34;"><i class="bi bi-clock-history"></i> ' + validityDays + ' DAYS</span>'
          + '</div>'
          + '<h3 style="font-size:1.45rem; font-weight:800; text-transform:uppercase; margin:8px 0 8px; letter-spacing:-0.02em;">' + pkg.name + '</h3>'
          + '<p style="font-size:0.86rem; color:#1a3d34; margin-bottom:18px; line-height:1.45;">' + desc + '</p>'
          + '</div>'
          + '<div style="display:flex; justify-content:space-between; align-items:center; border-top:2px dashed var(--ink); padding-top:16px; margin-top:10px; flex-wrap:wrap; gap:10px;">'
          + '<div>'
          + '<span class="mono" style="display:block; font-size:0.65rem; color:#1a3d34; font-weight:800;">TOTAL BUNDLE</span>'
          + '<strong style="font-size:1.45rem; font-family:\'DM Mono\', monospace; color:var(--ink);">&#8369;' + price + '</strong>'
          + '</div>'
          + '<a href="/pikvero/public/register.php" class="button coral" style="padding:9px 16px; font-size:0.82rem; display:inline-flex; align-items:center; gap:6px;">Claim Pass &rarr;</a>'
          + '</div>'
          + '</div>';
      }).join('');

      $grid.html(html);
    }

    // Helper: Escape HTML strings for safety
    function escapeHtml(text) {
      if (!text) return '';
      return $('<div>').text(text).html();
    }

    // Render: Most Booked Court for Hero Showcase
    function renderHeroFeaturedCard(court) {
      var $container = $('#hero-featured-card');
      if (!$container.length) return;

      var bookings = court ? parseInt(court.total_reservations || 0, 10) : 0;
      if (!court || !court.id || bookings <= 0) {
        $container.html(`
          <div class="card-streetside sand hero-showcase-card" style="border-radius:24px; padding:26px 18px; display:flex; flex-direction:column; justify-content:space-between; min-height:370px; box-shadow:8px 8px 0 var(--ink); text-align:center;">
            <div class="hero-card-header" style="display:flex; justify-content:space-between; align-items:center; gap:8px; flex-wrap:wrap;">
              <span class="badge-streetside dark" style="font-size:0.70rem; letter-spacing:0.04em;">
                <i class="bi bi-trophy"></i> FEATURED SHOWCASE
              </span>
              <span class="mono" style="font-size:0.70rem; font-weight:800; background:var(--ink); color:var(--white); padding:3px 8px; border-radius:6px;">NO POPULAR COURTS YET</span>
            </div>
            <div style="margin:20px 0 16px;">
              <div class="trophy-icon-wrap" style="width:60px; height:60px; margin:0 auto 12px; background:var(--white); border:2px solid var(--ink); border-radius:50%; display:flex; align-items:center; justify-content:center; box-shadow:3px 3px 0 var(--ink);">
                <i class="bi bi-calendar2-x" style="font-size:1.85rem; color:var(--coral);"></i>
              </div>
              <h3 style="font-size:1.45rem; font-weight:800; text-transform:uppercase; margin:6px 0 6px; letter-spacing:-0.02em; line-height:1.2;">NO POPULAR COURTS YET</h3>
              <p style="font-size:0.84rem; color:#4a5c56; line-height:1.45; max-width:340px; margin:0 auto 12px;">
                No court reservations have been recorded yet. Once players start booking time slots, the most popular court will be showcased here live.
              </p>
            </div>
            <div class="hero-card-footer" style="display:flex; justify-content:center; align-items:center; gap:8px; border-top:2px dashed var(--ink); padding-top:14px; flex-wrap:wrap;">
              <a href="/pikvero/public/search.php" class="button coral" style="padding:9px 16px; font-size:0.82rem; display:inline-flex; align-items:center; gap:6px;">
                <i class="bi bi-search"></i> Browse Courts
              </a>
              <a href="/pikvero/public/register.php?type=owner" class="button dark" style="padding:9px 16px; font-size:0.82rem; display:inline-flex; align-items:center; gap:6px;">
                <i class="bi bi-building-add"></i> List Your Facility
              </a>
            </div>
          </div>
        `);
        return;
      }

      var price = parseFloat(court.base_price_per_hour || 0).toFixed(2);
      var surface = getSurfaceLabel(court.surface_type);
      var typeBadge = (court.court_type === 'indoor') ? 'Indoor Aircon' : ((court.court_type === 'covered') ? 'Covered Flex' : 'LED Floodlights');
      var rating = court.avg_rating ? parseFloat(court.avg_rating).toFixed(1) : null;
      var totalReviews = parseInt(court.total_reviews || 0, 10);
      var bookingLabel = bookings + (bookings === 1 ? ' Booking' : ' Bookings');
      var facility = court.facility_name || '';
      var city = court.city || '';
      var courtName = court.name || '';
      var bookingUrl = '/pikvero/public/facility.php?id=' + (court.facility_id || 1) + '&court_id=' + court.id;

      var ratingBadgeHtml = '';
      if (rating && totalReviews > 0) {
        ratingBadgeHtml = '<span class="badge-streetside sand" style="font-size:0.68rem;">&#9733; ' + rating + ' (' + totalReviews + ' ' + (totalReviews === 1 ? 'Review' : 'Reviews') + ')</span>';
      }

      $container.html(`
        <div class="card-streetside sky hero-showcase-card" style="border-radius:24px; padding:24px 20px; display:flex; flex-direction:column; justify-content:space-between; min-height:390px; box-shadow:8px 8px 0 var(--ink);">
          <div class="hero-card-header" style="display:flex; justify-content:space-between; align-items:center; gap:8px; flex-wrap:wrap;">
            <span class="badge-streetside lime" style="display:inline-flex; align-items:center; gap:6px;">
              <span class="pulse-dot"></span> LIVE ON-COURT
            </span>
            <span class="mono" style="font-size:0.75rem; font-weight:800; background:var(--ink); color:var(--white); padding:4px 9px; border-radius:6px;">MOST BOOKED COURT</span>
          </div>
          <div style="text-align:center; margin:18px 0 14px;">
            <div class="trophy-icon-wrap" style="width:64px; height:64px; margin:0 auto 12px; background:var(--white); border:2px solid var(--ink); border-radius:50%; display:flex; align-items:center; justify-content:center; box-shadow:3px 3px 0 var(--ink);">
              <i class="bi bi-trophy-fill" style="font-size:2rem; color:var(--coral);"></i>
            </div>
            <h3 style="font-size:1.65rem; font-weight:800; text-transform:uppercase; margin:8px 0 4px; letter-spacing:-0.02em; line-height:1.15;">${escapeHtml(facility)}</h3>
            <p style="font-size:0.86rem; color:#1a3d34; font-weight:600; margin:0 0 10px;"><i class="bi bi-geo-alt-fill" style="color:var(--coral);"></i> ${escapeHtml(city)} &bull; ${escapeHtml(courtName)}</p>
            <div style="display:inline-flex; gap:6px; flex-wrap:wrap; justify-content:center;">
              <span class="badge-streetside sand" style="font-size:0.68rem;"><i class="bi bi-layers-fill"></i> ${surface}</span>
              <span class="badge-streetside sand" style="font-size:0.68rem;"><i class="bi bi-brightness-high"></i> ${typeBadge}</span>
              <span class="badge-streetside sand" style="font-size:0.68rem;"><i class="bi bi-calendar-check"></i> ${bookingLabel}</span>
              ${ratingBadgeHtml}
            </div>
          </div>
          <div class="hero-card-footer" style="display:flex; justify-content:space-between; align-items:center; border-top:2px solid var(--ink); padding:12px 14px; margin-top:6px; flex-wrap:wrap; gap:10px; background:rgba(255,255,255,0.45); border-radius:14px;">
            <div>
              <span class="mono" style="display:block; font-size:0.65rem; color:#3b4e48; font-weight:800;">STARTING RATE</span>
              <strong style="font-size:1.35rem; font-family:'DM Mono', monospace; color:var(--ink);">&#8369;${price}<span style="font-size:0.75rem; font-weight:500;">/hr</span></strong>
            </div>
            <a href="${bookingUrl}" class="button lime" style="padding:9px 16px; font-size:0.82rem; display:inline-flex; align-items:center; gap:6px;">
              <i class="bi bi-calendar-plus"></i> Book Time Slot
            </a>
          </div>
        </div>
      `);
    }

    // Navigate to facility booking page
    function selectFacilityBooking(facilityId) {
      window.location.href = '/pikvero/public/facility.php?id=' + facilityId;
    }

    // Load all homepage data via jQuery AJAX on DOM ready
    $(document).ready(function () {
      NavbarComponent.render();
      FooterComponent.render();

      $.ajax({
        url: '/pikvero/api/customer/home.php',
        method: 'GET',
        dataType: 'json',
        success: function (res) {
          if (res.success && res.data) {
            if (res.data.popular_cities && res.data.popular_cities.length > 0) {
              renderHeroCityChips(res.data.popular_cities);
            } else if (res.data.locations && res.data.locations.length > 0) {
              renderHeroCityChips(res.data.locations.map(function(l) { return l.city; }));
            }

            if (res.data.most_booked_court) {
              renderHeroFeaturedCard(res.data.most_booked_court);
            } else if (res.data.featured_courts && res.data.featured_courts.length > 0) {
              renderHeroFeaturedCard(res.data.featured_courts[0]);
            } else {
              renderHeroFeaturedCard(null);
            }
            renderFeaturedCourts(res.data.featured_courts);
            renderLocations(res.data.locations);
            renderFeaturedFacilities(res.data.featured_facilities);
            renderPopularPackages(res.data.packages);
          } else {
            renderHeroFeaturedCard(null);
            renderFeaturedCourts([]);
            renderLocations([]);
            renderFeaturedFacilities([]);
            renderPopularPackages([]);
          }
        },
        error: function (xhr, status, err) {
          console.error('Homepage data load failed:', status, err);
          renderHeroFeaturedCard(null);
          renderFeaturedCourts([]);
          renderLocations([]);
          renderFeaturedFacilities([]);
          renderPopularPackages([]);
        }
      });
    });
  </script>
</body>
</html>
