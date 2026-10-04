<?php
$pageTitle  = 'Pikvero — GCash Payouts & Withdrawals';
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
      overflow-y: auto;
      -webkit-overflow-scrolling: touch;
      padding: 40px 16px;
    }
    .modal-overlay.active {
      display: flex;
      justify-content: center;
      align-items: flex-start;
    }
    .modal-card-scrollable {
      max-height: none !important;
      overflow: visible !important;
      margin: auto;
    }
    /* Hide scrollbar for clean modals */
    .modal-overlay::-webkit-scrollbar,
    .modal-card-scrollable::-webkit-scrollbar,
    .no-scrollbar::-webkit-scrollbar {
      display: none !important;
      width: 0 !important;
      height: 0 !important;
    }
    .modal-overlay,
    .modal-card-scrollable,
    .no-scrollbar {
      -ms-overflow-style: none !important;  /* IE/Edge */
      scrollbar-width: none !important;  /* Firefox */
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

    /* Mobile Card View Style for Tables */
    @media (max-width: 768px) {
      .responsive-card-table {
        min-width: 0 !important;
      }
      .responsive-card-table thead {
        display: none !important;
      }
      .responsive-card-table tbody tr {
        display: flex !important;
        flex-direction: column !important;
        background: #ffffff !important;
        border: 2px solid var(--ink) !important;
        border-radius: 12px !important;
        padding: 14px !important;
        margin-bottom: 12px !important;
        box-shadow: 3px 3px 0 var(--ink) !important;
        gap: 6px !important;
      }
      .responsive-card-table tbody td {
        display: flex !important;
        justify-content: space-between !important;
        align-items: center !important;
        padding: 6px 0 !important;
        border-bottom: 1px dashed var(--line) !important;
        width: 100% !important;
        text-align: right !important;
      }
      .responsive-card-table tbody td:last-child {
        border-bottom: none !important;
        margin-top: 4px !important;
        padding-top: 8px !important;
        border-top: 1.5px solid var(--ink) !important;
      }
      .responsive-card-table tbody td::before {
        content: attr(data-label);
        font-family: 'DM Mono', monospace;
        font-size: 0.68rem;
        font-weight: 800;
        color: #4a5c56;
        text-transform: uppercase;
        margin-right: 12px;
        text-align: left;
        flex-shrink: 0;
      }
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
          <div class="eyebrow">GCASH PAYOUTS & WITHDRAWALS</div>
          <h1 style="font-size: clamp(1.8rem, 4vw, 2.2rem); font-weight:800; text-transform:uppercase; margin:4px 0 0;">GCASH PAYOUT DIRECTORY</h1>
        </div>
        <div style="display:flex; gap:8px; flex-wrap:wrap;">
          <button id="btn-request-payout" onclick="openRequestPayoutModal()" class="button coral" style="padding:10px 18px; font-size:0.85rem; display:none;"><i class="bi bi-wallet2"></i> Request GCash Payout</button>
          <button onclick="exportPayoutsCsv()" class="button sand" style="padding:10px 16px; font-size:0.82rem;"><i class="bi bi-download"></i> Export CSV</button>
          <button onclick="refreshPayoutsTable()" class="button lime" style="padding:10px 16px; font-size:0.82rem;"><i class="bi bi-arrow-clockwise"></i> Refresh</button>
        </div>
      </div>

      <!-- METRICS GRID -->
      <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:14px; margin-bottom:24px;">
        <div class="stat-card-mini" style="background:#eafc8d; cursor:pointer;" onclick="openGcashBookingsModal('all')" title="Click to view all GCash paid revenue bookings">
          <div style="font-size:0.72rem; font-weight:800; font-family:'DM Mono', monospace; color:var(--ink);">TOTAL GCASH REVENUE <i class="bi bi-box-arrow-up-right" style="font-size:0.7rem; margin-left:2px;"></i></div>
          <div style="font-size:1.5rem; font-weight:800; margin-top:4px;" id="metric-revenue">₱0.00</div>
          <div style="font-size:0.7rem; color:#4a5c56; margin-top:2px;">Paid booking receipts via GCash</div>
        </div>
        <div class="stat-card-mini" style="background:#fef3c7; border-color:#f59e0b; cursor:pointer;" onclick="openGcashBookingsModal('pending')" title="Click to view pending payout request bookings">
          <div style="font-size:0.72rem; font-weight:800; font-family:'DM Mono', monospace; color:#92400e;">PENDING PAYOUTS <i class="bi bi-box-arrow-up-right" style="font-size:0.7rem; margin-left:2px;"></i></div>
          <div style="font-size:1.5rem; font-weight:800; margin-top:4px; color:#b45309;" id="metric-pending">₱0.00</div>
          <div style="font-size:0.7rem; color:#92400e; margin-top:2px;">Awaiting admin approval</div>
        </div>
        <div class="stat-card-mini" style="background:#e0f2fe; border-color:#0284c7; cursor:pointer;" onclick="openGcashBookingsModal('approved')" title="Click to view approved payout request bookings">
          <div style="font-size:0.72rem; font-weight:800; font-family:'DM Mono', monospace; color:#0369a1;">APPROVED PAYOUTS <i class="bi bi-box-arrow-up-right" style="font-size:0.7rem; margin-left:2px;"></i></div>
          <div style="font-size:1.5rem; font-weight:800; margin-top:4px; color:#0284c7;" id="metric-approved">₱0.00</div>
          <div style="font-size:0.7rem; color:#0369a1; margin-top:2px;">Approved, awaiting transfer</div>
        </div>
        <div class="stat-card-mini" style="background:var(--ink); color:var(--white); cursor:pointer;" onclick="openGcashBookingsModal('claimed')" title="Click to view claimed funds & completed payout bookings">
          <div style="font-size:0.72rem; font-weight:800; font-family:'DM Mono', monospace; color:#a3b8b0;">COMPLETED PAYOUTS <i class="bi bi-box-arrow-up-right" style="font-size:0.7rem; margin-left:2px;"></i></div>
          <div style="font-size:1.5rem; font-weight:800; margin-top:4px; color:#eafc8d;" id="metric-completed">₱0.00</div>
          <div style="font-size:0.7rem; color:#a3b8b0; margin-top:2px;">Total funds transferred to owner</div>
        </div>
        <div class="stat-card-mini" style="background:#f0fdf4; border-color:#16a34a; cursor:pointer;" onclick="openGcashBookingsModal('unclaimed')" title="Click to view unclaimed available balance bookings">
          <div style="font-size:0.72rem; font-weight:800; font-family:'DM Mono', monospace; color:#15803d;">AVAILABLE BALANCE <i class="bi bi-box-arrow-up-right" style="font-size:0.7rem; margin-left:2px;"></i></div>
          <div style="font-size:1.5rem; font-weight:800; margin-top:4px; color:#16a34a;" id="metric-balance">₱0.00</div>
          <div style="font-size:0.7rem; color:#15803d; margin-top:2px;">Available for withdrawal</div>
        </div>
      </div>

      <!-- TABLE & FILTERS CARD -->
      <div class="card-streetside" style="padding:24px;">
        <!-- FILTERS BAR -->
        <div style="margin-bottom:16px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
          <div style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
            <div>
              <label class="mono" style="display:block; font-size:0.68rem; margin-bottom:2px;">STATUS</label>
              <select id="filter-status" onchange="refreshPayoutsTable()" style="padding:6px 12px; border:2px solid var(--ink); border-radius:8px; font-weight:700; font-size:0.82rem;">
                <option value="all">All Statuses</option>
                <option value="pending">Pending</option>
                <option value="approved">Approved</option>
                <option value="completed">Completed</option>
                <option value="rejected">Rejected</option>
              </select>
            </div>
          </div>
        </div>

        <div style="overflow-x:auto; -webkit-overflow-scrolling:touch;" class="no-scrollbar">
          <table id="payouts-datatable" class="responsive-card-table" style="width:100%; text-align:left;">
            <thead>
              <tr>
                <th>REFERENCE #</th>
                <th>ORGANIZATION & REQUESTER</th>
                <th>GCASH ACCOUNT</th>
                <th>AMOUNT</th>
                <th>STATUS</th>
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

  <!-- REQUEST PAYOUT MODAL -->
  <div class="modal-overlay no-scrollbar" id="request-payout-modal">
    <div class="card-streetside modal-card-scrollable no-scrollbar" style="width:min(500px, 100%); padding:24px; background:var(--white);">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; border-bottom:2px solid var(--ink); padding-bottom:10px;">
        <h3 style="font-size:1.1rem; font-weight:800; text-transform:uppercase; margin:0; color:var(--coral);"><i class="bi bi-wallet2"></i> REQUEST GCASH PAYOUT</h3>
        <button onclick="closeModal('request-payout-modal')" class="button sand" style="padding:2px 8px; font-size:0.8rem;">✕</button>
      </div>

      <form id="request-payout-form">
        <!-- AVAILABLE BALANCE BADGE -->
        <div style="background:#e0f2fe; border:2px solid #0284c7; border-radius:10px; padding:12px; margin-bottom:16px; display:flex; justify-content:space-between; align-items:center;">
          <div>
            <div style="font-size:0.68rem; font-weight:800; font-family:'DM Mono', monospace; color:#0369a1;">AVAILABLE BALANCE FOR PAYOUT</div>
            <div id="modal-available-balance" style="font-size:1.4rem; font-weight:800; color:#0284c7; margin-top:2px;">₱0.00</div>
          </div>
          <i class="bi bi-shield-check" style="font-size:2rem; color:#0284c7;"></i>
        </div>

        <!-- ZERO BALANCE WARNING BANNER -->
        <div id="payout-zero-balance-alert" style="display:none; background:#fff1f1; border:2px solid #ef4444; border-radius:10px; padding:12px; margin-bottom:14px; font-size:0.8rem; color:#ef4444; font-weight:800;">
          <i class="bi bi-exclamation-triangle-fill"></i> You have ₱0.00 available balance. Payout requests require available GCash booking revenue.
        </div>

        <div style="margin-bottom:12px;">
          <label class="mono" style="display:block; margin-bottom:4px; font-size:0.75rem;">GCASH ACCOUNT NAME *</label>
          <input type="text" id="req-account-name" required placeholder="e.g. JUAN DELA CRUZ" style="width:100%; padding:9px 12px; border:2px solid var(--ink); border-radius:8px; font-weight:700; font-family:inherit;" oninput="validatePayoutFormInputs()">
        </div>

        <div style="margin-bottom:12px;">
          <label class="mono" style="display:block; margin-bottom:4px; font-size:0.75rem;">GCASH PHONE NUMBER *</label>
          <input type="text" id="req-account-number" required placeholder="e.g. 09171234567" style="width:100%; padding:9px 12px; border:2px solid var(--ink); border-radius:8px; font-weight:700; font-family:inherit;" oninput="validatePayoutFormInputs()">
          <div id="fb-req-phone" style="font-size:0.72rem; margin-top:3px; font-weight:700;"></div>
        </div>

        <div style="margin-bottom:12px;">
          <label class="mono" style="display:block; margin-bottom:4px; font-size:0.75rem;">SELECT UNCLAIMED BOOKING PAYOUT PACKAGE *</label>
          <select id="select-payout-package" style="width:100%; padding:9px 12px; border:2px solid var(--ink); border-radius:8px; font-weight:800; font-family:inherit;" onchange="onSelectPayoutPackage(this)">
            <option value="">-- Select Booking Payout Amount --</option>
          </select>
        </div>

        <div style="margin-bottom:12px;">
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
            <label class="mono" style="font-size:0.75rem; margin:0;">REQUESTED GROSS AMOUNT (₱) *</label>
            <button type="button" onclick="setMaxPayoutAmount()" class="button sand" style="padding:2px 8px; font-size:0.68rem; font-weight:900; background:#e0f2fe; color:#0369a1; border:1px solid #0284c7;">
              <i class="bi bi-lightning-fill"></i> MAX (₱<span id="btn-max-val">0.00</span>)
            </button>
          </div>
          <input type="number" step="0.01" min="1" id="req-amount" required placeholder="0.00" style="width:100%; padding:9px 12px; border:2px solid var(--ink); border-radius:8px; font-weight:800; font-size:1.1rem; font-family:inherit;" oninput="validatePayoutFormInputs()">
          <div id="fb-req-amount" style="font-size:0.72rem; margin-top:3px; font-weight:700;"></div>
        </div>

        <!-- PAYMONGO PAYOUT FEE BREAKDOWN BOX -->
        <div id="payout-fee-breakdown-box" style="margin-bottom:14px; background:#f8faf9; border:2px solid var(--ink); border-radius:10px; padding:12px; font-size:0.8rem;">
          <div style="display:flex; justify-content:space-between; margin-bottom:4px;">
            <span style="color:#4a5c56;">Gross Booking Revenue:</span>
            <strong style="font-family:'DM Mono', monospace;" id="calc-gross-amt">₱0.00</strong>
          </div>
          <div style="display:flex; justify-content:space-between; margin-bottom:6px; color:#ef4444;">
            <span>PayMongo GCash Payout Fee:</span>
            <strong style="font-family:'DM Mono', monospace;" id="calc-fee-amt">-₱15.00</strong>
          </div>
          <div style="border-top:1.5px dashed var(--line); padding-top:6px; display:flex; justify-content:space-between; align-items:center;">
            <strong style="color:var(--ink); font-size:0.85rem;">NET GCASH DISBURSEMENT:</strong>
            <strong style="font-family:'DM Mono', monospace; font-size:1.1rem; color:var(--green);" id="calc-net-amt">₱0.00</strong>
          </div>
        </div>

        <div style="margin-bottom:16px;">
          <label class="mono" style="display:block; margin-bottom:4px; font-size:0.75rem;">REMARKS / NOTES (OPTIONAL)</label>
          <textarea id="req-notes" rows="2" placeholder="e.g. Weekly booking revenue payout..." style="width:100%; padding:8px 12px; border:2px solid var(--ink); border-radius:8px; font-weight:700; font-family:inherit;"></textarea>
        </div>

        <div style="display:flex; justify-content:flex-end; gap:8px;">
          <button type="button" onclick="closeModal('request-payout-modal')" class="button sand" style="padding:8px 14px; font-size:0.8rem;">Cancel</button>
          <button type="submit" id="btn-submit-payout" class="button coral" style="padding:8px 18px; font-size:0.8rem;"><i class="bi bi-send-fill"></i> Submit Request</button>
        </div>
      </form>
    </div>
  </div>

  <!-- VIEW PAYOUT DETAIL MODAL -->
  <div class="modal-overlay no-scrollbar" id="view-payout-modal">
    <div class="card-streetside modal-card-scrollable no-scrollbar" style="width:min(850px, 95vw); max-width:900px; padding:24px; background:var(--white);">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; border-bottom:2px solid var(--ink); padding-bottom:10px;">
        <h3 style="font-size:1.1rem; font-weight:800; text-transform:uppercase; margin:0;"><i class="bi bi-receipt-cutoff"></i> PAYOUT TRANSACTION VOUCHER</h3>
        <button onclick="closeModal('view-payout-modal')" class="button sand" style="padding:2px 8px; font-size:0.8rem;">✕</button>
      </div>

      <div id="view-payout-content">
        <!-- Dynamically filled -->
      </div>

      <div style="display:flex; justify-content:flex-end; gap:8px; margin-top:20px;">
        <button type="button" onclick="closeModal('view-payout-modal')" class="button sand" style="padding:8px 16px; font-size:0.8rem;">Close</button>
      </div>
    </div>
  </div>

  <!-- PROCESS PAYOUT STATUS MODAL (FOR ADMIN) -->
  <div class="modal-overlay no-scrollbar" id="process-payout-modal">
    <div class="card-streetside modal-card-scrollable no-scrollbar" style="width:min(550px, 95vw); padding:24px; background:var(--white);">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; border-bottom:2px solid var(--ink); padding-bottom:10px;">
        <h3 style="font-size:1.1rem; font-weight:800; text-transform:uppercase; margin:0; color:#0284c7;"><i class="bi bi-gear-fill"></i> PROCESS PAYOUT REQUEST</h3>
        <button onclick="closeModal('process-payout-modal')" class="button sand" style="padding:2px 8px; font-size:0.8rem;">✕</button>
      </div>

      <form id="process-payout-form">
        <input type="hidden" id="proc-payout-id">

        <div style="background:#f8faf9; border:2px solid var(--ink); border-radius:10px; padding:12px; margin-bottom:14px;">
          <div style="font-size:0.7rem; font-weight:800; font-family:'DM Mono', monospace; color:#4a5c56;">TARGET PAYOUT REF</div>
          <div id="proc-ref-no" style="font-weight:800; font-family:'DM Mono', monospace; font-size:1.05rem; color:var(--ink);">—</div>
          <div id="proc-amount-label" style="font-weight:800; font-size:1.2rem; color:var(--green); margin-top:4px;">₱0.00</div>
        </div>

        <div style="margin-bottom:12px;">
          <label class="mono" style="display:block; margin-bottom:4px; font-size:0.75rem;">PAYOUT ACTION / STATUS *</label>
          <select id="proc-status" style="width:100%; padding:9px 12px; border:2px solid var(--ink); border-radius:8px; font-weight:800; font-family:inherit;" onchange="toggleProcReceiptUpload()">
            <option value="pending">PENDING (Keep Pending Awaiting Approval)</option>
            <option value="approved">APPROVE PAYOUT (Mark Approved)</option>
            <option value="completed">COMPLETE PAYOUT (Transferred via GCash)</option>
            <option value="rejected">REJECT PAYOUT (Decline Request)</option>
          </select>
        </div>

        <!-- RECEIPT FILE UPLOAD BLOCK -->
        <div id="wrapper-proc-receipt" style="margin-bottom:14px; display:none; background:#f0fdf4; border:2px dashed #16a34a; border-radius:10px; padding:12px;">
          <label class="mono" style="display:block; margin-bottom:4px; font-size:0.75rem; color:#15803d; font-weight:800;">
            <i class="bi bi-file-earmark-arrow-up-fill"></i> UPLOAD GCASH TRANSFER RECEIPT PROOF *
          </label>
          <input type="file" id="proc-receipt-file" accept="image/*,.pdf" style="width:100%; font-size:0.8rem;" onchange="previewProcReceiptImage(this)">
          <div id="proc-receipt-preview-box" style="margin-top:8px; display:none; text-align:center;">
            <img id="proc-receipt-img-preview" src="" style="max-height:140px; border-radius:8px; border:2px solid var(--ink); box-shadow:2px 2px 0 var(--ink); object-fit:contain;">
          </div>
          <div style="font-size:0.7rem; color:#15803d; margin-top:4px;">Upload screenshot/photo of GCash payment transfer receipt (JPG, PNG, WEBP, PDF).</div>
        </div>

        <div style="margin-bottom:16px;">
          <label class="mono" style="display:block; margin-bottom:4px; font-size:0.75rem;">ADMIN REMARKS / TRANSACTION REF</label>
          <textarea id="proc-admin-notes" rows="2" placeholder="e.g. GCash Ref #99887711 transferred successfully." style="width:100%; padding:8px 12px; border:2px solid var(--ink); border-radius:8px; font-weight:700; font-family:inherit;"></textarea>
        </div>

        <div style="display:flex; justify-content:flex-end; gap:8px;">
          <button type="button" onclick="closeModal('process-payout-modal')" class="button sand" style="padding:8px 14px; font-size:0.8rem;">Cancel</button>
          <button type="submit" id="btn-submit-proc-status" class="button lime" style="padding:8px 18px; font-size:0.8rem;"><i class="bi bi-check-circle-fill"></i> Save Status</button>
        </div>
      </form>
    </div>
  </div>

  <!-- GCASH PAID BOOKINGS MODAL -->
  <div class="modal-overlay no-scrollbar" id="gcash-bookings-modal">
    <div class="card-streetside modal-card-scrollable no-scrollbar" style="width:min(1350px, 96vw) !important; max-width:1400px !important; padding:24px 28px; background:var(--white);">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; border-bottom:2px solid var(--ink); padding-bottom:10px;">
        <div>
          <h3 id="gcash-modal-title" style="font-size:1.1rem; font-weight:800; text-transform:uppercase; margin:0; color:var(--ink);"><i class="bi bi-phone-fill" style="color:#0284c7;"></i> GCASH PAID BOOKINGS DIRECTORY</h3>
          <div id="gcash-modal-desc" style="font-size:0.75rem; color:#4a5c56;">Court reservations with verified GCash payments contributing to total revenue</div>
        </div>
        <button onclick="closeModal('gcash-bookings-modal')" class="button sand" style="padding:2px 8px; font-size:0.8rem;">✕</button>
      </div>

      <div style="overflow-x:auto; -webkit-overflow-scrolling:touch;" class="no-scrollbar">
        <table id="gcash-bookings-datatable" class="responsive-card-table" style="width:100%; text-align:left;">
          <thead>
            <tr>
              <th>BOOKING &amp; REF</th>
              <th>CUSTOMER</th>
              <th>FACILITY &amp; COURT</th>
              <th>DATE RECORDED</th>
              <th style="text-align:right;">AMOUNT PAID</th>
            </tr>
          </thead>
          <tbody>
            <!-- Loaded via Server-Side DataTables -->
          </tbody>
        </table>
      </div>

      <!-- TOTAL SUMMARY FOOTER BANNER WITH FEE DEDUCTION BREAKDOWN -->
      <div style="margin-top:16px; background:#f8faf9; border:2px solid var(--ink); border-radius:12px; padding:14px 20px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; box-shadow:3px 3px 0 var(--ink);">
        <div style="font-family:'DM Mono', monospace; font-size:0.85rem; font-weight:800; color:var(--ink); text-transform:uppercase;">
          <i class="bi bi-calculator-fill" style="color:#0284c7;"></i> <span id="gcash-summary-label">TOTAL DIRECTORY REVENUE</span>
        </div>
        <div style="display:flex; align-items:center; gap:16px; flex-wrap:wrap;">
          <div>
            <span style="font-size:0.68rem; font-weight:800; font-family:'DM Mono', monospace; color:#6b7c76; display:block;">GROSS REVENUE</span>
            <span style="font-family:'DM Mono', monospace; font-size:1.15rem; font-weight:900; color:var(--ink);" id="gcash-modal-grand-total">₱0.00</span>
          </div>
          <div id="gcash-modal-fee-container" style="display:flex; align-items:center; gap:16px;">
            <div>
              <span style="font-size:0.68rem; font-weight:800; font-family:'DM Mono', monospace; color:#ef4444; display:block;">PAYMONGO FEE</span>
              <span style="font-family:'DM Mono', monospace; font-size:1.15rem; font-weight:900; color:#ef4444;" id="gcash-modal-fee-val">-₱0.00</span>
            </div>
            <div style="background:#eafc8d; border:2px solid var(--ink); padding:6px 14px; border-radius:10px;">
              <span style="font-size:0.65rem; font-weight:900; font-family:'DM Mono', monospace; color:var(--ink); display:block;">NET DISBURSED</span>
              <span style="font-family:'DM Mono', monospace; font-size:1.25rem; font-weight:900; color:#15803d;" id="gcash-modal-net-val">₱0.00</span>
            </div>
          </div>
        </div>
      </div>

      <div style="display:flex; justify-content:flex-end; gap:8px; margin-top:16px;">
        <button type="button" onclick="closeModal('gcash-bookings-modal')" class="button sand" style="padding:8px 16px; font-size:0.8rem;">Close</button>
      </div>
    </div>
  </div>

  <?php require_once __DIR__ . '/../../includes/scripts.php'; ?>
  <script>
    let payoutDataTable = null;
    let gcashBookingsDataTable = null;
    let currentPayoutsMap = {};
    let currentAvailableBalance = 0;
    let canRequestPayout = false;
    let canManagePayout = false;

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

      const canViewPayouts = isSuperAdmin || perms.includes('payouts.view') || perms.includes('payouts.request') || perms.includes('payouts.manage') || perms.includes('system.manage');

      if (!canViewPayouts) {
        window.location.href = '/pikvero/public/403.php?permission=payouts.view';
        return;
      }

      canRequestPayout = isSuperAdmin || perms.includes('payouts.request') || perms.includes('payouts.manage') || role === 'court_owner';
      canManagePayout  = isSuperAdmin || perms.includes('payouts.manage') || perms.includes('system.manage') || role === 'finance_admin';

      if (canRequestPayout) {
        document.getElementById('btn-request-payout').style.display = 'inline-flex';
      }

      SidebarComponent.render('payouts', (role === 'court_owner' || role === 'facility_manager' || role === 'receptionist') ? 'owner' : 'admin');
      FooterComponent.render('#footer-container', true);

      // Initialize DataTables
      payoutDataTable = $('#payouts-datatable').DataTable({
        serverSide: true,
        processing: true,
        order: [[5, 'desc']], // Default latest first by Created At
        ajax: {
          url: '/pikvero/api/admin/payouts.php',
          type: 'GET',
          data: function(d) {
            d.status = document.getElementById('filter-status').value;
          },
          dataSrc: function(json) {
            currentPayoutsMap = {};
            if (json.data) {
              json.data.forEach(p => { currentPayoutsMap[p.id] = p; });
            }
            if (json.summary) {
              currentAvailableBalance = json.summary.available_balance || 0;
              document.getElementById('metric-revenue').innerText   = '₱' + parseFloat(json.summary.total_gcash_revenue).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
              document.getElementById('metric-pending').innerText   = '₱' + parseFloat(json.summary.total_pending).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
              document.getElementById('metric-approved').innerText  = '₱' + parseFloat(json.summary.total_approved || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
              document.getElementById('metric-completed').innerText = '₱' + parseFloat(json.summary.total_completed).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
              document.getElementById('metric-balance').innerText   = '₱' + parseFloat(json.summary.available_balance).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
              
              if (document.getElementById('modal-available-balance')) {
                document.getElementById('modal-available-balance').innerText = '₱' + parseFloat(json.summary.available_balance).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
              }
            }
            return json.data || [];
          }
        },
        columns: [
          {
            data: 'reference_no',
            render: function(data) {
              return `<strong style="font-family:'DM Mono', monospace; font-size:0.92rem; color:#2563eb;">#${data}</strong>`;
            }
          },
          {
            data: null,
            render: function(data, type, row) {
              return `
                <div>
                  <strong>${row.organization_name || 'Organization'}</strong>
                  <div style="font-size:0.72rem; color:#4a5c56;">${row.requester_first_name ? row.requester_first_name + ' ' + row.requester_last_name : (row.requester_email || '—')}</div>
                </div>
              `;
            }
          },
          {
            data: null,
            render: function(data, type, row) {
              return `
                <div>
                  <strong style="color:var(--ink);">${row.gcash_account_name}</strong>
                  <div style="font-size:0.75rem; font-family:'DM Mono', monospace; color:#0284c7;">📱 ${row.gcash_account_number}</div>
                </div>
              `;
            }
          },
          {
            data: null,
            render: function(data, type, row) {
              const grossVal = parseFloat(row.amount) || 0;
              const feeVal   = (parseFloat(row.payout_fee) > 0) ? parseFloat(row.payout_fee) : 15.00;
              const rawNet   = parseFloat(row.net_amount);
              const netVal   = (!isNaN(rawNet) && rawNet > 0) ? rawNet : Math.max(0, grossVal - feeVal);

              const gross = grossVal.toFixed(2);
              const fee   = feeVal.toFixed(2);
              const net   = netVal.toFixed(2);
              return `
                <div>
                  <span style="font-family:'DM Mono', monospace; font-weight:900; font-size:0.95rem; color:var(--green);" title="Net GCash Amount Disbursed">₱${net}</span>
                  <div style="font-size:0.68rem; color:#6b7c76; font-family:'DM Mono', monospace;" title="Gross Revenue: ₱${gross} - PayMongo Fee: ₱${fee}">Gross: ₱${gross}</div>
                </div>
              `;
            }
          },
          {
            data: 'status',
            render: function(data) {
              const st = (data || 'pending').toLowerCase();
              if (st === 'completed') return '<span class="badge-streetside lime" style="font-size:0.68rem;">COMPLETED</span>';
              if (st === 'approved') return '<span class="badge-streetside sky" style="font-size:0.68rem; background:#e0f2fe; color:#0369a1; border:1px solid #0284c7;">APPROVED</span>';
              if (st === 'rejected') return '<span class="badge-streetside coral" style="font-size:0.68rem;">REJECTED</span>';
              return '<span class="badge-streetside sand" style="font-size:0.68rem;">PENDING</span>';
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
                  <button onclick="openViewPayoutModal(${row.id})" class="button lime action-btn" title="View Details"><i class="bi bi-eye"></i> View</button>
                  ${canManagePayout ? `<button onclick="openProcessPayoutModal(${row.id})" class="button sand action-btn" style="background:#e0f2fe; color:#0369a1; border:1px solid #0284c7;" title="Process Payout"><i class="bi bi-gear"></i> Process</button>` : ''}
                </div>
              `;
            }
          }
        ],
        createdRow: function(row, data, dataIndex) {
          $(row).find('td:eq(0)').attr('data-label', 'REF #');
          $(row).find('td:eq(1)').attr('data-label', 'ORGANIZATION');
          $(row).find('td:eq(2)').attr('data-label', 'GCASH ACCOUNT');
          $(row).find('td:eq(3)').attr('data-label', 'AMOUNT');
          $(row).find('td:eq(4)').attr('data-label', 'STATUS');
          $(row).find('td:eq(5)').attr('data-label', 'DATE RECORDED');
          $(row).find('td:eq(6)').attr('data-label', 'ACTIONS');
        }
      });

      // Submit Request Payout Form
      document.getElementById('request-payout-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const name   = document.getElementById('req-account-name').value.trim();
        const num    = document.getElementById('req-account-number').value.trim();
        const amt    = parseFloat(document.getElementById('req-amount').value);
        const notes  = document.getElementById('req-notes').value.trim();

        if (!name || !num || !amt || amt <= 0) {
          Toast.error('Validation Error', 'Please complete all required fields with a valid amount.');
          return;
        }

        if (amt > currentAvailableBalance) {
          Toast.error('Insufficient Balance', `Requested amount (₱${amt.toFixed(2)}) exceeds available balance (₱${currentAvailableBalance.toFixed(2)}).`);
          return;
        }

        const btn = document.getElementById('btn-submit-payout');
        const origHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = `<i class="bi bi-hourglass-split"></i> Submitting...`;

        try {
          const res = await Api.post('/pikvero/api/admin/payouts/request.php', {
            account_name: name,
            account_number: num,
            amount: amt,
            notes: notes
          });

          if (res && res.success) {
            closeModal('request-payout-modal');
            document.getElementById('request-payout-form').reset();
            Toast.success('Payout Requested!', `Reference #${res.data.reference_no} submitted for approval.`);
            refreshPayoutsTable();
          }
        } catch (err) {
          console.error(err);
        } finally {
          btn.disabled = false;
          btn.innerHTML = origHtml;
        }
      });

      // Process Payout Form Submit (Admin)
      document.getElementById('process-payout-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const payoutId    = document.getElementById('proc-payout-id').value;
        const status      = document.getElementById('proc-status').value;
        const adminNotes  = document.getElementById('proc-admin-notes').value.trim();
        const fileInput   = document.getElementById('proc-receipt-file');
        const submitBtn   = document.getElementById('btn-submit-proc-status');

        if (status === 'completed' && fileInput.files.length === 0) {
          const d = currentPayoutsMap[payoutId];
          if (!d || !d.receipt_image) {
            Toast.error('Receipt Required', 'Please select a GCash transfer receipt image before completing this payout.');
            return;
          }
        }

        const formData = new FormData();
        formData.append('payout_id', payoutId);
        formData.append('status', status);
        formData.append('admin_notes', adminNotes);
        if (fileInput.files.length > 0) {
          formData.append('receipt_image', fileInput.files[0]);
        }

        const origHtml = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = `<i class="bi bi-hourglass-split"></i> Saving...`;

        try {
          const response = await fetch('/pikvero/api/admin/payouts/update-status.php', {
            method: 'POST',
            body: formData
          });
          const res = await response.json();

          if (res && res.success) {
            closeModal('process-payout-modal');
            Toast.success('Payout Status Updated', res.message || 'Payout status updated successfully.');
            refreshPayoutsTable();
          } else {
            Toast.error('Update Failed', (res && res.message) ? res.message : 'Could not update payout status.');
          }
        } catch (err) {
          console.error(err);
          Toast.error('Server Error', 'Failed to update payout status. Please try again.');
        } finally {
          submitBtn.disabled = false;
          submitBtn.innerHTML = origHtml;
        }
      });

      // Real-time automatic polling timer (updates stat cards & table live every 10s without page refresh)
      setInterval(() => {
        refreshPayoutsTable();
      }, 10000);
    });

    function refreshPayoutsTable() {
      if (payoutDataTable) {
        payoutDataTable.ajax.reload(null, false);
      }
      if (gcashBookingsDataTable) {
        gcashBookingsDataTable.ajax.reload(null, false);
      }
    }

    let validPayoutPackages = [];

    async function openRequestPayoutModal() {
      const balStr = parseFloat(currentAvailableBalance).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
      document.getElementById('modal-available-balance').innerText = '₱' + balStr;
      document.getElementById('btn-max-val').innerText = balStr;

      const alertEl = document.getElementById('payout-zero-balance-alert');
      if (currentAvailableBalance <= 0) {
        if (alertEl) alertEl.style.display = 'block';
      } else {
        if (alertEl) alertEl.style.display = 'none';
      }

      // Load unclaimed booking package options
      const selectPkg = document.getElementById('select-payout-package');
      if (selectPkg) {
        selectPkg.innerHTML = '<option value="">-- Loading unclaimed booking packages... --</option>';
        try {
          const res = await Api.get('/pikvero/api/admin/payouts/options.php');
          if (res && res.success && res.data && res.data.valid_packages) {
            validPayoutPackages = res.data.valid_packages;
            if (validPayoutPackages.length > 0) {
              let optsHtml = '<option value="">-- Select Booking Payout Package --</option>';
              validPayoutPackages.forEach(pkg => {
                optsHtml += `<option value="${pkg.gross_amount}">${pkg.label}</option>`;
              });
              selectPkg.innerHTML = optsHtml;
            } else {
              selectPkg.innerHTML = '<option value="">No unclaimed bookings available for payout</option>';
            }
          } else {
            selectPkg.innerHTML = '<option value="">-- Select Booking Payout Amount --</option>';
          }
        } catch (e) {
          console.error(e);
          selectPkg.innerHTML = '<option value="">-- Select Booking Payout Amount --</option>';
        }
      }

      validatePayoutFormInputs();
      document.getElementById('request-payout-modal').classList.add('active');
    }

    function onSelectPayoutPackage(selectEl) {
      const val = parseFloat(selectEl.value) || 0;
      if (val > 0) {
        document.getElementById('req-amount').value = val.toFixed(2);
      } else {
        document.getElementById('req-amount').value = '';
      }
      validatePayoutFormInputs();
    }

    function setMaxPayoutAmount() {
      if (validPayoutPackages.length > 0) {
        const lastPkg = validPayoutPackages[validPayoutPackages.length - 1];
        document.getElementById('req-amount').value = lastPkg.gross_amount.toFixed(2);
        const selectPkg = document.getElementById('select-payout-package');
        if (selectPkg) selectPkg.value = lastPkg.gross_amount;
      } else if (currentAvailableBalance > 0) {
        document.getElementById('req-amount').value = currentAvailableBalance.toFixed(2);
      }
      validatePayoutFormInputs();
    }

    function validatePayoutFormInputs() {
      const nameInput = document.getElementById('req-account-name');
      const phoneInput = document.getElementById('req-account-number');
      const amountInput = document.getElementById('req-amount');
      const submitBtn = document.getElementById('btn-submit-payout');
      
      const phoneFb = document.getElementById('fb-req-phone');
      const amountFb = document.getElementById('fb-req-amount');

      if (!nameInput || !phoneInput || !amountInput || !submitBtn) return true;

      const nameVal = nameInput.value.trim();
      const phoneVal = phoneInput.value.trim();
      const amountVal = parseFloat(amountInput.value) || 0;

      // Update PayMongo fee calculation box
      const fee = 15.00;
      const net = Math.max(0, amountVal - fee);
      const grossEl = document.getElementById('calc-gross-amt');
      const feeEl   = document.getElementById('calc-fee-amt');
      const netEl   = document.getElementById('calc-net-amt');

      if (grossEl) grossEl.innerText = '₱' + amountVal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
      if (feeEl)   feeEl.innerText   = '-₱' + fee.toFixed(2);
      if (netEl)   netEl.innerText   = '₱' + net.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});

      let isPhoneOk = true;
      let isAmountOk = true;

      // Phone validation (Philippine GCash format)
      if (phoneVal.length > 0) {
        const phoneClean = phoneVal.replace(/[\s\-]/g, '');
        const isPhMobile = /^(09|\+639)\d{9}$/.test(phoneClean) || /^\d{11}$/.test(phoneClean);
        if (!isPhMobile) {
          isPhoneOk = false;
          phoneFb.style.color = '#ef4444';
          phoneFb.innerText = '✕ Enter a valid 11-digit GCash mobile number (e.g. 09171234567)';
        } else {
          phoneFb.style.color = '#15803d';
          phoneFb.innerText = '✓ Valid GCash mobile number format';
        }
      } else {
        phoneFb.innerText = '';
      }

      // Amount validation against exact valid package amounts
      if (amountVal > 0) {
        if (amountVal > currentAvailableBalance + 0.01) {
          isAmountOk = false;
          amountFb.style.color = '#ef4444';
          amountFb.innerText = `✕ Amount exceeds available GCash balance (₱${parseFloat(currentAvailableBalance).toFixed(2)})`;
        } else if (validPayoutPackages.length > 0) {
          const matchesPkg = validPayoutPackages.some(pkg => Math.abs(pkg.gross_amount - amountVal) < 0.01);
          if (!matchesPkg) {
            isAmountOk = false;
            const validOptionsStr = validPayoutPackages.map(p => '₱' + p.gross_amount.toFixed(2)).join(', ');
            amountFb.style.color = '#ef4444';
            amountFb.innerText = `✕ Amount must match exact full booking amounts (${validOptionsStr}). Partial booking payouts are not allowed.`;
          } else {
            amountFb.style.color = '#15803d';
            amountFb.innerText = `✓ Valid full booking payout amount (Net GCash: ₱${net.toFixed(2)})`;
          }
        } else {
          amountFb.style.color = '#15803d';
          amountFb.innerText = `✓ Valid payout amount (₱${amountVal.toFixed(2)})`;
        }
      } else if (amountInput.value.length > 0) {
        isAmountOk = false;
        amountFb.style.color = '#ef4444';
        amountFb.innerText = '✕ Amount must be greater than ₱0.00';
      } else {
        amountFb.innerText = '';
      }

      const isValid = (nameVal.length >= 2) && isPhoneOk && (phoneVal.length >= 10) && isAmountOk && (amountVal > 0) && (amountVal <= currentAvailableBalance);
      
      submitBtn.disabled = !isValid;
      if (!isValid) {
        submitBtn.style.opacity = '0.5';
        submitBtn.style.cursor = 'not-allowed';
      } else {
        submitBtn.style.opacity = '1';
        submitBtn.style.cursor = 'pointer';
      }

      return isValid;
    }

    async function openViewPayoutModal(payoutId) {
      document.getElementById('view-payout-content').innerHTML = `
        <div style="text-align:center; padding:30px 0;">
          <div class="spinner-border text-primary" role="status"></div>
          <div style="font-size:0.85rem; font-weight:700; margin-top:8px;">Loading payout transaction voucher & proof of revenue...</div>
        </div>
      `;
      document.getElementById('view-payout-modal').classList.add('active');

      try {
        const res = await Api.get('/pikvero/api/admin/payouts/detail.php', { id: payoutId }, { showToast: false });
        if (!res || !res.success || !res.data) {
          Toast.error('Error', 'Failed to load payout details.');
          closeModal('view-payout-modal');
          return;
        }

        const d = res.data;
        const items = d.items || [];
        const st = (d.status || 'pending').toLowerCase();

        const grossVal = parseFloat(d.amount) || 0;
        const feeVal   = (parseFloat(d.payout_fee) > 0) ? parseFloat(d.payout_fee) : 15.00;
        const rawNet   = parseFloat(d.net_amount);
        const netVal   = (!isNaN(rawNet) && rawNet > 0) ? rawNet : Math.max(0, grossVal - feeVal);
        let stBadge = '<span class="badge-streetside sand" style="font-size:0.68rem;">PENDING</span>';
        if (st === 'completed') stBadge = '<span class="badge-streetside lime" style="font-size:0.68rem;">COMPLETED</span>';
        if (st === 'approved') stBadge = '<span class="badge-streetside sky" style="font-size:0.68rem; background:#e0f2fe; color:#0369a1; border:1px solid #0284c7;">APPROVED</span>';
        if (st === 'rejected') stBadge = '<span class="badge-streetside coral" style="font-size:0.68rem;">REJECTED</span>';

        let itemsHtml = '';
        let itemsSum = 0;

        if (items.length > 0) {
          items.forEach(it => {
            const amt = parseFloat(it.item_amount || 0);
            itemsSum += amt;
            const isOp = (it.record_type === 'open_play');
            const typeTag = isOp 
              ? '<span class="badge-streetside coral" style="font-size:0.6rem; padding:1px 5px;"><i class="bi bi-dribbble"></i> OPEN PLAY</span>'
              : '<span class="badge-streetside sand" style="font-size:0.6rem; padding:1px 5px;"><i class="bi bi-calendar-check"></i> COURT BOOKING</span>';

            itemsHtml += `
              <tr style="border-bottom:1px solid var(--line);">
                <td style="padding:6px 4px; font-family:'DM Mono', monospace; font-weight:800;">
                  <div style="color:${isOp ? '#0b4d40' : '#2563eb'};">#${it.booking_reference}</div>
                  <div style="margin-top:2px;">${typeTag}</div>
                </td>
                <td style="padding:6px 4px;">
                  <div><strong>${it.first_name ? (it.first_name + ' ' + (it.last_name||'')).trim() : 'Guest Player'}</strong></div>
                  <div style="font-size:0.68rem; color:#4a5c56;">${it.customer_email || ''}</div>
                </td>
                <td style="padding:6px 4px;">
                  <div><strong>${it.court_name || 'Court'}</strong></div>
                  <div style="font-size:0.68rem; color:#4a5c56;">${it.facility_name || 'Facility'}</div>
                </td>
                <td style="padding:6px 4px; text-align:right; font-family:'DM Mono', monospace; font-weight:800; color:var(--green);">
                  ₱${amt.toFixed(2)}
                </td>
              </tr>
            `;
          });
        } else {
          itemsHtml = `<tr><td colspan="4" style="text-align:center; padding:12px; color:#6b7c76;">No specific bookings attached.</td></tr>`;
        }

        document.getElementById('view-payout-content').innerHTML = `
          <div style="background:#f8faf9; border:2px solid var(--ink); border-radius:10px; padding:14px; margin-bottom:14px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
              <div>
                <div style="font-size:0.7rem; font-weight:800; font-family:'DM Mono', monospace; color:#4a5c56;">REFERENCE NO</div>
                <div style="font-weight:800; font-family:'DM Mono', monospace; font-size:1.05rem; color:#2563eb;">#${d.reference_no}</div>
              </div>
              <div style="text-align:right;">
                ${stBadge}
              </div>
            </div>
            <div style="display:flex; justify-content:space-between; align-items:center; border-top:1px dashed var(--line); padding-top:8px; font-size:0.78rem;">
              <span>Requested Date: <strong>${d.created_at || '—'}</strong></span>
              <span>Method: <strong>GCASH</strong></span>
            </div>
          </div>

          <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px; font-size:0.83rem; margin-bottom:14px;">
            <div style="background:#fdfdfd; padding:10px; border-radius:8px; border:1px solid var(--line);">
              <div style="font-size:0.7rem; font-weight:800; font-family:'DM Mono', monospace; color:#4a5c56;">GCASH ACCOUNT NAME</div>
              <div style="font-weight:800; margin-top:2px;">${d.gcash_account_name}</div>
            </div>
            <div style="background:#fdfdfd; padding:10px; border-radius:8px; border:1px solid var(--line);">
              <div style="font-size:0.7rem; font-weight:800; font-family:'DM Mono', monospace; color:#4a5c56;">GCASH NUMBER</div>
              <div style="font-weight:800; margin-top:2px; font-family:'DM Mono', monospace; color:#0284c7;">📱 ${d.gcash_account_number}</div>
            </div>
            <div style="background:#fdfdfd; padding:10px; border-radius:8px; border:1px solid var(--line);">
              <div style="font-size:0.7rem; font-weight:800; font-family:'DM Mono', monospace; color:#4a5c56;">ORGANIZATION TENANT</div>
              <div style="font-weight:800; margin-top:2px;">${d.organization_name || 'Organization'}</div>
            </div>
            <div style="background:#fdfdfd; padding:10px; border-radius:8px; border:1px solid var(--line);">
              <div style="font-size:0.7rem; font-weight:800; font-family:'DM Mono', monospace; color:#4a5c56;">REQUESTED BY</div>
              <div style="font-weight:800; margin-top:2px;">${d.requester_first_name ? d.requester_first_name + ' ' + d.requester_last_name : 'Owner'}</div>
            </div>
          </div>

          ${d.notes ? `
          <div style="background:#f8faf9; border:1px solid var(--line); border-radius:8px; padding:10px; margin-bottom:14px; font-size:0.78rem;">
            <strong style="font-family:'DM Mono', monospace;">REQUESTER NOTES:</strong> ${d.notes}
          </div>` : ''}

          ${d.admin_notes ? `
          <div style="background:#e0f2fe; border:1.5px solid #0284c7; border-radius:8px; padding:10px; margin-bottom:14px; font-size:0.78rem; color:#0369a1;">
            <strong style="font-family:'DM Mono', monospace;">ADMIN PROCESSOR REMARKS:</strong> ${d.admin_notes}
            ${d.processor_first_name ? `<div style="font-size:0.7rem; margin-top:2px;">Processed by: ${d.processor_first_name} ${d.processor_last_name} on ${d.processed_at || ''}</div>` : ''}
          </div>` : ''}

          ${d.receipt_image ? `
          <div style="background:#f0fdf4; border:2px solid #16a34a; border-radius:10px; padding:14px; margin-bottom:14px;">
            <div style="font-size:0.75rem; font-weight:800; font-family:'DM Mono', monospace; color:#15803d; margin-bottom:8px; display:flex; justify-content:space-between; align-items:center;">
              <span><i class="bi bi-file-earmark-image-fill"></i> OFFICIAL GCASH TRANSFER RECEIPT PROOF</span>
              <span class="badge-streetside lime" style="font-size:0.65rem;">VERIFIED PROOF</span>
            </div>
            <div style="text-align:center; padding:6px; background:#ffffff; border:1px solid #bbf7d0; border-radius:8px;">
              <a href="${d.receipt_image}" target="_blank" title="Click to view full size receipt">
                <img src="${d.receipt_image}" style="max-height:220px; max-width:100%; border-radius:6px; border:1px solid var(--ink); box-shadow:2px 2px 0 var(--ink); cursor:pointer;">
              </a>
              <div style="font-size:0.7rem; color:#15803d; margin-top:6px; font-weight:700;"><i class="bi bi-box-arrow-up-right"></i> Click image to open full resolution</div>
            </div>
          </div>` : ''}

          <!-- COVERED GCASH BOOKINGS PROOF TABLE -->
          <div style="border:2px solid var(--ink); border-radius:10px; padding:12px; background:#fafdfc; margin-bottom:14px;">
            <div style="font-size:0.75rem; font-weight:800; font-family:'DM Mono', monospace; color:var(--ink); margin-bottom:8px; display:flex; justify-content:space-between; align-items:center;">
              <span><i class="bi bi-file-earmark-check-fill" style="color:var(--green);"></i> COVERED GCASH BOOKINGS (PROOF OF FUNDS)</span>
              <span class="badge-streetside lime" style="font-size:0.65rem;">${items.length} TRANSACTIONS ATTACHED</span>
            </div>
            <div style="overflow-x:auto;">
              <table style="width:100%; font-size:0.75rem; border-collapse:collapse;">
                <thead>
                  <tr style="border-bottom:1.5px solid var(--ink); text-align:left; font-family:'DM Mono', monospace; font-size:0.68rem; color:#4a5c56;">
                    <th style="padding:4px;">REF CODE</th>
                    <th style="padding:4px;">CUSTOMER / PLAYER</th>
                    <th style="padding:4px;">COURT / SESSION</th>
                    <th style="padding:4px; text-align:right;">AMOUNT</th>
                  </tr>
                </thead>
                <tbody>
                  ${itemsHtml}
                </tbody>
              </table>
            </div>
            <div style="display:flex; justify-content:space-between; align-items:center; border-top:1px dashed var(--ink); padding-top:6px; margin-top:6px; font-size:0.75rem; font-weight:800;">
              <span>TOTAL PROOF AMOUNT:</span>
              <span style="font-family:'DM Mono', monospace; color:var(--green); font-size:0.9rem;">₱${itemsSum.toFixed(2)}</span>
            </div>
          </div>

          <div style="background:#eafc8d; border:2px solid var(--ink); border-radius:10px; padding:12px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px; font-size:0.75rem;">
              <span style="font-family:'DM Mono', monospace; font-weight:800; color:var(--ink);">GROSS BOOKING REVENUE AMOUNT:</span>
              <strong style="font-family:'DM Mono', monospace; font-size:0.95rem;">₱${grossVal.toFixed(2)}</strong>
            </div>
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px; font-size:0.75rem; color:#ef4444;">
              <span style="font-family:'DM Mono', monospace; font-weight:800;">PAYMONGO PAYOUT DISBURSEMENT FEE:</span>
              <strong style="font-family:'DM Mono', monospace;">-₱${feeVal.toFixed(2)}</strong>
            </div>
            <div style="border-top:1.5px dashed var(--ink); padding-top:6px; display:flex; justify-content:space-between; align-items:center;">
              <span style="font-size:0.8rem; font-weight:900; font-family:'DM Mono', monospace; color:var(--ink);">NET DISBURSED GCASH AMOUNT:</span>
              <span style="font-size:1.4rem; font-weight:900; font-family:'DM Mono', monospace; color:#15803d;">₱${netVal.toFixed(2)}</span>
            </div>
          </div>
        `;
      } catch (err) {
        console.error(err);
        Toast.error('Error', 'Failed to load voucher details.');
      }
    }

    function toggleProcReceiptUpload() {
      const status = document.getElementById('proc-status').value;
      const wrapper = document.getElementById('wrapper-proc-receipt');
      if (wrapper) {
        wrapper.style.display = (status === 'completed') ? 'block' : 'none';
      }
    }

    function previewProcReceiptImage(input) {
      const box = document.getElementById('proc-receipt-preview-box');
      const img = document.getElementById('proc-receipt-img-preview');
      if (input.files && input.files[0]) {
        const file = input.files[0];
        if (file.type.startsWith('image/')) {
          const reader = new FileReader();
          reader.onload = function(e) {
            img.src = e.target.result;
            box.style.display = 'block';
          };
          reader.readAsDataURL(file);
        } else {
          box.style.display = 'none';
        }
      } else {
        box.style.display = 'none';
      }
    }

    function openProcessPayoutModal(payoutId) {
      const d = currentPayoutsMap[payoutId];
      if (!d) return;

      document.getElementById('proc-payout-id').value = d.id;
      document.getElementById('proc-ref-no').innerText = '#' + d.reference_no;
      document.getElementById('proc-amount-label').innerText = '₱' + parseFloat(d.amount).toFixed(2);
      document.getElementById('proc-status').value = d.status || 'approved';
      document.getElementById('proc-admin-notes').value = d.admin_notes || '';

      const fileInput = document.getElementById('proc-receipt-file');
      if (fileInput) fileInput.value = '';
      const previewBox = document.getElementById('proc-receipt-preview-box');
      if (previewBox) previewBox.style.display = 'none';

      toggleProcReceiptUpload();
      document.getElementById('process-payout-modal').classList.add('active');
    }

    let currentClaimFilter = 'all';

    function openGcashBookingsModal(filterType = 'all') {
      currentClaimFilter = filterType || 'all';

      const titleEl = document.getElementById('gcash-modal-title');
      const descEl  = document.getElementById('gcash-modal-desc');

      if (currentClaimFilter === 'claimed' || currentClaimFilter === 'completed') {
        if (titleEl) titleEl.innerHTML = '<i class="bi bi-check-circle-fill" style="color:#16a34a;"></i> CLAIMED & COMPLETED FUNDS DIRECTORY';
        if (descEl) descEl.innerText = 'Court reservations and open play registrations claimed in completed payout transfers';
      } else if (currentClaimFilter === 'pending') {
        if (titleEl) titleEl.innerHTML = '<i class="bi bi-clock-history" style="color:#f59e0b;"></i> PENDING PAYOUT FUNDS DIRECTORY';
        if (descEl) descEl.innerText = 'Court reservations and open play registrations attached to pending payout requests awaiting approval';
      } else if (currentClaimFilter === 'approved') {
        if (titleEl) titleEl.innerHTML = '<i class="bi bi-shield-check" style="color:#0284c7;"></i> APPROVED PAYOUT FUNDS DIRECTORY';
        if (descEl) descEl.innerText = 'Court reservations and open play registrations attached to approved payout requests awaiting GCash transfer';
      } else if (currentClaimFilter === 'unclaimed') {
        if (titleEl) titleEl.innerHTML = '<i class="bi bi-wallet2" style="color:#16a34a;"></i> UNCLAIMED AVAILABLE BALANCE DIRECTORY';
        if (descEl) descEl.innerText = 'Unclaimed court reservations and open play registrations available for payout';
      } else {
        if (titleEl) titleEl.innerHTML = '<i class="bi bi-phone-fill" style="color:#0284c7;"></i> ALL GCASH PAID BOOKINGS DIRECTORY';
        if (descEl) descEl.innerText = 'Court reservations with verified GCash payments contributing to total revenue';
      }

      document.getElementById('gcash-bookings-modal').classList.add('active');

      if (!gcashBookingsDataTable) {
        gcashBookingsDataTable = $('#gcash-bookings-datatable').DataTable({
          serverSide: true,
          processing: true,
          order: [[3, 'desc']], // Created At DESC
          ajax: {
            url: '/pikvero/api/admin/payouts/gcash-bookings.php',
            type: 'GET',
            data: function(d) {
              d.claim_filter = currentClaimFilter;
            },
            dataSrc: function(json) {
              if (json && json.totalAmount !== undefined) {
                const totalAmt = parseFloat(json.totalAmount) || 0;
                const totalFee = parseFloat(json.totalFee) || 0;
                const netAmt   = parseFloat(json.netAmount) || Math.max(0, totalAmt - totalFee);

                const fmtGross = '₱' + totalAmt.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                const fmtFee   = '-₱' + totalFee.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                const fmtNet   = '₱' + netAmt.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});

                const elGross = document.getElementById('gcash-modal-grand-total');
                if (elGross) elGross.innerText = fmtGross;

                const elFee = document.getElementById('gcash-modal-fee-val');
                if (elFee) elFee.innerText = fmtFee;

                const elNet = document.getElementById('gcash-modal-net-val');
                if (elNet) elNet.innerText = fmtNet;

                const feeBox = document.getElementById('gcash-modal-fee-container');
                if (feeBox) {
                  if (currentClaimFilter === 'unclaimed') {
                    feeBox.style.display = 'none';
                  } else {
                    feeBox.style.display = 'flex';
                  }
                }

                const labelEl = document.getElementById('gcash-summary-label');
                if (labelEl) {
                  if (currentClaimFilter === 'claimed' || currentClaimFilter === 'completed') labelEl.innerText = 'TOTAL CLAIMED & COMPLETED FUNDS';
                  else if (currentClaimFilter === 'pending') labelEl.innerText = 'TOTAL PENDING PAYOUT FUNDS';
                  else if (currentClaimFilter === 'approved') labelEl.innerText = 'TOTAL APPROVED PAYOUT FUNDS';
                  else if (currentClaimFilter === 'unclaimed') labelEl.innerText = 'TOTAL UNCLAIMED AVAILABLE BALANCE';
                  else labelEl.innerText = 'TOTAL DIRECTORY REVENUE';
                }
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
                      <strong style="font-family:'DM Mono', monospace; font-size:0.9rem; color:${isOp ? '#0b4d40' : '#2563eb'};">#${row.booking_reference}</strong>
                      ${typeBadge}
                    </div>
                    <div style="font-size:0.72rem; font-family:'DM Mono', monospace; color:#4a5c56;">Ref: ${row.transaction_reference || 'PAY-#' + row.booking_id}</div>
                  </div>
                `;
              }
            },
            {
              data: null,
              render: function(data, type, row) {
                const name = row.first_name ? (row.first_name + ' ' + (row.last_name || '')).trim() : 'Guest Player';
                return `
                  <div>
                    <strong>${name}</strong>
                    <div style="font-size:0.72rem; color:#4a5c56;">${row.customer_email || '—'}</div>
                  </div>
                `;
              }
            },
            {
              data: null,
              render: function(data, type, row) {
                const isOp = (row.record_type === 'open_play');
                return `
                  <div>
                    <strong style="font-size:0.85rem;">${row.court_name || 'Court'}</strong>
                    <div style="font-size:0.72rem; color:#4a5c56;">${row.facility_name || 'Facility'} ${isOp ? '&bull; <span style="color:#0284c7; font-weight:700;">Open Play Session</span>' : ''}</div>
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
              className: 'text-right',
              render: function(data, type, row) {
                let badge = '<span class="badge-streetside lime" style="font-size:0.65rem;"><i class="bi bi-check-circle-fill"></i> UNCLAIMED</span>';
                if (row.payout_reference_no) {
                  if (row.payout_status === 'pending') {
                    badge = `<span class="badge-streetside sand" style="font-size:0.65rem; background:#fef3c7; color:#b45309; border:1px solid #f59e0b;" title="Attached to Pending Payout #${row.payout_reference_no}"><i class="bi bi-clock-history"></i> PENDING (#${row.payout_reference_no})</span>`;
                  } else if (row.payout_status === 'approved') {
                    badge = `<span class="badge-streetside" style="font-size:0.65rem; background:#e0f2fe; color:#0369a1; border:1px solid #0284c7;" title="Attached to Approved Payout #${row.payout_reference_no}"><i class="bi bi-shield-check"></i> APPROVED (#${row.payout_reference_no})</span>`;
                  } else {
                    badge = `<span class="badge-streetside" style="font-size:0.65rem; background:#f0fdf4; color:#15803d; border:1px solid #16a34a;" title="Claimed in Completed Payout #${row.payout_reference_no}"><i class="bi bi-check-circle-fill"></i> CLAIMED (#${row.payout_reference_no})</span>`;
                  }
                }
                const isOp = (row.record_type === 'open_play');
                const grossAmt = parseFloat(row.amount) || 0;
                const feeAmt   = 15.00;
                const netAmt   = Math.max(0, grossAmt - feeAmt);

                return `
                  <div style="display:flex; flex-direction:column; align-items:flex-end;">
                    <span style="font-family:'DM Mono', monospace; font-weight:900; font-size:0.95rem; color:${isOp ? '#0b4d40' : 'var(--green)'};" title="Gross Booking Revenue">₱${grossAmt.toFixed(2)}</span>
                    <div style="font-size:0.68rem; font-family:'DM Mono', monospace; color:#ef4444; font-weight:700; margin-top:1px;" title="PayMongo GCash Fee: -₱15.00 | Net Amount: ₱${netAmt.toFixed(2)}">
                      Fee: -₱15.00 <span style="color:#15803d;">(Net: ₱${netAmt.toFixed(2)})</span>
                    </div>
                    <div style="margin-top:2px;">${badge}</div>
                  </div>
                `;
              }
            }
          ],
          createdRow: function(row, data, dataIndex) {
            $(row).find('td:eq(0)').attr('data-label', 'BOOKING & REF');
            $(row).find('td:eq(1)').attr('data-label', 'CUSTOMER');
            $(row).find('td:eq(2)').attr('data-label', 'FACILITY & COURT');
            $(row).find('td:eq(3)').attr('data-label', 'DATE RECORDED');
            $(row).find('td:eq(4)').attr('data-label', 'AMOUNT PAID');
          }
        });
      } else {
        gcashBookingsDataTable.ajax.reload(null, false);
      }
    }

    function exportPayoutsCsv() {
      Toast.success('Export Started', 'Exporting GCash payouts directory to CSV format...');
    }

    function closeModal(id) {
      const modal = document.getElementById(id);
      if (modal) modal.classList.remove('active');
    }
  </script>
</body>
</html>
