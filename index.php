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

$db = null;
$dbConnected = false;
$dbError = null;

try {
    $db = Connection::getInstance();
    $dbConnected = true;
} catch (\Throwable $e) {
    $dbConnected = false;
    $dbError = $e->getMessage();
}

// 1. Fetch featured/hero court with facility, images, and ratings
$heroCourt = null;
if ($dbConnected && $db) {
    try {
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

        if (!$heroCourt) {
            $fallbackFac = $db->selectOne("SELECT * FROM facilities WHERE status = 'active' LIMIT 1");
            if ($fallbackFac) {
                $heroCourt = [
                    'id' => 15,
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
    } catch (\Throwable $e) {
        $heroCourt = null;
        if (!$dbError) $dbError = $e->getMessage();
    }
}

// Ultimate resilient fallback if DB is offline or empty
if (!$heroCourt) {
    $heroCourt = [
        'id' => 15,
        'facility_id' => 1,
        'name' => 'Court 1 - Pro Championship',
        'court_number' => 1,
        'court_type' => 'indoor',
        'surface_type' => 'cushioned_acrylic',
        'base_price_per_hour' => 450.00,
        'facility_name' => 'SmashZone Center',
        'city' => 'Tagbilaran City',
        'province' => 'Bohol',
        'facility_address' => 'CPG North Avenue, Cogon',
        'facility_desc' => 'Premier indoor pickleball arena in Bohol featuring professional cushioned acrylic courts, air-conditioned lounge, and tournament lighting.',
        'avg_rating' => 4.9
    ];
}

// 2. Fetch images for the hero court slider
$courtImages = [];
if ($dbConnected && $db && !empty($heroCourt['id'])) {
    try {
        $cImages = $db->select("SELECT image_path FROM court_images WHERE court_id = ?", [(int)$heroCourt['id']], 'i');
        foreach ($cImages as $img) {
            $courtImages[] = $img['image_path'];
        }
        if (!empty($heroCourt['facility_id'])) {
            $fImages = $db->select("SELECT image_path FROM facility_images WHERE facility_id = ? ORDER BY is_primary DESC", [(int)$heroCourt['facility_id']], 'i');
            foreach ($fImages as $img) {
                if (!in_array($img['image_path'], $courtImages)) {
                    $courtImages[] = $img['image_path'];
                }
            }
        }
    } catch (\Throwable $e) {
        // Fall back below
    }
}

// Default fallback images
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
        if ($basePath !== '' && !str_starts_with($cleanPath, $basePath)) {
            $cleanPath = $basePath . $cleanPath;
        }
        $normalizedImages[] = $cleanPath;
    }
}

// 3. Fetch amenities for the hero facility
$heroAmenities = [];
if ($dbConnected && $db && !empty($heroCourt['facility_id'])) {
    try {
        $heroAmenities = $db->select("
            SELECT a.name, a.icon 
            FROM amenities a
            JOIN facility_amenities fa ON a.id = fa.amenity_id
            WHERE fa.facility_id = ?
            LIMIT 4
        ", [(int)$heroCourt['facility_id']], 'i');
    } catch (\Throwable $e) {
        // Fall back below
    }
}

if (empty($heroAmenities)) {
    $heroAmenities = [
        ['name' => 'Tournament Surface', 'icon' => 'bi-trophy'],
        ['name' => 'LED Lighting', 'icon' => 'bi-lightbulb-fill'],
        ['name' => 'Parking Available', 'icon' => 'bi-car-front-fill'],
        ['name' => 'Rest Area', 'icon' => 'bi-people-fill']
    ];
}

// 4. Platform Metrics
$totalCourts = 18;
$totalFacilities = 6;
$totalHours = 480;

if ($dbConnected && $db) {
    try {
        $totalCourts = (int)($db->selectOne("SELECT COUNT(*) AS cnt FROM courts WHERE status = 'active'")['cnt'] ?? 18);
        $totalFacilities = (int)($db->selectOne("SELECT COUNT(*) AS cnt FROM facilities WHERE status = 'active'")['cnt'] ?? 6);
        $totalHours = (int)($db->selectOne("SELECT COALESCE(SUM(TIMESTAMPDIFF(HOUR, start_time, end_time)), 480) AS cnt FROM bookings WHERE booking_status IN ('confirmed', 'completed')")['cnt'] ?? 480);
    } catch (\Throwable $e) {
        // Fall back to defaults
    }
}
if ($totalCourts <= 0) $totalCourts = 18;
if ($totalFacilities <= 0) $totalFacilities = 6;
if ($totalHours <= 0) $totalHours = 480;

// 5. Featured Courts List
$featuredCourts = [];
if ($dbConnected && $db) {
    try {
        $featuredCourts = $db->select("
            SELECT c.*, f.name AS facility_name, f.city, f.province,
                   COALESCE(
                     (SELECT image_path FROM court_images ci WHERE ci.court_id = c.id LIMIT 1),
                     (SELECT image_path FROM facility_images fi WHERE fi.facility_id = f.id LIMIT 1),
                     '/assets/images/facilities/facility_1_1787966757_ad0abf1b.jpg'
                   ) AS image_url
            FROM courts c
            JOIN facilities f ON c.facility_id = f.id
            WHERE c.status = 'active' AND f.status = 'active'
            ORDER BY c.id ASC
            LIMIT 6
        ");
    } catch (\Throwable $e) {
        $featuredCourts = [];
    }
}

if (empty($featuredCourts)) {
    $featuredCourts = [
        [
            'id' => 15,
            'name' => 'Court 1 - Championship Court',
            'facility_name' => 'SmashZone Center',
            'city' => 'Tagbilaran City',
            'province' => 'Bohol',
            'court_type' => 'indoor',
            'surface_type' => 'cushioned_acrylic',
            'base_price_per_hour' => 450.00,
            'image_url' => $basePath . '/assets/images/facilities/facility_1_1787966757_ad0abf1b.jpg'
        ],
        [
            'id' => 16,
            'name' => 'Court 2 - Open Play Zone',
            'facility_name' => 'SmashZone Center',
            'city' => 'Tagbilaran City',
            'province' => 'Bohol',
            'court_type' => 'indoor',
            'surface_type' => 'cushioned_acrylic',
            'base_price_per_hour' => 450.00,
            'image_url' => $basePath . '/assets/images/facilities/facility_1_1787966757_7941d551.jpg'
        ],
        [
            'id' => 17,
            'name' => 'Court 3 - Training Court',
            'facility_name' => 'SmashZone Center',
            'city' => 'Tagbilaran City',
            'province' => 'Bohol',
            'court_type' => 'indoor',
            'surface_type' => 'cushioned_acrylic',
            'base_price_per_hour' => 400.00,
            'image_url' => $basePath . '/assets/images/facilities/facility_1_1787966757_094eac0b.jpg'
        ]
    ];
}

// 6. Popular Locations / Cities
$popularCities = [];
if ($dbConnected && $db) {
    try {
        $popularCities = $db->select("
            SELECT f.city, f.province, COUNT(c.id) AS court_count, COUNT(DISTINCT f.id) AS facility_count
            FROM facilities f
            LEFT JOIN courts c ON c.facility_id = f.id AND c.status = 'active'
            WHERE f.status = 'active'
            GROUP BY f.city, f.province
            ORDER BY court_count DESC, f.city ASC
            LIMIT 6
        ");
    } catch (\Throwable $e) {
        $popularCities = [];
    }
}

if (empty($popularCities)) {
    $popularCities = [
        ['city' => 'Tagbilaran City', 'province' => 'Bohol', 'court_count' => 8, 'facility_count' => 3],
        ['city' => 'Panglao', 'province' => 'Bohol', 'court_count' => 4, 'facility_count' => 2],
        ['city' => 'Cebu City', 'province' => 'Cebu', 'court_count' => 12, 'facility_count' => 5],
        ['city' => 'Mandaue City', 'province' => 'Cebu', 'court_count' => 6, 'facility_count' => 2]
    ];
}

// 7. Featured Facilities
$featuredFacilities = [];
if ($dbConnected && $db) {
    try {
        $featuredFacilities = $db->select("
            SELECT f.*, 
                   COUNT(c.id) AS court_count,
                   COALESCE(
                     (SELECT image_path FROM facility_images fi WHERE fi.facility_id = f.id ORDER BY is_primary DESC LIMIT 1),
                     '/assets/images/facilities/facility_1_1787966757_ad0abf1b.jpg'
                   ) AS primary_img
            FROM facilities f
            LEFT JOIN courts c ON c.facility_id = f.id AND c.status = 'active'
            WHERE f.status = 'active'
            GROUP BY f.id
            ORDER BY f.id ASC
            LIMIT 4
        ");
    } catch (\Throwable $e) {
        $featuredFacilities = [];
    }
}

if (empty($featuredFacilities)) {
    $featuredFacilities = [
        [
            'id' => 1,
            'name' => 'SmashZone Center',
            'city' => 'Tagbilaran City',
            'province' => 'Bohol',
            'address' => 'CPG North Avenue, Cogon',
            'court_count' => 3,
            'primary_img' => $basePath . '/assets/images/facilities/facility_1_1787966757_ad0abf1b.jpg'
        ]
    ];
}

// 8. User authentication state
$isLoggedIn = false;
$userRole = 'guest';
$userName = '';

try {
    $isLoggedIn = class_exists(Auth::class) ? Auth::check() : false;
    $userRole   = $isLoggedIn ? (Auth::role() ?? 'customer') : 'guest';
    $userName   = $isLoggedIn ? (Auth::user()['first_name'] ?? 'Player') : '';
} catch (\Throwable $e) {
    // Session or auth fallback
}

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
      --cream: #fffdf5;
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
      background: #0d1e18;
      position: relative;
      overflow-x: hidden;
    }

    /* Hero section background container */
    .hero-bg-section {
      background: #0d1e18 url('<?= $basePath ?>/assets/images/bg-pikvero.png') no-repeat center center;
      background-size: cover;
      position: relative;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }

    .hero-bg-section::before {
      content: "";
      position: absolute;
      inset: 0;
      background: linear-gradient(90deg, 
        rgba(255, 255, 255, 0.92) 0%, 
        rgba(255, 255, 255, 0.84) 36%, 
        rgba(255, 255, 255, 0.48) 60%, 
        rgba(255, 255, 255, 0.08) 82%, 
        transparent 100%
      );
      pointer-events: none;
      z-index: 0;
    }

    .main-wrapper {
      position: relative;
      z-index: 1;
      width: 100%;
      max-width: 1380px;
      margin: 0 auto;
      padding: 104px 28px 40px;
      display: flex;
      flex-direction: column;
      flex-grow: 1;
    }

    /* ========================================================
       FLOATING PILL NAVBAR (FIXED AT TOP)
       ======================================================== */
    .navbar-pill {
      position: fixed;
      top: 18px;
      left: 50%;
      transform: translateX(-50%);
      width: calc(100% - 56px);
      max-width: 1324px;
      z-index: 1000;
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      border-radius: 9999px;
      padding: 8px 14px 8px 24px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      border: 1.5px solid rgba(255, 255, 255, 0.95);
      box-shadow: 0 14px 34px -8px rgba(0, 0, 0, 0.16);
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

    /* Right Column: Glass Court Card */
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

    .amenities-badges-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 8px 10px;
      margin-bottom: 20px;
    }

    .amenity-chip {
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      border-radius: 10px;
      padding: 7px 12px;
      display: flex;
      align-items: center;
      gap: 7px;
      font-size: 0.74rem;
      font-weight: 700;
      color: #1e293b;
      white-space: nowrap;
    }

    .amenity-chip i {
      font-size: 0.85rem;
      color: #0d9488;
      flex-shrink: 0;
    }

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
       MARQUEE TICKER STRIP
       ======================================================== */
    .marquee-wrap {
      background: var(--lime);
      border-top: 2px solid #000;
      border-bottom: 2px solid #000;
      padding: 12px 0;
      overflow: hidden;
      width: 100%;
      position: relative;
      z-index: 10;
    }

    .marquee-track {
      display: inline-flex;
      align-items: center;
      white-space: nowrap;
      animation: marquee-scroll 28s linear infinite;
      font-family: 'DM Mono', monospace;
      font-weight: 800;
      font-size: 0.86rem;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      gap: 18px;
      color: var(--ink);
    }

    @keyframes marquee-scroll {
      0% { transform: translateX(0); }
      100% { transform: translateX(-50%); }
    }

    /* ========================================================
       PLATFORM METRICS STRIP
       ======================================================== */
    .metrics-section {
      background: #091712;
      color: #ffffff;
      border-bottom: 2px solid #000;
      padding: 28px max(4vw, 24px);
    }

    .metrics-container {
      max-width: 1280px;
      margin: 0 auto;
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 20px;
    }

    .metric-card-item {
      border-left: 3px solid var(--lime);
      padding: 4px 16px;
      display: flex;
      flex-direction: column;
      gap: 4px;
    }

    .metric-val {
      font-family: 'Outfit', sans-serif;
      font-weight: 900;
      font-size: 2.1rem;
      color: var(--lime);
      line-height: 1;
      letter-spacing: -0.02em;
    }

    .metric-txt {
      font-size: 0.80rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.04em;
      color: #94a3b8;
    }

    /* ========================================================
       CONTENT SECTIONS (MODERN NEO-BRUTALIST STYLING)
       ======================================================== */
    .section-wrap {
      padding: 70px max(4vw, 24px);
      max-width: 1280px;
      margin: 0 auto;
    }

    .section-header-row {
      display: flex;
      justify-content: space-between;
      align-items: flex-end;
      flex-wrap: wrap;
      gap: 16px;
      margin-bottom: 34px;
    }

    .section-eyebrow {
      font-family: 'DM Mono', monospace;
      font-size: 0.74rem;
      font-weight: 700;
      letter-spacing: 0.12em;
      text-transform: uppercase;
      color: var(--coral);
      margin-bottom: 6px;
    }

    .section-main-title {
      font-family: 'Outfit', sans-serif;
      font-weight: 900;
      font-size: clamp(2.0rem, 3.8vw, 2.7rem);
      line-height: 1.05;
      letter-spacing: -0.03em;
      text-transform: uppercase;
      color: var(--ink);
    }

    .section-view-all {
      font-family: 'DM Mono', monospace;
      font-weight: 700;
      font-size: 0.88rem;
      color: var(--ink);
      text-decoration: underline;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      transition: color 0.2s;
    }

    .section-view-all:hover {
      color: var(--coral);
    }

    /* ── Section A: Featured Courts Cards ──────────────────── */
    .courts-grid-wrap {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(330px, 1fr));
      gap: 24px;
      margin-bottom: 32px;
    }

    .modern-court-card {
      background: #ffffff;
      border: 2px solid #000;
      border-radius: 20px;
      box-shadow: 4px 4px 0 #000;
      overflow: hidden;
      display: flex;
      flex-direction: column;
      transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .modern-court-card:hover {
      transform: translate(-3px, -3px);
      box-shadow: 7px 7px 0 #000;
    }

    .court-thumb-wrap {
      position: relative;
      width: 100%;
      height: 210px;
      border-bottom: 2px solid #000;
      overflow: hidden;
      background: #0f172a;
    }

    .court-thumb-wrap img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: transform 0.4s ease;
    }

    .modern-court-card:hover .court-thumb-wrap img {
      transform: scale(1.05);
    }

    .court-tag-pill {
      position: absolute;
      top: 12px;
      left: 12px;
      font-family: 'DM Mono', monospace;
      font-size: 0.68rem;
      font-weight: 700;
      padding: 4px 10px;
      border-radius: 9999px;
      border: 1.5px solid #000;
      box-shadow: 1.5px 1.5px 0 #000;
      background: var(--lime);
      color: var(--ink);
    }

    .court-card-body {
      padding: 20px;
      display: flex;
      flex-direction: column;
      flex-grow: 1;
      justify-content: space-between;
    }

    .court-card-title {
      font-family: 'Outfit', sans-serif;
      font-weight: 800;
      font-size: 1.22rem;
      text-transform: uppercase;
      letter-spacing: -0.02em;
      margin-bottom: 4px;
      color: var(--ink);
    }

    .court-facility-sub {
      font-size: 0.85rem;
      font-weight: 600;
      color: #475569;
      display: flex;
      align-items: center;
      gap: 6px;
      margin-bottom: 14px;
    }

    .court-card-chips {
      display: flex;
      gap: 6px;
      flex-wrap: wrap;
      margin-bottom: 18px;
    }

    .court-chip {
      font-family: 'DM Mono', monospace;
      font-size: 0.70rem;
      font-weight: 700;
      background: #f1f5f9;
      padding: 4px 8px;
      border-radius: 6px;
      border: 1px solid #cbd5e1;
      color: #334155;
    }

    .court-card-bottom {
      display: flex;
      align-items: center;
      justify-content: space-between;
      border-top: 1.5px solid #f1f5f9;
      padding-top: 14px;
    }

    .court-price-text {
      font-family: 'Outfit', sans-serif;
      font-weight: 900;
      font-size: 1.45rem;
      color: var(--ink);
      line-height: 1;
    }

    .court-price-text small {
      font-size: 0.75rem;
      color: #64748b;
      font-weight: 600;
    }

    .btn-court-book {
      background: var(--coral);
      color: #ffffff;
      font-weight: 800;
      font-size: 0.84rem;
      padding: 9px 18px;
      border-radius: 9999px;
      text-decoration: none;
      border: 1.5px solid #000;
      box-shadow: 2px 2px 0 #000;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      transition: all 0.15s ease;
    }

    .btn-court-book:hover {
      background: var(--coral-hover);
      transform: translate(-1px, -1px);
      box-shadow: 3px 3px 0 #000;
    }

    /* ── Section B: Popular Locations ──────────────────────── */
    .locations-grid-wrap {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
      gap: 16px;
    }

    .loc-card-item {
      background: #ffffff;
      border: 2px solid #000;
      border-radius: 16px;
      box-shadow: 3px 3px 0 #000;
      padding: 18px;
      text-decoration: none;
      color: var(--ink);
      display: flex;
      align-items: center;
      justify-content: space-between;
      transition: all 0.15s ease;
    }

    .loc-card-item:hover {
      background: var(--sand);
      transform: translate(-2px, -2px);
      box-shadow: 5px 5px 0 #000;
    }

    .loc-info-left {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .loc-icon-pill {
      width: 42px;
      height: 42px;
      border-radius: 12px;
      background: var(--lime);
      border: 1.5px solid #000;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.25rem;
      color: var(--ink);
      flex-shrink: 0;
    }

    .loc-title {
      font-family: 'Outfit', sans-serif;
      font-weight: 800;
      font-size: 1.08rem;
      text-transform: uppercase;
      line-height: 1.1;
    }

    .loc-province {
      font-size: 0.76rem;
      color: #64748b;
      font-weight: 600;
    }

    .loc-badge-count {
      font-family: 'DM Mono', monospace;
      font-size: 0.70rem;
      font-weight: 700;
      background: var(--sand);
      border: 1px solid #000;
      padding: 3px 8px;
      border-radius: 9999px;
    }

    /* ── Section C: How Pikvero Works (3-Step Guide) ───────── */
    .how-it-works-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 20px;
    }

    .step-box-card {
      background: #ffffff;
      border: 2px solid #000;
      border-radius: 20px;
      box-shadow: 4px 4px 0 #000;
      padding: 28px 22px;
      display: flex;
      flex-direction: column;
      align-items: flex-start;
      gap: 12px;
    }

    .step-num-badge {
      width: 46px;
      height: 46px;
      border-radius: 14px;
      border: 2px solid #000;
      font-family: 'DM Mono', monospace;
      font-weight: 900;
      font-size: 1.35rem;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 2px 2px 0 #000;
      margin-bottom: 4px;
    }

    .step-title {
      font-family: 'Outfit', sans-serif;
      font-weight: 800;
      font-size: 1.25rem;
      text-transform: uppercase;
      letter-spacing: -0.02em;
    }

    .step-desc {
      font-size: 0.90rem;
      color: #334155;
      line-height: 1.5;
    }

    /* ── Section D: Testimonials ──────────────────────────── */
    .testimonials-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 20px;
    }

    .testi-card-item {
      background: #ffffff;
      border: 2px solid #000;
      border-radius: 20px;
      box-shadow: 4px 4px 0 #000;
      padding: 24px;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
    }

    .testi-stars {
      color: #f59e0b;
      font-size: 1.15rem;
      margin-bottom: 12px;
      letter-spacing: 2px;
    }

    .testi-text {
      font-size: 0.94rem;
      line-height: 1.55;
      color: #1e293b;
      margin-bottom: 20px;
    }

    .testi-author-row {
      display: flex;
      align-items: center;
      gap: 12px;
      border-top: 1.5px solid #f1f5f9;
      padding-top: 14px;
    }

    .testi-avatar {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      border: 1.5px solid #000;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 800;
      font-size: 0.9rem;
      background: var(--lime);
      color: var(--ink);
    }

    /* ── Section E: Owner SaaS Banner ─────────────────────── */
    .owner-banner-wrap {
      background: var(--coral);
      color: #ffffff;
      border-top: 2px solid #000;
      border-bottom: 2px solid #000;
      padding: 70px max(4vw, 24px);
    }

    .owner-banner-inner {
      max-width: 1280px;
      margin: 0 auto;
      display: grid;
      grid-template-columns: 1.25fr 0.75fr;
      align-items: center;
      gap: 40px;
    }

    .owner-banner-title {
      font-family: 'Outfit', sans-serif;
      font-weight: 900;
      font-size: clamp(2.2rem, 4.2vw, 3.2rem);
      line-height: 1.05;
      letter-spacing: -0.03em;
      text-transform: uppercase;
      margin: 8px 0 16px;
    }

    .owner-banner-desc {
      font-size: 1.05rem;
      line-height: 1.55;
      opacity: 0.95;
      margin-bottom: 24px;
      max-width: 600px;
    }

    .owner-card-box {
      background: #ffffff;
      color: var(--ink);
      border: 2px solid #000;
      border-radius: 20px;
      box-shadow: 6px 6px 0 #000;
      padding: 30px;
      text-align: center;
    }

    /* ── Section F: Comprehensive Footer ──────────────────── */
    .footer-main {
      background: #091712;
      color: #ffffff;
      padding: 60px max(4vw, 24px) 30px;
      border-top: 2px solid #000;
    }

    .footer-container {
      max-width: 1280px;
      margin: 0 auto;
      display: grid;
      grid-template-columns: 1.5fr repeat(3, 1fr);
      gap: 40px;
      margin-bottom: 40px;
    }

    .footer-brand-title {
      font-family: 'Outfit', sans-serif;
      font-weight: 900;
      font-size: 1.5rem;
      letter-spacing: -0.04em;
      margin-bottom: 10px;
      color: #ffffff;
    }

    .footer-brand-desc {
      font-size: 0.88rem;
      line-height: 1.5;
      color: #94a3b8;
      max-width: 320px;
      margin-bottom: 16px;
    }

    .footer-col-title {
      font-family: 'DM Mono', monospace;
      font-size: 0.76rem;
      font-weight: 700;
      letter-spacing: 0.12em;
      text-transform: uppercase;
      color: var(--lime);
      margin-bottom: 16px;
    }

    .footer-link-list {
      list-style: none;
      display: flex;
      flex-direction: column;
      gap: 10px;
    }

    .footer-link-list a {
      color: #cbd5e1;
      text-decoration: none;
      font-size: 0.88rem;
      font-weight: 600;
      transition: color 0.2s ease;
    }

    .footer-link-list a:hover {
      color: var(--lime);
    }

    .footer-bottom-bar {
      border-top: 1px solid rgba(255, 255, 255, 0.1);
      padding-top: 24px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 14px;
      font-size: 0.82rem;
      color: #94a3b8;
    }

    /* Responsive */
    @media (max-width: 1080px) {
      .hero-grid {
        grid-template-columns: 1fr;
        gap: 40px;
      }
      .metrics-container {
        grid-template-columns: repeat(2, 1fr);
      }
      .owner-banner-inner {
        grid-template-columns: 1fr;
      }
      .footer-container {
        grid-template-columns: 1fr 1fr;
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
        padding: 84px 16px 30px;
      }
      .navbar-pill {
        top: 12px;
        width: calc(100% - 24px);
        padding: 8px 12px 8px 16px;
      }
      .metrics-container {
        grid-template-columns: 1fr;
      }
      .footer-container {
        grid-template-columns: 1fr;
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
    }

    /* Mobile drawer modal */
    .mobile-drawer {
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.65);
      backdrop-filter: blur(10px);
      z-index: 1001;
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

  <?php if (!empty($dbError)): ?>
    <!-- HOSTING DATABASE STATUS NOTICE -->
    <aside style="background: #111e18; border-bottom: 2px solid var(--coral); color: #f8fafc; padding: 10px 20px; font-size: 0.84rem; position: fixed; top: 0; left: 0; right: 0; z-index: 10000; box-shadow: 0 4px 18px rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; gap: 12px; flex-wrap: wrap;">
      <span style="color: var(--coral); display: inline-flex; align-items: center; gap: 6px; font-weight: 800;">
        <i class="bi bi-info-circle-fill"></i> HOSTING NOTICE:
      </span>
      <span style="color: #cbd5e1;">Database offline (<?= htmlspecialchars($dbError) ?>). Site running with verified preview data.</span>
      <a href="<?= $basePath ?>/debug" style="background: var(--coral); color: #fff; text-decoration: none; padding: 4px 12px; border-radius: 9999px; font-weight: 700; font-size: 0.78rem; text-transform: uppercase;">
        Open Database Diagnostics &rarr;
      </a>
    </aside>
  <?php endif; ?>

  <!-- HERO SECTION WITH BG-PIKVERO.PNG -->
  <section class="hero-bg-section">
    <div class="main-wrapper" <?= !empty($dbError) ? 'style="padding-top: 130px;"' : '' ?>>

      <!-- REUSABLE GLOBAL HEADER -->
      <?php require_once __DIR__ . '/includes/header.php'; ?>

      <!-- HERO SECTION GRID -->
      <div class="hero-grid">

        <!-- LEFT COLUMN -->
        <div class="hero-left">
          <div class="location-tag">
            <i class="bi bi-geo-alt-fill"></i>
            <span><?= htmlspecialchars($provinceName) ?> &amp; PHILIPPINES</span>
          </div>

          <h1 class="hero-title">
            RESERVE<br>
            <span class="text-coral">COURTS.</span><br>
            PLAY LOCAL.
            <span class="ball-wrapper" title="Play Pickleball">
              <svg class="ball-svg" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="50" cy="50" r="46" fill="#D6F827" stroke="#112922" stroke-width="4"/>
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

          <p class="hero-description">
            Pikvero connects players with top local pickleball courts. Real-time availability, instant online reservations, and multi-tenant court management.
          </p>

          <!-- Search Widget -->
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

            <div class="court-image-wrap" id="heroCourtSlider">
              <div class="badge-live-avail">
                <span class="live-dot"></span>
                <span>LIVE AVAILABILITY</span>
              </div>

              <div class="badge-court-pro">
                <span>COURT #<?= htmlspecialchars($courtNumber) ?></span>
                <span class="badge-pro-tag"><?= htmlspecialchars($courtType) ?></span>
              </div>

              <?php foreach ($normalizedImages as $idx => $imgSrc): ?>
                <img src="<?= htmlspecialchars($imgSrc) ?>" alt="<?= htmlspecialchars($facilityName) ?> Court Photo" class="slider-img <?= $idx === 0 ? 'active' : '' ?>" data-index="<?= $idx ?>">
              <?php endforeach; ?>

              <?php if (count($normalizedImages) > 1): ?>
                <button type="button" class="slider-btn prev" onclick="changeSlide(-1)" aria-label="Previous image">
                  <i class="bi bi-chevron-left"></i>
                </button>
                <button type="button" class="slider-btn next" onclick="changeSlide(1)" aria-label="Next image">
                  <i class="bi bi-chevron-right"></i>
                </button>

                <div class="slider-dots-wrap">
                  <?php foreach ($normalizedImages as $idx => $imgSrc): ?>
                    <div class="slider-dot <?= $idx === 0 ? 'active' : '' ?>" onclick="goToSlide(<?= $idx ?>)"></div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>

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

            <div class="amenities-badges-grid">
              <?php foreach ($heroAmenities as $amenity): ?>
                <div class="amenity-chip" title="<?= htmlspecialchars($amenity['name']) ?>">
                  <i class="bi <?= htmlspecialchars($amenity['icon'] ?: 'bi-check-circle-fill') ?>"></i>
                  <span><?= htmlspecialchars($amenity['name']) ?></span>
                </div>
              <?php endforeach; ?>
            </div>

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

      </div>

    </div>
  </section>

  <!-- MARQUEE TICKER STRIP -->
  <div class="marquee-wrap">
    <div class="marquee-track">
      <span>&#9733; INSTANT COURT RESERVATIONS</span> &bull; 
      <span>ZERO DOUBLE BOOKING CONFLICTS</span> &bull; 
      <span>&#9733; MULTI-TENANT PICKLEBALL SAAS</span> &bull; 
      <span>TOURNAMENT GRADE CUSHIONED SURFACES</span> &bull; 
      <span>&#9733; NIGHT FLOODLIGHTING AVAILABLE</span> &bull; 
      <span>DIGITAL MOBILE BOOKING SLIPS</span> &bull; 
      <span>&#9733; INSTANT COURT RESERVATIONS</span> &bull; 
      <span>ZERO DOUBLE BOOKING CONFLICTS</span> &bull; 
      <span>&#9733; MULTI-TENANT PICKLEBALL SAAS</span> &bull; 
      <span>TOURNAMENT GRADE CUSHIONED SURFACES</span> &bull; 
      <span>&#9733; NIGHT FLOODLIGHTING AVAILABLE</span> &bull; 
      <span>DIGITAL MOBILE BOOKING SLIPS</span>
    </div>
  </div>

  <!-- PLATFORM METRICS STRIP -->
  <section class="metrics-section">
    <div class="metrics-container">
      <div class="metric-card-item">
        <div class="metric-val"><?= htmlspecialchars($totalCourts) ?>+</div>
        <div class="metric-txt">Courts in <?= htmlspecialchars($provinceName) ?> &amp; Beyond</div>
      </div>
      <div class="metric-card-item">
        <div class="metric-val">100%</div>
        <div class="metric-txt">Anti-Conflict Schedule Lock</div>
      </div>
      <div class="metric-card-item">
        <div class="metric-val"><?= htmlspecialchars($totalHours) ?>+</div>
        <div class="metric-txt">Court Hours Booked</div>
      </div>
      <div class="metric-card-item">
        <div class="metric-val">&lt; 60s</div>
        <div class="metric-txt">Fast Online Reservations</div>
      </div>
    </div>
  </section>

  <!-- 1. FEATURED COURTS SECTION -->
  <section style="background:#ffffff; border-bottom: 2px solid #000;">
    <div class="section-wrap">
      <div class="section-header-row">
        <div>
          <div class="section-eyebrow">MOST RESERVED VENUES</div>
          <h2 class="section-main-title">TOP RESERVED COURTS</h2>
        </div>
        <a href="<?= $basePath ?>/public/search" class="section-view-all">VIEW ALL COURTS &rarr;</a>
      </div>

      <div class="courts-grid-wrap">
        <?php foreach ($featuredCourts as $c): ?>
          <?php 
            $cImg = !empty($c['image_url']) ? $c['image_url'] : $basePath . '/assets/images/facilities/facility_1_1787966757_ad0abf1b.jpg';
            if ($basePath !== '' && !str_starts_with($cImg, 'http') && !str_starts_with($cImg, $basePath)) {
              $cImg = $basePath . '/' . ltrim($cImg, '/');
            }
            $cPrice = number_format((float)($c['base_price_per_hour'] ?? 350), 2);
            $cSurface = ucwords(str_replace('_', ' ', $c['surface_type'] ?? 'cushioned_acrylic'));
            $cBadgeType = strtoupper($c['court_type'] ?? 'OUTDOOR');
          ?>
          <div class="modern-court-card">
            <div class="court-thumb-wrap">
              <img src="<?= htmlspecialchars($cImg) ?>" alt="<?= htmlspecialchars($c['name']) ?>" onerror="this.src='<?= $basePath ?>/assets/images/facilities/facility_1_1787966757_ad0abf1b.jpg';">
              <span class="court-tag-pill"><?= htmlspecialchars($cBadgeType) ?></span>
            </div>
            <div class="court-card-body">
              <div>
                <h3 class="court-card-title"><?= htmlspecialchars($c['name']) ?></h3>
                <div class="court-facility-sub">
                  <i class="bi bi-geo-alt-fill" style="color:var(--coral);"></i>
                  <span><?= htmlspecialchars($c['facility_name']) ?> &bull; <?= htmlspecialchars($c['city']) ?></span>
                </div>
                <div class="court-card-chips">
                  <span class="court-chip"><i class="bi bi-layers-fill"></i> <?= htmlspecialchars($cSurface) ?></span>
                  <span class="court-chip"><i class="bi bi-shield-check"></i> Verified</span>
                </div>
              </div>

              <div class="court-card-bottom">
                <div class="court-price-text">
                  &#8369;<?= htmlspecialchars($cPrice) ?> <small>/hr</small>
                </div>
                <a href="<?= $basePath ?>/public/facility?id=<?= $c['facility_id'] ?>&court_id=<?= $c['id'] ?>" class="btn-court-book">
                  <span>Book Slot</span>
                  <i class="bi bi-chevron-right"></i>
                </a>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <div style="text-align:center;">
        <a href="<?= $basePath ?>/public/search" class="btn-book-slot" style="background:var(--coral); color:#fff; box-shadow:3px 3px 0 #000;">
          <i class="bi bi-grid-fill"></i>
          <span>Explore All <?= $totalCourts ?> Courts</span>
        </a>
      </div>
    </div>
  </section>

  <!-- 2. POPULAR LOCATIONS SECTION -->
  <section style="background:var(--sand); border-bottom: 2px solid #000;">
    <div class="section-wrap">
      <div class="section-header-row">
        <div>
          <div class="section-eyebrow">METRO &amp; PROVINCIAL HUBS</div>
          <h2 class="section-main-title">POPULAR PLAY LOCATIONS</h2>
        </div>
        <a href="<?= $basePath ?>/public/search" class="section-view-all">ALL REGIONS &rarr;</a>
      </div>

      <div class="locations-grid-wrap">
        <?php foreach ($popularCities as $loc): ?>
          <a href="<?= $basePath ?>/public/search?city=<?= urlencode($loc['city']) ?>" class="loc-card-item">
            <div class="loc-info-left">
              <div class="loc-icon-pill">
                <i class="bi bi-geo-alt-fill"></i>
              </div>
              <div>
                <h4 class="loc-title"><?= htmlspecialchars($loc['city']) ?></h4>
                <div class="loc-province"><?= htmlspecialchars($loc['province']) ?></div>
              </div>
            </div>
            <span class="loc-badge-count"><?= (int)$loc['court_count'] ?> <?= (int)$loc['court_count'] === 1 ? 'Court' : 'Courts' ?></span>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- 3. HOW IT WORKS SECTION -->
  <section style="background:var(--cream); border-bottom: 2px solid #000;">
    <div class="section-wrap">
      <div style="text-align:center; max-width:680px; margin:0 auto 40px;">
        <div class="section-eyebrow">SIMPLE 3-STEP RESERVATION</div>
        <h2 class="section-main-title">HOW PIKVERO WORKS</h2>
        <p style="font-size:0.95rem; color:#475569; margin-top:8px;">
          Book premium court hours in under a minute with real-time atomic schedule locks.
        </p>
      </div>

      <div class="how-it-works-grid">
        <div class="step-box-card">
          <div class="step-num-badge" style="background:var(--lime); color:var(--ink);">1</div>
          <h3 class="step-title">SEARCH &amp; FILTER</h3>
          <p class="step-desc">
            Browse pickleball venues by city, tournament-grade cushioned acrylic surfaces, night lighting, and amenities.
          </p>
        </div>

        <div class="step-box-card">
          <div class="step-num-badge" style="background:#38bdf8; color:var(--ink);">2</div>
          <h3 class="step-title">SELECT TIME SLOT</h3>
          <p class="step-desc">
            Pick your preferred date and time slot. Our real-time atomic locking prevents double-booking disputes 100%.
          </p>
        </div>

        <div class="step-box-card">
          <div class="step-num-badge" style="background:var(--coral); color:#ffffff;">3</div>
          <h3 class="step-title">CONFIRM &amp; PLAY</h3>
          <p class="step-desc">
            Pay safely via GCash, Maya, or Card. Receive your instant digital booking slip, arrive at the facility, and play!
          </p>
        </div>
      </div>
    </div>
  </section>

  <!-- 4. FEATURED FACILITIES SECTION -->
  <section style="background:#ffffff; border-bottom: 2px solid #000;">
    <div class="section-wrap">
      <div class="section-header-row">
        <div>
          <div class="section-eyebrow">VERIFIED VENUES</div>
          <h2 class="section-main-title">FEATURED PICKLEBALL CENTERS</h2>
        </div>
        <a href="<?= $basePath ?>/public/search" class="section-view-all">ALL VENUES &rarr;</a>
      </div>

      <div class="courts-grid-wrap">
        <?php foreach ($featuredFacilities as $fac): ?>
          <?php 
            $facImg = !empty($fac['primary_img']) ? $fac['primary_img'] : $basePath . '/assets/images/facilities/facility_1_1787966757_ad0abf1b.jpg';
            if ($basePath !== '' && !str_starts_with($facImg, 'http') && !str_starts_with($facImg, $basePath)) {
              $facImg = $basePath . '/' . ltrim($facImg, '/');
            }
          ?>
          <div class="modern-court-card">
            <div class="court-thumb-wrap">
              <img src="<?= htmlspecialchars($facImg) ?>" alt="<?= htmlspecialchars($fac['name']) ?>">
              <span class="court-tag-pill" style="background:#ffffff;"><i class="bi bi-patch-check-fill" style="color:#0d9488;"></i> <?= (int)$fac['court_count'] ?> Courts</span>
            </div>
            <div class="court-card-body">
              <div>
                <h3 class="court-card-title"><?= htmlspecialchars($fac['name']) ?></h3>
                <div class="court-facility-sub">
                  <i class="bi bi-geo-alt-fill" style="color:var(--coral);"></i>
                  <span><?= htmlspecialchars($fac['city'] . ', ' . $fac['province']) ?></span>
                </div>
                <p style="font-size:0.84rem; color:#475569; line-height:1.45; margin-bottom:16px;">
                  <?= htmlspecialchars(mb_strimwidth($fac['description'] ?? 'Premier sports complex with tournament facilities.', 0, 95, '...')) ?>
                </p>
              </div>

              <div class="court-card-bottom">
                <span class="court-chip"><i class="bi bi-clock-fill"></i> Open 6AM - 10PM</span>
                <a href="<?= $basePath ?>/public/facility?id=<?= $fac['id'] ?>" class="btn-court-book" style="background:var(--lime); color:var(--ink);">
                  <span>View Center</span>
                  <i class="bi bi-chevron-right"></i>
                </a>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- 5. TESTIMONIALS SECTION -->
  <section style="background:var(--sand); border-bottom: 2px solid #000;">
    <div class="section-wrap">
      <div style="text-align:center; max-width:680px; margin:0 auto 36px;">
        <div class="section-eyebrow">COMMUNITY FEEDBACK</div>
        <h2 class="section-main-title">WHAT PLAYERS &amp; OPERATORS SAY</h2>
        <p style="font-size:0.95rem; color:#475569; margin-top:8px;">
          Trusted by pickleball enthusiasts and facility managers across Bohol and the Philippines.
        </p>
      </div>

      <div class="testimonials-grid">
        <div class="testi-card-item">
          <div>
            <div class="testi-stars">&#9733;&#9733;&#9733;&#9733;&#9733;</div>
            <p class="testi-text">
              "Booking tournament practice slots on Pikvero takes less than 30 seconds. No more endless messaging on Facebook or arriving to find an occupied court!"
            </p>
          </div>
          <div class="testi-author-row">
            <div class="testi-avatar" style="background:var(--lime);">JD</div>
            <div>
              <strong style="font-size:0.88rem; text-transform:uppercase;">Juan Dela Cruz</strong>
              <div style="font-size:0.75rem; color:#0d9488; font-weight:700;">Club Player &bull; Tagbilaran</div>
            </div>
          </div>
        </div>

        <div class="testi-card-item">
          <div>
            <div class="testi-stars">&#9733;&#9733;&#9733;&#9733;&#9733;</div>
            <p class="testi-text">
              "Our group plays every weekend in Panglao. The instant confirmation pass on mobile makes scheduling hassle-free and super organized."
            </p>
          </div>
          <div class="testi-author-row">
            <div class="testi-avatar" style="background:#38bdf8;">SR</div>
            <div>
              <strong style="font-size:0.88rem; text-transform:uppercase;">Sofia Reyes</strong>
              <div style="font-size:0.75rem; color:#0d9488; font-weight:700;">Weekend Social Player</div>
            </div>
          </div>
        </div>

        <div class="testi-card-item">
          <div>
            <div class="testi-stars">&#9733;&#9733;&#9733;&#9733;&#9733;</div>
            <p class="testi-text">
              "The schedule blockouts and automated booking prevention saved our court operations. Customer disputes dropped to absolute zero."
            </p>
          </div>
          <div class="testi-author-row">
            <div class="testi-avatar" style="background:var(--coral); color:#fff;">MV</div>
            <div>
              <strong style="font-size:0.88rem; text-transform:uppercase;">Marcus Vance</strong>
              <div style="font-size:0.75rem; color:#0d9488; font-weight:700;">Facility Owner &bull; SmashZone</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- 6. BECOME A COURT OWNER CTA SECTION -->
  <section class="owner-banner-wrap">
    <div class="owner-banner-inner">
      <div>
        <div style="background:#000; color:#fff; display:inline-block; padding:4px 10px; border-radius:6px; font-family:'DM Mono', monospace; font-size:0.72rem; font-weight:700;">
          OWNER SAAS PLATFORM
        </div>
        <h2 class="owner-banner-title">
          LIST YOUR PICKLEBALL FACILITY ON PIKVERO
        </h2>
        <p class="owner-banner-desc">
          Empower your sports complex with automated court scheduling, peak/off-peak rate rules, instant customer reservations, and revenue analytics.
        </p>
        <div style="display:flex; gap:14px; align-items:center; flex-wrap:wrap;">
          <a href="<?= $basePath ?>/public/register?type=owner" class="btn-book-slot" style="background:var(--lime); font-size:0.95rem; padding:14px 28px;">
            <i class="bi bi-building-add"></i>
            <span>List Your Facility &rarr;</span>
          </a>
          <a href="<?= $basePath ?>/public/pricing" class="btn-book-slot" style="background:#112922; color:#fff; font-size:0.92rem; padding:14px 24px;">
            View Owner Pricing
          </a>
        </div>
      </div>

      <div>
        <div class="owner-card-box">
          <div style="width:56px; height:56px; border-radius:14px; background:var(--lime); border:2px solid #000; box-shadow:2.5px 2.5px 0 #000; margin:0 auto 16px; display:flex; align-items:center; justify-content:center; font-size:1.8rem;">
            <i class="bi bi-speedometer2"></i>
          </div>
          <h3 style="font-family:'Outfit', sans-serif; font-weight:800; font-size:1.35rem; text-transform:uppercase; margin-bottom:8px;">
            START IN 5 MINUTES
          </h3>
          <p style="font-size:0.86rem; color:#475569; line-height:1.45; margin-bottom:20px;">
            Set up operating hours, configure court types, and begin accepting reservations immediately.
          </p>
          <a href="<?= $basePath ?>/public/register?type=owner" class="btn-book-slot" style="background:var(--coral); color:#fff; width:100%; justify-content:center;">
            Register Facility Now
          </a>
        </div>
      </div>
    </div>
  </section>

  <!-- 7. COMPREHENSIVE FOOTER -->
  <footer class="footer-main">
    <div class="footer-container">
      <div>
        <div class="footer-brand-title">PIKVERO</div>
        <p class="footer-brand-desc">
          The premier pickleball court booking platform in Bohol and across the Philippines. Connecting passionate players with top-tier facilities.
        </p>
        <div style="display:flex; gap:10px;">
          <a href="#" style="color:#ffffff; font-size:1.2rem;" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
          <a href="#" style="color:#ffffff; font-size:1.2rem;" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
          <a href="#" style="color:#ffffff; font-size:1.2rem;" aria-label="Twitter"><i class="bi bi-twitter-x"></i></a>
        </div>
      </div>

      <div>
        <div class="footer-col-title">EXPLORE</div>
        <ul class="footer-link-list">
          <li><a href="<?= $basePath ?>/public/search">Browse Courts</a></li>
          <li><a href="<?= $basePath ?>/public/open-play">Open Play Socials</a></li>
          <li><a href="<?= $basePath ?>/public/pricing">Hourly Passes</a></li>
          <li><a href="<?= $basePath ?>/public/search?city=Tagbilaran+City">Bohol Courts</a></li>
        </ul>
      </div>

      <div>
        <div class="footer-col-title">FACILITY OWNERS</div>
        <ul class="footer-link-list">
          <li><a href="<?= $basePath ?>/public/register?type=owner">List Your Venue</a></li>
          <li><a href="<?= $basePath ?>/public/pricing">SaaS Subscriptions</a></li>
          <li><a href="<?= $basePath ?>/public/owner/dashboard">Owner Portal</a></li>
          <li><a href="<?= $basePath ?>/public/login">Login to Dashboard</a></li>
        </ul>
      </div>

      <div>
        <div class="footer-col-title">SUPPORT &amp; LEGAL</div>
        <ul class="footer-link-list">
          <li><a href="<?= $basePath ?>/public/about">About Pikvero</a></li>
          <li><a href="<?= $basePath ?>/public/contact">Contact Support</a></li>
          <li><a href="<?= $basePath ?>/public/terms">Terms of Service</a></li>
          <li><a href="<?= $basePath ?>/public/privacy">Privacy Policy</a></li>
        </ul>
      </div>
    </div>

    <div class="footer-bottom-bar" style="max-width:1280px; margin:0 auto;">
      <div>&copy; <?= date('Y') ?> Pikvero. All rights reserved. Built for Streetside Pickleball.</div>
      <div style="display:flex; gap:16px;">
        <span>🇵🇭 Tagbilaran City, Bohol, Philippines</span>
      </div>
    </div>
  </footer>

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

    if (slides.length > 1) {
      setInterval(() => {
        changeSlide(1);
      }, 6000);
    }
  </script>
</body>
</html>
