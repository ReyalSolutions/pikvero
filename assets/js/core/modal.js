/**
 * Custom Confirmation Modal Utility for Pikvero
 */
const Modal = {
  confirm({ title = 'Confirm Action', message = 'Are you sure you want to proceed?', confirmText = 'Confirm', cancelText = 'Cancel', type = 'primary', onConfirm, onCancel }) {
    const backdrop = document.createElement('div');
    backdrop.className = 'modal-backdrop-custom';

    const box = document.createElement('div');
    box.className = 'modal-box-custom';

    box.innerHTML = `
      <h3>${title}</h3>
      <p>${message}</p>
      <div class="modal-actions">
        <button class="button dark btn-cancel">${cancelText}</button>
        <button class="button ${type === 'danger' ? 'coral' : 'lime'} btn-confirm">${confirmText}</button>
      </div>
    `;

    backdrop.appendChild(box);
    document.body.appendChild(backdrop);

    requestAnimationFrame(() => {
      backdrop.classList.add('is-open');
    });

    const close = () => {
      backdrop.classList.remove('is-open');
      setTimeout(() => backdrop.remove(), 250);
    };

    box.querySelector('.btn-cancel').addEventListener('click', () => {
      close();
      if (typeof onCancel === 'function') onCancel();
    });

    box.querySelector('.btn-confirm').addEventListener('click', () => {
      close();
      if (typeof onConfirm === 'function') onConfirm();
    });
  },

  alert({ title = 'Notice', message = '', buttonText = 'OK', onConfirm }) {
    const backdrop = document.createElement('div');
    backdrop.className = 'modal-backdrop-custom';

    const box = document.createElement('div');
    box.className = 'modal-box-custom';

    box.innerHTML = `
      <h3>${title}</h3>
      <div>${message}</div>
      <div class="modal-actions" style="justify-content:center; margin-top:16px;">
        <button class="button lime btn-confirm" style="width:100%; font-weight:900; justify-content:center;">${buttonText}</button>
      </div>
    `;

    backdrop.appendChild(box);
    document.body.appendChild(backdrop);

    requestAnimationFrame(() => {
      backdrop.classList.add('is-open');
    });

    const close = () => {
      backdrop.classList.remove('is-open');
      setTimeout(() => backdrop.remove(), 250);
    };

    box.querySelector('.btn-confirm').addEventListener('click', () => {
      close();
      if (typeof onConfirm === 'function') onConfirm();
    });
  }
};
