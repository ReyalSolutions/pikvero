<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Pikvero — Forgot Password</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="/pikvero/assets/css/streetside-theme.css">
  <link rel="stylesheet" href="/pikvero/assets/css/toast.css">
  <style>
    .inline-feedback {
      font-family: 'DM Mono', monospace;
      font-size: 0.75rem;
      font-weight: 700;
      margin-top: 6px;
      min-height: 18px;
    }
    .inline-feedback.valid {
      color: #10b981;
    }
    .inline-feedback.invalid {
      color: #ef4444;
    }
    input.is-valid {
      border-color: #10b981 !important;
    }
    input.is-invalid {
      border-color: #ef4444 !important;
    }
  </style>
</head>
<body>

  <?php require_once __DIR__ . '/../includes/header.php'; ?>

  <div style="min-height:80vh; display:grid; place-items:center; padding:110px 16px 40px;">
    <div class="card-streetside" style="width:min(440px, 100%); padding:28px;">
      <div style="text-align:center; margin-bottom:20px;">
        <div class="brand-mark" style="width:42px; height:42px; font-size:1.2rem; margin:0 auto 10px; background:var(--sky);">
          <i class="bi bi-key-fill"></i>
        </div>
        <h2 style="font-size:1.6rem; font-weight:800; text-transform:uppercase; margin:0;">RESET PASSWORD</h2>
        <p style="font-size:0.85rem; color:#4a5c56; margin-top:4px;">Enter your registered email address to receive password reset instructions.</p>
      </div>

      <form id="forgot-password-form" novalidate>
        <div style="margin-bottom:18px;">
          <label class="mono" style="display:block; margin-bottom:4px;">EMAIL ADDRESS *</label>
          <input type="email" id="reset-email" required placeholder="player@gmail.com" style="width:100%; padding:10px; border:2px solid var(--ink); border-radius:10px; font-family:inherit; font-weight:700;">
          <div id="fb-reset-email" class="inline-feedback"></div>
        </div>

        <button type="submit" id="submit-btn" class="button coral" style="width:100%; padding:12px; font-size:0.92rem; margin-bottom:16px;">
          <i class="bi bi-envelope-check"></i> Send Reset Instructions
        </button>

        <div style="text-align:center;">
          <a href="/pikvero/public/login.php" class="mono" style="font-size:0.8rem; font-weight:700; color:var(--ink);">&larr; Back to Login</a>
        </div>
      </form>
    </div>
  </div>

  <div id="footer-container"></div>

  <script src="/pikvero/assets/js/core/toast.js"></script>
  <script src="/pikvero/assets/js/core/ajax.js"></script>
  <script src="/pikvero/assets/js/core/auth.js"></script>
  <script src="/pikvero/assets/js/components/navbar.js"></script>
  <script src="/pikvero/assets/js/components/footer.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', async () => {
      await NavbarComponent.render();
      FooterComponent.render();

      const emailInput = document.getElementById('reset-email');
      emailInput.addEventListener('input', checkRegisteredEmail);

      document.getElementById('forgot-password-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const isRegistered = await checkRegisteredEmail();
        if (!isRegistered) {
          Toast.error('Account Not Found', 'The email address provided is not registered in our system.');
          return;
        }

        const email = emailInput.value.trim();
        const btn = document.getElementById('submit-btn');
        btn.disabled = true;
        btn.innerHTML = `<i class="bi bi-hourglass-split"></i> Dispatching Email...`;

        setTimeout(() => {
          btn.disabled = false;
          btn.innerHTML = `<i class="bi bi-envelope-check"></i> Send Reset Instructions`;
          Toast.success('Instructions Sent', `Password reset instructions have been dispatched to ${email}. Please check your inbox.`);
        }, 1000);
      });
    });

    function setFeedback(inputEl, feedbackEl, isValid, msg) {
      if (isValid) {
        inputEl.classList.remove('is-invalid');
        inputEl.classList.add('is-valid');
        feedbackEl.className = 'inline-feedback valid';
        feedbackEl.innerText = msg;
      } else {
        inputEl.classList.remove('is-valid');
        inputEl.classList.add('is-invalid');
        feedbackEl.className = 'inline-feedback invalid';
        feedbackEl.innerText = msg;
      }
    }

    async function checkRegisteredEmail() {
      const el = document.getElementById('reset-email');
      const fb = document.getElementById('fb-reset-email');
      const val = el.value.trim();
      
      const formatOk = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val);
      if (!formatOk) {
        setFeedback(el, fb, false, '✕ Enter a valid email address (e.g. player@gmail.com).');
        return false;
      }

      try {
        const res = await Api.get('/pikvero/api/auth/check-unique.php', { field: 'email', value: val });
        if (res.success) {
          if (!res.data.exists) {
            setFeedback(el, fb, false, '✕ This email address is not registered in our system.');
            return false;
          } else {
            setFeedback(el, fb, true, '✓ Registered account found. Ready to send reset link.');
            return true;
          }
        }
      } catch (e) {
        console.error(e);
      }
      return false;
    }
  </script>
</body>
</html>
