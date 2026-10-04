<?php require_once __DIR__ . '/../app/bootstrap.php'; ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Pikvero — Login to Your Account</title>
  <?php $favLogo = class_exists(\App\Core\Auth\Auth::class) ? \App\Core\Auth\Auth::getLogoUrl() : '/pikvero/assets/images/logo.png'; ?>
  <link rel="icon" type="image/png" href="<?= htmlspecialchars($favLogo) ?>">
  <link rel="shortcut icon" type="image/png" href="<?= htmlspecialchars($favLogo) ?>">
  <link rel="apple-touch-icon" href="<?= htmlspecialchars($favLogo) ?>">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="/pikvero/assets/css/streetside-theme.css?v=3">
  <link rel="stylesheet" href="/pikvero/assets/css/toast.css">
</head>
<body>

  <?php require_once __DIR__ . '/../includes/header.php'; ?>

  <div style="min-height:80vh; display:grid; place-items:center; padding:110px 16px 40px;">
    <div class="card-streetside" style="width:min(440px, 100%); padding:28px;">
      <div style="text-align:center; margin-bottom:20px;">
        <div class="brand-mark" style="width:42px; height:42px; font-size:1.2rem; margin:0 auto 10px;">P</div>
        <h2 style="font-size:1.6rem; font-weight:800; text-transform:uppercase; margin:0;">Welcome Back</h2>
        <p style="font-size:0.85rem; color:#4a5c56;">Sign in to access your bookings or court portal</p>
      </div>

      <form id="login-form">
        <div style="margin-bottom:14px;">
          <label class="mono" style="display:block; margin-bottom:4px;">USERNAME OR EMAIL ADDRESS</label>
          <input type="text" id="email" required placeholder="username or player@gmail.com" style="width:100%; padding:10px; border:2px solid var(--ink); border-radius:10px; font-family:inherit; font-weight:700;">
        </div>

        <div style="margin-bottom:14px;">
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
            <label class="mono">PASSWORD</label>
            <a href="/pikvero/public/forgot-password.php" class="mono" style="font-size:0.75rem; color:var(--coral); font-weight:700;">Forgot Password?</a>
          </div>
          <div style="position:relative;">
            <input type="password" id="password" required placeholder="••••••••" style="width:100%; padding:10px 36px 10px 10px; border:2px solid var(--ink); border-radius:10px; font-family:inherit; font-weight:700;">
            <i class="bi bi-eye-slash-fill" id="toggle-password" onclick="togglePasswordVisibility()" style="position:absolute; right:12px; top:50%; transform:translateY(-50%); cursor:pointer; color:#6b7c76; font-size:1.1rem;"></i>
          </div>
        </div>

        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:18px;">
          <label style="display:flex; align-items:center; gap:6px; cursor:pointer; font-family:'DM Mono', monospace; font-size:0.78rem; font-weight:700; color:var(--ink);">
            <input type="checkbox" id="remember-me" style="width:16px; height:16px; accent-color:var(--coral); cursor:pointer;">
            <span>Remember Me</span>
          </label>
        </div>

        <button type="submit" class="button coral" style="width:100%; padding:12px; font-size:0.92rem; margin-bottom:16px;">
          <i class="bi bi-box-arrow-in-right"></i> Sign In
        </button>
      </form>

      <!-- Quick Demo Login Presets -->
      <div style="border-top:2px dashed var(--ink); padding-top:14px; margin-top:8px;">
        <div class="mono" style="font-size:0.68rem; color:var(--green); text-align:center; margin-bottom:8px;">ONE-CLICK DEMO LOGIN (USERNAME)</div>
        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(90px, 1fr)); gap:6px;">
          <button onclick="demoLogin('player')" class="button dark" style="padding:6px; font-size:0.7rem;">Player</button>
          <button onclick="demoLogin('owner')" class="button lime" style="padding:6px; font-size:0.7rem;">Owner</button>
          <button onclick="demoLogin('admin')" class="button coral" style="padding:6px; font-size:0.7rem;">Admin</button>
        </div>
      </div>
    </div>
  </div>

  <script src="/pikvero/assets/js/core/toast.js"></script>
  <script src="/pikvero/assets/js/core/ajax.js"></script>
  <script src="/pikvero/assets/js/core/auth.js"></script>
  <script src="/pikvero/assets/js/components/navbar.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', async () => {
      await NavbarComponent.render();

      // Restore saved username if Remember Me was active
      const savedUser = localStorage.getItem('pikvero_remember_user');
      if (savedUser) {
        document.getElementById('email').value = savedUser;
        document.getElementById('remember-me').checked = true;
      }

      document.getElementById('login-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const username = document.getElementById('email').value;
        const password = document.getElementById('password').value;
        const rememberMe = document.getElementById('remember-me').checked;

        try {
          const res = await Api.post('/pikvero/api/auth/login.php', { username, password });
          if (res.success) {
            if (rememberMe) {
              localStorage.setItem('pikvero_remember_user', username);
            } else {
              localStorage.removeItem('pikvero_remember_user');
            }

            Toast.success('Login Successful', `Welcome back, ${res.data.name}!`);
            setTimeout(() => {
              if (res.data.role === 'court_owner') window.location.href = '/pikvero/public/owner/dashboard.php';
              else if (res.data.role === 'super_admin' || res.data.role === 'platform_admin') window.location.href = '/pikvero/public/admin/dashboard.php';
              else window.location.href = '/pikvero/public/customer/dashboard.php';
            }, 800);
          }
        } catch (err) {
          console.error(err);
        }
      });
    });

    function togglePasswordVisibility() {
      const passInput = document.getElementById('password');
      const icon = document.getElementById('toggle-password');
      if (passInput.type === 'password') {
        passInput.type = 'text';
        icon.className = 'bi bi-eye-fill';
      } else {
        passInput.type = 'password';
        icon.className = 'bi bi-eye-slash-fill';
      }
    }

    async function demoLogin(username) {
      document.getElementById('email').value = username;
      document.getElementById('password').value = 'Password123!';
      document.getElementById('login-form').dispatchEvent(new Event('submit'));
    }
  </script>
</body>
</html>
