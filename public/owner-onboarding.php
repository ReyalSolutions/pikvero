<?php
require_once __DIR__ . '/../app/bootstrap.php';
use App\Core\Auth\Session;
use App\Core\Database\Connection;
use App\Infrastructure\Repositories\SystemSettingRepository;

if (!Session::has('csrf_token')) {
    Session::set('csrf_token', bin2hex(random_bytes(32)));
}
$csrfToken = Session::get('csrf_token');

$db = Connection::getInstance();
$subscriptionPlans = $db->select("SELECT * FROM subscription_plans ORDER BY monthly_price ASC");
foreach ($subscriptionPlans as &$p) {
    $p['features'] = array_column($db->select("SELECT feature FROM subscription_plan_features WHERE plan_id = ?", [(int)$p['id']], 'i'), 'feature');
}
unset($p);

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
foreach ($paymentChannelsMap as $settingKey => $channelInfo) {
    if (isset($allSettings[$settingKey]) && $allSettings[$settingKey] === '1') {
        $enabledPaymentMethods[] = $channelInfo;
    }
}
if (empty($enabledPaymentMethods)) {
    $enabledPaymentMethods[] = $paymentChannelsMap['paymongo_enable_gcash'];
    $enabledPaymentMethods[] = $paymentChannelsMap['paymongo_enable_cards'];
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Pikvero — Court Owner Onboarding Wizard</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="/pikvero/assets/css/streetside-theme.css?v=3">
  <link rel="stylesheet" href="/pikvero/assets/css/toast.css">
  <style>
    .stepper-container {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(64px, 1fr));
      gap: 8px;
      margin-bottom: 20px;
    }
    .step-pill {
      background: var(--white);
      border: 2px solid var(--ink);
      border-radius: 12px;
      padding: 8px 4px;
      text-align: center;
      cursor: pointer;
      transition: all 0.2s ease;
      box-shadow: 2px 2px 0 var(--ink);
    }
    .step-pill.active {
      background: var(--coral);
      color: var(--white);
      transform: translateY(-2px);
      box-shadow: 3px 3px 0 var(--ink);
    }
    .step-pill.completed {
      background: var(--lime);
      color: var(--ink);
    }
    .step-pill-num {
      font-weight: 800;
      font-size: 0.88rem;
      display: block;
    }
    .step-pill-name {
      font-family: 'DM Mono', monospace;
      font-size: 0.62rem;
      display: block;
      text-transform: uppercase;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .progress-track {
      width: 100%;
      height: 10px;
      background: var(--sand);
      border: 2px solid var(--ink);
      border-radius: 10px;
      overflow: hidden;
      margin-bottom: 20px;
    }
    .progress-fill {
      height: 100%;
      background: var(--lime);
      width: 10%;
      transition: width 0.3s ease;
    }
    .wizard-step {
      display: none;
    }
    .wizard-step.active {
      display: block;
    }
    .inline-feedback {
      font-family: 'DM Mono', monospace;
      font-size: 0.72rem;
      font-weight: 700;
      margin-top: 4px;
      min-height: 16px;
    }
    .inline-feedback.valid {
      color: #10b981;
    }
    .inline-feedback.invalid {
      color: #ef4444;
    }
    input.is-valid, select.is-valid {
      border-color: #10b981 !important;
    }
    input.is-invalid, select.is-invalid {
      border-color: #ef4444 !important;
    }
  </style>
</head>
<body>

  <?php require_once __DIR__ . '/../includes/header.php'; ?>

  <div style="padding:110px max(4vw, 20px) 60px; max-width:860px; margin:0 auto;">
    <div style="margin-bottom:20px; display:flex; justify-content:space-between; align-items:flex-end; flex-wrap:wrap; gap:12px;">
      <div>
        <div class="eyebrow">SAAS PLATFORM ONBOARDING</div>
        <h1 style="font-size:clamp(1.8rem, 4vw, 2.6rem); font-weight:800; text-transform:uppercase; margin:4px 0 0;">BECOME A COURT OWNER</h1>
        <p style="font-size:0.88rem; color:#3b4e48; margin:2px 0 0;">Complete the 10-step wizard to register your facility and publish courts for bookings.</p>
      </div>
      <button type="button" onclick="clearOnboardingDraft()" class="button sand" style="padding:6px 12px; font-size:0.75rem; color:var(--ink);">
        <i class="bi bi-trash"></i> Reset Draft
      </button>
    </div>

    <!-- Overall Progress Header -->
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px; font-family:'DM Mono', monospace; font-size:0.75rem; font-weight:700;">
      <span id="progress-step-text">STEP 1 OF 10: OWNER ACCOUNT SETUP</span>
      <span id="progress-percent-text">10% COMPLETE</span>
    </div>
    <div class="progress-track">
      <div id="progress-fill" class="progress-fill"></div>
    </div>

    <!-- Stepper 10-Step Grid Pills -->
    <div class="stepper-container" id="stepper-pills">
      <div class="step-pill active" onclick="jumpToStep(1)" id="pill-1">
        <span class="step-pill-num">1</span>
        <span class="step-pill-name">Account</span>
      </div>
      <div class="step-pill" onclick="jumpToStep(2)" id="pill-2">
        <span class="step-pill-num">2</span>
        <span class="step-pill-name">Business</span>
      </div>
      <div class="step-pill" onclick="jumpToStep(3)" id="pill-3">
        <span class="step-pill-num">3</span>
        <span class="step-pill-name">Facility</span>
      </div>
      <div class="step-pill" onclick="jumpToStep(4)" id="pill-4">
        <span class="step-pill-num">4</span>
        <span class="step-pill-name">Docs</span>
      </div>
      <div class="step-pill" onclick="jumpToStep(5)" id="pill-5">
        <span class="step-pill-num">5</span>
        <span class="step-pill-name">Courts</span>
      </div>
      <div class="step-pill" onclick="jumpToStep(6)" id="pill-6">
        <span class="step-pill-num">6</span>
        <span class="step-pill-name">Pricing</span>
      </div>
      <div class="step-pill" onclick="jumpToStep(7)" id="pill-7">
        <span class="step-pill-num">7</span>
        <span class="step-pill-name">Hours</span>
      </div>
      <div class="step-pill" onclick="jumpToStep(8)" id="pill-8">
        <span class="step-pill-num">8</span>
        <span class="step-pill-name">Plan</span>
      </div>
      <div class="step-pill" onclick="jumpToStep(9)" id="pill-9">
        <span class="step-pill-num">9</span>
        <span class="step-pill-name">Payment</span>
      </div>
      <div class="step-pill" onclick="jumpToStep(10)" id="pill-10">
        <span class="step-pill-num">10</span>
        <span class="step-pill-name">Submit</span>
      </div>
    </div>

    <!-- Wizard Card Container -->
    <div class="card-streetside" style="padding:32px; background:var(--white);">
      <form id="onboarding-wizard-form" novalidate>
        <input type="hidden" id="csrf_token" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

        <!-- STEP 1: CREATE ACCOUNT -->
        <div class="wizard-step active" id="step-1">
          <h3 style="font-size:1.2rem; font-weight:800; text-transform:uppercase; margin:0 0 14px;"><i class="bi bi-person-circle"></i> STEP 1: OWNER ACCOUNT SETUP</h3>
          <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:12px;">
            <div>
              <label class="mono" style="display:block; margin-bottom:4px; font-size:0.75rem;">FIRST NAME *</label>
              <input type="text" id="ob-fname" placeholder="Marcus" style="width:100%; padding:10px; border:2px solid var(--ink); border-radius:10px; font-family:inherit; font-weight:700;">
              <div id="fb-fname" class="inline-feedback"></div>
            </div>
            <div>
              <label class="mono" style="display:block; margin-bottom:4px; font-size:0.75rem;">LAST NAME *</label>
              <input type="text" id="ob-lname" placeholder="Vance" style="width:100%; padding:10px; border:2px solid var(--ink); border-radius:10px; font-family:inherit; font-weight:700;">
              <div id="fb-lname" class="inline-feedback"></div>
            </div>
          </div>

          <div style="margin-bottom:12px;">
            <label class="mono" style="display:block; margin-bottom:4px; font-size:0.75rem;">USERNAME *</label>
            <input type="text" id="ob-username" placeholder="owner" style="width:100%; padding:10px; border:2px solid var(--ink); border-radius:10px; font-family:inherit; font-weight:700;">
            <div id="fb-username" class="inline-feedback"></div>
          </div>

          <div style="margin-bottom:12px;">
            <label class="mono" style="display:block; margin-bottom:4px; font-size:0.75rem;">EMAIL ADDRESS *</label>
            <input type="email" id="ob-email" placeholder="owner@smashzone.com" style="width:100%; padding:10px; border:2px solid var(--ink); border-radius:10px; font-family:inherit; font-weight:700;">
            <div id="fb-email" class="inline-feedback"></div>
          </div>

          <div style="margin-bottom:12px;">
            <label class="mono" style="display:block; margin-bottom:4px; font-size:0.75rem;">PHONE NUMBER (11 DIGITS) *</label>
            <input type="text" id="ob-phone" maxlength="11" placeholder="09181112222" style="width:100%; padding:10px; border:2px solid var(--ink); border-radius:10px; font-family:inherit; font-weight:700;">
            <div id="fb-phone" class="inline-feedback"></div>
          </div>

          <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:16px;">
            <div>
              <label class="mono" style="display:block; margin-bottom:4px; font-size:0.75rem;">PASSWORD *</label>
              <div style="position:relative;">
                <input type="password" id="ob-pass" autocomplete="new-password" placeholder="••••••••" style="width:100%; padding:10px 36px 10px 10px; border:2px solid var(--ink); border-radius:10px; font-family:inherit; font-weight:700;">
                <i class="bi bi-eye-slash-fill" id="toggle-ob-pass" onclick="togglePassVisibility('ob-pass', 'toggle-ob-pass')" style="position:absolute; right:12px; top:50%; transform:translateY(-50%); cursor:pointer; color:#6b7c76;"></i>
              </div>
              <div id="fb-pass" class="inline-feedback"></div>
            </div>
            <div>
              <label class="mono" style="display:block; margin-bottom:4px; font-size:0.75rem;">CONFIRM PASSWORD *</label>
              <div style="position:relative;">
                <input type="password" id="ob-cpass" autocomplete="new-password" placeholder="••••••••" style="width:100%; padding:10px 36px 10px 10px; border:2px solid var(--ink); border-radius:10px; font-family:inherit; font-weight:700;">
                <i class="bi bi-eye-slash-fill" id="toggle-ob-cpass" onclick="togglePassVisibility('ob-cpass', 'toggle-ob-cpass')" style="position:absolute; right:12px; top:50%; transform:translateY(-50%); cursor:pointer; color:#6b7c76;"></i>
              </div>
              <div id="fb-cpass" class="inline-feedback"></div>
            </div>
          </div>
        </div>

        <!-- STEP 2: BUSINESS INFORMATION -->
        <div class="wizard-step" id="step-2">
          <h3 style="font-size:1.2rem; font-weight:800; text-transform:uppercase; margin:0 0 14px;"><i class="bi bi-briefcase-fill"></i> STEP 2: BUSINESS INFORMATION</h3>
          <div style="margin-bottom:12px;">
            <label class="mono" style="display:block; margin-bottom:4px; font-size:0.75rem;">ORGANIZATION / CLUB NAME *</label>
            <input type="text" id="ob-orgname" placeholder="SmashZone Pickleball Club" style="width:100%; padding:10px; border:2px solid var(--ink); border-radius:10px; font-family:inherit; font-weight:700;">
            <div id="fb-orgname" class="inline-feedback"></div>
          </div>
          <div style="margin-bottom:16px;">
            <label class="mono" style="display:block; margin-bottom:4px; font-size:0.75rem;">BUSINESS PERMIT / DTI / SEC REGISTRATION # *</label>
            <input type="text" id="ob-taxid" placeholder="DTI-2026-998877" style="width:100%; padding:10px; border:2px solid var(--ink); border-radius:10px; font-family:inherit; font-weight:700;">
            <div id="fb-taxid" class="inline-feedback"></div>
          </div>
        </div>

        <!-- STEP 3: FACILITY INFORMATION -->
        <div class="wizard-step" id="step-3">
          <h3 style="font-size:1.2rem; font-weight:800; text-transform:uppercase; margin:0 0 14px;"><i class="bi bi-building"></i> STEP 3: FACILITY DETAILS</h3>
          <div style="margin-bottom:12px;">
            <label class="mono" style="display:block; margin-bottom:4px; font-size:0.75rem;">FACILITY NAME *</label>
            <input type="text" id="ob-facname" placeholder="SmashZone Pickleball Center" style="width:100%; padding:10px; border:2px solid var(--ink); border-radius:10px; font-family:inherit; font-weight:700;">
            <div id="fb-facname" class="inline-feedback"></div>
          </div>
          <div style="margin-bottom:12px;">
            <label class="mono" style="display:block; margin-bottom:4px; font-size:0.75rem;">STREET ADDRESS *</label>
            <input type="text" id="ob-address" placeholder="CPG North Avenue" style="width:100%; padding:10px; border:2px solid var(--ink); border-radius:10px; font-family:inherit; font-weight:700;">
            <div id="fb-address" class="inline-feedback"></div>
          </div>
          <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:16px;">
            <div>
              <label class="mono" style="display:block; margin-bottom:4px; font-size:0.75rem;">CITY *</label>
              <input type="text" id="ob-city" placeholder="Tagbilaran City" style="width:100%; padding:10px; border:2px solid var(--ink); border-radius:10px; font-family:inherit; font-weight:700;">
              <div id="fb-city" class="inline-feedback"></div>
            </div>
            <div>
              <label class="mono" style="display:block; margin-bottom:4px; font-size:0.75rem;">PROVINCE *</label>
              <input type="text" id="ob-province" placeholder="Bohol" style="width:100%; padding:10px; border:2px solid var(--ink); border-radius:10px; font-family:inherit; font-weight:700;">
              <div id="fb-province" class="inline-feedback"></div>
            </div>
          </div>
        </div>

        <!-- STEP 4: UPLOAD DOCUMENTS -->
        <div class="wizard-step" id="step-4">
          <h3 style="font-size:1.2rem; font-weight:800; text-transform:uppercase; margin:0 0 14px;"><i class="bi bi-file-earmark-text"></i> STEP 4: VERIFICATION DOCUMENTS</h3>
          <div class="card-streetside sand" style="padding:24px; text-align:center; margin-bottom:16px;">
            <i class="bi bi-cloud-arrow-up-fill" style="font-size:2.5rem; color:var(--green);"></i>
            <div class="mono" style="font-size:0.82rem; font-weight:700; margin-top:8px;">Upload Business Permit / Government Valid ID</div>
            <p style="font-size:0.78rem; color:#4a5c56; margin:4px 0 12px;">Accepted formats: PDF, PNG, JPG (Max 5MB)</p>
            <input type="file" id="ob-docs" accept=".pdf,.png,.jpg,.jpeg">
            <div id="fb-docs" class="inline-feedback" style="margin-top:8px;"></div>
          </div>
        </div>

        <!-- STEP 5: ADD COURTS -->
        <div class="wizard-step" id="step-5">
          <h3 style="font-size:1.2rem; font-weight:800; text-transform:uppercase; margin:0 0 14px;"><i class="bi bi-layers-fill"></i> STEP 5: INITIAL COURTS SETUP</h3>
          
          <div class="card-streetside sand" style="padding:12px 16px; margin-bottom:14px; border-left:4px solid var(--green); display:flex; align-items:center; gap:10px;">
            <i class="bi bi-info-circle-fill" style="font-size:1.2rem; color:var(--green);"></i>
            <span class="mono" style="font-size:0.78rem; font-weight:700; color:var(--ink);">NOTE: You can add more courts based on your selected package.</span>
          </div>

          <div style="margin-bottom:12px;">
            <label class="mono" style="display:block; margin-bottom:4px; font-size:0.75rem;">COURT NAME *</label>
            <input type="text" id="ob-courtname" placeholder="Court 1 - Pro Championship" style="width:100%; padding:10px; border:2px solid var(--ink); border-radius:10px; font-family:inherit; font-weight:700;">
            <div id="fb-courtname" class="inline-feedback"></div>
          </div>
          <div style="margin-bottom:16px;">
            <label class="mono" style="display:block; margin-bottom:4px; font-size:0.75rem;">SURFACE TYPE</label>
            <select id="ob-courttype" style="width:100%; padding:10px; border:2px solid var(--ink); border-radius:10px; font-family:inherit; font-weight:700;">
              <option value="indoor">Indoor Cushioned Acrylic</option>
              <option value="outdoor">Outdoor Acrylic</option>
              <option value="covered">Covered Polyurethane</option>
            </select>
          </div>
        </div>

        <!-- STEP 6: CONFIGURE PRICING -->
        <div class="wizard-step" id="step-6">
          <h3 style="font-size:1.2rem; font-weight:800; text-transform:uppercase; margin:0 0 14px;"><i class="bi bi-tag-fill"></i> STEP 6: CONFIGURE COURT PRICING</h3>
          <div style="margin-bottom:16px;">
            <label class="mono" style="display:block; margin-bottom:4px; font-size:0.75rem;">BASE RATE PER HOUR (₱) *</label>
            <input type="number" id="ob-price" placeholder="450.00" style="width:100%; padding:10px; border:2px solid var(--ink); border-radius:10px; font-family:inherit; font-weight:700;">
            <div id="fb-price" class="inline-feedback"></div>
          </div>
        </div>

        <!-- STEP 7: OPERATING HOURS & AVAILABILITY -->
        <div class="wizard-step" id="step-7">
          <h3 style="font-size:1.2rem; font-weight:800; text-transform:uppercase; margin:0 0 14px;"><i class="bi bi-clock-fill"></i> STEP 7: OPERATING HOURS</h3>
          <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:16px;">
            <div>
              <label class="mono" style="display:block; margin-bottom:4px; font-size:0.75rem;">OPEN TIME</label>
              <input type="time" id="ob-opentime" value="06:00" style="width:100%; padding:10px; border:2px solid var(--ink); border-radius:10px; font-family:inherit; font-weight:700;">
            </div>
            <div>
              <label class="mono" style="display:block; margin-bottom:4px; font-size:0.75rem;">CLOSE TIME</label>
              <input type="time" id="ob-closetime" value="22:00" style="width:100%; padding:10px; border:2px solid var(--ink); border-radius:10px; font-family:inherit; font-weight:700;">
            </div>
          </div>
        </div>

        <!-- STEP 8: PLATFORM SUBSCRIPTION -->
        <div class="wizard-step" id="step-8">
          <h3 style="font-size:1.2rem; font-weight:800; text-transform:uppercase; margin:0 0 10px;"><i class="bi bi-award-fill"></i> STEP 8: CHOOSE SAAS SUBSCRIPTION</h3>
          <p style="font-size:0.85rem; color:#3b4e48; margin-bottom:16px;">Select the platform package that fits your facility operations. Click a plan to review the full payment breakdown.</p>

          <div id="subscription-plans-grid" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); gap:16px; margin-bottom:16px;">
            <?php foreach ($subscriptionPlans as $index => $plan): ?>
              <?php
                $isDefault = ($index === 0);
                $slug = $plan['slug'] ?? strtolower(explode(' ', $plan['name'])[0]);
                $bgStyle = $isDefault ? 'background:var(--sand); border:3px solid var(--coral); transform:translateY(-4px); box-shadow:4px 4px 0 var(--ink);' : 'background:var(--white); border:2px solid var(--ink);';
              ?>
              <label id="plan-card-<?= htmlspecialchars($slug) ?>" class="card-streetside sub-plan-card <?= $isDefault ? 'selected' : '' ?>" onclick="selectSubPlan('<?= htmlspecialchars($slug) ?>')" style="padding:20px; cursor:pointer; display:block; position:relative; transition:all 0.25s ease; <?= $bgStyle ?>">
                <div style="display:flex; justify-content:space-between; align-items:center;">
                  <span class="mono badge-selected" style="<?= $isDefault ? 'display:inline-block;' : 'display:none;' ?> background:var(--coral); color:var(--white); font-size:0.68rem; font-weight:800; padding:3px 8px; border-radius:6px;">✓ SELECTED PLAN</span>
                  <input type="radio" name="sub_plan" value="<?= htmlspecialchars($slug) ?>" <?= $isDefault ? 'checked' : '' ?> style="transform:scale(1.3); accent-color:var(--coral);">
                </div>
                <strong style="display:block; margin-top:12px; font-size:1.25rem;"><?= htmlspecialchars($plan['name']) ?></strong>
                <div style="font-size:1.4rem; font-weight:900; color:var(--green); margin:4px 0 8px;">₱<?= number_format($plan['monthly_price'], 2) ?> <span style="font-size:0.78rem; font-weight:600; color:#3b4e48;">/ month</span></div>
                <p style="font-size:0.78rem; color:#4b5c56; margin:0 0 10px; font-weight:600;"><?= htmlspecialchars($plan['description'] ?? '') ?></p>
                
                <div style="border-top:1px dashed var(--ink); padding-top:10px; margin-top:10px;">
                  <div class="mono" style="font-size:0.68rem; font-weight:800; text-transform:uppercase; color:var(--green); margin-bottom:6px;">PLAN INCLUSIONS:</div>
                  <ul style="font-size:0.78rem; color:#1d2925; list-style:none; padding:0; margin:0; line-height:1.6;">
                    <?php if (!empty($plan['features'])): ?>
                      <?php foreach ($plan['features'] as $feat): ?>
                        <li style="display:flex; align-items:center; gap:6px;">
                          <i class="bi bi-check-circle-fill" style="color:var(--green); font-size:0.85rem;"></i>
                          <span><?= htmlspecialchars($feat) ?></span>
                        </li>
                      <?php endforeach; ?>
                    <?php else: ?>
                      <li style="display:flex; align-items:center; gap:6px;">
                        <i class="bi bi-check-circle-fill" style="color:var(--green); font-size:0.85rem;"></i>
                        <span><?= (int)$plan['max_facilities'] >= 90 ? 'Unlimited' : (int)$plan['max_facilities'] ?> Facility Location(s)</span>
                      </li>
                      <li style="display:flex; align-items:center; gap:6px;">
                        <i class="bi bi-check-circle-fill" style="color:var(--green); font-size:0.85rem;"></i>
                        <span>Up to <?= (int)$plan['max_courts'] >= 500 ? 'Unlimited' : (int)$plan['max_courts'] ?> Courts</span>
                      </li>
                      <li style="display:flex; align-items:center; gap:6px;">
                        <i class="bi bi-check-circle-fill" style="color:var(--green); font-size:0.85rem;"></i>
                        <span><?= (int)$plan['max_staff'] ?> Staff Account(s) Included</span>
                      </li>
                    <?php endif; ?>
                  </ul>
                </div>

                <button type="button" onclick="event.stopPropagation(); selectSubPlan('<?= htmlspecialchars($slug) ?>'); openPaymentModal('<?= htmlspecialchars($slug) ?>');" class="button coral" style="width:100%; margin-top:16px; padding:10px; font-size:0.82rem; font-weight:800; display:flex; align-items:center; justify-content:center; gap:6px;">
                  <i class="bi bi-credit-card-fill"></i> Select Plan &amp; Review Payment
                </button>
              </label>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- STEP 9: SUBSCRIPTION PAYMENT RECEIPT -->
        <div class="wizard-step" id="step-9">
          <h3 style="font-size:1.2rem; font-weight:800; text-transform:uppercase; margin:0 0 14px;"><i class="bi bi-receipt"></i> STEP 9: SUBSCRIPTION PAYMENT RECEIPT</h3>
          
          <div id="s9-payment-pending-box" class="card-streetside sand" style="display:block; padding:24px; text-align:center; border:2px solid var(--ink); margin-bottom:16px;">
            <i class="bi bi-exclamation-circle-fill" style="font-size:2.5rem; color:var(--coral);"></i>
            <h4 style="font-size:1.15rem; font-weight:900; margin:10px 0 6px; text-transform:uppercase;">PAYMENT NOT YET COMPLETED</h4>
            <p style="font-size:0.85rem; color:#4a5c56; margin-bottom:16px; line-height:1.4;">Please select your desired subscription plan in Step 8 and confirm the payment breakdown to proceed.</p>
            <button type="button" onclick="jumpToStep(8); openPaymentModalCurrent();" class="button coral" style="padding:10px 22px; font-size:0.85rem; font-weight:800;">
              <i class="bi bi-credit-card"></i> Complete Payment in Step 8 &rarr;
            </button>
          </div>

          <div id="s9-payment-success-box" style="display:none;">
            <div class="card-streetside" style="padding:20px; background:#dcfce7; border:2px solid var(--ink); margin-bottom:16px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px;">
              <div style="display:flex; align-items:center; gap:12px;">
                <i class="bi bi-patch-check-fill" style="font-size:2.2rem; color:#15803d;"></i>
                <div>
                  <strong style="font-size:1.05rem; display:block; color:#14532d; font-weight:900;">PAYMENT VERIFIED &amp; AUTHORIZED</strong>
                  <span class="mono" style="font-size:0.75rem; color:#166534; font-weight:700;" id="s9-receipt-ref">REF: PM-2026-894210</span>
                </div>
              </div>
              <span class="badge-streetside lime" style="font-weight:900; font-size:0.8rem;"><i class="bi bi-check-circle-fill"></i> STATUS: PAID</span>
            </div>

            <div class="card-streetside sand" style="padding:20px; border:2px solid var(--ink);">
              <div class="mono" style="font-size:0.75rem; font-weight:800; text-transform:uppercase; color:#4a5c56; margin-bottom:12px; border-bottom:1px solid var(--ink); padding-bottom:6px;">SUBSCRIPTION TRANSACTION RECEIPT</div>
              <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:14px; font-size:0.85rem;" class="mono">
                <div><span style="color:#4a5c56; display:block; font-size:0.72rem;">SELECTED PLAN</span> <strong id="s9-plan-name">Pro Plan</strong></div>
                <div><span style="color:#4a5c56; display:block; font-size:0.72rem;">PAYMENT METHOD</span> <strong id="s9-pay-method">GCash Philippines</strong></div>
                <div><span style="color:#4a5c56; display:block; font-size:0.72rem;">BASE PLAN PRICE</span> <strong id="s9-base-price">₱999.00</strong></div>
                <div><span style="color:#4a5c56; display:block; font-size:0.72rem;">TOTAL PAID (INCL. FEES)</span> <strong id="s9-total-paid" style="color:var(--green); font-size:1.05rem; font-weight:900;">₱1,043.96</strong></div>
                <div><span style="color:#4a5c56; display:block; font-size:0.72rem;">BILLING CYCLE</span> <strong>Monthly Recurring</strong></div>
                <div><span style="color:#4a5c56; display:block; font-size:0.72rem;">TRANSACTION DATE</span> <strong id="s9-payment-date">Today</strong></div>
              </div>
              <div style="margin-top:16px; border-top:1px dashed var(--ink); padding-top:12px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px;">
                <span style="font-size:0.78rem; color:#4a5c56;">Need to adjust or change plan?</span>
                <button type="button" onclick="jumpToStep(8); openPaymentModalCurrent();" class="button sand" style="padding:6px 14px; font-size:0.75rem; font-weight:700;">
                  <i class="bi bi-arrow-repeat"></i> Change Plan / Re-verify Payment
                </button>
              </div>
            </div>
          </div>

          <div style="display:none;">
            <select id="ob-paymethod">
              <?php foreach ($enabledPaymentMethods as $pm): ?>
                <option value="<?= htmlspecialchars($pm['id']) ?>"><?= htmlspecialchars($pm['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <!-- STEP 10: VERIFICATION SUBMISSION -->
        <div class="wizard-step" id="step-10">
          <div style="text-align:center; padding:10px 0;">
            <div class="brand-mark" style="width:56px; height:56px; font-size:1.8rem; margin:0 auto 14px; background:var(--lime);">
              <i class="bi bi-patch-check-fill"></i>
            </div>
            <h3 style="font-size:1.4rem; font-weight:800; text-transform:uppercase; margin:0 0 6px;">READY FOR VERIFICATION</h3>
            <p style="font-size:0.88rem; color:#3b4e48; margin-bottom:20px; line-height:1.4;">
              Your facility application status progression: <br>
              <strong class="mono" style="color:var(--green); font-size:0.9rem;">[Draft] &rarr; [Submitted] &rarr; [Under Review] &rarr; [Approved] &rarr; [Published]</strong>
            </p>
          </div>
        </div>

        <!-- WIZARD NAVIGATION CONTROLS -->
        <div style="display:flex; justify-content:space-between; align-items:center; border-top:2px solid var(--ink); padding-top:20px; margin-top:20px; gap:10px; flex-wrap:wrap;">
          <button type="button" id="prev-btn" onclick="navigateStep(-1)" class="button sand" style="display:none; padding:10px 18px; font-size:0.85rem;">
            &laquo; Previous Step
          </button>

          <button type="button" id="next-btn" onclick="navigateStep(1)" class="button coral" style="padding:10px 24px; font-size:0.88rem; margin-left:auto;">
            Next Step &rarr;
          </button>

          <button type="button" id="submit-btn" class="button lime" style="display:none; padding:10px 24px; font-size:0.88rem; margin-left:auto;">
            <i class="bi bi-send-fill"></i> Submit for Verification
          </button>
        </div>

      </form>
    </div>
  </div>

  <div id="footer-container"></div>

  <!-- PAYMENT BREAKDOWN & CONFIRMATION MODAL -->
  <div id="payment-summary-modal" style="display:none; position:fixed; inset:0; z-index:999999 !important; background:rgba(10,20,15,0.8); backdrop-filter:blur(8px); -webkit-backdrop-filter:blur(8px); overflow-y:auto; padding:20px 16px;">
    <div style="min-height:100%; display:flex; align-items:center; justify-content:center; padding:30px 0;">
      <div class="card-streetside" style="max-width:620px; width:100%; background:var(--white); padding:32px; position:relative; box-shadow:8px 8px 0 var(--ink); border:3px solid var(--ink); border-radius:18px;">
        <button type="button" onclick="closePaymentModal()" style="position:absolute; top:18px; right:18px; background:none; border:none; font-size:1.8rem; cursor:pointer; color:var(--ink); font-weight:900; line-height:1;">&times;</button>
        
        <div style="display:flex; align-items:center; gap:14px; margin-bottom:20px;">
          <div class="brand-mark" style="width:48px; height:48px; font-size:1.4rem; background:var(--coral); color:var(--white); border-radius:12px; display:grid; place-items:center; border:2px solid var(--ink); box-shadow:2px 2px 0 var(--ink);">
            <i class="bi bi-receipt"></i>
          </div>
          <div>
            <h3 style="font-size:1.25rem; font-weight:900; text-transform:uppercase; margin:0; line-height:1.2;">PAYMENT SUMMARY &amp; BREAKDOWN</h3>
            <div style="font-size:0.78rem; color:#4a5c56; font-family:'DM Mono', monospace; font-weight:700;">Review subscription invoice &amp; payable total</div>
          </div>
        </div>

        <div class="card-streetside sand" style="padding:20px; margin-bottom:24px; border:2px solid var(--ink); border-radius:14px;">
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;">
            <div>
              <span class="mono" style="font-size:0.7rem; color:#4a5c56; font-weight:800; text-transform:uppercase;">SELECTED SUBSCRIPTION PLAN</span>
              <div id="modal-plan-title" style="font-size:1.35rem; font-weight:900; color:var(--ink);">Pro Plan</div>
            </div>
            <span class="badge-streetside coral" style="font-size:0.75rem; font-weight:800;">MONTHLY RECURRING</span>
          </div>

          <!-- Financial Itemization Table -->
          <div style="border-top:2px dashed var(--ink); padding-top:12px; font-family:'DM Mono', monospace; font-size:0.85rem; line-height:2.0;">
            <div style="display:flex; justify-content:space-between;">
              <span style="color:#4a5c56;">Base Plan Rate:</span>
              <strong id="modal-base-price">₱999.00</strong>
            </div>
            <div id="modal-fee-row-platform" style="display:flex; justify-content:space-between;">
              <span style="color:#4a5c56;">Platform Infrastructure Fee (2%):</span>
              <strong id="modal-platform-fee">₱19.98</strong>
            </div>
            <div id="modal-fee-row-gateway" style="display:flex; justify-content:space-between;">
              <span style="color:#4a5c56;">PayMongo Gateway Fee (2.5%):</span>
              <strong id="modal-gateway-fee">₱24.98</strong>
            </div>
            <div id="modal-free-trial-note" style="display:none; padding:10px 14px; background:#dcfce7; border:1px dashed #15803d; border-radius:8px; color:#14532d; font-size:0.8rem; font-weight:700;">
              <i class="bi bi-gift-fill"></i> FREE TRIAL — No payment required. Activate instantly!
            </div>
            <div style="display:flex; justify-content:space-between; border-top:2px solid var(--ink); padding-top:10px; margin-top:10px; font-size:1.05rem; font-weight:900;">
              <span id="modal-total-label">TOTAL AMOUNT TO BE PAID:</span>
              <strong id="modal-total-amount" style="color:var(--green); font-size:1.3rem;">₱1,043.96</strong>
            </div>
          </div>
        </div>

        <!-- Payment Method Selection -->
        <div style="margin-bottom:24px;">
          <label class="mono" style="display:block; margin-bottom:10px; font-size:0.78rem; font-weight:800;">SELECT PAYMENT CHANNEL *</label>
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
          <button type="button" onclick="closePaymentModal()" class="button sand" style="padding:12px 22px; font-size:0.88rem; font-weight:700;">Cancel</button>
          <button type="button" id="btn-modal-pay-now" onclick="processSubscriptionPayment()" class="button lime" style="padding:12px 28px; font-size:0.92rem; font-weight:900;">
            <i class="bi bi-lock-fill" id="btn-pay-icon"></i> <span id="btn-pay-label">Continue to Payment &rarr;</span>
          </button>
        </div>
      </div>
    </div>
  </div>

  <script src="/pikvero/assets/js/core/toast.js"></script>
  <script src="/pikvero/assets/js/core/ajax.js"></script>
  <script src="/pikvero/assets/js/core/auth.js"></script>
  <script src="/pikvero/assets/js/components/navbar.js?v=2"></script>
  <script src="/pikvero/assets/js/components/footer.js"></script>
  <script>
    let currentStep = 1;
    const totalSteps = 10;
    const DRAFT_KEY = 'pikvero_owner_onboarding_draft';
    const subscriptionPlansData = <?= json_encode($subscriptionPlans) ?>;
    const enabledPaymentMethodsData = <?= json_encode($enabledPaymentMethods) ?>;
    let activePaymentData = null;

    const stepTitles = [
      "1. Owner Account Setup",
      "2. Business Information",
      "3. Facility Details",
      "4. Verification Documents",
      "5. Initial Courts Setup",
      "6. Configure Court Pricing",
      "7. Operating Hours",
      "8. Platform Subscription",
      "9. Subscription Payment Receipt",
      "10. Submit for Verification"
    ];

    document.addEventListener('DOMContentLoaded', async () => {
      await NavbarComponent.render();
      FooterComponent.render();

      // Attach Live Validation Listeners
      const fnameInput = document.getElementById('ob-fname');
      const lnameInput = document.getElementById('ob-lname');
      const emailInput = document.getElementById('ob-email');
      const phoneInput = document.getElementById('ob-phone');
      const orgNameInput = document.getElementById('ob-orgname');
      const taxIdInput = document.getElementById('ob-taxid');
      const facNameInput = document.getElementById('ob-facname');
      const addressInput = document.getElementById('ob-address');
      const cityInput = document.getElementById('ob-city');
      const provinceInput = document.getElementById('ob-province');
      const courtNameInput = document.getElementById('ob-courtname');
      const priceInput = document.getElementById('ob-price');
      const docsInput = document.getElementById('ob-docs');
      const passInput = document.getElementById('ob-pass');
      const cpassInput = document.getElementById('ob-cpass');

      const usernameInput = document.getElementById('ob-username');
      if (usernameInput) usernameInput.addEventListener('input', validateUsername);

      fnameInput.addEventListener('input', validateFirstName);
      lnameInput.addEventListener('input', validateLastName);
      emailInput.addEventListener('input', validateEmail);
      phoneInput.addEventListener('input', validatePhone);
      if (passInput) passInput.addEventListener('input', validatePassword);
      if (cpassInput) cpassInput.addEventListener('input', validateConfirmPassword);
      orgNameInput.addEventListener('input', validateOrgName);
      taxIdInput.addEventListener('input', validateTaxId);
      facNameInput.addEventListener('input', validateFacName);
      addressInput.addEventListener('input', validateAddress);
      cityInput.addEventListener('input', validateCity);
      provinceInput.addEventListener('input', validateProvince);
      courtNameInput.addEventListener('input', validateCourtName);
      priceInput.addEventListener('input', validatePrice);

      if (docsInput) {
        docsInput.addEventListener('change', validateFileUpload);
      }

      // Auto-filter phone number non-digits
      phoneInput.addEventListener('input', function() {
        this.value = this.value.replace(/\D/g, '');
      });

      restoreFromLocalStorage();

      // Check for PayMongo return callback URL parameters
      const urlParams = new URLSearchParams(window.location.search);
      if (urlParams.get('payment') === 'success') {
        const ref = urlParams.get('ref') || ('cs_' + Math.floor(Math.random() * 1000000));
        const planSlug = urlParams.get('plan') || 'starter';
        const methodId = urlParams.get('method') || 'gcash';
        
        let plan = subscriptionPlansData.find(p => p.slug === planSlug) || subscriptionPlansData[0];
        const basePrice = parseFloat(plan ? plan.monthly_price : 999);
        const isFreeTrial = (basePrice <= 0) || parseInt(plan ? plan.is_free_trial || 0 : 0) === 1 || methodId === 'free_trial';
        const platformFee = isFreeTrial ? 0 : Math.round((basePrice * 0.02) * 100) / 100;
        const gatewayFee  = isFreeTrial ? 0 : Math.round((basePrice * 0.025) * 100) / 100;
        const totalAmount = isFreeTrial ? 0 : (parseFloat(urlParams.get('amount')) || (basePrice + platformFee + gatewayFee));
        const matchedMethod = enabledPaymentMethodsData.find(m => m.id === methodId || m.id === methodId.toLowerCase());
        const payMethodLabel = isFreeTrial ? 'Free Trial (No Charge)' : (matchedMethod ? matchedMethod.name : 'PayMongo Gateway');
        const todayStr = new Date().toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });

        activePaymentData = {
          completed: true,
          planSlug: planSlug,
          planName: plan ? plan.name : 'Pro Plan',
          basePrice: basePrice,
          platformFee: platformFee,
          gatewayFee: gatewayFee,
          totalAmount: totalAmount,
          payMethod: methodId,
          payMethodLabel: payMethodLabel,
          reference: ref,
          paidDate: todayStr
        };

        // Populate Step 9 Receipt
        if (document.getElementById('s9-plan-name')) document.getElementById('s9-plan-name').innerText = activePaymentData.planName;
        if (document.getElementById('s9-pay-method')) document.getElementById('s9-pay-method').innerText = activePaymentData.payMethodLabel;
        if (document.getElementById('s9-base-price')) document.getElementById('s9-base-price').innerText = `₱${basePrice.toLocaleString('en-US', {minimumFractionDigits: 2})}`;
        if (document.getElementById('s9-total-paid')) document.getElementById('s9-total-paid').innerText = `₱${totalAmount.toLocaleString('en-US', {minimumFractionDigits: 2})}`;
        if (document.getElementById('s9-receipt-ref')) document.getElementById('s9-receipt-ref').innerText = `REF: ${ref}`;
        if (document.getElementById('s9-payment-date')) document.getElementById('s9-payment-date').innerText = todayStr;

        if (document.getElementById('s9-payment-pending-box')) document.getElementById('s9-payment-pending-box').style.display = 'none';
        if (document.getElementById('s9-payment-success-box')) document.getElementById('s9-payment-success-box').style.display = 'block';

        saveToLocalStorage();
        jumpToStepDirect(9);

        const isFreeTrialActivation = (activePaymentData.totalAmount === 0) || (activePaymentData.payMethod === 'free_trial');
        Toast.success(
          isFreeTrialActivation ? '🎁 Free Trial Activated!' : '✅ Payment Successful!',
          isFreeTrialActivation
            ? `${activePaymentData.planName} free trial is ready. REF: ${ref}`
            : `${activePaymentData.planName} subscription authorized. REF: ${ref}`
        );
      } else if (urlParams.get('payment') === 'cancelled') {
        Toast.warning('Payment Cancelled', 'PayMongo checkout session was cancelled.');
        jumpToStepDirect(8);
      }

      // Attach auto-save listener to form
      const form = document.getElementById('onboarding-wizard-form');
      form.addEventListener('input', saveToLocalStorage);
      form.addEventListener('change', saveToLocalStorage);

      // ── Submit handler wired directly to the button click ──────────────────
      // Using type="button" + click listener (NOT form submit) prevents any
      // possibility of the browser doing a native GET form submission which
      // would reload the page and show "Restored Draft" on the first click.
      document.getElementById('submit-btn').addEventListener('click', async () => {

        // Restore passwords before validation — Chrome clears type="password"
        // inputs inside display:none steps. Step 1 is hidden when the user is
        // on Step 10, so ob-pass.value reads as ''. We must restore from
        // localStorage BEFORE validateStep(1) runs.
        try {
          const _d = JSON.parse(localStorage.getItem(DRAFT_KEY) || '{}');
          const _p  = document.getElementById('ob-pass');
          const _cp = document.getElementById('ob-cpass');
          if (_p  && !_p.value  && _d.pass)  _p.value  = _d.pass;
          if (_cp && !_cp.value && _d.cpass) _cp.value = _d.cpass;
        } catch (_) {}

        // Security check: verify all steps 1-10 before submission
        for (let s = 1; s <= totalSteps; s++) {
          if (!await validateStep(s)) {
            jumpToStep(s);
            Toast.error('Submission Blocked', `Please complete required field in Step ${s}.`);
            return;
          }
        }

        const submitBtn = document.getElementById('submit-btn');
        const origHtml = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = `<i class="bi bi-hourglass-split"></i> Registering Account &amp; Saving Records...`;

        // Read draft for password fallback (field may have been cleared again)
        const _draft = JSON.parse(localStorage.getItem(DRAFT_KEY) || '{}');

        const payload = {
          username: document.getElementById('ob-username')?.value || '',
          fname: document.getElementById('ob-fname')?.value || '',
          lname: document.getElementById('ob-lname')?.value || '',
          email: document.getElementById('ob-email')?.value || '',
          phone: document.getElementById('ob-phone')?.value || '',
          pass:  document.getElementById('ob-pass')?.value  || _draft.pass  || '',
          orgname: document.getElementById('ob-orgname')?.value || '',
          taxid: document.getElementById('ob-taxid')?.value || '',
          facname: document.getElementById('ob-facname')?.value || '',
          address: document.getElementById('ob-address')?.value || '',
          city: document.getElementById('ob-city')?.value || '',
          province: document.getElementById('ob-province')?.value || '',
          courtname: document.getElementById('ob-courtname')?.value || '',
          courttype: document.getElementById('ob-courttype')?.value || '',
          price: document.getElementById('ob-price')?.value || '',
          opentime: document.getElementById('ob-opentime')?.value || '',
          closetime: document.getElementById('ob-closetime')?.value || '',
          subplan: document.querySelector('input[name="sub_plan"]:checked')?.value || 'starter',
          paymethod: document.getElementById('ob-paymethod')?.value || '',
          paymentData: activePaymentData,
          docData: savedDocData
        };

        try {
          const res = await Api.post('/pikvero/api/owner/submit-onboarding.php', payload);
          if (res.success) {
            localStorage.removeItem(DRAFT_KEY);
            Toast.success('Application Submitted!', res.message || 'Onboarding completed & records saved to database!');
            setTimeout(() => {
              window.location.href = res.data?.redirect || '/pikvero/public/admin/dashboard.php';
            }, 1200);
          } else {
            Toast.error('Submission Error', res.message || 'Failed to insert onboarding records into database.');
            submitBtn.disabled = false;
            submitBtn.innerHTML = origHtml;
          }
        } catch (err) {
          console.error(err);
          Toast.error('Server Error', 'Failed to connect to database submission API.');
          submitBtn.disabled = false;
          submitBtn.innerHTML = origHtml;
        }
      });

    });

    // Helper to set UI Feedback
    function setFeedback(inputEl, feedbackEl, isValid, msg) {
      if (isValid) {
        inputEl.classList.remove('is-invalid');
        inputEl.classList.add('is-valid');
        feedbackEl.className = 'inline-feedback valid';
        feedbackEl.innerText = msg;
      } else {
        inputEl.classList.remove('is-valid');
        inputEl.classList.add('is-invalid');
        feedbackEl.className = 'inline-feedback invalid';
        feedbackEl.innerText = msg;
      }
    }

    async function validateUsername() {
      const el = document.getElementById('ob-username');
      const fb = document.getElementById('fb-username');
      if (!el || !fb) return true;
      const val = el.value.trim();
      const ok = /^[a-zA-Z0-9_]{3,30}$/.test(val);
      if (!ok) {
        setFeedback(el, fb, false, '✕ Username must be 3-30 characters (letters, numbers, underscores).');
        return false;
      }
      try {
        const res = await Api.get('/pikvero/api/auth/check-unique.php', { field: 'username', value: val });
        if (res.success && res.data.exists) {
          setFeedback(el, fb, false, '✕ This username is already taken. Please choose another.');
          return false;
        }
      } catch (e) {}
      setFeedback(el, fb, true, '✓ Username is available');
      return true;
    }

    function validateFirstName() {
      const el = document.getElementById('ob-fname');
      const fb = document.getElementById('fb-fname');
      const ok = el.value.trim().length >= 2;
      setFeedback(el, fb, ok, ok ? '✓ Valid First Name' : '✕ First name is required (min 2 chars).');
      return ok;
    }

    function validateLastName() {
      const el = document.getElementById('ob-lname');
      const fb = document.getElementById('fb-lname');
      const ok = el.value.trim().length >= 2;
      setFeedback(el, fb, ok, ok ? '✓ Valid Last Name' : '✕ Last name is required (min 2 chars).');
      return ok;
    }

    async function validateEmail() {
      const el = document.getElementById('ob-email');
      const fb = document.getElementById('fb-email');
      const val = el.value.trim();
      const ok = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val);
      if (!ok) {
        setFeedback(el, fb, false, '✕ Enter a valid email address (e.g. owner@smashzone.com).');
        return false;
      }
      try {
        const res = await Api.get('/pikvero/api/auth/check-unique.php', { field: 'email', value: val });
        if (res.success && res.data.exists) {
          setFeedback(el, fb, false, '✕ This email address is already registered to an account.');
          return false;
        }
      } catch (e) {}
      setFeedback(el, fb, true, '✓ Email address is available');
      return true;
    }

    async function validatePhone() {
      const el = document.getElementById('ob-phone');
      const fb = document.getElementById('fb-phone');
      const val = el.value.trim();
      const ok = /^09\d{9}$/.test(val);
      if (!ok) {
        setFeedback(el, fb, false, '✕ Must be 11 digits starting with 09 (e.g. 09181112222).');
        return false;
      }
      try {
        const res = await Api.get('/pikvero/api/auth/check-unique.php', { field: 'phone', value: val });
        if (res.success && res.data.exists) {
          setFeedback(el, fb, false, '✕ This phone number is already registered to an account.');
          return false;
        }
      } catch (e) {}
      setFeedback(el, fb, true, '✓ Phone number is available');
      return true;
    }

    function validatePassword() {
      const el = document.getElementById('ob-pass');
      const fb = document.getElementById('fb-pass');
      if (!el || !fb) return true;
      const ok = el.value.length >= 6;
      setFeedback(el, fb, ok, ok ? '✓ Strong Password' : '✕ Password must be at least 6 characters.');
      validateConfirmPassword();
      return ok;
    }

    function validateConfirmPassword() {
      const pass = document.getElementById('ob-pass')?.value || '';
      const el = document.getElementById('ob-cpass');
      const fb = document.getElementById('fb-cpass');
      if (!el || !fb) return true;
      const ok = el.value.length >= 6 && el.value === pass;
      setFeedback(el, fb, ok, ok ? '✓ Passwords Match' : (el.value.length < 6 ? '✕ Confirm password must be at least 6 characters.' : '✕ Passwords do not match.'));
      return ok;
    }

    function togglePassVisibility(inputId, toggleIconId) {
      const input = document.getElementById(inputId);
      const icon = document.getElementById(toggleIconId);
      if (!input || !icon) return;
      if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'bi bi-eye-fill';
      } else {
        input.type = 'password';
        icon.className = 'bi bi-eye-slash-fill';
      }
    }

    async function validateOrgName() {
      const el = document.getElementById('ob-orgname');
      const fb = document.getElementById('fb-orgname');
      const val = el.value.trim();
      const ok = val.length >= 2;
      if (!ok) {
        setFeedback(el, fb, false, '✕ Organization name is required (min 2 characters).');
        return false;
      }
      try {
        const res = await Api.get('/pikvero/api/auth/check-unique.php', { field: 'organization_name', value: val });
        if (res.success && res.data.exists) {
          setFeedback(el, fb, false, '✕ This Organization / Club Name is already registered.');
          return false;
        }
      } catch (e) {}
      setFeedback(el, fb, true, '✓ Organization name is available');
      return true;
    }

    async function validateTaxId() {
      const el = document.getElementById('ob-taxid');
      const fb = document.getElementById('fb-taxid');
      const val = el.value.trim();
      const formatOk = /^[a-zA-Z0-9\-\/]{3,50}$/.test(val);
      if (!formatOk) {
        setFeedback(el, fb, false, '✕ Enter a valid Registration # (min 3 chars, letters/numbers/hyphens).');
        return false;
      }
      try {
        const res = await Api.get('/pikvero/api/auth/check-unique.php', { field: 'tax_id', value: val });
        if (res.success && res.data.exists) {
          setFeedback(el, fb, false, '✕ This Business Permit / DTI / SEC Registration # is already registered.');
          return false;
        }
      } catch (e) {}
      setFeedback(el, fb, true, '✓ Registration number is valid');
      return true;
    }

    function validateFacName() {
      const el = document.getElementById('ob-facname');
      const fb = document.getElementById('fb-facname');
      const ok = el.value.trim().length >= 2;
      setFeedback(el, fb, ok, ok ? '✓ Valid Facility Name' : '✕ Facility name is required.');
      return ok;
    }

    function validateAddress() {
      const el = document.getElementById('ob-address');
      const fb = document.getElementById('fb-address');
      const ok = el.value.trim().length >= 3;
      setFeedback(el, fb, ok, ok ? '✓ Valid Address' : '✕ Street address is required.');
      return ok;
    }

    function validateCity() {
      const el = document.getElementById('ob-city');
      const fb = document.getElementById('fb-city');
      const ok = el.value.trim().length >= 2;
      setFeedback(el, fb, ok, ok ? '✓ Valid City' : '✕ City is required.');
      return ok;
    }

    function validateProvince() {
      const el = document.getElementById('ob-province');
      const fb = document.getElementById('fb-province');
      const ok = el.value.trim().length >= 2;
      setFeedback(el, fb, ok, ok ? '✓ Valid Province' : '✕ Province is required.');
      return ok;
    }

    function validateCourtName() {
      const el = document.getElementById('ob-courtname');
      const fb = document.getElementById('fb-courtname');
      const ok = el.value.trim().length >= 2;
      setFeedback(el, fb, ok, ok ? '✓ Valid Court Name' : '✕ Court name is required.');
      return ok;
    }

    function validatePrice() {
      const el = document.getElementById('ob-price');
      const fb = document.getElementById('fb-price');
      const val = parseFloat(el.value);
      const ok = !isNaN(val) && val > 0;
      setFeedback(el, fb, ok, ok ? '✓ Valid Hourly Price' : '✕ Hourly rate must be a positive number.');
      return ok;
    }

    let savedDocData = null;

    function handleFileUpload() {
      const fileInput = document.getElementById('ob-docs');
      const fb = document.getElementById('fb-docs');
      if (!fileInput || !fileInput.files.length) {
        if (savedDocData && savedDocData.name) {
          setFeedback(fileInput, fb, true, `✓ File '${savedDocData.name}' attached successfully.`);
          renderDocPreview();
          return true;
        }
        return true;
      }

      const file = fileInput.files[0];
      const validExtensions = ['pdf', 'png', 'jpg', 'jpeg'];
      const ext = file.name.split('.').pop().toLowerCase();
      const maxSize = 5 * 1024 * 1024; // 5MB

      if (!validExtensions.includes(ext)) {
        setFeedback(fileInput, fb, false, '✕ Invalid file format. Only PDF, PNG, and JPG are accepted.');
        fileInput.value = '';
        savedDocData = null;
        renderDocPreview();
        return false;
      }

      if (file.size > maxSize) {
        setFeedback(fileInput, fb, false, '✕ File exceeds 5MB size limit.');
        fileInput.value = '';
        savedDocData = null;
        renderDocPreview();
        return false;
      }

      const reader = new FileReader();
      reader.onload = function(evt) {
        savedDocData = {
          name: file.name,
          size: file.size,
          type: file.type,
          base64: evt.target.result
        };
        setFeedback(fileInput, fb, true, `✓ File '${file.name}' attached and saved in draft.`);
        renderDocPreview();
        saveToLocalStorage();
      };
      reader.readAsDataURL(file);
      return true;
    }

    function renderDocPreview() {
      let previewBox = document.getElementById('doc-preview-box');
      if (savedDocData && savedDocData.name) {
        if (!previewBox) {
          previewBox = document.createElement('div');
          previewBox.id = 'doc-preview-box';
          const container = document.getElementById('ob-docs')?.parentElement;
          if (container) container.appendChild(previewBox);
        }
        const sizeMb = (savedDocData.size / (1024 * 1024)).toFixed(2);
        previewBox.className = 'card-streetside lime';
        previewBox.style.padding = '12px 16px';
        previewBox.style.marginTop = '14px';
        previewBox.style.display = 'flex';
        previewBox.style.alignItems = 'center';
        previewBox.style.justify = 'space-between';
        previewBox.style.border = '2px solid var(--ink)';
        previewBox.style.borderRadius = '10px';
        previewBox.innerHTML = `
          <div>
            <strong style="display:block; font-size:0.85rem; font-family:'DM Mono', monospace;"><i class="bi bi-file-earmark-check-fill" style="color:var(--ink);"></i> ATTACHED DOCUMENT: ${savedDocData.name}</strong>
            <span style="font-size:0.72rem; color:#334155; font-family:'DM Mono', monospace;">Size: ${sizeMb} MB | Saved in Draft</span>
          </div>
          <span class="badge-streetside coral" style="font-size:0.7rem;">✓ ATTACHED</span>
        `;
      } else if (previewBox) {
        previewBox.remove();
      }
    }

    function validateFileUpload() {
      return handleFileUpload();
    }

    async function validateStep(step) {
      if (step === 1) {
        const fnameOk = validateFirstName();
        const lnameOk = validateLastName();
        const emailOk = await validateEmail();
        const phoneOk = await validatePhone();
        const userOk  = await validateUsername();
        const passOk  = validatePassword();
        const cpassOk = validateConfirmPassword();
        return userOk && fnameOk && lnameOk && emailOk && phoneOk && passOk && cpassOk;
      } else if (step === 2) {
        const o = await validateOrgName();
        const t = await validateTaxId();
        return o && t;
      } else if (step === 3) {
        return validateFacName() && validateAddress() && validateCity() && validateProvince();
      } else if (step === 4) {
        return validateFileUpload();
      } else if (step === 5) {
        return validateCourtName();
      } else if (step === 6) {
        return validatePrice();
      } else if (step === 8) {
        if (!activePaymentData || !activePaymentData.completed) {
          openPaymentModalCurrent();
          Toast.info('Payment Confirmation Required', 'Please review the payment breakdown and authorize subscription payment to proceed.');
          return false;
        }
        return true;
      }
      return true;
    }

    // Modal Control & Calculation Logic
    function openPaymentModal(planSlug) {
      let plan = subscriptionPlansData.find(p => p.slug === planSlug);
      if (!plan) {
        plan = subscriptionPlansData.find(p => (p.slug || p.name.toLowerCase().split(' ')[0]) === planSlug) || subscriptionPlansData[0];
      }
      if (!plan) return;

      const basePrice = parseFloat(plan.monthly_price || 0);
      const isFreeTrial = (basePrice <= 0) || parseInt(plan.is_free_trial || 0) === 1;
      const platformFee = isFreeTrial ? 0 : Math.round((basePrice * 0.02) * 100) / 100;
      const gatewayFee  = isFreeTrial ? 0 : Math.round((basePrice * 0.025) * 100) / 100;
      const totalAmount  = isFreeTrial ? 0 : (basePrice + platformFee + gatewayFee);

      document.getElementById('modal-plan-title').innerText = plan.name;
      document.getElementById('modal-base-price').innerText = isFreeTrial ? '₱0.00 (FREE)' : `₱${basePrice.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
      document.getElementById('modal-platform-fee').innerText = `₱${platformFee.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
      document.getElementById('modal-gateway-fee').innerText = `₱${gatewayFee.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
      document.getElementById('modal-total-amount').innerText = isFreeTrial ? '₱0.00 (FREE TRIAL)' : `₱${totalAmount.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;

      // Toggle free-trial UI
      const feeRowPlatform = document.getElementById('modal-fee-row-platform');
      const feeRowGateway  = document.getElementById('modal-fee-row-gateway');
      const freeNote       = document.getElementById('modal-free-trial-note');
      const totalLabel     = document.getElementById('modal-total-label');
      const btnPayIcon     = document.getElementById('btn-pay-icon');
      const btnPayLabel    = document.getElementById('btn-pay-label');
      const payMethodSection = document.querySelector('#payment-summary-modal [style*="margin-bottom:24px"]');

      if (isFreeTrial) {
        if (feeRowPlatform) feeRowPlatform.style.display = 'none';
        if (feeRowGateway)  feeRowGateway.style.display  = 'none';
        if (freeNote)       freeNote.style.display        = 'flex';
        if (totalLabel)     totalLabel.innerText           = 'TOTAL DUE:';
        if (btnPayIcon)     btnPayIcon.className           = 'bi bi-gift-fill';
        if (btnPayLabel)    btnPayLabel.innerHTML          = 'Activate Free Trial &rarr;';
        if (payMethodSection) payMethodSection.style.display = 'none';
      } else {
        if (feeRowPlatform) feeRowPlatform.style.display = 'flex';
        if (feeRowGateway)  feeRowGateway.style.display  = 'flex';
        if (freeNote)       freeNote.style.display        = 'none';
        if (totalLabel)     totalLabel.innerText           = 'TOTAL AMOUNT TO BE PAID:';
        if (btnPayIcon)     btnPayIcon.className           = 'bi bi-lock-fill';
        if (btnPayLabel)    btnPayLabel.innerHTML          = 'Continue to Payment &rarr;';
        if (payMethodSection) payMethodSection.style.display = 'block';
      }

      const modal = document.getElementById('payment-summary-modal');
      if (modal) modal.style.display = 'block';
    }

    function openPaymentModalCurrent() {
      const selectedSlug = document.querySelector('input[name="sub_plan"]:checked')?.value || 'starter';
      openPaymentModal(selectedSlug);
    }

    function closePaymentModal() {
      const modal = document.getElementById('payment-summary-modal');
      if (modal) modal.style.display = 'none';
    }

    async function processSubscriptionPayment() {
      const selectedSlug = document.querySelector('input[name="sub_plan"]:checked')?.value || 'starter';
      const selectedPayOption = document.querySelector('input[name="modal_paymethod"]:checked')?.value || enabledPaymentMethodsData[0]?.id || 'gcash';

      const btn = document.getElementById('btn-modal-pay-now');
      const origHtml = btn.innerHTML;
      btn.disabled = true;
      btn.innerHTML = `<i class="bi bi-hourglass-split"></i> Redirecting to PayMongo...`;

      try {
        const res = await Api.post('/pikvero/api/payments/paymongo-checkout.php', {
          plan_slug: selectedSlug,
          payment_method: selectedPayOption,
          // Send the current page URL so PayMongo can redirect back here after payment
          redirect_url: window.location.origin + window.location.pathname
        });

        if (res.success && res.data && res.data.checkout_url) {
          Toast.info('PayMongo Checkout', 'Redirecting to official PayMongo payment portal...');
          saveToLocalStorage();
          closePaymentModal();
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
        Toast.error('Connection Error', 'Failed to connect to PayMongo API. Please try again.');
        btn.disabled = false;
        btn.innerHTML = origHtml;
      }
    }


    function saveToLocalStorage() {
      // Browsers (Chrome, etc.) silently clear type="password" inputs that are
      // inside display:none containers. Step 1 is hidden when payment fires, so
      // ob-pass.value reads '' even if the user filled it in. We preserve the
      // existing saved password whenever the live field is empty, so it is never
      // accidentally overwritten with an empty string.
      const existing = JSON.parse(localStorage.getItem(DRAFT_KEY) || '{}');
      const livePass  = document.getElementById('ob-pass')?.value  || '';
      const liveCpass = document.getElementById('ob-cpass')?.value || '';

      const data = {
        currentStep: currentStep,
        username: document.getElementById('ob-username')?.value || '',
        fname: document.getElementById('ob-fname')?.value || '',
        lname: document.getElementById('ob-lname')?.value || '',
        email: document.getElementById('ob-email')?.value || '',
        phone: document.getElementById('ob-phone')?.value || '',
        pass:  livePass  || existing.pass  || '',   // ← keep saved value if browser cleared it
        cpass: liveCpass || existing.cpass || '',   // ← keep saved value if browser cleared it
        orgname: document.getElementById('ob-orgname')?.value || '',
        taxid: document.getElementById('ob-taxid')?.value || '',
        facname: document.getElementById('ob-facname')?.value || '',
        address: document.getElementById('ob-address')?.value || '',
        city: document.getElementById('ob-city')?.value || '',
        province: document.getElementById('ob-province')?.value || '',
        courtname: document.getElementById('ob-courtname')?.value || '',
        courttype: document.getElementById('ob-courttype')?.value || '',
        price: document.getElementById('ob-price')?.value || '',
        opentime: document.getElementById('ob-opentime')?.value || '',
        closetime: document.getElementById('ob-closetime')?.value || '',
        subplan: document.querySelector('input[name="sub_plan"]:checked')?.value || 'starter',
        paymethod: document.getElementById('ob-paymethod')?.value || '',
        paymentData: activePaymentData,
        docData: savedDocData
      };

      localStorage.setItem(DRAFT_KEY, JSON.stringify(data));
    }


    function restoreFromLocalStorage() {
      const raw = localStorage.getItem(DRAFT_KEY);
      if (!raw) return;

      try {
        const data = JSON.parse(raw);
        if (data.username && document.getElementById('ob-username')) document.getElementById('ob-username').value = data.username;
        if (data.fname) document.getElementById('ob-fname').value = data.fname;
        if (data.lname) document.getElementById('ob-lname').value = data.lname;
        if (data.email) document.getElementById('ob-email').value = data.email;
        if (data.phone) document.getElementById('ob-phone').value = data.phone;
        if (data.pass) document.getElementById('ob-pass').value = data.pass;
        if (data.cpass) document.getElementById('ob-cpass').value = data.cpass;
        if (data.orgname) document.getElementById('ob-orgname').value = data.orgname;
        if (data.taxid) document.getElementById('ob-taxid').value = data.taxid;
        if (data.facname) document.getElementById('ob-facname').value = data.facname;
        if (data.address) document.getElementById('ob-address').value = data.address;
        if (data.city) document.getElementById('ob-city').value = data.city;
        if (data.province) document.getElementById('ob-province').value = data.province;
        if (data.courtname) document.getElementById('ob-courtname').value = data.courtname;
        if (data.courttype) document.getElementById('ob-courttype').value = data.courttype;
        if (data.price) document.getElementById('ob-price').value = data.price;
        if (data.opentime) document.getElementById('ob-opentime').value = data.opentime;
        if (data.closetime) document.getElementById('ob-closetime').value = data.closetime;
        if (data.paymethod) document.getElementById('ob-paymethod').value = data.paymethod;

        if (data.pass) {
          validatePassword();
          validateConfirmPassword();
        }

        if (data.docData) {
          savedDocData = data.docData;
          renderDocPreview();
          const fb = document.getElementById('fb-docs');
          const fileInput = document.getElementById('ob-docs');
          if (fileInput && fb) {
            setFeedback(fileInput, fb, true, `✓ File '${savedDocData.name}' restored from draft.`);
          }
        }

        if (data.paymentData && data.paymentData.completed) {
          activePaymentData = data.paymentData;
          document.getElementById('s9-plan-name').innerText = activePaymentData.planName;
          document.getElementById('s9-pay-method').innerText = activePaymentData.payMethodLabel;
          document.getElementById('s9-base-price').innerText = `₱${parseFloat(activePaymentData.basePrice).toLocaleString('en-US', {minimumFractionDigits: 2})}`;
          document.getElementById('s9-total-paid').innerText = `₱${parseFloat(activePaymentData.totalAmount).toLocaleString('en-US', {minimumFractionDigits: 2})}`;
          document.getElementById('s9-receipt-ref').innerText = `REF: ${activePaymentData.reference}`;
          document.getElementById('s9-payment-date').innerText = activePaymentData.paidDate;

          document.getElementById('s9-payment-pending-box').style.display = 'none';
          document.getElementById('s9-payment-success-box').style.display = 'block';
        }

        if (data.subplan) {
          selectSubPlan(data.subplan);
        }

        if (data.currentStep && data.currentStep >= 1 && data.currentStep <= totalSteps) {
          jumpToStepDirect(data.currentStep);
        }

        Toast.info('Restored Draft', 'Form progress restored from your local storage.');
      } catch (e) {
        console.error(e);
      }
    }

    function clearOnboardingDraft() {
      localStorage.removeItem(DRAFT_KEY);
      Toast.success('Draft Cleared', 'Reset onboarding draft.');
      setTimeout(() => location.reload(), 500);
    }

    async function jumpToStep(targetStep) {
      if (targetStep < 1 || targetStep > totalSteps) return;

      // Sequential Step Security Guard: Check all preceding steps 1..(targetStep-1)
      if (targetStep > currentStep) {
        for (let s = 1; s < targetStep; s++) {
          const isValid = await validateStep(s);
          if (!isValid) {
            Toast.error('Step Security Guard', `You must complete required fields in Step ${s} before proceeding to Step ${targetStep}.`);
            // Render active step N
            jumpToStepDirect(s);
            return;
          }
        }
      }

      jumpToStepDirect(targetStep);
    }

    function jumpToStepDirect(targetStep) {
      document.querySelectorAll('.wizard-step').forEach(el => el.classList.remove('active'));
      document.querySelectorAll('.step-pill').forEach((el, index) => {
        const stepNum = index + 1;
        el.classList.remove('active');
        if (stepNum < targetStep) el.classList.add('completed');
        else el.classList.remove('completed');
      });

      currentStep = targetStep;

      document.getElementById(`step-${currentStep}`).classList.add('active');
      const activePill = document.getElementById(`pill-${currentStep}`);
      if (activePill) {
        activePill.classList.remove('completed');
        activePill.classList.add('active');
      }

      // Update progress track & text
      const percent = Math.round((currentStep / totalSteps) * 100);
      document.getElementById('progress-fill').style.width = `${percent}%`;
      document.getElementById('progress-percent-text').innerText = `${percent}% COMPLETE`;
      document.getElementById('progress-step-text').innerText = `STEP ${currentStep} OF 10: ${stepTitles[currentStep - 1].toUpperCase()}`;

      // Update Navigation Buttons
      document.getElementById('prev-btn').style.display = currentStep > 1 ? 'inline-flex' : 'none';
      document.getElementById('next-btn').style.display = currentStep < totalSteps ? 'inline-flex' : 'none';
      document.getElementById('submit-btn').style.display = currentStep === totalSteps ? 'inline-flex' : 'none';

      // Re-populate password fields whenever Step 1 becomes visible.
      // Chrome silently clears type="password" inputs every time their parent
      // transitions through display:none → display:block, so we must restore
      // them from localStorage each time Step 1 is shown.
      // NOTE: We always overwrite (no !passEl.value guard) so the user's
      // exact saved password wins over any browser autofill injection.
      if (targetStep === 1) {
        setTimeout(() => {
          try {
            const saved = JSON.parse(localStorage.getItem(DRAFT_KEY) || '{}');
            const passEl  = document.getElementById('ob-pass');
            const cpassEl = document.getElementById('ob-cpass');
            if (passEl  && saved.pass)  { passEl.value  = saved.pass;  validatePassword(); }
            if (cpassEl && saved.cpass) { cpassEl.value = saved.cpass; validateConfirmPassword(); }
          } catch (e) {}
        }, 50);
      }

      saveToLocalStorage();
    }


    async function navigateStep(direction) {
      const targetStep = currentStep + direction;
      await jumpToStep(targetStep);
    }

    function selectSubPlan(planName) {
      document.querySelectorAll('.sub-plan-card').forEach(card => {
        card.style.border = '2px solid var(--ink)';
        card.style.background = 'var(--white)';
        card.style.transform = 'none';
        card.style.boxShadow = 'none';
        const badge = card.querySelector('.badge-selected');
        if (badge) badge.style.display = 'none';
      });

      const activeCard = document.getElementById(`plan-card-${planName}`);
      if (activeCard) {
        activeCard.style.border = '3px solid var(--coral)';
        activeCard.style.background = planName === 'pro' ? 'var(--lime)' : 'var(--sand)';
        activeCard.style.transform = 'translateY(-4px)';
        activeCard.style.boxShadow = '4px 4px 0 var(--ink)';
        const badge = activeCard.querySelector('.badge-selected');
        if (badge) badge.style.display = 'inline-block';
      }

      const radio = document.querySelector(`input[name="sub_plan"][value="${planName}"]`);
      if (radio) radio.checked = true;

      // If user changes plan after previous payment authorization, reset payment status
      if (activePaymentData && activePaymentData.planSlug !== planName) {
        activePaymentData = null;
        document.getElementById('s9-payment-pending-box').style.display = 'block';
        document.getElementById('s9-payment-success-box').style.display = 'none';
      }

      saveToLocalStorage();
    }
  </script>
</body>
</html>
