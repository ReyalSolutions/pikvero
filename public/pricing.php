<?php
/**
 * Pikvero — Owner Platform Subscriptions & Pricing
 * Frosted glassmorphic design matching visual reference with pricing-bg.png
 */
require_once __DIR__ . '/../app/bootstrap.php';
use App\Core\Auth\Auth;

// Calculate dynamic base URL and base path
$reqUri   = $_SERVER['REQUEST_URI'] ?? '/';
$basePath = (strpos($reqUri, '/pikvero') === 0) ? '/pikvero' : '';

$isLoggedIn = class_exists(Auth::class) ? Auth::check() : false;
$user = $isLoggedIn ? Auth::user() : [];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
  <title>Pikvero — Owner Subscription Plans &amp; Pricing</title>
  <meta name="description" content="Transparent subscription plans for Pikvero court owners and facility managers. Starter, Pro, and Enterprise tiers for every pickleball venue.">
  
  <link rel="icon" type="image/png" href="<?= $basePath ?>/assets/images/logo.png">
  <link rel="shortcut icon" type="image/png" href="<?= $basePath ?>/assets/images/logo.png">
  
  <!-- Google Fonts: Plus Jakarta Sans, Outfit, DM Mono -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@500;700;800&family=Outfit:wght@400;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="<?= $basePath ?>/assets/css/toast.css">

  <style>
    :root {
      --ink: #0c1a15;
      --dark-navy: #0f172a;
      --coral: #ff5733;
      --coral-hover: #e04422;
      --lime: #d4f82c;
      --lime-hover: #c2e51f;
      --emerald: #10b981;
      --sky: #0284c7;
      --sand: #f8fafc;
      --card-frosted: rgba(255, 255, 255, 0.84);
      --card-border: rgba(255, 255, 255, 0.95);
    }

    *, *::before, *::after {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      font-family: 'Plus Jakarta Sans', sans-serif;
      color: var(--dark-navy);
      min-height: 100vh;
      overflow-x: hidden;
      position: relative;
      background: #0c1a15 url('<?= $basePath ?>/assets/images/pricing-bg.png') no-repeat center top;
      background-size: cover;
      background-attachment: fixed;
    }

    body::before {
      content: "";
      position: fixed;
      inset: 0;
      background: linear-gradient(180deg, 
        rgba(255, 255, 255, 0.65) 0%, 
        rgba(255, 255, 255, 0.45) 30%, 
        rgba(255, 255, 255, 0.55) 70%,
        rgba(255, 255, 255, 0.70) 100%
      );
      pointer-events: none;
      z-index: 0;
    }

    .pricing-page-container {
      position: relative;
      z-index: 1;
      width: 100%;
      max-width: 1240px;
      margin: 0 auto;
      padding: 106px 20px 80px;
      display: flex;
      flex-direction: column;
    }

    /* ── 1. Hero Header ─────────────────────────────────────────────────────── */
    .pricing-hero {
      text-align: center;
      max-width: 1060px;
      margin: 0 auto 20px;
      position: relative;
      padding: 16px 20px 10px;
    }

    .pricing-hero::before {
      content: "";
      position: absolute;
      inset: -20px -30px;
      background: radial-gradient(ellipse 75% 70% at 50% 50%, rgba(255, 255, 255, 0.94) 0%, rgba(255, 255, 255, 0.72) 55%, rgba(255, 255, 255, 0) 85%);
      border-radius: 40px;
      filter: blur(14px);
      z-index: -1;
      pointer-events: none;
    }

    .pricing-eyebrow {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: rgba(224, 242, 254, 0.95);
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
      border: 1.5px solid rgba(186, 230, 253, 0.95);
      border-radius: 9999px;
      padding: 6px 18px;
      font-family: 'DM Mono', monospace;
      font-size: 0.76rem;
      font-weight: 800;
      color: #0369a1;
      letter-spacing: 0.12em;
      text-transform: uppercase;
      box-shadow: 0 2px 10px rgba(2, 132, 199, 0.15);
      margin-bottom: 12px;
    }

    .pricing-hero h1 {
      font-family: 'Outfit', sans-serif;
      font-size: clamp(2.3rem, 4.4vw, 3.7rem);
      font-weight: 900;
      letter-spacing: -0.03em;
      text-transform: uppercase;
      line-height: 1.1;
      margin-bottom: 12px;
      text-shadow: 0 2px 24px rgba(255, 255, 255, 0.95), 0 1px 4px rgba(255, 255, 255, 0.8);
    }

    .pricing-hero h1 .title-dark {
      color: var(--dark-navy);
    }

    .pricing-hero h1 .title-coral {
      color: var(--coral);
    }

    .pricing-hero p {
      font-size: 1.05rem;
      font-weight: 700;
      color: #0f172a;
      max-width: 680px;
      margin: 0 auto;
      line-height: 1.5;
      text-shadow: 0 1px 14px rgba(255, 255, 255, 0.98), 0 0 4px #ffffff;
    }

    /* ── 2. Billing Toggle Pill ────────────────────────────────────────────── */
    .billing-toggle-wrap {
      display: flex;
      justify-content: center;
      margin-bottom: 34px;
    }

    .billing-toggle-pill {
      display: inline-flex;
      align-items: center;
      gap: 12px;
      background: rgba(255, 255, 255, 0.88);
      backdrop-filter: blur(14px);
      -webkit-backdrop-filter: blur(14px);
      border: 1.5px solid rgba(255, 255, 255, 0.95);
      border-radius: 9999px;
      padding: 6px 16px;
      box-shadow: 0 10px 25px -6px rgba(15, 23, 42, 0.12);
    }

    .billing-toggle-label {
      font-family: 'DM Mono', monospace;
      font-size: 0.82rem;
      font-weight: 800;
      color: var(--dark-navy);
      letter-spacing: 0.04em;
      text-transform: uppercase;
      cursor: pointer;
      user-select: none;
      transition: opacity 0.2s;
    }

    .billing-toggle-label.inactive {
      opacity: 0.45;
    }

    .toggle-switch {
      position: relative;
      width: 48px;
      height: 26px;
      cursor: pointer;
      display: inline-block;
    }

    .toggle-switch input {
      display: none;
    }

    .toggle-track {
      position: absolute;
      inset: 0;
      background: #0c1a15;
      border-radius: 999px;
      transition: background 0.2s;
    }

    .toggle-thumb {
      position: absolute;
      top: 3px;
      left: 3px;
      width: 20px;
      height: 20px;
      background: var(--lime);
      border-radius: 50%;
      transition: transform 0.25s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }

    .toggle-switch input:checked + .toggle-track .toggle-thumb {
      transform: translateX(22px);
    }

    .yearly-badge {
      background: #ffe4e6;
      color: var(--coral);
      border: 1px solid #fecdd3;
      border-radius: 999px;
      padding: 3px 8px;
      font-family: 'DM Mono', monospace;
      font-size: 0.68rem;
      font-weight: 800;
      letter-spacing: 0.02em;
      vertical-align: middle;
      margin-left: 2px;
    }

    /* ── 3. Plans Grid ─────────────────────────────────────────────────────── */
    .plans-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 24px;
      align-items: stretch;
      margin-bottom: 36px;
    }

    .plans-loading {
      grid-column: 1 / -1;
      text-align: center;
      padding: 60px 20px;
      font-family: 'DM Mono', monospace;
      font-weight: 800;
      color: #334155;
      background: rgba(255, 255, 255, 0.85);
      border-radius: 24px;
      backdrop-filter: blur(14px);
    }

    /* Plan Card Common */
    .plan-card {
      position: relative;
      border-radius: 28px;
      padding: 32px 28px 28px;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      backdrop-filter: blur(18px);
      -webkit-backdrop-filter: blur(18px);
      border: 1.5px solid var(--card-border);
      box-shadow: 0 20px 45px -12px rgba(15, 23, 42, 0.15);
      transition: transform 0.25s ease, box-shadow 0.25s ease;
    }

    .plan-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 26px 55px -10px rgba(15, 23, 42, 0.2);
    }

    /* Normal Card (Starter & Enterprise) */
    .plan-card.plan-white {
      background: rgba(255, 255, 255, 0.94);
      backdrop-filter: blur(24px);
      -webkit-backdrop-filter: blur(24px);
      border: 1.5px solid rgba(255, 255, 255, 0.98);
      box-shadow: 0 20px 45px -10px rgba(15, 23, 42, 0.16), 0 2px 8px rgba(0, 0, 0, 0.04);
    }

    /* Featured Pro Card (Lime) */
    .plan-card.plan-featured {
      background: #d4f82c;
      border: 2px solid #ffffff;
      box-shadow: 0 24px 50px -10px rgba(212, 248, 44, 0.55), 0 10px 25px rgba(0, 0, 0, 0.12);
    }

    .plan-card.plan-featured:hover {
      box-shadow: 0 30px 60px -10px rgba(212, 248, 44, 0.7), 0 14px 32px rgba(0, 0, 0, 0.16);
    }

    /* Top Badges Row */
    .plan-top-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
      margin-bottom: 18px;
    }

    .plan-pill-tag {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 5px 12px;
      border-radius: 9999px;
      font-family: 'DM Mono', monospace;
      font-size: 0.74rem;
      font-weight: 800;
      letter-spacing: 0.04em;
      text-transform: uppercase;
    }

    .plan-pill-tag.sky {
      background: #e0f2fe;
      color: #0284c7;
      border: 1px solid #bae6fd;
    }

    .plan-pill-tag.coral {
      background: var(--coral);
      color: #ffffff;
      box-shadow: 0 3px 10px rgba(255, 87, 51, 0.35);
    }

    .plan-pill-tag.dark {
      background: #0c1a15;
      color: var(--lime);
      box-shadow: 0 3px 8px rgba(0, 0, 0, 0.18);
    }

    .plan-pill-tag.dark-white {
      background: #0c1a15;
      color: #ffffff;
      box-shadow: 0 3px 8px rgba(0, 0, 0, 0.18);
    }

    .plan-pill-tag.peach {
      background: #ffedd5;
      color: #ea580c;
      border: 1px solid #fed7aa;
    }

    /* Price Section */
    .plan-price-wrap {
      display: flex;
      align-items: baseline;
      gap: 6px;
      margin-bottom: 8px;
      font-family: 'Outfit', sans-serif;
    }

    .price-currency {
      font-size: 1.3rem;
      font-weight: 900;
      color: var(--dark-navy);
    }

    .price-val {
      font-size: 2.8rem;
      font-weight: 900;
      line-height: 1;
      color: var(--dark-navy);
      letter-spacing: -0.03em;
    }

    .price-cycle {
      font-family: 'Plus Jakarta Sans', sans-serif;
      font-size: 0.94rem;
      font-weight: 700;
      color: #475569;
    }

    .plan-card.plan-featured .price-currency,
    .plan-card.plan-featured .price-val {
      color: #0c1a15;
    }

    .plan-card.plan-featured .price-cycle {
      color: #1e293b;
    }

    .plan-yearly-subtext {
      font-family: 'DM Mono', monospace;
      font-size: 0.74rem;
      font-weight: 800;
      color: #334155;
      margin-top: -4px;
      margin-bottom: 10px;
    }

    .plan-card.plan-featured .plan-yearly-subtext {
      color: #1a3d34;
    }

    /* Description */
    .plan-desc {
      font-size: 0.92rem;
      line-height: 1.45;
      color: #1e293b;
      margin-bottom: 18px;
      font-weight: 600;
      min-height: 42px;
    }

    .plan-card.plan-featured .plan-desc {
      color: #0c1a15;
      font-weight: 700;
    }

    /* Specs Capsule */
    .plan-specs-box {
      background: rgba(241, 245, 249, 0.9);
      border: 1.5px solid rgba(203, 213, 225, 0.8);
      border-radius: 16px;
      padding: 12px 8px;
      display: grid;
      grid-template-columns: 1fr 1fr 1fr;
      gap: 6px;
      text-align: center;
      margin-bottom: 22px;
    }

    .plan-card.plan-featured .plan-specs-box {
      background: rgba(255, 255, 255, 0.55);
      border: 1.5px solid rgba(0, 0, 0, 0.12);
    }

    .spec-item {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 2px;
    }

    .spec-num {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      font-family: 'Outfit', sans-serif;
      font-size: 1.2rem;
      font-weight: 900;
      color: var(--dark-navy);
      line-height: 1.1;
    }

    .spec-num i {
      font-size: 0.95rem;
      color: var(--coral);
    }

    .plan-card.plan-featured .spec-num {
      color: #0c1a15;
    }

    .plan-card.plan-featured .spec-num i {
      color: #0c1a15;
    }

    .spec-lbl {
      font-family: 'DM Mono', monospace;
      font-size: 0.68rem;
      font-weight: 800;
      color: #334155;
      letter-spacing: 0.04em;
      text-transform: uppercase;
    }

    .plan-card.plan-featured .spec-lbl {
      color: #0c1a15;
    }

    /* Features List */
    .plan-features-list {
      list-style: none;
      padding: 0;
      margin: 0 0 24px;
      flex: 1;
      display: flex;
      flex-direction: column;
      gap: 11px;
    }

    .plan-features-list li {
      display: flex;
      align-items: flex-start;
      gap: 10px;
      font-size: 0.88rem;
      line-height: 1.4;
      color: #0f172a;
      font-weight: 600;
    }

    .plan-features-list li i {
      color: #10b981;
      font-size: 1.05rem;
      flex-shrink: 0;
      margin-top: 1px;
    }

    .plan-card.plan-featured .plan-features-list li {
      color: #0c1a15;
      font-weight: 700;
    }

    .plan-card.plan-featured .plan-features-list li i {
      color: #0c1a15;
    }

    /* CTA Buttons */
    .btn-plan-cta {
      width: 100%;
      padding: 13px 20px;
      border-radius: 9999px;
      font-family: 'Outfit', sans-serif;
      font-weight: 800;
      font-size: 0.94rem;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
      transition: all 0.2s ease;
      cursor: pointer;
    }

    .btn-plan-cta.light {
      background: #ffffff;
      color: var(--dark-navy);
      border: 1.5px solid var(--dark-navy);
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
    }

    .btn-plan-cta.light:hover {
      background: #f1f5f9;
      transform: translateY(-2px);
      box-shadow: 0 6px 18px rgba(0, 0, 0, 0.1);
    }

    .btn-plan-cta.dark {
      background: #0c1a15;
      color: #ffffff;
      border: none;
      box-shadow: 0 6px 20px rgba(12, 26, 21, 0.35);
    }

    .btn-plan-cta.dark:hover {
      background: #1e293b;
      transform: translateY(-2px);
      box-shadow: 0 8px 24px rgba(12, 26, 21, 0.45);
    }

    /* ── 4. Trust Strip ─────────────────────────────────────────────────────── */
    .pricing-trust-strip {
      display: flex;
      align-items: center;
      justify-content: center;
      flex-wrap: wrap;
      gap: 14px;
      margin-bottom: 30px;
    }

    .trust-pill {
      display: inline-flex;
      align-items: center;
      gap: 7px;
      background: rgba(255, 255, 255, 0.88);
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
      border: 1.5px solid rgba(255, 255, 255, 0.95);
      border-radius: 9999px;
      padding: 6px 16px;
      font-family: 'DM Mono', monospace;
      font-size: 0.78rem;
      font-weight: 800;
      color: var(--dark-navy);
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
    }

    .trust-pill i {
      color: var(--coral);
      font-size: 0.9rem;
    }

    /* ── 5. FAQ Section ─────────────────────────────────────────────────────── */
    .pricing-faq-section {
      background: rgba(255, 255, 255, 0.85);
      backdrop-filter: blur(18px);
      -webkit-backdrop-filter: blur(18px);
      border: 1.5px solid rgba(255, 255, 255, 0.95);
      border-radius: 28px;
      box-shadow: 0 20px 45px -12px rgba(15, 23, 42, 0.08);
      padding: 32px 36px;
      max-width: 900px;
      margin: 0 auto;
    }

    .pricing-faq-title {
      font-family: 'Outfit', sans-serif;
      font-size: 1.25rem;
      font-weight: 900;
      text-transform: uppercase;
      margin-bottom: 20px;
      color: var(--dark-navy);
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .pricing-faq-item {
      border-bottom: 1px solid rgba(0, 0, 0, 0.08);
      padding: 14px 0;
    }

    .pricing-faq-item:last-child {
      border-bottom: none;
      padding-bottom: 0;
    }

    .pricing-faq-q {
      font-family: 'Outfit', sans-serif;
      font-weight: 800;
      font-size: 0.96rem;
      color: var(--dark-navy);
      margin-bottom: 4px;
    }

    .pricing-faq-a {
      font-size: 0.86rem;
      color: #475569;
      line-height: 1.5;
    }

    /* Responsive */
    @media (max-width: 1024px) {
      .plans-grid {
        grid-template-columns: repeat(auto-fit, minmax(310px, 1fr));
      }
    }

    @media (max-width: 768px) {
      .pricing-page-container {
        padding: 94px 14px 60px;
      }
      .pricing-hero h1 {
        font-size: 2.1rem;
      }
      .plans-grid {
        grid-template-columns: 1fr;
        gap: 20px;
      }
      .plan-card {
        padding: 24px 20px;
      }
      .pricing-faq-section {
        padding: 24px 20px;
      }
    }
  </style>
</head>
<body>

  <!-- GLOBAL REUSABLE HEADER -->
  <?php require_once __DIR__ . '/../includes/header.php'; ?>

  <!-- MAIN PRICING CONTENT -->
  <main class="pricing-page-container">

    <!-- 1. HERO TITLE -->
    <section class="pricing-hero">
      <div class="pricing-eyebrow">
        TRANSPARENT &bull; SAAS &bull; TIERS
      </div>
      <h1>
        <span class="title-dark">OWNER</span>
        <span class="title-coral">SUBSCRIPTION PLANS</span>
      </h1>
      <p>Everything you need to run your pickleball facility — from solo courts to multi-venue operations.</p>
    </section>

    <!-- 2. BILLING TOGGLE -->
    <section class="billing-toggle-wrap">
      <div class="billing-toggle-pill">
        <span class="billing-toggle-label" id="label-monthly" onclick="setBilling(false)">MONTHLY</span>
        <label class="toggle-switch">
          <input type="checkbox" id="billing-toggle">
          <div class="toggle-track"><div class="toggle-thumb"></div></div>
        </label>
        <span class="billing-toggle-label inactive" id="label-yearly" onclick="setBilling(true)">
          YEARLY <span class="yearly-badge">SAVE 20%</span>
        </span>
      </div>
    </section>

    <!-- 3. PLANS GRID -->
    <section class="plans-grid" id="plans-grid">
      <div class="plans-loading">
        <i class="bi bi-hourglass-split" style="font-size:1.8rem; display:block; margin-bottom:10px;"></i>
        Loading subscription plans...
      </div>
    </section>

    <!-- 4. TRUST & GUARANTEE STRIP -->
    <section class="pricing-trust-strip">
      <div class="trust-pill">
        <i class="bi bi-shield-check"></i> Zero Lock-in Contract
      </div>
      <div class="trust-pill">
        <i class="bi bi-lightning-charge-fill"></i> Instant Court Activation
      </div>
      <div class="trust-pill">
        <i class="bi bi-arrow-repeat"></i> Upgrade or Switch Anytime
      </div>
    </section>

    <!-- 5. FAQ SECTION -->
    <section class="pricing-faq-section">
      <h3 class="pricing-faq-title">
        <i class="bi bi-question-circle-fill" style="color:var(--coral);"></i> Frequently Asked Questions
      </h3>
      <div class="pricing-faq-item">
        <div class="pricing-faq-q">How does the 14-Day Free Trial work?</div>
        <div class="pricing-faq-a">Court owners can test Pikvero free for 14 days without entering credit card details. Add courts, test booking slots, and invite players immediately.</div>
      </div>
      <div class="pricing-faq-item">
        <div class="pricing-faq-q">Can I switch plans or cancel later?</div>
        <div class="pricing-faq-a">Yes! You can upgrade, downgrade, or cancel your subscription at any time directly from your Court Owner Dashboard.</div>
      </div>
      <div class="pricing-faq-item">
        <div class="pricing-faq-q">Are customer booking payments supported?</div>
        <div class="pricing-faq-a">Yes, all tiers include support for GCash, Maya, and cash at desk payments with zero platform commission fee.</div>
      </div>
    </section>

  </main>

  <!-- GLOBAL DESKTOP FOOTER -->
  <?php require_once __DIR__ . '/../includes/footer.php'; ?>

  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="<?= $basePath ?>/assets/js/core/toast.js"></script>
  <script>
    window.APP_BASE_PATH = <?= json_encode($basePath) ?>;
    let allPlans = [];
    let isYearly = false;

    // Hardcoded fallback data matching reference design in case API is offline
    const fallbackPlans = [
      {
        id: 1,
        name: "Starter Plan",
        slug: "starter",
        monthly_price: 0,
        yearly_price: 0,
        is_free_trial: 1,
        max_facilities: 1,
        max_courts: 5,
        max_staff: 3,
        description: "Ideal for boutique single-venue court owners.",
        features: [
          "1 Facility Location",
          "Up to 2 Courts",
          "Basic Booking Engine & Calendar",
          "Real-Time Double Booking Protection",
          "System and Revenue Analytics"
        ]
      },
      {
        id: 2,
        name: "Pro Plan",
        slug: "pro",
        monthly_price: 999,
        yearly_price: 9590,
        is_free_trial: 0,
        max_facilities: 5,
        max_courts: 50,
        max_staff: 15,
        description: "Designed for growing multi-court clubs & venues.",
        features: [
          "Up to 2 Facility Locations",
          "Up to 10 Courts",
          "Advanced Analytics & Revenue Reports",
          "Staff Management & RBAC Controls",
          "Custom Promotions & Membership Packages"
        ]
      },
      {
        id: 3,
        name: "Enterprise Plan",
        slug: "enterprise",
        monthly_price: 2499,
        yearly_price: 23990,
        is_free_trial: 0,
        max_facilities: 99,
        max_courts: 999,
        max_staff: 99,
        description: "Unlimited court networks with dedicated priority support.",
        features: [
          "Unlimited Facility Locations",
          "Unlimited Courts & Networks",
          "Multi-tenant Organization Control",
          "Custom Domain & Brand Whitelabeling",
          "Dedicated Account Manager"
        ]
      }
    ];

    document.addEventListener('DOMContentLoaded', async () => {
      const toggle = document.getElementById('billing-toggle');
      const labelMonthly = document.getElementById('label-monthly');
      const labelYearly = document.getElementById('label-yearly');

      if (toggle) {
        toggle.addEventListener('change', () => {
          isYearly = toggle.checked;
          if (labelMonthly) labelMonthly.classList.toggle('inactive', isYearly);
          if (labelYearly) labelYearly.classList.toggle('inactive', !isYearly);
          renderPlans();
        });
      }

      await loadPlans();
    });

    function setBilling(yearly) {
      const toggle = document.getElementById('billing-toggle');
      if (toggle && toggle.checked !== yearly) {
        toggle.checked = yearly;
        toggle.dispatchEvent(new Event('change'));
      }
    }

    async function loadPlans() {
      const base = window.APP_BASE_PATH || '';
      try {
        const res = await $.getJSON(base + '/api/subscription-plans.php');
        if (res && res.success && Array.isArray(res.data) && res.data.length > 0) {
          allPlans = res.data;
        } else {
          allPlans = fallbackPlans;
        }
      } catch (e) {
        allPlans = fallbackPlans;
      }
      renderPlans();
    }

    function renderPlans() {
      const grid = document.getElementById('plans-grid');
      if (!grid) return;

      const plansToRender = (allPlans && allPlans.length) ? allPlans : fallbackPlans;
      const base = window.APP_BASE_PATH || '';

      grid.innerHTML = plansToRender.map((plan, i) => {
        const isFree = Boolean(plan.is_free_trial);
        const isPro = plan.slug === 'pro' || plan.name.toLowerCase().includes('pro');
        const isEnterprise = plan.slug === 'enterprise' || plan.name.toLowerCase().includes('enterprise');

        // Dynamic pricing calculation
        let priceHtml = '';
        let yearlySubtext = '';

        if (isFree) {
          priceHtml = `
            <div class="plan-price-wrap">
              <span class="price-currency">₱0</span>
              <strong class="price-val">FREE</strong>
              <span class="price-cycle">/mo</span>
            </div>
          `;
        } else {
          let calculatedPrice = plan.monthly_price;
          if (isYearly) {
            // Apply 20% discount
            calculatedPrice = Math.round(plan.monthly_price * 0.8);
            const totalYearly = calculatedPrice * 12;
            yearlySubtext = `<div class="plan-yearly-subtext">Billed ₱${totalYearly.toLocaleString('en-PH')}/yr (Save 20%)</div>`;
          }
          priceHtml = `
            <div class="plan-price-wrap">
              <strong class="price-val">₱${calculatedPrice.toLocaleString('en-PH')}</strong>
              <span class="price-cycle">/mo</span>
            </div>
            ${yearlySubtext}
          `;
        }

        // Top Badges
        let topBadgeHtml = '';
        if (isFree) {
          topBadgeHtml = `
            <div class="plan-top-row">
              <span class="plan-pill-tag sky">STARTER PLAN</span>
              <span class="plan-pill-tag coral"><i class="bi bi-gift-fill"></i> FREE TRIAL</span>
            </div>
          `;
        } else if (isPro) {
          topBadgeHtml = `
            <div class="plan-top-row">
              <span class="plan-pill-tag dark">PRO PLAN</span>
              <span class="plan-pill-tag dark-white"><i class="bi bi-crown-fill" style="color:#facc15;"></i> MOST POPULAR</span>
            </div>
          `;
        } else {
          topBadgeHtml = `
            <div class="plan-top-row">
              <span class="plan-pill-tag peach">ENTERPRISE PLAN</span>
            </div>
          `;
        }

        // Specs
        const maxFac = plan.max_facilities >= 99 ? '99' : plan.max_facilities;
        const facLabel = maxFac === 1 ? 'FACILITY' : 'FACILITIES';
        const maxCourt = plan.max_courts >= 999 ? '999' : plan.max_courts;
        const maxStaff = plan.max_staff >= 99 ? '99' : plan.max_staff;

        const specsHtml = `
          <div class="plan-specs-box">
            <div class="spec-item">
              <div class="spec-num"><i class="bi bi-building"></i> ${maxFac}</div>
              <div class="spec-lbl">${facLabel}</div>
            </div>
            <div class="spec-item">
              <div class="spec-num"><i class="bi bi-grid-fill"></i> ${maxCourt}</div>
              <div class="spec-lbl">COURTS</div>
            </div>
            <div class="spec-item">
              <div class="spec-num"><i class="bi bi-people-fill"></i> ${maxStaff}</div>
              <div class="spec-lbl">STAFF</div>
            </div>
          </div>
        `;

        // Features list (take top 5 features matching visual design)
        const features = (plan.features && plan.features.length) ? plan.features.slice(0, 5) : [
          "1 Facility Location",
          "Up to 2 Courts",
          "Basic Booking Engine & Calendar",
          "Real-Time Double Booking Protection",
          "System and Revenue Analytics"
        ];

        const featuresHtml = `
          <ul class="plan-features-list">
            ${features.map(f => `<li><i class="bi bi-check-circle-fill"></i> <span>${escapeHtml(f)}</span></li>`).join('')}
          </ul>
        `;

        // CTA Button
        let ctaBtnHtml = '';
        const registerUrl = `${base}/public/register?type=owner&plan=${encodeURIComponent(plan.slug || '')}`;

        if (isFree) {
          ctaBtnHtml = `<a href="${registerUrl}" class="btn-plan-cta light">Get Started Free <i class="bi bi-chevron-right"></i></a>`;
        } else if (isPro) {
          ctaBtnHtml = `<a href="${registerUrl}" class="btn-plan-cta dark">Start Pro Plan <i class="bi bi-chevron-right"></i></a>`;
        } else {
          ctaBtnHtml = `<a href="${registerUrl}" class="btn-plan-cta light">Contact Sales <i class="bi bi-chevron-right"></i></a>`;
        }

        const cardCls = isPro ? 'plan-card plan-featured' : 'plan-card plan-white';

        return `
          <div class="${cardCls}">
            <div>
              ${topBadgeHtml}
              ${priceHtml}
              <p class="plan-desc">${escapeHtml(plan.description || '')}</p>
              ${specsHtml}
              ${featuresHtml}
            </div>
            <div>
              ${ctaBtnHtml}
            </div>
          </div>
        `;
      }).join('');
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
