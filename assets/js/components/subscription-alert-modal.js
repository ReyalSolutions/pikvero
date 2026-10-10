/**
 * Subscription Alert & PayMongo Checkout Component for Pikvero
 * Displays persistent subscription warnings, renewal prompts, and PayMongo checkout for Owners.
 */
const SubscriptionAlertModal = {
  currentStatusData: null,
  selectedPlanId: null,
  selectedCycle: 'monthly',
  selectedPaymentMethod: 'gcash',
  lockedElements: new Map(),

  init() {
    if (document.getElementById('subscription-alert-modal')) return;

    const modalHtml = `
      <div id="subscription-alert-modal" style="display:none; position:fixed; inset:0; z-index:9999999 !important; background:rgba(10,20,15,0.85); backdrop-filter:blur(8px); -webkit-backdrop-filter:blur(8px); overflow-y:auto; padding:20px 16px;">
        <div style="min-height:100%; display:flex; align-items:center; justify-content:center; padding:20px 0;">
          <div class="card-streetside" style="max-width:640px; width:100%; background:var(--white); padding:32px; position:relative; box-shadow:8px 8px 0 var(--ink); border:3px solid var(--ink); border-radius:18px;">
            
            <button type="button" id="sam-close" aria-label="Close subscription notice" onclick="SubscriptionAlertModal.close()" style="position:absolute; top:18px; right:18px; background:none; border:none; font-size:1.8rem; cursor:pointer; color:var(--ink); font-weight:900; line-height:1;">&times;</button>

            <!-- Header Badge & Title -->
            <div style="display:flex; align-items:center; gap:12px; margin-bottom:12px; flex-wrap:wrap;">
              <span id="sam-badge" class="badge-streetside coral" style="font-size:0.75rem;">SUBSCRIPTION ALERT</span>
              <span class="mono" style="font-size:0.72rem; color:var(--green); font-weight:800;">PIKVERO SAAS PLATFORM</span>
            </div>

            <h2 id="sam-title" style="font-size:1.5rem; font-weight:900; text-transform:uppercase; margin:0 0 10px; color:var(--ink); line-height:1.2;">
              ACTION REQUIRED: SUBSCRIPTION RENEWAL
            </h2>

            <p id="sam-reason" style="font-size:0.88rem; color:#3b4e48; margin:0 0 20px; line-height:1.5; font-weight:600; background:var(--cream); padding:14px; border-radius:12px; border:2px solid var(--ink);">
              Your subscription requires attention before continuing facility management.
            </p>

            <!-- Billing Cycle Switcher -->
            <div style="display:flex; justify-content:center; gap:8px; margin-bottom:20px; background:var(--sand); padding:6px; border-radius:12px; border:2px solid var(--ink);">
              <button type="button" id="sam-cycle-monthly" onclick="SubscriptionAlertModal.setCycle('monthly')" class="button coral" style="padding:6px 18px; font-size:0.78rem; font-weight:900;">
                MONTHLY BILLING
              </button>
              <button type="button" id="sam-cycle-yearly" onclick="SubscriptionAlertModal.setCycle('yearly')" class="button sand" style="padding:6px 18px; font-size:0.78rem; font-weight:900;">
                YEARLY BILLING <span class="badge-streetside green" style="font-size:0.6rem; margin-left:4px;">SAVE 17%</span>
              </button>
            </div>

            <!-- Available Plans Grid -->
            <div id="sam-plans-container" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(170px, 1fr)); gap:12px; margin-bottom:20px;">
              <!-- Loaded via JS -->
            </div>

            <!-- Itemized Invoice Breakdown -->
            <div style="background:#f8faf9; border:2px solid var(--ink); border-radius:12px; padding:16px; margin-bottom:20px;">
              <div style="font-size:0.75rem; font-family:'DM Mono', monospace; font-weight:900; color:var(--green); text-transform:uppercase; margin-bottom:10px;">ORDER INVOICE BREAKDOWN</div>
              
              <div style="display:flex; justify-content:space-between; font-size:0.85rem; margin-bottom:6px;">
                <span id="sam-inv-plan">Plan Subscription</span>
                <span id="sam-inv-subtotal" style="font-family:'DM Mono', monospace; font-weight:800;">₱0.00</span>
              </div>

              <div style="display:flex; justify-content:space-between; font-size:0.85rem; margin-bottom:6px; color:#5a7060;">
                <span>Tax &amp; Gateway Fees</span>
                <span style="font-family:'DM Mono', monospace; font-weight:700;">₱0.00 (INCLUDED)</span>
              </div>

              <div style="display:flex; justify-content:space-between; font-size:1.1rem; font-weight:900; margin-top:10px; padding-top:10px; border-top:2px dashed var(--ink);">
                <span>TOTAL DUE NOW</span>
                <span id="sam-inv-total" style="font-family:'DM Mono', monospace; color:var(--green); font-weight:900;">₱0.00</span>
              </div>
            </div>

            <!-- Actions -->
            <div style="display:flex; gap:10px; flex-wrap:wrap;">
              <button type="button" class="button sand" onclick="SubscriptionChecker.refresh()">Check plan again</button>
              <button type="button" id="sam-btn-pay" onclick="SubscriptionAlertModal.submitPayMongo()" class="button coral" style="flex:1; padding:12px 18px; font-size:0.9rem; font-weight:900;">
                <i class="bi bi-credit-card-fill"></i> PAY VIA PAYMONGO NOW
              </button>
              <a href="/pikvero/public/owner/my-plan.php" class="button sand" style="padding:12px 18px; font-size:0.85rem; font-weight:800;">
                <i class="bi bi-gear-fill"></i> View My Plan Page
              </a>
            </div>

          </div>
        </div>
      </div>
    `;

    document.body.insertAdjacentHTML('beforeend', modalHtml);
    const modal = document.getElementById('subscription-alert-modal');
    modal.setAttribute('role', 'dialog');
    modal.setAttribute('aria-modal', 'true');
    modal.setAttribute('aria-labelledby', 'sam-title');
    modal.addEventListener('keydown', event => {
      if (event.key !== 'Tab') return;
      const items = [...modal.querySelectorAll('button, a[href]')].filter(el => !el.disabled && el.getClientRects().length);
      const first = items[0], last = items[items.length - 1];
      if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
      else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
    });
  },

  show(data) {
    this.init();
    this.currentStatusData = data;
    const modal = document.getElementById('subscription-alert-modal');
    if (!modal) return;

    const badge = document.getElementById('sam-badge');
    const title = document.getElementById('sam-title');
    const reason = document.getElementById('sam-reason');

    if (data.is_expired) {
      if (badge) { badge.textContent = 'SUBSCRIPTION EXPIRED'; badge.className = 'badge-streetside coral'; }
      if (title) title.textContent = 'YOUR SUBSCRIPTION HAS EXPIRED';
    } else if (!data.has_subscription) {
      if (badge) { badge.textContent = 'NO ACTIVE PLAN'; badge.className = 'badge-streetside coral'; }
      if (title) title.textContent = 'SUBSCRIBE TO A PLAN TO CONTINUE';
    } else if (data.is_past_due) {
      if (badge) { badge.textContent = 'PAYMENT PAST DUE'; badge.className = 'badge-streetside coral'; }
      if (title) title.textContent = 'SUBSCRIPTION PAYMENT PAST DUE';
    } else {
      if (badge) { badge.textContent = 'SUBSCRIPTION REQUIRED'; badge.className = 'badge-streetside coral'; }
      if (title) title.textContent = 'SELECT A SUBSCRIPTION PLAN';
    }

    if (reason) {
      reason.textContent = data.action_reason || 'Please select a subscription plan to continue managing facilities, courts, and bookings.';
    }
    document.getElementById('sam-close').hidden = Boolean(data.persistent);
    document.getElementById('sam-btn-pay').disabled = Boolean(data.check_failed);

    this.renderPlans();
    modal.style.display = 'block';
    if (data.persistent) {
      [...document.body.children].forEach(el => {
        if (el === modal || el.id === 'toast-container' || el.tagName === 'SCRIPT') return;
        if (!this.lockedElements.has(el)) this.lockedElements.set(el, el.inert);
        el.inert = true;
      });
      if (!modal.contains(document.activeElement)) modal.querySelector('button:not([hidden])')?.focus();
    }
  },

  close() {
    if (this.currentStatusData?.requires_action && this.currentStatusData?.persistent) return;
    const modal = document.getElementById('subscription-alert-modal');
    if (modal) modal.style.display = 'none';
    this.lockedElements.forEach((inert, el) => { el.inert = inert; });
    this.lockedElements.clear();
  },

  setCycle(cycle) {
    this.selectedCycle = cycle;
    const btnM = document.getElementById('sam-cycle-monthly');
    const btnY = document.getElementById('sam-cycle-yearly');

    if (cycle === 'yearly') {
      if (btnM) btnM.className = 'button sand';
      if (btnY) btnY.className = 'button coral';
    } else {
      if (btnM) btnM.className = 'button coral';
      if (btnY) btnY.className = 'button sand';
    }

    this.renderPlans();
  },

  selectPlan(planId) {
    this.selectedPlanId = parseInt(planId);
    this.renderPlans();
  },

  renderPlans() {
    const container = document.getElementById('sam-plans-container');
    if (!container) return;

    const plans = (this.currentStatusData && this.currentStatusData.plans) ? this.currentStatusData.plans : [];
    if (plans.length === 0) {
      container.innerHTML = `<div style="grid-column:1/-1; text-align:center; color:#5a7060;">No plans available.</div>`;
      return;
    }

    if (!this.selectedPlanId) {
      this.selectedPlanId = parseInt(plans[0].id);
    }

    container.innerHTML = plans.map(p => {
      const isSelected = (parseInt(p.id) === parseInt(this.selectedPlanId));
      const trialEligible = p.trial_eligible === true;
      const price = trialEligible ? 0 : ((this.selectedCycle === 'yearly') ? (parseFloat(p.yearly_price) || Math.round(parseFloat(p.monthly_price) * 12 * .70 * 100) / 100) : parseFloat(p.monthly_price));
      
      return `
        <div onclick="SubscriptionAlertModal.selectPlan(${p.id})" style="cursor:pointer; border:3px solid ${isSelected ? 'var(--coral)' : 'var(--ink)'}; background:${isSelected ? '#fff5f3' : 'var(--white)'}; border-radius:12px; padding:14px; position:relative; box-shadow:${isSelected ? '4px 4px 0 var(--coral)' : '2px 2px 0 var(--ink)'}; transition:all 0.15s ease;">
          <div style="font-size:0.72rem; font-weight:900; font-family:'DM Mono', monospace; text-transform:uppercase; color:${isSelected ? 'var(--coral)' : 'var(--ink)'};">
            ${p.name}
          </div>
          <div style="font-size:1.2rem; font-weight:900; color:var(--green); font-family:'DM Mono', monospace; margin:6px 0;">
            ₱${price.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2})}
            <span style="font-size:0.68rem; color:#5a7060;">/${this.selectedCycle === 'yearly' ? 'yr' : 'mo'}</span>
          </div>
          <div style="font-size:0.72rem; color:#5a7060; font-family:'DM Mono', monospace;">
            <i class="bi bi-building"></i> ${p.max_facilities} Fac | <i class="bi bi-layers"></i> ${p.max_courts} Courts
          </div>
        </div>
      `;
    }).join('');

    const selPlan = plans.find(p => parseInt(p.id) === parseInt(this.selectedPlanId));
    if (selPlan) {
      const finalPrice = selPlan.trial_eligible === true ? 0 : ((this.selectedCycle === 'yearly') ? (parseFloat(selPlan.yearly_price) || Math.round(parseFloat(selPlan.monthly_price) * 12 * .70 * 100) / 100) : parseFloat(selPlan.monthly_price));
      const subtotalEl = document.getElementById('sam-inv-subtotal');
      const totalEl = document.getElementById('sam-inv-total');
      const planNameEl = document.getElementById('sam-inv-plan');

      if (planNameEl) planNameEl.textContent = `${selPlan.name} Plan (${this.selectedCycle.toUpperCase()})`;
      if (subtotalEl) subtotalEl.textContent = `₱${finalPrice.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2})}`;
      if (totalEl) totalEl.textContent = `₱${finalPrice.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2})}`;
    }
  },

  async submitPayMongo() {
    if (!this.selectedPlanId) {
      Toast.error('Selection Required', 'Please select a subscription plan tier.');
      return;
    }

    const btn = document.getElementById('sam-btn-pay');
    if (btn) {
      btn.disabled = true;
      btn.innerHTML = `<i class="bi bi-hourglass-split"></i> Redirecting to PayMongo Checkout...`;
    }

    try {
      const plans = (this.currentStatusData && this.currentStatusData.plans) ? this.currentStatusData.plans : [];
      const selPlan = plans.find(p => parseInt(p.id) === parseInt(this.selectedPlanId));

      const payload = {
        plan_id: this.selectedPlanId,
        plan_slug: selPlan ? selPlan.slug : 'starter',
        billing_cycle: this.selectedCycle,
        redirect_url: window.location.origin + '/pikvero/public/owner/my-plan.php'
      };

      const res = await Api.post('/pikvero/api/payments/paymongo-checkout.php', payload);

      if (res && res.success && res.data && res.data.checkout_url) {
        Toast.success('Checkout Ready', 'Redirecting to PayMongo secure portal...');
        setTimeout(() => {
          window.location.href = res.data.checkout_url;
        }, 600);
      } else {
        Toast.error('Checkout Failed', res.message || 'Could not initiate PayMongo checkout session.');
        if (btn) {
          btn.disabled = false;
          btn.innerHTML = `<i class="bi bi-credit-card-fill"></i> PAY VIA PAYMONGO NOW`;
        }
      }
    } catch(err) {
      console.error(err);
      Toast.error('Error', 'An error occurred connecting to PayMongo gateway.');
      if (btn) {
        btn.disabled = false;
        btn.innerHTML = `<i class="bi bi-credit-card-fill"></i> PAY VIA PAYMONGO NOW`;
      }
    }
  }
};
