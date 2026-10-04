<?php
$pageTitle  = 'Pikvero — System Audit Logs';
$headExtras = ['datatables'];
require_once __DIR__ . '/../../includes/head.php';
?>
  <style>
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
    .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
      background: var(--lime) !important;
      color: var(--ink) !important;
      border: 2px solid var(--ink) !important;
      border-radius: 8px !important;
    }
    .log-checkbox {
      transform: scale(1.3);
      accent-color: var(--coral);
      cursor: pointer;
    }
  </style>
</head>
<body>

  <div id="sidebar-container"></div>
  <div id="navbar-container"></div>

  <main class="portal-main">
    <div id="logs-page-content">
      <div style="margin-bottom:20px; display:flex; justify-content:space-between; align-items:flex-end; flex-wrap:wrap; gap:12px;">
        <div>
          <div class="eyebrow">SECURITY COMPLIANCE</div>
          <h1 style="font-size: clamp(1.8rem, 4vw, 2.2rem); font-weight:800; text-transform:uppercase; margin:4px 0 0;">SYSTEM AUDIT LOGS</h1>
        </div>
        <div style="display:flex; gap:8px; flex-wrap:wrap;" id="logs-header-actions">
          <button id="btn-export-csv" onclick="exportLogsCsv()" class="button lime" style="padding:10px 18px; font-size:0.85rem; display:none;">
            <i class="bi bi-file-earmark-spreadsheet-fill"></i> Export Logs (CSV)
          </button>
          <button id="btn-delete-selected" onclick="deleteSelectedLogs()" disabled class="button coral" style="padding:10px 18px; font-size:0.85rem; opacity:0.5; cursor:not-allowed; display:none;">
            <i class="bi bi-trash-fill"></i> Delete Selected (<span id="selected-count">0</span>)
          </button>
        </div>
      </div>

      <div class="card-streetside" style="padding:24px;">
        <div style="overflow-x:auto; -webkit-overflow-scrolling:touch;">
          <table id="logs-datatable" style="width:100%; min-width:750px; text-align:left;">
            <thead id="logs-thead">
              <!-- Dynamically structured by permission -->
            </thead>
            <tbody>
              <!-- Loaded via Server-Side DataTables -->
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- 403 FORBIDDEN CONTAINER -->
    <div id="logs-forbidden-card" style="display:none; max-width:600px; margin:40px auto;">
      <div class="card-streetside" style="padding:32px; background:var(--white); text-align:center;">
        <div style="font-size:3rem; color:var(--coral); margin-bottom:12px;"><i class="bi bi-shield-slash-fill"></i></div>
        <div class="eyebrow" style="color:var(--coral);">ACCESS RESTRICTED</div>
        <h2 style="font-size:1.5rem; font-weight:800; text-transform:uppercase; margin:8px 0;">403 PERMISSION DENIED</h2>
        <p style="font-size:0.9rem; color:#4a5c56; margin-bottom:20px;">
          You do not have the required system permission (<code>audit_logs.view</code>) to inspect security audit logs. Please contact your administrator.
        </p>
        <a href="/pikvero/public/admin/dashboard.php" class="button dark" style="padding:10px 20px; text-transform:uppercase; font-size:0.85rem;">Return to Dashboard</a>
      </div>
    </div>

    <div id="footer-container"></div>
  </main>

  <?php require_once __DIR__ . '/../../includes/scripts.php'; ?>
  <script>
    let dataTable = null;
    let canViewLogs = false;
    let canExportLogs = false;
    let canDeleteLogs = false;

    document.addEventListener('DOMContentLoaded', async () => {
      const userCtx = await AuthHelper.checkSession();
      await NavbarComponent.render('#navbar-container', true);
      SidebarComponent.render('logs', 'admin');
      FooterComponent.render('#footer-container', true);

      // Determine RBAC permissions
      const role = userCtx ? (userCtx.role || (userCtx.user ? userCtx.user.role_name : '')) : '';
      const perms = (userCtx && userCtx.permissions) ? userCtx.permissions : [];

      canViewLogs = (role === 'super_admin' || role === 'platform_admin' || perms.includes('audit_logs.view') || perms.includes('audit_logs.manage') || perms.includes('system.manage'));
      canExportLogs = (role === 'super_admin' || role === 'platform_admin' || perms.includes('audit_logs.export') || perms.includes('audit_logs.view') || perms.includes('system.manage'));
      canDeleteLogs = (role === 'super_admin' || perms.includes('audit_logs.delete') || perms.includes('system.manage'));

      if (!canViewLogs) {
        window.location.href = '/pikvero/public/403.php?permission=audit_logs.view';
        return;
      }

      // Configure Header Actions according to permissions
      if (canExportLogs) {
        document.getElementById('btn-export-csv').style.display = 'inline-flex';
      }
      if (canDeleteLogs) {
        document.getElementById('btn-delete-selected').style.display = 'inline-flex';
      }

      // Build Table Header
      const thead = document.getElementById('logs-thead');
      let dtColumns = [];

      if (canDeleteLogs) {
        thead.innerHTML = `
          <tr>
            <th style="width:30px; text-align:center;">
              <input type="checkbox" id="select-all-logs" class="log-checkbox" onclick="toggleSelectAllLogs(this)">
            </th>
            <th>ACTION</th>
            <th>MODULE</th>
            <th>DESCRIPTION</th>
            <th>USER</th>
            <th>IP / TIMESTAMP</th>
          </tr>
        `;
        dtColumns = [
          {
            data: 'id',
            orderable: false,
            className: 'text-center',
            render: function(data) {
              return `<input type="checkbox" class="log-checkbox log-item-check" value="${data}" onchange="updateDeleteButtonState()">`;
            }
          },
          { data: 'action', render: data => `<span class="badge-streetside lime">${data}</span>` },
          { data: 'module', render: data => `<strong>${data}</strong>` },
          { data: 'description', render: data => data || 'N/A' },
          { data: 'user_name', render: (data, type, row) => `<div><strong>${data || 'System Guest'}</strong>${row.email ? `<br><span style="font-size:0.72rem; color:#6b7c76;">${row.email}</span>` : ''}</div>` },
          { data: 'created_at', render: (data, type, row) => `<div style="font-size:0.75rem; color:#4a5c56;"><span style="font-family:'DM Mono', monospace; font-weight:700;">${row.ip_address}</span><br>${data}</div>` }
        ];
      } else {
        thead.innerHTML = `
          <tr>
            <th>ACTION</th>
            <th>MODULE</th>
            <th>DESCRIPTION</th>
            <th>USER</th>
            <th>IP / TIMESTAMP</th>
          </tr>
        `;
        dtColumns = [
          { data: 'action', render: data => `<span class="badge-streetside lime">${data}</span>` },
          { data: 'module', render: data => `<strong>${data}</strong>` },
          { data: 'description', render: data => data || 'N/A' },
          { data: 'user_name', render: (data, type, row) => `<div><strong>${data || 'System Guest'}</strong>${row.email ? `<br><span style="font-size:0.72rem; color:#6b7c76;">${row.email}</span>` : ''}</div>` },
          { data: 'created_at', render: (data, type, row) => `<div style="font-size:0.75rem; color:#4a5c56;"><span style="font-family:'DM Mono', monospace; font-weight:700;">${row.ip_address}</span><br>${data}</div>` }
        ];
      }

      // Initialize Server-Side DataTables
      dataTable = $('#logs-datatable').DataTable({
        serverSide: true,
        processing: true,
        order: [[canDeleteLogs ? 5 : 4, 'desc']],
        ajax: {
          url: '/pikvero/api/admin/logs.php',
          type: 'GET',
          dataSrc: function(json) {
            const selectAll = document.getElementById('select-all-logs');
            if (selectAll) selectAll.checked = false;
            updateDeleteButtonState();
            return json.data || [];
          }
        },
        columns: dtColumns
      });
    });

    function toggleSelectAllLogs(masterCheckbox) {
      const itemCheckboxes = document.querySelectorAll('.log-item-check');
      itemCheckboxes.forEach(cb => {
        cb.checked = masterCheckbox.checked;
      });
      updateDeleteButtonState();
    }

    function updateDeleteButtonState() {
      if (!canDeleteLogs) return;
      const selectedCheckboxes = document.querySelectorAll('.log-item-check:checked');
      const count = selectedCheckboxes.length;
      const deleteBtn = document.getElementById('btn-delete-selected');
      const countSpan = document.getElementById('selected-count');

      if (countSpan) countSpan.innerText = count;

      if (count > 0) {
        deleteBtn.disabled = false;
        deleteBtn.style.opacity = '1';
        deleteBtn.style.cursor = 'pointer';
      } else {
        deleteBtn.disabled = true;
        deleteBtn.style.opacity = '0.5';
        deleteBtn.style.cursor = 'not-allowed';
      }
    }

    function exportLogsCsv() {
      if (!canExportLogs) {
        Toast.error('Access Denied', "Permission 'audit_logs.export' required.");
        return;
      }
      const searchVal = dataTable ? dataTable.search() : '';
      Toast.info('Export Started', 'Generating audit logs CSV export...');
      window.location.href = `/pikvero/api/admin/logs/export.php?search=${encodeURIComponent(searchVal)}`;
    }

    function deleteSelectedLogs() {
      if (!canDeleteLogs) {
        Toast.error('Access Denied', "Permission 'audit_logs.delete' required.");
        return;
      }
      const selectedCheckboxes = document.querySelectorAll('.log-item-check:checked');
      const selectedIds = Array.from(selectedCheckboxes).map(cb => parseInt(cb.value));

      if (selectedIds.length === 0) return;

      Modal.confirm({
        title: 'Delete Selected Audit Logs',
        message: `Are you sure you want to delete ${selectedIds.length} selected audit log entry/entries? This action cannot be undone.`,
        confirmText: `Yes, Delete (${selectedIds.length}) Logs`,
        cancelText: 'Cancel',
        type: 'danger',
        onConfirm: async () => {
          try {
            const res = await Api.post('/pikvero/api/admin/logs/delete.php', { ids: selectedIds });
            if (res.success) {
              Toast.success('Logs Deleted', `Successfully deleted ${res.data.deleted_count} log entry/entries.`);
              dataTable.ajax.reload(null, false);
            }
          } catch (err) {
            console.error(err);
          }
        }
      });
    }
  </script>
</body>
</html>
