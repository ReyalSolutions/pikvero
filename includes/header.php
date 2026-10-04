<?php
/**
 * Pikvero Global Fixed Header Partial
 * Reusable across guest, marketplace, and public pages.
 */
require_once __DIR__ . '/../app/bootstrap.php';

use App\Core\Auth\Auth;

// Calculate dynamic base URL and base path
$reqUri    = $_SERVER['REQUEST_URI'] ?? '/';
$script    = $_SERVER['SCRIPT_NAME'] ?? '';
$cleanUri  = strtok($reqUri, '?');
$basePath  = (strpos($reqUri, '/pikvero') === 0) ? '/pikvero' : '';

// Authentication and user state
$isLoggedIn = class_exists(Auth::class) ? Auth::check() : false;
$userRole   = $isLoggedIn ? (Auth::role() ?? 'customer') : 'guest';
$userName   = $isLoggedIn ? (Auth::user()['first_name'] ?? 'Player') : '';

$dashboardUrl = $basePath . '/public/customer/dashboard';
if ($userRole === 'court_owner') {
    $dashboardUrl = $basePath . '/public/owner/dashboard';
} elseif ($userRole === 'super_admin' || $userRole === 'admin') {
    $dashboardUrl = $basePath . '/public/admin/dashboard';
}

// Active navigation link detection
$isOwner = str_contains($reqUri, 'type=owner') || str_contains($script, 'owner-onboarding');
$isPricing = str_contains($script, 'pricing') || str_contains($cleanUri, '/pricing');
$isSearch = str_contains($script, 'search') || str_contains($script, 'facility') || str_contains($cleanUri, '/search') || str_contains($cleanUri, '/facility');
$isOpenPlay = str_contains($script, 'open-play') || str_contains($cleanUri, '/open-play');
$isHome = !$isOwner && !$isPricing && !$isSearch && !$isOpenPlay && (
    str_ends_with($cleanUri, '/pikvero') ||
    str_ends_with($cleanUri, '/pikvero/') ||
    str_ends_with($cleanUri, '/public') ||
    str_ends_with($cleanUri, '/public/') ||
    str_ends_with($cleanUri, '/index.php') ||
    $cleanUri === '/' ||
    $cleanUri === ''
);

$logoImg = $basePath . '/assets/images/logo.png';
?>
<!-- Styles for Global Floating Pill Header -->
<style id="pikvero-global-header-css">
  :root {
    --hdr-coral: #ff5733;
    --hdr-coral-hover: #e04422;
    --hdr-lime: #d6f827;
    --hdr-lime-hover: #c4e61b;
    --hdr-ink: #0c1a15;
  }

  .navbar-pill {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    right: 0 !important;
    width: 100% !important;
    max-width: 100% !important;
    transform: none !important;
    z-index: 1000 !important;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border-radius: 0 !important;
    padding: 10px 24px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    border: none !important;
    border-bottom: 1.5px solid rgba(255, 255, 255, 0.95) !important;
    box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.08) !important;
    transition: all 0.3s ease;
    box-sizing: border-box !important;
  }

  .navbar-inner-wrap {
    width: 100% !important;
    max-width: 1360px !important;
    margin: 0 auto !important;
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    box-sizing: border-box !important;
  }

  .navbar-pill * {
    box-sizing: border-box;
  }

  .brand-group {
    display: flex;
    align-items: center;
    gap: 12px;
    text-decoration: none;
    color: var(--hdr-ink);
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
    color: var(--hdr-ink);
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
    background: var(--hdr-coral);
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
    color: var(--hdr-ink);
    text-decoration: none;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    padding: 8px 12px;
    transition: color 0.2s ease;
  }

  .nav-link-login:hover {
    color: var(--hdr-coral);
  }

  .btn-play-local {
    background: var(--hdr-lime);
    color: var(--hdr-ink);
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
    background: var(--hdr-lime-hover);
    transform: translateY(-1px);
    box-shadow: 0 6px 16px rgba(214, 248, 39, 0.6);
  }

  .mobile-menu-btn {
    display: none;
    background: none;
    border: none;
    font-size: 1.6rem;
    color: var(--hdr-ink);
    cursor: pointer;
    padding: 4px;
  }

  /* Mobile Drawer */
  .mobile-drawer {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.65);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
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

  @media (max-width: 1080px) {
    .nav-links-wrap {
      display: none !important;
    }
    .mobile-menu-btn {
      display: block !important;
    }
  }

  @media (max-width: 640px) {
    .navbar-pill {
      top: 0 !important;
      left: 0 !important;
      right: 0 !important;
      width: 100% !important;
      border-radius: 0 !important;
      padding: 10px 16px !important;
    }
  }
</style>

<!-- Reusable Global Fixed Navbar (Pinned at very top) -->
<header class="navbar-pill" <?= !empty($GLOBALS['dbError'] ?? $dbError ?? null) ? 'style="top: 42px;"' : '' ?>>
  <div class="navbar-inner-wrap">
    <a href="<?= $basePath ?>/" class="brand-group">
      <div class="brand-logo-badge">
        <img src="<?= htmlspecialchars($logoImg) ?>" alt="Pikvero Logo" onerror="this.onerror=null; this.parentNode.innerHTML='🎾';">
      </div>
      <span class="brand-name">PIKVERO</span>
    </a>

    <nav class="nav-links-wrap">
      <a href="<?= $basePath ?>/" class="nav-pill-item <?= $isHome ? 'active' : '' ?>">
        <i class="bi bi-house-door-fill"></i>
        <span>Home</span>
      </a>
      <a href="<?= $basePath ?>/public/search" class="nav-pill-item <?= $isSearch ? 'active' : '' ?>">
        <i class="bi bi-search"></i>
        <span>Explore Courts</span>
      </a>
      <a href="<?= $basePath ?>/public/open-play" class="nav-pill-item <?= $isOpenPlay ? 'active' : '' ?>">
        <i class="bi bi-people-fill"></i>
        <span>Open Play</span>
      </a>
      <a href="<?= $basePath ?>/public/pricing" class="nav-pill-item <?= $isPricing ? 'active' : '' ?>">
        <i class="bi bi-tag-fill"></i>
        <span>Pricing</span>
      </a>
      <a href="<?= $basePath ?>/public/register?type=owner" class="nav-pill-item <?= $isOwner ? 'active' : '' ?>">
        <i class="bi bi-shop"></i>
        <span>Become an Owner</span>
      </a>
    </nav>

    <div class="nav-actions-wrap">
      <?php if ($isLoggedIn): ?>
        <a href="<?= $dashboardUrl ?>" class="nav-link-login" style="color:var(--hdr-coral);">
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
  </div>
</header>

<!-- Mobile Navigation Drawer -->
<div class="mobile-drawer" id="mobileDrawer" onclick="if(event.target === this) toggleMobileMenu()">
  <div class="drawer-content">
    <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1.5px solid #e2e8f0; padding-bottom:12px;">
      <span style="font-weight:800; font-family:'Outfit',sans-serif; text-transform:uppercase; font-size:1.1rem;">Menu</span>
      <button onclick="toggleMobileMenu()" style="background:none; border:none; font-size:1.5rem; cursor:pointer; line-height:1;" aria-label="Close menu">&times;</button>
    </div>
    <a href="<?= $basePath ?>/" style="text-decoration:none; color:<?= $isHome ? 'var(--hdr-coral)' : '#1e293b' ?>; font-weight:700; font-size:0.95rem; display:flex; align-items:center; gap:8px;">
      <i class="bi bi-house-door-fill"></i> Home
    </a>
    <a href="<?= $basePath ?>/public/search" style="text-decoration:none; color:<?= $isSearch ? 'var(--hdr-coral)' : '#1e293b' ?>; font-weight:700; font-size:0.95rem; display:flex; align-items:center; gap:8px;">
      <i class="bi bi-search"></i> Explore Courts
    </a>
    <a href="<?= $basePath ?>/public/open-play" style="text-decoration:none; color:<?= $isOpenPlay ? 'var(--hdr-coral)' : '#1e293b' ?>; font-weight:700; font-size:0.95rem; display:flex; align-items:center; gap:8px;">
      <i class="bi bi-people-fill"></i> Open Play
    </a>
    <a href="<?= $basePath ?>/public/pricing" style="text-decoration:none; color:<?= $isPricing ? 'var(--hdr-coral)' : '#1e293b' ?>; font-weight:700; font-size:0.95rem; display:flex; align-items:center; gap:8px;">
      <i class="bi bi-tag-fill"></i> Pricing
    </a>
    <a href="<?= $basePath ?>/public/register?type=owner" style="text-decoration:none; color:<?= $isOwner ? 'var(--hdr-coral)' : '#1e293b' ?>; font-weight:700; font-size:0.95rem; display:flex; align-items:center; gap:8px;">
      <i class="bi bi-shop"></i> Become an Owner
    </a>
    <div style="border-top:1.5px solid #e2e8f0; padding-top:14px; margin-top:auto;">
      <?php if ($isLoggedIn): ?>
        <a href="<?= $dashboardUrl ?>" class="btn-play-local" style="width:100%; justify-content:center; text-decoration:none;">Dashboard</a>
      <?php else: ?>
        <a href="<?= $basePath ?>/public/login" class="btn-play-local" style="width:100%; justify-content:center; text-decoration:none;">Login</a>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
  function toggleMobileMenu() {
    const drawer = document.getElementById('mobileDrawer');
    if (drawer) {
      drawer.classList.toggle('open');
    }
  }
  // Backward compatibility alias for legacy scripts
  function toggleSidebar() { toggleMobileMenu(); }
  function closeSidebar() { 
    const drawer = document.getElementById('mobileDrawer');
    if (drawer) drawer.classList.remove('open');
  }
</script>
