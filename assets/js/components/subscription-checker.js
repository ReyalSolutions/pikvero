/** Shared owner plan gate. Runs on every admin page after identity is resolved. */
const SubscriptionChecker = {
  lastData: null,
  checking: false,
  owner: false,
  async init() {
    const path = location.pathname.toLowerCase();
    if (!path.includes('/admin/') && !path.includes('/owner/')) return;
    try {
      const response = await fetch('/pikvero/api/auth/me.php', {credentials:'same-origin', headers:{Accept:'application/json'}});
      if (!response.ok) return;
      const result = await response.json();
      const role = (result.data?.role || result.data?.user?.role_name || '').toLowerCase();
      this.owner = ['court_owner', 'owner', 'organization_owner', 'tenant_admin'].includes(role);
      if (!this.owner) return;
      await this.refresh();
      window.addEventListener('focus', () => this.refresh());
      document.addEventListener('visibilitychange', () => { if (!document.hidden) this.refresh(); });
      setInterval(() => { if (!document.hidden) this.refresh(); }, 60000);
    } catch (error) { console.warn('Unable to resolve subscription identity', error); }
  },
  async refresh() {
    if (!this.owner || this.checking) return;
    this.checking = true;
    try {
      const response = await fetch('/pikvero/api/owner/subscription/status.php', {credentials:'same-origin', cache:'no-store', headers:{Accept:'application/json'}});
      const result = await response.json();
      if (!response.ok || !result.success || !result.data) throw new Error('Subscription status unavailable');
      this.evaluate(result.data);
    } catch (error) {
      this.evaluate({requires_action:true, check_failed:true, has_subscription:true, plans:[], action_reason:'We could not verify your plan. Retry the check before continuing.'});
    } finally { this.checking = false; }
  },
  evaluate(data) {
    this.lastData = data;
    const recoveryPage = location.pathname.replace(/\.php$/, '').endsWith('/owner/my-plan');
    if (data.requires_action) {
      SubscriptionAlertModal.show({...data, persistent:!recoveryPage});
    } else {
      SubscriptionAlertModal.currentStatusData = data;
      SubscriptionAlertModal.close();
    }
  }
};
document.addEventListener('DOMContentLoaded', () => SubscriptionChecker.init());
