<?php
$pageTitle  = 'Pikvero — Manage Facilities';
$headExtras = [];
require_once __DIR__ . '/../../includes/head.php';
?>
  <style>
    .facility-info-row {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 0.82rem;
      color: #3d504a;
      margin-bottom: 6px;
    }
    .facility-info-row i {
      color: var(--coral);
      font-size: 0.9rem;
    }
    .facility-badge-bar {
      display: flex;
      flex-wrap: wrap;
      gap: 6px;
      margin-bottom: 12px;
    }
    .form-group-wrap {
      margin-bottom: 14px;
    }
    .form-group-wrap label {
      display: block;
      margin-bottom: 4px;
      font-family: 'DM Mono', monospace;
      font-weight: 800;
      font-size: 0.78rem;
      text-transform: uppercase;
      color: var(--ink);
    }
    .form-input-ctrl {
      width: 100%;
      padding: 9px 12px;
      border: 2px solid var(--ink);
      border-radius: 8px;
      font-family: inherit;
      font-weight: 700;
      background: var(--white);
      outline: none;
      transition: border-color 0.2s ease, background 0.2s ease;
    }
    .form-input-ctrl.is-valid {
      border-color: var(--green) !important;
      background: #f0fdf4 !important;
    }
    .form-input-ctrl.is-invalid {
      border-color: var(--coral) !important;
      background: #fff5f3 !important;
    }
    .field-err-msg {
      display: block;
      font-size: 0.72rem;
      font-weight: 800;
      color: var(--coral);
      margin-top: 3px;
      min-height: 16px;
    }
  </style>
</head>
<body>

  <aside id="sidebar-container"></aside>
  <header id="navbar-container"></header>

  <main class="portal-main">
    <div>
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:12px;">
        <div>
          <div class="eyebrow">FACILITY MANAGEMENT</div>
          <h1 style="font-size: clamp(1.8rem, 4vw, 2.2rem); font-weight:800; text-transform:uppercase; margin:4px 0 0;">FACILITIES DIRECTORY</h1>
        </div>
        <button onclick="openCreateFacilityModal()" class="button coral" style="padding:9px 16px; font-size:0.82rem;">
          <i class="bi bi-plus-lg"></i> Add New Facility
        </button>
      </div>

      <div id="owner-facilities-list" style="display:grid; grid-template-columns:repeat(auto-fill, minmax(330px, 1fr)); gap:20px;">
        <!-- Loaded via JS -->
      </div>
    </div>

    <footer id="footer-container"></footer>
  </main>

  <!-- Create Facility Modal -->
  <div id="create-facility-modal" class="modal-overlay" style="display:none;">
    <div class="card-streetside modal-card-scrollable" style="width:min(500px, 100%); padding:26px; background:var(--cream);">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; border-bottom:2px solid var(--ink); padding-bottom:10px;">
        <h3 style="margin:0; font-size:1.25rem; text-transform:uppercase;">ADD NEW FACILITY</h3>
        <button onclick="document.getElementById('create-facility-modal').style.display='none'; document.getElementById('create-facility-modal').classList.remove('active');" class="button sand" style="padding:2px 8px; font-size:0.8rem;">✕</button>
      </div>

      <form id="create-facility-form" novalidate>
        <div class="form-group-wrap">
          <label for="fac_name">FACILITY NAME *</label>
          <input type="text" id="fac_name" placeholder="e.g. SmashZone Tagbilaran" class="form-input-ctrl" oninput="validateCreateFacilityField('name')">
          <span id="fac_name_err" class="field-err-msg"></span>
        </div>

        <div class="form-group-wrap">
          <label for="fac_address">STREET ADDRESS *</label>
          <input type="text" id="fac_address" placeholder="e.g. CPG North Ave, Cogon" class="form-input-ctrl" oninput="validateCreateFacilityField('address')">
          <span id="fac_address_err" class="field-err-msg"></span>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
          <div class="form-group-wrap">
            <label for="fac_city">CITY / MUNICIPALITY *</label>
            <input type="text" id="fac_city" placeholder="Tagbilaran City" class="form-input-ctrl" oninput="validateCreateFacilityField('city')">
            <span id="fac_city_err" class="field-err-msg"></span>
          </div>

          <div class="form-group-wrap">
            <label for="fac_phone">CONTACT PHONE</label>
            <input type="text" id="fac_phone" placeholder="09181234567" class="form-input-ctrl" oninput="validateCreateFacilityField('phone')">
            <span id="fac_phone_err" class="field-err-msg"></span>
          </div>
        </div>

        <div class="form-group-wrap">
          <label for="fac_email">CONTACT EMAIL</label>
          <input type="email" id="fac_email" placeholder="facility@smashzone.com" class="form-input-ctrl" oninput="validateCreateFacilityField('email')">
          <span id="fac_email_err" class="field-err-msg"></span>
        </div>

        <div class="form-group-wrap">
          <label for="fac_desc">DESCRIPTION / BIO</label>
          <textarea id="fac_desc" rows="3" placeholder="Tournament grade courts with night lighting & amenities..." class="form-input-ctrl"></textarea>
        </div>

        <div style="display:flex; justify-content:flex-end; gap:8px; margin-top:6px;">
          <button type="button" onclick="document.getElementById('create-facility-modal').style.display='none'; document.getElementById('create-facility-modal').classList.remove('active');" class="button sand" style="padding:8px 14px; font-size:0.8rem;">Cancel</button>
          <button type="submit" class="button lime" style="padding:9px 20px; font-size:0.82rem;"><i class="bi bi-floppy-fill"></i> Save Facility</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Edit Facility Modal -->
  <div id="edit-facility-modal" class="modal-overlay" style="display:none;">
    <div class="card-streetside modal-card-scrollable" style="width:min(520px, 100%); padding:26px; background:var(--cream);">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; border-bottom:2px solid var(--ink); padding-bottom:10px;">
        <h3 style="margin:0; font-size:1.2rem; text-transform:uppercase;">EDIT FACILITY DETAILS</h3>
        <button onclick="document.getElementById('edit-facility-modal').style.display='none'; document.getElementById('edit-facility-modal').classList.remove('active');" class="button sand" style="padding:2px 8px; font-size:0.8rem;">✕</button>
      </div>

      <form id="edit-facility-form" novalidate>
        <input type="hidden" id="edit_fac_id">

        <div class="form-group-wrap">
          <label for="edit_fac_name">FACILITY NAME *</label>
          <input type="text" id="edit_fac_name" class="form-input-ctrl" oninput="validateEditFacilityField('name')">
          <span id="edit_fac_name_err" class="field-err-msg"></span>
        </div>

        <div class="form-group-wrap">
          <label for="edit_fac_address">STREET ADDRESS *</label>
          <input type="text" id="edit_fac_address" class="form-input-ctrl" oninput="validateEditFacilityField('address')">
          <span id="edit_fac_address_err" class="field-err-msg"></span>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
          <div class="form-group-wrap">
            <label for="edit_fac_city">CITY / MUNICIPALITY *</label>
            <input type="text" id="edit_fac_city" class="form-input-ctrl" oninput="validateEditFacilityField('city')">
            <span id="edit_fac_city_err" class="field-err-msg"></span>
          </div>
          <div class="form-group-wrap">
            <label for="edit_fac_status">STATUS</label>
            <select id="edit_fac_status" class="form-input-ctrl" style="font-family:'DM Mono', monospace; font-weight:800;">
              <option value="active">ACTIVE</option>
              <option value="inactive">INACTIVE</option>
            </select>
          </div>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
          <div class="form-group-wrap">
            <label for="edit_fac_phone">PHONE NUMBER</label>
            <input type="text" id="edit_fac_phone" class="form-input-ctrl" oninput="validateEditFacilityField('phone')">
            <span id="edit_fac_phone_err" class="field-err-msg"></span>
          </div>
          <div class="form-group-wrap">
            <label for="edit_fac_email">EMAIL ADDRESS</label>
            <input type="email" id="edit_fac_email" class="form-input-ctrl" oninput="validateEditFacilityField('email')">
            <span id="edit_fac_email_err" class="field-err-msg"></span>
          </div>
        </div>

        <div class="form-group-wrap">
          <label for="edit_fac_desc">DESCRIPTION</label>
          <textarea id="edit_fac_desc" rows="3" class="form-input-ctrl"></textarea>
        </div>

        <div style="display:flex; justify-content:flex-end; gap:8px;">
          <button type="button" onclick="document.getElementById('edit-facility-modal').style.display='none'; document.getElementById('edit-facility-modal').classList.remove('active');" class="button sand" style="padding:8px 14px; font-size:0.8rem;">Cancel</button>
          <button type="submit" class="button lime" style="padding:8px 18px; font-size:0.8rem;"><i class="bi bi-floppy-fill"></i> Save Changes</button>
        </div>
      </form>
    </div>
  </div>

  <!-- View Facility Full Info Modal -->
  <div id="view-facility-modal" class="modal-overlay" style="display:none;">
    <div class="card-streetside modal-card-scrollable" style="width:min(600px, 100%); padding:28px; background:var(--cream);">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; border-bottom:2px solid var(--ink); padding-bottom:10px;">
        <h3 style="margin:0; font-size:1.25rem; text-transform:uppercase;" id="view-fac-title">FACILITY DETAILS</h3>
        <button onclick="document.getElementById('view-facility-modal').style.display='none'; document.getElementById('view-facility-modal').classList.remove('active');" class="button sand" style="padding:2px 8px; font-size:0.8rem;">✕</button>
      </div>

      <div id="view-fac-body" style="font-size:0.88rem;">
        <!-- Loaded via JS -->
      </div>

      <div style="margin-top:20px; display:flex; justify-content:flex-end; gap:10px;">
        <button type="button" onclick="document.getElementById('view-facility-modal').style.display='none'; document.getElementById('view-facility-modal').classList.remove('active');" class="button sand" style="padding:8px 18px; font-size:0.82rem;">Close</button>
      </div>
    </div>
  </div>

  <!-- ── Manage Facility Photos Modal (Up to 10 photos) ── -->
  <div id="facility-photos-modal" class="modal-overlay" style="display:none; align-items:center; justify-content:center;" onclick="if(event.target === this) closeFacilityPhotosModal();">
    <div class="card-streetside modal-card-scrollable" style="
        background: var(--white);
        border: 3px solid var(--ink);
        border-radius: 16px;
        padding: 24px;
        width: 100%;
        max-width: 680px;
        max-height: 90vh;
        overflow-y: auto;
        position: relative;
        box-shadow: 6px 6px 0 var(--ink);
      ">
      <!-- Header -->
      <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:18px; padding-bottom:12px; border-bottom:2px solid var(--ink);">
        <div>
          <h2 style="margin:0; font-size:1.1rem; font-weight:900; text-transform:uppercase; letter-spacing:0.04em;">
            <i class="bi bi-images" style="color:var(--coral); margin-right:6px;"></i>Facility Photos Carousel Manager
          </h2>
          <div id="fpm-facility-title" style="font-size:0.78rem; color:#4a5c56; font-weight:700; margin-top:2px;">Facility: Loading...</div>
        </div>
        <button onclick="closeFacilityPhotosModal()" style="
            background: none; border: 2px solid var(--ink); border-radius: 50%;
            width: 32px; height: 32px; font-size: 1rem; cursor: pointer;
            display:flex; align-items:center; justify-content:center; font-weight:900; line-height:1;
          ">&times;</button>
      </div>

      <input type="hidden" id="fpm_facility_id">

      <!-- Upload / Add Image Controls -->
      <div style="background:#f4f7f5; border:2px solid var(--ink); border-radius:12px; padding:16px; margin-bottom:20px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
          <strong style="font-size:0.85rem; text-transform:uppercase;"><i class="bi bi-cloud-upload-fill" style="color:var(--green);"></i> Add New Photo</strong>
          <span id="fpm-count-badge" class="badge-streetside lime" style="font-size:0.75rem;">0 / 10 Photos</span>
        </div>

        <!-- Tab Toggle: File Upload vs URL -->
        <div style="display:flex; gap:8px; margin-bottom:12px;">
          <button type="button" id="fpm-tab-file" onclick="toggleFpmAddTab('file')" class="button lime" style="padding:4px 12px; font-size:0.75rem;">File Upload</button>
          <button type="button" id="fpm-tab-url" onclick="toggleFpmAddTab('url')" class="button sand" style="padding:4px 12px; font-size:0.75rem;">Image URL</button>
        </div>

        <!-- File Upload Form -->
        <form id="fpm-file-form" onsubmit="handleFpmFileUpload(event)" style="display:block;">
          <div style="display:flex; gap:10px; align-items:center;">
            <input type="file" id="fpm_file_input" accept="image/jpeg,image/png,image/webp,image/avif" multiple class="form-input-ctrl" style="padding:6px; flex:1;">
            <button type="submit" class="button coral" style="padding:8px 16px; font-size:0.8rem; white-space:nowrap;">
              <i class="bi bi-upload"></i> Upload Selected
            </button>
          </div>
          <div style="font-size:0.68rem; color:#5a7060; margin-top:4px;">Allowed formats: JPG, PNG, WEBP, AVIF (Max 5MB each &bull; Select multiple files)</div>
        </form>

        <!-- Image URL Form -->
        <form id="fpm-url-form" onsubmit="handleFpmUrlSubmit(event)" style="display:none;">
          <div style="display:flex; gap:10px; align-items:center;">
            <input type="url" id="fpm_url_input" placeholder="https://example.com/facility-photo.jpg" class="form-input-ctrl" style="flex:1;">
            <button type="submit" class="button lime" style="padding:8px 16px; font-size:0.8rem; white-space:nowrap;">
              <i class="bi bi-plus-circle-fill"></i> Add URL
            </button>
          </div>
          <div style="font-size:0.68rem; color:#5a7060; margin-top:4px;">Paste any valid HTTPS image web address</div>
        </form>
      </div>

      <!-- 10 Slots Grid -->
      <div style="margin-bottom:12px; display:flex; justify-content:space-between; align-items:center;">
        <strong style="font-size:0.82rem; text-transform:uppercase;">Facility Carousel Photos (10 Max)</strong>
        <span style="font-size:0.72rem; color:#5a7060;">Photos display in the order added</span>
      </div>
      <div id="fpm-grid-container" style="display:grid; grid-template-columns: repeat(auto-fill, minmax(110px, 1fr)); gap:12px;">
        <!-- Dynamically rendered photo cards/placeholders -->
      </div>
    </div>
  </div>

  <!-- Subscription Plan Limit Reached Upgrade Modal -->
  <div id="upgrade-plan-modal" style="display:none; position:fixed; inset:0; z-index:9999; background:rgba(10,20,15,0.7); backdrop-filter:blur(6px); place-items:center; padding:16px;">
    <div class="card-streetside" style="width:min(520px, 100%); padding:28px; background:var(--cream); text-align:center; position:relative;">
      <div style="width:64px; height:64px; border-radius:50%; background:#fef2f2; border:3px solid var(--coral); display:flex; align-items:center; justify-content:center; margin:0 auto 16px; box-shadow:3px 3px 0 var(--ink);">
        <i class="bi bi-rocket-takeoff-fill" style="font-size:2rem; color:var(--coral);"></i>
      </div>

      <h3 style="margin:0 0 8px; font-size:1.4rem; text-transform:uppercase; font-weight:900; color:var(--ink);">
        SUBSCRIPTION PLAN LIMIT REACHED
      </h3>

      <div style="background:var(--white); border:2px solid var(--ink); border-radius:12px; padding:18px; margin-bottom:20px; text-align:left;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
          <span style="font-size:0.78rem; font-family:'DM Mono', monospace; font-weight:900; color:#5a7060; text-transform:uppercase;">CURRENT PLAN TIER:</span>
          <span id="upgrade-modal-plan-name" class="badge-streetside coral">STARTER PLAN</span>
        </div>
        <p id="upgrade-modal-msg" style="font-size:0.88rem; color:#2c3e38; margin:0; line-height:1.45;">
          You have reached the maximum number of facilities allowed by your current subscription plan.
        </p>
      </div>

      <div style="display:flex; gap:10px; justify-content:center;">
        <button type="button" onclick="closeUpgradePlanModal()" class="button sand" style="padding:10px 18px; font-size:0.84rem;">Cancel</button>
        <button type="button" onclick="redirectToMyPlanPage()" class="button coral" style="padding:10px 24px; font-size:0.84rem;">
          <i class="bi bi-award-fill"></i> Upgrade Plan Now &rarr;
        </button>
      </div>
    </div>
  </div>

  <?php require_once __DIR__ . '/../../includes/scripts.php'; ?>
  <script>
    let currentFacilitiesMap = {};

    document.addEventListener('DOMContentLoaded', async () => {
      await NavbarComponent.render('#navbar-container', true);
      const userCtx = await AuthHelper.checkSession();
      SidebarComponent.render('facilities', userCtx && userCtx.role === 'court_owner' ? 'owner' : 'admin');
      FooterComponent.render('#footer-container', true);

      loadFacilities();
      checkSubscriptionLimits();

      // Create Facility Submit Handler with Validation Guard
      document.getElementById('create-facility-form').addEventListener('submit', async (e) => {
        e.preventDefault();

        const isNameValid = validateCreateFacilityField('name');
        const isAddressValid = validateCreateFacilityField('address');
        const isCityValid = validateCreateFacilityField('city');
        const isPhoneValid = validateCreateFacilityField('phone');
        const isEmailValid = validateCreateFacilityField('email');

        if (!isNameValid || !isAddressValid || !isCityValid || !isPhoneValid || !isEmailValid) {
          Toast.error('Validation Error', 'Please check and resolve highlighted errors before submitting.');
          return;
        }

        try {
          const res = await Api.post('/pikvero/api/owner/facilities.php', {
            name: document.getElementById('fac_name').value.trim(),
            address: document.getElementById('fac_address').value.trim(),
            city: document.getElementById('fac_city').value.trim(),
            phone: document.getElementById('fac_phone').value.trim(),
            email: document.getElementById('fac_email').value.trim(),
            description: document.getElementById('fac_desc').value.trim()
          });

          if (res.success) {
            Toast.success('Created', 'Facility added successfully.');
            document.getElementById('create-facility-modal').style.display = 'none';
            resetCreateForm();
            loadFacilities();
            checkSubscriptionLimits();
          } else if (res && (res.code === 'PLAN_LIMIT_REACHED' || (res.message && res.message.includes('limit reached')))) {
            document.getElementById('create-facility-modal').style.display = 'none';
            openUpgradePlanModal(res.message);
          } else {
            Toast.error('Creation Failed', (res && res.message) ? res.message : 'Could not add facility.');
          }
        } catch (err) { console.error(err); }
      });

      // Edit Facility Submit Handler with Validation Guard
      document.getElementById('edit-facility-form').addEventListener('submit', async (e) => {
        e.preventDefault();

        const isNameValid = validateEditFacilityField('name');
        const isAddressValid = validateEditFacilityField('address');
        const isCityValid = validateEditFacilityField('city');
        const isPhoneValid = validateEditFacilityField('phone');
        const isEmailValid = validateEditFacilityField('email');

        if (!isNameValid || !isAddressValid || !isCityValid || !isPhoneValid || !isEmailValid) {
          Toast.error('Validation Error', 'Please check and resolve highlighted errors before saving.');
          return;
        }

        try {
          const res = await Api.post('/pikvero/api/owner/facilities/update.php', {
            facility_id: document.getElementById('edit_fac_id').value,
            name: document.getElementById('edit_fac_name').value.trim(),
            address: document.getElementById('edit_fac_address').value.trim(),
            city: document.getElementById('edit_fac_city').value.trim(),
            status: document.getElementById('edit_fac_status').value,
            phone: document.getElementById('edit_fac_phone').value.trim(),
            email: document.getElementById('edit_fac_email').value.trim(),
            description: document.getElementById('edit_fac_desc').value.trim()
          });

          if (res.success) {
            Toast.success('Facility Updated', res.message);
            document.getElementById('edit-facility-modal').style.display = 'none';
            loadFacilities();
          }
        } catch (err) { console.error(err); }
      });
    });

    // Helper functions for validating fields
    function setFieldState(inputEl, errEl, isValid, errorMsg = '') {
      if (isValid) {
        inputEl.classList.remove('is-invalid');
        inputEl.classList.add('is-valid');
        errEl.innerText = '';
      } else {
        inputEl.classList.remove('is-valid');
        inputEl.classList.add('is-invalid');
        errEl.innerText = errorMsg;
      }
      return isValid;
    }

    function clearFieldState(inputEl, errEl) {
      inputEl.classList.remove('is-valid', 'is-invalid');
      errEl.innerText = '';
    }

    function validateCreateFacilityField(field) {
      if (field === 'name') {
        const el = document.getElementById('fac_name');
        const err = document.getElementById('fac_name_err');
        const val = el.value.trim();
        if (!val) return setFieldState(el, err, false, 'Facility name is required.');
        if (val.length < 3) return setFieldState(el, err, false, 'Facility name must be at least 3 characters.');
        if (val.length > 100) return setFieldState(el, err, false, 'Facility name cannot exceed 100 characters.');
        return setFieldState(el, err, true);
      }

      if (field === 'address') {
        const el = document.getElementById('fac_address');
        const err = document.getElementById('fac_address_err');
        const val = el.value.trim();
        if (!val) return setFieldState(el, err, false, 'Street address is required.');
        if (val.length < 5) return setFieldState(el, err, false, 'Street address must be at least 5 characters.');
        return setFieldState(el, err, true);
      }

      if (field === 'city') {
        const el = document.getElementById('fac_city');
        const err = document.getElementById('fac_city_err');
        const val = el.value.trim();
        if (!val) return setFieldState(el, err, false, 'City / Municipality is required.');
        if (val.length < 2) return setFieldState(el, err, false, 'City must be at least 2 characters.');
        return setFieldState(el, err, true);
      }

      if (field === 'email') {
        const el = document.getElementById('fac_email');
        const err = document.getElementById('fac_email_err');
        const val = el.value.trim();
        if (!val) return setFieldState(el, err, true); // Optional
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(val)) return setFieldState(el, err, false, 'Please enter a valid email address (e.g. info@domain.com).');
        return setFieldState(el, err, true);
      }

      if (field === 'phone') {
        const el = document.getElementById('fac_phone');
        const err = document.getElementById('fac_phone_err');
        const val = el.value.trim();
        if (!val) return setFieldState(el, err, true); // Optional
        const phoneRegex = /^[0-9\s\+\-\(\)]{7,20}$/;
        if (!phoneRegex.test(val)) return setFieldState(el, err, false, 'Please enter a valid phone number (at least 7 digits).');
        return setFieldState(el, err, true);
      }
      return true;
    }

    function validateEditFacilityField(field) {
      if (field === 'name') {
        const el = document.getElementById('edit_fac_name');
        const err = document.getElementById('edit_fac_name_err');
        const val = el.value.trim();
        if (!val) return setFieldState(el, err, false, 'Facility name is required.');
        if (val.length < 3) return setFieldState(el, err, false, 'Facility name must be at least 3 characters.');
        if (val.length > 100) return setFieldState(el, err, false, 'Facility name cannot exceed 100 characters.');
        return setFieldState(el, err, true);
      }

      if (field === 'address') {
        const el = document.getElementById('edit_fac_address');
        const err = document.getElementById('edit_fac_address_err');
        const val = el.value.trim();
        if (!val) return setFieldState(el, err, false, 'Street address is required.');
        if (val.length < 5) return setFieldState(el, err, false, 'Street address must be at least 5 characters.');
        return setFieldState(el, err, true);
      }

      if (field === 'city') {
        const el = document.getElementById('edit_fac_city');
        const err = document.getElementById('edit_fac_city_err');
        const val = el.value.trim();
        if (!val) return setFieldState(el, err, false, 'City / Municipality is required.');
        if (val.length < 2) return setFieldState(el, err, false, 'City must be at least 2 characters.');
        return setFieldState(el, err, true);
      }

      if (field === 'email') {
        const el = document.getElementById('edit_fac_email');
        const err = document.getElementById('edit_fac_email_err');
        const val = el.value.trim();
        if (!val) return setFieldState(el, err, true); // Optional
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(val)) return setFieldState(el, err, false, 'Please enter a valid email address.');
        return setFieldState(el, err, true);
      }

      if (field === 'phone') {
        const el = document.getElementById('edit_fac_phone');
        const err = document.getElementById('edit_fac_phone_err');
        const val = el.value.trim();
        if (!val) return setFieldState(el, err, true); // Optional
        const phoneRegex = /^[0-9\s\+\-\(\)]{7,20}$/;
        if (!phoneRegex.test(val)) return setFieldState(el, err, false, 'Please enter a valid phone number.');
        return setFieldState(el, err, true);
      }
      return true;
    }

    let currentSubscriptionSummary = null;

    async function checkSubscriptionLimits() {
      try {
        const res = await Api.get('/pikvero/api/owner/subscription/index.php');
        if (res && res.success && res.data) {
          currentSubscriptionSummary = res.data;
        }
      } catch(err) { console.error(err); }
    }

    async function openCreateFacilityModal() {
      if (!currentSubscriptionSummary) {
        await checkSubscriptionLimits();
      }

      const sub = currentSubscriptionSummary ? currentSubscriptionSummary.subscription : null;
      const usage = currentSubscriptionSummary ? currentSubscriptionSummary.usage : null;
      
      const maxFac = sub ? parseInt(sub.max_facilities || 1) : 1;
      const currFac = (usage && usage.facilities !== undefined) ? parseInt(usage.facilities) : Object.keys(currentFacilitiesMap).length;

      if (currFac >= maxFac) {
        openUpgradePlanModal();
        return;
      }

      resetCreateForm();
      const modal = document.getElementById('create-facility-modal');
      if (modal) {
        modal.classList.add('active');
        modal.style.display = 'flex';
      }
    }

    function openUpgradePlanModal(customMsg = '') {
      UpgradePlanModal.open(customMsg);
    }

    function closeUpgradePlanModal() {
      UpgradePlanModal.close();
    }

    function redirectToMyPlanPage() {
      window.location.href = '/pikvero/public/owner/my-plan.php';
    }

    function resetCreateForm() {
      document.getElementById('create-facility-form').reset();
      ['fac_name', 'fac_address', 'fac_city', 'fac_phone', 'fac_email'].forEach(id => {
        clearFieldState(document.getElementById(id), document.getElementById(id + '_err'));
      });
    }

    function openEditFacilityModal(facilityId) {
      const f = currentFacilitiesMap[facilityId];
      if (!f) return;

      document.getElementById('edit_fac_id').value = f.id;
      document.getElementById('edit_fac_name').value = f.name || '';
      document.getElementById('edit_fac_address').value = f.address || '';
      document.getElementById('edit_fac_city').value = f.city || '';
      document.getElementById('edit_fac_status').value = f.status || 'active';
      document.getElementById('edit_fac_phone').value = f.phone || '';
      document.getElementById('edit_fac_email').value = f.email || '';
      document.getElementById('edit_fac_desc').value = f.description || '';

      ['edit_fac_name', 'edit_fac_address', 'edit_fac_city', 'edit_fac_phone', 'edit_fac_email'].forEach(id => {
        clearFieldState(document.getElementById(id), document.getElementById(id + '_err'));
      });

      const modal = document.getElementById('edit-facility-modal');
      if (modal) {
        modal.classList.add('active');
        modal.style.display = 'flex';
      }
    }

    async function openViewFacilityModal(facilityId) {
      const f = currentFacilitiesMap[facilityId];
      if (!f) return;

      document.getElementById('view-fac-title').innerText = f.name.toUpperCase();

      const priceRange = (f.min_price && f.max_price) ? `₱${parseFloat(f.min_price).toFixed(0)} - ₱${parseFloat(f.max_price).toFixed(0)} / hr` : 'Rates Not Configured';

      document.getElementById('view-fac-body').innerHTML = `
        <div style="display:flex; gap:10px; margin-bottom:16px; flex-wrap:wrap;">
          <span class="badge-streetside ${f.status === 'active' ? 'green' : 'coral'}">STATUS: ${f.status.toUpperCase()}</span>
          <span class="badge-streetside lime">${f.city}, ${f.province || 'Bohol'}</span>
          <span class="badge-streetside sky">${f.active_courts || 0} ACTIVE / ${f.total_courts || 0} TOTAL COURTS</span>
        </div>

        <div style="background:var(--white); border:2px solid var(--ink); border-radius:10px; padding:16px; margin-bottom:16px;">
          <div style="font-weight:800; font-size:0.8rem; font-family:'DM Mono', monospace; text-transform:uppercase; margin-bottom:8px; color:var(--green);">LOCATION &amp; CONTACT DETAILS</div>
          
          <div class="facility-info-row"><i class="bi bi-geo-alt-fill"></i> <strong>Address:</strong> ${f.address}, ${f.city}</div>
          <div class="facility-info-row"><i class="bi bi-telephone-fill"></i> <strong>Phone:</strong> ${f.phone || 'Not Provided'}</div>
          <div class="facility-info-row"><i class="bi bi-envelope-fill"></i> <strong>Email:</strong> ${f.email || 'Not Provided'}</div>
          <div class="facility-info-row"><i class="bi bi-building-fill"></i> <strong>Organization:</strong> ${f.organization_name || 'SmashZone'}</div>
        </div>

        <div style="background:var(--white); border:2px solid var(--ink); border-radius:10px; padding:16px; margin-bottom:16px;">
          <div style="font-weight:800; font-size:0.8rem; font-family:'DM Mono', monospace; text-transform:uppercase; margin-bottom:8px; color:var(--green);"><i class="bi bi-stars"></i> AMENITIES &amp; VENUE FEATURES</div>
          <div id="view-fac-amenities-container">
            <span style="font-size:0.82rem; color:#888;"><i class="bi bi-hourglass-split"></i> Loading amenities...</span>
          </div>
        </div>

        <div style="background:var(--white); border:2px solid var(--ink); border-radius:10px; padding:16px; margin-bottom:16px;">
          <div style="font-weight:800; font-size:0.8rem; font-family:'DM Mono', monospace; text-transform:uppercase; margin-bottom:8px; color:var(--green);">COURT PRICING &amp; RATES</div>
          <div style="font-size:1.1rem; font-weight:800; color:var(--green);">${priceRange}</div>
        </div>

        <div style="background:var(--white); border:2px solid var(--ink); border-radius:10px; padding:16px;">
          <div style="font-weight:800; font-size:0.8rem; font-family:'DM Mono', monospace; text-transform:uppercase; margin-bottom:8px; color:var(--green);">FACILITY DESCRIPTION</div>
          <p style="margin:0; line-height:1.5; color:#3b4e48;">${f.description || 'No facility description provided yet.'}</p>
        </div>
      `;

      const viewModal = document.getElementById('view-facility-modal');
      if (viewModal) {
        viewModal.classList.add('active');
        viewModal.style.display = 'flex';
      }

      // Load assigned amenities for this facility
      try {
        const amRes = await Api.get('/pikvero/api/admin/amenities.php', { action: 'get_facility_assignments', facility_id: facilityId });
        const container = document.getElementById('view-fac-amenities-container');
        if (container && amRes.success && amRes.data) {
          const assignedIds = (amRes.data.assigned_ids || []).map(id => parseInt(id, 10));
          const allAmenities = amRes.data.amenities || [];
          const assignedAmenities = allAmenities.filter(a => assignedIds.includes(parseInt(a.id, 10)));

          if (assignedAmenities.length > 0) {
            container.innerHTML = `
              <div style="display:flex; flex-wrap:wrap; gap:8px;">
                ${assignedAmenities.map(a => `
                  <span class="badge-streetside lime" style="display:inline-flex; align-items:center; gap:6px; font-size:0.78rem; padding:6px 10px;">
                    <i class="bi ${a.icon || 'bi-check-circle-fill'}"></i> ${a.name}
                  </span>
                `).join('')}
              </div>
            `;
          } else {
            container.innerHTML = `<span style="font-size:0.82rem; color:#888;">No amenities assigned to this facility yet.</span>`;
          }
        }
      } catch (err) { console.error(err); }
    }

    function selectFacilityAndManage(facilityId) {
      sessionStorage.setItem('selected_facility_id', facilityId);
      window.location.href = '/pikvero/public/admin/courts.php';
    }

    async function loadFacilities() {
      try {
        const res = await Api.get('/pikvero/api/owner/facilities.php');
        const container = document.getElementById('owner-facilities-list');

        if (res.success && res.data.length > 0) {
          currentFacilitiesMap = {};
          res.data.forEach(f => { currentFacilitiesMap[f.id] = f; });

          container.innerHTML = res.data.map(f => {
            const priceText = (f.min_price && f.max_price) ? `₱${parseFloat(f.min_price).toFixed(0)} - ₱${parseFloat(f.max_price).toFixed(0)} / hr` : 'Rates pending';

            return `
              <div class="card-streetside" style="display:flex; flex-direction:column; justify-content:space-between;">
                <div>
                  ${renderFacilityCarouselHtml(f)}
                  <div class="facility-badge-bar">
                    <span class="badge-streetside ${f.status === 'active' ? 'green' : 'coral'}">${f.status.toUpperCase()}</span>
                    <span class="badge-streetside lime">${f.city}</span>
                    <span class="badge-streetside sky" style="margin-left:auto;"><i class="bi bi-layers-fill"></i> ${f.total_courts} Courts</span>
                  </div>

                  <h3 style="font-size:1.25rem; font-weight:800; text-transform:uppercase; margin:0 0 6px;">${f.name}</h3>

                  <div class="facility-info-row">
                    <i class="bi bi-geo-alt-fill"></i>
                    <span style="font-weight:700;">${f.address}</span>
                  </div>

                  <div class="facility-info-row">
                    <i class="bi bi-telephone-fill"></i>
                    <span>${f.phone || 'Phone not set'}</span>
                  </div>

                  <div class="facility-info-row">
                    <i class="bi bi-envelope-fill"></i>
                    <span>${f.email || 'Email not set'}</span>
                  </div>

                  <div class="facility-info-row" style="margin-top:8px; padding-top:8px; border-top:1px dashed var(--line);">
                    <i class="bi bi-tag-fill" style="color:var(--green);"></i>
                    <span style="font-weight:800; color:var(--green);">${priceText}</span>
                  </div>

                  ${f.description ? `
                    <div style="font-size:0.8rem; color:#4a5c56; margin-top:8px; line-clamp:2; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;">
                      ${f.description}
                    </div>
                  ` : ''}
                </div>

                <div style="display:flex; gap:6px; margin-top:16px; padding-top:12px; border-top:1px solid var(--line);">
                  <button onclick="openViewFacilityModal(${f.id})" class="button sky" style="padding:6px 10px; font-size:0.75rem;" title="View Full Info">
                    <i class="bi bi-info-circle-fill"></i>
                  </button>
                  <button onclick="openEditFacilityModal(${f.id})" class="button sand" style="flex:1; padding:6px 10px; font-size:0.75rem;">
                    <i class="bi bi-pencil-fill"></i> Edit
                  </button>
                  <button onclick="selectFacilityAndManage(${f.id})" class="button coral" style="flex:2; padding:6px 10px; font-size:0.75rem;">
                    <i class="bi bi-layers-fill"></i> Courts &rarr;
                  </button>
                </div>
              </div>
            `;
          }).join('');
        } else {
          container.innerHTML = `
            <div class="card-streetside sand" style="grid-column: 1 / -1; max-width: 520px; margin: 40px auto; text-align: center; padding: 36px 24px;">
              <div class="brand-mark" style="width: 56px; height: 56px; font-size: 1.8rem; margin: 0 auto 16px; background: var(--lime);">
                <i class="bi bi-building-add"></i>
              </div>
              <h3 style="font-size: 1.35rem; font-weight: 800; text-transform: uppercase; margin: 0 0 8px;">NO FACILITIES REGISTERED YET</h3>
              <p style="font-size: 0.88rem; color: #3b4e48; margin-bottom: 20px; line-height: 1.4;">
                You haven't listed any pickleball facilities yet. Add your first facility to start setting up courts, schedules, and accepting player reservations.
              </p>
              <button onclick="openCreateFacilityModal()" class="button lime" style="padding: 10px 20px; font-size: 0.88rem;">
                <i class="bi bi-plus-lg"></i> Add Your First Facility
              </button>
            </div>
          `;
        }
      } catch (e) { console.error(e); }
    }

    // ── Facility Images Carousel & Photo Manager Modal (Up to 10 photos) ──────────

    function renderFacilityCarouselHtml(facility) {
      const images = facility.images || [];
      const facilityId = facility.id;
      const count = images.length;

      if (count === 0) {
        return `
          <div class="facility-carousel-container" style="position:relative; width:100%; height:180px; border-radius:12px; overflow:hidden; margin-bottom:12px; background:linear-gradient(135deg, #1b2e2b 0%, #2d4f48 100%); border:2px solid var(--ink); display:flex; flex-direction:column; align-items:center; justify-content:center; color:var(--white); text-align:center; padding:16px;">
            <i class="bi bi-images" style="font-size:2.2rem; color:var(--lime); margin-bottom:6px;"></i>
            <span style="font-weight:800; font-size:0.85rem; text-transform:uppercase; letter-spacing:0.04em;">NO PHOTOS ADDED YET</span>
            <span style="font-size:0.72rem; color:#b0c4de; margin-bottom:10px;">Add up to 10 photos for this facility</span>
            <button onclick="openFacilityPhotosModal(${facilityId})" class="button lime" style="padding:4px 12px; font-size:0.72rem; border-radius:8px;">
              <i class="bi bi-camera-fill"></i> Upload Photos
            </button>
          </div>
        `;
      }

      const slidesHtml = images.map((img, idx) => `
        <div class="carousel-slide" style="flex:0 0 100%; width:100%; height:180px; position:relative; background:#111;">
          <img src="${img.image_path}" alt="${facility.name} Photo ${idx + 1}" style="width:100%; height:100%; object-fit:cover;" onerror="this.onerror=null; this.src='/pikvero/assets/images/logo.png';">
        </div>
      `).join('');

      const dotsHtml = images.map((_, idx) => `
        <span id="fdot-${facilityId}-${idx}" class="carousel-dot ${idx === 0 ? 'active' : ''}" onclick="setFacilityCarouselSlide(${facilityId}, ${idx})" style="width:7px; height:7px; border-radius:50%; background:${idx === 0 ? 'var(--lime)' : 'rgba(255,255,255,0.5)'}; cursor:pointer; transition:all 0.2s ease;"></span>
      `).join('');

      return `
        <div class="facility-carousel-wrapper" id="fcarousel-wrapper-${facilityId}" data-index="0" data-total="${count}" style="position:relative; width:100%; height:180px; border-radius:12px; overflow:hidden; margin-bottom:12px; border:2px solid var(--ink); box-shadow:2px 2px 0 var(--ink);">
          <div class="facility-carousel-track" id="fcarousel-track-${facilityId}" style="display:flex; width:100%; height:100%; transition:transform 0.35s cubic-bezier(0.25, 1, 0.5, 1);">
            ${slidesHtml}
          </div>

          <!-- Counter Overlay Badge -->
          <div style="position:absolute; top:8px; right:8px; background:rgba(0,0,0,0.75); backdrop-filter:blur(4px); color:var(--white); font-size:0.68rem; font-weight:800; padding:3px 8px; border-radius:20px; border:1px solid rgba(255,255,255,0.2);">
            <i class="bi bi-images" style="color:var(--lime);"></i> <span id="fcarousel-counter-${facilityId}">1/${count}</span> (Max 10)
          </div>

          <!-- Manage Photos Action Overlay -->
          <button onclick="openFacilityPhotosModal(${facilityId})" class="button sand" style="position:absolute; top:8px; left:8px; padding:3px 8px; font-size:0.68rem; border-radius:8px; opacity:0.92; background:rgba(255,255,255,0.9);">
            <i class="bi bi-camera-fill"></i> Manage (${count}/10)
          </button>

          <!-- Navigation Arrows (only if > 1 photo) -->
          ${count > 1 ? `
            <button onclick="prevFacilityCarouselSlide(${facilityId})" style="position:absolute; left:6px; top:50%; transform:translateY(-50%); width:28px; height:28px; border-radius:50%; background:rgba(0,0,0,0.65); color:white; border:1px solid rgba(255,255,255,0.3); display:flex; align-items:center; justify-content:center; cursor:pointer; font-size:0.8rem; z-index:2;">
              <i class="bi bi-chevron-left"></i>
            </button>
            <button onclick="nextFacilityCarouselSlide(${facilityId})" style="position:absolute; right:6px; top:50%; transform:translateY(-50%); width:28px; height:28px; border-radius:50%; background:rgba(0,0,0,0.65); color:white; border:1px solid rgba(255,255,255,0.3); display:flex; align-items:center; justify-content:center; cursor:pointer; font-size:0.8rem; z-index:2;">
              <i class="bi bi-chevron-right"></i>
            </button>
            <!-- Dots -->
            <div style="position:absolute; bottom:8px; left:50%; transform:translateX(-50%); display:flex; gap:4px; z-index:2; background:rgba(0,0,0,0.5); padding:3px 8px; border-radius:12px;">
              ${dotsHtml}
            </div>
          ` : ''}
        </div>
      `;
    }

    function setFacilityCarouselSlide(facilityId, index) {
      const wrapper = document.getElementById(`fcarousel-wrapper-${facilityId}`);
      if (!wrapper) return;
      const total = parseInt(wrapper.dataset.total) || 1;
      if (index < 0) index = total - 1;
      if (index >= total) index = 0;
      
      wrapper.dataset.index = index;
      const track = document.getElementById(`fcarousel-track-${facilityId}`);
      if (track) {
        track.style.transform = `translateX(-${index * 100}%)`;
      }
      
      const counter = document.getElementById(`fcarousel-counter-${facilityId}`);
      if (counter) {
        counter.textContent = `${index + 1}/${total}`;
      }

      for (let i = 0; i < total; i++) {
        const dot = document.getElementById(`fdot-${facilityId}-${i}`);
        if (dot) {
          dot.style.background = (i === index) ? 'var(--lime)' : 'rgba(255,255,255,0.5)';
        }
      }
    }

    function prevFacilityCarouselSlide(facilityId) {
      const wrapper = document.getElementById(`fcarousel-wrapper-${facilityId}`);
      if (!wrapper) return;
      let idx = parseInt(wrapper.dataset.index) || 0;
      setFacilityCarouselSlide(facilityId, idx - 1);
    }

    function nextFacilityCarouselSlide(facilityId) {
      const wrapper = document.getElementById(`fcarousel-wrapper-${facilityId}`);
      if (!wrapper) return;
      let idx = parseInt(wrapper.dataset.index) || 0;
      setFacilityCarouselSlide(facilityId, idx + 1);
    }

    // Photo Manager Modal Functions
    let activeFpmFacilityId = null;

    async function openFacilityPhotosModal(facilityId) {
      activeFpmFacilityId = facilityId;
      if (!currentFacilitiesMap[facilityId]) {
        try {
          const res = await Api.get('/pikvero/api/owner/facilities.php', {}, { showToast: false });
          if (res && res.success && res.data) {
            res.data.forEach(f => { currentFacilitiesMap[f.id] = f; });
          }
        } catch(e) { console.error(e); }
      }
      const facility = currentFacilitiesMap[facilityId];
      const facIdEl = document.getElementById('fpm_facility_id');
      if (facIdEl) facIdEl.value = facilityId;

      const titleEl = document.getElementById('fpm-facility-title');
      if (titleEl) {
        titleEl.textContent = `Facility: ${facility ? facility.name : '#' + facilityId} (${facility ? (facility.city || '') : ''})`;
      }

      toggleFpmAddTab('file');
      renderFpmGrid(facilityId);

      const modal = document.getElementById('facility-photos-modal');
      if (modal) {
        modal.classList.add('active');
        modal.style.display = 'flex';
      }
      document.body.style.overflow = 'hidden';
    }

    function closeFacilityPhotosModal() {
      const modal = document.getElementById('facility-photos-modal');
      if (modal) {
        modal.classList.remove('active');
        modal.style.display = 'none';
      }
      document.body.style.overflow = '';
      loadFacilities(); // reload to reflect carousel updates
    }

    function toggleFpmAddTab(tab) {
      const fileForm = document.getElementById('fpm-file-form');
      const urlForm  = document.getElementById('fpm-url-form');
      const btnFile  = document.getElementById('fpm-tab-file');
      const btnUrl   = document.getElementById('fpm-tab-url');

      if (tab === 'file') {
        if (fileForm) fileForm.style.display = 'block';
        if (urlForm)  urlForm.style.display  = 'none';
        if (btnFile)  btnFile.className = 'button lime';
        if (btnUrl)   btnUrl.className  = 'button sand';
      } else {
        if (fileForm) fileForm.style.display = 'none';
        if (urlForm)  urlForm.style.display  = 'block';
        if (btnFile)  btnFile.className = 'button sand';
        if (btnUrl)   btnUrl.className  = 'button lime';
      }
    }

    function renderFpmGrid(facilityId) {
      const facility = currentFacilitiesMap[facilityId];
      const images = (facility && facility.images) ? facility.images : [];
      const count = images.length;

      const badge = document.getElementById('fpm-count-badge');
      if (badge) {
        badge.textContent = `${count} / 10 Photos`;
        badge.className = `badge-streetside ${count >= 10 ? 'coral' : 'lime'}`;
      }

      const container = document.getElementById('fpm-grid-container');
      if (!container) return;
      let html = '';

      // Render 10 slots
      for (let i = 0; i < 10; i++) {
        if (i < count) {
          const img = images[i];
          html += `
            <div style="position:relative; width:100%; height:110px; border-radius:10px; overflow:hidden; border:2px solid var(--ink); background:#111;">
              <img src="${img.image_path}" style="width:100%; height:100%; object-fit:cover;" onerror="this.onerror=null; this.src='/pikvero/assets/images/logo.png';">
              <span style="position:absolute; top:4px; left:4px; background:rgba(0,0,0,0.75); color:white; font-size:0.62rem; font-weight:900; padding:2px 6px; border-radius:10px;">
                #${i + 1}
              </span>
              <button onclick="deleteFpmImage(${img.id})" style="position:absolute; top:4px; right:4px; background:rgba(224,92,58,0.9); color:white; border:1px solid var(--ink); border-radius:6px; width:24px; height:24px; display:flex; align-items:center; justify-content:center; cursor:pointer; font-size:0.75rem;" title="Delete Photo">
                <i class="bi bi-trash-fill"></i>
              </button>
            </div>
          `;
        } else {
          html += `
            <div style="position:relative; width:100%; height:110px; border-radius:10px; border:2px dashed #b0c4de; background:#f8faf9; display:flex; flex-direction:column; align-items:center; justify-content:center; color:#7a8c85; font-size:0.72rem; text-align:center; padding:6px;">
              <i class="bi bi-image-fill" style="font-size:1.4rem; color:#b0c4de; margin-bottom:4px;"></i>
              <span style="font-weight:700;">Slot #${i + 1}</span>
              <span style="font-size:0.62rem; color:#aaa;">(Empty)</span>
            </div>
          `;
        }
      }
      container.innerHTML = html;
    }

    async function handleFpmFileUpload(e) {
      e.preventDefault();
      const input = document.getElementById('fpm_file_input');
      if (!input || !input.files || input.files.length === 0) {
        Toast.error('No Files Selected', 'Please select one or more image files to upload.');
        return;
      }

      const facilityId = document.getElementById('fpm_facility_id').value;
      const files = Array.from(input.files);
      const totalFiles = files.length;
      let successCount = 0;
      let failCount = 0;
      let lastErrMsg = '';

      const submitBtn = e.target.querySelector('button[type="submit"]');
      if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = `<i class="bi bi-arrow-repeat spin"></i> Uploading ${totalFiles} file(s)...`;
      }

      for (let i = 0; i < files.length; i++) {
        const file = files[i];
        const formData = new FormData();
        formData.append('facility_id', facilityId);
        formData.append('image', file);

        try {
          const res = await Api.post('/pikvero/api/owner/facilities/images-add.php', formData, { showToast: false });
          if (res && res.success) {
            successCount++;
          } else {
            failCount++;
            if (res && res.message) lastErrMsg = res.message;
          }
        } catch(err) {
          console.error('File Upload Error:', err);
          failCount++;
          if (err && err.message) lastErrMsg = err.message;
        }
      }

      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.innerHTML = `<i class="bi bi-upload"></i> Upload Selected`;
      }

      input.value = '';

      if (successCount > 0) {
        Toast.success('Photos Uploaded', `${successCount} photo(s) successfully added to facility carousel.` + (failCount > 0 ? ` (${failCount} failed)` : ''));
        const facilitiesRes = await Api.get('/pikvero/api/owner/facilities.php', {}, { showToast: false });
        if (facilitiesRes && facilitiesRes.success && facilitiesRes.data) {
          facilitiesRes.data.forEach(f => { currentFacilitiesMap[f.id] = f; });
        }
        renderFpmGrid(facilityId);
      } else {
        Toast.error('Upload Failed', lastErrMsg || 'Could not upload selected photos.');
      }
    }

    async function handleFpmUrlSubmit(e) {
      e.preventDefault();
      const input = document.getElementById('fpm_url_input');
      const url = input ? input.value.trim() : '';
      if (!url) {
        Toast.error('Empty URL', 'Please enter a valid image web URL.');
        return;
      }

      const facilityId = document.getElementById('fpm_facility_id').value;
      const submitBtn = e.target.querySelector('button[type="submit"]');
      if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = `<i class="bi bi-arrow-repeat spin"></i> Adding...`;
      }

      try {
        const res = await Api.post('/pikvero/api/owner/facilities/images-add.php', {
          facility_id: facilityId,
          image_url: url
        }, { showToast: false });

        if (res && res.success) {
          Toast.success('Photo Added', 'Image URL added to facility carousel.');
          if (input) input.value = '';
          const facilitiesRes = await Api.get('/pikvero/api/owner/facilities.php', {}, { showToast: false });
          if (facilitiesRes && facilitiesRes.success && facilitiesRes.data) {
            facilitiesRes.data.forEach(f => { currentFacilitiesMap[f.id] = f; });
          }
          renderFpmGrid(facilityId);
        } else {
          Toast.error('Failed', (res && res.message) ? res.message : 'Could not add image URL.');
        }
      } catch(err) {
        Toast.error('Error', err.message || 'An unexpected error occurred.');
      } finally {
        if (submitBtn) {
          submitBtn.disabled = false;
          submitBtn.innerHTML = `<i class="bi bi-plus-circle-fill"></i> Add URL`;
        }
      }
    }

    async function deleteFpmImage(imageId) {
      if (!confirm('Delete this photo from facility carousel?')) return;
      const facilityId = document.getElementById('fpm_facility_id').value;
      try {
        const res = await Api.post('/pikvero/api/owner/facilities/images-delete.php', { image_id: imageId }, { showToast: false });
        if (res && res.success) {
          Toast.success('Photo Deleted', 'Photo removed from facility carousel.');
          const facilitiesRes = await Api.get('/pikvero/api/owner/facilities.php', {}, { showToast: false });
          if (facilitiesRes && facilitiesRes.success && facilitiesRes.data) {
            facilitiesRes.data.forEach(f => { currentFacilitiesMap[f.id] = f; });
          }
          renderFpmGrid(facilityId);
        } else {
          Toast.error('Failed', (res && res.message) ? res.message : 'Could not delete photo.');
        }
      } catch(err) {
        Toast.error('Error', err.message || 'An unexpected error occurred.');
      }
    }
  </script>
</body>
</html>
