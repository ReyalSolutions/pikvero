<?php
$pageTitle  = 'Pikvero — Subscription Payments & Billing';
$headExtras = ['datatables'];
require_once __DIR__ . '/../../includes/head.php';
?>
  <style>
    .stat-card-mini {
      background: var(--white);
      border: 2px solid var(--ink);
      border-radius: 12px;
      padding: 16px;
      box-shadow: 4px 4px 0 var(--ink);
      display: flex;
      flex-direction: column;
      justify-content: space-between;
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

    /* DataTables Overrides */
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
  </style>
</head>
<body>

  <div id="sidebar-container"></div>
  <div id="navbar-container"></div>

  <main class="portal-main">
    <div>
      <!-- HEADER -->
      <div style="margin-bottom:24px; display:flex; justify-content:space-between; align-items:flex-end; flex-wrap:wrap; gap:12px;">
        <div>
          <div class="eyebrow">SAAS BILLING & SUBSCRIPTIONS</div>
          <h1 style="font-size: clamp(1.8rem, 4vw, 2.2rem); font-weight:800; text-transform:uppercase; margin:4px 0 0;">SUBSCRIPTION PAYMENTS DIRECTORY</h1>
        </div>
        <div style="display:flex; gap:8px;">
          <button onclick="exportSubPaymentsCsv()" class="button sand" style="padding:10px 16px; font-size:0.82rem;"><i class="bi bi-download"></i> Export CSV</button>
          <button onclick="refreshSubPaymentsTable()" class="button lime" style="padding:10px 16px; font-size:0.82rem;"><i class="bi bi-arrow-clockwise"></i> Refresh</button>
        </div>
      </div>

      <!-- METRICS GRID -->
      <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:16px; margin-bottom:24px;">
        <div class="stat-card-mini" style="background:#eafc8d;">
          <div style="font-size:0.72rem; font-weight:800; font-family:'DM Mono', monospace; color:var(--ink);">TOTAL SUBSCRIPTION REVENUE</div>
          <div style="font-size:1.6rem; font-weight:800; margin-top:4px;" id="metric-collected">₱0.00</div>
          <div style="font-size:0.72rem; color:#4a5c56; margin-top:2px;">Gross platform plan receipts</div>
        </div>
        <div class="stat-card-mini" style="background:#ffd9d9;">
          <div style="font-size:0.72rem; font-weight:800; font-family:'DM Mono', monospace; color:var(--ink);">FAILED / REFUNDED</div>
          <div style="font-size:1.6rem; font-weight:800; margin-top:4px; color:#ef4444;" id="metric-refunded">₱0.00</div>
          <div style="font-size:0.72rem; color:#4a5c56; margin-top:2px;">Cancelled or returned plans</div>
        </div>
        <div class="stat-card-mini" style="background:var(--ink); color:var(--white);">
          <div style="font-size:0.72rem; font-weight:800; font-family:'DM Mono', monospace; color:#a3b8b0;">NET SUBSCRIPTION REVENUE</div>
          <div style="font-size:1.6rem; font-weight:800; margin-top:4px; color:#eafc8d;" id="metric-net">₱0.00</div>
          <div style="font-size:0.72rem; color:#a3b8b0; margin-top:2px;">Realized SaaS earnings</div>
        </div>
        <div class="stat-card-mini">
          <div style="font-size:0.72rem; font-weight:800; font-family:'DM Mono', monospace; color:var(--ink);">TOTAL TRANSACTIONS</div>
          <div style="font-size:1.6rem; font-weight:800; margin-top:4px;" id="metric-count">0</div>
          <div style="font-size:0.72rem; color:#4a5c56; margin-top:2px;">Recorded subscription payments</div>
        </div>
      </div>

      <!-- TABLE & FILTERS CARD -->
      <div class="card-streetside" style="padding:24px;">
        <!-- FILTERS BAR -->
        <div style="margin-bottom:16px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
          <div style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
            <div>
              <label class="mono" style="display:block; font-size:0.68rem; margin-bottom:2px;">STATUS</label>
              <select id="filter-status" onchange="refreshSubPaymentsTable()" style="padding:6px 12px; border:2px solid var(--ink); border-radius:8px; font-weight:700; font-size:0.82rem;">
                <option value="all">All Statuses</option>
                <option value="completed">Paid / Active</option>
                <option value="pending">Pending / Past Due</option>
                <option value="failed">Failed / Cancelled</option>
              </select>
            </div>
            <div>
              <label class="mono" style="display:block; font-size:0.68rem; margin-bottom:2px;">PAYMENT METHOD</label>
              <select id="filter-method" onchange="refreshSubPaymentsTable()" style="padding:6px 12px; border:2px solid var(--ink); border-radius:8px; font-weight:700; font-size:0.82rem;">
                <option value="all">All Methods</option>
                <option value="card">Card / PayMongo</option>
                <option value="gcash">GCash</option>
                <option value="maya">Maya</option>
              </select>
            </div>
            <div>
              <label class="mono" style="display:block; font-size:0.68rem; margin-bottom:2px;">START DATE</label>
              <input type="date" id="filter-start-date" onchange="refreshSubPaymentsTable()" style="padding:5px 10px; border:2px solid var(--ink); border-radius:8px; font-weight:700; font-size:0.82rem; font-family:inherit;">
            </div>
            <div>
              <label class="mono" style="display:block; font-size:0.68rem; margin-bottom:2px;">END DATE</label>
              <input type="date" id="filter-end-date" onchange="refreshSubPaymentsTable()" style="padding:5px 10px; border:2px solid var(--ink); border-radius:8px; font-weight:700; font-size:0.82rem; font-family:inherit;">
            </div>
            <div style="align-self:flex-end;">
              <button onclick="clearDateFilters()" class="button sand" style="padding:6px 12px; font-size:0.75rem;" title="Clear Date Range"><i class="bi bi-x-circle"></i> Clear Dates</button>
            </div>
          </div>
        </div>

        <div style="overflow-x:auto; -webkit-overflow-scrolling:touch;">
          <table id="sub-payments-datatable" style="width:100%; min-width:700px; text-align:left;">
            <thead>
              <tr>
                <th>TRANSACTION & PLAN</th>
                <th>ORGANIZATION / OWNER</th>
                <th>AMOUNT</th>
                <th>STATUS & METHOD</th>
                <th>DATE RECORDED</th>
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

    <div id="footer-container"></div>
  </main>

  <!-- VIEW SUBSCRIPTION PAYMENT DETAIL MODAL -->
  <div class="modal-overlay" id="view-sub-modal">
    <div class="card-streetside" style="width:min(580px, 100%); padding:24px; background:var(--white);">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; border-bottom:2px solid var(--ink); padding-bottom:10px;">
        <h3 style="font-size:1.1rem; font-weight:800; text-transform:uppercase; margin:0;"><i class="bi bi-receipt-cutoff"></i> SUBSCRIPTION PAYMENT VOUCHER</h3>
        <button onclick="closeModal('view-sub-modal')" class="button sand" style="padding:2px 8px; font-size:0.8rem;">✕</button>
      </div>

      <div id="view-sub-content">
        <!-- Dynamically filled -->
      </div>

      <div style="display:flex; justify-content:flex-end; gap:8px; margin-top:20px;">
        <button type="button" onclick="closeModal('view-sub-modal')" class="button sand" style="padding:8px 16px; font-size:0.8rem;">Close</button>
        <button type="button" id="modal-print-btn" onclick="printSelectedSubReceipt()" class="button lime" style="padding:8px 18px; font-size:0.8rem;"><i class="bi bi-printer-fill"></i> Print Receipt</button>
      </div>
    </div>
  </div>

  <?php require_once __DIR__ . '/../../includes/scripts.php'; ?>
  <script>
    let subDataTable = null;
    let currentSubPaymentsMap = {};
    let activeSubPaymentId = null;

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

      const canViewSubPayments = isSuperAdmin || perms.includes('subscription_payments.view') || perms.includes('subscription_payments.manage') || perms.includes('system.manage');

      if (!canViewSubPayments) {
        window.location.href = '/pikvero/public/403.php?permission=subscription_payments.view';
        return;
      }

      SidebarComponent.render('subscription-payments', (role === 'court_owner' || role === 'facility_manager' || role === 'receptionist') ? 'owner' : 'admin');
      FooterComponent.render('#footer-container', true);

      // Initialize DataTables
      subDataTable = $('#sub-payments-datatable').DataTable({
        serverSide: true,
        processing: true,
        ajax: {
          url: '/pikvero/api/admin/subscription-payments.php',
          type: 'GET',
          data: function(d) {
            d.status = document.getElementById('filter-status').value;
            d.method = document.getElementById('filter-method').value;
            d.start_date = document.getElementById('filter-start-date').value;
            d.end_date = document.getElementById('filter-end-date').value;
          },
          dataSrc: function(json) {
            currentSubPaymentsMap = {};
            if (json.data) {
              json.data.forEach(p => { currentSubPaymentsMap[p.payment_id] = p; });
            }
            if (json.summary) {
              document.getElementById('metric-collected').innerText = '₱' + parseFloat(json.summary.total_collected).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
              document.getElementById('metric-refunded').innerText = '₱' + parseFloat(json.summary.total_refunded).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
              document.getElementById('metric-net').innerText = '₱' + parseFloat(json.summary.net_revenue).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
              document.getElementById('metric-count').innerText = json.summary.total_records;
            }
            return json.data || [];
          }
        },
        order: [[4, 'desc']],  // default: latest first
        columns: [
          {
            data: null,
            render: function(data, type, row) {
              return `
                <div>
                  <strong style="font-family:'DM Mono', monospace; font-size:0.92rem; color:#2563eb;">#${row.transaction_reference}</strong>
                  <div style="font-size:0.75rem; font-weight:700; color:var(--ink);">${row.plan_name || 'Subscription Plan'}</div>
                </div>
              `;
            }
          },
          {
            data: null,
            render: function(data, type, row) {
              return `
                <div>
                  <strong>${row.organization_name || 'Organization'}</strong>
                  <div style="font-size:0.72rem; color:#4a5c56;">${row.first_name ? row.first_name + ' ' + row.last_name : (row.owner_email || '—')}</div>
                </div>
              `;
            }
          },
          {
            data: 'amount',
            render: function(data) {
              return `<span style="font-family:'DM Mono', monospace; font-weight:800; font-size:0.95rem;">₱${parseFloat(data).toFixed(2)}</span>`;
            }
          },
          {
            data: null,
            render: function(data, type, row) {
              const m = (row.payment_method || 'card').toLowerCase();
              const st = (row.payment_status || 'paid').toLowerCase();

              let icon = 'bi-credit-card-fill';
              let mBadgeClass = 'dark';
              if (m.includes('gcash')) { icon = 'bi-phone-fill'; mBadgeClass = 'lime'; }
              else if (m.includes('maya')) { icon = 'bi-wallet2'; mBadgeClass = 'lime'; }

              let stBadge = '<span class="badge-streetside lime" style="font-size:0.68rem;">PAID</span>';
              if (st === 'failed' || st === 'refunded' || st === 'cancelled') {
                stBadge = '<span class="badge-streetside coral" style="font-size:0.68rem;">FAILED / CANCELLED</span>';
              } else if (st === 'pending' || st === 'past_due') {
                stBadge = '<span class="badge-streetside sand" style="font-size:0.68rem;">PENDING</span>';
              }

              return `
                <div style="display:flex; gap:6px; align-items:center; flex-wrap:wrap;">
                  ${stBadge}
                  <span class="badge-streetside ${mBadgeClass}" style="font-size:0.68rem;"><i class="bi ${icon}"></i> ${m.toUpperCase()}</span>
                </div>
              `;
            }
          },
          {
            data: 'created_at',
            render: function(data) {
              if (!data) return '<span style="color:#aaa;">—</span>';
              const dt = new Date(data);
              const date = dt.toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' });
              const time = dt.toLocaleTimeString('en-PH', { hour: '2-digit', minute: '2-digit' });
              return `<div style="font-family:'DM Mono',monospace; font-size:0.78rem;">
                        <div style="font-weight:700;">${date}</div>
                        <div style="color:#6b7c76; font-size:0.7rem;">${time}</div>
                      </div>`;
            }
          },
          {
            data: null,
            orderable: false,
            className: 'text-right',
            render: function(data, type, row) {
              return `
                <div style="display:flex; justify-content:flex-end; gap:4px;">
                  <button onclick="openViewSubModal(${row.payment_id})" class="button lime action-btn" title="View Voucher"><i class="bi bi-eye"></i> View</button>
                  <button onclick="printSelectedSubReceipt(${row.payment_id})" class="button sand action-btn" title="Print Receipt"><i class="bi bi-printer"></i> Receipt</button>
                </div>
              `;
            }
          }
        ]
      });
    });

    function refreshSubPaymentsTable() {
      if (subDataTable) {
        subDataTable.ajax.reload(null, false);
      }
    }

    function clearDateFilters() {
      document.getElementById('filter-start-date').value = '';
      document.getElementById('filter-end-date').value = '';
      refreshSubPaymentsTable();
    }

    function openViewSubModal(paymentId) {
      activeSubPaymentId = paymentId;
      const d = currentSubPaymentsMap[paymentId];
      if (!d) return;

      const st = (d.payment_status || 'paid').toLowerCase();
      const isRefunded = (st === 'failed' || st === 'refunded' || st === 'cancelled');

      document.getElementById('view-sub-content').innerHTML = `
        <!-- HEADER INFO -->
        <div style="background:#f8faf9; border:2px solid var(--ink); border-radius:10px; padding:14px; margin-bottom:14px;">
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
            <div>
              <div style="font-size:0.7rem; font-weight:800; font-family:'DM Mono', monospace; color:#4a5c56;">TRANSACTION REF</div>
              <div style="font-weight:800; font-family:'DM Mono', monospace; font-size:1.05rem; color:var(--ink);">${d.transaction_reference}</div>
            </div>
            <div style="text-align:right;">
              <div style="font-size:0.7rem; font-weight:800; font-family:'DM Mono', monospace; color:#4a5c56;">PLAN NAME</div>
              <div style="font-weight:800; font-family:'DM Mono', monospace; font-size:1.05rem; color:#2563eb;">${d.plan_name}</div>
            </div>
          </div>
          <div style="display:flex; justify-content:space-between; align-items:center; border-top:1px dashed var(--line); padding-top:8px; font-size:0.78rem;">
            <span>Payment Date: <strong>${d.payment_date || '—'}</strong></span>
            <span>
              <span class="badge-streetside ${isRefunded ? 'coral' : 'lime'}" style="font-size:0.68rem;">${isRefunded ? 'FAILED / CANCELLED' : 'PAID'}</span>
            </span>
          </div>
        </div>

        <!-- DETAILS GRID -->
        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px; font-size:0.83rem; margin-bottom:14px;">
          <!-- Organization Info -->
          <div style="background:#fdfdfd; padding:10px; border-radius:8px; border:1px solid var(--line);">
            <div style="font-size:0.7rem; font-weight:800; font-family:'DM Mono', monospace; color:#4a5c56;">ORGANIZATION TENANT</div>
            <div style="font-weight:800; margin-top:2px;">${d.organization_name || 'Main Organization'}</div>
            <div style="font-size:0.75rem; color:#4a5c56;">Tax ID: ${d.organization_tax_id || 'N/A'}</div>
          </div>

          <!-- Owner Contact -->
          <div style="background:#fdfdfd; padding:10px; border-radius:8px; border:1px solid var(--line);">
            <div style="font-size:0.7rem; font-weight:800; font-family:'DM Mono', monospace; color:#4a5c56;">COURT OWNER</div>
            <div style="font-weight:800; margin-top:2px;">${d.first_name ? d.first_name + ' ' + d.last_name : 'Owner'}</div>
            <div style="font-size:0.75rem; color:#4a5c56;">${d.owner_email || 'N/A'}</div>
          </div>

          <!-- Subscription Schedule -->
          <div style="background:#fdfdfd; padding:10px; border-radius:8px; border:1px solid var(--line);">
            <div style="font-size:0.7rem; font-weight:800; font-family:'DM Mono', monospace; color:#4a5c56;">BILLING CYCLE</div>
            <div style="font-weight:800; margin-top:2px; text-transform:uppercase;">${d.billing_cycle || 'Monthly'}</div>
            <div style="font-size:0.75rem; color:#4a5c56;">Period End: <strong>${d.current_period_end || 'Auto-renew'}</strong></div>
          </div>

          <!-- Payment Method -->
          <div style="background:#fdfdfd; padding:10px; border-radius:8px; border:1px solid var(--line);">
            <div style="font-size:0.7rem; font-weight:800; font-family:'DM Mono', monospace; color:#4a5c56;">PAYMENT METHOD</div>
            <div style="font-weight:800; margin-top:2px; text-transform:uppercase;">${d.payment_method}</div>
            <div style="font-size:0.75rem; color:#4a5c56;">Sub Status: <strong>${(d.subscription_status || 'active').toUpperCase()}</strong></div>
          </div>

          <!-- Date Recorded -->
          <div style="background:#eafc8d; padding:10px; border-radius:8px; border:1px solid var(--ink); grid-column:1/-1;">
            <div style="font-size:0.7rem; font-weight:800; font-family:'DM Mono', monospace; color:#4a5c56;">DATE RECORDED (CREATED AT)</div>
            <div style="font-weight:800; margin-top:2px; font-size:0.95rem;">${d.created_at ? new Date(d.created_at).toLocaleString('en-PH', {dateStyle:'long', timeStyle:'short'}) : (d.payment_date || '—')}</div>
          </div>
        </div>

        <!-- TOTAL AMOUNT -->
        <div style="background:#eafc8d; border:2px solid var(--ink); border-radius:10px; padding:14px; text-align:center;">
          <div style="font-size:0.72rem; font-weight:800; font-family:'DM Mono', monospace; color:var(--ink);">TOTAL SUBSCRIPTION AMOUNT PAID</div>
          <div style="font-size:1.8rem; font-weight:800; margin-top:2px; color:var(--ink);">₱${parseFloat(d.amount).toFixed(2)}</div>
        </div>
      `;

      document.getElementById('view-sub-modal').classList.add('active');
    }

    function printSelectedSubReceipt(paymentId) {
      const pid = paymentId || activeSubPaymentId;
      if (pid) {
        window.open(`/pikvero/public/subscription-receipt.php?payment_id=${pid}`, '_blank');
      }
    }

    function exportSubPaymentsCsv() {
      Toast.success('Export Started', 'Exporting subscription payments directory to CSV format...');
    }

    function closeModal(id) {
      const modal = document.getElementById(id);
      if (modal) modal.classList.remove('active');
    }
  </script>
</body>
</html>
