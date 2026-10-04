<?php
$pageTitle  = 'Pikvero — System Users, Roles & Permissions';
$headExtras = ['datatables'];
require_once __DIR__ . '/../../includes/head.php';
?>
  <style>
    .tab-btn {
      padding: 10px 20px;
      font-size: 0.85rem;
      font-weight: 800;
      text-transform: uppercase;
      border-radius: 8px;
      cursor: pointer;
      transition: all 0.2s ease;
    }
    .tab-btn.active {
      background: var(--coral) !important;
      color: var(--white) !important;
      box-shadow: 3px 3px 0 var(--ink) !important;
    }
    .tab-panel {
      display: none;
    }
    .tab-panel.active {
      display: block;
    }
    .modal-overlay {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100vw;
      height: 100vh;
      background: rgba(13, 33, 29, 0.78);
      backdrop-filter: blur(4px);
      -webkit-backdrop-filter: blur(4px);
      z-index: 50000 !important;
      place-items: center;
      padding: 20px;
    }
    .modal-overlay.active {
      display: grid;
    }
    .action-btn {
      padding: 4px 8px;
      font-size: 0.72rem;
      border-radius: 6px;
      font-weight: 700;
    }
    .inline-feedback {
      font-family: 'DM Mono', monospace;
      font-size: 0.72rem;
      font-weight: 700;
      margin-top: 4px;
      min-height: 16px;
    }
    .inline-feedback.valid { color: #10b981; }
    .inline-feedback.invalid { color: #ef4444; }
    input.is-valid { border-color: #10b981 !important; }
    input.is-invalid { border-color: #ef4444 !important; }

    /* Streetside DataTables Styling Overrides */
    .dataTables_wrapper {
      font-family: inherit;
      font-size: 0.85rem;
    }
    .dataTables_wrapper .dataTables_length select,
    .dataTables_wrapper .dataTables_filter input {
      border: 2px solid var(--ink) !important;
      border-radius: 8px !important;
      padding: 4px 8px !important;
      font-weight: 700 !important;
      font-family: inherit !important;
    }
    table.dataTable {
      border-collapse: collapse !important;
      width: 100% !important;
      margin-top: 12px !important;
      margin-bottom: 12px !important;
    }
    table.dataTable thead th {
      border-bottom: 2px solid var(--ink) !important;
      font-family: 'DM Mono', monospace !important;
      font-size: 0.72rem !important;
      padding: 10px !important;
    }
    table.dataTable tbody td {
      padding: 10px !important;
      border-bottom: 1px solid var(--line) !important;
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button.current {
      background: var(--coral) !important;
      color: var(--white) !important;
      border: 2px solid var(--ink) !important;
      border-radius: 8px !important;
      font-weight: 800 !important;
      box-shadow: 2px 2px 0 var(--ink) !important;
    }

    /* Role Card Styles */
    .role-card {
      background: var(--white);
      border: 2px solid var(--ink);
      border-radius: 12px;
      padding: 20px;
      box-shadow: 4px 4px 0 var(--ink);
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      transition: transform 0.15s ease;
    }
    .role-card:hover {
      transform: translateY(-2px);
    }
    .perm-module-card {
      border: 2px solid var(--ink);
      border-radius: 10px;
      padding: 14px;
      background: #f8faf9;
      margin-bottom: 14px;
    }
  </style>
</head>
<body>

  <div id="sidebar-container"></div>
  <div id="navbar-container"></div>

  <main class="portal-main">
    <div>
      <!-- TOP NAVIGATION TABS -->
      <div style="display:flex; gap:10px; border-bottom:2px solid var(--ink); margin-bottom:24px; padding-bottom:12px; flex-wrap:wrap;">
        <button class="button tab-btn active" id="tab-btn-users" onclick="switchTab('users')">
          <i class="bi bi-people-fill"></i> System Users
        </button>
        <button class="button tab-btn sand" id="tab-btn-roles" onclick="switchTab('roles')">
          <i class="bi bi-shield-lock-fill"></i> Roles Management
        </button>
        <button class="button tab-btn sand" id="tab-btn-permissions" onclick="switchTab('permissions')">
          <i class="bi bi-key-fill"></i> Permissions Directory
        </button>
      </div>

      <!-- TAB 1: USERS DIRECTORY -->
      <div id="tab-users" class="tab-panel active">
        <div style="margin-bottom:20px; display:flex; justify-content:space-between; align-items:flex-end; flex-wrap:wrap; gap:12px;">
          <div>
            <div class="eyebrow">USER MANAGEMENT</div>
            <h1 style="font-size: clamp(1.8rem, 4vw, 2.2rem); font-weight:800; text-transform:uppercase; margin:4px 0 0;">SYSTEM USERS DIRECTORY</h1>
          </div>
          <button onclick="openAddUserModal()" class="button lime" style="padding:10px 18px; font-size:0.85rem;"><i class="bi bi-person-plus-fill"></i> Add New User</button>
        </div>

        <div class="card-streetside" style="padding:24px;">
          <div style="overflow-x:auto; -webkit-overflow-scrolling:touch;">
            <table id="users-datatable" style="width:100%; min-width:750px; text-align:left;">
              <thead>
                <tr>
                  <th>USER</th>
                  <th>USERNAME</th>
                  <th>ROLE</th>
                  <th>STATUS</th>
                  <th style="text-align:right;">ACTIONS</th>
                </tr>
              </thead>
              <tbody>
                <!-- Loaded via Server-Side DataTables -->
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- TAB 2: ROLES MANAGEMENT -->
      <div id="tab-roles" class="tab-panel">
        <div style="margin-bottom:20px; display:flex; justify-content:space-between; align-items:flex-end; flex-wrap:wrap; gap:12px;">
          <div>
            <div class="eyebrow">ACCESS CONTROL</div>
            <h1 style="font-size: clamp(1.8rem, 4vw, 2.2rem); font-weight:800; text-transform:uppercase; margin:4px 0 0;">SYSTEM ROLES MANAGEMENT</h1>
          </div>
          <button onclick="openAddRoleModal()" class="button lime" style="padding:10px 18px; font-size:0.85rem;"><i class="bi bi-shield-plus"></i> Add New Role</button>
        </div>

        <div id="roles-grid" style="display:grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap:18px;">
          <!-- Loaded via JS -->
        </div>
      </div>

      <!-- TAB 3: PERMISSIONS DIRECTORY -->
      <div id="tab-permissions" class="tab-panel">
        <div style="margin-bottom:20px; display:flex; justify-content:space-between; align-items:flex-end; flex-wrap:wrap; gap:12px;">
          <div>
            <div class="eyebrow">RBAC MATRIX</div>
            <h1 style="font-size: clamp(1.8rem, 4vw, 2.2rem); font-weight:800; text-transform:uppercase; margin:4px 0 0;">SYSTEM PERMISSIONS DIRECTORY</h1>
          </div>
        </div>

        <div class="card-streetside" style="padding:24px;">
          <div style="margin-bottom:16px; display:flex; justify-content:space-between; align-items:center;">
            <div style="font-weight:800; font-size:0.9rem; text-transform:uppercase;">Registered Permissions List</div>
            <input type="text" id="perm-search-input" placeholder="Search permissions..." oninput="filterPermissionsTable()" style="padding:6px 12px; border:2px solid var(--ink); border-radius:8px; font-weight:700; font-size:0.82rem; width:260px;">
          </div>
          <div style="overflow-x:auto; -webkit-overflow-scrolling:touch;">
            <table id="permissions-table" style="width:100%; border-collapse:collapse; text-align:left; font-size:0.82rem;">
              <thead>
                <tr style="border-bottom:2px solid var(--ink); font-family:'DM Mono', monospace; font-size:0.72rem;">
                  <th style="padding:10px;">PERMISSION KEY</th>
                  <th style="padding:10px;">DESCRIPTION</th>
                  <th style="padding:10px;">ASSIGNED ROLES</th>
                </tr>
              </thead>
              <tbody id="permissions-tbody">
                <!-- Loaded via JS -->
              </tbody>
            </table>
          </div>
        </div>
      </div>

    </div>

    <div id="footer-container"></div>
  </main>

  <!-- ADD USER MODAL -->
  <div class="modal-overlay" id="add-user-modal">
    <div class="card-streetside" style="width:min(520px, 100%); padding:24px; background:var(--white);">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
        <h3 style="font-size:1.1rem; font-weight:800; text-transform:uppercase; margin:0;"><i class="bi bi-person-plus-fill"></i> CREATE NEW SYSTEM USER</h3>
        <button onclick="closeModal('add-user-modal')" class="button sand" style="padding:2px 8px; font-size:0.8rem;">✕</button>
      </div>
      <form id="add-user-form">
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:10px;">
          <div>
            <label class="mono" style="display:block; margin-bottom:4px; font-size:0.72rem;">FIRST NAME *</label>
            <input type="text" id="add-fname" required style="width:100%; padding:8px; border:2px solid var(--ink); border-radius:8px; font-weight:700;">
          </div>
          <div>
            <label class="mono" style="display:block; margin-bottom:4px; font-size:0.72rem;">LAST NAME *</label>
            <input type="text" id="add-lname" required style="width:100%; padding:8px; border:2px solid var(--ink); border-radius:8px; font-weight:700;">
          </div>
        </div>

        <div style="margin-bottom:10px;">
          <label class="mono" style="display:block; margin-bottom:4px; font-size:0.72rem;">USERNAME *</label>
          <input type="text" id="add-username" required placeholder="james" style="width:100%; padding:8px; border:2px solid var(--ink); border-radius:8px; font-weight:700;">
          <div id="fb-add-username" class="inline-feedback"></div>
        </div>

        <div style="margin-bottom:10px;">
          <label class="mono" style="display:block; margin-bottom:4px; font-size:0.72rem;">EMAIL ADDRESS *</label>
          <input type="email" id="add-email" required placeholder="alvin100golosino@gmail.com" style="width:100%; padding:8px; border:2px solid var(--ink); border-radius:8px; font-weight:700;">
          <div id="fb-add-email" class="inline-feedback"></div>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:10px;">
          <div>
            <label class="mono" style="display:block; margin-bottom:4px; font-size:0.72rem;">PHONE NUMBER</label>
            <input type="text" id="add-phone" placeholder="09181112222" maxlength="11" style="width:100%; padding:8px; border:2px solid var(--ink); border-radius:8px; font-weight:700;">
            <div id="fb-add-phone" class="inline-feedback"></div>
          </div>
          <div>
            <label class="mono" style="display:block; margin-bottom:4px; font-size:0.72rem;">SYSTEM ROLE *</label>
            <select id="add-roleid" required style="width:100%; padding:8px; border:2px solid var(--ink); border-radius:8px; font-weight:700; font-family:inherit;">
              <option value="">Loading roles...</option>
            </select>
          </div>
        </div>

        <div style="margin-bottom:16px;">
          <label class="mono" style="display:block; margin-bottom:4px; font-size:0.72rem;">PASSWORD *</label>
          <div style="display:flex; gap:6px;">
            <input type="text" id="add-password" required minlength="6" placeholder="Password123!" style="flex:1; padding:8px; border:2px solid var(--ink); border-radius:8px; font-weight:700; font-family:'DM Mono', monospace;">
            <button type="button" onclick="generateAddPassword()" class="button sand" style="padding:6px 10px; font-size:0.75rem;">Generate</button>
          </div>
        </div>

        <div style="display:flex; justify-content:flex-end; gap:8px;">
          <button type="button" onclick="closeModal('add-user-modal')" class="button sand" style="padding:8px 14px; font-size:0.8rem;">Cancel</button>
          <button type="submit" class="button lime" style="padding:8px 16px; font-size:0.8rem;"><i class="bi bi-person-check-fill"></i> Create User</button>
        </div>
      </form>
    </div>
  </div>

  <!-- EDIT USER MODAL -->
  <div class="modal-overlay" id="edit-user-modal">
    <div class="card-streetside" style="width:min(480px, 100%); padding:24px; background:var(--white);">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
        <h3 style="font-size:1.1rem; font-weight:800; text-transform:uppercase; margin:0;"><i class="bi bi-pencil-square"></i> EDIT USER ACCOUNT</h3>
        <button onclick="closeModal('edit-user-modal')" class="button sand" style="padding:2px 8px; font-size:0.8rem;">✕</button>
      </div>
      <form id="edit-user-form">
        <input type="hidden" id="edit-userid">
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:12px;">
          <div>
            <label class="mono" style="display:block; margin-bottom:4px; font-size:0.72rem;">FIRST NAME *</label>
            <input type="text" id="edit-fname" required style="width:100%; padding:8px; border:2px solid var(--ink); border-radius:8px; font-weight:700;">
          </div>
          <div>
            <label class="mono" style="display:block; margin-bottom:4px; font-size:0.72rem;">LAST NAME *</label>
            <input type="text" id="edit-lname" required style="width:100%; padding:8px; border:2px solid var(--ink); border-radius:8px; font-weight:700;">
          </div>
        </div>
        <div style="margin-bottom:12px;">
          <label class="mono" style="display:block; margin-bottom:4px; font-size:0.72rem;">USERNAME *</label>
          <input type="text" id="edit-username" required style="width:100%; padding:8px; border:2px solid var(--ink); border-radius:8px; font-weight:700;">
        </div>
        <div style="margin-bottom:12px;">
          <label class="mono" style="display:block; margin-bottom:4px; font-size:0.72rem;">EMAIL ADDRESS *</label>
          <input type="email" id="edit-email" required style="width:100%; padding:8px; border:2px solid var(--ink); border-radius:8px; font-weight:700;">
        </div>
        <div style="margin-bottom:16px;">
          <label class="mono" style="display:block; margin-bottom:4px; font-size:0.72rem;">PHONE NUMBER</label>
          <input type="text" id="edit-phone" placeholder="09181112222" style="width:100%; padding:8px; border:2px solid var(--ink); border-radius:8px; font-weight:700;">
        </div>
        <div style="display:flex; justify-content:flex-end; gap:8px;">
          <button type="button" onclick="closeModal('edit-user-modal')" class="button sand" style="padding:8px 14px; font-size:0.8rem;">Cancel</button>
          <button type="submit" class="button lime" style="padding:8px 16px; font-size:0.8rem;"><i class="bi bi-check-lg"></i> Save Changes</button>
        </div>
      </form>
    </div>
  </div>

  <!-- RESET PASSWORD MODAL -->
  <div class="modal-overlay" id="reset-pass-modal">
    <div class="card-streetside" style="width:min(420px, 100%); padding:24px; background:var(--white);">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
        <h3 style="font-size:1.1rem; font-weight:800; text-transform:uppercase; margin:0;"><i class="bi bi-key-fill"></i> ADMIN RESET PASSWORD</h3>
        <button onclick="closeModal('reset-pass-modal')" class="button sand" style="padding:2px 8px; font-size:0.8rem;">✕</button>
      </div>
      <p id="reset-user-label" style="font-size:0.82rem; color:#4a5c56; margin-bottom:14px;"></p>
      <form id="reset-pass-form">
        <input type="hidden" id="reset-userid">
        <div style="margin-bottom:14px;">
          <label class="mono" style="display:block; margin-bottom:4px; font-size:0.72rem;">NEW PASSWORD (MIN 6 CHARS)</label>
          <div style="display:flex; gap:6px;">
            <input type="text" id="reset-newpass" required minlength="6" style="flex:1; padding:8px; border:2px solid var(--ink); border-radius:8px; font-weight:700; font-family:'DM Mono', monospace;">
            <button type="button" onclick="generateRandomPass()" class="button sand" style="padding:6px 10px; font-size:0.75rem;">Generate</button>
          </div>
        </div>
        <div style="display:flex; justify-content:flex-end; gap:8px;">
          <button type="button" onclick="closeModal('reset-pass-modal')" class="button sand" style="padding:8px 14px; font-size:0.8rem;">Cancel</button>
          <button type="submit" class="button coral" style="padding:8px 16px; font-size:0.8rem;"><i class="bi bi-key"></i> Update Password</button>
        </div>
      </form>
    </div>
  </div>

  <!-- VIEW USER ARCHIVE SYSTEM INFO MODAL -->
  <div class="modal-overlay" id="view-user-modal" style="align-items:flex-start; justify-content:center; overflow-y:auto; padding:80px 20px 40px;">
    <div class="card-streetside" style="width:min(600px, 100%); padding:24px; background:var(--white);">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
        <div>
          <span class="eyebrow">SYSTEM ACCOUNT DETAILS</span>
          <h3 id="view-user-title" style="font-size:1.2rem; font-weight:800; text-transform:uppercase; margin:2px 0 0;">USER PROFILE</h3>
        </div>
        <button onclick="closeModal('view-user-modal')" class="button sand" style="padding:2px 8px; font-size:0.8rem;">✕</button>
      </div>
      <div id="view-user-content">
        <!-- Loaded via AJAX -->
      </div>
      <div style="margin-top:20px; display:flex; justify-content:flex-end;">
        <button onclick="closeModal('view-user-modal')" class="button dark" style="padding:8px 16px; font-size:0.8rem;">Close Info</button>
      </div>
    </div>
  </div>


  <!-- ADD ROLE MODAL -->
  <div class="modal-overlay" id="add-role-modal">
    <div class="card-streetside" style="width:min(440px, 100%); padding:24px; background:var(--white);">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
        <h3 style="font-size:1.1rem; font-weight:800; text-transform:uppercase; margin:0;"><i class="bi bi-shield-plus"></i> CREATE SYSTEM ROLE</h3>
        <button onclick="closeModal('add-role-modal')" class="button sand" style="padding:2px 8px; font-size:0.8rem;">✕</button>
      </div>
      <form id="add-role-form">
        <div style="margin-bottom:12px;">
          <label class="mono" style="display:block; margin-bottom:4px; font-size:0.72rem;">ROLE DISPLAY NAME *</label>
          <input type="text" id="add-role-display" required placeholder="Tournament Director" style="width:100%; padding:8px; border:2px solid var(--ink); border-radius:8px; font-weight:700;">
        </div>
        <div style="margin-bottom:16px;">
          <label class="mono" style="display:block; margin-bottom:4px; font-size:0.72rem;">SYSTEM IDENTIFIER KEY (SLUG)</label>
          <input type="text" id="add-role-name" placeholder="tournament_director (Auto-generated if empty)" style="width:100%; padding:8px; border:2px solid var(--ink); border-radius:8px; font-weight:700; font-family:'DM Mono', monospace;">
        </div>
        <div style="display:flex; justify-content:flex-end; gap:8px;">
          <button type="button" onclick="closeModal('add-role-modal')" class="button sand" style="padding:8px 14px; font-size:0.8rem;">Cancel</button>
          <button type="submit" class="button lime" style="padding:8px 16px; font-size:0.8rem;"><i class="bi bi-shield-check"></i> Save Role</button>
        </div>
      </form>
    </div>
  </div>

  <!-- EDIT ROLE MODAL -->
  <div class="modal-overlay" id="edit-role-modal">
    <div class="card-streetside" style="width:min(440px, 100%); padding:24px; background:var(--white);">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
        <h3 style="font-size:1.1rem; font-weight:800; text-transform:uppercase; margin:0;"><i class="bi bi-pencil-square"></i> EDIT ROLE NAME</h3>
        <button onclick="closeModal('edit-role-modal')" class="button sand" style="padding:2px 8px; font-size:0.8rem;">✕</button>
      </div>
      <form id="edit-role-form">
        <input type="hidden" id="edit-role-id">
        <div style="margin-bottom:16px;">
          <label class="mono" style="display:block; margin-bottom:4px; font-size:0.72rem;">ROLE DISPLAY NAME *</label>
          <input type="text" id="edit-role-display" required style="width:100%; padding:8px; border:2px solid var(--ink); border-radius:8px; font-weight:700;">
        </div>
        <div style="display:flex; justify-content:flex-end; gap:8px;">
          <button type="button" onclick="closeModal('edit-role-modal')" class="button sand" style="padding:8px 14px; font-size:0.8rem;">Cancel</button>
          <button type="submit" class="button lime" style="padding:8px 16px; font-size:0.8rem;"><i class="bi bi-check-lg"></i> Update Role</button>
        </div>
      </form>
    </div>
  </div>

  <!-- ASSIGN PERMISSIONS MODAL (BIG COLLAPSIBLE MODAL) -->
  <div class="modal-overlay" id="assign-permissions-modal">
    <div class="card-streetside" style="width:min(1100px, 95vw); padding:24px; background:var(--white); max-height:92vh; display:flex; flex-direction:column;">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; border-bottom:2px solid var(--ink); padding-bottom:12px;">
        <div>
          <span class="eyebrow">ROLE & PERMISSION MATRIX</span>
          <h3 id="assign-perm-role-title" style="font-size:1.25rem; font-weight:800; text-transform:uppercase; margin:2px 0 0;">ASSIGN PERMISSIONS</h3>
        </div>
        <button onclick="closeModal('assign-permissions-modal')" class="button sand" style="padding:4px 10px; font-size:0.85rem;">✕</button>
      </div>

      <div style="margin-bottom:14px; display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap;">
        <input type="text" id="assign-perm-search" placeholder="Search permissions by key name or description..." oninput="filterAssignPermissions()" style="padding:8px 14px; border:2px solid var(--ink); border-radius:8px; font-weight:700; font-size:0.85rem; flex:1; min-width:280px;">
        <div style="display:flex; gap:6px; flex-wrap:wrap;">
          <button type="button" onclick="toggleExpandAllPermissions(true)" class="button sand" style="padding:6px 12px; font-size:0.75rem;"><i class="bi bi-arrows-expand"></i> Expand All</button>
          <button type="button" onclick="toggleExpandAllPermissions(false)" class="button sand" style="padding:6px 12px; font-size:0.75rem;"><i class="bi bi-arrows-collapse"></i> Collapse All</button>
          <button type="button" onclick="toggleSelectAllPermissions(true)" class="button lime" style="padding:6px 12px; font-size:0.75rem;"><i class="bi bi-check-all"></i> Select All</button>
          <button type="button" onclick="toggleSelectAllPermissions(false)" class="button coral" style="padding:6px 12px; font-size:0.75rem;"><i class="bi bi-x-circle"></i> Clear All</button>
        </div>
      </div>

      <form id="assign-permissions-form" style="flex:1; overflow-y:auto; padding-right:8px;">
        <input type="hidden" id="assign-perm-role-id">
        <div id="assign-perm-modules-container">
          <!-- Loaded dynamically -->
        </div>
      </form>

      <div style="margin-top:16px; padding-top:12px; border-top:2px solid var(--ink); display:flex; justify-content:space-between; align-items:center;">
        <div id="total-perms-selected-badge" style="font-family:'DM Mono', monospace; font-size:0.82rem; font-weight:800;">
          Total Selected: <span id="assign-total-count" style="color:var(--coral);">0</span> permissions
        </div>
        <div style="display:flex; gap:8px;">
          <button type="button" onclick="closeModal('assign-permissions-modal')" class="button sand" style="padding:8px 16px; font-size:0.85rem;">Cancel</button>
          <button type="button" onclick="submitAssignPermissions()" class="button lime" style="padding:8px 18px; font-size:0.85rem;"><i class="bi bi-shield-lock-fill"></i> Save Role Permissions</button>
        </div>
      </div>
    </div>
  </div>

  <?php require_once __DIR__ . '/../../includes/scripts.php'; ?>
  <script>
    let dataTable = null;
    let currentUsersMap = {};
    let allRoles = [];
    let allPermissionsList = [];

    document.addEventListener('DOMContentLoaded', async () => {
      await NavbarComponent.render('#navbar-container', true);
      const userCtx = await AuthHelper.checkSession();
      if (!userCtx || !userCtx.user) {
        window.location.href = '/pikvero/public/login.php';
        return;
      }

      const role = userCtx.role || (userCtx.user ? userCtx.user.role_name : '');
      const perms = userCtx.permissions || [];
      const isSuperAdmin = (role === 'super_admin');

      const canViewUsers = isSuperAdmin || perms.includes('users.view') || perms.includes('users.manage') || perms.includes('system.manage');

      if (!canViewUsers) {
        window.location.href = '/pikvero/public/403.php?permission=users.view';
        return;
      }

      SidebarComponent.render('users', (role === 'court_owner' || role === 'facility_manager' || role === 'receptionist') ? 'owner' : 'admin');
      FooterComponent.render('#footer-container', true);

      const canCreateUser  = isSuperAdmin || perms.includes('users.create') || perms.includes('users.manage') || perms.includes('system.manage');
      const canEditUser    = isSuperAdmin || perms.includes('users.edit') || perms.includes('users.manage') || perms.includes('system.manage');
      const canResetPass   = isSuperAdmin || perms.includes('users.reset_password') || perms.includes('users.manage') || perms.includes('system.manage');
      const canSuspendUser = isSuperAdmin || perms.includes('users.suspend') || perms.includes('users.manage') || perms.includes('system.manage');
      const canDeleteUser  = isSuperAdmin || perms.includes('users.delete') || perms.includes('users.manage') || perms.includes('system.manage');
      const canManageRoles = isSuperAdmin || perms.includes('roles.manage') || perms.includes('system.manage');
      const canManagePerms = isSuperAdmin || perms.includes('permissions.manage') || perms.includes('system.manage');

      const btnAdd = document.querySelector('button[onclick="openAddUserModal()"]');
      if (btnAdd) {
        btnAdd.style.display = canCreateUser ? 'inline-flex' : 'none';
      }

      const btnRoles = document.getElementById('tab-btn-roles');
      if (btnRoles) btnRoles.style.display = canManageRoles ? 'inline-flex' : 'none';

      const btnPerms = document.getElementById('tab-btn-permissions');
      if (btnPerms) btnPerms.style.display = canManagePerms ? 'inline-flex' : 'none';

      // Initialize Server-Side DataTables for Users
      dataTable = $('#users-datatable').DataTable({
        serverSide: true,
        processing: true,
        ajax: {
          url: '/pikvero/api/admin/users.php',
          type: 'GET',
          dataSrc: function(json) {
            currentUsersMap = {};
            if (json.data) {
              json.data.forEach(u => { currentUsersMap[u.id] = u; });
            }
            return json.data || [];
          }
        },
        columns: [
          {
            data: null,
            render: function(data, type, row) {
              return `<div><strong>${row.first_name} ${row.last_name}</strong></div>`;
            }
          },
          {
            data: 'username',
            render: function(data) {
              return `<span style="font-family:'DM Mono', monospace; font-weight:700;">${data ? '@' + data : '—'}</span>`;
            }
          },
          {
            data: 'role_display',
            render: function(data) { return `<span class="badge-streetside dark">${data}</span>`; }
          },
          {
            data: 'status',
            render: function(data) {
              return `<span class="badge-streetside ${data === 'active' ? 'lime' : 'coral'}">${data.toUpperCase()}</span>`;
            }
          },
          {
            data: null,
            orderable: false,
            className: 'text-right',
            render: function(data, type, row) {
              return `
                <div style="display:flex; justify-content:flex-end; gap:4px;">
                  <button onclick="openViewModal(${row.id})" class="button lime action-btn" title="View System Details"><i class="bi bi-eye"></i> View</button>
                  ${canEditUser ? `<button onclick="openEditModal(${row.id})" class="button sand action-btn" title="Edit User"><i class="bi bi-pencil"></i> Edit</button>` : ''}
                  ${canResetPass ? `<button onclick="openResetModal(${row.id})" class="button dark action-btn" title="Reset Password"><i class="bi bi-key"></i> Reset</button>` : ''}
                  ${canSuspendUser ? `
                    <button onclick="toggleUserStatus(${row.id}, '${row.status}')" class="button ${row.status === 'active' ? 'coral' : 'lime'} action-btn">
                      <i class="bi bi-${row.status === 'active' ? 'slash-circle' : 'check-circle'}"></i> ${row.status === 'active' ? 'Suspend' : 'Activate'}
                    </button>
                  ` : ''}
                  ${canDeleteUser ? `
                    <button onclick="deleteUserConfirm(${row.id}, '${row.username ? row.username.replace(/'/g, "\\'") : ''}')" class="button coral action-btn" title="Delete User Account">
                      <i class="bi bi-trash-fill"></i> Delete
                    </button>
                  ` : ''}
                </div>
              `;
            }
          }
        ]
      });

      document.getElementById('add-username').addEventListener('input', validateAddUsername);
      document.getElementById('add-email').addEventListener('input', validateAddEmail);
      document.getElementById('add-phone').addEventListener('input', function() {
        this.value = this.value.replace(/\D/g, '');
        validateAddPhone();
      });

      // Add User Form submit handler
      document.getElementById('add-user-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const uOk = await validateAddUsername();
        const eOk = await validateAddEmail();
        const pOk = await validateAddPhone();

        if (!uOk || !eOk || !pOk) {
          Toast.error('Validation Error', 'Please resolve the highlighted field errors before creating the user.');
          return;
        }

        const fname = document.getElementById('add-fname').value;
        const lname = document.getElementById('add-lname').value;
        const username = document.getElementById('add-username').value;
        const email = document.getElementById('add-email').value;
        const phone = document.getElementById('add-phone').value;
        const roleId = document.getElementById('add-roleid').value;
        const password = document.getElementById('add-password').value;

        try {
          const res = await Api.post('/pikvero/api/admin/users/create.php', {
            first_name: fname,
            last_name: lname,
            username: username,
            email: email,
            phone: phone,
            role_id: roleId,
            password: password
          });

          if (res.success) {
            Toast.success('User Created', `Account for ${fname} ${lname} created successfully.`);
            closeModal('add-user-modal');
            document.getElementById('add-user-form').reset();
            dataTable.ajax.reload(null, false);
          }
        } catch (err) {
          console.error(err);
        }
      });

      // Form submission for Edit User
      document.getElementById('edit-user-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const userId = document.getElementById('edit-userid').value;
        const fname = document.getElementById('edit-fname').value;
        const lname = document.getElementById('edit-lname').value;
        const username = document.getElementById('edit-username').value;
        const email = document.getElementById('edit-email').value;
        const phone = document.getElementById('edit-phone').value;

        try {
          const res = await Api.post('/pikvero/api/admin/users/update.php', {
            user_id: userId,
            first_name: fname,
            last_name: lname,
            username: username,
            email: email,
            phone: phone
          });
          if (res.success) {
            Toast.success('User Updated', 'Account details updated successfully.');
            closeModal('edit-user-modal');
            dataTable.ajax.reload(null, false);
          }
        } catch (err) {
          console.error(err);
        }
      });

      // Form submission for Reset Password
      document.getElementById('reset-pass-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const userId = document.getElementById('reset-userid').value;
        const newPassword = document.getElementById('reset-newpass').value;

        try {
          const res = await Api.post('/pikvero/api/admin/users/reset-password.php', {
            user_id: userId,
            new_password: newPassword
          });

          if (res.success) {
            Toast.success('Password Reset', 'New password applied successfully.');
            closeModal('reset-pass-modal');
          }
        } catch (err) {
          console.error(err);
        }
      });

      // Form submission for Add Role
      document.getElementById('add-role-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const displayName = document.getElementById('add-role-display').value;
        const name = document.getElementById('add-role-name').value;

        try {
          const res = await Api.post('/pikvero/api/admin/roles/create.php', {
            display_name: displayName,
            name: name
          });
          if (res.success) {
            Toast.success('Role Created', res.message);
            closeModal('add-role-modal');
            document.getElementById('add-role-form').reset();
            loadRolesTab();
          }
        } catch (err) { console.error(err); }
      });

      // Form submission for Edit Role
      document.getElementById('edit-role-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const roleId = document.getElementById('edit-role-id').value;
        const displayName = document.getElementById('edit-role-display').value;

        try {
          const res = await Api.post('/pikvero/api/admin/roles/update.php', {
            role_id: roleId,
            display_name: displayName
          });
          if (res.success) {
            Toast.success('Role Updated', res.message);
            closeModal('edit-role-modal');
            loadRolesTab();
          }
        } catch (err) { console.error(err); }
      });
    });

    function switchTab(tabName) {
      document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active', 'sand'));
      document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.add('sand'));
      document.querySelectorAll('.tab-panel').forEach(panel => panel.classList.remove('active'));

      const activeBtn = document.getElementById(`tab-btn-${tabName}`);
      const activePanel = document.getElementById(`tab-${tabName}`);

      if (activeBtn) {
        activeBtn.classList.add('active');
        activeBtn.classList.remove('sand');
      }
      if (activePanel) activePanel.classList.add('active');

      if (tabName === 'roles') {
        loadRolesTab();
      } else if (tabName === 'permissions') {
        loadPermissionsTab();
      }
    }

    async function loadRolesTab() {
      try {
        const res = await Api.get('/pikvero/api/admin/roles.php');
        if (res.success) {
          allRoles = res.data;
          renderRolesGrid(allRoles);
        }
      } catch (err) { console.error(err); }
    }

    function renderRolesGrid(roles) {
      const container = document.getElementById('roles-grid');
      if (!container) return;

      container.innerHTML = roles.map(r => `
        <div class="role-card">
          <div>
            <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:8px;">
              <h3 style="font-size:1.1rem; font-weight:800; margin:0; text-transform:uppercase;">${r.display_name}</h3>
              <span class="badge-streetside dark" style="font-size:0.68rem; font-family:'DM Mono', monospace;">@${r.name}</span>
            </div>
            <div style="font-size:0.8rem; color:#4a5c56; margin-bottom:14px; display:flex; gap:14px;">
              <span><i class="bi bi-people"></i> <strong>${r.user_count}</strong> Users</span>
              <span><i class="bi bi-key"></i> <strong>${r.permission_count}</strong> Permissions</span>
            </div>
          </div>
          <div style="display:flex; gap:6px; margin-top:12px;">
            <button onclick="openEditRoleModal(${r.id}, '${r.display_name.replace(/'/g, "\\'")}')" class="button sand action-btn" style="flex:1;"><i class="bi bi-pencil"></i> Edit</button>
            <button onclick="openAssignPermissionsModal(${r.id}, '${r.display_name.replace(/'/g, "\\'")}')" class="button lime action-btn" style="flex:2;"><i class="bi bi-shield-lock"></i> Assign Permissions</button>
            <button onclick="deleteRoleConfirm(${r.id}, '${r.display_name.replace(/'/g, "\\'")}', ${r.user_count})" class="button coral action-btn" style="padding:4px 8px;" title="Delete Role"><i class="bi bi-trash-fill"></i></button>
          </div>
        </div>
      `).join('');
    }

    function deleteRoleConfirm(roleId, displayName, userCount) {
      if (parseInt(userCount) > 0) {
        Toast.error(
          'Cannot Delete Role',
          `Role '${displayName}' cannot be deleted because it is currently assigned to ${userCount} active user account(s). Please reassign these users first.`
        );
        return;
      }

      Modal.confirm({
        title: 'Delete System Role',
        message: `Are you sure you want to delete role '${displayName}'? This action cannot be undone.`,
        confirmText: 'Yes, Delete Role',
        cancelText: 'Cancel',
        type: 'danger',
        onConfirm: async () => {
          try {
            const res = await Api.post('/pikvero/api/admin/roles/delete.php', { role_id: roleId });
            if (res.success) {
              Toast.success('Role Deleted', res.message);
              loadRolesTab();
            }
          } catch (err) {
            console.error(err);
          }
        }
      });
    }

    async function loadPermissionsTab() {
      try {
        const res = await Api.get('/pikvero/api/admin/permissions.php');
        if (res.success) {
          allPermissionsList = res.data;
          renderPermissionsTable(allPermissionsList);
        }
      } catch (err) { console.error(err); }
    }

    function renderPermissionsTable(permissions) {
      const tbody = document.getElementById('permissions-tbody');
      if (!tbody) return;

      tbody.innerHTML = permissions.map(p => {
        const rolesBadges = p.assigned_roles ? p.assigned_roles.split(', ').map(r => `<span class="badge-streetside dark" style="margin:2px 2px; font-size:0.68rem;">${r}</span>`).join('') : '<span style="color:#8c9b95;">No roles assigned</span>';
        return `
          <tr class="perm-row" style="border-bottom:1px solid var(--line);">
            <td style="padding:10px; font-family:'DM Mono', monospace; font-weight:800; color:var(--ink);">${p.name}</td>
            <td style="padding:10px;">${p.description || 'N/A'}</td>
            <td style="padding:10px;">${rolesBadges}</td>
          </tr>
        `;
      }).join('');
    }

    function filterPermissionsTable() {
      const q = document.getElementById('perm-search-input').value.toLowerCase().trim();
      const rows = document.querySelectorAll('.perm-row');
      rows.forEach(row => {
        const text = row.innerText.toLowerCase();
        row.style.display = text.includes(q) ? '' : 'none';
      });
    }

    function openAddRoleModal() {
      document.getElementById('add-role-form').reset();
      document.getElementById('add-role-modal').classList.add('active');
    }

    function openEditRoleModal(id, displayName) {
      document.getElementById('edit-role-id').value = id;
      document.getElementById('edit-role-display').value = displayName;
      document.getElementById('edit-role-modal').classList.add('active');
    }

    async function openAssignPermissionsModal(roleId, roleDisplayName) {
      document.getElementById('assign-perm-role-id').value = roleId;
      document.getElementById('assign-perm-role-title').innerText = `ASSIGN PERMISSIONS: ${roleDisplayName}`;
      document.getElementById('assign-perm-search').value = '';

      try {
        const res = await Api.get('/pikvero/api/admin/roles/permissions.php', { role_id: roleId });
        if (res.success) {
          const assignedIds = new Set(res.data.assigned_permission_ids);
          const grouped = res.data.grouped_permissions;
          renderAssignPermissionsModules(grouped, assignedIds);
          document.getElementById('assign-permissions-modal').classList.add('active');
        }
      } catch (err) { console.error(err); }
    }

    function renderAssignPermissionsModules(grouped, assignedIds) {
      const container = document.getElementById('assign-perm-modules-container');
      if (!container) return;

      let html = '';
      for (const [module, perms] of Object.entries(grouped)) {
        const moduleSafe = module.replace(/[^a-zA-Z0-9]/g, '_');
        const selectedInModule = perms.filter(p => assignedIds.has(parseInt(p.id))).length;
        const allSelected = selectedInModule === perms.length && perms.length > 0;

        html += `
          <div class="perm-module-card perm-module-group" id="perm-mod-group-${moduleSafe}" style="background:#f8faf9; border:2px solid var(--ink); border-radius:10px; margin-bottom:12px; overflow:hidden;">
            <div class="perm-module-header" onclick="toggleCollapseModule('${moduleSafe}')" style="display:flex; justify-content:space-between; align-items:center; cursor:pointer; user-select:none; padding:12px 16px; background:#eef3f1; border-bottom:1px solid var(--line);">
              <div style="display:flex; align-items:center; gap:10px;">
                <i class="bi bi-chevron-down mod-chevron-${moduleSafe}" style="font-weight:800; font-size:0.95rem; transition:transform 0.2s ease;"></i>
                <strong style="font-size:0.9rem; text-transform:uppercase; font-family:'DM Mono', monospace; color:var(--ink);"><i class="bi bi-folder-fill" style="color:#4a5c56;"></i> ${module}</strong>
                <span class="badge-streetside sand" id="mod-badge-${moduleSafe}" style="font-size:0.72rem; margin-left:6px;">
                  <span id="mod-count-${moduleSafe}">${selectedInModule}</span> / ${perms.length} selected
                </span>
              </div>
              <div onclick="event.stopPropagation();" style="display:flex; align-items:center; gap:8px;">
                <label style="font-size:0.75rem; font-weight:800; cursor:pointer; font-family:'DM Mono', monospace;">
                  <input type="checkbox" onchange="toggleModuleCheckboxes('${moduleSafe}', this.checked)" id="mod-chk-all-${moduleSafe}" ${allSelected ? 'checked' : ''}> Select All ${module}
                </label>
              </div>
            </div>
            <div class="perm-module-body" id="perm-mod-body-${moduleSafe}" style="display:block; padding:14px; background:var(--white);">
              <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap:10px;">
                ${perms.map(p => `
                  <label class="perm-checkbox-item" data-module="${moduleSafe}" style="display:flex; align-items:flex-start; gap:10px; font-size:0.8rem; cursor:pointer; background:#fdfdfd; padding:10px 12px; border:1px solid var(--line); border-radius:8px; transition:border-color 0.15s ease;">
                    <input type="checkbox" name="permissions[]" value="${p.id}" class="perm-chk perm-chk-mod-${moduleSafe}" ${assignedIds.has(parseInt(p.id)) ? 'checked' : ''} onchange="onPermissionCheckboxChange('${moduleSafe}')" style="margin-top:2px; transform:scale(1.15);">
                    <div>
                      <div style="font-weight:800; font-family:'DM Mono', monospace; font-size:0.78rem; color:var(--ink);">${p.name}</div>
                      <div style="font-size:0.72rem; color:#4a5c56; margin-top:2px;">${p.description || 'No description provided'}</div>
                    </div>
                  </label>
                `).join('')}
              </div>
            </div>
          </div>
        `;
      }
      container.innerHTML = html;
      updateTotalSelectedCount();
    }

    function toggleCollapseModule(moduleSafe) {
      const body = document.getElementById(`perm-mod-body-${moduleSafe}`);
      const chevron = document.querySelector(`.mod-chevron-${moduleSafe}`);
      if (!body || !chevron) return;

      if (body.style.display === 'none') {
        body.style.display = 'block';
        chevron.style.transform = 'rotate(0deg)';
      } else {
        body.style.display = 'none';
        chevron.style.transform = 'rotate(-90deg)';
      }
    }

    function toggleExpandAllPermissions(shouldExpand) {
      document.querySelectorAll('.perm-module-body').forEach(body => {
        body.style.display = shouldExpand ? 'block' : 'none';
      });
      document.querySelectorAll('[class^="mod-chevron-"]').forEach(chevron => {
        chevron.style.transform = shouldExpand ? 'rotate(0deg)' : 'rotate(-90deg)';
      });
    }

    function toggleModuleCheckboxes(moduleSafe, isChecked) {
      document.querySelectorAll(`.perm-chk-mod-${moduleSafe}`).forEach(cb => cb.checked = isChecked);
      onPermissionCheckboxChange(moduleSafe);
    }

    function toggleSelectAllPermissions(isChecked) {
      document.querySelectorAll('.perm-chk').forEach(cb => cb.checked = isChecked);
      document.querySelectorAll('[id^="mod-chk-all-"]').forEach(cb => cb.checked = isChecked);
      document.querySelectorAll('[id^="mod-count-"]').forEach(span => {
        const moduleSafe = span.id.replace('mod-count-', '');
        const total = document.querySelectorAll(`.perm-chk-mod-${moduleSafe}`).length;
        span.innerText = isChecked ? total : 0;
      });
      updateTotalSelectedCount();
    }

    function onPermissionCheckboxChange(moduleSafe) {
      const allInMod = document.querySelectorAll(`.perm-chk-mod-${moduleSafe}`);
      const checkedInMod = document.querySelectorAll(`.perm-chk-mod-${moduleSafe}:checked`);
      
      const countSpan = document.getElementById(`mod-count-${moduleSafe}`);
      if (countSpan) countSpan.innerText = checkedInMod.length;

      const modChkAll = document.getElementById(`mod-chk-all-${moduleSafe}`);
      if (modChkAll) modChkAll.checked = (checkedInMod.length === allInMod.length && allInMod.length > 0);

      updateTotalSelectedCount();
    }

    function updateTotalSelectedCount() {
      const totalChecked = document.querySelectorAll('.perm-chk:checked').length;
      const countEl = document.getElementById('assign-total-count');
      if (countEl) countEl.innerText = totalChecked;
    }

    function filterAssignPermissions() {
      const q = document.getElementById('assign-perm-search').value.toLowerCase().trim();
      document.querySelectorAll('.perm-module-group').forEach(group => {
        let hasVisibleInGroup = false;
        group.querySelectorAll('.perm-checkbox-item').forEach(item => {
          const text = item.innerText.toLowerCase();
          if (text.includes(q)) {
            item.style.display = 'flex';
            hasVisibleInGroup = true;
          } else {
            item.style.display = 'none';
          }
        });
        group.style.display = hasVisibleInGroup ? 'block' : 'none';
      });
    }

    async function submitAssignPermissions() {
      const roleId = document.getElementById('assign-perm-role-id').value;
      const checkedInputs = document.querySelectorAll('.perm-chk:checked');
      const permIds = Array.from(checkedInputs).map(cb => parseInt(cb.value));

      try {
        const res = await Api.post('/pikvero/api/admin/roles/assign-permissions.php', {
          role_id: roleId,
          permission_ids: permIds
        });
        if (res.success) {
          Toast.success('Permissions Updated', res.message);
          closeModal('assign-permissions-modal');
          loadRolesTab();
        }
      } catch (err) { console.error(err); }
    }

    async function openAddUserModal() {
      document.getElementById('add-user-form').reset();
      document.getElementById('fb-add-username').innerText = '';
      document.getElementById('fb-add-email').innerText = '';
      document.getElementById('fb-add-phone').innerText = '';

      try {
        const res = await Api.get('/pikvero/api/admin/roles.php');
        if (res.success && res.data) {
          const select = document.getElementById('add-roleid');
          if (select) {
            select.innerHTML = res.data.map(r => `<option value="${r.id}">${r.display_name}</option>`).join('');
          }
        }
      } catch (err) { console.error(err); }

      document.getElementById('add-user-modal').classList.add('active');
    }

    function generateAddPassword() {
      const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789!@#$';
      let pass = '';
      for (let i = 0; i < 10; i++) {
        pass += chars.charAt(Math.floor(Math.random() * chars.length));
      }
      document.getElementById('add-password').value = pass;
    }

    function openEditModal(userId) {
      const u = currentUsersMap[userId];
      if (!u) return;

      document.getElementById('edit-userid').value = u.id;
      document.getElementById('edit-fname').value = u.first_name;
      document.getElementById('edit-lname').value = u.last_name;
      document.getElementById('edit-username').value = u.username || '';
      document.getElementById('edit-email').value = u.email;
      document.getElementById('edit-phone').value = u.phone || '';

      document.getElementById('edit-user-modal').classList.add('active');
    }

    async function openViewModal(userId) {
      document.getElementById('view-user-title').innerText = `USER PROFILE #${userId}`;
      document.getElementById('view-user-content').innerHTML = `
        <div style="text-align:center; padding:30px; font-family:'DM Mono', monospace; font-weight:700;">
          <i class="bi bi-hourglass-split" style="font-size:1.5rem; display:block; margin-bottom:8px;"></i> Loading full system archive...
        </div>
      `;
      document.getElementById('view-user-modal').classList.add('active');

      try {
        const res = await Api.get('/pikvero/api/admin/users/detail.php', { id: userId });
        if (res.success && res.data) {
          const u = res.data;
          document.getElementById('view-user-title').innerText = `${u.first_name} ${u.last_name} (#${u.id})`;
          
          let orgHtml = '';
          if (u.organization) {
            orgHtml = `
              <div style="margin-top:16px; border-top:2px solid var(--ink); padding-top:14px;">
                <div class="eyebrow">COURT OWNER ORGANIZATION</div>
                <div style="font-size:1rem; font-weight:800; margin-top:2px;">${u.organization.name}</div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px; font-size:0.78rem; margin-top:8px; background:#f4f7f6; padding:10px; border-radius:8px;">
                  <div><strong>Tax ID:</strong> ${u.organization.tax_id || 'N/A'}</div>
                  <div><strong>Org Status:</strong> <span class="badge-streetside lime">${(u.organization.status || 'ACTIVE').toUpperCase()}</span></div>
                  <div><strong>Business Email:</strong> ${u.organization.email || 'N/A'}</div>
                  <div><strong>Contact Phone:</strong> ${u.organization.phone || 'N/A'}</div>
                </div>
              </div>
            `;
          }

          document.getElementById('view-user-content').innerHTML = `
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px; font-size:0.85rem; margin-bottom:16px;">
              <div style="background:#f8faf9; padding:12px; border-radius:8px; border:1px solid var(--line);">
                <div style="font-size:0.72rem; font-weight:800; font-family:'DM Mono', monospace; color:#4a5c56;">USERNAME</div>
                <div style="font-weight:800; font-family:'DM Mono', monospace; font-size:0.95rem;">@${u.username || 'N/A'}</div>
              </div>
              <div style="background:#f8faf9; padding:12px; border-radius:8px; border:1px solid var(--line);">
                <div style="font-size:0.72rem; font-weight:800; font-family:'DM Mono', monospace; color:#4a5c56;">SYSTEM ROLE</div>
                <div style="margin-top:2px;"><span class="badge-streetside dark">${u.role_display}</span></div>
              </div>
              <div style="background:#f8faf9; padding:12px; border-radius:8px; border:1px solid var(--line);">
                <div style="font-size:0.72rem; font-weight:800; font-family:'DM Mono', monospace; color:#4a5c56;">EMAIL ADDRESS</div>
                <div style="font-weight:700;">${u.email}</div>
              </div>
              <div style="background:#f8faf9; padding:12px; border-radius:8px; border:1px solid var(--line);">
                <div style="font-size:0.72rem; font-weight:800; font-family:'DM Mono', monospace; color:#4a5c56;">PHONE NUMBER</div>
                <div style="font-weight:700;">${u.phone || 'N/A'}</div>
              </div>
              <div style="background:#f8faf9; padding:12px; border-radius:8px; border:1px solid var(--line);">
                <div style="font-size:0.72rem; font-weight:800; font-family:'DM Mono', monospace; color:#4a5c56;">ACCOUNT STATUS</div>
                <div style="margin-top:2px;"><span class="badge-streetside ${u.status === 'active' ? 'lime' : 'coral'}">${u.status.toUpperCase()}</span></div>
              </div>
              <div style="background:#f8faf9; padding:12px; border-radius:8px; border:1px solid var(--line);">
                <div style="font-size:0.72rem; font-weight:800; font-family:'DM Mono', monospace; color:#4a5c56;">MEMBER SINCE</div>
                <div style="font-weight:700;">${u.created_at}</div>
              </div>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-top:12px;">
              <div style="border:2px solid var(--ink); border-radius:10px; padding:12px; background:#eafc8d; text-align:center;">
                <div style="font-size:0.72rem; font-weight:800; font-family:'DM Mono', monospace;">TOTAL BOOKINGS RECORDED</div>
                <div style="font-size:1.5rem; font-weight:800; margin-top:2px;">${u.bookings_count}</div>
              </div>
              <div style="border:2px solid var(--ink); border-radius:10px; padding:12px; background:#sky; text-align:center;">
                <div style="font-size:0.72rem; font-weight:800; font-family:'DM Mono', monospace;">FACILITIES OWNED</div>
                <div style="font-size:1.5rem; font-weight:800; margin-top:2px;">${u.facilities_count}</div>
              </div>
            </div>

            ${orgHtml}
          `;
        }
      } catch (err) {
        console.error(err);
      }
    }

    function openResetModal(userId) {
      const u = currentUsersMap[userId];
      if (!u) return;

      document.getElementById('reset-userid').value = u.id;
      document.getElementById('reset-user-label').innerText = `Resetting password for ${u.first_name} ${u.last_name} (${u.email})`;
      document.getElementById('reset-newpass').value = 'Password123!';

      document.getElementById('reset-pass-modal').classList.add('active');
    }

    function generateRandomPass() {
      const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789!@#$';
      let pass = '';
      for (let i = 0; i < 10; i++) {
        pass += chars.charAt(Math.floor(Math.random() * chars.length));
      }
      document.getElementById('reset-newpass').value = pass;
    }

    function toggleUserStatus(userId, currentStatus) {
      const u = currentUsersMap[userId];
      const actionText = currentStatus === 'active' ? 'suspend' : 'activate';
      const userName = u ? `${u.first_name} ${u.last_name}` : `ID #${userId}`;

      Modal.confirm({
        title: currentStatus === 'active' ? 'Suspend User Account' : 'Reactivate User Account',
        message: `Are you sure you want to ${actionText} user account ${userName}?`,
        confirmText: currentStatus === 'active' ? 'Yes, Suspend Account' : 'Yes, Activate Account',
        cancelText: 'Cancel',
        type: currentStatus === 'active' ? 'danger' : 'primary',
        onConfirm: async () => {
          try {
            const res = await Api.post('/pikvero/api/admin/users/suspend.php', { user_id: userId });
            if (res.success) {
              Toast.success('Status Updated', `User account is now ${res.data.status.toUpperCase()}.`);
              dataTable.ajax.reload(null, false);
            }
          } catch (err) {
            console.error(err);
          }
        }
      });
    }

    function deleteUserConfirm(userId, username) {
      const u = currentUsersMap[userId];
      const userName = u ? `${u.first_name} ${u.last_name} (@${u.username})` : `@${username}`;

      Modal.confirm({
        title: 'Delete System User',
        message: `Are you sure you want to permanently delete user account ${userName}? This action cannot be undone.`,
        confirmText: 'Yes, Delete User',
        cancelText: 'Cancel',
        type: 'danger',
        onConfirm: async () => {
          try {
            const res = await Api.post('/pikvero/api/admin/users/delete.php', { user_id: userId });
            if (res.success) {
              Toast.success('User Deleted', res.message);
              dataTable.ajax.reload(null, false);
            }
          } catch (err) {
            console.error(err);
          }
        }
      });
    }

    function closeModal(id) {
      const modal = document.getElementById(id);
      if (modal) {
        modal.classList.remove('active');
        const container = document.getElementById('toast-container');
        if (container && container.parentNode === modal) {
          document.body.appendChild(container);
        }
      }
    }

    function setFeedback(inputEl, feedbackEl, isValid, msg) {
      if (!inputEl || !feedbackEl) return;
      if (isValid) {
        inputEl.classList.remove('is-invalid');
        if (msg) inputEl.classList.add('is-valid');
        feedbackEl.className = 'inline-feedback valid';
        feedbackEl.innerText = msg;
      } else {
        inputEl.classList.remove('is-valid');
        inputEl.classList.add('is-invalid');
        feedbackEl.className = 'inline-feedback invalid';
        feedbackEl.innerText = msg;
      }
    }

    async function validateAddUsername() {
      const el = document.getElementById('add-username');
      const fb = document.getElementById('fb-add-username');
      if (!el) return true;
      const val = el.value.trim();
      if (!val) {
        setFeedback(el, fb, false, '✕ Username is required.');
        return false;
      }
      if (val.length < 3) {
        setFeedback(el, fb, false, '✕ Username must be at least 3 characters.');
        return false;
      }
      try {
        const res = await Api.get('/pikvero/api/auth/check-unique.php', { field: 'username', value: val });
        if (res.success && res.data.exists) {
          setFeedback(el, fb, false, `✕ Username '${val}' is already taken.`);
          return false;
        }
      } catch (e) {}
      setFeedback(el, fb, true, '✓ Username is available');
      return true;
    }

    async function validateAddEmail() {
      const el = document.getElementById('add-email');
      const fb = document.getElementById('fb-add-email');
      if (!el) return true;
      const val = el.value.trim();
      const formatOk = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val);
      if (!formatOk) {
        setFeedback(el, fb, false, '✕ Enter a valid email address (e.g. user@gmail.com).');
        return false;
      }
      try {
        const res = await Api.get('/pikvero/api/auth/check-unique.php', { field: 'email', value: val });
        if (res.success && res.data.exists) {
          setFeedback(el, fb, false, `✕ Email address '${val}' is already registered.`);
          return false;
        }
      } catch (e) {}
      setFeedback(el, fb, true, '✓ Email address is available');
      return true;
    }

    async function validateAddPhone() {
      const el = document.getElementById('add-phone');
      const fb = document.getElementById('fb-add-phone');
      if (!el) return true;
      const val = el.value.trim();
      if (!val) {
        setFeedback(el, fb, true, '');
        return true;
      }
      const ok = /^09\d{9}$/.test(val);
      if (!ok) {
        setFeedback(el, fb, false, '✕ Phone must be 11 digits starting with 09 (e.g. 09181112222).');
        return false;
      }
      try {
        const res = await Api.get('/pikvero/api/auth/check-unique.php', { field: 'phone', value: val });
        if (res.success && res.data.exists) {
          setFeedback(el, fb, false, '✕ This phone number is already registered.');
          return false;
        }
      } catch (e) {}
      setFeedback(el, fb, true, '✓ Phone number is available');
      return true;
    }
  </script>
</body>
</html>
