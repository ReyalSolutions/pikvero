/**
 * Reusable Page Loader & Transition Utility for Pikvero
 */
const PageLoader = {
  overlayEl: null,
  progressBarEl: null,
  textEl: null,

  init() {
    if (document.getElementById('page-loader-overlay')) {
      this.overlayEl = document.getElementById('page-loader-overlay');
      this.progressBarEl = document.getElementById('page-progress-bar');
      this.textEl = document.getElementById('loader-msg');
      return;
    }

    // Create Top Progress Bar
    this.progressBarEl = document.createElement('div');
    this.progressBarEl.id = 'page-progress-bar';
    document.body.appendChild(this.progressBarEl);

    // Create Fullscreen Overlay
    this.overlayEl = document.createElement('div');
    this.overlayEl.id = 'page-loader-overlay';
    this.overlayEl.innerHTML = `
      <div class="loader-streetside-card">
        <div class="loader-spinner-ball"></div>
        <div class="loader-text-msg" id="loader-msg">Loading Pikvero...</div>
      </div>
    `;
    document.body.appendChild(this.overlayEl);
    this.textEl = document.getElementById('loader-msg');
  },

  show(msg = 'Loading Pikvero...') {
    if (!this.overlayEl) this.init();
    if (this.textEl) this.textEl.innerText = msg;
    if (this.progressBarEl) this.progressBarEl.style.width = '70%';
    if (this.overlayEl) this.overlayEl.classList.add('active');
  },

  hide() {
    if (this.progressBarEl) {
      this.progressBarEl.style.width = '100%';
      setTimeout(() => {
        if (this.progressBarEl) this.progressBarEl.style.width = '0%';
      }, 200);
    }
    if (this.overlayEl) {
      this.overlayEl.classList.remove('active');
    }
  }
};

// Initialize PageLoader when script loads
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => {
    PageLoader.init();
    setTimeout(() => { PageLoader.hide(); }, 150);
  });
} else {
  PageLoader.init();
  setTimeout(() => { PageLoader.hide(); }, 150);
}

// Show loader during window unload / navigation
window.addEventListener('beforeunload', () => {
  PageLoader.show('Loading Page...');
});
