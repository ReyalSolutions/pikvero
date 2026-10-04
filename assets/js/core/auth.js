/**
 * Auth Helper Utility for Pikvero
 */
const AuthHelper = {
  currentUser: null,
  systemSettings: {
    app_name: 'Pikvero',
    org_logo_url: '/pikvero/assets/images/logo.png',
    currency_symbol: '₱'
  },

  async checkSession() {
    try {
      const res = await Api.get('/pikvero/api/auth/me.php', {}, { showToast: false, ignoreUnauthorized: true });
      if (res && res.success && res.data) {
        this.currentUser = res.data.user ? res.data : null;
        if (res.data.settings) {
          this.systemSettings = res.data.settings;
          if (res.data.settings.org_logo_url) {
            this.setFavicon(res.data.settings.org_logo_url);
          }
        }
        return res.data.user ? res.data : null;
      }
    } catch (e) {
      // Unauthenticated guest user
    }
    this.currentUser = null;
    return null;
  },

  setFavicon(url) {
    if (!url) return;
    let links = document.querySelectorAll("link[rel*='icon']");
    if (links.length === 0) {
      const link = document.createElement('link');
      link.rel = 'icon';
      link.type = 'image/png';
      link.href = url;
      document.head.appendChild(link);
    } else {
      links.forEach(l => l.href = url);
    }
  },

  confirmLogout() {
    let modal = document.getElementById('global-logout-confirm-modal');
    if (!modal) {
      modal = document.createElement('div');
      modal.id = 'global-logout-confirm-modal';
      modal.className = 'modal-overlay';
      modal.style.zIndex = '90000';
      modal.innerHTML = `
        <div class="card-streetside" style="width:min(440px, 92vw); padding:24px; background:var(--white); margin:auto;">
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px; border-bottom:2px solid var(--ink); padding-bottom:10px;">
            <h3 style="font-size:1.15rem; font-weight:800; text-transform:uppercase; margin:0; color:var(--coral);"><i class="bi bi-box-arrow-right"></i> CONFIRM LOGOUT</h3>
            <button onclick="AuthHelper.closeLogoutModal()" class="button sand" style="padding:2px 8px; font-size:0.8rem;">✕</button>
          </div>

          <div style="background:#fff1f1; border:2px solid var(--ink); border-radius:10px; padding:14px; margin-bottom:18px;">
            <p style="margin:0; font-size:0.92rem; font-weight:800; color:var(--ink); line-height:1.4;">
              Are you sure you want to end your active session and log out of Pikvero?
            </p>
          </div>

          <div style="display:flex; justify-content:flex-end; gap:10px;">
            <button type="button" onclick="AuthHelper.closeLogoutModal()" class="button sand" style="padding:8px 16px; font-size:0.82rem;">Stay Logged In</button>
            <button type="button" id="btn-confirm-logout-act" onclick="AuthHelper.executeLogout()" class="button coral" style="padding:8px 18px; font-size:0.82rem;"><i class="bi bi-box-arrow-right"></i> Yes, Log Me Out</button>
          </div>
        </div>
      `;
      document.body.appendChild(modal);
    }

    modal.classList.add('active');
    modal.style.display = 'flex';
  },

  closeLogoutModal() {
    const modal = document.getElementById('global-logout-confirm-modal');
    if (modal) {
      modal.classList.remove('active');
      modal.style.display = 'none';
    }
  },

  async executeLogout() {
    const btn = document.getElementById('btn-confirm-logout-act');
    if (btn) {
      btn.disabled = true;
      btn.innerHTML = `<i class="bi bi-hourglass-split"></i> Logging out...`;
    }

    try {
      await Api.post('/pikvero/api/auth/logout.php');
      this.closeLogoutModal();
      Toast.success('Logged Out', 'You have been logged out successfully.');
      setTimeout(() => {
        window.location.href = '/pikvero/public/login.php';
      }, 500);
    } catch (e) {
      console.error(e);
      this.closeLogoutModal();
    }
  },

  logout() {
    this.confirmLogout();
  }
};
