<?php
$pageTitle  = 'Pikvero — Booking Payments & Transactions';
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
          <div class="eyebrow">REVENUE & TRANSACTIONS</div>
          <h1 style="font-size: clamp(1.8rem, 4vw, 2.2rem); font-weight:800; text-transform:uppercase; margin:4px 0 0;">BOOKING PAYMENTS DIRECTORY</h1>
        </div>
        <div style="display:flex; gap:8px;">
          <button onclick="exportPaymentsCsv()" class="button sand" style="padding:10px 16px; font-size:0.82rem;"><i class="bi bi-download"></i> Export CSV</button>
          <button onclick="refreshPaymentsTable()" class="button lime" style="padding:10px 16px; font-size:0.82rem;"><i class="bi bi-arrow-clockwise"></i> Refresh</button>
        </div>
      </div>

      <!-- METRICS GRID -->
      <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:16px; margin-bottom:24px;">
        <div class="stat-card-mini" style="background:#eafc8d;">
          <div style="font-size:0.72rem; font-weight:800; font-family:'DM Mono', monospace; color:var(--ink);">TOTAL COLLECTED</div>
          <div style="font-size:1.6rem; font-weight:800; margin-top:4px;" id="metric-collected">₱0.00</div>
          <div style="font-size:0.72rem; color:#4a5c56; margin-top:2px;">Gross booking receipts</div>
        </div>
        <div class="stat-card-mini" style="background:#ffd9d9;">
          <div style="font-size:0.72rem; font-weight:800; font-family:'DM Mono', monospace; color:var(--ink);">TOTAL REFUNDED / FAILED</div>
          <div style="font-size:1.6rem; font-weight:800; margin-top:4px; color:#ef4444;" id="metric-refunded">₱0.00</div>
          <div style="font-size:0.72rem; color:#4a5c56; margin-top:2px;">Returned player funds</div>
        </div>
        <div class="stat-card-mini" style="background:var(--ink); color:var(--white);">
          <div style="font-size:0.72rem; font-weight:800; font-family:'DM Mono', monospace; color:#a3b8b0;">NET REVENUE</div>
          <div style="font-size:1.6rem; font-weight:800; margin-top:4px; color:#eafc8d;" id="metric-net">₱0.00</div>
          <div style="font-size:0.72rem; color:#a3b8b0; margin-top:2px;">Net realized revenue</div>
        </div>
        <div class="stat-card-mini">
          <div style="font-size:0.72rem; font-weight:800; font-family:'DM Mono', monospace; color:var(--ink);">TOTAL TRANSACTIONS</div>
          <div style="font-size:1.6rem; font-weight:800; margin-top:4px;" id="metric-count">0</div>
          <div style="font-size:0.72rem; color:#4a5c56; margin-top:2px;">Recorded payment logs</div>
        </div>
      </div>

      <!-- TABLE & FILTERS CARD -->
      <div class="card-streetside" style="padding:24px;">
        <!-- FILTERS BAR -->
        <div style="margin-bottom:16px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
          <div style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
            <div>
              <label class="mono" style="display:block; font-size:0.68rem; margin-bottom:2px;">STATUS</label>
              <select id="filter-status" onchange="refreshPaymentsTable()" style="padding:6px 12px; border:2px solid var(--ink); border-radius:8px; font-weight:700; font-size:0.82rem;">
                <option value="all">All Statuses</option>
                <option value="completed">Completed</option>
                <option value="pending">Unpaid / Pending</option>
                <option value="failed">Failed / Refunded</option>
              </select>
            </div>
            <div>
              <label class="mono" style="display:block; font-size:0.68rem; margin-bottom:2px;">PAYMENT METHOD</label>
              <select id="filter-method" onchange="refreshPaymentsTable()" style="padding:6px 12px; border:2px solid var(--ink); border-radius:8px; font-weight:700; font-size:0.82rem;">
                <option value="all">All Methods</option>
                <option value="gcash">GCash</option>
                <option value="maya">Maya</option>
                <option value="card">Credit / Debit Card</option>
                <option value="cash">Cash (Over-the-counter)</option>
              </select>
            </div>
            <div>
              <label class="mono" style="display:block; font-size:0.68rem; margin-bottom:2px;">START DATE</label>
              <input type="date" id="filter-start-date" onchange="refreshPaymentsTable()" style="padding:5px 10px; border:2px solid var(--ink); border-radius:8px; font-weight:700; font-size:0.82rem; font-family:inherit;">
            </div>
            <div>
              <label class="mono" style="display:block; font-size:0.68rem; margin-bottom:2px;">END DATE</label>
              <input type="date" id="filter-end-date" onchange="refreshPaymentsTable()" style="padding:5px 10px; border:2px solid var(--ink); border-radius:8px; font-weight:700; font-size:0.82rem; font-family:inherit;">
            </div>
            <div style="align-self:flex-end;">
              <button onclick="clearDateFilters()" class="button sand" style="padding:6px 12px; font-size:0.75rem;" title="Clear Date Range"><i class="bi bi-x-circle"></i> Clear Dates</button>
            </div>
          </div>
        </div>

        <div style="overflow-x:auto; -webkit-overflow-scrolling:touch;">
          <table id="payments-datatable" style="width:100%; min-width:700px; text-align:left;">
            <thead>
              <tr>
                <th>BOOKING &amp; REF</th>
                <th>CUSTOMER</th>
                <th>AMOUNT</th>
                <th>STATUS &amp; METHOD</th>
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

  <!-- VIEW PAYMENT DETAIL MODAL -->
  <div class="modal-overlay" id="view-payment-modal">
    <div class="card-streetside" style="width:min(580px, 100%); padding:24px; background:var(--white);">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; border-bottom:2px solid var(--ink); padding-bottom:10px;">
        <h3 style="font-size:1.1rem; font-weight:800; text-transform:uppercase; margin:0;"><i class="bi bi-receipt"></i> TRANSACTION SUMMARY VOUCHER</h3>
        <button onclick="closeModal('view-payment-modal')" class="button sand" style="padding:2px 8px; font-size:0.8rem;">✕</button>
      </div>

      <div id="view-payment-content">
        <!-- Dynamically filled -->
      </div>

      <div style="display:flex; justify-content:flex-end; gap:8px; margin-top:20px;">
        <button type="button" onclick="closeModal('view-payment-modal')" class="button sand" style="padding:8px 16px; font-size:0.8rem;">Close</button>
        <button type="button" id="modal-print-btn" onclick="printSelectedReceipt()" class="button lime" style="padding:8px 18px; font-size:0.8rem;"><i class="bi bi-printer-fill"></i> Print Receipt</button>
      </div>
    </div>
  </div>

  <!-- PROCESS REFUND MODAL -->
  <div class="modal-overlay" id="refund-modal">
    <div class="card-streetside" style="width:min(460px, 100%); padding:24px; background:var(--white);">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; border-bottom:2px solid var(--ink); padding-bottom:10px;">
        <h3 style="font-size:1.1rem; font-weight:800; text-transform:uppercase; margin:0; color:#ef4444;"><i class="bi bi-arrow-return-left"></i> PROCESS PAYMENT REFUND</h3>
        <button onclick="closeModal('refund-modal')" class="button sand" style="padding:2px 8px; font-size:0.8rem;">✕</button>
      </div>

      <form id="refund-form">
        <input type="hidden" id="refund-payment-id">
        
        <div style="background:#fff1f1; border:2px solid #ef4444; border-radius:8px; padding:12px; margin-bottom:16px; font-size:0.82rem;">
          <div style="font-weight:800; text-transform:uppercase; color:#ef4444;">WARNING</div>
          <div>This action will issue a full refund, mark the payment transaction as refunded, and cancel the associated court booking.</div>
        </div>

        <div style="margin-bottom:12px;">
          <div style="font-size:0.75rem; font-weight:800; font-family:'DM Mono', monospace; color:#4a5c56;">BOOKING REF</div>
          <div id="refund-booking-ref" style="font-weight:800; font-family:'DM Mono', monospace; font-size:1rem;">—</div>
        </div>

        <div style="margin-bottom:14px;">
          <div style="font-size:0.75rem; font-weight:800; font-family:'DM Mono', monospace; color:#4a5c56;">REFUND AMOUNT</div>
          <div id="refund-amount-label" style="font-size:1.4rem; font-weight:800; color:#ef4444;">₱0.00</div>
        </div>

        <div style="margin-bottom:16px;">
          <label class="mono" style="display:block; margin-bottom:4px; font-size:0.72rem;">REFUND REASON *</label>
          <textarea id="refund-reason" required rows="3" placeholder="e.g. Customer cancelled court booking due to rainy weather..." style="width:100%; padding:8px; border:2px solid var(--ink); border-radius:8px; font-weight:700; font-family:inherit;"></textarea>
        </div>

        <div style="display:flex; justify-content:flex-end; gap:8px;">
          <button type="button" onclick="closeModal('refund-modal')" class="button sand" style="padding:8px 14px; font-size:0.8rem;">Cancel</button>
          <button type="submit" class="button coral" style="padding:8px 16px; font-size:0.8rem;"><i class="bi bi-arrow-return-left"></i> Confirm Refund</button>
        </div>
      </form>
    </div>
  </div>

  <?php require_once __DIR__ . '/../../includes/scripts.php'; ?>
  <script>
    let dataTable = null;
    let currentPaymentsMap = {};

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

      const canViewPayments = isSuperAdmin || perms.includes('payments.view') || perms.includes('payment.view') || perms.includes('payments.manage') || perms.includes('system.manage');

      if (!canViewPayments) {
        window.location.href = '/pikvero/public/403.php?permission=payments.view';
        return;
      }

      SidebarComponent.render('payments', (role === 'court_owner' || role === 'facility_manager' || role === 'receptionist') ? 'owner' : 'admin');
      FooterComponent.render('#footer-container', true);

      const canRefundPayment = isSuperAdmin || perms.includes('payment.refund') || perms.includes('payments.manage') || perms.includes('system.manage');

      // Initialize DataTables
      dataTable = $('#payments-datatable').DataTable({
        serverSide: true,
        processing: true,
        order: [[4, 'desc']], // default: latest first
        ajax: {
          url: '/pikvero/api/admin/payments.php',
          type: 'GET',
          data: function(d) {
            d.status = document.getElementById('filter-status').value;
            d.method = document.getElementById('filter-method').value;
            d.start_date = document.getElementById('filter-start-date').value;
            d.end_date = document.getElementById('filter-end-date').value;
          },
          dataSrc: function(json) {
            currentPaymentsMap = {};
            if (json.data) {
              json.data.forEach(p => { 
                currentPaymentsMap[p.record_type + '_' + p.payment_id] = p; 
              });
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
        columns: [
          {
            data: null,
            render: function(data, type, row) {
              const isOp = (row.record_type === 'open_play');
              const typeBadge = isOp 
                ? '<span class="badge-streetside coral" style="font-size:0.62rem; padding:2px 6px;"><i class="bi bi-dribbble"></i> OPEN PLAY</span>'
                : '<span class="badge-streetside sand" style="font-size:0.62rem; padding:2px 6px;"><i class="bi bi-calendar-check"></i> COURT BOOKING</span>';

              return `
                <div>
                  <div style="display:flex; align-items:center; gap:6px; flex-wrap:wrap; margin-bottom:2px;">
                    <strong style="font-family:'DM Mono', monospace; font-size:0.92rem; color:${isOp ? '#0b4d40' : '#2563eb'};">#${row.booking_reference}</strong>
                    ${typeBadge}
                  </div>
                  <div style="font-size:0.72rem; font-family:'DM Mono', monospace; color:#4a5c56;">Ref: ${row.transaction_reference || 'PAY-#' + row.payment_id}</div>
                </div>
              `;
            }
          },
          {
            data: null,
            render: function(data, type, row) {
              const name = row.customer_name || (row.first_name ? (row.first_name + ' ' + (row.last_name || '')).trim() : 'Guest Player');
              const subtext = row.session_title 
                ? `<span style="color:#0284c7; font-weight:700;"><i class="bi bi-dribbble"></i> ${row.session_title}</span>` 
                : (row.customer_email || '—');
              return `
                <div>
                  <strong>${name}</strong>
                  <div style="font-size:0.72rem; color:#4a5c56;">${subtext}</div>
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
              const m = (row.payment_method || 'cash').toLowerCase();
              const st = (row.payment_status || 'completed').toLowerCase();
              const bkSt = (row.booking_payment_status || '').toLowerCase();

              let icon = 'bi-cash-stack';
              let mBadgeClass = 'sand';
              let mText = 'CASH AT COURT';
              if (m === 'gcash') { icon = 'bi-wallet2'; mBadgeClass = 'sky'; mText = 'PAYMONGO (GCASH)'; }
              else if (m === 'maya' || m === 'paymaya') { icon = 'bi-wallet2'; mBadgeClass = 'sky'; mText = 'PAYMONGO (MAYA)'; }
              else if (m === 'card') { icon = 'bi-credit-card'; mBadgeClass = 'lime'; mText = 'PAYMONGO (CARD)'; }

              let stBadge = '<span class="badge-streetside lime" style="font-size:0.68rem;">COMPLETED</span>';
              if (st === 'failed' || bkSt === 'refunded') {
                stBadge = '<span class="badge-streetside coral" style="font-size:0.68rem;">REFUNDED</span>';
              } else if (st === 'pending' || bkSt === 'unpaid' || bkSt === 'pending') {
                stBadge = '<span class="badge-streetside sand" style="font-size:0.68rem;">UNPAID / PENDING</span>';
              }

              return `
                <div style="display:flex; gap:6px; align-items:center; flex-wrap:wrap;">
                  ${stBadge}
                  <span class="badge-streetside ${mBadgeClass}" style="font-size:0.68rem;"><i class="bi ${icon}"></i> ${mText}</span>
                </div>
              `;
            }
          },
          {
            data: 'created_at',
            render: function(data, type, row) {
              const val = data || row.payment_date;
              if (!val) return '<span style="color:#aaa;">—</span>';
              const dt = new Date(val);
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
              const isRefunded = (row.payment_status === 'failed' || row.booking_payment_status === 'refunded');
              return `
                <div style="display:flex; justify-content:flex-end; gap:4px;">
                  <button onclick="openViewModal('${row.record_type}', ${row.payment_id})" class="button lime action-btn" title="View Full Details & Voucher"><i class="bi bi-eye"></i> View</button>
                  ${canRefundPayment && !isRefunded ? `<button onclick="openRefundModal('${row.record_type}', ${row.payment_id})" class="button coral action-btn" title="Issue Refund"><i class="bi bi-arrow-return-left"></i> Refund</button>` : ''}
                </div>
              `;
            }
          }
        ]
      });

      // Submit Refund Form
      document.getElementById('refund-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const paymentId = document.getElementById('refund-payment-id').value;
        const reason = document.getElementById('refund-reason').value;

        try {
          const res = await Api.post('/pikvero/api/admin/payments/refund.php', {
            payment_id: paymentId,
            reason: reason
          });
          if (res.success) {
            Toast.success('Refund Processed', res.message);
            closeModal('refund-modal');
            dataTable.ajax.reload(null, false);
          }
        } catch (err) {
          console.error(err);
        }
      });
    });

    function refreshPaymentsTable() {
      if (dataTable) {
        dataTable.ajax.reload(null, false);
      }
    }

    function clearDateFilters() {
      document.getElementById('filter-start-date').value = '';
      document.getElementById('filter-end-date').value = '';
      refreshPaymentsTable();
    }

    let activeRecordType = null;
    let activePaymentId = null;

    function openViewModal(recordType, paymentId) {
      activeRecordType = recordType;
      activePaymentId = paymentId;
      const key = recordType + '_' + paymentId;
      const d = currentPaymentsMap[key];
      if (!d) return;

      const st = (d.payment_status || 'completed').toLowerCase();
      const bkSt = (d.booking_payment_status || '').toLowerCase();
      const isRefunded = (st === 'failed' || bkSt === 'refunded');
      const isOp = (d.record_type === 'open_play');

      const typeBadge = isOp 
        ? '<span class="badge-streetside coral" style="font-size:0.68rem;"><i class="bi bi-dribbble"></i> OPEN PLAY REGISTRATION</span>'
        : '<span class="badge-streetside sand" style="font-size:0.68rem;"><i class="bi bi-calendar-check"></i> COURT BOOKING</span>';

      const customerName = d.customer_name || (d.first_name ? (d.first_name + ' ' + (d.last_name || '')).trim() : 'Guest Player');

      document.getElementById('view-payment-content').innerHTML = `
        <!-- HEADER INFO -->
        <div style="background:#f8faf9; border:2px solid var(--ink); border-radius:10px; padding:14px; margin-bottom:14px;">
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
            <div>
              <div style="font-size:0.7rem; font-weight:800; font-family:'DM Mono', monospace; color:#4a5c56;">TRANSACTION REF</div>
              <div style="font-weight:800; font-family:'DM Mono', monospace; font-size:1.05rem; color:var(--ink);">${d.transaction_reference || 'PAY-#' + d.payment_id}</div>
            </div>
            <div style="text-align:right;">
              <div style="font-size:0.7rem; font-weight:800; font-family:'DM Mono', monospace; color:#4a5c56;">REFERENCE CODE</div>
              <div style="font-weight:800; font-family:'DM Mono', monospace; font-size:1.05rem; color:${isOp ? '#0b4d40' : '#2563eb'};">#${d.booking_reference}</div>
            </div>
          </div>
          <div style="display:flex; justify-content:space-between; align-items:center; border-top:1px dashed var(--line); padding-top:8px; font-size:0.78rem;">
            <span>${typeBadge}</span>
            <span>
              <span class="badge-streetside ${isRefunded ? 'coral' : 'lime'}" style="font-size:0.68rem;">${isRefunded ? 'REFUNDED / FAILED' : 'COMPLETED'}</span>
            </span>
          </div>
        </div>

        <!-- DETAILS GRID -->
        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px; font-size:0.83rem; margin-bottom:14px;">
          <!-- Customer Info -->
          <div style="background:#fdfdfd; padding:10px; border-radius:8px; border:1px solid var(--line);">
            <div style="font-size:0.7rem; font-weight:800; font-family:'DM Mono', monospace; color:#4a5c56;">PLAYER / CUSTOMER</div>
            <div style="font-weight:800; margin-top:2px;">${customerName}</div>
            <div style="font-size:0.75rem; color:#4a5c56;">${d.customer_email || 'No email provided'}</div>
            <div style="font-size:0.75rem; color:#4a5c56;">${d.customer_phone || 'No phone provided'}</div>
          </div>

          <!-- Venue Info -->
          <div style="background:#fdfdfd; padding:10px; border-radius:8px; border:1px solid var(--line);">
            <div style="font-size:0.7rem; font-weight:800; font-family:'DM Mono', monospace; color:#4a5c56;">FACILITY & VENUE</div>
            <div style="font-weight:800; margin-top:2px;">${d.facility_name || 'Main Location'}</div>
            <div style="font-size:0.75rem; color:#4a5c56;">Court / Session: <strong>${d.session_title || d.court_name || 'Court'}</strong></div>
          </div>

          <!-- Reservation Details -->
          <div style="background:#fdfdfd; padding:10px; border-radius:8px; border:1px solid var(--line);">
            <div style="font-size:0.7rem; font-weight:800; font-family:'DM Mono', monospace; color:#4a5c56;">RESERVATION SCHEDULE</div>
            <div style="font-weight:800; margin-top:2px;">Date: ${d.booking_date}</div>
            <div style="font-size:0.75rem; color:#4a5c56;">Time: <strong>${d.start_time} - ${d.end_time}</strong></div>
          </div>

          <!-- Financial Breakdown -->
          <div style="background:#fdfdfd; padding:10px; border-radius:8px; border:1px solid var(--line);">
            <div style="font-size:0.7rem; font-weight:800; font-family:'DM Mono', monospace; color:#4a5c56;">PAYMENT METHOD</div>
            <div style="font-weight:800; margin-top:2px; text-transform:uppercase;">${d.payment_method}</div>
            <div style="font-size:0.75rem; color:#4a5c56;">Payment Status: <strong>${(d.booking_payment_status || d.payment_status).toUpperCase()}</strong></div>
          </div>

          <!-- Date Recorded -->
          <div style="background:#eafc8d; padding:10px; border-radius:8px; border:1px solid var(--ink); grid-column:1/-1;">
            <div style="font-size:0.7rem; font-weight:800; font-family:'DM Mono', monospace; color:var(--ink);">DATE RECORDED (CREATED AT)</div>
            <div style="font-weight:800; margin-top:2px; font-size:0.95rem;">${(d.created_at || d.payment_date) ? new Date(d.created_at || d.payment_date).toLocaleString('en-PH', {dateStyle:'long', timeStyle:'short'}) : '—'}</div>
          </div>
        </div>

        <!-- TOTAL AMOUNT -->
        <div style="background:#eafc8d; border:2px solid var(--ink); border-radius:10px; padding:14px; text-align:center;">
          <div style="font-size:0.72rem; font-weight:800; font-family:'DM Mono', monospace; color:var(--ink);">NET TOTAL AMOUNT PAID</div>
          <div style="font-size:1.8rem; font-weight:800; margin-top:2px; color:var(--ink);">₱${parseFloat(d.amount).toFixed(2)}</div>
        </div>
      `;

      document.getElementById('view-payment-modal').classList.add('active');
    }

    function printSelectedReceipt(recordType, paymentId) {
      const rType = recordType || activeRecordType;
      const pid = paymentId || activePaymentId;
      if (!pid) return;

      const key = rType + '_' + pid;
      const d = currentPaymentsMap[key];
      if (d && d.record_type === 'open_play') {
        window.open(`/pikvero/public/open-play-receipt.php?registration_id=${d.payment_id}&from=admin_bookings`, '_blank');
      } else if (d) {
        window.open(`/pikvero/public/receipt.php?payment_id=${d.payment_id}`, '_blank');
      } else {
        window.open(`/pikvero/public/receipt.php?payment_id=${pid}`, '_blank');
      }
    }

    function openRefundModal(recordType, paymentId) {
      const key = recordType + '_' + paymentId;
      const p = currentPaymentsMap[key];
      if (!p) return;

      document.getElementById('refund-payment-id').value = p.payment_id;
      document.getElementById('refund-booking-ref').innerText = '#' + p.booking_reference;
      document.getElementById('refund-amount-label').innerText = '₱' + parseFloat(p.amount).toFixed(2);
      document.getElementById('refund-reason').value = '';

      document.getElementById('refund-modal').classList.add('active');
    }

    function exportPaymentsCsv() {
      Toast.success('Export Started', 'Exporting booking payments directory to CSV format...');
    }

    function closeModal(id) {
      const modal = document.getElementById(id);
      if (modal) modal.classList.remove('active');
    }
  </script>
</body>
</html>
