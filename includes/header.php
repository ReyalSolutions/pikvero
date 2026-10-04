<?php
/**
 * Shared Header Partial for Guest & Public Pages
 */
require_once __DIR__ . '/../app/bootstrap.php';

use App\Core\Auth\Auth;

$uri      = $_SERVER['REQUEST_URI'] ?? '';
$script   = $_SERVER['SCRIPT_NAME'] ?? '';
$cleanUri = strtok($uri, '?');

$isOwner   = str_contains($uri, 'type=owner') || str_contains($script, 'owner-onboarding.php');
$isPricing = str_contains($script, 'pricing.php');
$isSearch  = str_contains($script, 'search.php') || str_contains($script, 'facility.php');
$isOpenPlay= str_contains($script, 'open-play.php');
$isHome    = !$isOwner && !$isPricing && !$isSearch && !$isOpenPlay && (
              str_contains($script, 'index.php') || 
              str_ends_with($cleanUri, '/public/') || 
              str_ends_with($cleanUri, '/public') || 
              str_ends_with($cleanUri, '/pikvero/') || 
              str_ends_with($cleanUri, '/pikvero')
            );

$isLoggedIn = class_exists(Auth::class) ? Auth::check() : false;
$homeLink = '/pikvero/public/index.php';
$role = 'guest';
if ($isLoggedIn) {
    $role = strtolower(Auth::role() ?? 'customer');
    if ($role === 'customer') {
        $homeLink = '/pikvero/public/customer/dashboard.php';
    } elseif ($role === 'court_owner') {
        $homeLink = '/pikvero/public/owner/dashboard.php';
    }
}
$favLogo = class_exists(Auth::class) ? Auth::getLogoUrl() : '/pikvero/assets/images/logo.png';
$appName = 'Pikvero';
?>
<script>
  window.SERVER_AUTH_STATE = <?= json_encode(['isLoggedIn' => $isLoggedIn, 'role' => $role, 'homeLink' => $homeLink, 'logoUrl' => $favLogo, 'appName' => $appName]) ?>;
  (function() {
    var logoUrl = <?= json_encode($favLogo) ?>;
    var links = document.querySelectorAll("link[rel*='icon']");
    if (links.length === 0 && logoUrl) {
      var link = document.createElement('link');
      link.rel = 'icon';
      link.type = 'image/png';
      link.href = logoUrl;
      document.head.appendChild(link);
    }
  })();

  function toggleSidebar() {
    var sc = document.getElementById('sidebar-container');
    var bd = document.getElementById('sidebar-backdrop');
    if (!sc) return;
    var willOpen = !sc.classList.contains('is-open');
    sc.classList.toggle('is-open', willOpen);
    if (bd) bd.classList.toggle('is-active', willOpen);
    document.body.classList.toggle('sidebar-open', willOpen);
  }

  function closeSidebar() {
    var sc = document.getElementById('sidebar-container');
    var bd = document.getElementById('sidebar-backdrop');
    if (sc) sc.classList.remove('is-open');
    if (bd) bd.classList.remove('is-active');
    document.body.classList.remove('sidebar-open');
  }

  // Close sidebar on ESC key
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeSidebar();
  });
</script>
<script src="/pikvero/assets/js/core/push-notifications.js"></script>

<!-- Mobile Navigation Drawer / Sidebar for Guest & Marketplace Visitors -->
<aside id="sidebar-container" class="portal-sidebar drawer-only">
  <div style="display:flex; flex-direction:column; justify-content:space-between; height:100%;">
    <div>
      <!-- Brand Header with Close Button -->
      <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:20px; padding-bottom:12px; border-bottom:2px solid var(--ink);">
        <a href="<?= $homeLink ?>" class="brand-streetside" style="gap:10px; display:flex; align-items:center;">
          <div class="brand-mark sidebar-logo-mark" style="width:42px; height:42px; border-radius:12px; overflow:hidden; padding:3px; display:flex; align-items:center; justify-content:center; background:var(--white); border:2px solid var(--ink); box-shadow:2px 2px 0 var(--ink); flex-shrink:0; box-sizing:border-box;">
            <img src="<?= htmlspecialchars($favLogo) ?>" alt="Pikvero" style="width:100%; height:100%; max-width:100%; max-height:100%; object-fit:contain; display:block;" onerror="this.onerror=null; this.parentNode.innerHTML='P';">
          </div>
          <span style="font-weight:800; font-size:1.15rem; text-transform:uppercase; letter-spacing:-0.03em;"><?= htmlspecialchars($appName) ?></span>
        </a>
        <button type="button" onclick="closeSidebar()" class="button dark" style="padding:4px 10px; font-size:1.1rem; line-height:1;" id="sidebar-close-btn" aria-label="Close menu">&times;</button>
      </div>

      <div class="mono" style="margin-bottom:12px; color:var(--green); font-size:0.68rem; font-weight:700;">NAVIGATION MENU</div>

      <!-- Navigation Links -->
      <div id="sidebar-nav-links" style="display:flex; flex-direction:column; gap:6px;">
        <a href="<?= $homeLink ?>" class="button <?= $isHome ? 'coral' : 'sand' ?>" style="justify-content:flex-start; width:100%; border-radius:12px; padding:10px 14px; font-size:0.85rem; box-shadow:2px 2px 0 var(--ink);">
          <i class="bi bi-house-door-fill"></i>
          <span>Home</span>
        </a>
        <a href="/pikvero/public/search.php" class="button <?= $isSearch ? 'coral' : 'sand' ?>" style="justify-content:flex-start; width:100%; border-radius:12px; padding:10px 14px; font-size:0.85rem; box-shadow:2px 2px 0 var(--ink);">
          <i class="bi bi-search"></i>
          <span>Explore Courts</span>
        </a>
        <a href="/pikvero/public/open-play.php" class="button <?= $isOpenPlay ? 'coral' : 'sand' ?>" style="justify-content:flex-start; width:100%; border-radius:12px; padding:10px 14px; font-size:0.85rem; box-shadow:2px 2px 0 var(--ink);">
          <i class="bi bi-dribbble"></i>
          <span>Open Play Socials</span>
        </a>
        <a href="/pikvero/public/pricing.php" class="button <?= $isPricing ? 'coral' : 'sand' ?>" style="justify-content:flex-start; width:100%; border-radius:12px; padding:10px 14px; font-size:0.85rem; box-shadow:2px 2px 0 var(--ink);">
          <i class="bi bi-tag-fill"></i>
          <span>Pricing &amp; Passes</span>
        </a>
        <a href="/pikvero/public/register.php?type=owner" class="button <?= $isOwner ? 'coral' : 'sand' ?>" style="justify-content:flex-start; width:100%; border-radius:12px; padding:10px 14px; font-size:0.85rem; box-shadow:2px 2px 0 var(--ink);">
          <i class="bi bi-building-fill-add"></i>
          <span>Become an Owner</span>
        </a>

        <!-- Extra Information Links -->
        <div style="margin-top:6px; border-top:1px dashed var(--line); padding-top:10px;">
          <div class="mono" style="margin-bottom:8px; color:#4a5c56; font-size:0.65rem; font-weight:700;">ABOUT &amp; SUPPORT</div>
          <a href="/pikvero/public/about.php" class="button sand" style="justify-content:flex-start; width:100%; border-radius:10px; margin-bottom:4px; padding:8px 12px; font-size:0.78rem; box-shadow:1.5px 1.5px 0 var(--ink);">
            <i class="bi bi-info-circle-fill"></i>
            <span>About Pikvero</span>
          </a>
          <a href="/pikvero/public/contact.php" class="button sand" style="justify-content:flex-start; width:100%; border-radius:10px; padding:8px 12px; font-size:0.78rem; box-shadow:1.5px 1.5px 0 var(--ink);">
            <i class="bi bi-envelope-fill"></i>
            <span>Contact Us</span>
          </a>
        </div>
      </div>
    </div>

    <!-- Auth Actions in Sidebar Footer -->
    <div id="sidebar-auth-section" style="border-top:2px solid var(--ink); padding-top:14px; margin-top:16px;">
      <?php if ($isLoggedIn): ?>
        <a href="<?= $homeLink ?>" class="button coral" style="justify-content:center; width:100%; border-radius:12px; padding:10px 14px; font-size:0.85rem; margin-bottom:8px; box-shadow:2px 2px 0 var(--ink);">
          <i class="bi bi-grid-fill"></i>
          <span>My Dashboard</span>
        </a>
        <button type="button" onclick="if(typeof AuthHelper !== 'undefined') AuthHelper.logout(); else window.location.href='/pikvero/public/login.php?action=logout';" class="button dark" style="justify-content:center; width:100%; border-radius:12px; padding:9px 14px; font-size:0.82rem; box-shadow:2px 2px 0 var(--coral);">
          <i class="bi bi-box-arrow-right"></i>
          <span>Logout</span>
        </button>
      <?php else: ?>
        <a href="/pikvero/public/login.php" class="button sand" style="justify-content:center; width:100%; border-radius:12px; padding:10px 14px; font-size:0.85rem; margin-bottom:8px; box-shadow:2px 2px 0 var(--ink);">
          <i class="bi bi-box-arrow-in-right"></i>
          <span>Login to Account</span>
        </a>
        <a href="/pikvero/public/register.php" class="button lime" style="justify-content:center; width:100%; border-radius:12px; padding:10px 14px; font-size:0.85rem; box-shadow:2px 2px 0 var(--ink);">
          <i class="bi bi-person-plus-fill"></i>
          <span>Play Local (Sign Up)</span>
        </a>
      <?php endif; ?>
    </div>
  </div>
</aside>

<!-- Backdrop overlay for drawer sidebar on mobile -->
<div id="sidebar-backdrop" class="sidebar-backdrop" onclick="closeSidebar()"></div>

<div id="navbar-container">
  <nav class="nav-streetside">
    <div style="display:flex; align-items:center; gap:8px;">
      <a href="<?= $homeLink ?>" class="brand-streetside" style="gap:8px; display:flex; align-items:center;">
        <div class="brand-mark navbar-logo-mark" style="width:38px; height:38px; font-size:1rem; border-radius:10px; flex-shrink:0; overflow:hidden; padding:3px; display:flex; align-items:center; justify-content:center; background:var(--white); border:2px solid var(--ink); box-sizing:border-box;">
          <img src="<?= htmlspecialchars($favLogo) ?>" alt="Pikvero" style="width:100%; height:100%; max-width:100%; max-height:100%; object-fit:contain; display:block;" onerror="this.onerror=null; this.parentNode.innerHTML='P';">
        </div>
        <span style="font-weight:800; font-size:1.1rem; text-transform:uppercase;"><?= htmlspecialchars($appName) ?></span>
      </a>
    </div>
    <div class="nav-links" style="display:flex; gap:14px; align-items:center; font-weight:800; font-size:0.85rem;">
      <a href="<?= $homeLink ?>" class="<?= $isHome ? 'active' : '' ?>">Home</a>
      <a href="/pikvero/public/search.php" class="<?= $isSearch ? 'active' : '' ?>">Explore Courts</a>
      <a href="/pikvero/public/open-play.php" class="<?= $isOpenPlay ? 'active' : '' ?>">Open Play</a>
      <?php if (!$isLoggedIn): ?>
        <a href="/pikvero/public/pricing.php" class="<?= $isPricing ? 'active' : '' ?>">Pricing</a>
        <a href="/pikvero/public/register.php?type=owner" class="<?= $isOwner ? 'active' : '' ?>">Become an Owner</a>
      <?php endif; ?>
    </div>
    <div style="display:flex; align-items:center; gap:8px;">
      <div class="navbar-auth-desktop" style="align-items:center; gap:8px;">
        <?php if ($isLoggedIn): ?>
          <a href="<?= $homeLink ?>" class="button coral" style="padding:6px 12px; font-size:0.8rem;">
            <i class="bi bi-grid-fill"></i> My Dashboard
          </a>
        <?php else: ?>
          <a href="/pikvero/public/login.php" class="mono" style="font-weight:700; padding:0 6px;">Login</a>
          <a href="/pikvero/public/register.php" class="button" style="padding:8px 14px; font-size:0.82rem;">Play Local</a>
        <?php endif; ?>
      </div>
      <button type="button" onclick="toggleSidebar()" class="hamburger-toggle-btn button dark" style="padding:6px 11px; font-size:1.15rem; align-items:center; justify-content:center; border-radius:10px; cursor:pointer;" title="Open Menu" aria-label="Toggle navigation menu">
        <i class="bi bi-list" style="font-weight:900;"></i>
      </button>
    </div>
  </nav>
</div>
