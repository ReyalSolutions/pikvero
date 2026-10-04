/**
 * Reusable Footer Component for Pikvero
 */
const FooterComponent = {
  render(selector = '#footer-container', isPortal = false) {
    const el = document.querySelector(selector);
    if (!el) return;

    const settings = (typeof AuthHelper !== 'undefined' && AuthHelper.systemSettings) ? AuthHelper.systemSettings : {};
    const logoUrl = settings.org_logo_url || (window.SERVER_AUTH_STATE ? window.SERVER_AUTH_STATE.logoUrl : '/pikvero/assets/images/logo.png');
    const brandMarkHtml = `<div class="brand-mark navbar-logo-mark" style="width:26px; height:26px; border-radius:6px; overflow:hidden; padding:2px; flex-shrink:0; background:var(--white); border:1.5px solid var(--ink); box-sizing:border-box;"><img src="${logoUrl}" alt="Pikvero Logo" style="width:100%; height:100%; object-fit:contain; display:block;" onerror="this.onerror=null; this.parentNode.innerHTML='P';"></div>`;

    if (isPortal) {
      el.innerHTML = `
        <footer class="portal-footer">
          <div style="display:flex; align-items:center; gap:8px;">
            ${brandMarkHtml}
            <span>&copy; ${new Date().getFullYear()} Pikvero SaaS Platform</span>
          </div>
          <div style="display:flex; gap:16px; font-family:'DM Mono', monospace; font-size:0.75rem;">
            <a href="/pikvero/public/privacy.php" style="color:inherit; text-decoration:none;">PRIVACY</a>
            <a href="/pikvero/public/terms.php" style="color:inherit; text-decoration:none;">TERMS</a>
            <a href="/pikvero/public/contact.php" style="color:inherit; text-decoration:none;">SUPPORT</a>
          </div>
        </footer>
      `;
    } else {
      el.innerHTML = `
        <footer style="display:flex; justify-content:space-between; align-items:center; padding:32px max(4vw, 20px); border-top:2px solid var(--ink); background:var(--sand); margin-top:60px; font-size:0.82rem; font-weight:800; flex-wrap:wrap; gap:12px;">
          <div style="display:flex; align-items:center; gap:12px;">
            ${brandMarkHtml}
            <span>&copy; ${new Date().getFullYear()} Pikvero Platform</span>
          </div>
          <div style="display:flex; gap:20px; font-family:'DM Mono', monospace;">
            <a href="/pikvero/public/about.php" style="color:inherit; text-decoration:none;">ABOUT</a>
            <a href="/pikvero/public/pricing.php" style="color:inherit; text-decoration:none;">PRICING</a>
            <a href="/pikvero/public/privacy.php" style="color:inherit; text-decoration:none;">PRIVACY</a>
            <a href="/pikvero/public/terms.php" style="color:inherit; text-decoration:none;">TERMS</a>
            <a href="/pikvero/public/contact.php" style="color:inherit; text-decoration:none;">SUPPORT</a>
          </div>
        </footer>
      `;
    }
  }
};
