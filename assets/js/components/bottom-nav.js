/**
 * Fixed Bottom Navigation Bar Component for Pikvero Mobile Customer Portal
 */
const BottomNavComponent = {
  render(activeKey = 'dashboard', selector = '#bottom-nav-container') {
    let el = document.querySelector(selector);
    if (!el) {
      el = document.createElement('div');
      el.id = selector.replace('#', '');
      document.body.appendChild(el);
    }

    const items = [
      { key: 'dashboard', label: 'Home', link: '/pikvero/public/customer/dashboard.php', icon: 'bi-house' },
      { key: 'bookings', label: 'Bookings', link: '/pikvero/public/customer/bookings.php', icon: 'bi-ticket-detailed' },
      { key: 'explore', label: 'Explore', link: '/pikvero/public/customer/search.php', icon: 'bi-search' },
      { key: 'open_play', label: 'Open Play', link: '/pikvero/public/customer/open-play.php', icon: 'bi-dribbble' },
      { key: 'profile', label: 'Profile', link: '/pikvero/public/customer/profile.php', icon: 'bi-person-circle' }
    ];

    const navItemsHtml = items.map(item => {
      const isActive = (activeKey === item.key);
      return `
        <a href="${item.link}" class="bottom-nav-item ${isActive ? 'active-tab' : ''}" ${isActive ? 'aria-current="page"' : ''} onclick="if (typeof PageLoader !== 'undefined') PageLoader.show('Loading ${item.label}...');">
          <i class="bi ${item.icon} bottom-nav-icon" aria-hidden="true"></i>
          <span class="bottom-nav-label">${item.label}</span>
        </a>
      `;
    }).join('');

    el.innerHTML = `
      <nav class="bottom-nav-streetside" aria-label="Player navigation">
        ${navItemsHtml}
      </nav>
    `;
  }
};
