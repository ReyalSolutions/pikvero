<?php
/**
 * Pikvero - Official Home Landing Page
 * Designed after the high-impact Streetside Pickleball aesthetic
 */
require_once __DIR__ . '/app/bootstrap.php';

use App\Core\Database\Connection;
use App\Core\Auth\Auth;

// Calculate dynamic base URL and base path
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$basePath   = (strpos($requestUri, '/pikvero') === 0) ? '/pikvero' : '';

$db = Connection::getInstance();

// 1. Fetch featured/hero court with facility, images, and ratings
$heroCourt = $db->selectOne("
    SELECT c.*, 
           f.id AS facility_id,
           f.name AS facility_name, 
           f.city, 
           f.province, 
           f.address AS facility_address,
           f.description AS facility_desc,
           (SELECT ROUND(AVG(r.rating), 1) FROM reviews r WHERE r.facility_id = f.id) AS avg_rating
    FROM courts c
    JOIN facilities f ON c.facility_id = f.id
    WHERE c.status = 'active' AND f.status = 'active'
    ORDER BY (c.court_number = 1) DESC, c.id ASC
    LIMIT 1
");

// Fallback to active facility if no court assigned yet
if (!$heroCourt) {
    $fallbackFac = $db->selectOne("SELECT * FROM facilities WHERE status = 'active' LIMIT 1");
    if ($fallbackFac) {
        $heroCourt = [
            'id' => 1,
            'facility_id' => $fallbackFac['id'],
            'name' => 'Court 1 - Pro Championship',
            'court_number' => 1,
            'court_type' => 'indoor',
            'surface_type' => 'cushioned_acrylic',
            'base_price_per_hour' => 450.00,
            'facility_name' => $fallbackFac['name'],
            'city' => $fallbackFac['city'] ?? 'Tagbilaran City',
            'province' => $fallbackFac['province'] ?? 'Bohol',
            'facility_address' => $fallbackFac['address'] ?? 'CPG North Avenue, Cogon',
            'facility_desc' => $fallbackFac['description'] ?? '',
            'avg_rating' => 4.9
        ];
    }
}

// 2. Fetch images for the hero court slider
$courtImages = [];
if (!empty($heroCourt['id'])) {
    $cImages = $db->select("SELECT image_path FROM court_images WHERE court_id = ?", [(int)$heroCourt['id']], 'i');
    foreach ($cImages as $img) {
        $courtImages[] = $img['image_path'];
    }
}
if (!empty($heroCourt['facility_id'])) {
    $fImages = $db->select("SELECT image_path FROM facility_images WHERE facility_id = ? ORDER BY is_primary DESC", [(int)$heroCourt['facility_id']], 'i');
    foreach ($fImages as $img) {
        if (!in_array($img['image_path'], $courtImages)) {
            $courtImages[] = $img['image_path'];
        }
    }
}

// Default fallback images if database has none
if (empty($courtImages)) {
    $courtImages = [
        $basePath . '/assets/images/facilities/facility_1_1787966757_ad0abf1b.jpg',
        $basePath . '/assets/images/facilities/facility_1_1787966757_7941d551.jpg',
        $basePath . '/assets/images/facilities/facility_1_1787966757_094eac0b.jpg'
    ];
}

// Normalize image paths
$normalizedImages = [];
foreach ($courtImages as $imgPath) {
    if (str_starts_with($imgPath, 'http')) {
        $normalizedImages[] = $imgPath;
    } else {
        $cleanPath = '/' . ltrim($imgPath, '/');
        // If path doesn't start with basePath, prepend it
        if ($basePath !== '' && !str_starts_with($cleanPath, $basePath)) {
            $cleanPath = $basePath . $cleanPath;
        }
        $normalizedImages[] = $cleanPath;
    }
}

// 3. Fetch amenities for the hero facility
$heroAmenities = [];
if (!empty($heroCourt['facility_id'])) {
    $heroAmenities = $db->select("
        SELECT a.name, a.icon 
        FROM amenities a
        JOIN facility_amenities fa ON a.id = fa.amenity_id
        WHERE fa.facility_id = ?
        LIMIT 4
    ", [(int)$heroCourt['facility_id']], 'i');
}

// Default amenities if none in DB
if (empty($heroAmenities)) {
    $heroAmenities = [
        ['name' => 'Tournament-Grade Surface', 'icon' => 'bi-trophy'],
        ['name' => 'LED Lighting', 'icon' => 'bi-lightbulb-fill'],
        ['name' => 'Parking Available', 'icon' => 'bi-car-front-fill'],
        ['name' => 'Rest Area', 'icon' => 'bi-people-fill']
    ];
}

// 4. User authentication state
$isLoggedIn = class_exists(Auth::class) ? Auth::check() : false;
$userRole   = $isLoggedIn ? (Auth::role() ?? 'customer') : 'guest';
$userName   = $isLoggedIn ? (Auth::user()['first_name'] ?? 'Player') : '';

$dashboardUrl = $basePath . '/public/customer/dashboard';
if ($userRole === 'court_owner') {
    $dashboardUrl = $basePath . '/public/owner/dashboard';
} elseif ($userRole === 'super_admin' || $userRole === 'admin') {
    $dashboardUrl = $basePath . '/public/admin/dashboard';
}

$facilityName = $heroCourt['facility_name'] ?? 'SmashZone Center';
$cityName     = $heroCourt['city'] ?? 'Tagbilaran City';
$provinceName = $heroCourt['province'] ?? 'Bohol';
$courtNumber  = $heroCourt['court_number'] ?? 1;
$hourlyRate   = (float)($heroCourt['base_price_per_hour'] ?? 450.00);
$facilityId   = (int)($heroCourt['facility_id'] ?? 1);
$courtId      = (int)($heroCourt['id'] ?? 15);
$courtType    = strtoupper($heroCourt['court_type'] ?? 'PRO');
if ($courtType === 'INDOOR' || $courtType === 'OUTDOOR') {
    $courtType = 'PRO';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pikvero — Reserve Courts. Play Local.</title>
  <meta name="description" content="Pikvero connects players with top local pickleball courts in Bohol and across the Philippines. Real-time availability, instant online reservations, and multi-tenant court management.">
  
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
      --bg-dark: #071711;
      --ink: #0c1a15;
      --coral: #ff5733;
      --coral-hover: #e04422;
      --lime: #d6f827;
      --lime-hover: #c4e61b;
      --teal: #0d9488;
      --sand: #f8fafc;
      --card-bg: rgba(255, 255, 255, 0.88);
      --card-border: rgba(255, 255, 255, 0.95);
      --glass-shadow: 0 24px 60px -12px rgba(12, 26, 21, 0.22);
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
      background: #0d1e18 url('<?= $basePath ?>/assets/images/bg-pikvero.png') no-repeat center center fixed;
      background-size: cover;
      position: relative;
      overflow-x: hidden;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
    }

    /* Ambient soft gradient overlays to match reference design */
    body::before {
      content: "";
      position: fixed;
      inset: 0;
      background: radial-gradient(circle at 10% 20%, rgba(255, 255, 255, 0.15), transparent 45%),
                  radial-gradient(circle at 90% 80%, rgba(214, 248, 39, 0.12), transparent 50%);
      pointer-events: none;
      z-index: 0;
    }

    .main-wrapper {
      position: relative;
      z-index: 1;
      width: 100%;
      max-width: 1380px;
      margin: 0 auto;
      padding: 24px 28px 40px;
      display: flex;
      flex-direction: column;
      flex-grow: 1;
    }

    /* ========================================================
       FLOATING PILL NAVBAR
       ======================================================== */
    .navbar-pill {
      background: rgba(255, 255, 255, 0.94);
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      border-radius: 9999px;
      padding: 8px 14px 8px 24px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      border: 1.5px solid rgba(255, 255, 255, 0.95);
      box-shadow: 0 14px 34px -8px rgba(0, 0, 0, 0.14);
      margin-bottom: 36px;
      transition: all 0.3s ease;
    }

    .brand-group {
      display: flex;
      align-items: center;
      gap: 12px;
      text-decoration: none;
      color: var(--ink);
    }

    .brand-logo-badge {
      width: 38px;
      height: 38px;
      border-radius: 10px;
      background: #ffffff;
      border: 1.5px solid #000;
      box-shadow: 1.5px 1.5px 0 #000;
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
      flex-shrink: 0;
    }

    .brand-logo-badge img {
      width: 82%;
      height: 82%;
      object-fit: contain;
    }

    .brand-name {
      font-family: 'Outfit', sans-serif;
      font-weight: 900;
      font-size: 1.35rem;
      letter-spacing: -0.04em;
      color: var(--ink);
      text-transform: uppercase;
    }

    .nav-links-wrap {
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .nav-pill-item {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 9px 18px;
      font-size: 0.88rem;
      font-weight: 700;
      text-decoration: none;
      color: #1e293b;
      border-radius: 9999px;
      transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    }

    .nav-pill-item:hover {
      color: #000;
      background: rgba(0, 0, 0, 0.05);
    }

    .nav-pill-item.active {
      background: var(--coral);
      color: #ffffff;
      box-shadow: 0 4px 14px rgba(255, 87, 51, 0.35);
    }

    .nav-pill-item i {
      font-size: 1rem;
    }

    .nav-actions-wrap {
      display: flex;
      align-items: center;
      gap: 14px;
    }

    .nav-link-login {
      font-size: 0.86rem;
      font-weight: 800;
      color: var(--ink);
      text-decoration: none;
      text-transform: uppercase;
      letter-spacing: 0.04em;
      padding: 8px 12px;
      transition: color 0.2s ease;
    }

    .nav-link-login:hover {
      color: var(--coral);
    }

    .btn-play-local {
      background: var(--lime);
      color: var(--ink);
      font-weight: 800;
      font-size: 0.88rem;
      padding: 10px 22px;
      border-radius: 9999px;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      border: 1.5px solid rgba(0, 0, 0, 0.15);
      box-shadow: 0 4px 12px rgba(214, 248, 39, 0.45);
      transition: all 0.2s ease;
    }

    .btn-play-local:hover {
      background: var(--lime-hover);
      transform: translateY(-1px);
      box-shadow: 0 6px 16px rgba(214, 248, 39, 0.6);
    }

    /* Mobile Menu Toggle Button */
    .mobile-menu-btn {
      display: none;
      background: none;
      border: none;
      font-size: 1.6rem;
      color: var(--ink);
      cursor: pointer;
      padding: 4px;
    }

    /* ========================================================
       HERO CONTENT GRID
       ======================================================== */
    .hero-grid {
      display: grid;
      grid-template-columns: 1.15fr 0.95fr;
      align-items: center;
      gap: 48px;
      margin: auto 0;
      padding: 16px 0 24px;
    }

    /* ------------------------------------
       LEFT COLUMN: HEADLINE & ACTIONS
       ------------------------------------ */
    .hero-left {
      display: flex;
      flex-direction: column;
      gap: 20px;
    }

    .location-tag {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      font-family: 'DM Mono', monospace;
      font-size: 0.82rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.12em;
      color: var(--ink);
      margin-bottom: -6px;
    }

    .location-tag i {
      font-size: 1.1rem;
      color: #0f172a;
    }

    .hero-title {
      font-family: 'Outfit', sans-serif;
      font-weight: 900;
      font-size: clamp(3.2rem, 5.8vw, 5.2rem);
      line-height: 0.96;
      letter-spacing: -0.045em;
      text-transform: uppercase;
      color: var(--ink);
      position: relative;
    }

    .hero-title .text-coral {
      color: var(--coral);
      display: inline-block;
      text-shadow: 0 6px 24px rgba(255, 87, 51, 0.25);
    }

    .ball-wrapper {
      display: inline-flex;
      position: relative;
      margin-left: 8px;
      vertical-align: middle;
      transform: translateY(-4px);
    }

    .ball-svg {
      width: clamp(48px, 6vw, 68px);
      height: clamp(48px, 6vw, 68px);
      filter: drop-shadow(0 8px 16px rgba(214, 248, 39, 0.65));
      animation: float-ball 3s ease-in-out infinite;
    }

    @keyframes float-ball {
      0%, 100% { transform: translateY(0) rotate(0deg); }
      50% { transform: translateY(-8px) rotate(15deg); }
    }

    .hero-description {
      font-size: 1.08rem;
      line-height: 1.55;
      color: #334155;
      max-width: 540px;
      font-weight: 500;
    }

    /* Search Bar Widget */
    .search-widget-form {
      display: flex;
      align-items: center;
      background: #ffffff;
      border: 1.5px solid rgba(0, 0, 0, 0.12);
      border-radius: 9999px;
      padding: 6px 8px 6px 20px;
      box-shadow: 0 10px 28px -6px rgba(0, 0, 0, 0.12);
      max-width: 530px;
      gap: 12px;
      margin-top: 4px;
    }

    .search-widget-form i.bi-geo-alt-fill {
      color: #475569;
      font-size: 1.15rem;
    }

    .search-widget-input {
      border: none;
      outline: none;
      flex-grow: 1;
      font-family: inherit;
      font-size: 0.92rem;
      font-weight: 600;
      color: var(--ink);
      background: transparent;
    }

    .search-widget-input::placeholder {
      color: #94a3b8;
      font-weight: 500;
    }

    .btn-search-pill {
      background: var(--coral);
      color: #ffffff;
      border: none;
      outline: none;
      padding: 10px 24px;
      border-radius: 9999px;
      font-family: inherit;
      font-weight: 700;
      font-size: 0.92rem;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      cursor: pointer;
      box-shadow: 0 4px 14px rgba(255, 87, 51, 0.35);
      transition: all 0.2s ease;
    }

    .btn-search-pill:hover {
      background: var(--coral-hover);
      transform: translateY(-1px);
    }

    /* Quick Action Pills */
    .quick-actions-row {
      display: flex;
      align-items: center;
      gap: 12px;
      flex-wrap: wrap;
      margin-top: 4px;
    }

    .quick-pill {
      display: inline-flex;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
      padding: 12px 20px;
      border-radius: 16px;
      text-decoration: none;
      font-weight: 700;
      font-size: 0.88rem;
      border: 1.5px solid #000;
      box-shadow: 2px 2px 0 #000;
      transition: all 0.15s ease;
    }

    .quick-pill:hover {
      transform: translate(-1.5px, -1.5px);
      box-shadow: 3.5px 3.5px 0 #000;
    }

    .quick-pill.coral {
      background: var(--coral);
      color: #ffffff;
    }

    .quick-pill.lime {
      background: var(--lime);
      color: var(--ink);
    }

    .quick-pill.dark {
      background: #112922;
      color: #ffffff;
    }

    .quick-pill i.bi-chevron-right {
      font-size: 0.78rem;
    }

    /* Trust / Feature Badges */
    .trust-badges-row {
      display: flex;
      align-items: center;
      gap: 20px;
      flex-wrap: wrap;
      margin-top: 14px;
      padding-top: 18px;
      border-top: 1px solid rgba(0, 0, 0, 0.08);
    }

    .trust-item {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      font-size: 0.82rem;
      font-weight: 600;
      color: #334155;
    }

    .trust-item i {
      color: #0d9488;
      font-size: 1.1rem;
    }

    /* ------------------------------------
       RIGHT COLUMN: GLASS COURT CARD
       ------------------------------------ */
    .hero-right {
      display: flex;
      justify-content: center;
    }

    .glass-court-card {
      background: var(--card-bg);
      backdrop-filter: blur(28px);
      -webkit-backdrop-filter: blur(28px);
      border-radius: 28px;
      border: 1.8px solid var(--card-border);
      box-shadow: var(--glass-shadow);
      padding: 16px;
      width: 100%;
      max-width: 530px;
      position: relative;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .glass-court-card:hover {
      box-shadow: 0 32px 70px -10px rgba(12, 26, 21, 0.28);
    }

    /* Image Showcase & Slider */
    .court-image-wrap {
      position: relative;
      width: 100%;
      height: 250px;
      border-radius: 20px;
      overflow: hidden;
      background: #e2e8f0;
      border: 1px solid rgba(0, 0, 0, 0.06);
    }

    .slider-img {
      position: absolute;
      inset: 0;
      width: 100%;
      height: 100%;
      object-fit: cover;
      opacity: 0;
      transition: opacity 0.5s ease-in-out;
    }

    .slider-img.active {
      opacity: 1;
    }

    /* Badges Over Image */
    .badge-live-avail {
      position: absolute;
      top: 14px;
      left: 14px;
      background: var(--lime);
      color: var(--ink);
      font-family: 'DM Mono', monospace;
      font-size: 0.68rem;
      font-weight: 700;
      letter-spacing: 0.06em;
      padding: 6px 12px;
      border-radius: 9999px;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
      z-index: 2;
    }

    .live-dot {
      width: 7px;
      height: 7px;
      background: #16a34a;
      border-radius: 50%;
      box-shadow: 0 0 0 2px rgba(22, 163, 74, 0.3);
      animation: pulse-dot 1.8s infinite;
    }

    @keyframes pulse-dot {
      0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(22, 163, 74, 0.7); }
      70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(22, 163, 74, 0); }
      100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(22, 163, 74, 0); }
    }

    .badge-court-pro {
      position: absolute;
      top: 14px;
      right: 14px;
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(8px);
      color: var(--ink);
      font-family: 'DM Mono', monospace;
      font-size: 0.70rem;
      font-weight: 700;
      padding: 5px 10px;
      border-radius: 9999px;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
      z-index: 2;
    }

    .badge-pro-tag {
      background: #3b82f6;
      color: #ffffff;
      font-size: 0.62rem;
      font-weight: 800;
      padding: 2px 6px;
      border-radius: 6px;
      letter-spacing: 0.04em;
    }

    /* Slider Nav Arrows */
    .slider-btn {
      position: absolute;
      top: 50%;
      transform: translateY(-50%);
      width: 32px;
      height: 32px;
      background: rgba(255, 255, 255, 0.88);
      border: 1px solid rgba(0, 0, 0, 0.1);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      color: var(--ink);
      font-size: 0.85rem;
      z-index: 2;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.12);
      transition: all 0.2s ease;
    }

    .slider-btn:hover {
      background: #ffffff;
      transform: translateY(-50%) scale(1.08);
    }

    .slider-btn.prev { left: 10px; }
    .slider-btn.next { right: 10px; }

    /* Slider Dots Indicator */
    .slider-dots-wrap {
      position: absolute;
      bottom: 12px;
      right: 14px;
      display: flex;
      align-items: center;
      gap: 5px;
      z-index: 2;
    }

    .slider-dot {
      width: 6px;
      height: 6px;
      border-radius: 9999px;
      background: rgba(255, 255, 255, 0.55);
      cursor: pointer;
      transition: all 0.3s ease;
    }

    .slider-dot.active {
      width: 18px;
      background: #ffffff;
    }

    /* Facility Info Box */
    .facility-meta-row {
      display: flex;
      align-items: center;
      gap: 14px;
      margin-top: 18px;
      margin-bottom: 16px;
      padding: 0 4px;
    }

    .facility-icon-box {
      width: 48px;
      height: 48px;
      border-radius: 14px;
      background: #f1f5f9;
      border: 1.5px solid #000;
      box-shadow: 2px 2px 0 #000;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.35rem;
      color: #0f172a;
      flex-shrink: 0;
    }

    .facility-text-group {
      display: flex;
      flex-direction: column;
      gap: 3px;
    }

    .facility-title {
      font-family: 'Outfit', sans-serif;
      font-weight: 800;
      font-size: 1.3rem;
      color: var(--ink);
      letter-spacing: -0.02em;
      text-transform: uppercase;
      line-height: 1.1;
    }

    .facility-location-sub {
      font-size: 0.82rem;
      font-weight: 600;
      color: #475569;
      display: flex;
      align-items: center;
      gap: 5px;
    }

    .facility-location-sub i {
      color: #0d9488;
    }

    /* Amenities Row */
    .amenities-badges-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(110px, 1fr));
      gap: 8px;
      margin-bottom: 20px;
    }

    .amenity-chip {
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      border-radius: 12px;
      padding: 8px 10px;
      display: flex;
      align-items: center;
      gap: 6px;
      font-size: 0.72rem;
      font-weight: 700;
      color: #1e293b;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .amenity-chip i {
      font-size: 0.85rem;
      color: #0d9488;
      flex-shrink: 0;
    }

    /* Card Footer: Rate & CTA Button */
    .card-footer-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      border-top: 1.5px solid #e2e8f0;
      padding-top: 16px;
      margin-top: 4px;
    }

    .rate-box {
      display: flex;
      flex-direction: column;
    }

    .rate-label {
      font-family: 'DM Mono', monospace;
      font-size: 0.68rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      color: #64748b;
    }

    .rate-amount {
      font-family: 'Outfit', sans-serif;
      font-weight: 900;
      font-size: 1.65rem;
      color: var(--ink);
      letter-spacing: -0.03em;
      line-height: 1.1;
    }

    .btn-book-slot {
      background: var(--lime);
      color: var(--ink);
      font-weight: 800;
      font-size: 0.90rem;
      padding: 12px 24px;
      border-radius: 9999px;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      border: 1.5px solid #000;
      box-shadow: 2.5px 2.5px 0 #000;
      transition: all 0.15s ease;
    }

    .btn-book-slot:hover {
      background: var(--lime-hover);
      transform: translate(-1.5px, -1.5px);
      box-shadow: 4px 4px 0 #000;
    }

    /* ========================================================
       RESPONSIVE BREAKPOINTS
       ======================================================== */
    @media (max-width: 1080px) {
      .hero-grid {
        grid-template-columns: 1fr;
        gap: 40px;
      }
      .hero-title {
        font-size: clamp(2.8rem, 7vw, 4rem);
      }
      .glass-court-card {
        max-width: 600px;
      }
      .nav-links-wrap {
        display: none;
      }
      .mobile-menu-btn {
        display: block;
      }
    }

    @media (max-width: 640px) {
      .main-wrapper {
        padding: 16px 16px 30px;
      }
      .navbar-pill {
        padding: 8px 12px 8px 16px;
        margin-bottom: 24px;
      }
      .btn-play-local span {
        display: none;
      }
      .btn-play-local {
        padding: 8px 12px;
      }
      .hero-title {
        font-size: 2.6rem;
      }
      .search-widget-form {
        flex-direction: column;
        border-radius: 20px;
        padding: 14px;
        align-items: stretch;
      }
      .btn-search-pill {
        justify-content: center;
        width: 100%;
      }
      .card-footer-row {
        flex-direction: column;
        align-items: stretch;
        gap: 12px;
        text-align: center;
      }
      .btn-book-slot {
        justify-content: center;
        width: 100%;
      }
      .amenities-badges-grid {
        grid-template-columns: 1fr 1fr;
      }
    }

    /* Mobile drawer modal */
    .mobile-drawer {
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.65);
      backdrop-filter: blur(10px);
      z-index: 100;
      opacity: 0;
      pointer-events: none;
      transition: opacity 0.25s ease;
      display: flex;
      justify-content: flex-end;
    }

    .mobile-drawer.open {
      opacity: 1;
      pointer-events: auto;
    }

    .drawer-content {
      background: #ffffff;
      width: 82%;
      max-width: 320px;
      height: 100%;
      padding: 24px 20px;
      display: flex;
      flex-direction: column;
      gap: 14px;
      box-shadow: -8px 0 25px rgba(0,0,0,0.25);
    }
  </style>
</head>
<body>

  <div class="main-wrapper">

    <!-- FLOATING PILL NAVBAR -->
    <header class="navbar-pill">
      <!-- Brand Logo -->
      <a href="<?= $basePath ?>/" class="brand-group">
        <div class="brand-logo-badge">
          <img src="<?= $basePath ?>/assets/images/logo.png" alt="Pikvero Logo" onerror="this.onerror=null; this.parentNode.innerHTML='🎾';">
        </div>
        <span class="brand-name">PIKVERO</span>
      </a>

      <!-- Center Nav Links -->
      <nav class="nav-links-wrap">
        <a href="<?= $basePath ?>/" class="nav-pill-item active">
          <i class="bi bi-house-door-fill"></i>
          <span>Home</span>
        </a>
        <a href="<?= $basePath ?>/public/search" class="nav-pill-item">
          <i class="bi bi-search"></i>
          <span>Explore Courts</span>
        </a>
        <a href="<?= $basePath ?>/public/open-play" class="nav-pill-item">
          <i class="bi bi-people-fill"></i>
          <span>Open Play</span>
        </a>
        <a href="<?= $basePath ?>/public/pricing" class="nav-pill-item">
          <i class="bi bi-tag-fill"></i>
          <span>Pricing</span>
        </a>
        <a href="<?= $basePath ?>/public/register?type=owner" class="nav-pill-item">
          <i class="bi bi-shop"></i>
          <span>Become an Owner</span>
        </a>
      </nav>

      <!-- Right Action Buttons -->
      <div class="nav-actions-wrap">
        <?php if ($isLoggedIn): ?>
          <a href="<?= $dashboardUrl ?>" class="nav-link-login" style="color:var(--coral);">
            <i class="bi bi-person-circle"></i> <?= htmlspecialchars($userName) ?>
          </a>
        <?php else: ?>
          <a href="<?= $basePath ?>/public/login" class="nav-link-login">LOGIN</a>
        <?php endif; ?>

        <a href="<?= $basePath ?>/public/search" class="btn-play-local">
          <i class="bi bi-play-fill"></i>
          <span>Play Local</span>
        </a>

        <button class="mobile-menu-btn" onclick="toggleMobileMenu()" aria-label="Open Navigation Menu">
          <i class="bi bi-list"></i>
        </button>
      </div>
    </header>

    <!-- HERO SECTION GRID -->
    <main class="hero-grid">

      <!-- LEFT COLUMN -->
      <div class="hero-left">
        <!-- Location Tag -->
        <div class="location-tag">
          <i class="bi bi-geo-alt-fill"></i>
          <span><?= htmlspecialchars($provinceName) ?> &amp; PHILIPPINES</span>
        </div>

        <!-- Headline with Pickleball Graphic -->
        <h1 class="hero-title">
          RESERVE<br>
          <span class="text-coral">COURTS.</span><br>
          PLAY LOCAL.
          <span class="ball-wrapper" title="Play Pickleball">
            <svg class="ball-svg" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
              <circle cx="50" cy="50" r="46" fill="#D6F827" stroke="#112922" stroke-width="4"/>
              <!-- Holes of pickleball -->
              <circle cx="50" cy="30" r="5" fill="#112922"/>
              <circle cx="34" cy="40" r="5" fill="#112922"/>
              <circle cx="66" cy="40" r="5" fill="#112922"/>
              <circle cx="38" cy="62" r="5" fill="#112922"/>
              <circle cx="62" cy="62" r="5" fill="#112922"/>
              <circle cx="50" cy="74" r="5" fill="#112922"/>
              <circle cx="50" cy="50" r="5.5" fill="#112922"/>
              <path d="M78 20 Q95 2 108 -10" stroke="#D6F827" stroke-width="4" stroke-linecap="round" opacity="0.8"/>
            </svg>
          </span>
        </h1>

        <!-- Subtitle Description -->
        <p class="hero-description">
          Pikvero connects players with top local pickleball courts. Real-time availability, instant online reservations, and multi-tenant court management.
        </p>

        <!-- Search Bar Widget -->
        <form action="<?= $basePath ?>/public/search" method="GET" class="search-widget-form">
          <i class="bi bi-geo-alt-fill"></i>
          <input type="text" name="city" class="search-widget-input" placeholder="Search city or location (e.g. <?= htmlspecialchars($cityName) ?>)" value="<?= htmlspecialchars($cityName) ?>">
          <button type="submit" class="btn-search-pill">
            <i class="bi bi-search"></i>
            <span>Search</span>
          </button>
        </form>

        <!-- Quick Action Pills -->
        <div class="quick-actions-row">
          <a href="<?= $basePath ?>/public/search" class="quick-pill coral">
            <span><i class="bi bi-grid-fill" style="margin-right:4px;"></i> Explore All Courts</span>
            <i class="bi bi-chevron-right"></i>
          </a>
          <a href="<?= $basePath ?>/public/pricing" class="quick-pill lime">
            <span><i class="bi bi-tag-fill" style="margin-right:4px;"></i> Pricing</span>
            <i class="bi bi-chevron-right"></i>
          </a>
          <a href="<?= $basePath ?>/public/register?type=owner" class="quick-pill dark">
            <span><i class="bi bi-building" style="margin-right:4px;"></i> List Your Facility</span>
            <i class="bi bi-chevron-right"></i>
          </a>
        </div>

        <!-- Trust Badges -->
        <div class="trust-badges-row">
          <div class="trust-item">
            <i class="bi bi-calendar2-check-fill"></i>
            <span>Instant Reservations</span>
          </div>
          <div class="trust-item">
            <i class="bi bi-lightning-charge-fill"></i>
            <span>Real-Time Availability</span>
          </div>
          <div class="trust-item">
            <i class="bi bi-people-fill"></i>
            <span>Local Communities</span>
          </div>
          <div class="trust-item">
            <i class="bi bi-shield-check"></i>
            <span>Safe &amp; Secure Payments</span>
          </div>
        </div>
      </div>

      <!-- RIGHT COLUMN: GLASS COURT CARD -->
      <div class="hero-right">
        <div class="glass-court-card">

          <!-- Image Carousel -->
          <div class="court-image-wrap" id="heroCourtSlider">
            <!-- Overlaid Badges -->
            <div class="badge-live-avail">
              <span class="live-dot"></span>
              <span>LIVE AVAILABILITY</span>
            </div>

            <div class="badge-court-pro">
              <span>COURT #<?= htmlspecialchars($courtNumber) ?></span>
              <span class="badge-pro-tag"><?= htmlspecialchars($courtType) ?></span>
            </div>

            <!-- Carousel Images -->
            <?php foreach ($normalizedImages as $idx => $imgSrc): ?>
              <img src="<?= htmlspecialchars($imgSrc) ?>" alt="<?= htmlspecialchars($facilityName) ?> Court Photo" class="slider-img <?= $idx === 0 ? 'active' : '' ?>" data-index="<?= $idx ?>">
            <?php endforeach; ?>

            <!-- Navigation Arrows -->
            <?php if (count($normalizedImages) > 1): ?>
              <button type="button" class="slider-btn prev" onclick="changeSlide(-1)" aria-label="Previous image">
                <i class="bi bi-chevron-left"></i>
              </button>
              <button type="button" class="slider-btn next" onclick="changeSlide(1)" aria-label="Next image">
                <i class="bi bi-chevron-right"></i>
              </button>

              <!-- Dots -->
              <div class="slider-dots-wrap">
                <?php foreach ($normalizedImages as $idx => $imgSrc): ?>
                  <div class="slider-dot <?= $idx === 0 ? 'active' : '' ?>" onclick="goToSlide(<?= $idx ?>)"></div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>

          <!-- Facility Metadata Row -->
          <div class="facility-meta-row">
            <div class="facility-icon-box">
              <i class="bi bi-trophy-fill"></i>
            </div>
            <div class="facility-text-group">
              <h2 class="facility-title"><?= htmlspecialchars($facilityName) ?></h2>
              <div class="facility-location-sub">
                <i class="bi bi-geo-alt-fill"></i>
                <span><?= htmlspecialchars($cityName . ', ' . $provinceName) ?></span>
              </div>
            </div>
          </div>

          <!-- Amenities Badges -->
          <div class="amenities-badges-grid">
            <?php foreach ($heroAmenities as $amenity): ?>
              <div class="amenity-chip" title="<?= htmlspecialchars($amenity['name']) ?>">
                <i class="bi <?= htmlspecialchars($amenity['icon'] ?: 'bi-check-circle-fill') ?>"></i>
                <span><?= htmlspecialchars($amenity['name']) ?></span>
              </div>
            <?php endforeach; ?>
          </div>

          <!-- Footer Rate & CTA -->
          <div class="card-footer-row">
            <div class="rate-box">
              <span class="rate-label">RATE PER HOUR</span>
              <span class="rate-amount">&#8369;<?= number_format($hourlyRate, 2) ?></span>
            </div>

            <a href="<?= $basePath ?>/public/facility?id=<?= $facilityId ?>&court_id=<?= $courtId ?>" class="btn-book-slot">
              <i class="bi bi-calendar-check-fill"></i>
              <span>Book Time Slot</span>
              <i class="bi bi-chevron-right"></i>
            </a>
          </div>

        </div>
      </div>

    </main>
  </div>

  <!-- Mobile Drawer Menu -->
  <div class="mobile-drawer" id="mobileDrawer" onclick="toggleMobileMenu()">
    <div class="drawer-content" onclick="event.stopPropagation()">
      <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1.5px solid #e2e8f0; padding-bottom:14px;">
        <span style="font-family:'Outfit',sans-serif; font-weight:900; font-size:1.2rem;">PIKVERO</span>
        <button onclick="toggleMobileMenu()" style="background:none; border:none; font-size:1.4rem; cursor:pointer;">&times;</button>
      </div>
      <a href="<?= $basePath ?>/" style="text-decoration:none; color:var(--coral); font-weight:700;"><i class="bi bi-house-door-fill"></i> Home</a>
      <a href="<?= $basePath ?>/public/search" style="text-decoration:none; color:#1e293b; font-weight:700;"><i class="bi bi-search"></i> Explore Courts</a>
      <a href="<?= $basePath ?>/public/open-play" style="text-decoration:none; color:#1e293b; font-weight:700;"><i class="bi bi-people-fill"></i> Open Play</a>
      <a href="<?= $basePath ?>/public/pricing" style="text-decoration:none; color:#1e293b; font-weight:700;"><i class="bi bi-tag-fill"></i> Pricing</a>
      <a href="<?= $basePath ?>/public/register?type=owner" style="text-decoration:none; color:#1e293b; font-weight:700;"><i class="bi bi-shop"></i> Become an Owner</a>
      <div style="border-top:1.5px solid #e2e8f0; padding-top:12px; margin-top:auto;">
        <?php if ($isLoggedIn): ?>
          <a href="<?= $dashboardUrl ?>" class="btn-search-pill" style="width:100%; justify-content:center; text-decoration:none;">Dashboard</a>
        <?php else: ?>
          <a href="<?= $basePath ?>/public/login" class="btn-search-pill" style="width:100%; justify-content:center; text-decoration:none;">Login</a>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Interactive Slider Script -->
  <script>
    let currentSlide = 0;
    const slides = document.querySelectorAll('.slider-img');
    const dots = document.querySelectorAll('.slider-dot');

    function showSlide(index) {
      if (slides.length <= 1) return;
      if (index >= slides.length) currentSlide = 0;
      else if (index < 0) currentSlide = slides.length - 1;
      else currentSlide = index;

      slides.forEach((s, i) => {
        s.classList.toggle('active', i === currentSlide);
      });
      dots.forEach((d, i) => {
        d.classList.toggle('active', i === currentSlide);
      });
    }

    function changeSlide(direction) {
      showSlide(currentSlide + direction);
    }

    function goToSlide(index) {
      showSlide(index);
    }

    // Auto rotate slides every 6 seconds
    if (slides.length > 1) {
      setInterval(() => {
        changeSlide(1);
      }, 6000);
    }

    // Mobile Drawer Toggle
    function toggleMobileMenu() {
      const drawer = document.getElementById('mobileDrawer');
      drawer.classList.toggle('open');
    }
  </script>
</body>
</html>
