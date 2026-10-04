<?php
$pageTitle  = 'Pikvero — Court Owner Organization Settings';
$headExtras = ['jquery'];
require_once __DIR__ . '/../../includes/head.php';
?>
  <style>
    .form-group-streetside {
      margin-bottom: 18px;
    }
    .form-group-streetside label {
      display: block;
      font-weight: 800;
      font-size: 0.82rem;
      margin-bottom: 6px;
      text-transform: uppercase;
      font-family: 'DM Mono', monospace;
      color: var(--ink);
    }
    .form-group-streetside input[type="text"],
    .form-group-streetside input[type="email"],
    .form-group-streetside textarea {
      width: 100%;
      padding: 10px 14px;
      border: 2px solid var(--ink);
      border-radius: 8px;
      font-family: inherit;
      font-weight: 700;
      font-size: 0.88rem;
      background: var(--white);
    }
    .logo-preview-box {
      width: 140px;
      height: 140px;
      border: 2px dashed var(--ink);
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      background: #f8faf9;
      overflow: hidden;
    }
    .logo-preview-box img {
      max-width: 100%;
      max-height: 100%;
      object-fit: contain;
    }
  </style>
</head>
<body>

  <div id="sidebar-container"></div>
  <div id="navbar-container"></div>

  <main class="portal-main">
    <div id="settings-page-content">
      <div style="margin-bottom:24px; display:flex; justify-content:space-between; align-items:flex-end; flex-wrap:wrap; gap:12px;">
        <div>
          <div class="eyebrow">COURT OWNER MANAGEMENT</div>
          <h1 style="font-size: clamp(1.8rem, 4vw, 2.2rem); font-weight:800; text-transform:uppercase; margin:4px 0 0;">ORGANIZATION BRANDING &amp; PROFILE</h1>
        </div>
        <div>
          <button type="button" onclick="submitSaveOwnerSettings()" class="button lime" style="padding:10px 22px; font-size:0.88rem;">
            <i class="bi bi-floppy-fill"></i> Save Branding Settings
          </button>
        </div>
      </div>

      <div class="card-streetside" style="padding:32px; background:var(--white);">
        <form id="owner-settings-form" onsubmit="event.preventDefault(); submitSaveOwnerSettings();">
          
          <h3 style="font-size:1.1rem; font-weight:800; text-transform:uppercase; margin-bottom:18px; border-bottom:1px solid var(--line); padding-bottom:8px;">
            ORGANIZATION LOGO &amp; APP DISPLAY TITLE
          </h3>

          <div style="display:flex; gap:28px; flex-wrap:wrap; margin-bottom:24px; align-items:flex-start;">
            <div>
              <label style="display:block; font-weight:800; font-size:0.82rem; margin-bottom:6px; font-family:'DM Mono', monospace;">ORGANIZATION LOGO</label>
              <div class="logo-preview-box" id="logo-preview-container">
                <img id="logo-preview-img" src="/pikvero/assets/images/logo.png" alt="Organization Logo" onerror="this.src='/pikvero/assets/images/logo-placeholder.png'">
              </div>
              <input type="file" id="logo-file-input" accept="image/*" style="display:none;" onchange="handleOwnerLogoFileSelect(this)">
              <button type="button" onclick="document.getElementById('logo-file-input').click()" class="button coral" style="width:100%; margin-top:12px; padding:8px 12px; font-size:0.78rem;">
                <i class="bi bi-upload"></i> Upload Logo File
              </button>
            </div>

            <div style="flex:1; min-width:280px;">
              <div class="form-group-streetside">
                <label for="set-logo_url">ORGANIZATION LOGO URL</label>
                <input type="text" id="set-logo_url" name="logo_url" placeholder="/pikvero/assets/images/logo.png" oninput="updateLogoPreview(this.value)">
                <div style="font-size:0.72rem; color:#4a5c56; margin-top:4px;">Upload an image file using the button or enter a relative/external HTTPS logo image URL.</div>
              </div>

              <div class="form-group-streetside">
                <label for="set-app_title">CUSTOM APP DISPLAY TITLE</label>
                <input type="text" id="set-app_title" name="app_title" placeholder="e.g. SmashZone Pickleball Center">
                <div style="font-size:0.72rem; color:#4a5c56; margin-top:4px;">This title and logo will be displayed on your sidebar, navbar, and booking pages when logged in!</div>
              </div>
            </div>
          </div>

          <h3 style="font-size:1.1rem; font-weight:800; text-transform:uppercase; margin-bottom:18px; border-bottom:1px solid var(--line); padding-bottom:8px;">
            BUSINESS DETAILS &amp; CONTACT INFORMATION
          </h3>

          <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap:16px;">
            <div class="form-group-streetside">
              <label for="set-name">OFFICIAL REGISTERED ORGANIZATION NAME *</label>
              <input type="text" id="set-name" name="name" required placeholder="SmashZone Pickleball Center Inc.">
            </div>

            <div class="form-group-streetside">
              <label for="set-tax_id">TAX IDENTIFICATION NUMBER (TIN / TAX ID)</label>
              <input type="text" id="set-tax_id" name="tax_id" placeholder="123-456-789-000">
            </div>

            <div class="form-group-streetside">
              <label for="set-phone">CONTACT PHONE NUMBER</label>
              <input type="text" id="set-phone" name="phone" placeholder="09181234567">
            </div>

            <div class="form-group-streetside">
              <label for="set-email">CONTACT EMAIL ADDRESS</label>
              <input type="email" id="set-email" name="email" placeholder="owner@smashzone.com">
            </div>
          </div>

          <div class="form-group-streetside">
            <label for="set-description">ORGANIZATION / FACILITY DESCRIPTION</label>
            <textarea id="set-description" name="description" rows="4" placeholder="Describe your pickleball organization, court facilities, amenities, and operating mission..."></textarea>
          </div>

          <div style="margin-top:20px; display:flex; justify-content:flex-end;">
            <button type="button" onclick="submitSaveOwnerSettings()" class="button lime" style="padding:12px 26px; font-size:0.9rem;">
              <i class="bi bi-floppy-fill"></i> Save Branding Settings
            </button>
          </div>

        </form>
      </div>
    </div>

    <div id="footer-container"></div>
  </main>

  <?php require_once __DIR__ . '/../../includes/scripts.php'; ?>
  <script>
    document.addEventListener('DOMContentLoaded', async () => {
      const userCtx = await AuthHelper.checkSession();
      await NavbarComponent.render('#navbar-container', true);
      SidebarComponent.render('settings', 'owner');
      FooterComponent.render('#footer-container', true);

      loadOwnerSettings();
    });

    function updateLogoPreview(url) {
      const img = document.getElementById('logo-preview-img');
      if (img) {
        img.src = url || '/pikvero/assets/images/logo-placeholder.png';
      }
    }

    async function loadOwnerSettings() {
      try {
        const res = await Api.get('/pikvero/api/owner/settings.php');
        if (res.success && res.data) {
          const org = res.data;
          document.getElementById('set-name').value = org.name || '';
          document.getElementById('set-app_title').value = org.app_title || '';
          document.getElementById('set-logo_url').value = org.logo_url || '';
          document.getElementById('set-tax_id').value = org.tax_id || '';
          document.getElementById('set-phone').value = org.phone || '';
          document.getElementById('set-email').value = org.email || '';
          document.getElementById('set-description').value = org.description || '';

          if (org.logo_url) {
            updateLogoPreview(org.logo_url);
          }
        }
      } catch (err) {
        console.error(err);
      }
    }

    async function handleOwnerLogoFileSelect(fileInput) {
      if (!fileInput.files || fileInput.files.length === 0) return;

      const file = fileInput.files[0];
      const formData = new FormData();
      formData.append('logo', file);

      // Instant client-side preview
      const reader = new FileReader();
      reader.onload = function(e) {
        updateLogoPreview(e.target.result);
      };
      reader.readAsDataURL(file);

      Toast.info('Uploading Logo', 'Uploading organization logo image...');

      try {
        const response = await fetch('/pikvero/api/owner/settings/upload-logo.php', {
          method: 'POST',
          body: formData
        });
        const res = await response.json();

        if (res.success) {
          Toast.success('Logo Uploaded', res.message);
          const logoUrl = res.data.logo_url;
          document.getElementById('set-logo_url').value = logoUrl;
          updateLogoPreview(logoUrl);

          // Refresh session to update sidebar logo instantly!
          await AuthHelper.checkSession();
          SidebarComponent.render('settings', 'owner');
        } else {
          Toast.error('Upload Failed', res.message || 'Failed to upload logo image.');
        }
      } catch (err) {
        console.error(err);
        Toast.error('Upload Failed', 'An error occurred while uploading the logo file.');
      }
    }

    async function submitSaveOwnerSettings() {
      const payload = {
        name: document.getElementById('set-name').value.trim(),
        app_title: document.getElementById('set-app_title').value.trim(),
        logo_url: document.getElementById('set-logo_url').value.trim(),
        tax_id: document.getElementById('set-tax_id').value.trim(),
        phone: document.getElementById('set-phone').value.trim(),
        email: document.getElementById('set-email').value.trim(),
        description: document.getElementById('set-description').value.trim()
      };

      if (!payload.name) {
        Toast.error('Validation Error', 'Official Registered Organization Name is required.');
        return;
      }

      try {
        const res = await Api.post('/pikvero/api/owner/settings/update.php', payload);
        if (res.success) {
          Toast.success('Branding Saved', res.message);

          // Refresh session & re-render sidebar so logo & app name update live!
          await AuthHelper.checkSession();
          SidebarComponent.render('settings', 'owner');
        }
      } catch (err) {
        console.error(err);
      }
    }
  </script>
</body>
</html>
