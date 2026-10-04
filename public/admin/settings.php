<?php
$pageTitle  = 'Pikvero — Platform Security & System Settings';
$headExtras = ['jquery'];
require_once __DIR__ . '/../../includes/head.php';
?>
  <style>
    .settings-tab-btn {
      border: 2px solid var(--ink);
      border-bottom: none;
      border-radius: 10px 10px 0 0;
      padding: 10px 18px;
      font-weight: 800;
      font-size: 0.85rem;
      cursor: pointer;
      background: var(--sand);
      color: var(--ink);
      transition: all 0.15s ease;
    }
    .settings-tab-btn.active {
      background: var(--white);
      color: var(--ink);
      border-bottom: 2px solid var(--white);
      box-shadow: 0 -2px 0 var(--coral);
    }
    .settings-panel {
      display: none;
    }
    .settings-panel.active {
      display: block;
    }
    .form-group-streetside {
      margin-bottom: 18px;
    }
    .form-group-streetside label {
      display: block;
      font-weight: 800;
      font-size: 0.82rem;
      margin-bottom: 6px;
      text-transform: uppercase;
      font-family: 'DM Mono', monospace;
      color: var(--ink);
    }
    .form-group-streetside input[type="text"],
    .form-group-streetside input[type="password"],
    .form-group-streetside input[type="number"],
    .form-group-streetside input[type="email"],
    .form-group-streetside textarea {
      width: 100%;
      padding: 10px 14px;
      border: 2px solid var(--ink);
      border-radius: 8px;
      font-family: inherit;
      font-weight: 700;
      font-size: 0.88rem;
      background: var(--white);
    }
    .logo-preview-box {
      width: 120px;
      height: 120px;
      border: 2px dashed var(--ink);
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      background: #f8faf9;
      overflow: hidden;
    }
    .logo-preview-box img {
      max-width: 100%;
      max-height: 100%;
      object-fit: contain;
    }
    .toggle-switch-card {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 14px 18px;
      border: 2px solid var(--ink);
      border-radius: 10px;
      background: #fdfdfd;
      margin-bottom: 12px;
    }
  </style>
</head>
<body>

  <div id="sidebar-container"></div>
  <div id="navbar-container"></div>

  <main class="portal-main">
    <div id="settings-page-content">
      <div style="margin-bottom:24px; display:flex; justify-content:space-between; align-items:flex-end; flex-wrap:wrap; gap:12px;">
        <div>
          <div class="eyebrow">GLOBAL SYSTEM CONFIGURATION</div>
          <h1 style="font-size: clamp(1.8rem, 4vw, 2.2rem); font-weight:800; text-transform:uppercase; margin:4px 0 0;">SECURITY &amp; SETTINGS</h1>
        </div>
        <div>
          <button type="button" onclick="submitSaveSettings()" class="button lime" style="padding:10px 22px; font-size:0.88rem;">
            <i class="bi bi-floppy-fill"></i> Save Settings
          </button>
        </div>
      </div>

      <!-- Settings Tabs Header -->
      <div style="display:flex; gap:6px; border-bottom:2px solid var(--ink); padding-left:8px; margin-bottom:0; flex-wrap:wrap;">
        <button onclick="switchSettingsTab('branding', this)" class="settings-tab-btn active"><i class="bi bi-palette-fill"></i> Identity &amp; Branding</button>
        <button onclick="switchSettingsTab('security', this)" class="settings-tab-btn"><i class="bi bi-shield-lock-fill"></i> Security Policies</button>
        <button onclick="switchSettingsTab('payment', this)" class="settings-tab-btn"><i class="bi bi-credit-card-2-front-fill"></i> PayMongo &amp; Gateways</button>
        <button onclick="switchSettingsTab('booking', this)" class="settings-tab-btn"><i class="bi bi-sliders"></i> Operating Rules</button>
        <button onclick="switchSettingsTab('system', this)" class="settings-tab-btn"><i class="bi bi-tools"></i> System &amp; Maintenance</button>
      </div>

      <!-- Settings Content Container -->
      <div class="card-streetside" style="padding:28px; background:var(--white); border-top-left-radius:0;">
        <form id="settings-form" onsubmit="event.preventDefault(); submitSaveSettings();">

          <!-- TAB 1: IDENTITY & BRANDING -->
          <div id="tab-panel-branding" class="settings-panel active">
            <h3 style="font-size:1.1rem; font-weight:800; text-transform:uppercase; margin-bottom:18px; border-bottom:1px solid var(--line); padding-bottom:8px;">
              ORGANIZATION &amp; BRANDING IDENTITY
            </h3>

            <div style="display:flex; gap:24px; flex-wrap:wrap; margin-bottom:20px; align-items:flex-start;">
              <div>
                <label style="display:block; font-weight:800; font-size:0.82rem; margin-bottom:6px; font-family:'DM Mono', monospace;">ORGANIZATION LOGO</label>
                <div class="logo-preview-box" id="logo-preview-container">
                  <img id="logo-preview-img" src="/pikvero/assets/images/logo.png" alt="Logo Preview" onerror="this.src='/pikvero/assets/images/logo-placeholder.png'">
                </div>
                <input type="file" id="logo-file-input" accept="image/*" style="display:none;" onchange="handleLogoFileSelect(this)">
                <button type="button" onclick="document.getElementById('logo-file-input').click()" class="button coral" style="width:100%; margin-top:10px; padding:8px 12px; font-size:0.78rem;">
                  <i class="bi bi-upload"></i> Upload Logo File
                </button>
              </div>
              <div style="flex:1; min-width:280px;">
                <div class="form-group-streetside">
                  <label for="set-org_logo_url">ORGANIZATION LOGO IMAGE URL</label>
                  <input type="text" id="set-org_logo_url" name="org_logo_url" placeholder="/pikvero/assets/images/logo.png" oninput="updateLogoPreview(this.value)">
                  <div style="font-size:0.72rem; color:#4a5c56; margin-top:4px;">Upload an image file using the button above or enter a relative/external HTTPS logo URL.</div>
                </div>

                <div class="form-group-streetside">
                  <label for="set-org_name">ORGANIZATION NAME</label>
                  <input type="text" id="set-org_name" name="org_name" placeholder="Pikvero SaaS Philippines">
                </div>
              </div>
            </div>

            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap:16px;">
              <div class="form-group-streetside">
                <label for="set-app_name">APPLICATION BRAND TITLE</label>
                <input type="text" id="set-app_name" name="app_name" placeholder="Pikvero">
              </div>

              <div class="form-group-streetside">
                <label for="set-support_email">SUPPORT EMAIL ADDRESS</label>
                <input type="email" id="set-support_email" name="support_email" placeholder="support@pikvero.com">
              </div>

              <div class="form-group-streetside">
                <label for="set-contact_phone">CONTACT PHONE NUMBER</label>
                <input type="text" id="set-contact_phone" name="contact_phone" placeholder="09181112222">
              </div>
            </div>

            <div class="form-group-streetside">
              <label for="set-hero_tagline">PLATFORM HERO TAGLINE</label>
              <input type="text" id="set-hero_tagline" name="hero_tagline" placeholder="Empowering Pickleball Communities & Court Facility Management">
            </div>
          </div>

          <!-- TAB 2: SECURITY POLICIES -->
          <div id="tab-panel-security" class="settings-panel">
            <h3 style="font-size:1.1rem; font-weight:800; text-transform:uppercase; margin-bottom:18px; border-bottom:1px solid var(--line); padding-bottom:8px;">
              SYSTEM SECURITY &amp; AUTHENTICATION POLICIES
            </h3>

            <div class="toggle-switch-card">
              <div>
                <strong style="display:block; font-size:0.88rem;">Enforce Strong Password Requirements</strong>
                <span style="font-size:0.75rem; color:#4a5c56;">Requires minimum 6 characters with mixed letters and numbers upon user registration.</span>
              </div>
              <input type="checkbox" id="set-require_strong_pass" class="log-checkbox" style="transform:scale(1.4);">
            </div>

            <div class="toggle-switch-card">
              <div>
                <strong style="display:block; font-size:0.88rem;">Two-Factor Authentication (2FA) Mandatory Guard</strong>
                <span style="font-size:0.75rem; color:#4a5c56;">Mandates email TOTP verification codes for administrative accounts upon login.</span>
              </div>
              <input type="checkbox" id="set-two_factor_auth_required" class="log-checkbox" style="transform:scale(1.4);">
            </div>

            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap:16px; margin-top:16px;">
              <div class="form-group-streetside">
                <label for="set-max_login_attempts">MAX LOGIN ATTEMPTS LOCKOUT THRESHOLD</label>
                <input type="number" id="set-max_login_attempts" name="max_login_attempts" min="3" max="10" placeholder="5">
              </div>

              <div class="form-group-streetside">
                <label for="set-session_timeout_minutes">SESSION INACTIVITY TIMEOUT (MINUTES)</label>
                <input type="number" id="set-session_timeout_minutes" name="session_timeout_minutes" min="15" max="1440" placeholder="60">
              </div>

              <div class="form-group-streetside">
                <label for="set-audit_retention_days">SECURITY AUDIT LOG RETENTION (DAYS)</label>
                <input type="number" id="set-audit_retention_days" name="audit_retention_days" min="30" max="365" placeholder="90">
              </div>
            </div>
          </div>

          <!-- TAB 3: PAYMONGO & PAYMENT GATEWAYS -->
          <div id="tab-panel-payment" class="settings-panel">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:18px; border-bottom:1px solid var(--line); padding-bottom:8px; flex-wrap:wrap; gap:10px;">
              <div>
                <h3 style="font-size:1.1rem; font-weight:800; text-transform:uppercase; margin:0;">
                  <i class="bi bi-wallet2" style="color:var(--coral);"></i> PAYMONGO PAYMENT GATEWAY &amp; CHANNEL INTEGRATION
                </h3>
                <div style="font-size:0.75rem; color:#4a5c56; margin-top:2px;">Configure PayMongo API credentials, webhook signature keys, and active payment channels for court bookings &amp; subscriptions.</div>
              </div>
              <span class="badge-streetside lime" style="font-size:0.75rem; font-family:'DM Mono', monospace;"><i class="bi bi-patch-check-fill"></i> Official PayMongo v2 Integration</span>
            </div>

            <!-- Environment Mode Selection -->
            <div style="background:#f8faf9; border:2px solid var(--ink); border-radius:10px; padding:16px; margin-bottom:20px;">
              <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
                <div>
                  <strong style="font-size:0.9rem; text-transform:uppercase; font-family:'DM Mono', monospace;">GATEWAY ENVIRONMENT MODE</strong>
                  <div style="font-size:0.75rem; color:#4a5c56;">Select Sandbox/Test mode for development or Production/Live for real customer billing.</div>
                </div>
                <div style="min-width:240px;">
                  <select id="set-paymongo_mode" name="paymongo_mode" style="width:100%; padding:8px 12px; border:2px solid var(--ink); border-radius:8px; font-family:'DM Mono', monospace; font-weight:800; font-size:0.85rem; background:var(--white);">
                    <option value="test">🧪 TEST / SANDBOX MODE (pk_test / sk_test)</option>
                    <option value="live">🔴 LIVE / PRODUCTION MODE (pk_live / sk_live)</option>
                  </select>
                </div>
              </div>
            </div>

            <!-- API Credentials -->
            <h4 style="font-size:0.85rem; font-weight:800; font-family:'DM Mono', monospace; text-transform:uppercase; margin-bottom:12px;">API KEYS &amp; WEBHOOK SIGNATURES</h4>
            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap:16px; margin-bottom:24px;">
              <div class="form-group-streetside">
                <label for="set-paymongo_public_key">PAYMONGO PUBLIC API KEY (pk_test_... / pk_live_...)</label>
                <input type="text" id="set-paymongo_public_key" name="paymongo_public_key" placeholder="pk_test_sample1234567890abcdef" style="font-family:'DM Mono', monospace;">
              </div>

              <div class="form-group-streetside">
                <label for="set-paymongo_secret_key">PAYMONGO SECRET API KEY (sk_test_... / sk_live_...)</label>
                <input type="password" id="set-paymongo_secret_key" name="paymongo_secret_key" placeholder="sk_test_sample1234567890abcdef" style="font-family:'DM Mono', monospace;">
              </div>

              <div class="form-group-streetside">
                <label for="set-paymongo_webhook_secret">WEBHOOK SIGNATURE SECRET KEY (whsec_...)</label>
                <input type="password" id="set-paymongo_webhook_secret" name="paymongo_webhook_secret" placeholder="whsec_sample1234567890abcdef" style="font-family:'DM Mono', monospace;">
              </div>
            </div>

            <!-- Key Verification & Channel Discovery Button -->
            <div style="background:#f0fdf4; border:2px solid var(--ink); border-radius:10px; padding:18px; margin-bottom:24px;">
              <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:14px;">
                <div>
                  <strong style="font-size:0.92rem; font-family:'DM Mono', monospace; text-transform:uppercase; display:block;">
                    <i class="bi bi-shield-check" style="color:var(--green);"></i> PAYMONGO CREDENTIAL VERIFICATION
                  </strong>
                  <div style="font-size:0.78rem; color:#3b4e48; margin-top:2px;">
                    Click below to test your inputted PayMongo Public and Secret API keys against the PayMongo gateway and load all active payment methods.
                  </div>
                </div>
                <div style="display:flex; gap:10px; align-items:center;">
                  <button type="button" id="btn-verify-paymongo" onclick="verifyPaymongoKeys()" class="button lime" style="padding:10px 20px; font-size:0.85rem;">
                    <i class="bi bi-search"></i> See Available Payment Methods
                  </button>
                </div>
              </div>

              <!-- Verification Results Alert Box -->
              <div id="paymongo-verification-result" style="display:none; margin-top:14px; padding:12px 16px; border-radius:8px; border:2px solid var(--ink); font-size:0.85rem; font-family:'DM Mono', monospace; font-weight:700;">
              </div>
            </div>

            <!-- Active Payment Channels Header & Checkboxes -->
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; flex-wrap:wrap; gap:10px;">
              <h4 style="font-size:0.85rem; font-weight:800; font-family:'DM Mono', monospace; text-transform:uppercase; margin:0;">
                ACTIVE PAYMONGO PAYMENT CHANNELS
              </h4>
              <span id="channels-count-badge" class="badge-streetside sand" style="font-size:0.7rem; font-family:'DM Mono', monospace;">
                7 PH Channels Configured
              </span>
            </div>

            <div id="paymongo-channels-grid" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap:12px; margin-bottom:24px;">
              
              <div class="toggle-switch-card" style="margin-bottom:0;">
                <div>
                  <strong style="display:block; font-size:0.88rem;"><i class="bi bi-qr-code" style="color:#005ce6;"></i> GCash E-Wallet</strong>
                  <span style="font-size:0.72rem; color:#4a5c56;">Allow instant GCash e-wallet Checkout.</span>
                </div>
                <input type="checkbox" id="set-paymongo_enable_gcash" class="log-checkbox" style="transform:scale(1.4);">
              </div>

              <div class="toggle-switch-card" style="margin-bottom:0;">
                <div>
                  <strong style="display:block; font-size:0.88rem;"><i class="bi bi-phone-vibrate" style="color:#00b14f;"></i> GrabPay E-Wallet</strong>
                  <span style="font-size:0.72rem; color:#4a5c56;">Allow GrabPay e-wallet transactions.</span>
                </div>
                <input type="checkbox" id="set-paymongo_enable_grabpay" class="log-checkbox" style="transform:scale(1.4);">
              </div>

              <div class="toggle-switch-card" style="margin-bottom:0;">
                <div>
                  <strong style="display:block; font-size:0.88rem;"><i class="bi bi-wallet" style="color:#2baf67;"></i> Maya / PayMaya E-Wallet</strong>
                  <span style="font-size:0.72rem; color:#4a5c56;">Allow Maya e-wallet transactions.</span>
                </div>
                <input type="checkbox" id="set-paymongo_enable_paymaya" class="log-checkbox" style="transform:scale(1.4);">
              </div>

              <div class="toggle-switch-card" style="margin-bottom:0;">
                <div>
                  <strong style="display:block; font-size:0.88rem;"><i class="bi bi-credit-card-2-front-fill" style="color:var(--coral);"></i> Credit / Debit Cards</strong>
                  <span style="font-size:0.72rem; color:#4a5c56;">Visa, Mastercard, and JCB cards.</span>
                </div>
                <input type="checkbox" id="set-paymongo_enable_cards" class="log-checkbox" style="transform:scale(1.4);">
              </div>

              <div class="toggle-switch-card" style="margin-bottom:0;">
                <div>
                  <strong style="display:block; font-size:0.88rem;"><i class="bi bi-qr-code-scan" style="color:var(--ink);"></i> QR Ph National Standard</strong>
                  <span style="font-size:0.72rem; color:#4a5c56;">Scan to pay via any Philippine banking app.</span>
                </div>
                <input type="checkbox" id="set-paymongo_enable_qrph" class="log-checkbox" style="transform:scale(1.4);">
              </div>

              <div class="toggle-switch-card" style="margin-bottom:0;">
                <div>
                  <strong style="display:block; font-size:0.88rem;"><i class="bi bi-bank" style="color:#0284c7;"></i> Direct Online Banking / OTC</strong>
                  <span style="font-size:0.72rem; color:#4a5c56;">BDO, BPI, Landbank &amp; UnionBank OTC.</span>
                </div>
                <input type="checkbox" id="set-paymongo_enable_dob" class="log-checkbox" style="transform:scale(1.4);">
              </div>

              <div class="toggle-switch-card" style="margin-bottom:0;">
                <div>
                  <strong style="display:block; font-size:0.88rem;"><i class="bi bi-bag-check-fill" style="color:#6366f1;"></i> BillEase BNPL Installments</strong>
                  <span style="font-size:0.72rem; color:#4a5c56;">Buy Now, Pay Later flexible payments.</span>
                </div>
                <input type="checkbox" id="set-paymongo_enable_billease" class="log-checkbox" style="transform:scale(1.4);">
              </div>

            </div>

            <!-- Continue & Save Payment Config Button -->
            <div style="margin-bottom:24px; padding:14px; background:var(--sand); border:2px solid var(--ink); border-radius:10px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
              <span style="font-size:0.82rem; font-weight:700; font-family:'DM Mono', monospace; color:#3b4e48;">
                <i class="bi bi-check2-circle" style="color:var(--green);"></i> Confirm your checked payment methods and save changes to database.
              </span>
              <button type="button" onclick="submitSaveSettings()" class="button coral" style="padding:10px 22px; font-size:0.88rem;">
                Continue &amp; Save Payment Methods to Database &rarr;
              </button>
            </div>

            <!-- Currency & Fee Surcharge Rules -->
            <h4 style="font-size:0.85rem; font-weight:800; font-family:'DM Mono', monospace; text-transform:uppercase; margin-bottom:12px;">CURRENCY &amp; TRANSACTION FEE SETTINGS</h4>
            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap:16px;">
              <div class="form-group-streetside">
                <label for="set-currency_code">DEFAULT CURRENCY CODE</label>
                <input type="text" id="set-currency_code" name="currency_code" placeholder="PHP" style="font-family:'DM Mono', monospace;">
              </div>

              <div class="form-group-streetside">
                <label for="set-currency_symbol">CURRENCY SYMBOL</label>
                <input type="text" id="set-currency_symbol" name="currency_symbol" placeholder="₱">
              </div>

              <div class="toggle-switch-card" style="margin-bottom:0;">
                <div>
                  <strong style="display:block; font-size:0.88rem;">Pass Gateway Fees to Customer</strong>
                  <span style="font-size:0.72rem; color:#4a5c56;">Automatically add PayMongo processing fee to final booking total.</span>
                </div>
                <input type="checkbox" id="set-payment_gateway_fee_pass" class="log-checkbox" style="transform:scale(1.4);">
              </div>
            </div>
          </div>

          <!-- TAB 4: OPERATING RULES -->
          <div id="tab-panel-booking" class="settings-panel">
            <h3 style="font-size:1.1rem; font-weight:800; text-transform:uppercase; margin-bottom:18px; border-bottom:1px solid var(--line); padding-bottom:8px;">
              BUSINESS &amp; BOOKING OPERATING RULES
            </h3>

            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap:16px;">
              <div class="form-group-streetside">
                <label for="set-platform_commission_pct">PLATFORM SAAS COMMISSION FEE RATE (%)</label>
                <input type="number" step="0.1" id="set-platform_commission_pct" name="platform_commission_pct" min="0" max="50" placeholder="10.0">
              </div>

              <div class="form-group-streetside">
                <label for="set-paymongo_fee_percent">PAYMONGO GATEWAY FEE RATE (%)</label>
                <input type="number" step="0.1" id="set-paymongo_fee_percent" name="paymongo_fee_percent" min="0" max="20" placeholder="2.5">
              </div>

              <div class="form-group-streetside">
                <label for="set-advance_booking_days">MAX ADVANCE BOOKING WINDOW (DAYS)</label>
                <input type="number" id="set-advance_booking_days" name="advance_booking_days" min="1" max="90" placeholder="30">
              </div>

              <div class="form-group-streetside">
                <label for="set-cancellation_notice_hours">CANCELLATION REFUND NOTICE WINDOW (HOURS)</label>
                <input type="number" id="set-cancellation_notice_hours" name="cancellation_notice_hours" min="0" max="72" placeholder="24">
              </div>
            </div>
          </div>

          <!-- TAB 5: SYSTEM & MAINTENANCE -->
          <div id="tab-panel-system" class="settings-panel">
            <h3 style="font-size:1.1rem; font-weight:800; text-transform:uppercase; margin-bottom:18px; border-bottom:1px solid var(--line); padding-bottom:8px;">
              SYSTEM STATUS &amp; MAINTENANCE CONTROLS
            </h3>

            <div class="toggle-switch-card" style="background:#fff3f0; border-color:var(--coral);">
              <div>
                <strong style="display:block; font-size:0.88rem; color:var(--coral);"><i class="bi bi-exclamation-triangle-fill"></i> System Maintenance Mode</strong>
                <span style="font-size:0.75rem; color:#4a5c56;">Restricts platform access for non-administrator users while performing system upgrades.</span>
              </div>
              <input type="checkbox" id="set-maintenance_mode" class="log-checkbox" style="transform:scale(1.4);">
            </div>

            <div class="form-group-streetside" style="margin-top:14px;">
              <label for="set-maintenance_message">MAINTENANCE ANNOUNCEMENT BANNER MESSAGE</label>
              <textarea id="set-maintenance_message" name="maintenance_message" rows="3" placeholder="Pikvero is undergoing scheduled maintenance. Services will resume shortly."></textarea>
            </div>
          </div>

        </form>
      </div>
    </div>

    <!-- 403 FORBIDDEN CONTAINER -->
    <div id="settings-forbidden-card" style="display:none; max-width:600px; margin:40px auto;">
      <div class="card-streetside" style="padding:32px; background:var(--white); text-align:center;">
        <div style="font-size:3rem; color:var(--coral); margin-bottom:12px;"><i class="bi bi-shield-slash-fill"></i></div>
        <div class="eyebrow" style="color:var(--coral);">ACCESS RESTRICTED</div>
        <h2 style="font-size:1.5rem; font-weight:800; text-transform:uppercase; margin:8px 0;">403 PERMISSION DENIED</h2>
        <p style="font-size:0.9rem; color:#4a5c56; margin-bottom:20px;">
          You do not have the required permission (<code>settings.manage</code>) to access or modify system settings.
        </p>
        <a href="/pikvero/public/admin/dashboard.php" class="button dark" style="padding:10px 20px; text-transform:uppercase; font-size:0.85rem;">Return to Dashboard</a>
      </div>
    </div>

    <div id="footer-container"></div>
  </main>

  <?php require_once __DIR__ . '/../../includes/scripts.php'; ?>
  <script>
    let settingsMap = {};

    document.addEventListener('DOMContentLoaded', async () => {
      const userCtx = await AuthHelper.checkSession();
      await NavbarComponent.render('#navbar-container', true);
      SidebarComponent.render('settings', 'admin');
      FooterComponent.render('#footer-container', true);

      // Determine RBAC permission for Settings
      const role = userCtx ? (userCtx.role || (userCtx.user ? userCtx.user.role_name : '')) : '';
      const perms = (userCtx && userCtx.permissions) ? userCtx.permissions : [];

      const canManage = (role === 'super_admin' || role === 'platform_admin' || perms.includes('settings.manage') || perms.includes('system.manage'));

      if (!canManage) {
        window.location.href = '/pikvero/public/403.php?permission=settings.manage';
        return;
      }

      loadSystemSettings();
    });

    function switchSettingsTab(tabKey, btn) {
      document.querySelectorAll('.settings-tab-btn').forEach(b => b.classList.remove('active'));
      document.querySelectorAll('.settings-panel').forEach(p => p.classList.remove('active'));

      btn.classList.add('active');
      const panel = document.getElementById(`tab-panel-${tabKey}`);
      if (panel) panel.classList.add('active');
    }

    function updateLogoPreview(url) {
      const img = document.getElementById('logo-preview-img');
      if (img) {
        img.src = url || '/pikvero/assets/images/logo-placeholder.png';
      }
    }

    async function handleLogoFileSelect(fileInput) {
      if (!fileInput.files || fileInput.files.length === 0) return;

      const file = fileInput.files[0];
      const formData = new FormData();
      formData.append('logo', file);

      // Instant client-side image preview
      const reader = new FileReader();
      reader.onload = function(e) {
        updateLogoPreview(e.target.result);
      };
      reader.readAsDataURL(file);

      Toast.info('Uploading Logo', 'Uploading organization logo image...');

      try {
        const response = await fetch('/pikvero/api/admin/settings/upload-logo.php', {
          method: 'POST',
          body: formData
        });
        const res = await response.json();

        if (res.success) {
          Toast.success('Logo Uploaded', res.message);
          const logoUrl = res.data.logo_url;
          document.getElementById('set-org_logo_url').value = logoUrl;
          updateLogoPreview(logoUrl);
        } else {
          Toast.error('Upload Failed', res.message || 'Failed to upload logo image.');
        }
      } catch (err) {
        console.error(err);
        Toast.error('Upload Failed', 'An error occurred while uploading the logo file.');
      }
    }

    async function verifyPaymongoKeys() {
      const mode = document.getElementById('set-paymongo_mode').value;
      const publicKey = document.getElementById('set-paymongo_public_key').value.trim();
      const secretKey = document.getElementById('set-paymongo_secret_key').value.trim();
      const resBox = document.getElementById('paymongo-verification-result');

      if (!publicKey || !secretKey) {
        Toast.error('Validation Error', 'Please enter both PayMongo Public Key and Secret Key before verifying.');
        return;
      }

      const btn = document.getElementById('btn-verify-paymongo');
      const originalText = btn.innerHTML;
      btn.disabled = true;
      btn.innerHTML = `<i class="bi bi-hourglass-split"></i> Verifying Keys with PayMongo API...`;

      try {
        const res = await Api.post('/pikvero/api/admin/settings/verify-paymongo.php', {
          mode: mode,
          public_key: publicKey,
          secret_key: secretKey
        });

        if (res.success && res.data) {
          Toast.success('PayMongo Verified', res.message);

          if (resBox) {
            resBox.style.display = 'block';
            resBox.style.background = '#dcfce7';
            resBox.style.borderColor = 'var(--ink)';
            resBox.style.color = '#15803d';
            resBox.innerHTML = `
              <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px;">
                <span><i class="bi bi-check-circle-fill"></i> PAYMONGO KEYS VERIFIED &amp; AUTHENTICATED (${res.data.mode.toUpperCase()} MODE)</span>
                <span class="badge-streetside lime" style="font-size:0.7rem;">${res.data.channels.length} PAYMENT CHANNELS AVAILABLE</span>
              </div>
            `;
          }

          // Pre-check verified payment channel checkboxes
          if (res.data.channels && Array.isArray(res.data.channels)) {
            res.data.channels.forEach(ch => {
              const el = document.getElementById(`set-${ch.code}`);
              if (el && !el.checked) {
                el.checked = true;
              }
            });
          }
        } else {
          if (resBox) {
            resBox.style.display = 'block';
            resBox.style.background = '#fee2e2';
            resBox.style.color = '#b91c1c';
            resBox.innerHTML = `<i class="bi bi-x-circle-fill"></i> VERIFICATION FAILED: ${res.message || 'Invalid PayMongo keys.'}`;
          }
        }
      } catch (err) {
        console.error(err);
        if (resBox) {
          resBox.style.display = 'block';
          resBox.style.background = '#fee2e2';
          resBox.style.color = '#b91c1c';
          resBox.innerHTML = `<i class="bi bi-x-circle-fill"></i> API Authentication error. Please verify your PayMongo secret key.`;
        }
      } finally {
        btn.disabled = false;
        btn.innerHTML = originalText;
      }
    }

    async function loadSystemSettings() {
      try {
        const res = await Api.get('/pikvero/api/admin/settings.php');
        if (res.success && res.data) {
          settingsMap = res.data;
          populateSettingsForm(settingsMap);
        }
      } catch (err) {
        console.error(err);
      }
    }

    function populateSettingsForm(data) {
      for (const [key, val] of Object.entries(data)) {
        const el = document.getElementById(`set-${key}`);
        if (!el) continue;

        if (el.type === 'checkbox') {
          el.checked = (val === '1' || val === 1 || val === 'true' || val === true);
        } else {
          el.value = val !== null ? val : '';
          if (key === 'org_logo_url') {
            updateLogoPreview(val);
          }
        }
      }
    }

    async function submitSaveSettings() {
      const payload = {
        org_logo_url: document.getElementById('set-org_logo_url').value.trim(),
        org_name: document.getElementById('set-org_name').value.trim(),
        app_name: document.getElementById('set-app_name').value.trim(),
        support_email: document.getElementById('set-support_email').value.trim(),
        contact_phone: document.getElementById('set-contact_phone').value.trim(),
        hero_tagline: document.getElementById('set-hero_tagline').value.trim(),

        require_strong_pass: document.getElementById('set-require_strong_pass').checked ? '1' : '0',
        two_factor_auth_required: document.getElementById('set-two_factor_auth_required').checked ? '1' : '0',
        max_login_attempts: document.getElementById('set-max_login_attempts').value,
        session_timeout_minutes: document.getElementById('set-session_timeout_minutes').value,
        audit_retention_days: document.getElementById('set-audit_retention_days').value,

        paymongo_mode: document.getElementById('set-paymongo_mode').value,
        paymongo_public_key: document.getElementById('set-paymongo_public_key').value.trim(),
        paymongo_secret_key: document.getElementById('set-paymongo_secret_key').value.trim(),
        paymongo_webhook_secret: document.getElementById('set-paymongo_webhook_secret').value.trim(),

        paymongo_enable_gcash: document.getElementById('set-paymongo_enable_gcash').checked ? '1' : '0',
        paymongo_enable_grabpay: document.getElementById('set-paymongo_enable_grabpay').checked ? '1' : '0',
        paymongo_enable_paymaya: document.getElementById('set-paymongo_enable_paymaya').checked ? '1' : '0',
        paymongo_enable_cards: document.getElementById('set-paymongo_enable_cards').checked ? '1' : '0',
        paymongo_enable_qrph: document.getElementById('set-paymongo_enable_qrph').checked ? '1' : '0',
        paymongo_enable_dob: document.getElementById('set-paymongo_enable_dob')?.checked ? '1' : '0',
        paymongo_enable_billease: document.getElementById('set-paymongo_enable_billease')?.checked ? '1' : '0',

        currency_code: document.getElementById('set-currency_code').value.trim(),
        currency_symbol: document.getElementById('set-currency_symbol').value.trim(),
        payment_gateway_fee_pass: document.getElementById('set-payment_gateway_fee_pass').checked ? '1' : '0',

        platform_commission_pct: document.getElementById('set-platform_commission_pct').value,
        paymongo_fee_percent: document.getElementById('set-paymongo_fee_percent').value,
        advance_booking_days: document.getElementById('set-advance_booking_days').value,
        cancellation_notice_hours: document.getElementById('set-cancellation_notice_hours').value,

        maintenance_mode: document.getElementById('set-maintenance_mode').checked ? '1' : '0',
        maintenance_message: document.getElementById('set-maintenance_message').value.trim()
      };

      try {
        const res = await Api.post('/pikvero/api/admin/settings/update.php', payload);
        if (res.success) {
          Toast.success('Settings Saved', res.message);
          loadSystemSettings();
        }
      } catch (err) {
        console.error(err);
      }
    }
  </script>
</body>
</html>
