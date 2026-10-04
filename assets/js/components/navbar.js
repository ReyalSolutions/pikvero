/**
 * Reusable Navbar Component for Pikvero with Live Clock & Profile Dropdown
 */
const NavbarComponent = {
  clockInterval: null,

  async render(selector = '#navbar-container', isPortal = false) {
    const el = document.querySelector(selector);
    if (!el) return;

    const userCtx = await AuthHelper.checkSession();
    let authNavHtml = '';

    if (userCtx && userCtx.user) {
      let dashLink = '/pikvero/public/customer/dashboard.php';
      if (userCtx.role === 'court_owner' || userCtx.role === 'super_admin' || userCtx.role === 'platform_admin') {
        dashLink = '/pikvero/public/admin/dashboard.php';
      }

      authNavHtml = `
        <div style="position:relative;">
          <button id="user-profile-btn" type="button" class="button sand" style="padding:4px 12px 4px 6px; font-size:0.82rem; gap:8px; cursor:pointer;">
            <div class="brand-mark" style="width:28px; height:28px; font-size:0.78rem; background:var(--coral); color:var(--white);">
              ${userCtx.user.last_name.charAt(0).toUpperCase()}
            </div>
            <span style="font-weight:800; font-size:0.85rem;">${userCtx.user.last_name}</span>
            <i class="bi bi-chevron-down" style="font-size:0.7rem;"></i>
          </button>

          <!-- Floating User Profile Dropdown Card -->
          <div id="user-profile-menu" class="card-streetside" style="display:none; position:absolute; right:0; top:calc(100% + 8px); width:230px; padding:14px; z-index:1100; box-shadow:4px 4px 0 var(--ink); background:var(--white);">
            <div style="margin-bottom:10px; padding-bottom:8px; border-bottom:1px solid var(--line);">
              <div style="font-weight:800; font-size:0.9rem;">${userCtx.user.first_name} ${userCtx.user.last_name}</div>
              <div style="font-size:0.73rem; color:#4a5c56; margin-top:2px;">${userCtx.user.email}</div>
              <div style="margin-top:6px;"><span class="badge-streetside lime" style="font-size:0.6rem;">${userCtx.role.toUpperCase()}</span></div>
            </div>

            <a href="${dashLink}" class="mono" style="display:flex; align-items:center; gap:8px; padding:6px 0; font-size:0.78rem; font-weight:700; color:var(--ink);">
              <i class="bi bi-grid-fill"></i> My Dashboard
            </a>

            <button onclick="AuthHelper.logout()" class="button coral" style="justify-content:flex-start; width:100%; margin-top:10px; padding:8px 12px; font-size:0.78rem; box-shadow:2px 2px 0 var(--ink);">
              <i class="bi bi-box-arrow-right"></i> Logout
            </button>
          </div>
        </div>
      `;
    } else {
      authNavHtml = `
        <div class="navbar-auth-desktop" style="align-items:center; gap:8px;">
          <a href="/pikvero/public/login.php" class="mono" style="font-weight:700; padding:0 6px;">Login</a>
          <a href="/pikvero/public/register.php" class="button" style="padding:8px 14px; font-size:0.82rem;">Play Local</a>
        </div>
      `;
    }

    const settings = (AuthHelper && AuthHelper.systemSettings) ? AuthHelper.systemSettings : {};
    const logoUrl = settings.org_logo_url || '/pikvero/assets/images/logo.png';
    const appName = settings.app_name || 'Pikvero';

    const brandMarkHtml = logoUrl ? `
      <div class="brand-mark navbar-logo-mark" style="width:38px; height:38px; border-radius:10px; overflow:hidden; padding:3px; display:flex; align-items:center; justify-content:center; background:var(--white); border:2px solid var(--ink); flex-shrink:0; box-sizing:border-box;">
        <img src="${logoUrl}" alt="${appName} Logo" style="width:100%; height:100%; max-width:100%; max-height:100%; object-fit:contain; display:block;" onerror="this.onerror=null; this.parentNode.innerHTML='P';">
      </div>
    ` : `<div class="brand-mark navbar-logo-mark" style="width:38px; height:38px; font-size:1rem; border-radius:10px; flex-shrink:0;">P</div>`;

    if (isPortal) {
      el.className = 'portal-nav';
      el.innerHTML = `
        <div style="display:flex; align-items:center; gap:12px;">
          <button onclick="document.querySelector('#sidebar-container')?.classList.toggle('is-open')" class="button dark" style="padding:6px 10px; font-size:0.8rem; display:none;" id="mobile-toggle-btn">
            <i class="bi bi-list"></i> Menu
          </button>

          <!-- Live Digital Clock Display -->
          <div id="portal-live-clock" class="mono" style="font-weight:700; font-size:0.82rem; color:var(--green); display:flex; align-items:center; gap:6px; background:var(--sand); padding:6px 12px; border-radius:8px; border:1px solid var(--ink);">
            <i class="bi bi-clock-fill" style="color:var(--coral);"></i>
            <span id="live-clock-time">--:--:-- --</span>
          </div>
        </div>

        ${authNavHtml}
      `;

      // Start live clock updates
      this.startLiveClock();

      if (window.innerWidth <= 992) {
        const toggleBtn = document.getElementById('mobile-toggle-btn');
        if (toggleBtn) toggleBtn.style.display = 'inline-flex';
      }
    } else {
      const path   = window.location.pathname;
      const search = window.location.search;

      const isOwner   = search.includes('type=owner') || path.includes('/owner-onboarding.php');
      const isPricing = path.includes('/pricing.php');
      const isSearch  = path.includes('/search.php') || path.includes('/facility.php');
      const isHome    = !isOwner && !isPricing && !isSearch && (
                        path.endsWith('/index.php') || 
                        path.endsWith('/public/') || 
                        path.endsWith('/public') || 
                        path.endsWith('/pikvero/') || 
                        path.endsWith('/pikvero')
                      );

      const serverAuth = window.SERVER_AUTH_STATE || {};
      const isLoggedIn = !!((userCtx && (userCtx.user || userCtx.role)) || serverAuth.isLoggedIn);
      let homeLink = serverAuth.homeLink || '/pikvero/public/index.php';

      if (userCtx && (userCtx.user || userCtx.role)) {
        const uRole = (userCtx.role || (userCtx.user ? userCtx.user.role_name : '') || 'customer').toLowerCase();
        if (uRole === 'customer') {
          homeLink = '/pikvero/public/customer/dashboard.php';
        } else if (uRole === 'court_owner') {
          homeLink = '/pikvero/public/owner/dashboard.php';
        } else {
          homeLink = '/pikvero/public/admin/dashboard.php';
        }
      }

      let navLinksHtml = `
        <a href="${homeLink}" class="${isHome ? 'active' : ''}">Home</a>
        <a href="/pikvero/public/search.php" class="${isSearch ? 'active' : ''}">Explore Courts</a>
        <a href="/pikvero/public/open-play.php" class="${path.includes('/open-play.php') ? 'active' : ''}">Open Play</a>
      `;

      if (!isLoggedIn) {
        navLinksHtml += `
          <a href="/pikvero/public/pricing.php" class="${isPricing ? 'active' : ''}">Pricing</a>
          <a href="/pikvero/public/register.php?type=owner" class="${isOwner ? 'active' : ''}">Become an Owner</a>
        `;
      }

      const hamburgerBtnHtml = `
        <button type="button" onclick="if(typeof toggleSidebar === 'function'){ toggleSidebar(); } else { const sc = document.getElementById('sidebar-container'); if(sc) sc.classList.toggle('is-open'); }" class="hamburger-toggle-btn button dark" style="padding:6px 11px; font-size:1.15rem; align-items:center; justify-content:center; border-radius:10px; cursor:pointer;" title="Open Menu" aria-label="Toggle navigation menu">
          <i class="bi bi-list" style="font-weight:900;"></i>
        </button>
      `;

      el.className = '';
      el.innerHTML = `
        <nav class="nav-streetside">
          <div style="display:flex; align-items:center; gap:8px;">
            <a href="${homeLink}" class="brand-streetside" style="gap:8px; display:flex; align-items:center;">
              ${brandMarkHtml}
              <span style="font-weight:800; font-size:1.1rem; text-transform:uppercase;">${appName}</span>
            </a>
          </div>
          <div class="nav-links" style="display:flex; gap:14px; align-items:center; font-weight:800; font-size:0.85rem;">
            ${navLinksHtml}
          </div>
          <div style="display:flex; align-items:center; gap:8px;">
            ${authNavHtml}
            ${hamburgerBtnHtml}
          </div>
        </nav>
      `;

      // Ensure global sidebar toggle functions exist
      if (!window.toggleSidebar) {
        window.toggleSidebar = function() {
          const sc = document.getElementById('sidebar-container');
          const bd = document.getElementById('sidebar-backdrop');
          if (!sc) return;
          const willOpen = !sc.classList.contains('is-open');
          sc.classList.toggle('is-open', willOpen);
          if (bd) bd.classList.toggle('is-active', willOpen);
          document.body.classList.toggle('sidebar-open', willOpen);
        };
      }
      if (!window.closeSidebar) {
        window.closeSidebar = function() {
          const sc = document.getElementById('sidebar-container');
          const bd = document.getElementById('sidebar-backdrop');
          if (sc) sc.classList.remove('is-open');
          if (bd) bd.classList.remove('is-active');
          document.body.classList.remove('sidebar-open');
        };
      }

      // Ensure sidebar-container and backdrop exist in DOM
      let sc = document.getElementById('sidebar-container');
      if (!sc) {
        sc = document.createElement('aside');
        sc.id = 'sidebar-container';
        sc.className = 'portal-sidebar drawer-only';
        document.body.insertAdjacentElement('afterbegin', sc);
      }
      let bd = document.getElementById('sidebar-backdrop');
      if (!bd) {
        bd = document.createElement('div');
        bd.id = 'sidebar-backdrop';
        bd.className = 'sidebar-backdrop';
        bd.onclick = window.closeSidebar;
        document.body.appendChild(bd);
      }

      // Update sidebar content if not already populated
      if (isLoggedIn && typeof SidebarComponent !== 'undefined') {
        const uRole = (userCtx.role || (userCtx.user ? userCtx.user.role_name : '') || 'customer').toLowerCase();
        let pType = 'customer';
        if (uRole === 'court_owner') pType = 'owner';
        else if (uRole !== 'customer') pType = 'admin';
        SidebarComponent.render(isHome ? 'home' : (isSearch ? 'explore' : (path.includes('open-play') ? 'open_play' : 'dashboard')), pType);
      } else if (!isLoggedIn) {
        if (!sc.innerHTML.trim() || !sc.querySelector('#sidebar-nav-links')) {
          NavbarComponent.renderGuestSidebar(sc, {
            homeLink, brandMarkHtml, appName, isHome, isSearch, isPricing, isOwner, path
          });
        }
      }
    }

    // Attach profile dropdown toggle listener
    const btn = document.getElementById('user-profile-btn');
    const menu = document.getElementById('user-profile-menu');
    if (btn && menu) {
      btn.addEventListener('click', (e) => {
        e.stopPropagation();
        const isOpen = menu.style.display === 'block';
        menu.style.display = isOpen ? 'none' : 'block';
      });

      document.addEventListener('click', (e) => {
        if (!btn.contains(e.target) && !menu.contains(e.target)) {
          menu.style.display = 'none';
        }
      });
    }
  },

  renderGuestSidebar(el, ctx = {}) {
    if (!el) return;
    const homeLink = ctx.homeLink || '/pikvero/public/index.php';
    const settings = (typeof AuthHelper !== 'undefined' && AuthHelper.systemSettings) ? AuthHelper.systemSettings : {};
    const logoUrl = ctx.logoUrl || settings.org_logo_url || (window.SERVER_AUTH_STATE ? window.SERVER_AUTH_STATE.logoUrl : '/pikvero/assets/images/logo.png');
    const brandMarkHtml = ctx.brandMarkHtml || (logoUrl ? `
      <div class="brand-mark sidebar-logo-mark" style="width:42px; height:42px; border-radius:12px; overflow:hidden; padding:3px; display:flex; align-items:center; justify-content:center; background:var(--white); border:2px solid var(--ink); box-shadow:2px 2px 0 var(--ink); flex-shrink:0; box-sizing:border-box;">
        <img src="${logoUrl}" alt="${appName} Logo" style="width:100%; height:100%; max-width:100%; max-height:100%; object-fit:contain; display:block;" onerror="this.onerror=null; this.parentNode.innerHTML='P';">
      </div>
    ` : `<div class="brand-mark sidebar-logo-mark" style="width:42px; height:42px; font-size:1.2rem; border-radius:12px; flex-shrink:0;">P</div>`);
    const isHome = ctx.isHome ?? false;
    const isSearch = ctx.isSearch ?? false;
    const isPricing = ctx.isPricing ?? false;
    const isOwner = ctx.isOwner ?? false;
    const isOpenPlay = (ctx.path || window.location.pathname).includes('open-play');

    el.className = 'portal-sidebar drawer-only';
    el.innerHTML = `
      <div style="display:flex; flex-direction:column; justify-content:space-between; height:100%;">
        <div>
          <!-- Brand Header with Close Button -->
          <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:20px; padding-bottom:12px; border-bottom:2px solid var(--ink);">
            <a href="${homeLink}" class="brand-streetside" style="gap:10px; display:flex; align-items:center;">
              ${brandMarkHtml}
              <span style="font-weight:800; font-size:1.15rem; text-transform:uppercase; letter-spacing:-0.03em;">${appName}</span>
            </a>
            <button type="button" onclick="closeSidebar()" class="button dark" style="padding:4px 10px; font-size:1.1rem; line-height:1;" id="sidebar-close-btn" aria-label="Close menu">&times;</button>
          </div>

          <div class="mono" style="margin-bottom:12px; color:var(--green); font-size:0.68rem; font-weight:700;">NAVIGATION MENU</div>

          <!-- Navigation Links -->
          <div id="sidebar-nav-links" style="display:flex; flex-direction:column; gap:6px;">
            <a href="${homeLink}" class="button ${isHome ? 'coral' : 'sand'}" style="justify-content:flex-start; width:100%; border-radius:12px; padding:10px 14px; font-size:0.85rem; box-shadow:2px 2px 0 var(--ink);">
              <i class="bi bi-house-door-fill"></i>
              <span>Home</span>
            </a>
            <a href="/pikvero/public/search.php" class="button ${isSearch ? 'coral' : 'sand'}" style="justify-content:flex-start; width:100%; border-radius:12px; padding:10px 14px; font-size:0.85rem; box-shadow:2px 2px 0 var(--ink);">
              <i class="bi bi-search"></i>
              <span>Explore Courts</span>
            </a>
            <a href="/pikvero/public/open-play.php" class="button ${isOpenPlay ? 'coral' : 'sand'}" style="justify-content:flex-start; width:100%; border-radius:12px; padding:10px 14px; font-size:0.85rem; box-shadow:2px 2px 0 var(--ink);">
              <i class="bi bi-dribbble"></i>
              <span>Open Play Socials</span>
            </a>
            <a href="/pikvero/public/pricing.php" class="button ${isPricing ? 'coral' : 'sand'}" style="justify-content:flex-start; width:100%; border-radius:12px; padding:10px 14px; font-size:0.85rem; box-shadow:2px 2px 0 var(--ink);">
              <i class="bi bi-tag-fill"></i>
              <span>Pricing &amp; Passes</span>
            </a>
            <a href="/pikvero/public/register.php?type=owner" class="button ${isOwner ? 'coral' : 'sand'}" style="justify-content:flex-start; width:100%; border-radius:12px; padding:10px 14px; font-size:0.85rem; box-shadow:2px 2px 0 var(--ink);">
              <i class="bi bi-building-fill-add"></i>
              <span>Become an Owner</span>
            </a>

            <!-- Extra Information Links -->
            <div style="margin-top:6px; border-top:1px dashed var(--line); padding-top:10px;">
              <div class="mono" style="margin-bottom:8px; color:#4a5c56; font-size:0.65rem; font-weight:700;">ABOUT &amp; SUPPORT</div>
              <a href="/pikvero/public/about.php" class="button sand" style="justify-content:flex-start; width:100%; border-radius:10px; margin-bottom:4px; padding:8px 12px; font-size:0.78rem; box-shadow:1.5px 1.5px 0 var(--ink);">
                <i class="bi bi-info-circle-fill"></i>
                <span>About Pikvero</span>
              </a>
              <a href="/pikvero/public/contact.php" class="button sand" style="justify-content:flex-start; width:100%; border-radius:10px; padding:8px 12px; font-size:0.78rem; box-shadow:1.5px 1.5px 0 var(--ink);">
                <i class="bi bi-envelope-fill"></i>
                <span>Contact Us</span>
              </a>
            </div>
          </div>
        </div>

        <!-- Auth Actions in Sidebar Footer -->
        <div id="sidebar-auth-section" style="border-top:2px solid var(--ink); padding-top:14px; margin-top:16px;">
          <a href="/pikvero/public/login.php" class="button sand" style="justify-content:center; width:100%; border-radius:12px; padding:10px 14px; font-size:0.85rem; margin-bottom:8px; box-shadow:2px 2px 0 var(--ink);">
            <i class="bi bi-box-arrow-in-right"></i>
            <span>Login to Account</span>
          </a>
          <a href="/pikvero/public/register.php" class="button lime" style="justify-content:center; width:100%; border-radius:12px; padding:10px 14px; font-size:0.85rem; box-shadow:2px 2px 0 var(--ink);">
            <i class="bi bi-person-plus-fill"></i>
            <span>Play Local (Sign Up)</span>
          </a>
        </div>
      </div>
    `;
  },

  startLiveClock() {
    if (this.clockInterval) clearInterval(this.clockInterval);

    const updateClock = () => {
      const timeEl = document.getElementById('live-clock-time');
      if (!timeEl) return;
      const now = new Date();
      timeEl.innerText = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true });
    };

    updateClock();
    this.clockInterval = setInterval(updateClock, 1000);
  }
};
