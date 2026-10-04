<?php
require_once __DIR__ . '/../../app/bootstrap.php';

use App\Infrastructure\Repositories\SystemSettingRepository;

$pageTitle  = 'Pikvero — My Subscription Plan';
$headExtras = ['jquery', 'datatables'];
require_once __DIR__ . '/../../includes/head.php';

$settingRepo = new SystemSettingRepository();
$allSettings = $settingRepo->getAllAsMap();

$paymentChannelsMap = [
    'paymongo_enable_gcash'    => ['id' => 'gcash',    'name' => 'GCash Philippines',       'sub' => 'E-Wallet Checkout',       'icon' => 'bi-qr-code',              'color' => '#005ce6'],
    'paymongo_enable_grabpay'  => ['id' => 'grabpay',  'name' => 'GrabPay E-Wallet',        'sub' => 'E-Wallet Transfer',       'icon' => 'bi-phone-vibrate',        'color' => '#00b14f'],
    'paymongo_enable_paymaya'  => ['id' => 'maya',     'name' => 'Maya / PayMaya',          'sub' => 'Maya Balance & Credit',   'icon' => 'bi-wallet2',              'color' => '#2baf67'],
    'paymongo_enable_cards'    => ['id' => 'card',     'name' => 'Credit / Debit Card',     'sub' => 'Visa, Mastercard & JCB',  'icon' => 'bi-credit-card-2-front-fill',  'color' => '#e11d48'],
    'paymongo_enable_qrph'     => ['id' => 'qrph',     'name' => 'QR Ph National Standard', 'sub' => 'All PH Banks (InstaPay)', 'icon' => 'bi-qr-code-scan',        'color' => '#0f172a'],
    'paymongo_enable_dob'      => ['id' => 'dob',      'name' => 'Direct Online Banking',   'sub' => 'BDO, BPI, UnionBank OTC', 'icon' => 'bi-bank',                 'color' => '#0284c7'],
    'paymongo_enable_billease' => ['id' => 'billease', 'name' => 'BillEase Installments',   'sub' => 'Buy Now, Pay Later',      'icon' => 'bi-bag-check-fill',       'color' => '#6366f1'],
];

$enabledPaymentMethods = [];
foreach ($paymentChannelsMap as $key => $pm) {
    if (isset($allSettings[$key]) && $allSettings[$key] === '1') {
        $enabledPaymentMethods[] = $pm;
    }
}
if (empty($enabledPaymentMethods)) {
    $enabledPaymentMethods[] = $paymentChannelsMap['paymongo_enable_gcash'];
    $enabledPaymentMethods[] = $paymentChannelsMap['paymongo_enable_cards'];
}
?>
  <style>
    .plan-card-streetside {
      background: var(--white);
      border: 3px solid var(--ink);
      border-radius: 16px;
      padding: 24px;
      position: relative;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      box-shadow: 4px 4px 0 var(--ink);
      transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .plan-card-streetside.is-current {
      border-color: var(--green);
      background: #f0fdf4;
      box-shadow: 6px 6px 0 var(--green);
    }
    .plan-card-streetside:hover {
      transform: translateY(-3px);
    }
    .plan-perk-item {
      display: flex;
      align-items: flex-start;
      gap: 8px;
      font-size: 0.84rem;
      color: #2b3a35;
      margin-bottom: 8px;
      line-height: 1.35;
    }
    .plan-perk-item i {
      color: var(--green);
      font-size: 0.95rem;
      margin-top: 1px;
    }
    .usage-progress-bar {
      height: 8px;
      border-radius: 10px;
      background: #e2e8f0;
      overflow: hidden;
      margin-top: 4px;
      border: 1px solid var(--ink);
    }
    .usage-progress-fill {
      height: 100%;
      background: var(--green);
      transition: width 0.4s ease;
    }
    #subscribe-modal .card-streetside::-webkit-scrollbar {
      display: none;
    }
    #subscribe-modal .card-streetside {
      -ms-overflow-style: none;
      scrollbar-width: none;
    }
  </style>
</head>
<body>

  <aside id="sidebar-container"></aside>
  <header id="navbar-container"></header>

  <main class="portal-main">
    <div>
      <!-- Header Bar -->
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; flex-wrap:wrap; gap:12px;">
        <div>
          <div class="eyebrow"><i class="bi bi-award-fill" style="color:var(--coral);"></i> SAAS MEMBERSHIP MANAGEMENT</div>
          <h1 style="font-size: clamp(1.8rem, 4vw, 2.2rem); font-weight:800; text-transform:uppercase; margin:4px 0 0;">MY SUBSCRIPTION PLAN</h1>
        </div>
        <div>
          <a href="/pikvero/public/billing-statement.php" target="_blank" class="button lime" style="padding:10px 18px; font-size:0.85rem;" title="View & Print Official Account Billing Statement">
            <i class="bi bi-file-earmark-text-fill"></i> Official Billing Statement
          </a>
        </div>
      </div>

      <!-- Current Active Plan & Usage Dashboard Banner -->
      <div id="current-plan-banner" class="card-streetside" style="padding:28px; background:var(--white); margin-bottom:32px; border-color:var(--ink);">
        <!-- Loaded via JS -->
        <div style="text-align:center; padding:20px; color:#5a7060;">
          <i class="bi bi-hourglass-split" style="font-size:1.8rem; color:var(--coral); display:block; margin-bottom:8px;"></i>
          Loading your active SaaS subscription details...
        </div>
      </div>

      <!-- Available Subscription Plans Section -->
      <div id="section-available-tiers" style="margin-bottom:36px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:18px; flex-wrap:wrap; gap:12px;">
          <div>
            <h3 style="font-size:1.2rem; font-weight:900; text-transform:uppercase; margin:0;">
              <i class="bi bi-rocket-takeoff-fill" style="color:var(--coral); margin-right:6px;"></i>AVAILABLE SUBSCRIPTION TIERS
            </h3>
            <div style="font-size:0.78rem; color:#5a7060;">Choose or upgrade your plan to unlock more court slots and facilities</div>
          </div>

          <!-- Billing Cycle Selector (Monthly vs Yearly 30% Off) -->
          <div style="display:flex; align-items:center; gap:10px; background:var(--white); border:2px solid var(--ink); border-radius:30px; padding:4px 14px; box-shadow:2px 2px 0 var(--ink);">
            <span id="cycle-monthly-label" style="font-weight:900; font-size:0.78rem; color:var(--green); font-family:'DM Mono', monospace; text-transform:uppercase;">MONTHLY</span>
            <label style="position:relative; display:inline-block; width:44px; height:22px; margin:0; cursor:pointer;">
              <input type="checkbox" id="billing-cycle-toggle" onchange="toggleBillingCycle()" style="opacity:0; width:0; height:0;">
              <span style="position:absolute; inset:0; background:var(--ink); border-radius:30px; transition:0.3s; border:1px solid var(--ink);"></span>
              <span id="toggle-knob" style="position:absolute; height:16px; width:16px; left:3px; bottom:2px; background:var(--lime); border-radius:50%; transition:0.3s;"></span>
            </label>
            <span id="cycle-yearly-label" style="font-weight:800; font-size:0.78rem; color:#5a7060; font-family:'DM Mono', monospace; text-transform:uppercase;">
              YEARLY <span class="badge-streetside coral" style="font-size:0.65rem; padding:2px 6px;">SAVE 30%</span>
            </span>
          </div>
        </div>

        <div id="subscription-plans-grid" style="display:grid; grid-template-columns:repeat(auto-fill, minmax(320px, 1fr)); gap:22px;">
          <!-- Loaded via JS -->
        </div>
      </div>

      <!-- Payment & Invoice History Table -->
      <div id="section-payment-history" class="card-streetside" style="padding:24px; background:var(--white); margin-bottom:24px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; flex-wrap:wrap; gap:10px;">
          <h3 style="font-size:1.1rem; font-weight:900; text-transform:uppercase; margin:0;">
            <i class="bi bi-receipt" style="color:var(--green); margin-right:6px;"></i>SUBSCRIPTION PAYMENT HISTORY
          </h3>
          <div style="display:flex; gap:8px; align-items:center;">
            <a href="/pikvero/public/billing-statement.php" target="_blank" class="button lime" style="padding:6px 14px; font-size:0.75rem;" title="View & Print Account Statement">
              <i class="bi bi-file-earmark-text-fill"></i> Print Billing Statement
            </a>
            <span id="payments-count-badge" class="badge-streetside lime" style="font-size:0.75rem;">0 Payments</span>
          </div>
        </div>

        <div style="overflow-x:auto;">
          <table id="payments-history-table" class="table-streetside" style="width:100%; border-collapse:collapse;">
            <thead>
              <tr style="border-bottom:2px solid var(--ink); text-align:left; font-family:'DM Mono', monospace; font-size:0.75rem; text-transform:uppercase;">
                <th style="padding:10px;">DATE</th>
                <th style="padding:10px;">AMOUNT PAID</th>
                <th style="padding:10px;">PAYMENT METHOD</th>
                <th style="padding:10px;">STATUS</th>
                <th style="padding:10px; text-align:right;">ACTIONS</th>
              </tr>
            </thead>
            <tbody>
              <!-- Loaded via DataTables Server-Side -->
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- VIEW SUBSCRIPTION PAYMENT DETAIL MODAL -->
    <div class="modal-overlay" id="view-sub-modal" style="display:none; position:fixed; inset:0; z-index:99999 !important; background:rgba(10,20,15,0.78); backdrop-filter:blur(4px); place-items:center; padding:20px;">
      <div class="card-streetside" style="width:min(580px, 100%); padding:24px; background:var(--white);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; border-bottom:2px solid var(--ink); padding-bottom:10px;">
          <h3 style="font-size:1.1rem; font-weight:800; text-transform:uppercase; margin:0;"><i class="bi bi-receipt-cutoff"></i> SUBSCRIPTION PAYMENT VOUCHER</h3>
          <button onclick="closeModal('view-sub-modal')" class="button sand" style="padding:2px 8px; font-size:0.8rem;">✕</button>
        </div>

        <div id="view-sub-content">
          <!-- Dynamically filled -->
        </div>

        <div style="display:flex; justify-content:flex-end; gap:8px; margin-top:20px;">
          <button type="button" onclick="closeModal('view-sub-modal')" class="button sand" style="padding:8px 16px; font-size:0.8rem;">Close</button>
          <button type="button" id="modal-print-btn" onclick="printSelectedSubReceipt()" class="button lime" style="padding:8px 18px; font-size:0.8rem;"><i class="bi bi-printer-fill"></i> Print Receipt</button>
        </div>
      </div>
    </div>

    <footer id="footer-container"></footer>
  </main>

  <!-- ── Subscribe / Upgrade PayMongo Payment Breakdown Modal ── -->
  <div id="subscribe-modal" style="display:none; position:fixed; inset:0; z-index:999999 !important; background:rgba(10,20,15,0.8); backdrop-filter:blur(8px); -webkit-backdrop-filter:blur(8px); overflow-y:auto; padding:20px 16px;">
    <div style="min-height:100%; display:flex; align-items:center; justify-content:center; padding:30px 0;">
      <div class="card-streetside" style="max-width:620px; width:100%; background:var(--white); padding:32px; position:relative; box-shadow:8px 8px 0 var(--ink); border:3px solid var(--ink); border-radius:18px;">
        <button type="button" onclick="closeSubscribeModal()" style="position:absolute; top:18px; right:18px; background:none; border:none; font-size:1.8rem; cursor:pointer; color:var(--ink); font-weight:900; line-height:1;">&times;</button>

        <input type="hidden" id="sub_plan_id">

        <div style="display:flex; align-items:center; gap:14px; margin-bottom:20px;">
          <div class="brand-mark" style="width:48px; height:48px; font-size:1.4rem; background:var(--coral); color:var(--white); border-radius:12px; display:grid; place-items:center; border:2px solid var(--ink); box-shadow:2px 2px 0 var(--ink);">
            <i class="bi bi-receipt"></i>
          </div>
          <div>
            <h3 id="sub-modal-title" style="font-size:1.25rem; font-weight:900; text-transform:uppercase; margin:0; line-height:1.2;">PAYMONGO SUBSCRIPTION PAYMENT</h3>
            <div style="font-size:0.78rem; color:#4a5c56; font-family:'DM Mono', monospace; font-weight:700;">Review SaaS subscription invoice &amp; select payment channel</div>
          </div>
        </div>

        <div class="card-streetside sand" style="padding:20px; margin-bottom:24px; border:2px solid var(--ink); border-radius:14px;">
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;">
            <div>
              <span class="mono" style="font-size:0.7rem; color:#4a5c56; font-weight:800; text-transform:uppercase;">SELECTED PLAN TIER</span>
              <div id="sub-modal-plan-name" style="font-size:1.35rem; font-weight:900; color:var(--ink);">Pro Plan</div>
            </div>
            <span class="badge-streetside coral" id="sub-modal-cycle-badge" style="font-size:0.75rem; font-weight:800;">MONTHLY RECURRING</span>
          </div>

          <!-- Financial Itemization Table -->
          <div style="border-top:2px dashed var(--ink); padding-top:12px; font-family:'DM Mono', monospace; font-size:0.85rem; line-height:2.0;">
            <div style="display:flex; justify-content:space-between;">
              <span style="color:#4a5c56;">Base Plan Rate:</span>
              <strong id="sub-modal-base-price">₱999.00</strong>
            </div>
            <div style="display:flex; justify-content:space-between;">
              <span style="color:#4a5c56;">Platform Infrastructure Fee (2%):</span>
              <strong id="sub-modal-platform-fee">₱19.98</strong>
            </div>
            <div style="display:flex; justify-content:space-between;">
              <span style="color:#4a5c56;">PayMongo Gateway Fee (2.5%):</span>
              <strong id="sub-modal-gateway-fee">₱24.98</strong>
            </div>
            <div style="display:flex; justify-content:space-between; border-top:2px solid var(--ink); padding-top:10px; margin-top:10px; font-size:1.05rem; font-weight:900;">
              <span>TOTAL AMOUNT TO BE PAID:</span>
              <strong id="sub-modal-total-amount" style="color:var(--green); font-size:1.3rem;">₱1,043.96</strong>
            </div>
          </div>
        </div>

        <!-- Payment Method Selection -->
        <div style="margin-bottom:24px;">
          <label class="mono" style="display:block; margin-bottom:10px; font-size:0.78rem; font-weight:800;">SELECT PAYMONGO PAYMENT CHANNEL *</label>
          <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:12px;">
            <?php foreach ($enabledPaymentMethods as $idx => $pm): ?>
              <label class="card-streetside modal-pay-option" style="padding:12px 14px; cursor:pointer; display:flex; align-items:center; gap:12px; border:2px solid var(--ink); border-radius:12px; background:var(--white);">
                <input type="radio" name="modal_paymethod" value="<?= htmlspecialchars($pm['id']) ?>" <?= $idx === 0 ? 'checked' : '' ?> style="accent-color:var(--coral); transform:scale(1.25);">
                <div>
                  <strong style="display:block; font-size:0.88rem;"><i class="bi <?= htmlspecialchars($pm['icon']) ?>" style="color:<?= htmlspecialchars($pm['color']) ?>;"></i> <?= htmlspecialchars($pm['name']) ?></strong>
                  <span style="font-size:0.7rem; color:#4a5c56; font-family:'DM Mono', monospace;"><?= htmlspecialchars($pm['sub']) ?></span>
                </div>
              </label>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Action Buttons -->
        <div style="display:flex; justify-content:flex-end; gap:12px; border-top:2px solid var(--ink); padding-top:20px;">
          <button type="button" onclick="closeSubscribeModal()" class="button sand" style="padding:12px 22px; font-size:0.88rem; font-weight:700;">Cancel</button>
          <button type="button" id="btn-modal-pay-now" onclick="processSubscriptionChangePayment()" class="button lime" style="padding:12px 28px; font-size:0.92rem; font-weight:900;">
            <i class="bi bi-lock-fill"></i> Continue to PayMongo Payment &rarr;
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Cancel Subscription Modal -->
  <div id="cancel-subscription-modal" style="display:none; position:fixed; inset:0; z-index:9999; background:rgba(10,20,15,0.7); backdrop-filter:blur(6px); place-items:center; padding:16px;">
    <div class="card-streetside" style="width:min(500px, 100%); padding:28px; background:var(--cream); text-align:center; position:relative;">
      <div style="width:64px; height:64px; border-radius:50%; background:#fff5f3; border:3px solid var(--coral); display:flex; align-items:center; justify-content:center; margin:0 auto 16px; box-shadow:3px 3px 0 var(--ink);">
        <i class="bi bi-exclamation-triangle-fill" style="font-size:2rem; color:var(--coral);"></i>
      </div>

      <h3 style="margin:0 0 8px; font-size:1.35rem; text-transform:uppercase; font-weight:900; color:var(--ink);">
        CANCEL SUBSCRIPTION
      </h3>

      <div style="background:var(--white); border:2px solid var(--ink); border-radius:12px; padding:18px; margin-bottom:20px; text-align:left;">
        <p style="font-size:0.88rem; color:#2c3e38; margin:0 0 10px; line-height:1.45;">
          Are you sure you want to cancel your current subscription plan (<strong id="cancel-modal-plan-name">Current Plan</strong>)?
        </p>
        <div style="font-size:0.8rem; color:#5a7060; font-family:'DM Mono', monospace;">
          Your active plan perks and feature limits will remain accessible until the end of your billing cycle (<strong id="cancel-modal-period-end">N/A</strong>).
        </div>
      </div>

      <div style="display:flex; gap:10px; justify-content:center;">
        <button type="button" onclick="closeCancelSubscriptionModal()" class="button sand" style="padding:10px 18px; font-size:0.84rem;">Keep My Subscription</button>
        <button type="button" id="btn-confirm-cancel-sub" onclick="confirmCancelSubscription()" class="button coral" style="padding:10px 22px; font-size:0.84rem;">
          <i class="bi bi-x-circle-fill"></i> Confirm Cancellation
        </button>
      </div>
    </div>
  </div>

  <!-- Upgrade Plan Selector Modal -->
  <div id="upgrade-plan-selector-modal" style="display:none; position:fixed; inset:0; z-index:9999; background:rgba(10,20,15,0.75); backdrop-filter:blur(6px); place-items:center; padding:20px;">
    <div class="card-streetside" style="width:min(900px, 100%); padding:28px; background:var(--cream); position:relative; max-height:92vh; overflow-y:auto;">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; border-bottom:2px solid var(--ink); padding-bottom:12px; flex-wrap:wrap; gap:12px;">
        <div>
          <h3 style="margin:0; font-size:1.3rem; font-weight:900; text-transform:uppercase; color:var(--ink);">
            <i class="bi bi-rocket-takeoff-fill" style="color:var(--coral); margin-right:8px;"></i>UPGRADE YOUR PLAN TIER
          </h3>
          <div style="font-size:0.8rem; color:#5a7060;">Select any plan tier below to upgrade facilities, courts, and staff slots</div>
        </div>

        <div style="display:flex; align-items:center; gap:12px;">
          <!-- Modal Billing Cycle Selector -->
          <div style="display:flex; align-items:center; gap:10px; background:var(--white); border:2px solid var(--ink); border-radius:30px; padding:4px 14px; box-shadow:2px 2px 0 var(--ink);">
            <span id="modal-cycle-monthly-label" style="font-weight:900; font-size:0.78rem; color:var(--green); font-family:'DM Mono', monospace; text-transform:uppercase;">MONTHLY</span>
            <label style="position:relative; display:inline-block; width:44px; height:22px; margin:0; cursor:pointer;">
              <input type="checkbox" id="modal-billing-cycle-toggle" onchange="toggleModalBillingCycle()" style="opacity:0; width:0; height:0;">
              <span style="position:absolute; inset:0; background:var(--ink); border-radius:30px; transition:0.3s; border:1px solid var(--ink);"></span>
              <span id="modal-toggle-knob" style="position:absolute; height:16px; width:16px; left:3px; bottom:2px; background:var(--lime); border-radius:50%; transition:0.3s;"></span>
            </label>
            <span id="modal-cycle-yearly-label" style="font-weight:800; font-size:0.78rem; color:#5a7060; font-family:'DM Mono', monospace; text-transform:uppercase;">
              YEARLY <span class="badge-streetside coral" style="font-size:0.65rem; padding:2px 6px;">SAVE 30%</span>
            </span>
          </div>

          <button onclick="closeUpgradePlanSelectorModal()" class="button sand" style="padding:4px 10px; font-size:0.85rem;">✕</button>
        </div>
      </div>

      <!-- Modal Plan Cards Grid -->
      <div id="modal-subscription-plans-grid" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(260px, 1fr)); gap:18px;">
        <!-- Dynamically rendered via JS -->
      </div>
    </div>
  </div>

  <!-- Pay / Renew Now Confirmation Modal -->
  <div id="pay-now-modal" style="display:none; position:fixed; inset:0; z-index:99999; background:rgba(10,20,15,0.7); backdrop-filter:blur(6px); overflow-y:auto; padding:40px 16px;">
    <div class="card-streetside" style="width:min(500px, 100%); margin:0 auto; padding:28px; background:var(--cream); text-align:center; position:relative;">
      <div style="width:64px; height:64px; border-radius:50%; background:#f0fdf4; border:3px solid var(--green); display:flex; align-items:center; justify-content:center; margin:0 auto 16px; box-shadow:3px 3px 0 var(--ink);">
        <i class="bi bi-credit-card-fill" style="font-size:2rem; color:var(--green);"></i>
      </div>

      <h3 style="margin:0 0 8px; font-size:1.35rem; text-transform:uppercase; font-weight:900; color:var(--ink);">
        RENEW SUBSCRIPTION
      </h3>

      <div style="background:var(--white); border:2px solid var(--ink); border-radius:12px; padding:18px; margin-bottom:20px; text-align:left;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
          <span style="font-weight:800; font-size:0.9rem;" id="pay-modal-plan-name">Starter Plan</span>
          <span class="badge-streetside coral" id="pay-modal-cycle-badge">ANNUAL BILLING</span>
        </div>
        <div style="font-size:1.8rem; font-weight:900; color:var(--green); font-family:'DM Mono', monospace;" id="pay-modal-price">
          ₱0.00
        </div>
        <div style="font-size:0.75rem; color:#5a7060; margin-top:4px;" id="pay-modal-notice">
          Executing payment will immediately extend your subscription access for another billing period.
        </div>
      </div>

      <div style="display:flex; gap:10px; justify-content:center;">
        <button type="button" onclick="closePayNowModal()" class="button sand" style="padding:10px 18px; font-size:0.84rem;">Cancel</button>
        <button type="button" id="btn-confirm-pay-now" onclick="confirmPayNow()" class="button lime" style="padding:10px 22px; font-size:0.84rem;">
          <i class="bi bi-check-circle-fill"></i> Confirm Payment &amp; Renew Now
        </button>
      </div>
    </div>
  </div>

  <?php require_once __DIR__ . '/../../includes/scripts.php'; ?>
  <script>
    let currentSubscriptionData = null;
    let allPlansList = [];
    let currentBillingCycle = 'monthly';
    let canManageSubscription = false;

    function triggerPayNowModal() {
      const sub = currentSubscriptionData ? currentSubscriptionData.subscription : null;
      if (!sub) return;

      const isYearly = (sub.current_billing_cycle === 'yearly');
      const mPrice = parseFloat(sub.monthly_price) || 0;
      const yPrice = (parseFloat(sub.yearly_price) > 0) ? parseFloat(sub.yearly_price) : Math.round(mPrice * 12 * 0.70 * 100) / 100;
      const amount = isYearly ? yPrice : mPrice;

      const planNameEl = document.getElementById('pay-modal-plan-name');
      if (planNameEl) planNameEl.textContent = sub.plan_name || 'Starter Plan';

      const badgeEl = document.getElementById('pay-modal-cycle-badge');
      if (badgeEl) badgeEl.textContent = isYearly ? 'ANNUAL BILLING (SAVE 30%)' : 'MONTHLY BILLING';

      const priceEl = document.getElementById('pay-modal-price');
      if (priceEl) priceEl.textContent = `₱${amount.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2})}`;

      // Calculate extended subscription period end date preview
      let currentEnd = sub.current_period_end ? new Date(sub.current_period_end) : new Date();
      let now = new Date();
      let baseDate = (currentEnd > now) ? currentEnd : now;

      let newEndDate = new Date(baseDate);
      if (isYearly) {
        newEndDate.setFullYear(newEndDate.getFullYear() + 1);
      } else {
        newEndDate.setMonth(newEndDate.getMonth() + 1);
      }
      const formattedNewEnd = newEndDate.toISOString().split('T')[0];

      const noticeEl = document.getElementById('pay-modal-notice');
      if (noticeEl) {
        noticeEl.innerHTML = `Executing payment will add <strong>${isYearly ? '1 Year (+12 Months)' : '1 Month'}</strong> to your current term, extending your active access to <strong style="color:var(--green);">${formattedNewEnd}</strong>.`;
      }

      document.body.style.overflow = 'hidden';
      const modal = document.getElementById('pay-now-modal');
      if (modal) modal.style.display = 'block';
    }

    function closePayNowModal() {
      document.body.style.overflow = '';
      const modal = document.getElementById('pay-now-modal');
      if (modal) modal.style.display = 'none';
    }

    async function confirmPayNow() {
      const sub = currentSubscriptionData ? currentSubscriptionData.subscription : null;
      if (!sub) return;

      const btn = document.getElementById('btn-confirm-pay-now');
      if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Processing Payment...';
      }

      try {
        const res = await Api.post('/pikvero/api/owner/subscription/change.php', {
          plan_id: sub.plan_id,
          billing_cycle: sub.current_billing_cycle || 'monthly'
        });

        if (res && res.success) {
          Toast.success('Payment Received!', 'Your subscription renewal payment was processed successfully.');
          closePayNowModal();
          await loadOwnerSubscription();
          await loadAvailablePlans();
        } else {
          Toast.error('Payment Failed', (res && res.message) ? res.message : 'Could not process renewal.');
        }
      } catch(err) {
        console.error(err);
        Toast.error('Error', err.message || 'An error occurred while renewing your subscription.');
      } finally {
        if (btn) {
          btn.disabled = false;
          btn.innerHTML = '<i class="bi bi-check-circle-fill"></i> Confirm Payment &amp; Renew Now';
        }
      }
    }

    function openUpgradePlanSelectorModal() {
      UpgradePlanModal.open();
    }

    function closeUpgradePlanSelectorModal() {
      const modal = document.getElementById('upgrade-plan-selector-modal');
      if (modal) modal.style.display = 'none';
    }

    function toggleModalBillingCycle() {
      const modalToggle = document.getElementById('modal-billing-cycle-toggle');
      const mainToggle = document.getElementById('billing-cycle-toggle');
      if (mainToggle) mainToggle.checked = modalToggle.checked;

      const modalKnob = document.getElementById('modal-toggle-knob');
      const modalMonthly = document.getElementById('modal-cycle-monthly-label');
      const modalYearly = document.getElementById('modal-cycle-yearly-label');

      if (modalToggle.checked) {
        currentBillingCycle = 'yearly';
        if (modalKnob) modalKnob.style.left = '25px';
        if (modalMonthly) { modalMonthly.style.color = '#5a7060'; modalMonthly.style.fontWeight = '700'; }
        if (modalYearly) { modalYearly.style.color = 'var(--green)'; modalYearly.style.fontWeight = '900'; }
      } else {
        currentBillingCycle = 'monthly';
        if (modalKnob) modalKnob.style.left = '3px';
        if (modalMonthly) { modalMonthly.style.color = 'var(--green)'; modalMonthly.style.fontWeight = '900'; }
        if (modalYearly) { modalYearly.style.color = '#5a7060'; modalYearly.style.fontWeight = '700'; }
      }

      toggleBillingCycle();
      renderModalPlansGrid();
    }

    function renderModalPlansGrid() {
      const container = document.getElementById('modal-subscription-plans-grid');
      if (!container) return;

      const activeSub = currentSubscriptionData ? currentSubscriptionData.subscription : null;
      const activePlanId = activeSub ? parseInt(activeSub.plan_id) : 0;

      if (!allPlansList || allPlansList.length === 0) {
        container.innerHTML = `<div style="grid-column:1/-1; text-align:center; color:#5a7060; padding:20px;">No platform subscription plans configured yet.</div>`;
        return;
      }

      container.innerHTML = allPlansList.map(p => {
        const isCurrent = (parseInt(p.id) === activePlanId);
        const isFree = parseInt(p.is_free_trial) === 1;
        const trialMonths = p.trial_duration_months || 1;
        const features = p.features || [];

        const perksListHtml = features.length > 0
          ? features.map(f => `
              <div class="plan-perk-item" style="font-size:0.75rem; margin-bottom:4px; display:flex; gap:6px; align-items:center;">
                <i class="bi bi-check-circle-fill" style="color:var(--green);"></i>
                <span>${f.feature}</span>
              </div>
            `).join('')
          : `<div style="font-size:0.75rem; color:#888; font-style:italic;">No custom perks configured.</div>`;

        let priceDisplay = '';
        if (isFree) {
          priceDisplay = `<div style="font-size:1.6rem; font-weight:900; color:var(--green); font-family:'DM Mono', monospace; line-height:1.1;">FREE TRIAL <span style="font-size:0.78rem; color:#4a5c56;">(${trialMonths} Mo)</span></div>`;
        } else if (currentBillingCycle === 'yearly') {
          const monthlyPrice = parseFloat(p.monthly_price) || 0;
          const yearlyPrice = (parseFloat(p.yearly_price) > 0) ? parseFloat(p.yearly_price) : Math.round(monthlyPrice * 12 * 0.70 * 100) / 100;
          const equivMonthly = yearlyPrice / 12;

          priceDisplay = `
            <div style="font-size:1.5rem; font-weight:900; color:var(--green); font-family:'DM Mono', monospace; line-height:1.1;">
              ₱${yearlyPrice.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2})}
              <span style="font-size:0.78rem; color:#4a5c56;">/ yr</span>
              <span class="badge-streetside coral" style="font-size:0.6rem; vertical-align:middle; margin-left:4px;">SAVE 30%</span>
            </div>
            <div style="font-size:0.72rem; color:#5a7060; font-family:'DM Mono', monospace; margin-top:2px;">
              Equivalent to <strong>₱${equivMonthly.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2})}/mo</strong>
            </div>
          `;
        } else {
          priceDisplay = `
            <div style="font-size:1.6rem; font-weight:900; color:var(--green); font-family:'DM Mono', monospace; line-height:1.1;">
              ₱${parseFloat(p.monthly_price).toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2})}
              <span style="font-size:0.78rem; color:#4a5c56;">/ mo</span>
            </div>
          `;
        }

        let actionButtonHtml = '';
        if (isCurrent) {
          actionButtonHtml = `
            <button class="button green" style="width:100%; padding:8px; font-size:0.78rem; cursor:default;" disabled>
              <i class="bi bi-check-circle-fill"></i> CURRENT PLAN
            </button>
          `;
        } else if (canManageSubscription) {
          actionButtonHtml = `
            <button onclick="closeUpgradePlanSelectorModal(); openSubscribeModal(${p.id});" class="button coral" style="width:100%; padding:8px; font-size:0.78rem;">
              <i class="bi bi-rocket-takeoff-fill"></i> ${isFree ? 'Claim Free Trial' : 'Subscribe / Upgrade Plan'}
            </button>
          `;
        } else {
          actionButtonHtml = `<div style="font-size:0.75rem; color:#888; text-align:center;">Read Only Mode</div>`;
        }

        return `
          <div class="card-streetside" style="background:var(--white); padding:20px; display:flex; flex-direction:column; justify-content:space-between; position:relative; ${isCurrent ? 'border:3px solid var(--green);' : ''}">
            ${isCurrent ? `<span class="badge-streetside green" style="position:absolute; top:-12px; right:14px; font-size:0.65rem;">ACTIVE TIER</span>` : ''}
            <div>
              <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                <h4 style="font-size:1.15rem; font-weight:900; text-transform:uppercase; margin:0;">${p.name}</h4>
                ${isFree ? `<span class="badge-streetside lime" style="font-size:0.65rem;">FREE</span>` : ''}
              </div>

              <div style="margin-bottom:14px;">${priceDisplay}</div>
              <p style="font-size:0.78rem; color:#4a5c56; margin:0 0 14px; min-height:36px;">${p.description || 'SaaS subscription tier'}</p>

              <div style="background:#f4f7f5; border:1px solid var(--ink); border-radius:8px; padding:10px; margin-bottom:14px;">
                <div style="font-size:0.7rem; font-family:'DM Mono', monospace; font-weight:800; color:var(--green); text-transform:uppercase; margin-bottom:6px;">RESOURCE LIMITS</div>
                <div style="display:flex; justify-content:space-between; font-size:0.75rem; font-family:'DM Mono', monospace; font-weight:700;">
                  <span>FACILITIES:</span> <strong>${p.max_facilities}</strong>
                </div>
                <div style="display:flex; justify-content:space-between; font-size:0.75rem; font-family:'DM Mono', monospace; font-weight:700;">
                  <span>COURTS:</span> <strong>${p.max_courts}</strong>
                </div>
                <div style="display:flex; justify-content:space-between; font-size:0.75rem; font-family:'DM Mono', monospace; font-weight:700;">
                  <span>STAFF MEMBERS:</span> <strong>${p.max_staff}</strong>
                </div>
              </div>

              <div style="margin-bottom:16px;">
                <div style="font-size:0.7rem; font-family:'DM Mono', monospace; font-weight:800; color:var(--green); text-transform:uppercase; margin-bottom:6px;">INCLUDED PERKS</div>
                ${perksListHtml}
              </div>
            </div>

            <div>${actionButtonHtml}</div>
          </div>
        `;
      }).join('');
    }

    function scrollToAvailableTiers() {
      const el = document.getElementById('section-available-tiers');
      if (el) {
        el.scrollIntoView({ behavior: 'smooth' });
      }
    }

    function triggerPayNowModal() {
      const sub = currentSubscriptionData ? currentSubscriptionData.subscription : null;
      if (!sub) {
        Toast.error('No Active Subscription', 'Please select a plan tier below to subscribe.');
        return;
      }
      openSubscribeModal(sub.plan_id, sub.current_billing_cycle || 'monthly');
    }

    function openCancelSubscriptionModal() {
      const sub = currentSubscriptionData ? currentSubscriptionData.subscription : null;
      if (!sub) return;

      const planNameEl = document.getElementById('cancel-modal-plan-name');
      if (planNameEl) planNameEl.textContent = sub.plan_name || 'Current Plan';

      const endEl = document.getElementById('cancel-modal-period-end');
      if (endEl) endEl.textContent = sub.current_period_end || 'N/A';

      const modal = document.getElementById('cancel-subscription-modal');
      if (modal) modal.style.display = 'grid';
    }

    function closeCancelSubscriptionModal() {
      const modal = document.getElementById('cancel-subscription-modal');
      if (modal) modal.style.display = 'none';
    }

    async function confirmCancelSubscription() {
      const btn = document.getElementById('btn-confirm-cancel-sub');
      if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Cancelling...';
      }

      try {
        const res = await Api.post('/pikvero/api/owner/subscription/cancel.php', {});
        if (res && res.success) {
          Toast.success('Subscription Cancelled', res.message || 'Your subscription has been cancelled.');
          closeCancelSubscriptionModal();
          loadOwnerSubscription();
        } else {
          Toast.error('Cancellation Failed', (res && res.message) ? res.message : 'Could not cancel subscription.');
        }
      } catch(err) {
        console.error(err);
        Toast.error('Error', 'An error occurred while cancelling your subscription.');
      } finally {
        if (btn) {
          btn.disabled = false;
          btn.innerHTML = '<i class="bi bi-x-circle-fill"></i> Confirm Cancellation';
        }
      }
    }

    document.addEventListener('DOMContentLoaded', async () => {
      await NavbarComponent.render('#navbar-container', true);
      const userCtx = await AuthHelper.checkSession();
      if (!userCtx || !userCtx.user) {
        window.location.href = '/pikvero/public/login.php';
        return;
      }

      const role = userCtx.role || (userCtx.user ? userCtx.user.role_name : '');
      const perms = userCtx.permissions || [];
      const isSuperAdmin = (role === 'super_admin');

      const canViewPage = isSuperAdmin || perms.includes('subscription.view') || perms.includes('subscriptions.view') || perms.includes('subscriptionplans.view') || perms.includes('system.manage');
      if (!canViewPage) {
        window.location.href = '/pikvero/public/403.php?permission=subscriptionplans.view';
        return;
      }

      canManageSubscription = isSuperAdmin || role === 'court_owner' || perms.includes('subscriptionplans.manage') || perms.includes('subscription.manage') || perms.includes('subscriptions.manage') || perms.includes('system.manage');

      // Granular Section Visibility Toggles strictly governed by role permissions
      const canViewTiers = isSuperAdmin || perms.includes('subscriptionplans.view') || perms.includes('subscriptions.view') || perms.includes('system.manage');
      const sectionTiers = document.getElementById('section-available-tiers');
      if (sectionTiers) {
        sectionTiers.style.display = canViewTiers ? 'block' : 'none';
      }

      const canViewHistory = isSuperAdmin || perms.includes('subscriptionhistory.view') || perms.includes('system.manage');
      const sectionPayments = document.getElementById('section-payment-history');
      if (sectionPayments) {
        sectionPayments.style.display = canViewHistory ? 'block' : 'none';
      }

      SidebarComponent.render('subscriptions', 'owner');
      FooterComponent.render('#footer-container', true);

      await loadOwnerSubscription();
      await loadAvailablePlans();

      const urlParams = new URLSearchParams(window.location.search);
      if (urlParams.get('payment') === 'success') {
        const ref = urlParams.get('ref') || '';
        const planId = urlParams.get('plan_id') || urlParams.get('plan');
        const cycle  = urlParams.get('cycle') || 'monthly';
        const method = urlParams.get('method') || 'PayMongo';
        const amount = urlParams.get('amount') || '0.00';

        let planName = 'SaaS Subscription Plan';
        if (planId && allPlansList.length > 0) {
          const matchedPlan = allPlansList.find(p => String(p.id) === String(planId) || p.slug === planId);
          if (matchedPlan) planName = matchedPlan.name;
        }

        if (planId) {
          try {
            await Api.post('/pikvero/api/owner/subscription/change.php', {
              plan_id: planId,
              billing_cycle: cycle,
              payment_ref: ref,
              payment_method: method
            });
          } catch (e) {
            console.error(e);
          }
        }

        window.history.replaceState({}, document.title, window.location.pathname);
        await loadOwnerSubscription();
        await loadAvailablePlans();

        PaymentSuccessModal.show({
          title: 'PAYMENT SUCCESSFUL!',
          message: 'Your SaaS subscription plan payment has been authorized & activated successfully via PayMongo.',
          reference: ref,
          amount: amount,
          channel: method,
          planName: planName + ` (${cycle.toUpperCase()} RECURRING)`
        });
      } else if (urlParams.get('payment') === 'cancelled') {
        Toast.warning('Payment Cancelled', 'PayMongo checkout session was cancelled.');
        window.history.replaceState({}, document.title, window.location.pathname);
      } else {
        const urlPlanId = urlParams.get('plan_id');
        const urlCycle  = urlParams.get('cycle');
        if (urlPlanId) {
          openSubscribeModal(urlPlanId, urlCycle || 'monthly');
        }
      }
    });

    function toggleBillingCycle() {
      const toggle = document.getElementById('billing-cycle-toggle');
      const knob = document.getElementById('toggle-knob');
      const monthlyLabel = document.getElementById('cycle-monthly-label');
      const yearlyLabel = document.getElementById('cycle-yearly-label');

      if (toggle.checked) {
        currentBillingCycle = 'yearly';
        knob.style.left = '25px';
        monthlyLabel.style.color = '#5a7060';
        monthlyLabel.style.fontWeight = '700';
        yearlyLabel.style.color = 'var(--green)';
        yearlyLabel.style.fontWeight = '900';
      } else {
        currentBillingCycle = 'monthly';
        knob.style.left = '3px';
        monthlyLabel.style.color = 'var(--green)';
        monthlyLabel.style.fontWeight = '900';
        yearlyLabel.style.color = '#5a7060';
        yearlyLabel.style.fontWeight = '700';
      }
      renderPlansGrid();
    }

    async function loadOwnerSubscription() {
      const container = document.getElementById('current-plan-banner');
      try {
        const res = await Api.get('/pikvero/api/owner/subscription/index.php');

        if (res && res.success && res.data) {
          currentSubscriptionData = res.data;
          const sub = res.data.subscription;
          const usage = res.data.usage || { facilities:0, courts:0, staff:0 };
          const payments = res.data.payments || [];

          if (sub) {
            const isFree = parseInt(sub.is_free_trial) === 1;
            const facPct = Math.min(100, Math.round((usage.facilities / (sub.max_facilities || 1)) * 100));
            const courtPct = Math.min(100, Math.round((usage.courts / (sub.max_courts || 1)) * 100));
            const staffPct = Math.min(100, Math.round((usage.staff / (sub.max_staff || 1)) * 100));

            const isYearlySub = (sub.current_billing_cycle === 'yearly');
            const mPrice = parseFloat(sub.monthly_price) || 0;
            const yPrice = (parseFloat(sub.yearly_price) > 0) ? parseFloat(sub.yearly_price) : Math.round(mPrice * 12 * 0.70 * 100) / 100;

            const featuresList = sub.features || [];
            const perksHtml = featuresList.length > 0
              ? featuresList.map(f => `
                  <div style="font-size:0.8rem; color:#2c3e38; display:flex; gap:8px; align-items:center; font-weight:700;">
                    <i class="bi bi-check-circle-fill" style="color:var(--green); font-size:0.95rem;"></i>
                    <span>${f.feature}</span>
                  </div>
                `).join('')
              : `
                  <div style="font-size:0.8rem; color:#2c3e38; display:flex; gap:8px; align-items:center; font-weight:700;">
                    <i class="bi bi-check-circle-fill" style="color:var(--green); font-size:0.95rem;"></i>
                    <span>Max ${sub.max_facilities} Facility Slot</span>
                  </div>
                  <div style="font-size:0.8rem; color:#2c3e38; display:flex; gap:8px; align-items:center; font-weight:700;">
                    <i class="bi bi-check-circle-fill" style="color:var(--green); font-size:0.95rem;"></i>
                    <span>Up to ${sub.max_courts} Court Slots</span>
                  </div>
                  <div style="font-size:0.8rem; color:#2c3e38; display:flex; gap:8px; align-items:center; font-weight:700;">
                    <i class="bi bi-check-circle-fill" style="color:var(--green); font-size:0.95rem;"></i>
                    <span>${sub.max_staff} Staff Member Licenses</span>
                  </div>
                  <div style="font-size:0.8rem; color:#2c3e38; display:flex; gap:8px; align-items:center; font-weight:700;">
                    <i class="bi bi-check-circle-fill" style="color:var(--green); font-size:0.95rem;"></i>
                    <span>Online Court Booking Engine</span>
                  </div>
                `;

            container.innerHTML = `
              <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:20px; flex-wrap:wrap; gap:12px;">
                <div>
                  <div style="display:flex; gap:8px; align-items:center; margin-bottom:4px; flex-wrap:wrap;">
                    <span class="badge-streetside ${sub.status === 'active' ? 'green' : 'coral'}">${sub.status.toUpperCase()}</span>
                    <span class="badge-streetside ${isYearlySub ? 'coral' : 'sand'}">${isYearlySub ? 'ANNUAL BILLING (SAVE 30%)' : 'MONTHLY BILLING'}</span>
                    ${isFree ? `<span class="badge-streetside lime"><i class="bi bi-gift-fill"></i> FREE TRIAL PROMO</span>` : ''}
                  </div>
                  <h2 style="font-size:1.8rem; font-weight:900; text-transform:uppercase; margin:0 0 4px; color:var(--ink);">
                    ${sub.plan_name}
                  </h2>
                  <div style="font-size:0.85rem; color:#4a5c56; max-width:600px; line-height:1.4;">
                    ${sub.plan_description || 'Ideal for boutique single-venue court owners.'}
                  </div>
                </div>

                <div style="text-align:right;">
                  <div style="font-size:1.8rem; font-weight:900; color:var(--green); font-family:'DM Mono', monospace;">
                    ${isFree ? 'FREE TRIAL' : (isYearlySub ? `₱${yPrice.toLocaleString('en-US', {minimumFractionDigits:2})}/yr` : `₱${mPrice.toLocaleString('en-US', {minimumFractionDigits:2})}/mo`)}
                  </div>
                  ${(!isFree && isYearlySub) ? `<div style="font-size:0.72rem; color:#5a7060; font-family:'DM Mono', monospace;">(₱${(yPrice/12).toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2})}/mo)</div>` : ''}
                  <div style="font-size:0.75rem; color:#5a7060; font-family:'DM Mono', monospace; margin-top:2px;">
                    Current Period Ends: <strong>${sub.current_period_end || 'N/A'}</strong>
                  </div>
                </div>
              </div>

              <!-- Included Plan Perks & Features Grid -->
              <div style="margin-bottom:20px; background:var(--cream); border:2px solid var(--ink); border-radius:12px; padding:18px;">
                <div style="font-size:0.78rem; font-family:'DM Mono', monospace; font-weight:900; color:var(--green); text-transform:uppercase; margin-bottom:12px; display:flex; align-items:center; gap:6px;">
                  <i class="bi bi-award-fill" style="color:var(--coral);"></i> INCLUDED PLAN PERKS &amp; FEATURES
                </div>
                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:12px;">
                  ${perksHtml}
                </div>
              </div>

              <!-- Usage Progress Limits -->
              <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:16px; background:#f8faf9; border:2px solid var(--ink); border-radius:12px; padding:16px; margin-bottom:20px;">
                <div>
                  <div style="display:flex; justify-content:space-between; font-size:0.78rem; font-family:'DM Mono', monospace; font-weight:800;">
                    <span>FACILITIES</span>
                    <span style="color:var(--green);">${usage.facilities} / ${sub.max_facilities}</span>
                  </div>
                  <div class="usage-progress-bar"><div class="usage-progress-fill" style="width:${facPct}%;"></div></div>
                </div>

                <div>
                  <div style="display:flex; justify-content:space-between; font-size:0.78rem; font-family:'DM Mono', monospace; font-weight:800;">
                    <span>COURTS</span>
                    <span style="color:var(--green);">${usage.courts} / ${sub.max_courts}</span>
                  </div>
                  <div class="usage-progress-bar"><div class="usage-progress-fill" style="width:${courtPct}%;"></div></div>
                </div>

                <div>
                  <div style="display:flex; justify-content:space-between; font-size:0.78rem; font-family:'DM Mono', monospace; font-weight:800;">
                    <span>STAFF MEMBERS</span>
                    <span style="color:var(--green);">${usage.staff} / ${sub.max_staff}</span>
                  </div>
                  <div class="usage-progress-bar"><div class="usage-progress-fill" style="width:${staffPct}%;"></div></div>
                </div>
              </div>

              <!-- Next Renewal Cycle & Pay Now Bar -->
              <div style="background:#f4f7f5; border:2px solid var(--ink); border-radius:12px; padding:18px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:14px;">
                <div>
                  <div style="font-size:0.75rem; font-family:'DM Mono', monospace; font-weight:900; color:#5a7060; text-transform:uppercase;">
                    NEXT RENEWAL BILLING CYCLE
                  </div>
                  <div style="font-size:0.95rem; font-weight:800; color:var(--ink); margin-top:2px;">
                    ${isYearlySub ? 'Annual Auto-Renewal' : 'Monthly Auto-Renewal'} &bull; Period Ends <strong style="color:var(--green);">${sub.current_period_end || 'N/A'}</strong>
                  </div>
                  <div style="font-size:0.78rem; color:#5a7060; margin-top:2px;">
                    Upcoming Charge: <strong>₱${(isFree ? 0 : (isYearlySub ? yPrice : mPrice)).toLocaleString('en-US', {minimumFractionDigits:2})}</strong> on ${sub.current_period_end || 'N/A'}
                  </div>
                </div>

                <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                  <button onclick="triggerPayNowModal()" class="button lime" style="padding:9px 18px; font-size:0.82rem;">
                    <i class="bi bi-credit-card-fill"></i> Pay / Renew Subscription Now
                  </button>
                  <button onclick="openUpgradePlanSelectorModal()" class="button sand" style="padding:9px 16px; font-size:0.82rem;">
                    <i class="bi bi-rocket-takeoff-fill"></i> Upgrade / Change Plan
                  </button>
                  ${sub.status === 'active' ? `
                    <button onclick="openCancelSubscriptionModal()" class="button coral" style="padding:9px 14px; font-size:0.82rem;">
                      <i class="bi bi-x-circle-fill"></i> Cancel Subscription
                    </button>
                  ` : ''}
                </div>
              </div>
            `;
          } else {
            container.innerHTML = `
              <div style="text-align:center; padding:16px;">
                <span class="badge-streetside coral" style="margin-bottom:8px;">NO ACTIVE SUBSCRIPTION</span>
                <h3 style="font-size:1.3rem; font-weight:800; text-transform:uppercase; margin:4px 0 8px;">YOU DO NOT HAVE AN ACTIVE SUBSCRIPTION PLAN</h3>
                <p style="font-size:0.85rem; color:#4a5c56; margin-bottom:16px;">Please choose a subscription plan tier below to start managing facilities, courts, and receiving player bookings.</p>
              </div>
            `;
          }

          // Initialize server-side DataTables for payment history
          initPaymentsDataTable();
        } else {
          container.innerHTML = `
            <div style="text-align:center; padding:16px;">
              <span class="badge-streetside coral" style="margin-bottom:8px;">NO ACTIVE SUBSCRIPTION</span>
              <h3 style="font-size:1.3rem; font-weight:800; text-transform:uppercase; margin:4px 0 8px;">NO SUBSCRIPTION FOUND</h3>
              <p style="font-size:0.85rem; color:#4a5c56; margin-bottom:16px;">Choose a subscription plan tier below to activate your organization subscription.</p>
            </div>
          `;
          initPaymentsDataTable();
        }
      } catch(err) {
        console.error(err);
        if (container) {
          container.innerHTML = `
            <div style="text-align:center; padding:16px;">
              <span class="badge-streetside coral" style="margin-bottom:8px;">NO ACTIVE SUBSCRIPTION</span>
              <h3 style="font-size:1.3rem; font-weight:800; text-transform:uppercase; margin:4px 0 8px;">NO ACTIVE PLAN</h3>
              <p style="font-size:0.85rem; color:#4a5c56; margin-bottom:16px;">Select any subscription plan tier below to activate court management.</p>
            </div>
          `;
        }
        initPaymentsDataTable();
      }
    }

    let paymentsDataTable = null;

    function initPaymentsDataTable() {
      if (paymentsDataTable) {
        paymentsDataTable.ajax.reload(null, false);
        return;
      }

      paymentsDataTable = $('#payments-history-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
          url: '/pikvero/api/owner/subscription/payments.php',
          type: 'GET'
        },
        columns: [
          {
            data: 'created_at',
            render: function(data) {
              return `<span style="font-family:'DM Mono', monospace;">${data || '—'}</span>`;
            }
          },
          {
            data: 'amount',
            render: function(data) {
              const amt = parseFloat(data || 0).toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2});
              return `<span style="font-family:'DM Mono', monospace; font-weight:800; color:var(--green);">₱${amt}</span>`;
            }
          },
          {
            data: 'payment_method',
            render: function(data) {
              return `<span class="badge-streetside sand">${data || 'PayMongo'}</span>`;
            }
          },
          {
            data: 'payment_status',
            render: function(data) {
              const st = (data || 'PAID').toUpperCase();
              const badgeClass = (st === 'PAID' || st === 'COMPLETED') ? 'green' : 'coral';
              return `<span class="badge-streetside ${badgeClass}">${st}</span>`;
            }
          },
          {
            data: null,
            orderable: false,
            className: 'text-right',
            render: function(data, type, row) {
              const pid = row.id || row.payment_id;
              return `
                <div style="display:flex; justify-content:flex-end; gap:4px;">
                  <button onclick="openViewSubModalFromHistory(${pid})" class="button lime action-btn" style="padding:3px 8px; font-size:0.72rem;" title="View Voucher"><i class="bi bi-eye"></i> View</button>
                  <button onclick="printSelectedSubReceipt(${pid})" class="button sand action-btn" style="padding:3px 8px; font-size:0.72rem;" title="Print Receipt"><i class="bi bi-printer"></i> Receipt</button>
                </div>
              `;
            }
          }
        ],
        order: [[0, 'desc']],
        pageLength: 10,
        lengthMenu: [5, 10, 25, 50],
        language: {
          search: "FILTER TRANSACTIONS:",
          lengthMenu: "SHOW _MENU_ RECORDS",
          info: "SHOWING _START_ TO _END_ OF _TOTAL_ PAYMENTS",
          infoEmpty: "NO PAYMENT RECORDS FOUND",
          emptyTable: "No subscription payment transactions recorded yet."
        },
        drawCallback: function(settings) {
          const api = this.api();
          const info = api.page.info();
          const badge = document.getElementById('payments-count-badge');
          if (badge) {
            badge.textContent = `${info.recordsTotal} Payments`;
          }
        }
      });
    }

    let activeSubPaymentId = null;

    async function openViewSubModalFromHistory(paymentId) {
      activeSubPaymentId = paymentId;
      document.getElementById('view-sub-content').innerHTML = `
        <div style="text-align:center; padding:30px; font-family:'DM Mono', monospace; font-weight:700;">
          <i class="bi bi-arrow-repeat spin" style="font-size:1.5rem;"></i> Loading subscription voucher details...
        </div>
      `;
      document.getElementById('view-sub-modal').style.display = 'grid';

      try {
        const res = await Api.get('/pikvero/api/admin/subscription-payments/detail.php', { id: paymentId });
        if (res.success && res.data) {
          const d = res.data;
          const st = (d.payment_status || 'paid').toLowerCase();
          const isRefunded = (st === 'failed' || st === 'refunded' || st === 'cancelled');

          document.getElementById('view-sub-content').innerHTML = `
            <!-- HEADER INFO -->
            <div style="background:#f8faf9; border:2px solid var(--ink); border-radius:10px; padding:14px; margin-bottom:14px;">
              <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                <div>
                  <div style="font-size:0.7rem; font-weight:800; font-family:'DM Mono', monospace; color:#4a5c56;">TRANSACTION REF</div>
                  <div style="font-weight:800; font-family:'DM Mono', monospace; font-size:1.05rem; color:var(--ink);">${d.transaction_reference || ('SUB-' + d.payment_id)}</div>
                </div>
                <div style="text-align:right;">
                  <div style="font-size:0.7rem; font-weight:800; font-family:'DM Mono', monospace; color:#4a5c56;">PLAN NAME</div>
                  <div style="font-weight:800; font-family:'DM Mono', monospace; font-size:1.05rem; color:#2563eb;">${d.plan_name || 'Subscription Plan'}</div>
                </div>
              </div>
              <div style="display:flex; justify-content:space-between; align-items:center; border-top:1px dashed var(--line); padding-top:8px; font-size:0.78rem;">
                <span>Payment Date: <strong>${d.payment_date || d.created_at || '—'}</strong></span>
                <span>
                  <span class="badge-streetside ${isRefunded ? 'coral' : 'lime'}" style="font-size:0.68rem;">${isRefunded ? 'FAILED / CANCELLED' : 'PAID'}</span>
                </span>
              </div>
            </div>

            <!-- DETAILS GRID -->
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px; font-size:0.83rem; margin-bottom:14px;">
              <div style="background:#fdfdfd; padding:10px; border-radius:8px; border:1px solid var(--line);">
                <div style="font-size:0.7rem; font-weight:800; font-family:'DM Mono', monospace; color:#4a5c56;">ORGANIZATION TENANT</div>
                <div style="font-weight:800; margin-top:2px;">${d.organization_name || 'My Organization'}</div>
                <div style="font-size:0.75rem; color:#4a5c56;">Tax ID: ${d.organization_tax_id || 'N/A'}</div>
              </div>

              <div style="background:#fdfdfd; padding:10px; border-radius:8px; border:1px solid var(--line);">
                <div style="font-size:0.7rem; font-weight:800; font-family:'DM Mono', monospace; color:#4a5c56;">COURT OWNER</div>
                <div style="font-weight:800; margin-top:2px;">${d.first_name ? d.first_name + ' ' + d.last_name : 'Owner'}</div>
                <div style="font-size:0.75rem; color:#4a5c56;">${d.owner_email || 'N/A'}</div>
              </div>

              <div style="background:#fdfdfd; padding:10px; border-radius:8px; border:1px solid var(--line);">
                <div style="font-size:0.7rem; font-weight:800; font-family:'DM Mono', monospace; color:#4a5c56;">BILLING CYCLE</div>
                <div style="font-weight:800; margin-top:2px; text-transform:uppercase;">${d.billing_cycle || 'Monthly'}</div>
                <div style="font-size:0.75rem; color:#4a5c56;">Period End: <strong>${d.current_period_end || 'Auto-renew'}</strong></div>
              </div>

              <div style="background:#fdfdfd; padding:10px; border-radius:8px; border:1px solid var(--line);">
                <div style="font-size:0.7rem; font-weight:800; font-family:'DM Mono', monospace; color:#4a5c56;">PAYMENT METHOD</div>
                <div style="font-weight:800; margin-top:2px; text-transform:uppercase;">${d.payment_method || 'PayMongo'}</div>
                <div style="font-size:0.75rem; color:#4a5c56;">Sub Status: <strong>${(d.subscription_status || 'active').toUpperCase()}</strong></div>
              </div>
            </div>

            <!-- TOTAL AMOUNT -->
            <div style="background:#eafc8d; border:2px solid var(--ink); border-radius:10px; padding:14px; text-align:center;">
              <div style="font-size:0.72rem; font-weight:800; font-family:'DM Mono', monospace; color:var(--ink);">TOTAL SUBSCRIPTION AMOUNT PAID</div>
              <div style="font-size:1.8rem; font-weight:800; margin-top:2px; color:var(--ink);">₱${parseFloat(d.amount || 0).toFixed(2)}</div>
            </div>
          `;
        }
      } catch (err) {
        console.error(err);
      }
    }

    function printSelectedSubReceipt(paymentId) {
      const pid = paymentId || activeSubPaymentId;
      if (pid) {
        window.open(`/pikvero/public/subscription-receipt.php?payment_id=${pid}`, '_blank');
      }
    }

    function closeModal(id) {
      const modal = document.getElementById(id);
      if (modal) modal.style.display = 'none';
    }

    async function loadAvailablePlans() {
      try {
        const res = await Api.get('/pikvero/api/admin/subscriptions/plans.php');
        if (res && res.success && res.data) {
          allPlansList = res.data;
          renderPlansGrid();
        }
      } catch(err) { console.error(err); }
    }

    function renderPlansGrid() {
      const container = document.getElementById('subscription-plans-grid');
      const activeSub = currentSubscriptionData ? currentSubscriptionData.subscription : null;
      const activePlanId = activeSub ? parseInt(activeSub.plan_id) : 0;

      if (!allPlansList || allPlansList.length === 0) {
        container.innerHTML = `<div style="grid-column:1/-1; text-align:center; color:#5a7060; padding:20px;">No platform subscription plans configured yet.</div>`;
        return;
      }

      container.innerHTML = allPlansList.map(p => {
        const isCurrent = (parseInt(p.id) === activePlanId);
        const isFree = parseInt(p.is_free_trial) === 1;
        const trialMonths = p.trial_duration_months || 1;
        const features = p.features || [];

        const perksListHtml = features.length > 0
          ? features.map(f => `
              <div class="plan-perk-item">
                <i class="bi bi-check-circle-fill"></i>
                <span>${f.feature}</span>
              </div>
            `).join('')
          : `<div style="font-size:0.78rem; color:#888; font-style:italic;">No custom perks configured.</div>`;

        let priceDisplay = '';
        if (isFree) {
          priceDisplay = `<div style="font-size:1.9rem; font-weight:900; color:var(--green); font-family:'DM Mono', monospace; line-height:1.1;"><span style="color:var(--green);">FREE TRIAL</span> <span style="font-size:0.85rem; color:#4a5c56; font-weight:700;">(${trialMonths} ${trialMonths > 1 ? 'Months' : 'Month'})</span></div>`;
        } else if (currentBillingCycle === 'yearly') {
          const monthlyPrice = parseFloat(p.monthly_price) || 0;
          const yearlyPrice = (parseFloat(p.yearly_price) > 0) ? parseFloat(p.yearly_price) : Math.round(monthlyPrice * 12 * 0.70 * 100) / 100;
          const equivMonthly = yearlyPrice / 12;

          priceDisplay = `
            <div style="font-size:1.8rem; font-weight:900; color:var(--green); font-family:'DM Mono', monospace; line-height:1.1;">
              ₱${yearlyPrice.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2})}
              <span style="font-size:0.85rem; color:#4a5c56; font-weight:700;">/ yr</span>
              <span class="badge-streetside coral" style="font-size:0.65rem; vertical-align:middle; margin-left:4px;">SAVE 30%</span>
            </div>
            <div style="font-size:0.75rem; color:#5a7060; font-family:'DM Mono', monospace; margin-top:2px;">
              Equivalent to <strong>₱${equivMonthly.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2})}/mo</strong>
            </div>
          `;
        } else {
          priceDisplay = `
            <div style="font-size:1.9rem; font-weight:900; color:var(--green); font-family:'DM Mono', monospace; line-height:1.1;">
              ₱${parseFloat(p.monthly_price).toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2})}
              <span style="font-size:0.85rem; color:#4a5c56; font-weight:700;">/ mo</span>
            </div>
          `;
        }

        let actionButtonHtml = '';
        if (isCurrent) {
          actionButtonHtml = `
            <button class="button green" style="width:100%; padding:10px; font-size:0.82rem; cursor:default;" disabled>
              <i class="bi bi-check-circle-fill"></i> CURRENT ACTIVE PLAN
            </button>
          `;
        } else if (canManageSubscription) {
          actionButtonHtml = `
            <button onclick="openSubscribeModal(${p.id}, '${currentBillingCycle}')" class="button coral" style="width:100%; padding:10px; font-size:0.82rem;">
              <i class="bi bi-rocket-takeoff-fill"></i> ${isFree ? 'Claim Free Trial' : 'Subscribe / Upgrade Plan'}
            </button>
          `;
        }

        return `
          <div class="plan-card-streetside ${isCurrent ? 'is-current' : ''}">
            <div>
              <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; flex-wrap:wrap; gap:4px;">
                <div style="display:flex; gap:6px; align-items:center; flex-wrap:wrap;">
                  <span class="badge-streetside coral">${p.name.toUpperCase()}</span>
                  ${isFree ? `<span class="badge-streetside green"><i class="bi bi-gift-fill"></i> ${trialMonths} MOS FREE</span>` : ''}
                </div>
                ${isCurrent ? `<span class="badge-streetside green">ACTIVE</span>` : ''}
              </div>

              <div style="margin-bottom:14px;">
                ${priceDisplay}
                ${p.description ? `<p style="font-size:0.82rem; color:#4a5c56; margin:8px 0 0; line-height:1.4;">${p.description}</p>` : ''}
              </div>

              <div style="display:flex; gap:6px; flex-wrap:wrap; margin-bottom:16px; padding-bottom:14px; border-bottom:2px solid var(--line);">
                <span class="badge-streetside sand" title="Max Facilities"><i class="bi bi-building-fill"></i> ${p.max_facilities} Facilities</span>
                <span class="badge-streetside sky" title="Max Courts"><i class="bi bi-layers-fill"></i> ${p.max_courts} Courts</span>
                <span class="badge-streetside lime" title="Max Staff"><i class="bi bi-person-badge-fill"></i> ${p.max_staff} Staff</span>
              </div>

              <div style="margin-bottom:16px;">
                <div style="font-size:0.72rem; font-family:'DM Mono', monospace; font-weight:900; text-transform:uppercase; color:#4a5c56; margin-bottom:8px;">INCLUDED PERKS</div>
                ${perksListHtml}
              </div>
            </div>

            <div style="padding-top:14px; border-top:2px solid var(--line);">
              ${actionButtonHtml}
            </div>
          </div>
        `;
      }).join('');
    }

    function openSubscribeModal(planId, cycle = null) {
      if (cycle) {
        currentBillingCycle = cycle;
      }
      if (!canManageSubscription) {
        Toast.error('Access Denied', 'Permission required.');
        return;
      }

      const plan = allPlansList.find(p => parseInt(p.id) === parseInt(planId));
      if (!plan) return;

      document.getElementById('sub_plan_id').value = planId;
      document.getElementById('sub-modal-plan-name').textContent = plan.name;
      document.getElementById('sub-modal-cycle-badge').textContent = (currentBillingCycle === 'yearly') ? 'ANNUAL BILLING (SAVE 30%)' : 'MONTHLY RECURRING';

      const isFree = parseInt(plan.is_free_trial) === 1;
      const monthlyPrice = parseFloat(plan.monthly_price) || 0;
      const yearlyPrice  = (parseFloat(plan.yearly_price) > 0) ? parseFloat(plan.yearly_price) : Math.round(monthlyPrice * 12 * 0.70 * 100) / 100;
      const basePrice    = isFree ? 0 : ((currentBillingCycle === 'yearly') ? yearlyPrice : monthlyPrice);

      const platformFee = Math.round((basePrice * 0.02) * 100) / 100;
      const gatewayFee  = Math.round((basePrice * 0.025) * 100) / 100;
      const totalAmount = basePrice + platformFee + gatewayFee;

      document.getElementById('sub-modal-base-price').textContent = `₱${basePrice.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2})}`;
      document.getElementById('sub-modal-platform-fee').textContent = `₱${platformFee.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2})}`;
      document.getElementById('sub-modal-gateway-fee').textContent = `₱${gatewayFee.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2})}`;
      document.getElementById('sub-modal-total-amount').textContent = `₱${totalAmount.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2})}`;

      document.body.style.overflow = 'hidden';
      document.getElementById('subscribe-modal').style.display = 'block';
    }

    function closeSubscribeModal() {
      document.body.style.overflow = '';
      document.getElementById('subscribe-modal').style.display = 'none';
    }

    async function processSubscriptionChangePayment() {
      const planId = document.getElementById('sub_plan_id').value;
      if (!planId) return;

      const selectedPayOption = document.querySelector('input[name="modal_paymethod"]:checked')?.value || 'gcash';
      const btn = document.getElementById('btn-modal-pay-now');
      const origHtml = btn.innerHTML;
      btn.disabled = true;
      btn.innerHTML = `<i class="bi bi-hourglass-split"></i> Redirecting to PayMongo...`;

      const redirectUrl = window.location.origin + window.location.pathname;

      try {
        const res = await Api.post('/pikvero/api/payments/paymongo-checkout.php', {
          plan_id: planId,
          billing_cycle: currentBillingCycle,
          payment_method: selectedPayOption,
          redirect_url: redirectUrl
        });

        if (res.success && res.data && res.data.checkout_url) {
          Toast.info('PayMongo Checkout', 'Redirecting to official PayMongo payment portal...');
          closeSubscribeModal();
          setTimeout(() => {
            window.location.href = res.data.checkout_url;
          }, 600);
        } else {
          Toast.error('PayMongo Gateway Error', res.message || 'Failed to initialize PayMongo checkout session.');
          btn.disabled = false;
          btn.innerHTML = origHtml;
        }
      } catch (err) {
        console.error(err);
        Toast.error('Connection Error', 'Failed to connect to PayMongo API.');
        btn.disabled = false;
        btn.innerHTML = origHtml;
      }
    }
  </script>
</body>
</html>
