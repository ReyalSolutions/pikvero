/**
 * Dashboard Sidebar Component for Pikvero (Full 100vh Fixed Layout)
 * Dynamically filters sidebar navigation links strictly based on module RBAC permissions,
 * grouped into interactive Streetside dropdown category menus.
 */
const SidebarComponent = {
  /**
   * Toggle expandable submenu dropdowns
   * @param {string} groupKey Menu group identifier
   */
  toggleSubmenu(groupKey) {
    const submenu = document.getElementById(`submenu-${groupKey}`);
    const arrow = document.getElementById(`arrow-${groupKey}`);
    if (submenu) {
      const isOpen = submenu.style.display !== 'none';
      submenu.style.display = isOpen ? 'none' : 'flex';
      if (arrow) {
        arrow.style.transform = isOpen ? 'rotate(0deg)' : 'rotate(180deg)';
      }
    }
  },

  render(activeKey = 'dashboard', portalType = 'admin', selector = '#sidebar-container') {
    if (portalType === 'customer') {
      document.body.classList.add('customer-portal');
      if (typeof BottomNavComponent !== 'undefined') {
        BottomNavComponent.render(activeKey);
      }
    }

    const el = document.querySelector(selector);
    if (!el) return;

    const path = window.location.pathname;
    const isPortalPath = path.includes('/admin/') || path.includes('/owner/') || path.includes('/customer/');
    const isPublicMarketplacePage = !isPortalPath && (
      path.includes('/search.php') ||
      path.includes('/open-play.php') ||
      path.includes('/facility.php') ||
      path.includes('/index.php') ||
      activeKey === 'explore' ||
      activeKey === 'open_play'
    );

    if (isPublicMarketplacePage) {
      el.className = 'portal-sidebar drawer-only';
    } else {
      el.className = 'portal-sidebar portal-sidebar-permanent';
    }

    const user = (typeof AuthHelper !== 'undefined' && AuthHelper.currentUser) ? AuthHelper.currentUser : null;
    const role = user ? (user.role || (user.user ? user.user.role_name : '')) : '';
    const perms = (user && user.permissions) ? user.permissions : [];
    const isSuperAdmin = (role === 'super_admin');

    const hasAnyPerm = (...requiredPerms) => {
      if (isSuperAdmin) return true;
      return requiredPerms.some(p => perms.includes(p));
    };

    let menuStructure = [];

    if (portalType === 'guest') {
      menuStructure = [
        {
          type: 'single',
          key: 'home',
          label: 'Home',
          link: '/pikvero/public/index.php',
          icon: 'bi-house-door-fill'
        },
        {
          type: 'single',
          key: 'explore',
          label: 'Explore Courts',
          link: '/pikvero/public/search.php',
          icon: 'bi-search'
        },
        {
          type: 'single',
          key: 'open_play',
          label: 'Open Play Socials',
          link: '/pikvero/public/open-play.php',
          icon: 'bi-dribbble'
        },
        {
          type: 'single',
          key: 'pricing',
          label: 'Pricing & Passes',
          link: '/pikvero/public/pricing.php',
          icon: 'bi-tag-fill'
        },
        {
          type: 'single',
          key: 'owner',
          label: 'Become an Owner',
          link: '/pikvero/public/register.php?type=owner',
          icon: 'bi-building-fill-add'
        },
        {
          type: 'dropdown',
          groupKey: 'about_support',
          label: 'About & Support',
          icon: 'bi-info-circle-fill',
          items: [
            { key: 'about', label: 'About Pikvero', link: '/pikvero/public/about.php', icon: 'bi-info-circle' },
            { key: 'contact', label: 'Contact Support', link: '/pikvero/public/contact.php', icon: 'bi-envelope-fill' }
          ]
        }
      ];
    } else if (portalType === 'customer') {
      menuStructure = [
        {
          type: 'single',
          key: 'dashboard',
          label: 'My Dashboard',
          link: '/pikvero/public/customer/dashboard.php',
          icon: 'bi-grid-1x2-fill'
        },
        {
          type: 'dropdown',
          groupKey: 'court_bookings',
          label: 'Court Bookings',
          icon: 'bi-ticket-detailed-fill',
          items: [
            { key: 'bookings', label: 'My Bookings', link: '/pikvero/public/customer/bookings.php', icon: 'bi-journal-check' },
            { key: 'open_play', label: 'Open Play Socials', link: '/pikvero/public/open-play.php', icon: 'bi-dribbble' },
            { key: 'explore', label: 'Book a Court', link: '/pikvero/public/search.php', icon: 'bi-search' }
          ]
        },
        {
          type: 'single',
          key: 'profile',
          label: 'My Profile',
          link: '/pikvero/public/customer/profile.php',
          icon: 'bi-person-badge-fill'
        },
        {
          type: 'single',
          key: 'feedback',
          label: 'Feedback & Bug Reports',
          link: '/pikvero/public/customer/feedback.php',
          icon: 'bi-chat-right-dots-fill'
        }
      ];
    } else {
      // 1. Single Overview Link
      if (hasAnyPerm('organization.view', 'facility.view', 'court.view', 'booking.view', 'bookings.view', 'reports.view', 'report.view', 'system.manage', 'users.view')) {
        menuStructure.push({
          type: 'single',
          key: 'dashboard',
          label: 'Overview',
          link: portalType === 'owner' ? '/pikvero/public/owner/dashboard.php' : '/pikvero/public/admin/dashboard.php',
          icon: 'bi-grid-1x2-fill'
        });
      }

      // 2. Venue Management Dropdown Group (Facilities, Courts)
      const venueItems = [];
      if (hasAnyPerm('facility.view', 'facilities.view', 'facilities.manage', 'system.manage')) {
        venueItems.push({
          key: 'facilities',
          label: portalType === 'owner' ? 'My Facilities' : 'Facilities',
          link: portalType === 'owner' ? '/pikvero/public/owner/facilities.php' : '/pikvero/public/admin/facilities.php',
          icon: 'bi-building-fill'
        });
      }
      if (hasAnyPerm('court.view', 'courts.view', 'courts.manage', 'system.manage')) {
        venueItems.push({
          key: 'courts',
          label: 'Court Manager',
          link: portalType === 'owner' ? '/pikvero/public/owner/courts.php' : '/pikvero/public/admin/courts.php',
          icon: 'bi-layers-fill'
        });
      }
      if (hasAnyPerm('amenities.view', 'amenities.manage', 'system.manage')) {
        venueItems.push({
          key: 'amenities',
          label: 'Amenities',
          link: '/pikvero/public/admin/amenities.php',
          icon: 'bi-stars'
        });
      }
      if (hasAnyPerm('open_play.view', 'open_play.create', 'system.manage')) {
        venueItems.push({
          key: 'open-play',
          label: 'Open Play',
          link: '/pikvero/public/admin/open-play.php',
          icon: 'bi-dribbble'
        });
      }
      if (venueItems.length > 0) {
        menuStructure.push({
          type: 'dropdown',
          groupKey: 'venue',
          label: 'Venue Management',
          icon: 'bi-building-gear',
          items: venueItems
        });
      }

      // 3. Operations & Analytics Dropdown Group (Bookings, Reports)
      const opsItems = [];
      if (hasAnyPerm('calendar.view', 'booking.view', 'system.manage')) {
        opsItems.push({
          key: 'calendar',
          label: 'Schedule Calendar',
          link: '/pikvero/public/admin/calendar.php',
          icon: 'bi-calendar3'
        });
      }
      if (hasAnyPerm('booking.view', 'bookings.view', 'bookings.manage', 'system.manage')) {
        opsItems.push({
          key: 'bookings',
          label: 'Reservations',
          link: portalType === 'owner' ? '/pikvero/public/owner/bookings.php' : '/pikvero/public/admin/bookings.php',
          icon: 'bi-calendar-check-fill'
        });
      }
      if (hasAnyPerm('products.view', 'products.sell', 'system.manage')) {
        opsItems.push({
          key: 'products',
          label: 'Products & Gear',
          link: '/pikvero/public/admin/products.php',
          icon: 'bi-shop'
        });
      }
      if (hasAnyPerm('payments.view', 'payment.view', 'payments.manage', 'system.manage')) {
        opsItems.push({
          key: 'payments',
          label: 'Payments',
          link: portalType === 'owner' ? '/pikvero/public/owner/payments.php' : '/pikvero/public/admin/payments.php',
          icon: 'bi-credit-card-2-front-fill'
        });
      }
      if (hasAnyPerm('payouts.view', 'payouts.request', 'payouts.manage', 'system.manage')) {
        opsItems.push({
          key: 'payouts',
          label: 'GCash Payouts',
          link: portalType === 'owner' ? '/pikvero/public/owner/payouts.php' : '/pikvero/public/admin/payouts.php',
          icon: 'bi-wallet-fill'
        });
      }
      if (hasAnyPerm('reports.view', 'report.view', 'reports.financial', 'system.manage')) {
        opsItems.push({
          key: 'reports',
          label: portalType === 'owner' ? 'Analytics' : 'Revenue Analytics',
          link: portalType === 'owner' ? '/pikvero/public/owner/reports.php' : '/pikvero/public/admin/reports.php',
          icon: 'bi-graph-up-arrow'
        });
      }
      if (portalType === 'owner') {
        opsItems.push({
          key: 'feedback',
          label: 'Feedback & Bug Reports',
          link: '/pikvero/public/owner/feedback.php',
          icon: 'bi-chat-right-dots-fill'
        });
      }
      if (opsItems.length > 0) {
        menuStructure.push({
          type: 'dropdown',
          groupKey: 'operations',
          label: 'Operations',
          icon: 'bi-card-checklist',
          items: opsItems
        });
      }

      // 4. Platform Administration Dropdown Group (Subscriptions, Users, Logs, Settings)
      const adminItems = [];
      if (hasAnyPerm('subscriptions.view', 'subscriptions.manage', 'subscription.view', 'subscription.manage', 'plans.manage', 'system.manage')) {
        adminItems.push({
          key: 'subscriptions',
          label: portalType === 'owner' ? 'My Plan' : 'Subscription Plans',
          link: portalType === 'owner' ? '/pikvero/public/owner/my-plan.php' : '/pikvero/public/admin/subscriptions.php',
          icon: 'bi-award-fill'
        });
      }
      if (hasAnyPerm('subscription_payments.view', 'subscription_payments.manage', 'system.manage')) {
        adminItems.push({
          key: 'subscription-payments',
          label: portalType === 'owner' ? 'Plan Payments' : 'Sub Payments',
          link: portalType === 'owner' ? '/pikvero/public/owner/subscription-payments.php' : '/pikvero/public/admin/subscription-payments.php',
          icon: 'bi-receipt-cutoff'
        });
      }
      if (hasAnyPerm('users.view', 'users.manage', 'roles.manage', 'permissions.manage', 'system.manage')) {
        adminItems.push({
          key: 'users',
          label: 'User Directory',
          link: '/pikvero/public/admin/users.php',
          icon: 'bi-people-fill'
        });
      }
      if (hasAnyPerm('audit_logs.view', 'audit_logs.manage', 'system.manage')) {
        adminItems.push({
          key: 'logs',
          label: 'Audit Logs',
          link: '/pikvero/public/admin/logs.php',
          icon: 'bi-shield-check'
        });
      }
      if (hasAnyPerm('settings.manage', 'organization.update', 'system.manage')) {
        adminItems.push({
          key: 'settings',
          label: portalType === 'owner' ? 'Org Branding' : 'Security & Settings',
          link: portalType === 'owner' ? '/pikvero/public/owner/settings.php' : '/pikvero/public/admin/settings.php',
          icon: portalType === 'owner' ? 'bi-palette-fill' : 'bi-gear-fill'
        });
      }
      if (portalType !== 'owner') {
        adminItems.push({
          key: 'feedback_reports',
          label: 'Bug & Feature Reports',
          link: '/pikvero/public/admin/feedback-reports.php',
          icon: 'bi-bug-fill'
        });
      }
      if (adminItems.length > 0) {
        menuStructure.push({
          type: 'dropdown',
          groupKey: 'administration',
          label: 'Settings',
          icon: 'bi-sliders2',
          items: adminItems
        });
      }
    }

    // Build Navigation HTML
    let navListHtml = menuStructure.map(group => {
      if (group.type === 'single') {
        const isActive = (activeKey === group.key);
        return `
          <a href="${group.link}" onclick="if (typeof PageLoader !== 'undefined') PageLoader.show('Loading ${group.label.replace(/'/g, "\\'")}...');" class="button ${isActive ? 'coral' : 'sand'}" style="justify-content:flex-start; width:100%; border-radius:12px; margin-bottom:6px; padding:10px 14px; font-size:0.85rem; box-shadow: 2px 2px 0 var(--ink);">
            <i class="bi ${group.icon}"></i>
            <span>${group.label}</span>
          </a>
        `;
      } else if (group.type === 'dropdown') {
        const isGroupActive = group.items.some(item => item.key === activeKey);
        const childrenHtml = group.items.map(item => {
          const isItemActive = (activeKey === item.key);
          return `
            <a href="${item.link}" onclick="if (typeof PageLoader !== 'undefined') PageLoader.show('Loading ${item.label.replace(/'/g, "\\'")}...');" class="button ${isItemActive ? 'coral' : 'lime'}" style="justify-content:flex-start; width:100%; border-radius:10px; padding:8px 12px; font-size:0.80rem; box-shadow: 2px 2px 0 var(--ink);">
              <i class="bi ${item.icon}"></i>
              <span>${item.label}</span>
            </a>
          `;
        }).join('');

        return `
          <div style="margin-bottom:6px;">
            <button type="button" onclick="SidebarComponent.toggleSubmenu('${group.groupKey}')" class="button ${isGroupActive ? 'coral' : 'sand'}" style="justify-content:space-between; width:100%; border-radius:12px; padding:10px 14px; font-size:0.85rem; box-shadow: 2px 2px 0 var(--ink); cursor:pointer;">
              <div style="display:flex; align-items:center; gap:8px;">
                <i class="bi ${group.icon}"></i>
                <span style="font-weight:800;">${group.label}</span>
              </div>
              <i class="bi bi-chevron-down" id="arrow-${group.groupKey}" style="font-size:0.75rem; transition:transform 0.25s ease; ${isGroupActive ? 'transform:rotate(180deg);' : ''}"></i>
            </button>
            <div id="submenu-${group.groupKey}" style="display:${isGroupActive ? 'flex' : 'none'}; flex-direction:column; gap:4px; padding-left:14px; margin-top:4px; margin-bottom:4px; border-left:2px dashed var(--ink); margin-left:8px;">
              ${childrenHtml}
            </div>
          </div>
        `;
      }
      return '';
    }).join('');

    // Fetch dynamic logo and app title from AuthHelper.systemSettings
    const settings = (typeof AuthHelper !== 'undefined' && AuthHelper.systemSettings) ? AuthHelper.systemSettings : {};
    const logoUrl = settings.org_logo_url || '/pikvero/assets/images/logo.png';
    const appName = settings.app_name || 'Pikvero';

    const brandMarkHtml = logoUrl ? `
      <div class="brand-mark sidebar-logo-mark" style="width:48px; height:48px; border-radius:14px; overflow:hidden; padding:4px; display:flex; align-items:center; justify-content:center; background:var(--white); border:2px solid var(--ink); box-shadow:2.5px 2.5px 0 var(--ink); flex-shrink:0; box-sizing:border-box;">
        <img src="${logoUrl}" alt="${appName} Logo" style="width:100%; height:100%; max-width:100%; max-height:100%; object-fit:contain; display:block;" onerror="this.onerror=null; this.parentNode.innerHTML='P';">
      </div>
    ` : `<div class="brand-mark sidebar-logo-mark" style="width:48px; height:48px; font-size:1.4rem; border-radius:14px; flex-shrink:0;">P</div>`;

    el.innerHTML = `
      <div style="display:flex; flex-direction:column; justify-content:space-between; height:100%;">
        <div>
          <!-- Brand Header -->
          <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:24px; padding-bottom:12px; border-bottom:2px solid var(--ink);">
            <a href="/pikvero/public/index.php" class="brand-streetside" style="gap:12px; display:flex; align-items:center;">
              ${brandMarkHtml}
              <span style="font-weight:800; font-size:1.15rem; text-transform:uppercase; letter-spacing:-0.03em;">${appName}</span>
            </a>
            <button onclick="if(typeof closeSidebar === 'function') closeSidebar(); else document.querySelector('#sidebar-container')?.classList.remove('is-open');" class="button dark" style="padding:2px 8px; font-size:0.75rem; display:none;" id="sidebar-close-btn">&times;</button>
          </div>

          <div class="mono" style="margin-bottom:12px; color:var(--green); font-size:0.68rem; font-weight:700;">NAVIGATION &amp; PORTAL MENU</div>
          <div style="display:flex; flex-direction:column; gap:2px;">
            ${navListHtml}
          </div>
        </div>

        <div style="border-top:2px solid var(--ink); padding-top:14px; margin-top:auto;">
          ${portalType === 'guest' ? `
            <div style="display:flex; flex-direction:column; gap:8px;">
              <a href="/pikvero/public/login.php" class="button sand" style="justify-content:center; width:100%; border-radius:12px; padding:10px 14px; font-size:0.85rem; box-shadow:2px 2px 0 var(--ink);">
                <i class="bi bi-box-arrow-in-right"></i>
                <span>Login to Account</span>
              </a>
              <a href="/pikvero/public/register.php" class="button lime" style="justify-content:center; width:100%; border-radius:12px; padding:10px 14px; font-size:0.85rem; box-shadow:2px 2px 0 var(--ink);">
                <i class="bi bi-person-plus-fill"></i>
                <span>Play Local (Sign Up)</span>
              </a>
            </div>
          ` : `
            <button onclick="AuthHelper.logout()" class="button dark" style="justify-content:flex-start; width:100%; border-radius:12px; padding:10px 14px; font-size:0.82rem; box-shadow:2px 2px 0 var(--coral);">
              <i class="bi bi-box-arrow-right"></i>
              <span>Logout</span>
            </button>
          `}
        </div>
      </div>
    `;

    if (window.innerWidth <= 992 || el.classList.contains('drawer-only')) {
      const closeBtn = document.getElementById('sidebar-close-btn');
      if (closeBtn) closeBtn.style.display = 'inline-flex';
    }

    if (!window._sidebarOutsideClickListenerAttached) {
      window._sidebarOutsideClickListenerAttached = true;
      document.addEventListener('click', (e) => {
        const sidebar = document.querySelector('#sidebar-container');
        if (sidebar && sidebar.classList.contains('is-open')) {
          const toggleBtns = document.querySelectorAll('.hamburger-toggle-btn, #mobile-toggle-btn, #header-mobile-toggle-btn');
          let isClickInside = sidebar.contains(e.target);
          toggleBtns.forEach(btn => {
            if (btn.contains(e.target)) isClickInside = true;
          });
          if (!isClickInside) {
            if (typeof closeSidebar === 'function') {
              closeSidebar();
            } else {
              sidebar.classList.remove('is-open');
              document.body.classList.remove('sidebar-open');
            }
          }
        }
      });
    }
  }
};
