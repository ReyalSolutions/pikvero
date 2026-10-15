/**
 * Dashboard Sidebar Component for Pikvero (Full 100vh Fixed Layout)
 * Dynamically filters sidebar navigation links strictly based on module RBAC permissions,
 * organized under permanent, readable section headings.
 */
const SidebarComponent = {
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
    const isOwnerPortal = portalType === 'owner' || ['court_owner', 'owner'].includes(role);

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
          link: '/pikvero/public/customer/search.php',
          icon: 'bi-search'
        },
        {
          type: 'single',
          key: 'open_play',
          label: 'Open Play Socials',
          link: '/pikvero/public/customer/open-play.php',
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
          type: 'group',
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
          type: 'group',
          groupKey: 'court_bookings',
          label: 'Court Bookings',
          icon: 'bi-ticket-detailed-fill',
          items: [
            { key: 'bookings', label: 'My Bookings', link: '/pikvero/public/customer/bookings.php', icon: 'bi-journal-check' },
            { key: 'open_play', label: 'Open Play Socials', link: '/pikvero/public/customer/open-play.php', icon: 'bi-dribbble' },
            { key: 'explore', label: 'Book a Court', link: '/pikvero/public/customer/search.php', icon: 'bi-search' }
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
          link: isOwnerPortal ? '/pikvero/public/owner/dashboard.php' : '/pikvero/public/admin/dashboard.php',
          icon: 'bi-grid-1x2-fill'
        });
      }

      // 2. Venue Management Group (Facilities, Courts)
      const venueItems = [];
      if (hasAnyPerm('facility.view', 'facilities.view', 'facilities.manage', 'system.manage')) {
        venueItems.push({
          key: 'facilities',
          label: isOwnerPortal ? 'My Facilities' : 'Facilities',
          link: isOwnerPortal ? '/pikvero/public/owner/facilities.php' : '/pikvero/public/admin/facilities.php',
          icon: 'bi-building-fill'
        });
      }
      if (hasAnyPerm('court.view', 'courts.view', 'courts.manage', 'system.manage')) {
        venueItems.push({
          key: 'courts',
          label: 'Court Manager',
          link: isOwnerPortal ? '/pikvero/public/owner/courts.php' : '/pikvero/public/admin/courts.php',
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
          type: 'group',
          groupKey: 'venue',
          label: 'Venue Management',
          icon: 'bi-building-gear',
          items: venueItems
        });
      }

      // 3. Operations & Analytics Group (Bookings, Reports)
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
          link: isOwnerPortal ? '/pikvero/public/owner/bookings.php' : '/pikvero/public/admin/bookings.php',
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
          link: isOwnerPortal ? '/pikvero/public/owner/payments.php' : '/pikvero/public/admin/payments.php',
          icon: 'bi-credit-card-2-front-fill'
        });
      }
      if (hasAnyPerm('payouts.view', 'payouts.request', 'payouts.manage', 'system.manage')) {
        opsItems.push({
          key: 'payouts',
          label: 'GCash Payouts',
          link: isOwnerPortal ? '/pikvero/public/owner/payouts.php' : '/pikvero/public/admin/payouts.php',
          icon: 'bi-wallet-fill'
        });
      }
      if (hasAnyPerm('reports.view', 'report.view', 'reports.financial', 'system.manage')) {
        opsItems.push({
          key: 'reports',
          label: isOwnerPortal ? 'Analytics' : 'Revenue Analytics',
          link: isOwnerPortal ? '/pikvero/public/owner/reports.php' : '/pikvero/public/admin/reports.php',
          icon: 'bi-graph-up-arrow'
        });
      }
      if (isOwnerPortal) {
        opsItems.push({
          key: 'feedback',
          label: 'Feedback & Bug Reports',
          link: '/pikvero/public/owner/feedback.php',
          icon: 'bi-chat-right-dots-fill'
        });
      }
      if (opsItems.length > 0) {
        menuStructure.push({
          type: 'group',
          groupKey: 'operations',
          label: 'Operations',
          icon: 'bi-card-checklist',
          items: opsItems
        });
      }

      // 4. Platform Administration Group (Subscriptions, Users, Logs, Settings)
      const adminItems = [];
      if (hasAnyPerm('subscriptions.view', 'subscriptions.manage', 'subscription.view', 'subscription.manage', 'plans.manage', 'system.manage')) {
        adminItems.push({
          key: 'subscriptions',
          label: isOwnerPortal ? 'My Plan' : 'Subscription Plans',
          link: isOwnerPortal ? '/pikvero/public/owner/my-plan.php' : '/pikvero/public/admin/subscriptions.php',
          icon: 'bi-award-fill'
        });
      }
      if (hasAnyPerm('subscription_payments.view', 'subscription_payments.manage', 'system.manage')) {
        adminItems.push({
          key: 'subscription-payments',
          label: isOwnerPortal ? 'Plan Payments' : 'Sub Payments',
          link: isOwnerPortal ? '/pikvero/public/owner/subscription-payments.php' : '/pikvero/public/admin/subscription-payments.php',
          icon: 'bi-receipt-cutoff'
        });
      }
      if (hasAnyPerm('users.view', 'users.manage', 'roles.manage', 'permissions.manage', 'system.manage')) {
        adminItems.push({
          key: 'users',
          label: isOwnerPortal ? 'Team & Customers' : 'User Directory',
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
          label: isOwnerPortal ? 'Org Branding' : 'Security & Settings',
          link: isOwnerPortal ? '/pikvero/public/owner/settings.php' : '/pikvero/public/admin/settings.php',
          icon: isOwnerPortal ? 'bi-palette-fill' : 'bi-gear-fill'
        });
      }
      if (!isOwnerPortal) {
        adminItems.push({
          key: 'feedback_reports',
          label: 'Bug & Feature Reports',
          link: '/pikvero/public/admin/feedback-reports.php',
          icon: 'bi-bug-fill'
        });
      }
      if (['super_admin','platform_admin','developer','court_owner','owner'].includes(role) || hasAnyPerm('system.manage')) {
        adminItems.push({key:'announcements',label:'Announcements',link:'/pikvero/public/admin/announcements.php',icon:'bi-megaphone'});
      }
      adminItems.push({key:'notifications',label:'Notifications',link:'/pikvero/public/notifications.php',icon:'bi-bell'});
      if (adminItems.length > 0) {
        menuStructure.push({
          type: 'group',
          groupKey: 'administration',
          label: 'Settings',
          icon: 'bi-sliders2',
          items: adminItems
        });
      }
    }

    // Build Navigation HTML
    if (user?.user) {
      menuStructure.push({type:'single',key:'referrals',label:'Referral Bonuses',link:portalType==='customer'?'/pikvero/public/customer/referrals.php':'/pikvero/public/admin/referrals.php',icon:'bi-gift-fill'});
    }
    if (['super_admin','platform_admin'].includes(role)) {
      menuStructure.push({type:'single',key:'security',label:'Security Monitor',link:'/pikvero/public/admin/security.php',icon:'bi-shield-lock'});
      menuStructure.push({type:'single',key:'page-visits',label:'Page Visits',link:'/pikvero/public/admin/page-visits.php',icon:'bi-bar-chart-line'});
    }
    if (portalType !== 'guest' && portalType !== 'customer') {
      const links = menuStructure.flatMap(group => group.items || [group]);
      const sections = [
        ['Workspace', ['dashboard']],
        ['Facilities & courts', ['facilities', 'courts', 'amenities']],
        ['Bookings & play', ['calendar', 'bookings', 'open-play']],
        ['Sales & finances', ['products', 'payments', 'payouts', 'reports']],
        ['Account & team', ['subscriptions', 'subscription-payments', 'users', 'settings', 'referrals']],
        ['Communication & support', ['notifications', 'announcements', 'feedback', 'feedback_reports']],
        ['Platform administration', ['logs', 'security', 'page-visits']]
      ];
      menuStructure = sections.map(([label, keys], index) => ({
        type: 'group', groupKey: 'section-' + index, label,
        items: keys.map(key => links.find(link => link.key === key)).filter(Boolean)
      })).filter(group => group.items.length);
    }
    const renderLink = item => {
      const active = activeKey === item.key || (activeKey === 'open_play' && item.key === 'open-play');
      return `<a href="${item.link}" class="sidebar-nav-link${active ? ' is-active' : ''}"${active ? ' aria-current="page"' : ''}><i class="bi ${item.icon}" aria-hidden="true"></i><span>${item.label}</span></a>`;
    };
    const navListHtml = menuStructure.map(group => group.type === 'single' ? renderLink(group) : `
      <section class="sidebar-nav-section" aria-labelledby="sidebar-heading-${group.groupKey}">
        <h2 class="sidebar-nav-heading" id="sidebar-heading-${group.groupKey}">${group.label}</h2>
        <div class="sidebar-nav-links">${group.items.map(renderLink).join('')}</div>
      </section>`).join('');

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
      <style>
        .portal-sidebar .sidebar-nav-section{margin:0 0 18px}
        .portal-sidebar .sidebar-nav-heading{margin:0 10px 6px;font-family:inherit;font-size:.65rem;font-weight:800;line-height:1.5;letter-spacing:.09em;text-transform:uppercase;color:#64756d}
        .portal-sidebar .sidebar-nav-links{display:flex;flex-direction:column;gap:3px}
        .portal-sidebar .sidebar-nav-link{display:flex;align-items:center;gap:11px;min-height:42px;padding:9px 12px;box-sizing:border-box;border:1px solid transparent;border-radius:10px;color:var(--ink);font-size:.82rem;font-weight:650;text-decoration:none;line-height:1.35}
        .portal-sidebar .sidebar-nav-link i{font-size:1rem;flex:0 0 20px;text-align:center;color:var(--green)}
        .portal-sidebar .sidebar-nav-link:hover{background:#eaf1e9}
        .portal-sidebar .sidebar-nav-link:focus-visible{outline:2px solid var(--green);outline-offset:2px}
        .portal-sidebar .sidebar-nav-link.is-active{background:var(--green);color:#fff;border-color:var(--green)}
        .portal-sidebar .sidebar-nav-link.is-active i{color:var(--lime)}
      </style>
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


          <nav aria-label="Portal navigation">${navListHtml}</nav>
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
