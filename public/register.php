<?php
/**
 * Pikvero — Create Your Account
 * Streetside aesthetic with crisp white card, high contrast typography, and pricing-bg.png
 */
require_once __DIR__ . '/../app/bootstrap.php';
use App\Core\Auth\Auth;

// Dynamic base path calculation for local & live deployments
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
  <title>Pikvero — Create Your Account</title>
  <meta name="description" content="Sign up for Pikvero to book pickleball courts across Bohol, join open play sessions, or register your facility.">
  
  <link rel="icon" type="image/png" href="<?= htmlspecialchars($favLogo) ?>">
  <link rel="shortcut icon" type="image/png" href="<?= htmlspecialchars($favLogo) ?>">
  <link rel="apple-touch-icon" href="<?= htmlspecialchars($favLogo) ?>">
  
  <!-- Google Fonts: Plus Jakarta Sans, Outfit, DM Mono -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@500;700;800&family=Outfit:wght@400;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  
  <!-- Bootstrap Icons & Toast CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="<?= $basePath ?>/assets/css/toast.css">

  <style>
    :root {
      --ink: #0c1a15;
      --dark-navy: #0c1a15;
      --coral: #ff5733;
      --coral-hover: #e04422;
      --lime: #d4f82c;
      --lime-hover: #c2e51f;
      --sand: #f8fafc;
      --green: #15803d;
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
      background: #f1f5f9 url('<?= $basePath ?>/assets/images/pricing-bg.png') no-repeat center top;
      background-size: cover;
      background-attachment: fixed;
      display: flex;
      flex-direction: column;
    }

    body::before {
      content: "";
      position: fixed;
      inset: 0;
      background: rgba(241, 245, 249, 0.72);
      pointer-events: none;
      z-index: 0;
    }

    /* ── Main Container ──────────────────────────────────────────────────────── */
    .register-page-wrap {
      position: relative;
      z-index: 1;
      width: 100%;
      flex: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 105px 20px 60px;
    }

    /* ── High Contrast Card ─────────────────────────────────────────────────── */
    .register-card {
      width: 100%;
      max-width: 580px;
      background: #ffffff;
      border: 2px solid #0c1a15;
      border-radius: 24px;
      box-shadow: 6px 6px 0 #0c1a15;
      padding: 38px 36px 32px;
      position: relative;
    }

    @media (max-width: 520px) {
      .register-card {
        padding: 26px 20px 22px;
        border-radius: 18px;
        box-shadow: 4px 4px 0 #0c1a15;
      }
    }

    /* Top Logo Badge */
    .brand-mark {
      width: 50px;
      height: 50px;
      border-radius: 50%;
      background: #ff5733;
      color: #ffffff;
      border: 2.5px solid #000000;
      box-shadow: 2px 2px 0 #000000;
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: 'Outfit', sans-serif;
      font-size: 1.45rem;
      font-weight: 900;
      margin: 0 auto 12px;
      user-select: none;
    }

    .register-header {
      text-align: center;
      margin-bottom: 24px;
    }

    .register-title {
      font-family: 'Outfit', sans-serif;
      font-size: 2rem;
      font-weight: 900;
      color: #0c1a15;
      letter-spacing: -0.02em;
      text-transform: uppercase;
      line-height: 1.15;
    }

    .register-subtitle {
      font-size: 0.9rem;
      color: #1e293b;
      font-weight: 700;
      margin-top: 6px;
    }

    /* ── Role Selector Segmented Control ────────────────────────────────────── */
    .role-switcher {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 6px;
      background: #e2e8f0;
      padding: 6px;
      border-radius: 14px;
      margin-bottom: 20px;
      border: 2px solid #0c1a15;
    }

    .role-btn {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      padding: 10px 14px;
      border-radius: 10px;
      font-family: 'DM Mono', monospace;
      font-size: 0.82rem;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: 0.02em;
      border: none;
      cursor: pointer;
      background: transparent;
      color: #334155;
      transition: all 0.2s ease;
    }

    .role-btn.active {
      background: #0c1a15;
      color: #ffffff;
      box-shadow: 2px 2px 0 rgba(0, 0, 0, 0.2);
    }

    .role-btn:hover:not(.active) {
      color: #0c1a15;
      background: rgba(255, 255, 255, 0.7);
    }

    /* ── Role Info Banner ───────────────────────────────────────────────────── */
    .role-banner {
      background: #e8f98c;
      border: 2px solid #0c1a15;
      box-shadow: 3px 3px 0 #0c1a15;
      border-radius: 14px;
      padding: 12px 16px;
      margin-bottom: 22px;
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .role-banner-icon {
      width: 38px;
      height: 38px;
      border-radius: 10px;
      background: #0c1a15;
      color: #d4f82c;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.2rem;
      flex-shrink: 0;
    }

    .role-banner-text {
      font-size: 0.85rem;
      color: #0c1a15;
      font-weight: 600;
      line-height: 1.35;
    }

    .role-banner-text strong {
      font-weight: 900;
      color: #0c1a15;
    }

    /* Owner Callout Card (shown when owner is toggled) */
    .owner-callout-card {
      display: none;
      background: #f8fafc;
      border: 2px solid #0c1a15;
      border-radius: 16px;
      padding: 24px;
      text-align: center;
      margin-bottom: 20px;
      box-shadow: 4px 4px 0 #0c1a15;
    }

    /* ── Form Inputs ────────────────────────────────────────────────────────── */
    .form-grid-2 {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 14px;
      margin-bottom: 14px;
    }

    @media (max-width: 520px) {
      .form-grid-2 {
        grid-template-columns: 1fr;
        gap: 14px;
      }
    }

    .form-group {
      margin-bottom: 16px;
      position: relative;
    }

    .form-label {
      display: flex;
      align-items: center;
      gap: 6px;
      font-family: 'DM Mono', monospace;
      font-size: 0.78rem;
      font-weight: 900;
      letter-spacing: 0.05em;
      text-transform: uppercase;
      color: #0c1a15;
      margin-bottom: 6px;
    }

    .input-wrapper {
      position: relative;
      display: flex;
      align-items: center;
    }

    .input-icon-left {
      position: absolute;
      left: 14px;
      font-size: 1.1rem;
      color: #0c1a15;
      pointer-events: none;
      transition: color 0.2s;
    }

    .custom-input {
      width: 100%;
      height: 48px;
      background: #ffffff;
      border: 2px solid #0c1a15;
      border-radius: 12px;
      padding: 0 16px 0 42px;
      font-family: inherit;
      font-size: 0.95rem;
      font-weight: 700;
      color: #0c1a15;
      transition: all 0.2s ease;
      outline: none;
    }

    .custom-input::placeholder {
      color: #64748b;
      font-weight: 500;
    }

    .custom-input:focus {
      border-color: #ff5733;
      box-shadow: 0 0 0 3px rgba(255, 87, 51, 0.25);
    }

    .custom-input.is-valid {
      border-color: #10b981 !important;
      background-color: #f0fdf4;
    }

    .custom-input.is-invalid {
      border-color: #ef4444 !important;
      background-color: #fef2f2;
    }

    /* Password Toggle Button */
    .toggle-pass-btn {
      position: absolute;
      right: 12px;
      background: none;
      border: none;
      color: #0c1a15;
      font-size: 1.2rem;
      cursor: pointer;
      padding: 4px;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: color 0.2s;
    }

    .toggle-pass-btn:hover {
      color: #ff5733;
    }

    /* Inline Feedback */
    .inline-feedback {
      font-family: 'DM Mono', monospace;
      font-size: 0.76rem;
      font-weight: 800;
      margin-top: 4px;
      display: block;
      min-height: 18px;
      line-height: 1.2;
    }

    .inline-feedback.valid {
      color: #047857;
    }

    .inline-feedback.invalid {
      color: #b91c1c;
    }

    /* Password Strength Meter */
    .strength-meter-wrap {
      margin-top: 6px;
      width: 100%;
    }

    .strength-meter-bg {
      height: 6px;
      width: 100%;
      background: #e2e8f0;
      border: 1px solid #cbd5e1;
      border-radius: 99px;
      overflow: hidden;
    }

    .strength-bar {
      height: 100%;
      width: 0%;
      border-radius: 99px;
      transition: width 0.3s ease, background 0.3s ease;
    }

    .strength-bar.weak {
      width: 30%;
      background: #ef4444;
    }

    .strength-bar.fair {
      width: 65%;
      background: #f59e0b;
    }

    .strength-bar.strong {
      width: 100%;
      background: #10b981;
    }

    /* ── Submit CTA ─────────────────────────────────────────────────────────── */
    .btn-submit {
      width: 100%;
      height: 52px;
      margin-top: 16px;
      background: #ff5733;
      color: #ffffff;
      border: 2px solid #000000;
      box-shadow: 4px 4px 0 #000000;
      border-radius: 12px;
      font-family: 'DM Mono', monospace;
      font-size: 0.95rem;
      font-weight: 900;
      text-transform: uppercase;
      letter-spacing: 0.03em;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      cursor: pointer;
      transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    }

    .btn-submit:hover {
      background: #e04422;
      transform: translate(-2px, -2px);
      box-shadow: 6px 6px 0 #000000;
    }

    .btn-submit:active {
      transform: translate(1px, 1px);
      box-shadow: 2px 2px 0 #000000;
    }

    .btn-submit:disabled {
      opacity: 0.6;
      cursor: not-allowed;
      transform: none !important;
      box-shadow: 3px 3px 0 #000000 !important;
    }

    /* ── Sign In Footer Link ────────────────────────────────────────────────── */
    .signin-prompt {
      margin-top: 24px;
      text-align: center;
      font-size: 0.9rem;
      color: #1e293b;
      font-weight: 700;
      border-top: 2px solid #e2e8f0;
      padding-top: 18px;
    }

    .signin-prompt a {
      color: #ff5733;
      font-weight: 900;
      text-decoration: underline;
      margin-left: 4px;
      transition: color 0.2s;
    }

    .signin-prompt a:hover {
      color: #0c1a15;
    }

    .terms-text {
      font-size: 0.78rem;
      color: #475569;
      font-weight: 600;
      text-align: center;
      margin-top: 12px;
      line-height: 1.4;
    }

    .terms-text a {
      color: #0c1a15;
      font-weight: 800;
      text-decoration: underline;
    }
  </style>
</head>
<body>

  <!-- Dynamic Fixed Header -->
  <?php require_once __DIR__ . '/../includes/header.php'; ?>

  <main class="register-page-wrap">
    <div class="register-card">
      
      <!-- Logo Mark -->
      <div class="brand-mark">P</div>

      <!-- Header -->
      <div class="register-header">
        <h1 class="register-title">CREATE AN ACCOUNT</h1>
        <p class="register-subtitle">Join Pikvero to book courts, join open play, or manage your facility</p>
      </div>

      <!-- Role Switcher -->
      <div class="role-switcher">
        <button type="button" class="role-btn active type-btn" id="btn-type-player" data-type="player">
          <i class="bi bi-person-fill"></i> Player
        </button>
        <button type="button" class="role-btn type-btn" id="btn-type-owner" data-type="owner">
          <i class="bi bi-building-fill"></i> Court Owner
        </button>
      </div>

      <!-- Player Role Banner -->
      <div id="role-indicator-banner" class="role-banner">
        <div class="role-banner-icon">
          <i id="role-icon" class="bi bi-person-fill"></i>
        </div>
        <div class="role-banner-text">
          Registering as a <strong>Player / Customer</strong> to discover venues, book time slots, and register for community open play in Bohol.
        </div>
      </div>

      <!-- Dedicated Court Owner Redirection Card -->
      <div id="owner-redirect-card" class="owner-callout-card">
        <div style="width:52px; height:52px; border-radius:50%; background:#d4f82c; color:#0c1a15; display:grid; place-items:center; font-size:1.6rem; margin:0 auto 12px; border:2px solid #000; box-shadow:2px 2px 0 #000;">
          <i class="bi bi-patch-check-fill"></i>
        </div>
        <h3 style="font-family:'Outfit', sans-serif; font-size:1.35rem; font-weight:900; text-transform:uppercase; margin-bottom:8px; color:#0c1a15;">
          COURT OWNER ONBOARDING WIZARD
        </h3>
        <p style="font-size:0.88rem; color:#1e293b; font-weight:600; margin-bottom:18px; line-height:1.45;">
          Court owners register through our dedicated 10-step onboarding wizard to configure their facility, courts, hourly pricing, and SaaS platform subscription.
        </p>
        <a href="<?= $basePath ?>/public/owner-onboarding" class="btn-submit" style="text-decoration:none;">
          <i class="bi bi-arrow-right-circle-fill"></i> Start Owner Onboarding &rarr;
        </a>
      </div>

      <!-- Registration Form -->
      <form id="register-form" novalidate>
        
        <!-- Names Grid -->
        <div class="form-grid-2">
          <div class="form-group" style="margin-bottom:0;">
            <label class="form-label" for="first_name">
              <i class="bi bi-person"></i> First Name *
            </label>
            <div class="input-wrapper">
              <i class="bi bi-person input-icon-left"></i>
              <input type="text" id="first_name" class="custom-input" placeholder="Juan" autocomplete="given-name" required>
            </div>
            <span id="fb-first-name" class="inline-feedback"></span>
          </div>

          <div class="form-group" style="margin-bottom:0;">
            <label class="form-label" for="last_name">
              <i class="bi bi-person"></i> Last Name *
            </label>
            <div class="input-wrapper">
              <i class="bi bi-person input-icon-left"></i>
              <input type="text" id="last_name" class="custom-input" placeholder="Dela Cruz" autocomplete="family-name" required>
            </div>
            <span id="fb-last-name" class="inline-feedback"></span>
          </div>
        </div>

        <!-- Email -->
        <div class="form-group">
          <label class="form-label" for="email">
            <i class="bi bi-envelope"></i> Email Address *
          </label>
          <div class="input-wrapper">
            <i class="bi bi-envelope input-icon-left"></i>
            <input type="email" id="email" class="custom-input" placeholder="juan.delacruz@example.com" autocomplete="email" required>
          </div>
          <span id="fb-email" class="inline-feedback"></span>
        </div>

        <!-- Phone -->
        <div class="form-group">
          <label class="form-label" for="phone">
            <i class="bi bi-phone"></i> Phone Number (11 Digits) *
          </label>
          <div class="input-wrapper">
            <i class="bi bi-phone input-icon-left"></i>
            <input type="tel" id="phone" class="custom-input" maxlength="11" placeholder="09171234567" autocomplete="tel" required>
          </div>
          <span id="fb-phone" class="inline-feedback"></span>
        </div>

        <!-- Optional Org Field (hidden by default) -->
        <div id="org-name-field" class="form-group" style="display:none;">
          <label class="form-label" for="organization_name">
            <i class="bi bi-building"></i> Organization / Club Name *
          </label>
          <div class="input-wrapper">
            <i class="bi bi-building input-icon-left"></i>
            <input type="text" id="organization_name" class="custom-input" placeholder="e.g. Bohol Pickleball Club">
          </div>
          <span id="fb-org-name" class="inline-feedback"></span>
        </div>

        <!-- Password -->
        <div class="form-group">
          <label class="form-label" for="password">
            <i class="bi bi-shield-lock"></i> Password *
          </label>
          <div class="input-wrapper">
            <i class="bi bi-shield-lock input-icon-left"></i>
            <input type="password" id="password" class="custom-input" placeholder="At least 6 characters" autocomplete="new-password" style="padding-right:44px;" required>
            <button type="button" class="toggle-pass-btn password-toggle-btn" data-target="password" title="Toggle password visibility">
              <i class="bi bi-eye-slash-fill"></i>
            </button>
          </div>
          <!-- Strength Meter Bar -->
          <div class="strength-meter-wrap">
            <div class="strength-meter-bg">
              <div id="strength-bar" class="strength-bar"></div>
            </div>
          </div>
          <span id="fb-password" class="inline-feedback"></span>
        </div>

        <!-- Confirm Password -->
        <div class="form-group">
          <label class="form-label" for="confirm_password">
            <i class="bi bi-check2-circle"></i> Confirm Password *
          </label>
          <div class="input-wrapper">
            <i class="bi bi-check2-circle input-icon-left"></i>
            <input type="password" id="confirm_password" class="custom-input" placeholder="Re-enter your password" autocomplete="new-password" style="padding-right:44px;" required>
            <button type="button" class="toggle-pass-btn password-toggle-btn" data-target="confirm_password" title="Toggle confirm password visibility">
              <i class="bi bi-eye-slash-fill"></i>
            </button>
          </div>
          <span id="fb-confirm-password" class="inline-feedback"></span>
        </div>

        <!-- Submit Button -->
        <button type="submit" id="submit-btn" class="btn-submit">
          <i class="bi bi-person-plus-fill"></i> Complete Registration
        </button>

        <p class="terms-text">
          By registering, you agree to Pikvero's <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a>.
        </p>
      </form>

      <!-- Sign In Option -->
      <div class="signin-prompt">
        Already have an account?
        <a href="<?= $basePath ?>/public/login">Sign In &rarr;</a>
      </div>

    </div>
  </main>

  <!-- Global Desktop Footer -->
  <?php require_once __DIR__ . '/../includes/footer.php'; ?>

  <!-- Scripts -->
  <script src="<?= $basePath ?>/assets/js/core/toast.js"></script>
  <script src="<?= $basePath ?>/assets/js/core/ajax.js"></script>
  <script src="<?= $basePath ?>/assets/js/core/auth.js"></script>
  <script src="<?= $basePath ?>/assets/js/components/navbar.js"></script>
  <script>
    let selectedType = 'player';
    let fields = {};
    const APP_BASE = '<?= $basePath ?>';

    function setFeedback(input, feedbackEl, isValid, message) {
      if (isValid) {
        input.classList.remove('is-invalid');
        input.classList.add('is-valid');
        feedbackEl.className = 'inline-feedback valid';
        feedbackEl.innerText = message || '✓ Looks good';
      } else {
        input.classList.remove('is-valid');
        input.classList.add('is-invalid');
        feedbackEl.className = 'inline-feedback invalid';
        feedbackEl.innerText = message;
      }
    }

    function validateFirstName() {
      if (!fields.firstName) return true;
      const val = fields.firstName.value.trim();
      const isValid = val.length >= 2;
      setFeedback(fields.firstName, document.getElementById('fb-first-name'), isValid, isValid ? '✓ Valid' : 'First name must be at least 2 characters.');
      return isValid;
    }

    function validateLastName() {
      if (!fields.lastName) return true;
      const val = fields.lastName.value.trim();
      const isValid = val.length >= 2;
      setFeedback(fields.lastName, document.getElementById('fb-last-name'), isValid, isValid ? '✓ Valid' : 'Last name must be at least 2 characters.');
      return isValid;
    }

    function validateEmail() {
      if (!fields.email) return true;
      const val = fields.email.value.trim();
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      const isValid = emailRegex.test(val);
      setFeedback(fields.email, document.getElementById('fb-email'), isValid, isValid ? '✓ Valid email address' : 'Please enter a valid email address.');
      return isValid;
    }

    function validatePhone() {
      if (!fields.phone) return true;
      fields.phone.value = fields.phone.value.replace(/\D/g, ''); // Numbers only
      const val = fields.phone.value.trim();
      const phoneRegex = /^\d{11}$/;
      const isValid = phoneRegex.test(val);
      setFeedback(fields.phone, document.getElementById('fb-phone'), isValid, isValid ? '✓ Valid 11-digit phone number' : 'Phone number must be exactly 11 digits (e.g. 09171234567).');
      return isValid;
    }

    function validateOrgName() {
      if (selectedType !== 'owner' || !fields.orgName) return true;
      const val = fields.orgName.value.trim();
      const isValid = val.length >= 3;
      setFeedback(fields.orgName, document.getElementById('fb-org-name'), isValid, isValid ? '✓ Valid organization name' : 'Club/Organization name is required (min 3 chars).');
      return isValid;
    }

    function validatePassword() {
      if (!fields.password) return true;
      const val = fields.password.value;
      const fb = document.getElementById('fb-password');
      const bar = document.getElementById('strength-bar');

      if (!val) {
        bar.className = 'strength-bar';
        setFeedback(fields.password, fb, false, 'Password is required (min 6 characters).');
        return false;
      }

      if (val.length < 6) {
        bar.className = 'strength-bar weak';
        setFeedback(fields.password, fb, false, 'Weak: Password must be at least 6 characters.');
        return false;
      }

      // Strength evaluation
      const hasNumber = /\d/.test(val);
      const hasLetter = /[a-zA-Z]/.test(val);
      const hasSpecial = /[^a-zA-Z0-9]/.test(val);

      if (val.length >= 8 && hasNumber && hasLetter && (hasSpecial || val.length >= 10)) {
        bar.className = 'strength-bar strong';
        setFeedback(fields.password, fb, true, '✓ Strong password');
      } else {
        bar.className = 'strength-bar fair';
        setFeedback(fields.password, fb, true, '✓ Medium strength password');
      }

      // Re-validate confirm password if filled
      if (fields.confirmPassword && fields.confirmPassword.value) {
        validateConfirmPassword();
      }

      return true;
    }

    function validateConfirmPassword() {
      if (!fields.confirmPassword) return true;
      const val = fields.confirmPassword.value;
      const passVal = fields.password.value;
      const fb = document.getElementById('fb-confirm-password');

      if (!val) {
        setFeedback(fields.confirmPassword, fb, false, 'Please confirm your password.');
        return false;
      }

      const isMatch = (val === passVal && passVal.length >= 6);
      setFeedback(fields.confirmPassword, fb, isMatch, isMatch ? '✓ Passwords match' : 'Passwords do not match.');
      return isMatch;
    }

    function validateForm() {
      let ok = true;
      if (fields.firstName && fields.firstName.value) ok = validateFirstName() && ok;
      if (fields.lastName && fields.lastName.value) ok = validateLastName() && ok;
      if (fields.email && fields.email.value) ok = validateEmail() && ok;
      if (fields.phone && fields.phone.value) ok = validatePhone() && ok;
      if (selectedType === 'owner' && fields.orgName && fields.orgName.value) ok = validateOrgName() && ok;
      if (fields.password && fields.password.value) ok = validatePassword() && ok;
      if (fields.confirmPassword && fields.confirmPassword.value) ok = validateConfirmPassword() && ok;
      return ok;
    }

    function selectType(type) {
      selectedType = type;
      const banner = document.getElementById('role-indicator-banner');
      const ownerCard = document.getElementById('owner-redirect-card');
      const regForm = document.getElementById('register-form');
      const btnPlayer = document.getElementById('btn-type-player');
      const btnOwner = document.getElementById('btn-type-owner');

      if (type === 'owner') {
        btnPlayer.classList.remove('active');
        btnOwner.classList.add('active');
        banner.style.display = 'none';
        regForm.style.display = 'none';
        ownerCard.style.display = 'block';
      } else {
        btnOwner.classList.remove('active');
        btnPlayer.classList.add('active');
        banner.style.display = 'flex';
        regForm.style.display = 'block';
        ownerCard.style.display = 'none';
      }
      validateForm();
    }

    document.addEventListener('DOMContentLoaded', async () => {
      await NavbarComponent.render();

      fields = {
        firstName: document.getElementById('first_name'),
        lastName: document.getElementById('last_name'),
        email: document.getElementById('email'),
        phone: document.getElementById('phone'),
        orgName: document.getElementById('organization_name'),
        password: document.getElementById('password'),
        confirmPassword: document.getElementById('confirm_password')
      };

      const urlParams = new URLSearchParams(window.location.search);
      if (urlParams.get('type') === 'owner') {
        selectType('owner');
      } else {
        selectType('player');
      }

      document.querySelectorAll('.type-btn').forEach(btn => {
        btn.addEventListener('click', () => selectType(btn.dataset.type));
      });

      // Toggle Password Visibility Handlers
      document.querySelectorAll('.password-toggle-btn').forEach(btn => {
        btn.addEventListener('click', () => {
          const targetId = btn.dataset.target;
          const input = document.getElementById(targetId);
          const icon = btn.querySelector('i');

          if (input.type === 'password') {
            input.type = 'text';
            icon.className = 'bi bi-eye-fill';
          } else {
            input.type = 'password';
            icon.className = 'bi bi-eye-slash-fill';
          }
        });
      });

      fields.firstName.addEventListener('input', validateFirstName);
      fields.lastName.addEventListener('input', validateLastName);
      fields.email.addEventListener('input', validateEmail);
      fields.phone.addEventListener('input', validatePhone);
      if (fields.orgName) fields.orgName.addEventListener('input', validateOrgName);
      fields.password.addEventListener('input', validatePassword);
      fields.confirmPassword.addEventListener('input', validateConfirmPassword);

      document.getElementById('register-form').addEventListener('submit', async (e) => {
        e.preventDefault();

        const v1 = validateFirstName();
        const v2 = validateLastName();
        const v3 = validateEmail();
        const v4 = validatePhone();
        const v5 = validateOrgName();
        const v6 = validatePassword();
        const v7 = validateConfirmPassword();

        if (!v1 || !v2 || !v3 || !v4 || !v5 || !v6 || !v7) {
          Toast.error('Form Incomplete', 'Please check and correct the highlighted fields before submitting.');
          return;
        }

        const submitBtn = document.getElementById('submit-btn');
        const origText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = `<i class="bi bi-hourglass-split"></i> Creating Account...`;

        try {
          const res = await Api.post(`${APP_BASE}/api/auth/register.php`, {
            account_type: selectedType,
            first_name: fields.firstName.value.trim(),
            last_name: fields.lastName.value.trim(),
            email: fields.email.value.trim(),
            phone: fields.phone.value.trim(),
            organization_name: fields.orgName ? fields.orgName.value.trim() : '',
            password: fields.password.value
          });

          if (res.success) {
            Toast.success('Account Created', 'Registration successful! Redirecting...');
            setTimeout(() => {
              if (selectedType === 'owner') window.location.href = `${APP_BASE}/public/owner/dashboard.php`;
              else window.location.href = `${APP_BASE}/public/customer/dashboard.php`;
            }, 800);
          } else {
            Toast.error('Registration Failed', res.message || 'Could not complete registration.');
            submitBtn.disabled = false;
            submitBtn.innerHTML = origText;
          }
        } catch (err) {
          console.error(err);
          Toast.error('Server Error', 'An unexpected error occurred. Please try again.');
          submitBtn.disabled = false;
          submitBtn.innerHTML = origText;
        }
      });
    });
  </script>
</body>
</html>
