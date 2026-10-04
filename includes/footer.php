<?php
/**
 * Pikvero Global Desktop Footer Partial
 * Visible on desktop and large screens, matching Pikvero's glassmorphic aesthetic.
 */
require_once __DIR__ . '/../app/bootstrap.php';
$reqUri    = $_SERVER['REQUEST_URI'] ?? '/';
$basePath  = (strpos($reqUri, '/pikvero') === 0) ? '/pikvero' : '';
$logoImg   = $basePath . '/assets/images/logo.png';
$currYear  = date('Y');
?>
<style id="pikvero-global-footer-css">
  .pikvero-global-footer {
    position: relative;
    z-index: 10;
    width: 100%;
    background: rgba(12, 26, 21, 0.95);
    backdrop-filter: blur(24px);
    -webkit-backdrop-filter: blur(24px);
    border-top: 1.5px solid rgba(255, 255, 255, 0.12);
    color: #cbd5e1;
    font-family: 'Plus Jakarta Sans', sans-serif;
    margin-top: auto;
    padding: 56px 24px 28px;
    box-sizing: border-box;
  }

  .footer-inner-container {
    max-width: 1280px;
    margin: 0 auto;
    display: flex;
    flex-direction: column;
    gap: 40px;
  }

  .footer-top-grid {
    display: grid;
    grid-template-columns: 2.2fr 1fr 1fr 1.2fr;
    gap: 36px;
    align-items: flex-start;
  }

  .footer-brand-col {
    display: flex;
    flex-direction: column;
    gap: 16px;
  }

  .footer-brand-row {
    display: inline-flex;
    align-items: center;
    gap: 12px;
    text-decoration: none;
    color: #ffffff;
  }

  .footer-logo-badge {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    background: #ffffff;
    padding: 3px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
  }

  .footer-logo-badge img {
    width: 100%;
    height: 100%;
    object-fit: contain;
  }

  .footer-brand-title {
    font-family: 'Outfit', sans-serif;
    font-size: 1.4rem;
    font-weight: 900;
    letter-spacing: -0.02em;
    color: #ffffff;
  }

  .footer-brand-desc {
    font-size: 0.90rem;
    line-height: 1.6;
    color: #94a3b8;
    max-width: 380px;
  }

  .footer-status-pill {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.15);
    border-radius: 9999px;
    padding: 6px 14px;
    font-family: 'DM Mono', monospace;
    font-size: 0.76rem;
    font-weight: 800;
    color: #e2e8f0;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    width: fit-content;
  }

  .footer-status-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #10b981;
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.3);
  }

  .footer-links-col {
    display: flex;
    flex-direction: column;
    gap: 12px;
  }

  .footer-col-title {
    font-family: 'Outfit', sans-serif;
    font-size: 0.96rem;
    font-weight: 800;
    color: #ffffff;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    margin-bottom: 4px;
  }

  .footer-link-item {
    color: #94a3b8;
    text-decoration: none;
    font-size: 0.88rem;
    font-weight: 500;
    transition: color 0.2s ease, transform 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    width: fit-content;
  }

  .footer-link-item:hover {
    color: #d4f82c;
    transform: translateX(3px);
  }

  .footer-link-item i {
    font-size: 0.82rem;
    opacity: 0.6;
  }

  .footer-bottom-row {
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    padding-top: 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 16px;
    font-size: 0.82rem;
    color: #64748b;
  }

  .footer-copyright {
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .footer-legal-links {
    display: flex;
    align-items: center;
    gap: 20px;
  }

  .footer-legal-links a {
    color: #64748b;
    text-decoration: none;
    font-family: 'DM Mono', monospace;
    font-size: 0.78rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    transition: color 0.2s ease;
  }

  .footer-legal-links a:hover {
    color: #ffffff;
  }

  @media (max-width: 900px) {
    .footer-top-grid {
      grid-template-columns: 1fr 1fr;
      gap: 28px;
    }
    .footer-brand-col {
      grid-column: 1 / -1;
    }
  }

  @media (max-width: 600px) {
    .footer-top-grid {
      grid-template-columns: 1fr;
      gap: 24px;
    }
    .footer-bottom-row {
      flex-direction: column;
      align-items: flex-start;
      gap: 12px;
    }
  }
</style>

<footer class="pikvero-global-footer">
  <div class="footer-inner-container">
    <div class="footer-top-grid">
      <!-- Col 1: Brand Info -->
      <div class="footer-brand-col">
        <a href="<?= $basePath ?>/" class="footer-brand-row">
          <div class="footer-logo-badge">
            <img src="<?= htmlspecialchars($logoImg) ?>" alt="Pikvero Logo" onerror="this.onerror=null; this.parentNode.innerHTML='🎾';">
          </div>
          <span class="footer-brand-title">PIKVERO</span>
        </a>
        <p class="footer-brand-desc">
          Bohol's premier multi-tenant pickleball network. Real-time court reservations, social open play paddle-rotation sessions, and facility management.
        </p>
        <div class="footer-status-pill">
          <span class="footer-status-dot"></span>
          <span>BOHOL NETWORK &bull; ALL COURTS ACTIVE</span>
        </div>
      </div>

      <!-- Col 2: Players & Community -->
      <div class="footer-links-col">
        <div class="footer-col-title">Players</div>
        <a href="<?= $basePath ?>/public/search" class="footer-link-item"><i class="bi bi-chevron-right"></i> Explore Courts</a>
        <a href="<?= $basePath ?>/public/open-play" class="footer-link-item"><i class="bi bi-chevron-right"></i> Open Play Sessions</a>
        <a href="<?= $basePath ?>/public/search?type=indoor" class="footer-link-item"><i class="bi bi-chevron-right"></i> Indoor Courts</a>
        <a href="<?= $basePath ?>/public/search?type=outdoor" class="footer-link-item"><i class="bi bi-chevron-right"></i> Outdoor Courts</a>
      </div>

      <!-- Col 3: Facility Owners -->
      <div class="footer-links-col">
        <div class="footer-col-title">Venue Owners</div>
        <a href="<?= $basePath ?>/public/register?type=owner" class="footer-link-item"><i class="bi bi-chevron-right"></i> List Your Facility</a>
        <a href="<?= $basePath ?>/public/pricing" class="footer-link-item"><i class="bi bi-chevron-right"></i> Subscription Plans</a>
        <a href="<?= $basePath ?>/public/login" class="footer-link-item"><i class="bi bi-chevron-right"></i> Owner Portal Login</a>
      </div>

      <!-- Col 4: Platform & Support -->
      <div class="footer-links-col">
        <div class="footer-col-title">Pikvero Support</div>
        <a href="<?= $basePath ?>/public/contact" class="footer-link-item"><i class="bi bi-chevron-right"></i> Help Center</a>
        <a href="<?= $basePath ?>/public/about" class="footer-link-item"><i class="bi bi-chevron-right"></i> About Us</a>
        <a href="<?= $basePath ?>/public/privacy" class="footer-link-item"><i class="bi bi-chevron-right"></i> Privacy Policy</a>
        <a href="<?= $basePath ?>/public/terms" class="footer-link-item"><i class="bi bi-chevron-right"></i> Terms of Service</a>
      </div>
    </div>

    <!-- Bottom Row -->
    <div class="footer-bottom-row">
      <div class="footer-copyright">
        <span>&copy; <?= $currYear ?> Pikvero Platform. All rights reserved.</span>
        <span style="opacity:0.4;">|</span>
        <span>Crafted for Bohol Pickleball Community</span>
      </div>
      <div class="footer-legal-links">
        <a href="<?= $basePath ?>/public/privacy">PRIVACY</a>
        <a href="<?= $basePath ?>/public/terms">TERMS</a>
        <a href="<?= $basePath ?>/public/contact">CONTACT</a>
      </div>
    </div>
  </div>
</footer>
