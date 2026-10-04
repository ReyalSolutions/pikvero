/**
 * Global Subscription Checker Component for Pikvero Owners
 * Automatically evaluates owner subscription status, renders top alert banners, and pops payment modals.
 */
const SubscriptionChecker = {
  lastData: null,

  async init() {
    // Only run for authenticated owner users
    const user = (typeof AuthHelper !== 'undefined' && AuthHelper.currentUser) ? AuthHelper.currentUser : null;
    if (!user) return;

    const role = (user.role || (user.user ? user.user.role_name : '')).toLowerCase();
    const isOwner = (role === 'owner' || role === 'organization_owner' || role === 'tenant_admin' || role === 'facility_manager');
    
    // Also check if current page path is under owner portal
    const path = window.location.pathname.toLowerCase();
    const isOwnerPage = path.includes('/owner/') || path.includes('/admin/facilities') || path.includes('/admin/courts') || path.includes('/admin/bookings');

    if (!isOwner && !isOwnerPage) return;

    try {
      const res = await Api.get('/pikvero/api/owner/subscription/status.php');
      if (res && res.success && res.data) {
        this.lastData = res.data;
        this.evaluate(res.data);
      }
    } catch(err) {
      console.warn('Subscription check error:', err);
    }
  },

  evaluate(data) {
    if (!data) return;

    const currentPath = window.location.pathname.toLowerCase();
    const isMyPlanPage = currentPath.includes('my-plan.php');

    // Render persistent banner if subscription requires action
    if (data.requires_action) {
      this.renderBanner(data);

      // If user is on an owner management page (not my-plan page), pop alert modal automatically
      if (!isMyPlanPage) {
        setTimeout(() => {
          if (typeof SubscriptionAlertModal !== 'undefined') {
            SubscriptionAlertModal.show(data);
          }
        }, 800);
      }
    }
  },

  renderBanner(data) {
    if (document.getElementById('global-sub-banner')) return;

    const bannerHtml = `
      <div id="global-sub-banner" style="background:#fef3c7; border-bottom:2px solid var(--ink); padding:10px 24px; font-size:0.84rem; font-weight:800; color:#92400e; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; position:relative; z-index:9999;">
        <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
          <span class="badge-streetside coral" style="font-size:0.68rem;">SUBSCRIPTION EXPIRED / REQUIRED</span>
          <span>${data.action_reason || 'Action required: Please renew your subscription to maintain full facility features.'}</span>
        </div>
        <button onclick="SubscriptionAlertModal.show(SubscriptionChecker.lastData)" class="button coral" style="padding:6px 14px; font-size:0.78rem; font-weight:900;">
          <i class="bi bi-credit-card-fill"></i> SUBSCRIBE / RENEW NOW
        </button>
      </div>
    `;

    const mainEl = document.querySelector('main.portal-main') || document.body;
    mainEl.insertAdjacentHTML('afterbegin', bannerHtml);
  }
};

document.addEventListener('DOMContentLoaded', () => {
  setTimeout(() => {
    SubscriptionChecker.init();
  }, 400);
});
