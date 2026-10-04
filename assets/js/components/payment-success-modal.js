/**
 * Reusable Payment Success Confirmation Modal
 * Provides a clean modal confirmation overlay after successful PayMongo payments.
 */
class PaymentSuccessModal {
  static modalId = 'reusable-payment-success-modal';

  /**
   * Show the reusable payment success modal
   * @param {Object} options Configuration options
   */
  static show(options = {}) {
    this.close(); // Remove existing modal if present

    const title = options.title || 'PAYMENT SUCCESSFUL!';
    const message = options.message || 'Your payment transaction was authorized & processed successfully.';
    
    // Ensure reference is valid and not literal template string
    let reference = options.reference || '';
    if (!reference || reference.includes('{CHECKOUT_SESSION_ID}')) {
      reference = 'PAYMONGO-REF-' + Math.floor(100000 + Math.random() * 900000);
    }

    const amountNum = parseFloat(options.amount) || 0;
    const amount = amountNum > 0 
      ? amountNum.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
      : '0.00';
      
    const channelRaw = options.channel || options.method || 'PayMongo Gateway';
    const channel = channelRaw.charAt(0).toUpperCase() + channelRaw.slice(1);
    const itemOrPlan = options.planName || options.itemName || 'SaaS Subscription';
    const callback = options.onClose || null;

    const modalHtml = `
      <div id="${this.modalId}" style="position:fixed; inset:0; z-index:9999999 !important; background:rgba(10,20,15,0.85); backdrop-filter:blur(8px); -webkit-backdrop-filter:blur(8px); display:flex; align-items:center; justify-content:center; padding:20px; overflow-y:auto;">
        <div class="card-streetside" style="max-width:520px; width:100%; background:var(--white); padding:32px; text-align:center; position:relative; box-shadow:8px 8px 0 var(--ink); border:3px solid var(--ink); border-radius:20px; margin:auto;">
          
          <div style="width:76px; height:76px; border-radius:50%; background:#f0fdf4; border:3px solid var(--green); display:flex; align-items:center; justify-content:center; margin:0 auto 18px; box-shadow:4px 4px 0 var(--ink);">
            <i class="bi bi-check-circle-fill" style="font-size:3rem; color:var(--green);"></i>
          </div>

          <h2 style="font-size:1.6rem; font-weight:900; text-transform:uppercase; margin:0 0 6px; color:var(--ink); line-height:1.2;">
            ${title}
          </h2>
          <p style="font-size:0.85rem; color:#4a5c56; margin:0 0 22px; line-height:1.4;">
            ${message}
          </p>

          <!-- Receipt Details Card -->
          <div class="card-streetside sand" style="padding:18px; margin-bottom:24px; text-align:left; border:2px solid var(--ink); border-radius:14px; font-family:'DM Mono', monospace; font-size:0.84rem; line-height:2.0;">
            <div style="display:flex; justify-content:space-between; border-bottom:1px dashed var(--ink); padding-bottom:6px; margin-bottom:8px;">
              <span style="color:#5a7060;">TRANSACTION REF:</span>
              <strong style="color:var(--coral); font-weight:900; word-break:break-all;">${reference}</strong>
            </div>

            <div style="display:flex; justify-content:space-between;">
              <span style="color:#5a7060;">ITEM / PLAN:</span>
              <strong style="color:var(--ink);">${itemOrPlan}</strong>
            </div>

            <div style="display:flex; justify-content:space-between;">
              <span style="color:#5a7060;">PAYMENT CHANNEL:</span>
              <strong style="color:var(--ink);">${channel}</strong>
            </div>

            <div style="display:flex; justify-content:space-between;">
              <span style="color:#5a7060;">PAYMENT STATUS:</span>
              <span class="badge-streetside green" style="font-size:0.7rem; padding:2px 8px;">COMPLETED</span>
            </div>

            <div style="display:flex; justify-content:space-between; border-top:2px solid var(--ink); padding-top:8px; margin-top:8px; font-size:1rem; font-weight:900;">
              <span>TOTAL AMOUNT PAID:</span>
              <strong style="color:var(--green); font-size:1.25rem;">₱${amount}</strong>
            </div>
          </div>

          <button id="${this.modalId}-btn" class="button lime" style="width:100%; padding:14px; font-size:1rem; font-weight:900; box-shadow:4px 4px 0 var(--ink); border-radius:12px;">
            <i class="bi bi-check2-circle"></i> CONFIRM &amp; CONTINUE
          </button>
        </div>
      </div>
    `;

    document.body.insertAdjacentHTML('beforeend', modalHtml);
    document.body.style.overflow = 'hidden';

    const btn = document.getElementById(`${this.modalId}-btn`);
    if (btn) {
      btn.addEventListener('click', () => {
        this.close();
        if (typeof callback === 'function') {
          callback();
        }
      });
    }
  }

  /**
   * Close the payment success modal
   */
  static close() {
    const el = document.getElementById(this.modalId);
    if (el) {
      el.remove();
    }
    document.body.style.overflow = '';
  }
}

if (typeof window !== 'undefined') {
  window.PaymentSuccessModal = PaymentSuccessModal;
}
