<?php require_once __DIR__ . '/../app/bootstrap.php'; ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Pikvero — Create Your Account</title>
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

  <div style="min-height:90vh; display:grid; place-items:center; padding:110px 16px 40px;">
    <div class="card-streetside" style="width:min(540px, 100%); padding:28px;">
      <div style="text-align:center; margin-bottom:20px;">
        <div class="brand-mark" style="width:44px; height:44px; font-size:1.3rem; margin:0 auto 10px;">P</div>
        <h2 style="font-size:1.7rem; font-weight:800; text-transform:uppercase; margin:0;">Create an Account</h2>
        <p style="font-size:0.85rem; color:#4a5c56;">Join Pikvero to book courts or manage your facility</p>
      </div>

      <form id="register-form" novalidate>

        <!-- GROUP 1: ACCOUNT ROLE & TYPE -->
        <div class="form-section">
          <div class="form-section-title">
            <i class="bi bi-shield-lock-fill"></i> SECTION 1: ACCOUNT TYPE &amp; ROLE
          </div>

          <div style="margin-bottom:12px;">
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px;">
              <button type="button" class="button lime type-btn" id="btn-type-player" data-type="player" style="justify-content:center; padding:10px; font-size:0.85rem;">
                <i class="bi bi-person-fill"></i> Player
              </button>
              <button type="button" class="button sand type-btn" id="btn-type-owner" data-type="owner" style="justify-content:center; padding:10px; font-size:0.85rem;">
                <i class="bi bi-building-fill"></i> Court Owner
              </button>
            </div>
          </div>

          <!-- Account Role Indicator Banner -->
          <div id="role-indicator-banner" class="card-streetside lime" style="padding:12px 14px; border-width:2px; box-shadow:2px 2px 0 var(--ink);">
            <div style="display:flex; align-items:center; gap:10px;">
              <i id="role-icon" class="bi bi-person-fill" style="font-size:1.4rem;"></i>
              <div>
                <span id="role-badge" class="badge-streetside dark" style="font-size:0.65rem;">PLAYER / CUSTOMER</span>
                <p id="role-description" style="margin:2px 0 0; font-size:0.8rem; font-weight:700; line-height:1.3;">
                  You are registering as a <strong>Player</strong> to browse courts and book time slots.
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- GROUP 2: PERSONAL & CONTACT INFORMATION -->
        <div class="form-section">
          <div class="form-section-title">
            <i class="bi bi-person-vcard-fill"></i> SECTION 2: PERSONAL &amp; CONTACT DETAILS
          </div>

          <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap:10px; margin-bottom:10px;">
            <div>
              <label class="mono" style="display:block; margin-bottom:4px;">FIRST NAME *</label>
              <input type="text" id="first_name" class="input-field" placeholder="Juan">
              <span id="fb-first-name" class="inline-feedback"></span>
            </div>
            <div>
              <label class="mono" style="display:block; margin-bottom:4px;">LAST NAME *</label>
              <input type="text" id="last_name" class="input-field" placeholder="Dela Cruz">
              <span id="fb-last-name" class="inline-feedback"></span>
            </div>
          </div>

          <div style="margin-bottom:10px;">
            <label class="mono" style="display:block; margin-bottom:4px;">EMAIL ADDRESS *</label>
            <input type="email" id="email" class="input-field" placeholder="juan@gmail.com">
            <span id="fb-email" class="inline-feedback"></span>
          </div>

          <div style="margin-bottom:10px;">
            <label class="mono" style="display:block; margin-bottom:4px;">PHONE NUMBER (11 DIGITS) *</label>
            <input type="text" id="phone" class="input-field" maxlength="11" placeholder="09171234567">
            <span id="fb-phone" class="inline-feedback"></span>
          </div>

          <div id="org-name-field" style="display:none;">
            <label class="mono" style="display:block; margin-bottom:4px;">ORGANIZATION / CLUB NAME *</label>
            <input type="text" id="organization_name" class="input-field" placeholder="e.g. Bohol Pickleball Club">
            <span id="fb-org-name" class="inline-feedback"></span>
          </div>
        </div>

        <!-- GROUP 3: SECURITY CREDENTIALS -->
        <div class="form-section">
          <div class="form-section-title">
            <i class="bi bi-key-fill"></i> SECTION 3: ACCOUNT SECURITY
          </div>

          <!-- Password Field with Toggle -->
          <div style="margin-bottom:12px;">
            <label class="mono" style="display:block; margin-bottom:4px;">PASSWORD *</label>
            <div class="password-input-wrap">
              <input type="password" id="password" class="input-field" placeholder="At least 6 characters" style="padding-right:40px;">
              <button type="button" class="password-toggle-btn" data-target="password" title="Toggle Password Visibility">
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

          <!-- Confirm Password Field with Toggle -->
          <div>
            <label class="mono" style="display:block; margin-bottom:4px;">CONFIRM PASSWORD *</label>
            <div class="password-input-wrap">
              <input type="password" id="confirm_password" class="input-field" placeholder="Re-enter your password" style="padding-right:40px;">
              <button type="button" class="password-toggle-btn" data-target="confirm_password" title="Toggle Password Visibility">
                <i class="bi bi-eye-slash-fill"></i>
              </button>
            </div>
            <span id="fb-confirm-password" class="inline-feedback"></span>
          </div>
        </div>

        <button type="submit" id="submit-btn" class="button coral" style="width:100%; padding:14px; font-size:0.95rem; margin-top:8px;">
          <i class="bi bi-person-plus-fill"></i> Complete Registration
        </button>
      </form>
    </div>
  </div>

  <script src="/pikvero/assets/js/core/toast.js"></script>
  <script src="/pikvero/assets/js/core/ajax.js"></script>
  <script src="/pikvero/assets/js/core/auth.js"></script>
  <script src="/pikvero/assets/js/components/navbar.js"></script>
  <script>
    let selectedType = 'player';
    let fields = {};

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
      const icon = document.getElementById('role-icon');
      const badge = document.getElementById('role-badge');
      const desc = document.getElementById('role-description');
      const orgField = document.getElementById('org-name-field');
      const btnPlayer = document.getElementById('btn-type-player');
      const btnOwner = document.getElementById('btn-type-owner');

      if (type === 'owner') {
        window.location.href = '/pikvero/public/owner-onboarding.php';
        return;
      } else {
        banner.className = 'card-streetside lime';
        icon.className = 'bi bi-person-fill';
        badge.className = 'badge-streetside dark';
        badge.innerText = 'PLAYER / CUSTOMER';
        desc.innerHTML = 'You are registering as a <strong>Player</strong> to browse courts and book time slots.';
        orgField.style.display = 'none';

        btnPlayer.className = 'button lime type-btn';
        btnOwner.className = 'button sand type-btn';
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
      fields.orgName.addEventListener('input', validateOrgName);
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
          Toast.error('Form Error', 'Please correct the inline validation errors before submitting.');
          return;
        }

        try {
          const res = await Api.post('/pikvero/api/auth/register.php', {
            account_type: selectedType,
            first_name: fields.firstName.value.trim(),
            last_name: fields.lastName.value.trim(),
            email: fields.email.value.trim(),
            phone: fields.phone.value.trim(),
            organization_name: fields.orgName.value.trim(),
            password: fields.password.value
          });

          if (res.success) {
            Toast.success('Account Created', 'Registration successful!');
            setTimeout(() => {
              if (selectedType === 'owner') window.location.href = '/pikvero/public/owner/dashboard.php';
              else window.location.href = '/pikvero/public/customer/dashboard.php';
            }, 800);
          }
        } catch (err) {
          console.error(err);
        }
      });
    });
  </script>
</body>
</html>
