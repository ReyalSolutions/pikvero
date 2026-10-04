/**
 * Custom Toast Notification Utility for Pikvero
 */
const Toast = {
  container: null,

  init() {
    if (!this.container) {
      this.container = document.createElement('div');
      this.container.id = 'toast-container';
      this.container.style.zIndex = '9999999';
    }

    if (this.container.parentNode !== document.body) {
      document.body.appendChild(this.container);
    }

    this.checkFlash();
  },

  checkFlash() {
    try {
      const flash = sessionStorage.getItem('flash_toast');
      if (flash) {
        sessionStorage.removeItem('flash_toast');
        const data = JSON.parse(flash);
        if (data && data.title) {
          setTimeout(() => {
            this.show(data.title, data.message || '', data.type || 'success', data.duration || 5000);
          }, 300);
        }
      }
    } catch(e) {}
  },

  setFlash(title, message, type = 'success', duration = 5000) {
    try {
      sessionStorage.setItem('flash_toast', JSON.stringify({ title, message, type, duration }));
    } catch(e) {}
  },

  show(title, message, type = 'info', duration = 4000) {
    this.init();

    const icons = {
      success: '✓',
      error: '✕',
      warning: '⚠',
      info: 'ℹ'
    };

    const item = document.createElement('div');
    item.className = `toast-item ${type}`;
    item.innerHTML = `
      <div class="toast-icon">${icons[type] || 'ℹ'}</div>
      <div class="toast-content">
        <div class="toast-title">${title}</div>
        <div class="toast-message">${message}</div>
      </div>
      <button class="toast-close">&times;</button>
    `;

    item.querySelector('.toast-close').addEventListener('click', () => {
      item.remove();
    });

    this.container.appendChild(item);

    if (duration > 0) {
      setTimeout(() => {
        item.style.opacity = '0';
        item.style.transition = 'opacity 0.3s ease';
        setTimeout(() => item.remove(), 300);
      }, duration);
    }
  },

  success(title, message, duration = 4000) {
    this.show(title, message, 'success', duration);
  },

  error(title, message, duration = 5000) {
    this.show(title, message, 'error', duration);
  },

  warning(title, message, duration = 4000) {
    this.show(title, message, 'warning', duration);
  },

  info(title, message, duration = 4000) {
    this.show(title, message, 'info', duration);
  }
};
