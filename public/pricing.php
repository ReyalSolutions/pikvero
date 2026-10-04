<?php require_once __DIR__ . '/../app/bootstrap.php'; ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
  <title>Pikvero — Platform Subscriptions &amp; Pricing</title>
  <meta name="description" content="Transparent subscription plans for Pikvero court owners. Choose a plan that fits your facility.">
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
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }

    .pricing-container {
      flex: 1;
      padding: 105px max(4vw, 20px) 50px;
      max-width: 1120px;
      margin: 0 auto;
      width: 100%;
    }

    /* Hero Header */
    .pricing-hero {
      text-align: center;
      max-width: 680px;
      margin: 0 auto 12px;
    }
    .pricing-hero h1 {
      font-size: clamp(1.8rem, 4.5vw, 3rem);
      font-weight: 900;
      text-transform: uppercase;
      margin: 6px 0 6px;
      letter-spacing: -0.03em;
      line-height: 1.1;
      color: var(--ink);
    }
    .pricing-hero p {
      font-size: 0.92rem;
      color: #3d524a;
      margin: 0 auto;
      line-height: 1.45;
      max-width: 540px;
    }

    /* Billing Toggle Pill */
    .billing-toggle-pill {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 12px;
      background: var(--sand);
      border: 2px solid var(--ink);
      border-radius: 999px;
      padding: 5px 14px;
      box-shadow: 3px 3px 0 var(--ink);
      margin: 18px auto 26px;
    }
    .billing-toggle-wrap {
      text-align: center;
    }
    .billing-toggle-label {
      font-family: "DM Mono", monospace;
      font-weight: 800;
      font-size: 0.82rem;
      text-transform: uppercase;
      color: var(--ink);
      cursor: pointer;
      user-select: none;
      transition: opacity 0.2s;
    }
    .billing-toggle-label.inactive {
      opacity: 0.4;
    }
    .toggle-switch {
      position: relative;
      width: 48px;
      height: 26px;
      cursor: pointer;
      display: inline-block;
      vertical-align: middle;
    }
    .toggle-switch input { display: none; }
    .toggle-track {
      position: absolute;
      inset: 0;
      background: var(--ink);
      border-radius: 13px;
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
      transition: transform 0.2s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }
    .toggle-switch input:checked + .toggle-track .toggle-thumb {
      transform: translateX(22px);
    }
    .yearly-badge {
      background: var(--coral);
      color: var(--white);
      font-family: "DM Mono", monospace;
      font-size: 0.62rem;
      font-weight: 800;
      padding: 2px 7px;
      border-radius: 999px;
      border: 1.5px solid var(--ink);
      display: inline-block;
      vertical-align: middle;
      margin-left: 2px;
      letter-spacing: 0.02em;
    }

    /* Plans Grid */
    #plans-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(290px, 1fr));
      gap: 20px;
      align-items: stretch;
      margin-bottom: 28px;
    }

    .plans-loading {
      grid-column: 1 / -1;
      text-align: center;
      padding: 50px 20px;
      font-family: "DM Mono", monospace;
      font-weight: 800;
      color: #4c635a;
    }

    /* Plan Card */
    .plan-card {
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      padding: 24px 20px 20px;
      background: var(--white);
      border: 2.5px solid var(--ink);
      border-radius: 16px;
      box-shadow: 4px 4px 0 var(--ink);
      transition: transform 0.18s ease, box-shadow 0.18s ease;
      position: relative;
      overflow: hidden;
    }
    .plan-card:hover {
      transform: translateY(-3px);
      box-shadow: 6px 6px 0 var(--ink);
    }
    .plan-card.featured {
      background: var(--lime);
      box-shadow: 5px 5px 0 var(--ink);
    }

    .plan-card-ribbon {
      position: absolute;
      top: 12px;
      right: -6px;
      color: #fff;
      font-family: "DM Mono", monospace;
      font-size: 0.64rem;
      font-weight: 800;
      padding: 3px 10px;
      border-radius: 4px 0 0 4px;
      border: 1.5px solid var(--ink);
      box-shadow: 2px 2px 0 var(--ink);
      display: inline-flex;
      align-items: center;
      gap: 4px;
      text-transform: uppercase;
      letter-spacing: 0.03em;
    }
    .plan-card-ribbon.coral { background: var(--coral); }
    .plan-card-ribbon.dark { background: var(--ink); }

    .plan-header {
      margin-bottom: 8px;
    }
    .plan-price {
      font-size: 2.2rem;
      font-weight: 900;
      margin: 10px 0 2px;
      line-height: 1;
      color: var(--ink);
      letter-spacing: -0.02em;
    }
    .plan-price span {
      font-size: 0.88rem;
      font-weight: 700;
      color: #3b5048;
    }
    .plan-desc {
      font-size: 0.82rem;
      color: #3b4e48;
      margin-bottom: 12px;
      line-height: 1.4;
      min-height: 38px;
    }
    .plan-card.featured .plan-desc {
      color: #1a3d34;
    }

    /* Specs mini-grid */
    .plan-specs {
      display: grid;
      grid-template-columns: 1fr 1fr 1fr;
      gap: 6px;
      background: rgba(0, 0, 0, 0.05);
      border: 1px solid rgba(0, 0, 0, 0.08);
      border-radius: 10px;
      padding: 8px 6px;
      margin-bottom: 14px;
      text-align: center;
    }
    .plan-spec-item {
      font-family: "DM Mono", monospace;
      font-size: 0.68rem;
      font-weight: 800;
      color: var(--ink);
    }
    .plan-spec-item strong {
      display: block;
      font-size: 1.05rem;
      font-weight: 900;
      margin-bottom: 1px;
    }

    /* Features checklist */
    .plan-features {
      list-style: none;
      padding: 0;
      margin: 0 0 16px;
      flex: 1;
    }
    .plan-features li {
      display: flex;
      align-items: flex-start;
      gap: 8px;
      font-size: 0.82rem;
      padding: 4px 0;
      border-bottom: 1px solid rgba(0, 0, 0, 0.06);
      line-height: 1.35;
      color: var(--ink);
    }
    .plan-features li:last-child {
      border-bottom: none;
    }
    .plan-features li i {
      color: #137547;
      margin-top: 1px;
      flex-shrink: 0;
      font-size: 0.90rem;
    }
    .plan-card.featured .plan-features li i {
      color: #0c4a2c;
    }

    .plan-toggle-features-btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 5px;
      width: 100%;
      background: rgba(0, 0, 0, 0.04);
      border: 1px dashed var(--ink);
      border-radius: 6px;
      padding: 5px 8px;
      font-family: 'DM Mono', monospace;
      font-size: 0.72rem;
      font-weight: 800;
      color: var(--ink);
      cursor: pointer;
      margin-top: 6px;
      margin-bottom: 12px;
      transition: background 0.15s ease;
    }
    .plan-toggle-features-btn:hover {
      background: rgba(0, 0, 0, 0.08);
    }

    .plan-cta {
      width: 100%;
      padding: 10px 14px;
      font-size: 0.85rem;
      font-weight: 800;
      text-align: center;
      margin-top: auto;
      border-radius: 10px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
      text-decoration: none;
    }

    /* Trust & Guarantee Strip */
    .pricing-trust-strip {
      display: flex;
      align-items: center;
      justify-content: center;
      flex-wrap: wrap;
      gap: 12px;
      margin-top: 10px;
      margin-bottom: 24px;
    }
    .pricing-trust-item {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      font-family: 'DM Mono', monospace;
      font-size: 0.76rem;
      font-weight: 800;
      color: #2b3d36;
      background: var(--sand);
      border: 1.5px solid var(--ink);
      border-radius: 999px;
      padding: 4px 12px;
      box-shadow: 1.5px 1.5px 0 var(--ink);
    }
    .pricing-trust-item i {
      color: var(--coral);
    }

    /* FAQ Box */
    .pricing-faq-section {
      background: var(--white);
      border: 2px solid var(--ink);
      border-radius: 14px;
      box-shadow: 3.5px 3.5px 0 var(--ink);
      padding: 18px 20px;
      max-width: 800px;
      margin: 20px auto 10px;
    }
    .pricing-faq-title {
      font-size: 1.05rem;
      font-weight: 900;
      text-transform: uppercase;
      margin: 0 0 12px;
      color: var(--ink);
      display: flex;
      align-items: center;
      gap: 6px;
    }
    .pricing-faq-item {
      border-bottom: 1.5px dashed #cbd5d1;
      padding: 10px 0;
    }
    .pricing-faq-item:last-child {
      border-bottom: none;
      padding-bottom: 0;
    }
    .pricing-faq-q {
      font-weight: 800;
      font-size: 0.85rem;
      color: var(--ink);
      margin-bottom: 3px;
    }
    .pricing-faq-a {
      font-size: 0.78rem;
      color: #4a5c56;
      line-height: 1.4;
      margin: 0;
    }

    /* Mobile Responsive Optimizations */
    @media (max-width: 768px) {
      .pricing-container {
        padding: 78px 10px 24px !important;
      }

      /* Compact Hero */
      .pricing-hero {
        margin-bottom: 6px !important;
      }
      .pricing-hero .eyebrow {
        font-size: 0.65rem !important;
        padding: 2px 8px !important;
        margin-bottom: 2px !important;
      }
      .pricing-hero h1 {
        font-size: 1.38rem !important;
        margin: 2px 0 4px !important;
        letter-spacing: -0.02em !important;
        line-height: 1.15 !important;
      }
      .pricing-hero p {
        font-size: 0.76rem !important;
        line-height: 1.32 !important;
        max-width: 320px !important;
        color: #3b5048 !important;
      }

      /* Compact Toggle */
      .billing-toggle-pill {
        padding: 3px 10px !important;
        margin: 8px auto 14px !important;
        gap: 8px !important;
        box-shadow: 2px 2px 0 var(--ink) !important;
        border-width: 1.5px !important;
      }
      .billing-toggle-label {
        font-size: 0.72rem !important;
      }
      .toggle-switch {
        width: 42px !important;
        height: 23px !important;
      }
      .toggle-thumb {
        width: 17px !important;
        height: 17px !important;
      }
      .toggle-switch input:checked + .toggle-track .toggle-thumb {
        transform: translateX(19px) !important;
      }
      .yearly-badge {
        font-size: 0.56rem !important;
        padding: 1px 5px !important;
        border-width: 1px !important;
      }

      /* Compact Grid & Cards */
      #plans-grid {
        grid-template-columns: 1fr !important;
        gap: 12px !important;
        margin-bottom: 16px !important;
      }
      .plan-card {
        padding: 13px 12px 12px !important;
        border-radius: 12px !important;
        border-width: 2px !important;
        box-shadow: 2.5px 2.5px 0 var(--ink) !important;
      }
      .plan-card.featured {
        box-shadow: 3px 3px 0 var(--ink) !important;
      }
      .plan-card-ribbon {
        top: 9px !important;
        right: -4px !important;
        font-size: 0.58rem !important;
        padding: 2px 7px !important;
        border-width: 1px !important;
        box-shadow: 1.5px 1.5px 0 var(--ink) !important;
      }
      .plan-header .badge-streetside {
        font-size: 0.62rem !important;
        padding: 2px 7px !important;
      }
      .plan-price {
        font-size: 1.68rem !important;
        margin: 5px 0 1px !important;
      }
      .plan-price span {
        font-size: 0.78rem !important;
      }
      .plan-desc {
        font-size: 0.74rem !important;
        line-height: 1.28 !important;
        min-height: 0 !important;
        margin-bottom: 7px !important;
      }
      .plan-specs {
        padding: 5px 4px !important;
        margin-bottom: 8px !important;
        border-radius: 7px !important;
        gap: 4px !important;
      }
      .plan-spec-item {
        font-size: 0.60rem !important;
      }
      .plan-spec-item strong {
        font-size: 0.90rem !important;
      }

      /* Features list on mobile */
      .plan-features {
        margin: 0 0 6px !important;
      }
      .plan-features li {
        font-size: 0.74rem !important;
        padding: 3px 0 !important;
        line-height: 1.25 !important;
        gap: 5px !important;
      }
      .plan-features li i {
        font-size: 0.78rem !important;
      }
      .plan-toggle-features-btn {
        font-size: 0.68rem !important;
        padding: 4px 6px !important;
        margin-top: 4px !important;
        margin-bottom: 8px !important;
      }

      .plan-cta {
        padding: 8px 12px !important;
        font-size: 0.78rem !important;
        border-radius: 8px !important;
      }

      /* Trust strip */
      .pricing-trust-strip {
        gap: 6px !important;
        margin-top: 6px !important;
        margin-bottom: 16px !important;
      }
      .pricing-trust-item {
        font-size: 0.68rem !important;
        padding: 3px 9px !important;
        box-shadow: 1px 1px 0 var(--ink) !important;
      }

      /* FAQ section */
      .pricing-faq-section {
        padding: 12px 12px !important;
        border-radius: 11px !important;
        border-width: 1.5px !important;
        box-shadow: 2px 2px 0 var(--ink) !important;
        margin-top: 10px !important;
      }
      .pricing-faq-title {
        font-size: 0.90rem !important;
        margin-bottom: 8px !important;
      }
      .pricing-faq-item {
        padding: 7px 0 !important;
      }
      .pricing-faq-q {
        font-size: 0.76rem !important;
      }
      .pricing-faq-a {
        font-size: 0.70rem !important;
        line-height: 1.3 !important;
      }
    }
  </style>
</head>
<body>

  <?php require_once __DIR__ . '/../includes/header.php'; ?>

  <div class="pricing-container">

    <!-- 1. HERO TITLE -->
    <div class="pricing-hero">
      <span class="eyebrow" style="background:var(--sand);">
        <span class="pulse-dot"></span> TRANSPARENT SAAS TIERS
      </span>
      <h1>OWNER SUBSCRIPTION PLANS</h1>
      <p>Everything you need to run your pickleball facility — from solo courts to multi-venue operations.</p>
    </div>

    <!-- 2. BILLING TOGGLE -->
    <div class="billing-toggle-wrap">
      <div class="billing-toggle-pill">
        <span class="billing-toggle-label" id="label-monthly" onclick="setBilling(false)">MONTHLY</span>
        <label class="toggle-switch">
          <input type="checkbox" id="billing-toggle">
          <div class="toggle-track"><div class="toggle-thumb"></div></div>
        </label>
        <span class="billing-toggle-label inactive" id="label-yearly" onclick="setBilling(true)">
          YEARLY <span class="yearly-badge">SAVE 30%</span>
        </span>
      </div>
    </div>

    <!-- 3. PLANS GRID -->
    <div id="plans-grid">
      <div class="plans-loading">
        <i class="bi bi-hourglass-split" style="font-size:1.6rem; display:block; margin-bottom:10px;"></i>
        Loading subscription plans...
      </div>
    </div>

    <!-- 4. TRUST & GUARANTEE STRIP -->
    <div class="pricing-trust-strip">
      <div class="pricing-trust-item">
        <i class="bi bi-shield-check"></i> Zero Lock-in Contract
      </div>
      <div class="pricing-trust-item">
        <i class="bi bi-lightning-charge-fill"></i> Instant Court Activation
      </div>
      <div class="pricing-trust-item">
        <i class="bi bi-arrow-repeat"></i> Upgrade or Switch Anytime
      </div>
    </div>

    <!-- 5. FAQ SECTION -->
    <div class="pricing-faq-section">
      <div class="pricing-faq-title">
        <i class="bi bi-question-circle-fill" style="color:var(--coral);"></i> Frequently Asked Questions
      </div>
      <div class="pricing-faq-item">
        <div class="pricing-faq-q">How does the 14-Day Free Trial work?</div>
        <div class="pricing-faq-a">Court owners can test Pikvero free for 14 days without entering credit card details. Add courts, test booking slots, and invite players.</div>
      </div>
      <div class="pricing-faq-item">
        <div class="pricing-faq-q">Can I switch plans or cancel later?</div>
        <div class="pricing-faq-a">Yes! You can upgrade, downgrade, or cancel your subscription at any time directly from your Court Owner Dashboard.</div>
      </div>
      <div class="pricing-faq-item">
        <div class="pricing-faq-q">Are customer booking payments supported?</div>
        <div class="pricing-faq-a">Yes, all tiers include support for GCash, Maya, and cash at desk payments with zero platform commission fee.</div>
      </div>
    </div>

  </div>

  <div id="footer-container"></div>

  <script src="/pikvero/assets/js/core/toast.js"></script>
  <script src="/pikvero/assets/js/core/ajax.js"></script>
  <script src="/pikvero/assets/js/core/auth.js"></script>
  <script src="/pikvero/assets/js/components/navbar.js?v=<?= time() ?>"></script>
  <script src="/pikvero/assets/js/components/footer.js?v=<?= time() ?>"></script>
  <script>
    let allPlans = [];
    let isYearly = false;
    let expandedPlans = {};

    const CARD_STYLES = [
      { cardClass: '',         badge: 'badge-streetside sand',  btn: 'button coral', cta: 'Get Started',     ribbon: 'coral' },
      { cardClass: 'featured', badge: 'badge-streetside dark',  btn: 'button dark',  cta: 'Go Professional', ribbon: 'dark'  },
      { cardClass: '',         badge: 'badge-streetside coral', btn: 'button lime',  cta: 'Go Enterprise',   ribbon: 'coral' },
      { cardClass: '',         badge: 'badge-streetside lime',  btn: 'button sand',  cta: 'Get This Plan',   ribbon: 'dark'  },
    ];

    document.addEventListener('DOMContentLoaded', async () => {
      await NavbarComponent.render('#navbar-container', false);
      FooterComponent.render('#footer-container', false);

      const toggle       = document.getElementById('billing-toggle');
      const labelMonthly = document.getElementById('label-monthly');
      const labelYearly  = document.getElementById('label-yearly');

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
      try {
        const res = await Api.get('/pikvero/api/subscription-plans.php');
        if (res.success && Array.isArray(res.data)) {
          allPlans = res.data;
          renderPlans();
        } else {
          showPlansError('Could not load subscription plans.');
        }
      } catch (e) {
        console.error(e);
        showPlansError('Failed to connect to the server. Please try again later.');
      }
    }

    function fmtPrice(plan) {
      if (plan.is_free_trial) return '<span>₱0</span> FREE';
      
      const yearlyVal = plan.yearly_price > plan.monthly_price ? plan.yearly_price : (plan.monthly_price * 12 * 0.7);
      const price = isYearly ? (yearlyVal / 12) : plan.monthly_price;
      return `₱${Math.round(price).toLocaleString('en-PH')} <span>/ mo</span>`;
    }

    function fmtYearlySub(plan) {
      if (plan.is_free_trial || !isYearly) return '';
      const yearly = plan.yearly_price > plan.monthly_price ? plan.yearly_price : (plan.monthly_price * 12 * 0.7);
      return `<div style="font-family:'DM Mono',monospace; font-size:0.70rem; font-weight:800; color:#3e544c; margin-top:2px;">
                Billed ₱${Math.round(yearly).toLocaleString('en-PH')} annually (Save 30%)
              </div>`;
    }

    function toggleFeatures(planId) {
      expandedPlans[planId] = !expandedPlans[planId];
      renderPlans();
    }

    function renderPlans() {
      const grid = document.getElementById('plans-grid');
      if (!grid) return;

      if (!allPlans.length) {
        grid.innerHTML = '<div class="plans-loading"><i class="bi bi-exclamation-circle" style="font-size:1.6rem; display:block; margin-bottom:10px;"></i>No plans available at this time.</div>';
        return;
      }

      // Detect if screen width is mobile
      const isMobile = window.innerWidth <= 768;

      grid.innerHTML = allPlans.map((plan, i) => {
        const s = CARD_STYLES[i % CARD_STYLES.length];
        const isFree = Boolean(plan.is_free_trial);
        const isPro = plan.slug === 'pro' || plan.name.toLowerCase().includes('pro');
        const registerUrl = `/pikvero/public/register.php?type=owner&plan=${encodeURIComponent(plan.slug || '')}`;

        const maxFac    = plan.max_facilities <= 0 ? '&infin;' : plan.max_facilities;
        const maxCourt  = plan.max_courts     <= 0 ? '&infin;' : plan.max_courts;
        const maxStaff  = plan.max_staff      <= 0 ? '&infin;' : plan.max_staff;
        const facLabel  = plan.max_facilities === 1 ? 'FACILITY' : 'FACILITIES';

        const rawFeatures = (plan.features && plan.features.length) ? plan.features : [
          'Court management tools',
          'Real-time online booking',
          'Schedule blockout manager'
        ];

        const isExpanded = Boolean(expandedPlans[plan.id]);
        const maxVisibleFeatures = isMobile ? 4 : 6;
        const hasExtra = rawFeatures.length > maxVisibleFeatures;
        const visibleFeatures = (isExpanded || !hasExtra) ? rawFeatures : rawFeatures.slice(0, maxVisibleFeatures);

        const featuresHtml = visibleFeatures.map(f => `<li><i class="bi bi-check-circle-fill"></i><span>${escapeHtml(f)}</span></li>`).join('');

        let ribbonHtml = '';
        if (isFree) {
          ribbonHtml = '<div class="plan-card-ribbon coral"><i class="bi bi-gift-fill"></i> FREE TRIAL</div>';
        } else if (isPro) {
          ribbonHtml = '<div class="plan-card-ribbon dark"><i class="bi bi-star-fill" style="color:var(--lime);"></i> MOST POPULAR</div>';
        }

        const toggleBtnHtml = hasExtra ? `
          <button type="button" class="plan-toggle-features-btn" onclick="toggleFeatures(${plan.id})">
            ${isExpanded ? '<i class="bi bi-chevron-up"></i> Show Less' : `<i class="bi bi-plus-circle"></i> +${rawFeatures.length - maxVisibleFeatures} More Features`}
          </button>
        ` : '';

        return `
          <div class="plan-card ${s.cardClass}">
            <div>
              ${ribbonHtml}
              <div class="plan-header">
                <span class="${s.badge}">${escapeHtml(plan.name.toUpperCase())}</span>
              </div>
              
              <div class="plan-price">${fmtPrice(plan)}</div>
              ${fmtYearlySub(plan)}

              <p class="plan-desc">${escapeHtml(plan.description || 'Manage your courts, bookings, and team with ease.')}</p>

              <div class="plan-specs">
                <div class="plan-spec-item"><strong>${maxFac}</strong>${facLabel}</div>
                <div class="plan-spec-item"><strong>${maxCourt}</strong>COURTS</div>
                <div class="plan-spec-item"><strong>${maxStaff}</strong>STAFF</div>
              </div>

              <ul class="plan-features">${featuresHtml}</ul>
              ${toggleBtnHtml}
            </div>

            <div>
              <a href="${registerUrl}" class="${s.btn} plan-cta">
                <i class="bi bi-arrow-right-circle-fill"></i> ${isFree ? 'Start Free Trial' : s.cta}
              </a>
            </div>
          </div>`;
      }).join('');
    }

    function showPlansError(msg) {
      const grid = document.getElementById('plans-grid');
      if (grid) {
        grid.innerHTML = `
          <div class="plans-loading">
            <i class="bi bi-exclamation-triangle" style="font-size:1.6rem; display:block; margin-bottom:10px; color:var(--coral);"></i>
            ${escapeHtml(msg)}
          </div>`;
      }
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
