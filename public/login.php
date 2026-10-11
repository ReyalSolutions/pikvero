<?php
/**
 * Pikvero — User & Court Owner Login Page
 * Redesigned to match the visual reference with frosted glass card and pricing-bg.png
 */
require_once __DIR__ . '/../app/bootstrap.php';
use App\Core\Auth\Auth;

// Calculate dynamic base URL and base path
$reqUri   = $_SERVER['REQUEST_URI'] ?? '/';
$basePath = (strpos($reqUri, '/pikvero') === 0) ? '/pikvero' : '';

if (class_exists(Auth::class) && Auth::check()) {
    $role = Auth::role();
    if ($role === 'court_owner') {
        header("Location: {$basePath}/public/owner/dashboard");
    } elseif ($role === 'super_admin' || $role === 'admin' || $role === 'platform_admin') {
        header("Location: {$basePath}/public/admin/dashboard");
    } else {
        header("Location: {$basePath}/public/customer/dashboard");
    }
    exit;
}

$favLogo = class_exists(Auth::class) ? Auth::getLogoUrl() : ($basePath . '/assets/images/logo.png');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
  <title>Pikvero — Welcome Back | Sign In</title>
  <meta name="description" content="Sign in to your Pikvero account to access your bookings, open play passes, and court management portal.">
  
  <link rel="icon" type="image/png" href="<?= htmlspecialchars($favLogo) ?>">
  <link rel="shortcut icon" type="image/png" href="<?= htmlspecialchars($favLogo) ?>">
  
  <!-- Google Fonts: Plus Jakarta Sans, Outfit, DM Mono -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@500;700;800&family=Outfit:wght@400;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  
  <!-- Bootstrap Icons & Toast -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="<?= $basePath ?>/assets/css/toast.css">

  <style>
    :root {
      --ink: #0c1a15;
      --dark-navy: #0f172a;
      --coral: #ff5733;
      --coral-hover: #e04422;
      --lime: #d4f82c;
      --lime-hover: #c2e51f;
      --sand: #f8fafc;
    }

    *, *::before, *::after {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      font-family: 'Plus Jakarta Sans', sans-serif;
      color: var(--dark-navy);
      min-height: 100vh;
      overflow-x: hidden;
      position: relative;
      background: #0c1a15 url('<?= $basePath ?>/assets/images/pricing-bg.png') no-repeat center top;
      background-size: cover;
      background-attachment: fixed;
      display: flex;
      flex-direction: column;
    }

    body::before {
      content: "";
      position: fixed;
      inset: 0;
      background: linear-gradient(180deg, 
        rgba(255, 255, 255, 0.40) 0%, 
        rgba(255, 255, 255, 0.15) 35%, 
        rgba(255, 255, 255, 0.35) 100%
      );
      pointer-events: none;
      z-index: 0;
    }

    /* ── Main Container ──────────────────────────────────────────────────────── */
    .login-page-wrap {
      position: relative;
      z-index: 1;
      width: 100%;
      flex: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 100px 20px 60px;
    }

    /* ── Frosted Login Card ─────────────────────────────────────────────────── */
    .login-card {
      width: 100%;
      max-width: 440px;
      background: rgba(255, 255, 255, 0.88);
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      border: 1.5px solid rgba(255, 255, 255, 0.95);
      border-radius: 28px;
      box-shadow: 0 24px 60px -12px rgba(15, 23, 42, 0.2), 0 2px 6px rgba(0, 0, 0, 0.04);
      padding: 36px 34px 30px;
      position: relative;
    }

    /* Top Logo Badge */
    .login-logo-badge {
      width: 46px;
      height: 46px;
      border-radius: 50%;
      background: #ff5e36;
      color: #0c1a15;
      border: 2px solid #000000;
      box-shadow: 2px 2px 0 #000000;
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: 'Outfit', sans-serif;
      font-size: 1.35rem;
      font-weight: 900;
      margin: 0 auto 12px;
      user-select: none;
    }

    .login-header {
      text-align: center;
      margin-bottom: 22px;
    }

    .login-title {
      font-family: 'Outfit', sans-serif;
      font-size: 1.85rem;
      font-weight: 900;
      letter-spacing: -0.02em;
      text-transform: uppercase;
      line-height: 1.1;
      margin-bottom: 6px;
    }

    .login-title .title-dark {
      color: var(--dark-navy);
    }

    .login-title .title-coral {
      color: var(--coral);
    }

    .login-subtitle {
      font-size: 0.86rem;
      color: #475569;
      font-weight: 500;
      line-height: 1.4;
    }

    /* Form Fields */
    .form-group {
      margin-bottom: 16px;
    }

    .form-label {
      display: block;
      font-family: 'DM Mono', monospace;
      font-size: 0.72rem;
      font-weight: 800;
      color: #334155;
      letter-spacing: 0.04em;
      text-transform: uppercase;
      margin-bottom: 6px;
    }

    .form-label-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 6px;
    }

    .forgot-link {
      font-family: 'DM Mono', monospace;
      font-size: 0.70rem;
      font-weight: 800;
      color: var(--coral);
      letter-spacing: 0.03em;
      text-transform: uppercase;
      text-decoration: none;
      transition: color 0.15s ease;
    }

    .forgot-link:hover {
      color: var(--coral-hover);
      text-decoration: underline;
    }

    .input-icon-wrap {
      position: relative;
      width: 100%;
    }

    .input-icon-wrap i.field-icon {
      position: absolute;
      left: 14px;
      top: 50%;
      transform: translateY(-50%);
      color: #64748b;
      font-size: 1rem;
      pointer-events: none;
    }

    .input-icon-wrap input {
      width: 100%;
      background: #ffffff;
      border: 1.5px solid #cbd5e1;
      border-radius: 12px;
      padding: 11px 14px 11px 40px;
      font-family: 'Plus Jakarta Sans', sans-serif;
      font-size: 0.90rem;
      font-weight: 600;
      color: var(--dark-navy);
      outline: none;
      transition: all 0.2s ease;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
    }

    .input-icon-wrap input::placeholder {
      color: #94a3b8;
      font-weight: 500;
    }

    .input-icon-wrap input:focus {
      border-color: var(--coral);
      box-shadow: 0 0 0 3px rgba(255, 87, 51, 0.15);
    }

    .input-icon-wrap i.toggle-password {
      position: absolute;
      right: 14px;
      top: 50%;
      transform: translateY(-50%);
      color: #64748b;
      font-size: 1.05rem;
      cursor: pointer;
      transition: color 0.15s;
    }

    .input-icon-wrap i.toggle-password:hover {
      color: var(--dark-navy);
    }

    /* Remember me */
    .remember-wrap {
      display: flex;
      align-items: center;
      margin-bottom: 20px;
    }

    .remember-label {
      display: inline-flex;
      align-items: center;
      gap: 7px;
      cursor: pointer;
      font-size: 0.82rem;
      font-weight: 600;
      color: #334155;
      user-select: none;
    }

    .remember-label input[type="checkbox"] {
      width: 16px;
      height: 16px;
      accent-color: var(--coral);
      cursor: pointer;
    }

    /* Sign In Button */
    .btn-signin {
      width: 100%;
      padding: 13px 20px;
      border-radius: 9999px;
      background: linear-gradient(135deg, #ff5e36 0%, #ff4b2b 100%);
      color: #ffffff;
      border: none;
      font-family: 'Outfit', sans-serif;
      font-weight: 800;
      font-size: 0.98rem;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      box-shadow: 0 8px 24px rgba(255, 94, 54, 0.4);
      cursor: pointer;
      transition: all 0.2s ease;
      text-decoration: none;
    }

    .btn-signin:hover {
      background: linear-gradient(135deg, #e04422 0%, #d83b1f 100%);
      transform: translateY(-2px);
      box-shadow: 0 10px 28px rgba(255, 94, 54, 0.5);
    }

    .btn-signin:active {
      transform: translateY(0);
    }

    /* Register Prompt Link */
    .register-prompt-box {
      margin-top: 18px;
      text-align: center;
      font-size: 0.88rem;
      color: #475569;
      font-weight: 500;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
    }

    .register-link {
      color: var(--coral);
      font-weight: 800;
      text-decoration: none;
      transition: color 0.15s ease;
    }

    .register-link:hover {
      color: var(--coral-hover);
      text-decoration: underline;
    }

    /* One-Click Demo Section */
    .demo-divider {
      border-top: 1px dashed rgba(0, 0, 0, 0.16);
      margin: 22px 0 14px;
      position: relative;
    }

    .demo-title {
      font-family: 'DM Mono', monospace;
      font-size: 0.68rem;
      font-weight: 800;
      color: #475569;
      letter-spacing: 0.04em;
      text-align: center;
      text-transform: uppercase;
      margin-bottom: 12px;
    }

    .demo-buttons-grid {
      display: grid;
      grid-template-columns: 1fr 1fr 1fr;
      gap: 8px;
    }

    .btn-demo {
      padding: 9px 12px;
      border-radius: 9999px;
      font-family: 'Outfit', sans-serif;
      font-weight: 800;
      font-size: 0.86rem;
      border: none;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      transition: all 0.2s ease;
    }

    .btn-demo.dark {
      background: #0c1a15;
      color: #ffffff;
      box-shadow: 0 4px 12px rgba(12, 26, 21, 0.25);
    }

    .btn-demo.dark:hover {
      background: #1e293b;
      transform: translateY(-2px);
    }

    .btn-demo.lime {
      background: var(--lime);
      color: #0c1a15;
      box-shadow: 0 4px 12px rgba(212, 248, 44, 0.35);
    }

    .btn-demo.lime:hover {
      background: var(--lime-hover);
      transform: translateY(-2px);
    }

    .btn-demo.coral {
      background: var(--coral);
      color: #ffffff;
      box-shadow: 0 4px 12px rgba(255, 87, 51, 0.35);
    }

    .btn-demo.coral:hover {
      background: var(--coral-hover);
      transform: translateY(-2px);
    }

    @media (max-width: 480px) {
      .login-page-wrap {
        padding: 86px 14px 40px;
      }
      .login-card {
        padding: 28px 20px 24px;
        border-radius: 22px;
      }
      .login-title {
        font-size: 1.6rem;
      }
    }
  </style>
<meta name="theme-color" content="#003d2d">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="Pikvero">
<link rel="apple-touch-icon" href="/pikvero/assets/images/pwa/icon-180.png">
<script defer src="/pikvero/assets/js/components/pwa.js?v=20261011-shortcut"></script>
</head>
<body>

  <!-- GLOBAL REUSABLE HEADER (Pinned at very top) -->
  <?php require_once __DIR__ . '/../includes/header.php'; ?>

  <!-- MAIN LOGIN CARD -->
  <main class="login-page-wrap">
    <div class="login-card">
      
      <!-- Top "P" Badge -->
      <div class="login-logo-badge">P</div>

      <!-- Welcome Back Header -->
      <div class="login-header">
        <h1 class="login-title">
          <span class="title-dark">WELCOME</span>
          <span class="title-coral">BACK</span>
        </h1>
        <p class="login-subtitle">Sign in to access your bookings or court portal</p>
      </div>

      <!-- Login Form -->
      <form id="login-form">
        
        <!-- Username or Email -->
        <div class="form-group">
          <label class="form-label" for="email">USERNAME OR EMAIL ADDRESS</label>
          <div class="input-icon-wrap">
            <i class="bi bi-person-fill field-icon"></i>
            <input type="text" id="email" required placeholder="username or player@gmail.com" autocomplete="username">
          </div>
        </div>

        <!-- Password -->
        <div class="form-group">
          <div class="form-label-row">
            <label class="form-label" for="password">PASSWORD</label>
            <a href="<?= $basePath ?>/public/forgot-password" class="forgot-link">FORGOT PASSWORD?</a>
          </div>
          <div class="input-icon-wrap">
            <i class="bi bi-lock-fill field-icon"></i>
            <input type="password" id="password" required placeholder="••••••••" autocomplete="current-password">
            <i class="bi bi-eye-slash-fill toggle-password" id="toggle-password" onclick="togglePasswordVisibility()" aria-label="Toggle password visibility"></i>
          </div>
        </div>

        <!-- Remember Me -->
        <div class="remember-wrap">
          <label class="remember-label">
            <input type="checkbox" id="remember-me">
            <span>Remember Me</span>
          </label>
        </div>

        <!-- Sign In Button -->
        <button type="submit" class="btn-signin" id="btn-submit">
          <i class="bi bi-box-arrow-in-right"></i> Sign In
        </button>
      </form>

      <!-- Register Prompt Link -->
      <div class="register-prompt-box">
        <span>Don't have an account?</span>
        <a href="<?= $basePath ?>/public/register" class="register-link">Create Account &rarr;</a>
      </div>

     

    </div>
  </main>

 

  <!-- Scripts -->
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="<?= $basePath ?>/assets/js/core/toast.js"></script>
  <script>
    window.APP_BASE_PATH = <?= json_encode($basePath) ?>;

    document.addEventListener('DOMContentLoaded', () => {
      // Restore saved username if Remember Me was previously checked
      const savedUser = localStorage.getItem('pikvero_remember_user');
      if (savedUser) {
        document.getElementById('email').value = savedUser;
        document.getElementById('remember-me').checked = true;
      }

      // Handle form submit
      const form = document.getElementById('login-form');
      form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const username = document.getElementById('email').value.trim();
        const password = document.getElementById('password').value;
        const rememberMe = document.getElementById('remember-me').checked;
        const btnSubmit = document.getElementById('btn-submit');

        if (!username || !password) return;

        btnSubmit.disabled = true;
        btnSubmit.innerHTML = '<i class="bi bi-arrow-repeat" style="animation: spin 1s linear infinite;"></i> Signing In...';

        try {
          const base = window.APP_BASE_PATH || '';
          const res = await $.ajax({
            url: base + '/api/auth/login.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ username, password })
          });

          if (res && res.success) {
            if (rememberMe) {
              localStorage.setItem('pikvero_remember_user', username);
            } else {
              localStorage.removeItem('pikvero_remember_user');
            }

            if (typeof Toast !== 'undefined') {
              Toast.success('Login Successful', `Welcome back, ${res.data.name || username}!`);
            }

            // Check URL redirect param
            const urlParams = new URLSearchParams(window.location.search);
            const redirectUrl = urlParams.get('redirect');

            setTimeout(() => {
              if (redirectUrl && redirectUrl.startsWith(base)) {
                window.location.href = redirectUrl;
              } else if (res.data.role === 'court_owner') {
                window.location.href = base + '/public/owner/dashboard';
              } else if (res.data.role === 'super_admin' || res.data.role === 'platform_admin' || res.data.role === 'admin') {
                window.location.href = base + '/public/admin/dashboard';
              } else {
                window.location.href = base + '/public/customer/dashboard';
              }
            }, 700);

          } else {
            const msg = (res && res.message) ? res.message : 'Invalid credentials. Please try again.';
            if (typeof Toast !== 'undefined') Toast.error('Sign In Failed', msg);
            btnSubmit.disabled = false;
            btnSubmit.innerHTML = '<i class="bi bi-box-arrow-in-right"></i> Sign In';
          }
        } catch (err) {
          const errMsg = (err.responseJSON && err.responseJSON.message) ? err.responseJSON.message : 'Connection failed. Please check your credentials.';
          if (typeof Toast !== 'undefined') Toast.error('Sign In Failed', errMsg);
          btnSubmit.disabled = false;
          btnSubmit.innerHTML = '<i class="bi bi-box-arrow-in-right"></i> Sign In';
        }
      });
    });

    function togglePasswordVisibility() {
      const passInput = document.getElementById('password');
      const icon = document.getElementById('toggle-password');
      if (passInput.type === 'password') {
        passInput.type = 'text';
        icon.className = 'bi bi-eye-fill toggle-password';
      } else {
        passInput.type = 'password';
        icon.className = 'bi bi-eye-slash-fill toggle-password';
      }
    }

    function demoLogin(username) {
      document.getElementById('email').value = username;
      document.getElementById('password').value = 'Password123!';
      const form = document.getElementById('login-form');
      form.dispatchEvent(new Event('submit'));
    }
  </script>
</body>
</html>
