/**
 * Reusable Upgrade Plan Modal Component
 * Pikvero SaaS Application
 */
const UpgradePlanModal = (function() {
  let allPlans = [];
  let currentSubData = null;
  let billingCycle = 'monthly';
  let isInitialized = false;

  function injectModalCssAndHtml() {
    if (document.getElementById('reusable-upgrade-plan-modal')) return;

    // Inject CSS for zero scrollbars
    const style = document.createElement('style');
    style.id = 'reusable-upgrade-modal-css';
    style.textContent = `
      #reusable-upgrade-plan-modal::-webkit-scrollbar,
      .up-modal-no-scrollbar::-webkit-scrollbar {
        display: none !important;
        width: 0 !important;
        height: 0 !important;
      }
      #reusable-upgrade-plan-modal,
      .up-modal-no-scrollbar {
        -ms-overflow-style: none !important;
        scrollbar-width: none !important;
      }
    `;
    document.head.appendChild(style);

    // Inject Modal Overlay HTML
    const div = document.createElement('div');
    div.id = 'reusable-upgrade-plan-modal';
    div.style.cssText = 'display:none; position:fixed; inset:0; z-index:99999; background:rgba(10,20,15,0.78); backdrop-filter:blur(6px); overflow-y:auto; padding:40px 16px;';
    div.innerHTML = `
      <div class="card-streetside" style="width:min(920px, 100%); padding:28px; background:var(--cream); position:relative; margin:0 auto;">
        
        <!-- Header -->
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; border-bottom:2px solid var(--ink); padding-bottom:14px; flex-wrap:wrap; gap:12px;">
          <div>
            <h3 style="margin:0; font-size:1.35rem; font-weight:900; text-transform:uppercase; color:var(--ink);">
              <i class="bi bi-rocket-takeoff-fill" style="color:var(--coral); margin-right:8px;"></i>UPGRADE YOUR PLAN TIER
            </h3>
            <div style="font-size:0.82rem; color:#5a7060; margin-top:2px;">Select any plan tier below to upgrade facilities, courts, and staff slots</div>
          </div>

          <div style="display:flex; align-items:center; gap:12px;">
            <!-- Billing Cycle Switch -->
            <div style="display:flex; align-items:center; gap:10px; background:var(--white); border:2px solid var(--ink); border-radius:30px; padding:4px 14px; box-shadow:2px 2px 0 var(--ink);">
              <span id="reusable-cycle-monthly" style="font-weight:900; font-size:0.78rem; color:var(--green); font-family:'DM Mono', monospace; text-transform:uppercase;">MONTHLY</span>
              <label style="position:relative; display:inline-block; width:44px; height:22px; margin:0; cursor:pointer;">
                <input type="checkbox" id="reusable-cycle-toggle" onchange="UpgradePlanModal.toggleBillingCycle()" style="opacity:0; width:0; height:0;">
                <span style="position:absolute; inset:0; background:var(--ink); border-radius:30px; transition:0.3s; border:1px solid var(--ink);"></span>
                <span id="reusable-toggle-knob" style="position:absolute; height:16px; width:16px; left:3px; bottom:2px; background:var(--lime); border-radius:50%; transition:0.3s;"></span>
              </label>
              <span id="reusable-cycle-yearly" style="font-weight:800; font-size:0.78rem; color:#5a7060; font-family:'DM Mono', monospace; text-transform:uppercase;">
                YEARLY <span class="badge-streetside coral" style="font-size:0.65rem; padding:2px 6px;">SAVE 30%</span>
              </span>
            </div>

            <button onclick="UpgradePlanModal.close()" class="button sand" style="padding:4px 10px; font-size:0.88rem; font-weight:800;">✕</button>
          </div>
        </div>

        <!-- Custom Limit Notice Box -->
        <div id="reusable-upgrade-notice-box" style="display:none; background:#fff5f3; border:2px solid var(--coral); border-radius:12px; padding:14px 18px; margin-bottom:20px;">
          <div style="display:flex; gap:10px; align-items:center;">
            <i class="bi bi-exclamation-triangle-fill" style="font-size:1.4rem; color:var(--coral);"></i>
            <div id="reusable-upgrade-notice-msg" style="font-size:0.86rem; color:#2c3e38; font-weight:700; line-height:1.4;">
              You have reached your subscription plan limit.
            </div>
          </div>
        </div>

        <!-- Plan Cards Grid -->
        <div id="reusable-upgrade-plans-grid" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(260px, 1fr)); gap:20px;">
          <div style="grid-column:1/-1; text-align:center; padding:30px; color:#5a7060;">
            <i class="bi bi-hourglass-split" style="font-size:1.8rem; color:var(--coral); display:block; margin-bottom:8px;"></i>
            Loading available subscription plans...
          </div>
        </div>

      </div>
    `;
    document.body.appendChild(div);
  }

  async function loadData() {
    try {
      const [plansRes, subRes] = await Promise.all([
        Api.get('/pikvero/api/admin/subscriptions/plans.php'),
        Api.get('/pikvero/api/owner/subscription/index.php')
      ]);

      if (plansRes && plansRes.success) allPlans = plansRes.data || [];
      if (subRes && subRes.success) currentSubData = subRes.data || null;

      renderPlans();
    } catch(err) {
      console.error('Error loading upgrade modal data:', err);
    }
  }

  function renderPlans() {
    const container = document.getElementById('reusable-upgrade-plans-grid');
    if (!container) return;

    const activeSub = currentSubData ? currentSubData.subscription : null;
    const activePlanId = activeSub ? parseInt(activeSub.plan_id) : 0;

    if (!allPlans || allPlans.length === 0) {
      container.innerHTML = `<div style="grid-column:1/-1; text-align:center; color:#5a7060; padding:20px;">No platform subscription plans configured yet.</div>`;
      return;
    }

    container.innerHTML = allPlans.map(p => {
      const isCurrent = (parseInt(p.id) === activePlanId);
      const isFree = parseInt(p.is_free_trial) === 1 && !currentSubData?.has_used_free_trial;
      const trialMonths = p.trial_duration_months || 1;
      const features = p.features || [];

      const perksListHtml = features.length > 0
        ? features.map(f => `
            <div class="plan-perk-item" style="font-size:0.76rem; margin-bottom:4px; display:flex; gap:6px; align-items:center;">
              <i class="bi bi-check-circle-fill" style="color:var(--green);"></i>
              <span>${f.feature}</span>
            </div>
          `).join('')
        : `<div style="font-size:0.75rem; color:#888; font-style:italic;">No custom perks configured.</div>`;

      let priceDisplay = '';
      if (isFree) {
        priceDisplay = `<div style="font-size:1.6rem; font-weight:900; color:var(--green); font-family:'DM Mono', monospace; line-height:1.1;">FREE TRIAL <span style="font-size:0.78rem; color:#4a5c56;">(${trialMonths} Mo)</span></div>`;
      } else if (billingCycle === 'yearly') {
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

      const activeCycle = activeSub ? (activeSub.current_billing_cycle || 'monthly') : 'monthly';
      const isSameCycle = (activeCycle === billingCycle);
      const isYearlyActive = (activeCycle === 'yearly');

      if (!isFree && billingCycle === 'yearly' && activeCycle === 'monthly') {
        const mVal = parseFloat(p.monthly_price) || 0;
        if (mVal > 0) {
          priceDisplay += `
            <div style="font-size:0.72rem; color:var(--green); font-family:'DM Mono', monospace; margin-top:4px; font-weight:800;">
              <i class="bi bi-tag-fill"></i> ₱${mVal.toFixed(2)} Monthly Credit Deducted!
            </div>
          `;
        }
      }

      let actionButtonHtml = '';
      if (isYearlyActive && billingCycle === 'monthly') {
        actionButtonHtml = `
          <button class="button sand" style="width:100%; padding:9px 10px; font-size:0.75rem; cursor:not-allowed; opacity:0.65; white-space:normal; line-height:1.2; text-align:center;" disabled title="Downgrading from an active Annual Plan to Monthly billing is not available until your current term ends.">
            <i class="bi bi-lock-fill"></i> NOT AVAILABLE (YEARLY TERM ACTIVE)
          </button>
        `;
      } else if (isCurrent && isSameCycle) {
        actionButtonHtml = `
          <button class="button green" style="width:100%; padding:9px 10px; font-size:0.78rem; cursor:default; white-space:normal; line-height:1.2;" disabled>
            <i class="bi bi-check-circle-fill"></i> CURRENT BILLING TIER
          </button>
        `;
      } else if (isCurrent && !isSameCycle) {
        actionButtonHtml = `
          <button onclick="UpgradePlanModal.selectPlan(${p.id})" class="button lime" style="width:100%; padding:9px 10px; font-size:0.78rem; white-space:normal; line-height:1.2; text-align:center;">
            <i class="bi bi-arrow-repeat"></i> Switch to ${billingCycle === 'yearly' ? 'Yearly (Save 30%)' : 'Monthly Billing'}
          </button>
        `;
      } else {
        actionButtonHtml = `
          <button onclick="UpgradePlanModal.selectPlan(${p.id})" class="button coral" style="width:100%; padding:9px 10px; font-size:0.78rem; white-space:normal; line-height:1.2; text-align:center;">
            <i class="bi bi-rocket-takeoff-fill"></i> ${isFree ? 'Claim Free Trial' : 'Subscribe / Upgrade Plan'}
          </button>
        `;
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

  return {
    open: function(customNotice = '') {
      injectModalCssAndHtml();
      
      document.body.style.overflow = 'hidden';

      const noticeBox = document.getElementById('reusable-upgrade-notice-box');
      const noticeMsg = document.getElementById('reusable-upgrade-notice-msg');
      if (noticeBox && noticeMsg) {
        if (customNotice) {
          noticeMsg.innerHTML = customNotice;
          noticeBox.style.display = 'block';
        } else {
          noticeBox.style.display = 'none';
        }
      }

      const modal = document.getElementById('reusable-upgrade-plan-modal');
      if (modal) {
        modal.style.display = 'flex';
        modal.style.justifyContent = 'center';
        modal.style.alignItems = 'flex-start';
      }

      loadData();
    },

    close: function() {
      document.body.style.overflow = '';
      const modal = document.getElementById('reusable-upgrade-plan-modal');
      if (modal) modal.style.display = 'none';
    },

    toggleBillingCycle: function() {
      const toggle = document.getElementById('reusable-cycle-toggle');
      const knob = document.getElementById('reusable-toggle-knob');
      const monthly = document.getElementById('reusable-cycle-monthly');
      const yearly = document.getElementById('reusable-cycle-yearly');

      if (toggle.checked) {
        billingCycle = 'yearly';
        if (knob) knob.style.left = '25px';
        if (monthly) { monthly.style.color = '#5a7060'; monthly.style.fontWeight = '700'; }
        if (yearly) { yearly.style.color = 'var(--green)'; yearly.style.fontWeight = '900'; }
      } else {
        billingCycle = 'monthly';
        if (knob) knob.style.left = '3px';
        if (monthly) { monthly.style.color = 'var(--green)'; monthly.style.fontWeight = '900'; }
        if (yearly) { yearly.style.color = '#5a7060'; yearly.style.fontWeight = '700'; }
      }
      renderPlans();
    },

    selectPlan: function(planId) {
      this.close();
      if (typeof openSubscribeModal === 'function') {
        openSubscribeModal(planId, billingCycle);
      } else {
        window.location.href = `/pikvero/public/owner/my-plan.php?plan_id=${planId}&cycle=${billingCycle}`;
      }
    }
  };
})();
