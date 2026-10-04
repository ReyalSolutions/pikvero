<?php
$pageTitle  = 'Pikvero — Subscription Plans & Tiers';
$headExtras = ['datatables'];
require_once __DIR__ . '/../../includes/head.php';
?>
  <style>
    /* Streetside DataTables Styling Overrides */
    .dataTables_wrapper {
      padding: 0;
      font-family: inherit;
    }
    .dataTables_wrapper .dataTables_length select,
    .dataTables_wrapper .dataTables_filter input {
      border: 2px solid var(--ink);
      border-radius: 8px;
      padding: 5px 10px;
      font-weight: 700;
      background: var(--white);
      outline: none;
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button.current {
      background: var(--lime) !important;
      border: 2px solid var(--ink) !important;
      border-radius: 6px !important;
      font-weight: 800 !important;
      color: var(--ink) !important;
    }
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
    .plan-card-streetside:hover {
      transform: translateY(-3px);
      box-shadow: 6px 6px 0 var(--ink);
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
    .perk-input-row {
      display: flex;
      gap: 8px;
      margin-bottom: 8px;
      align-items: center;
    }
    .form-group-wrap {
      margin-bottom: 14px;
    }
    .form-group-wrap label {
      display: block;
      margin-bottom: 4px;
      font-family: 'DM Mono', monospace;
      font-weight: 800;
      font-size: 0.78rem;
      text-transform: uppercase;
      color: var(--ink);
    }
    .form-input-ctrl {
      width: 100%;
      padding: 9px 12px;
      border: 2px solid var(--ink);
      border-radius: 8px;
      font-family: inherit;
      font-weight: 700;
      background: var(--white);
      outline: none;
      transition: border-color 0.2s ease;
    }
    .form-input-ctrl:focus {
      border-color: var(--green);
    }
    .field-err-msg {
      display: block;
      font-size: 0.72rem;
      font-weight: 800;
      color: var(--coral);
      margin-top: 3px;
      min-height: 16px;
    }
    #plan-modal .card-streetside::-webkit-scrollbar {
      display: none;
    }
    #plan-modal .card-streetside {
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
          <div class="eyebrow"><i class="bi bi-award-fill" style="color:var(--coral);"></i> SAAS TIER CONFIGURATION</div>
          <h1 style="font-size: clamp(1.8rem, 4vw, 2.2rem); font-weight:800; text-transform:uppercase; margin:4px 0 0;">SUBSCRIPTION PLANS &amp; PERKS</h1>
        </div>
        <button id="btn-add-plan" onclick="openCreatePlanModal()" class="button coral" style="padding:10px 18px; font-size:0.85rem; display:none;">
          <i class="bi bi-plus-lg"></i> Add Subscription Plan
        </button>
      </div>

      <!-- Subscription Plans Cards Grid -->
      <div id="section-available-tiers" style="margin-bottom:36px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; flex-wrap:wrap; gap:12px;">
          <div class="mono" style="font-weight:800; font-size:0.85rem; color:var(--green); text-transform:uppercase;">
            ACTIVE SAAS PLANS &amp; INCLUDED PERKS
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
          <!-- Rendered via JS -->
        </div>
      </div>

      <!-- Active Tenant Subscriptions Overview Table -->
      <div id="section-payment-history" class="card-streetside" style="padding:24px; background:var(--white); margin-bottom:24px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; flex-wrap:wrap; gap:10px;">
          <div>
            <h3 style="font-size:1.15rem; font-weight:900; text-transform:uppercase; margin:0 0 2px;">
              <i class="bi bi-building-check" style="color:var(--green); margin-right:6px;"></i>SUBSCRIBED TENANT ORGANIZATIONS
            </h3>
            <span style="font-size:0.78rem; color:#5a7060;">Overview of active court owner SaaS subscriptions</span>
          </div>
          <span id="subs-count-badge" class="badge-streetside lime" style="font-size:0.78rem;">0 Subscriptions</span>
        </div>

        <div style="overflow-x:auto;">
          <table class="table-streetside" style="width:100%; border-collapse:collapse;" id="active-subs-table">
            <thead>
              <tr style="border-bottom:2px solid var(--ink); text-align:left; font-family:'DM Mono', monospace; font-size:0.75rem; text-transform:uppercase;">
                <th style="padding:10px;">ORGANIZATION &amp; PLAN</th>
                <th style="padding:10px;">BILLING RATE &amp; CYCLE</th>
                <th style="padding:10px;">STATUS &amp; EXPIRY</th>
                <th style="padding:10px; text-align:right;">ACTIONS</th>
              </tr>
            </thead>
            <tbody id="active-subs-tbody">
              <!-- Loaded via JS -->
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ADMIN BYPASS SUBSCRIPTION UPGRADE MODAL -->
    <div class="modal-overlay" id="bypass-upgrade-modal" style="display:none; position:fixed; inset:0; z-index:99999 !important; background:rgba(10,20,15,0.78); backdrop-filter:blur(4px); place-items:center; padding:20px;">
      <div class="card-streetside" style="width:min(520px, 100%); padding:24px; background:var(--white);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; border-bottom:2px solid var(--ink); padding-bottom:10px;">
          <h3 style="font-size:1.1rem; font-weight:800; text-transform:uppercase; margin:0;"><i class="bi bi-shield-lock-fill" style="color:var(--coral);"></i> ADMIN BYPASS SUBSCRIPTION UPGRADE</h3>
          <button onclick="closeModal('bypass-upgrade-modal')" class="button sand" style="padding:2px 8px; font-size:0.8rem;">✕</button>
        </div>

        <input type="hidden" id="bypass-subscription-id">

        <div style="background:#f8faf9; border:2px solid var(--ink); border-radius:10px; padding:14px; margin-bottom:16px;">
          <div style="font-size:0.7rem; font-weight:800; font-family:'DM Mono', monospace; color:#4a5c56;">TENANT ORGANIZATION</div>
          <div style="font-weight:900; font-size:1.1rem; color:var(--ink);" id="bypass-org-name">Organization Name</div>
          <div style="font-size:0.75rem; color:#4a5c56; margin-top:2px;">Current Active Plan: <strong id="bypass-current-plan">Starter Plan</strong></div>
        </div>

        <div class="form-group-wrap">
          <label for="bypass-plan-id">SELECT NEW SUBSCRIPTION PLAN TIER *</label>
          <select id="bypass-plan-id" class="form-input-ctrl" required>
            <!-- Loaded dynamically -->
          </select>
        </div>

        <div class="form-group-wrap">
          <label>SELECT BILLING CYCLE *</label>
          <div style="display:flex; gap:16px; margin-top:6px;">
            <label style="display:flex; align-items:center; gap:6px; font-weight:700; cursor:pointer;">
              <input type="radio" name="bypass_cycle" value="monthly" checked style="accent-color:var(--coral);"> Monthly Billing (30 Days)
            </label>
            <label style="display:flex; align-items:center; gap:6px; font-weight:700; cursor:pointer;">
              <input type="radio" name="bypass_cycle" value="yearly" style="accent-color:var(--coral);"> Annual Billing (1 Year)
            </label>
          </div>
        </div>

        <div style="background:#fef2f2; border:2px solid #ef4444; border-radius:10px; padding:12px; font-size:0.78rem; color:#991b1b; margin-top:14px;">
          <i class="bi bi-exclamation-triangle-fill"></i> <strong>Super Admin Privilege:</strong> This action instantly upgrades the tenant organization's subscription without generating PayMongo charges.
        </div>

        <div style="display:flex; justify-content:flex-end; gap:8px; margin-top:20px;">
          <button type="button" onclick="closeModal('bypass-upgrade-modal')" class="button sand" style="padding:8px 16px; font-size:0.8rem;">Cancel</button>
          <button type="button" id="btn-confirm-bypass" onclick="confirmAdminBypassUpgrade()" class="button lime" style="padding:8px 18px; font-size:0.8rem;"><i class="bi bi-check-circle-fill"></i> Execute Admin Upgrade</button>
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
    </div>

    <footer id="footer-container"></footer>
  </main>

  <!-- ── Add / Edit Subscription Plan Modal ── -->
  <div id="plan-modal" style="display:none; position:fixed; inset:0; z-index:9999; background:rgba(10,20,15,0.65); backdrop-filter:blur(6px); overflow-y:auto; padding:40px 16px;">
    <div class="card-streetside" style="width:min(580px, 100%); margin:0 auto; padding:26px; background:var(--cream); position:relative;">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:18px; border-bottom:2px solid var(--ink); padding-bottom:10px;">
        <h3 id="plan-modal-title" style="margin:0; font-size:1.2rem; text-transform:uppercase; font-weight:900;">
          <i class="bi bi-award-fill" style="color:var(--coral); margin-right:6px;"></i>ADD NEW SUBSCRIPTION PLAN
        </h3>
        <button onclick="closePlanModal()" class="button sand" style="padding:2px 8px; font-size:0.8rem;">✕</button>
      </div>

      <form id="plan-form" novalidate>
        <input type="hidden" id="pm_plan_id">

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
          <div class="form-group-wrap">
            <label for="pm_name">PLAN NAME *</label>
            <input type="text" id="pm_name" placeholder="e.g. Pro Tier" class="form-input-ctrl" required>
            <span id="pm_name_err" class="field-err-msg"></span>
          </div>

          <div class="form-group-wrap">
            <label for="pm_slug">URL SLUG (OPTIONAL)</label>
            <input type="text" id="pm_slug" placeholder="e.g. pro-tier" class="form-input-ctrl">
            <span id="pm_slug_err" class="field-err-msg"></span>
          </div>
        </div>

        <!-- Free Trial Settings Toggle & Box -->
        <div style="background:#f0fdf4; border:2px solid var(--green); border-radius:12px; padding:14px 16px; margin-bottom:16px;">
          <div style="display:flex; align-items:center; justify-content:space-between;">
            <div>
              <label for="pm_is_free_trial" style="margin:0; font-family:'DM Mono', monospace; font-weight:900; font-size:0.85rem; text-transform:uppercase; cursor:pointer; color:var(--ink); display:flex; align-items:center; gap:8px;">
                <input type="checkbox" id="pm_is_free_trial" onchange="toggleFreeTrialFields()" style="width:18px; height:18px; cursor:pointer; accent-color:var(--green);">
                <i class="bi bi-gift-fill" style="color:var(--green);"></i> MARK AS FREE TRIAL PLAN
              </label>
              <div style="font-size:0.72rem; color:#4a5c56; margin-top:2px;">Offer this subscription tier for free for a specified number of months or promo date range.</div>
            </div>
            <span id="free-trial-status-badge" class="badge-streetside sand" style="font-size:0.7rem;">PAID PLAN</span>
          </div>

          <!-- Free Trial Duration & Date Range Options -->
          <div id="trial-options-panel" style="display:none; margin-top:14px; padding-top:12px; border-top:1px dashed var(--green);">
            <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:10px;">
              <div class="form-group-wrap" style="margin-bottom:0;">
                <label for="pm_trial_duration">TRIAL DURATION *</label>
                <select id="pm_trial_duration" class="form-input-ctrl">
                  <option value="1">1 Month Free</option>
                  <option value="2">2 Months Free</option>
                  <option value="3">3 Months Free</option>
                  <option value="6">6 Months Free</option>
                  <option value="12">12 Months Free (1 Year)</option>
                </select>
              </div>

              <div class="form-group-wrap" style="margin-bottom:0;">
                <label for="pm_trial_start">PROMO START DATE</label>
                <input type="date" id="pm_trial_start" class="form-input-ctrl">
              </div>

              <div class="form-group-wrap" style="margin-bottom:0;">
                <label for="pm_trial_end">PROMO END DATE</label>
                <input type="date" id="pm_trial_end" class="form-input-ctrl">
              </div>
            </div>
          </div>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:14px;">
          <div class="form-group-wrap" style="margin-bottom:0;">
            <label for="pm_price">MONTHLY PRICE (₱/MO) *</label>
            <input type="number" id="pm_price" placeholder="1499" class="form-input-ctrl" min="0" step="1" oninput="calcYearlyPriceFromMonthly()" required>
            <span id="pm_price_err" class="field-err-msg"></span>
          </div>

          <div class="form-group-wrap" style="margin-bottom:0;">
            <label for="pm_yearly_price">YEARLY PRICE (30% OFF AUTO-CALC)</label>
            <input type="number" id="pm_yearly_price" placeholder="12591.60" class="form-input-ctrl" style="background:#f8faf9;" readonly>
            <div id="pm_yearly_hint" style="font-size:0.7rem; font-weight:800; color:var(--green); margin-top:3px;"></div>
          </div>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:10px;">
          <div class="form-group-wrap">
            <label for="pm_facilities">MAX FACILITIES *</label>
            <input type="number" id="pm_facilities" placeholder="2" class="form-input-ctrl" min="1" required>
            <span id="pm_facilities_err" class="field-err-msg"></span>
          </div>

          <div class="form-group-wrap">
            <label for="pm_courts">MAX COURTS *</label>
            <input type="number" id="pm_courts" placeholder="8" class="form-input-ctrl" min="1" required>
            <span id="pm_courts_err" class="field-err-msg"></span>
          </div>

          <div class="form-group-wrap">
            <label for="pm_staff">MAX STAFF *</label>
            <input type="number" id="pm_staff" placeholder="5" class="form-input-ctrl" min="1" required>
            <span id="pm_staff_err" class="field-err-msg"></span>
          </div>
        </div>

        <div class="form-group-wrap">
          <label for="pm_desc">SHORT DESCRIPTION</label>
          <textarea id="pm_desc" rows="2" placeholder="Ideal for growing pickleball clubs with multiple courts..." class="form-input-ctrl"></textarea>
        </div>

        <!-- ── Additional Perks & Features Manager ── -->
        <div style="background:var(--white); border:2px solid var(--ink); border-radius:12px; padding:16px; margin-bottom:20px;">
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
            <label style="margin:0; font-family:'DM Mono', monospace; font-weight:900; font-size:0.8rem; text-transform:uppercase;">
              <i class="bi bi-star-fill" style="color:var(--coral);"></i> PLAN INCLUDED PERKS &amp; FEATURES
            </label>
            <button type="button" onclick="addPerkInputRow()" class="button lime" style="padding:4px 10px; font-size:0.75rem;">
              <i class="bi bi-plus-lg"></i> Add Perk
            </button>
          </div>

          <div id="perks-container">
            <!-- Dynamic perk input rows inserted here -->
          </div>
        </div>

        <div style="display:flex; justify-content:flex-end; gap:10px;">
          <button type="button" onclick="closePlanModal()" class="button sand" style="padding:9px 18px; font-size:0.82rem;">Cancel</button>
          <button type="submit" class="button lime" style="padding:9px 24px; font-size:0.82rem;">
            <i class="bi bi-floppy-fill"></i> Save Plan Tiers
          </button>
        </div>
      </form>
    </div>
  </div>

  <?php require_once __DIR__ . '/../../includes/scripts.php'; ?>
  <script>
    let currentPlansMap = {};
    let canManagePlans = false;
    let dataTable = null;

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

      const canViewSubscriptions = isSuperAdmin || perms.includes('subscriptions.view') || perms.includes('subscriptions.manage') || perms.includes('subscription.view') || perms.includes('subscription.manage') || perms.includes('plans.manage') || perms.includes('system.manage');

      if (!canViewSubscriptions) {
        window.location.href = '/pikvero/public/403.php?permission=subscriptions.view';
        return;
      }

      canManagePlans = isSuperAdmin || perms.includes('subscriptionplans.manage') || perms.includes('subscriptions.manage') || perms.includes('subscription.manage') || perms.includes('plans.manage') || perms.includes('system.manage');

      const btnAdd = document.getElementById('btn-add-plan');
      if (btnAdd) {
        btnAdd.style.display = canManagePlans ? 'inline-flex' : 'none';
      }

      // Granular section visibility toggles based on permissions
      const canViewTiers = isSuperAdmin || perms.includes('subscriptionplans.view') || perms.includes('subscriptions.view') || perms.includes('subscription.view') || perms.includes('system.manage');
      const sectionTiers = document.getElementById('section-available-tiers');
      if (sectionTiers) {
        sectionTiers.style.display = canViewTiers ? 'block' : 'none';
      }

      const canViewHistory = isSuperAdmin || perms.includes('subscriptionhistory.view') || perms.includes('system.manage');
      const sectionPayments = document.getElementById('section-payment-history');
      if (sectionPayments) {
        sectionPayments.style.display = canViewHistory ? 'block' : 'none';
      }

      SidebarComponent.render('subscriptions', (role === 'court_owner' || role === 'facility_manager' || role === 'receptionist') ? 'owner' : 'admin');
      FooterComponent.render('#footer-container', true);

      loadSubscriptionPlans();

      // Initialize Server-Side DataTables for Subscribed Tenant Organizations
      dataTable = $('#active-subs-table').DataTable({
        serverSide: true,
        processing: true,
        ajax: {
          url: '/pikvero/api/admin/subscriptions/active.php',
          type: 'GET',
          dataSrc: function(json) {
            const countBadge = document.getElementById('subs-count-badge');
            if (countBadge) {
              countBadge.textContent = `${json.recordsTotal || 0} Subscriptions`;
            }
            return json.data || [];
          }
        },
        columns: [
          {
            data: null,
            render: function(data, type, row) {
              return `
                <div>
                  <strong style="color:var(--ink); font-size:0.92rem; display:block;">${row.organization_name || 'Organization Tenant'}</strong>
                  <span class="badge-streetside green" style="font-size:0.68rem; margin-top:2px;">${row.plan_name || 'Starter Plan'}</span>
                </div>
              `;
            }
          },
          {
            data: null,
            render: function(data, type, row) {
              const isYearly = (row.billing_cycle === 'yearly');
              const mPrice = parseFloat(row.monthly_price || 0);
              const yPrice = (parseFloat(row.yearly_price || 0) > 0) ? parseFloat(row.yearly_price) : Math.round(mPrice * 12 * 0.70 * 100) / 100;
              const priceStr = isYearly ? `₱${yPrice.toLocaleString('en-US', {minimumFractionDigits:2})}/yr` : `₱${mPrice.toLocaleString('en-US', {minimumFractionDigits:2})}/mo`;

              return `
                <div>
                  <span style="font-family:'DM Mono', monospace; font-weight:800; color:var(--green); font-size:0.9rem;">${priceStr}</span>
                  <div style="margin-top:2px;">
                    <span class="badge-streetside ${isYearly ? 'coral' : 'sand'}" style="font-size:0.65rem;">${isYearly ? 'ANNUAL (SAVE 30%)' : 'MONTHLY'}</span>
                  </div>
                </div>
              `;
            }
          },
          {
            data: null,
            render: function(data, type, row) {
              const statusStr = (row.status || 'active').toUpperCase();
              const badgeClass = row.status === 'active' ? 'green' : 'coral';

              return `
                <div>
                  <span class="badge-streetside ${badgeClass}" style="font-size:0.68rem;">${statusStr}</span>
                  <div style="font-size:0.72rem; font-family:'DM Mono', monospace; color:#4a5c56; margin-top:2px;">
                    Exp: <strong>${row.current_period_end || '—'}</strong>
                  </div>
                </div>
              `;
            }
          },
          {
            data: null,
            orderable: false,
            className: 'text-right',
            render: function(data, type, row) {
              const pid = row.last_payment_id || row.id;
              const subId = row.id;
              const orgName = (row.organization_name || 'Org').replace(/'/g, "\\'");
              const planName = (row.plan_name || 'Plan').replace(/'/g, "\\'");
              return `
                <div style="display:flex; justify-content:flex-end; gap:4px; flex-wrap:wrap;">
                  <button onclick="openViewSubModalFromActive(${pid})" class="button lime action-btn" style="padding:4px 8px; font-size:0.72rem;" title="View Voucher"><i class="bi bi-eye"></i> View</button>
                  <button onclick="printSelectedSubReceipt(${pid})" class="button sand action-btn" style="padding:4px 8px; font-size:0.72rem;" title="Print Receipt"><i class="bi bi-printer"></i> Receipt</button>
                  ${canManagePlans ? `<button onclick="openBypassUpgradeModal(${subId}, '${orgName}', '${planName}')" class="button coral action-btn" style="padding:4px 8px; font-size:0.72rem;" title="Admin Upgrade without Payment"><i class="bi bi-lightning-fill"></i> Bypass</button>` : ''}
                </div>
              `;
            }
          }
        ]
      });

      // Form submit listener
      document.getElementById('plan-form').addEventListener('submit', handlePlanFormSubmit);
    });

    let currentBillingCycle = 'monthly';

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
      loadSubscriptionPlans();
    }

    function calcYearlyPriceFromMonthly() {
      const monthlyVal = parseFloat(document.getElementById('pm_price').value) || 0;
      const yearlyVal = Math.round(monthlyVal * 12 * 0.70 * 100) / 100;
      const equivMonthly = Math.round((yearlyVal / 12) * 100) / 100;
      
      document.getElementById('pm_yearly_price').value = yearlyVal > 0 ? yearlyVal.toFixed(2) : '0.00';
      const hintEl = document.getElementById('pm_yearly_hint');
      if (hintEl) {
        hintEl.innerHTML = monthlyVal > 0 
          ? `<i class="bi bi-tag-fill" style="color:var(--green);"></i> ₱${yearlyVal.toLocaleString('en-US', {minimumFractionDigits:2})}/yr (30% discount, ~₱${equivMonthly.toLocaleString('en-US', {minimumFractionDigits:2})}/mo)`
          : '';
      }
    }

    async function loadSubscriptionPlans() {
      try {
        const res = await Api.get('/pikvero/api/admin/subscriptions/plans.php');
        const container = document.getElementById('subscription-plans-grid');

        if (res.success && res.data.length > 0) {
          currentPlansMap = {};
          res.data.forEach(p => { currentPlansMap[p.id] = p; });

          container.innerHTML = res.data.map(p => {
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
              : `<div style="font-size:0.78rem; color:#888; font-style:italic;">No custom perks configured for this tier.</div>`;

            let promoDateHtml = '';
            if (isFree && (p.trial_start_date || p.trial_end_date)) {
              promoDateHtml = `
                <div style="margin-top:6px; font-size:0.72rem; font-family:'DM Mono', monospace; font-weight:800; color:var(--green); background:#f0fdf4; padding:3px 8px; border-radius:6px; border:1px solid #bbf7d0; display:inline-block;">
                  <i class="bi bi-calendar-event"></i> Promo: ${p.trial_start_date || 'Start'} to ${p.trial_end_date || 'Ongoing'}
                </div>
              `;
            }

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

            return `
              <div class="plan-card-streetside" style="${isFree ? 'border-color:var(--green); background:#fcfdfc;' : ''}">
                <div>
                  <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; flex-wrap:wrap; gap:4px;">
                    <div style="display:flex; gap:6px; align-items:center; flex-wrap:wrap;">
                      <span class="badge-streetside coral">${p.name.toUpperCase()}</span>
                      ${isFree ? `<span class="badge-streetside green"><i class="bi bi-gift-fill"></i> ${trialMonths} MOS FREE</span>` : ''}
                    </div>
                    <span class="badge-streetside lime">${p.active_subscribers || 0} SUBSCRIBERS</span>
                  </div>

                  <div style="margin-bottom:14px;">
                    ${priceDisplay}
                    ${promoDateHtml}
                    ${p.description ? `<p style="font-size:0.82rem; color:#4a5c56; margin:8px 0 0; line-height:1.4;">${p.description}</p>` : ''}
                  </div>

                  <!-- Limits Badges Bar -->
                  <div style="display:flex; gap:6px; flex-wrap:wrap; margin-bottom:16px; padding-bottom:14px; border-bottom:2px solid var(--line);">
                    <span class="badge-streetside sand" title="Max Facilities Allowed"><i class="bi bi-building-fill"></i> ${p.max_facilities} Facilities</span>
                    <span class="badge-streetside sky" title="Max Courts Allowed"><i class="bi bi-layers-fill"></i> ${p.max_courts} Courts</span>
                    <span class="badge-streetside lime" title="Max Staff Allowed"><i class="bi bi-person-badge-fill"></i> ${p.max_staff} Staff</span>
                  </div>

                  <!-- Perks List -->
                  <div style="margin-bottom:16px;">
                    <div style="font-size:0.72rem; font-family:'DM Mono', monospace; font-weight:900; text-transform:uppercase; color:#4a5c56; margin-bottom:8px;">INCLUDED PERKS &amp; FEATURES</div>
                    ${perksListHtml}
                  </div>
                </div>

                ${canManagePlans ? `
                  <div style="display:flex; gap:8px; padding-top:14px; border-top:2px solid var(--line);">
                    <button onclick="openEditPlanModal(${p.id})" class="button sand" style="flex:1; padding:8px 12px; font-size:0.78rem;">
                      <i class="bi bi-pencil-fill"></i> Edit Tier
                    </button>
                    <button onclick="deleteSubscriptionPlan(${p.id})" class="button coral" style="padding:8px 12px; font-size:0.78rem;" title="Delete Plan Tier">
                      <i class="bi bi-trash-fill"></i>
                    </button>
                  </div>
                ` : ''}
              </div>
            `;
          }).join('');
        } else {
          container.innerHTML = `
            <div class="card-streetside sand" style="grid-column:1/-1; max-width:500px; margin:30px auto; text-align:center; padding:30px 20px;">
              <i class="bi bi-award-fill" style="font-size:2.5rem; color:var(--coral); display:block; margin-bottom:10px;"></i>
              <h3 style="font-size:1.2rem; font-weight:800; text-transform:uppercase; margin:0 0 6px;">NO SUBSCRIPTION PLANS CREATED</h3>
              <p style="font-size:0.85rem; color:#4a5c56; margin-bottom:16px;">Click the button below to add your first SaaS subscription tier.</p>
              ${canManagePlans ? `
                <button onclick="openCreatePlanModal()" class="button lime" style="padding:8px 18px; font-size:0.82rem;">
                  <i class="bi bi-plus-lg"></i> Create First Plan
                </button>
              ` : ''}
            </div>
          `;
        }
      } catch(e) {
        console.error(e);
      }
    }

    function reloadSubscriptionsTable() {
      if (dataTable) {
        dataTable.ajax.reload(null, false);
      }
    }

    function toggleFreeTrialFields() {
      const isChecked = document.getElementById('pm_is_free_trial').checked;
      const panel = document.getElementById('trial-options-panel');
      const badge = document.getElementById('free-trial-status-badge');
      const priceInput = document.getElementById('pm_price');

      if (isChecked) {
        panel.style.display = 'block';
        badge.className = 'badge-streetside green';
        badge.textContent = 'FREE TRIAL ENABLED';
        priceInput.value = '0';
        priceInput.disabled = true;
      } else {
        panel.style.display = 'none';
        badge.className = 'badge-streetside sand';
        badge.textContent = 'PAID PLAN';
        priceInput.disabled = false;
      }
    }

    // Modal Control Functions
    function openCreatePlanModal() {
      if (!canManagePlans) {
        Toast.error('Access Denied', 'Permission required.');
        return;
      }
      document.getElementById('plan-modal-title').innerHTML = '<i class="bi bi-award-fill" style="color:var(--coral); margin-right:6px;"></i>ADD NEW SUBSCRIPTION PLAN';
      document.getElementById('plan-form').reset();
      document.getElementById('pm_plan_id').value = '';
      document.getElementById('perks-container').innerHTML = '';
      
      // Reset Free Trial toggle
      document.getElementById('pm_is_free_trial').checked = false;
      document.getElementById('pm_trial_duration').value = '1';
      document.getElementById('pm_trial_start').value = '';
      document.getElementById('pm_trial_end').value = '';
      toggleFreeTrialFields();

      // Add 2 default empty perk rows
      addPerkInputRow('Full Court Booking Management');
      addPerkInputRow('Real-time Player Reservations');
      document.getElementById('plan-modal').style.display = 'block';
    }

    function openEditPlanModal(planId) {
      if (!canManagePlans) {
        Toast.error('Access Denied', 'Permission required.');
        return;
      }
      const p = currentPlansMap[planId];
      if (!p) return;

      document.getElementById('plan-modal-title').innerHTML = '<i class="bi bi-pencil-fill" style="color:var(--green); margin-right:6px;"></i>EDIT SUBSCRIPTION PLAN TIER';
      document.getElementById('pm_plan_id').value = p.id;
      document.getElementById('pm_name').value = p.name || '';
      document.getElementById('pm_slug').value = p.slug || '';
      document.getElementById('pm_price').value = p.monthly_price || 0;
      document.getElementById('pm_facilities').value = p.max_facilities || 1;
      document.getElementById('pm_courts').value = p.max_courts || 5;
      document.getElementById('pm_staff').value = p.max_staff || 5;
      document.getElementById('pm_desc').value = p.description || '';

      const isFree = parseInt(p.is_free_trial) === 1;
      document.getElementById('pm_is_free_trial').checked = isFree;
      document.getElementById('pm_trial_duration').value = p.trial_duration_months || 1;
      document.getElementById('pm_trial_start').value = p.trial_start_date || '';
      document.getElementById('pm_trial_end').value = p.trial_end_date || '';
      toggleFreeTrialFields();
      calcYearlyPriceFromMonthly();

      const container = document.getElementById('perks-container');
      container.innerHTML = '';

      const features = p.features || [];
      if (features.length > 0) {
        features.forEach(f => addPerkInputRow(f.feature));
      } else {
        addPerkInputRow('');
      }

      document.getElementById('plan-modal').style.display = 'block';
    }

    function closePlanModal() {
      document.getElementById('plan-modal').style.display = 'none';
    }

    function addPerkInputRow(value = '') {
      const container = document.getElementById('perks-container');
      const row = document.createElement('div');
      row.className = 'perk-input-row';
      row.innerHTML = `
        <i class="bi bi-check-circle-fill" style="color:var(--green); font-size:1.1rem;"></i>
        <input type="text" name="perks[]" value="${value.replace(/"/g, '&quot;')}" placeholder="e.g. 24/7 Priority Support &amp; Analytics" class="form-input-ctrl" style="flex:1; padding:6px 10px; font-size:0.82rem;">
        <button type="button" onclick="this.parentNode.remove()" class="button sand" style="padding:4px 8px; font-size:0.75rem;" title="Remove Perk">✕</button>
      `;
      container.appendChild(row);
    }

    async function handlePlanFormSubmit(e) {
      e.preventDefault();
      if (!canManagePlans) {
        Toast.error('Access Denied', 'Permission required.');
        return;
      }

      const planId = document.getElementById('pm_plan_id').value;
      const name = document.getElementById('pm_name').value.trim();
      const slug = document.getElementById('pm_slug').value.trim();
      const is_free_trial = document.getElementById('pm_is_free_trial').checked ? 1 : 0;
      const trial_duration_months = document.getElementById('pm_trial_duration').value;
      const trial_start_date = document.getElementById('pm_trial_start').value;
      const trial_end_date = document.getElementById('pm_trial_end').value;
      const monthly_price = is_free_trial ? 0 : document.getElementById('pm_price').value;
      const max_facilities = document.getElementById('pm_facilities').value;
      const max_courts = document.getElementById('pm_courts').value;
      const max_staff = document.getElementById('pm_staff').value;
      const description = document.getElementById('pm_desc').value.trim();

      if (!name || (!is_free_trial && (monthly_price === '' || monthly_price < 0)) || !max_facilities || !max_courts || !max_staff) {
        Toast.error('Validation Error', 'Please complete all required plan tier fields.');
        return;
      }

      // Collect perks
      const perkInputs = document.querySelectorAll('input[name="perks[]"]');
      const features = [];
      perkInputs.forEach(inp => {
        const val = inp.value.trim();
        if (val) features.push(val);
      });

      const payload = {
        id: planId,
        name,
        slug,
        monthly_price,
        is_free_trial,
        trial_duration_months,
        trial_start_date,
        trial_end_date,
        max_facilities,
        max_courts,
        max_staff,
        description,
        features
      };

      const url = planId ? '/pikvero/api/admin/subscriptions/update.php' : '/pikvero/api/admin/subscriptions/plans.php';

      try {
        const res = await Api.post(url, payload);
        if (res && res.success) {
          Toast.success('Success', res.message || 'Subscription plan saved.');
          closePlanModal();
          loadSubscriptionPlans();
        } else {
          Toast.error('Failed', (res && res.message) ? res.message : 'Could not save plan.');
        }
      } catch(err) {
        console.error(err);
        Toast.error('Error', err.message || 'An unexpected error occurred.');
      }
    }

    async function deleteSubscriptionPlan(planId) {
      if (!canManagePlans) {
        Toast.error('Access Denied', 'Permission required.');
        return;
      }
      if (!confirm('Are you sure you want to delete this subscription plan tier?')) return;

      try {
        const res = await Api.post('/pikvero/api/admin/subscriptions/delete.php', { id: planId });
        if (res && res.success) {
          Toast.success('Deleted', 'Subscription plan removed.');
          loadSubscriptionPlans();
        } else {
          Toast.error('Failed', (res && res.message) ? res.message : 'Could not delete plan.');
        }
      } catch(err) {
        console.error(err);
        Toast.error('Error', err.message || 'An unexpected error occurred.');
      }
    }

    let activeSubPaymentId = null;

    async function openViewSubModalFromActive(paymentId) {
      activeSubPaymentId = paymentId;
      document.getElementById('view-sub-content').innerHTML = `
        <div style="text-align:center; padding:30px; font-family:'DM Mono', monospace; font-weight:700;">
          <i class="bi bi-arrow-repeat spin" style="font-size:1.5rem;"></i> Loading subscription voucher details...
        </div>
      `;
      const modal = document.getElementById('view-sub-modal');
      if (modal) {
        modal.classList.add('active');
        modal.style.display = 'grid';
      }

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
                <div style="font-weight:800; margin-top:2px;">${d.organization_name || 'Organization'}</div>
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

    function openBypassUpgradeModal(subId, orgName, currentPlanName) {
      document.getElementById('bypass-subscription-id').value = subId;
      document.getElementById('bypass-org-name').innerText = orgName;
      document.getElementById('bypass-current-plan').innerText = currentPlanName;

      // Populate plan select
      const select = document.getElementById('bypass-plan-id');
      select.innerHTML = allPlansList.map(p => `<option value="${p.id}">${p.name} (₱${parseFloat(p.monthly_price).toFixed(2)}/mo)</option>`).join('');

      const modal = document.getElementById('bypass-upgrade-modal');
      if (modal) {
        modal.classList.add('active');
        modal.style.display = 'grid';
      }
    }

    async function confirmAdminBypassUpgrade() {
      const subId = document.getElementById('bypass-subscription-id').value;
      const planId = document.getElementById('bypass-plan-id').value;
      const cycle = document.querySelector('input[name="bypass_cycle"]:checked').value;
      const btn = document.getElementById('btn-confirm-bypass');

      if (!subId || !planId) return;

      btn.disabled = true;
      btn.innerHTML = '<i class="bi bi-arrow-repeat spin"></i> Upgrading...';

      try {
        const res = await Api.post('/pikvero/api/admin/subscriptions/bypass-upgrade.php', {
          subscription_id: subId,
          plan_id: planId,
          billing_cycle: cycle
        });

        if (res && res.success) {
          Toast.success('Subscription Upgraded', res.message);
          closeModal('bypass-upgrade-modal');
          reloadSubscriptionsTable();
        } else {
          Toast.error('Bypass Failed', res.message || 'Could not upgrade subscription.');
        }
      } catch (err) {
        console.error(err);
        Toast.error('Error', err.message || 'An error occurred during bypass upgrade.');
      } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-circle-fill"></i> Execute Admin Upgrade';
      }
    }

    function closeModal(id) {
      const modal = document.getElementById(id);
      if (modal) {
        modal.classList.remove('active');
        modal.style.display = 'none';
      }
    }
  </script>
</body>
</html>
