<?php
$pageTitle  = 'Pikvero — Manage Reservations';
$headExtras = [];
require_once __DIR__ . '/../../includes/head.php';
?>
<style>
  .card-streetside,
  .card-streetside:hover {
    transform: none !important;
  }
  .desktop-table-wrap {
    overflow: visible !important;
  }
  #owner-bookings-table-body tr {
    position: relative;
  }
  .row-actions-dropdown {
    position: relative !important;
    display: inline-block !important;
  }
  .row-actions-menu {
    position: absolute !important;
    z-index: 999999 !important;
    background: var(--white) !important;
    border: 2px solid var(--ink) !important;
    border-radius: 10px !important;
    box-shadow: 4px 4px 0 var(--ink), 0 10px 25px rgba(0,0,0,0.2) !important;
    min-width: 165px;
    padding: 4px 0;
    text-align: left;
  }
  .dropdown-item-btn {
    width: 100%;
    padding: 8px 14px;
    background: transparent;
    border: none;
    text-align: left;
    font-weight: 700;
    font-size: 0.78rem;
    font-family: inherit;
    color: var(--ink);
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: background 0.15s ease;
  }
  .dropdown-item-btn:hover {
    background: var(--cream);
  }
  .dropdown-item-btn.danger:hover {
    background: #fee2e2;
    color: #991b1b;
  }
  .dropdown-item-btn.warning:hover {
    background: #fef3c7;
    color: #92400e;
  }

  @media (max-width: 480px) {
    #add-booking-modal > div,
    #add-payment-modal > div {
      padding: 18px 14px !important;
    }
    #add-booking-form > div[style*="grid-template-columns"],
    #vb-body > div[style*="grid-template-columns"] {
      grid-template-columns: 1fr !important;
    }
    #view-booking-modal > div > div:first-child {
      padding: 16px 16px 12px !important;
    }
    #vb-body {
      padding: 16px 16px 20px !important;
    }
  }
</style>
<body>

  <aside id="sidebar-container"></aside>
  <header id="navbar-container"></header>

  <main class="portal-main">
    <div>
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:12px;">
        <div>
          <div class="eyebrow">RESERVATIONS MANAGEMENT</div>
          <h1 style="font-size: clamp(1.8rem, 4vw, 2.2rem); font-weight:800; text-transform:uppercase; margin:4px 0 0;">ALL RESERVATIONS</h1>
        </div>
        <div style="display:flex; gap:8px; flex-wrap:wrap;">
          <button id="btn-export-csv" onclick="triggerExportCSV()" class="button sand" style="padding:9px 14px; font-size:0.82rem; font-weight:800; display:none;" title="Export CSV Report">
            <i class="bi bi-download"></i> Export CSV
          </button>
          <button id="btn-print-report" onclick="triggerPrintReport()" class="button sand" style="background:#fef3c7; color:#92400e; border:2px solid #f59e0b; padding:9px 14px; font-size:0.82rem; font-weight:900; display:none;" title="Print Summary Report">
            <i class="bi bi-printer-fill"></i> Print Summary
          </button>
          <button id="btn-add-reservation" onclick="openAddBookingModal()" class="button coral" style="padding:9px 16px; font-size:0.82rem; display:none;">
            <i class="bi bi-plus-lg"></i> Add Reservation
          </button>
        </div>
      </div>

      <div class="card-streetside" style="padding:24px;">

        <!-- SERVER-SIDE DATATABLE TOOLBAR -->
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:18px; background:var(--sand); padding:14px; border-radius:12px; border:2px solid var(--ink);">
          
          <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
            <div style="display:flex; align-items:center; gap:6px;">
              <span class="mono" style="font-size:0.7rem;">SHOW</span>
              <select id="dt-per-page" style="padding:6px 10px; border:2px solid var(--ink); border-radius:8px; font-weight:700; font-family:inherit; cursor:pointer;">
                <option value="5">5</option>
                <option value="10" selected>10</option>
                <option value="25">25</option>
                <option value="50">50</option>
              </select>
              <span class="mono" style="font-size:0.7rem;">ENTRIES</span>
            </div>

            <div style="display:flex; align-items:center; gap:6px;">
              <span class="mono" style="font-size:0.7rem;">STATUS</span>
              <select id="dt-status-filter" style="padding:6px 10px; border:2px solid var(--ink); border-radius:8px; font-weight:700; font-family:inherit; cursor:pointer;">
                <option value="all">All Statuses</option>
                <option value="confirmed">Confirmed</option>
                <option value="pending">Pending</option>
                <option value="cancelled">Cancelled</option>
              </select>
            </div>

            <div style="display:flex; align-items:center; gap:6px;">
              <span class="mono" style="font-size:0.7rem;">TYPE</span>
              <select id="dt-type-filter" style="padding:6px 10px; border:2px solid var(--ink); border-radius:8px; font-weight:700; font-family:inherit; cursor:pointer;">
                <option value="all">All Types</option>
                <option value="court_booking">Court Reservations Only</option>
                <option value="open_play">Open Play Drop-Ins Only</option>
              </select>
            </div>
          </div>

          <div style="position:relative; width:min(320px, 100%);">
            <input type="text" id="dt-search-input" class="input-field" placeholder="Search reference, customer, court..." style="padding-right:36px; font-size:0.85rem;">
            <i class="bi bi-search" style="position:absolute; right:12px; top:50%; transform:translateY(-50%); color:#4a5c56;"></i>
          </div>

        </div>

        <!-- TABLE VIEW (DESKTOP & TABLET) -->
        <div class="desktop-table-wrap" style="overflow-x:auto; -webkit-overflow-scrolling:touch;">
          <table style="width:100%; min-width:650px; border-collapse:collapse; text-align:left; font-size:0.8rem;">
            <thead>
              <tr style="border-bottom:2px solid var(--ink); font-family:'DM Mono', monospace; font-size:0.68rem; background:#f8faf9;">
                <th style="padding:8px 10px;">REF # &amp; TYPE</th>
                <th style="padding:8px 10px;">CUSTOMER</th>
                <th style="padding:8px 10px;">COURT &amp; FACILITY</th>
                <th style="padding:8px 10px;">SCHEDULE</th>
                <th style="padding:8px 10px;">TOTAL</th>
                <th style="padding:8px 10px;">STATUS</th>
                <th style="padding:8px 10px; text-align:right;">ACTIONS</th>
              </tr>
            </thead>
            <tbody id="owner-bookings-table-body">
              <!-- Loaded via Server-Side AJAX -->
            </tbody>
          </table>
        </div>

        <!-- MOBILE CARDS VIEW (< 640px) -->
        <div id="owner-bookings-mobile" class="mobile-cards-wrap" style="margin-top:12px;">
          <!-- Loaded via Server-Side AJAX -->
        </div>

        <!-- SERVER-SIDE DATATABLE PAGINATION FOOTER -->
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-top:20px; padding-top:16px; border-top:2px solid var(--ink);">
          <div id="dt-counter-info" class="mono" style="font-size:0.75rem; color:var(--green); font-weight:700;">
            Showing 0 to 0 of 0 entries
          </div>

          <div id="dt-pagination-btns" style="display:flex; gap:4px; flex-wrap:wrap;">
            <!-- Rendered dynamically -->
          </div>
        </div>

      </div>
    </div>

    <footer id="footer-container"></footer>
  </main>

  <!-- Add Manual Reservation Modal -->
  <div id="add-booking-modal" style="
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(10,20,15,0.68);
      backdrop-filter: blur(6px);
      z-index: 1500;
      align-items: flex-start;
      justify-content: center;
      overflow-y: auto;
      padding: 24px 16px;
    ">
    <div class="card-streetside" style="
        background: var(--white);
        border: 3px solid var(--ink);
        border-radius: 20px;
        width: 100%;
        max-width: 560px;
        margin: auto;
        padding: 24px;
        position: relative;
        box-shadow: 8px 8px 0 var(--ink);
      ">
      
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; border-bottom:2px solid var(--ink); padding-bottom:12px;">
        <h3 style="margin:0; font-size:1.2rem; font-weight:900; text-transform:uppercase;">
          <i class="bi bi-calendar-plus-fill" style="color:var(--coral); margin-right:6px;"></i>ADD MANUAL RESERVATION
        </h3>
        <button onclick="closeAddBookingModal()" class="button sand" style="padding:2px 8px; font-size:0.8rem;">✕</button>
      </div>

      <form id="add-booking-form" novalidate>
        
        <!-- Select Court -->
        <div style="margin-bottom:12px;">
          <label style="display:block; margin-bottom:4px; font-family:'DM Mono', monospace; font-weight:800; font-size:0.78rem; text-transform:uppercase;">SELECT COURT *</label>
          <select id="ab_court_id" class="input-field" style="width:100%; padding:9px 12px; border:2px solid var(--ink); border-radius:8px; font-weight:700;" onchange="onCourtSelectChange()">
            <option value="">Loading courts...</option>
          </select>
        </div>

        <!-- Customer Live Search -->
        <div style="margin-bottom:12px; position:relative;">
          <label style="display:block; margin-bottom:4px; font-family:'DM Mono', monospace; font-weight:800; font-size:0.78rem; text-transform:uppercase;">CUSTOMER *</label>
          <input type="hidden" id="ab_customer_id" value="">
          <input type="text" id="ab_customer_search" class="input-field" placeholder="Type name or email to search customer..." style="width:100%; padding:9px 12px; border:2px solid var(--ink); border-radius:8px; font-weight:700;" autocomplete="off">
          <div id="ab_customer_dropdown" style="
              display:none;
              position:absolute;
              top:100%;
              left:0;
              right:0;
              background:var(--white);
              border:2px solid var(--ink);
              border-radius:8px;
              box-shadow:4px 4px 0 var(--ink);
              max-height:180px;
              overflow-y:auto;
              z-index:20;
              margin-top:4px;
            ">
          </div>
          <span id="ab_customer_selected_badge" style="display:none; font-size:0.75rem; font-weight:800; color:var(--green); margin-top:4px;">
            <i class="bi bi-check-circle-fill"></i> <span id="ab_customer_selected_text"></span>
          </span>
        </div>

        <!-- Date & Rate Grid -->
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:12px;">
          <div>
            <label style="display:block; margin-bottom:4px; font-family:'DM Mono', monospace; font-weight:800; font-size:0.78rem; text-transform:uppercase;">DATE *</label>
            <input type="date" id="ab_date" class="input-field" style="width:100%; padding:9px 12px; border:2px solid var(--ink); border-radius:8px; font-weight:700;" onchange="onTimeOrDateChange()">
          </div>
          <div>
            <label style="display:block; margin-bottom:4px; font-family:'DM Mono', monospace; font-weight:800; font-size:0.78rem; text-transform:uppercase;">HOURLY RATE (₱) *</label>
            <input type="number" step="10" id="ab_rate" class="input-field" value="400.00" style="width:100%; padding:9px 12px; border:2px solid var(--ink); border-radius:8px; font-weight:700;" oninput="calculateBookingTotal()">
          </div>
        </div>

        <!-- Time Range Grid -->
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:12px;">
          <div>
            <label style="display:block; margin-bottom:4px; font-family:'DM Mono', monospace; font-weight:800; font-size:0.78rem; text-transform:uppercase;">START TIME *</label>
            <input type="time" id="ab_start_time" class="input-field" value="08:00" style="width:100%; padding:9px 12px; border:2px solid var(--ink); border-radius:8px; font-weight:700;" onchange="onTimeOrDateChange()">
          </div>
          <div>
            <label style="display:block; margin-bottom:4px; font-family:'DM Mono', monospace; font-weight:800; font-size:0.78rem; text-transform:uppercase;">END TIME *</label>
            <input type="time" id="ab_end_time" class="input-field" value="10:00" style="width:100%; padding:9px 12px; border:2px solid var(--ink); border-radius:8px; font-weight:700;" onchange="onTimeOrDateChange()">
          </div>
        </div>

        <!-- Inline Real-Time Conflict / Availability Banner -->
        <div id="ab_availability_banner" style="
            display: none;
            margin-bottom: 12px;
            padding: 10px 14px;
            border-radius: 10px;
            font-size: 0.78rem;
            font-weight: 800;
            font-family: 'DM Mono', monospace;
            border: 2px solid var(--ink);
            align-items: center;
            gap: 8px;
          ">
          <div id="ab_availability_text" style="display:flex; align-items:center; gap:8px; width:100%;"></div>
        </div>

        <!-- Applied Rule Notification Banner -->
        <div id="ab_rule_badge" style="
            display: none;
            margin-bottom: 12px;
            background: #eafaf1;
            border: 2px solid var(--green);
            border-radius: 10px;
            padding: 10px 14px;
            font-size: 0.78rem;
            font-weight: 800;
            color: #2d6a4f;
            align-items: center;
            gap: 8px;
          ">
          <i class="bi bi-lightning-charge-fill" style="color:var(--green); font-size:1.1rem;"></i>
          <div id="ab_rule_badge_text">⚡ Applied Special Rule</div>
        </div>

        <!-- Pricing Rules List Display -->
        <div id="ab_pricing_rules_section" style="margin-bottom:14px; display:none;">
          <div style="font-family:'DM Mono', monospace; font-size:0.72rem; font-weight:900; text-transform:uppercase; color:#5a7060; margin-bottom:6px; display:flex; align-items:center; gap:6px;">
            <i class="bi bi-tags-fill" style="color:var(--green);"></i> COURT PRICING RULES
          </div>
          <div id="ab_pricing_rules_list" style="display:flex; flex-direction:column; gap:6px;">
            <!-- Loaded dynamically -->
          </div>
        </div>

        <!-- Reservation Source & Payment Status -->
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:12px;">
          <div>
            <label style="display:block; margin-bottom:4px; font-family:'DM Mono', monospace; font-weight:800; font-size:0.78rem; text-transform:uppercase;">RESERVATION SOURCE *</label>
            <select id="ab_booking_source" class="input-field" style="width:100%; padding:9px 12px; border:2px solid var(--ink); border-radius:8px; font-weight:800; font-family:'DM Mono',monospace;">
              <option value="Walk-in" selected>🚶 WALK-IN (Counter / On-Site)</option>
              <option value="Messenger">💬 MESSENGER (Facebook / Socials)</option>
              <option value="Phone Call">📞 PHONE / CALL</option>
              <option value="Website">🌐 WEBSITE / ONLINE</option>
            </select>
          </div>
          <div>
            <label style="display:block; margin-bottom:4px; font-family:'DM Mono', monospace; font-weight:800; font-size:0.78rem; text-transform:uppercase;">PAYMENT STATUS</label>
            <select id="ab_payment_status" class="input-field" style="width:100%; padding:9px 12px; border:2px solid var(--ink); border-radius:8px; font-weight:700;">
              <option value="paid" selected>PAID</option>
              <option value="unpaid">UNPAID</option>
              <option value="pending">PENDING</option>
            </select>
          </div>
        </div>

        <!-- Booking Status & Notes -->
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:16px;">
          <div>
            <label style="display:block; margin-bottom:4px; font-family:'DM Mono', monospace; font-weight:800; font-size:0.78rem; text-transform:uppercase;">BOOKING STATUS</label>
            <select id="ab_booking_status" class="input-field" style="width:100%; padding:9px 12px; border:2px solid var(--ink); border-radius:8px; font-weight:700;">
              <option value="confirmed" selected>CONFIRMED</option>
              <option value="pending">PENDING</option>
            </select>
          </div>
          <div>
            <label style="display:block; margin-bottom:4px; font-family:'DM Mono', monospace; font-weight:800; font-size:0.78rem; text-transform:uppercase;">NOTES / MEMO</label>
            <input type="text" id="ab_notes" class="input-field" placeholder="e.g. Cash payment at counter" style="width:100%; padding:9px 12px; border:2px solid var(--ink); border-radius:8px; font-weight:700;" value="Counter reservation">
          </div>
        </div>

        <!-- Total Calculation Summary Badge -->
        <div style="
            background: #f0fdf4;
            border: 2px solid var(--green);
            border-radius: 10px;
            padding: 12px 16px;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
          ">
          <div>
            <div style="font-size:0.7rem; font-weight:900; text-transform:uppercase; color:#2d6a4f; font-family:'DM Mono', monospace;">ESTIMATED TOTAL</div>
            <div id="ab_duration_text" style="font-size:0.75rem; color:#5a7060; font-weight:700;">2 hours @ ₱400.00/hr</div>
          </div>
          <div id="ab_total_price" style="font-size:1.3rem; font-weight:900; color:var(--green);">₱800.00</div>
        </div>

        <!-- Actions -->
        <div style="display:flex; justify-content:flex-end; gap:8px;">
          <button type="button" onclick="closeAddBookingModal()" class="button sand" style="padding:9px 16px; font-size:0.8rem;">Cancel</button>
          <button type="submit" id="ab_submit_btn" class="button lime" style="padding:9px 20px; font-size:0.82rem;"><i class="bi bi-check-lg"></i> Create Reservation</button>
        </div>

      </form>
    </div>
  </div>

  <!-- View Reservation Detail Modal (z-index 1500) -->
  <div id="view-booking-modal" style="
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(10,20,15,0.68);
      backdrop-filter: blur(6px);
      z-index: 1500;
      align-items: flex-start;
      justify-content: center;
      overflow-y: auto;
      padding: 24px 16px;
    ">
    <div style="
        background: var(--white);
        border: 3px solid var(--ink);
        border-radius: 20px;
        width: 100%;
        max-width: 620px;
        margin: auto;
        position: relative;
        box-shadow: 8px 8px 0 var(--ink);
      ">
      <!-- Header -->
      <div style="
          display: flex; align-items: center; justify-content: space-between;
          padding: 20px 26px 16px;
          background: var(--ink);
          border-radius: 17px 17px 0 0;
        ">
        <div>
          <div style="font-size:0.65rem;font-weight:900;text-transform:uppercase;letter-spacing:0.12em;color:#8db89a;font-family:'DM Mono',monospace;margin-bottom:3px;">
            Reservation Details
          </div>
          <h2 id="vb-ref-title" style="margin:0;font-size:1.2rem;font-weight:900;text-transform:uppercase;color:var(--white);">Loading...</h2>
        </div>
        <button onclick="closeViewBookingModal()" style="
            background:rgba(255,255,255,0.12);border:2px solid rgba(255,255,255,0.35);border-radius:50%;
            width:34px;height:34px;font-size:1.1rem;cursor:pointer;
            display:flex;align-items:center;justify-content:center;font-weight:900;color:var(--white);
          ">&times;</button>
      </div>
      <!-- Body -->
      <div id="vb-body" style="padding:22px 26px 26px;">
        <div style="text-align:center;padding:40px 0;color:#5a7060;">
          <i class="bi bi-arrow-clockwise spin" style="font-size:2rem;"></i>
          <p style="margin-top:8px;font-weight:700;">Loading reservation details...</p>
        </div>
      </div>
    </div>
  </div>

  <!-- Add / Update Payment Modal (z-index 1600) -->
  <div id="add-payment-modal" style="
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(10,20,15,0.72);
      backdrop-filter: blur(6px);
      z-index: 1600;
      align-items: center;
      justify-content: center;
      padding: 16px;
    ">
    <div class="card-streetside" style="
        background: var(--cream);
        border: 3px solid var(--ink);
        border-radius: 18px;
        width: 100%;
        max-width: 440px;
        padding: 24px;
        box-shadow: 6px 6px 0 var(--ink);
      ">
      
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; border-bottom:2px solid var(--ink); padding-bottom:10px;">
        <h3 style="margin:0; font-size:1.1rem; font-weight:900; text-transform:uppercase;">
          <i class="bi bi-credit-card-fill" style="color:var(--green); margin-right:6px;"></i>ADD / UPDATE PAYMENT
        </h3>
        <button onclick="closeAddPaymentModal()" class="button sand" style="padding:2px 8px; font-size:0.8rem;">✕</button>
      </div>

      <form id="add-payment-form" novalidate>
        <input type="hidden" id="ap_booking_id" value="">

        <div style="margin-bottom:12px; background:var(--white); border:2px solid var(--ink); border-radius:8px; padding:10px 14px;">
          <div style="font-size:0.7rem; font-weight:800; font-family:'DM Mono',monospace; color:#5a7060;">TARGET RESERVATION</div>
          <div id="ap_target_info" style="font-weight:900; font-size:0.95rem; color:var(--ink); margin-top:2px;">#MNL-XXXXXXXX</div>
        </div>

        <div style="margin-bottom:12px;">
          <label style="display:block; margin-bottom:4px; font-family:'DM Mono', monospace; font-weight:800; font-size:0.78rem; text-transform:uppercase;">PAYMENT STATUS *</label>
          <select id="ap_payment_status" class="input-field" style="width:100%; padding:9px 12px; border:2px solid var(--ink); border-radius:8px; font-weight:800; font-family:'DM Mono',monospace;">
            <option value="paid">PAID (Full Payment Received)</option>
            <option value="pending">PENDING (Deposit / Awaiting Confirmation)</option>
            <option value="unpaid">UNPAID (Payment Pending)</option>
          </select>
        </div>

        <div style="margin-bottom:18px;">
          <label style="display:block; margin-bottom:4px; font-family:'DM Mono', monospace; font-weight:800; font-size:0.78rem; text-transform:uppercase;">PAYMENT METHOD / NOTE</label>
          <input type="text" id="ap_note" class="input-field" placeholder="e.g. Cash at counter, GCash Ref #123456" style="width:100%; padding:9px 12px; border:2px solid var(--ink); border-radius:8px; font-weight:700;">
        </div>

        <div style="display:flex; justify-content:flex-end; gap:8px;">
          <button type="button" onclick="closeAddPaymentModal()" class="button sand" style="padding:8px 14px; font-size:0.8rem;">Cancel</button>
          <button type="submit" class="button lime" style="padding:8px 18px; font-size:0.8rem;"><i class="bi bi-floppy-fill"></i> Save Payment</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Cancel Reservation Confirmation Modal (z-index 1600) -->
  <div id="cancel-booking-modal" style="
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(10,20,15,0.72);
      backdrop-filter: blur(6px);
      z-index: 1600;
      align-items: center;
      justify-content: center;
      padding: 16px;
    ">
    <div class="card-streetside" style="
        background: var(--cream);
        border: 3px solid var(--ink);
        border-radius: 18px;
        width: 100%;
        max-width: 440px;
        padding: 24px;
        box-shadow: 6px 6px 0 var(--ink);
      ">
      
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; border-bottom:2px solid var(--ink); padding-bottom:10px;">
        <h3 style="margin:0; font-size:1.1rem; font-weight:900; text-transform:uppercase; color:var(--coral);">
          <i class="bi bi-x-circle-fill" style="margin-right:6px;"></i>CANCEL RESERVATION
        </h3>
        <button onclick="closeCancelBookingModal()" class="button sand" style="padding:2px 8px; font-size:0.8rem;">✕</button>
      </div>

      <form id="cancel-booking-form" novalidate>
        <input type="hidden" id="cb_booking_id" value="">

        <div style="margin-bottom:12px; background:var(--white); border:2px solid var(--ink); border-radius:8px; padding:10px 14px;">
          <div style="font-size:0.7rem; font-weight:800; font-family:'DM Mono',monospace; color:#5a7060;">TARGET RESERVATION</div>
          <div id="cb_target_info" style="font-weight:900; font-size:0.95rem; color:var(--ink); margin-top:2px;">#MNL-XXXXXXXX</div>
        </div>

        <div style="margin-bottom:18px;">
          <label style="display:block; margin-bottom:4px; font-family:'DM Mono', monospace; font-weight:800; font-size:0.78rem; text-transform:uppercase;">REASON FOR CANCELLATION</label>
          <input type="text" id="cb_reason" class="input-field" placeholder="e.g. Customer change of plans, Bad weather" style="width:100%; padding:9px 12px; border:2px solid var(--ink); border-radius:8px; font-weight:700;" value="Customer requested cancellation">
        </div>

        <div style="display:flex; justify-content:flex-end; gap:8px;">
          <button type="button" onclick="closeCancelBookingModal()" class="button sand" style="padding:8px 14px; font-size:0.8rem;">Keep Reservation</button>
          <button type="submit" class="button coral" style="padding:8px 18px; font-size:0.8rem;"><i class="bi bi-x-lg"></i> Confirm Cancel</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Process Refund Modal (z-index 1600) -->
  <div id="refund-booking-modal" style="
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(10,20,15,0.72);
      backdrop-filter: blur(6px);
      z-index: 1600;
      align-items: center;
      justify-content: center;
      padding: 16px;
    ">
    <div class="card-streetside" style="
        background: var(--cream);
        border: 3px solid var(--ink);
        border-radius: 18px;
        width: 100%;
        max-width: 440px;
        padding: 24px;
        box-shadow: 6px 6px 0 var(--ink);
      ">
      
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; border-bottom:2px solid var(--ink); padding-bottom:10px;">
        <h3 style="margin:0; font-size:1.1rem; font-weight:900; text-transform:uppercase; color:#d97706;">
          <i class="bi bi-arrow-counterclockwise" style="margin-right:6px;"></i>ISSUE REFUND
        </h3>
        <button onclick="closeRefundBookingModal()" class="button sand" style="padding:2px 8px; font-size:0.8rem;">✕</button>
      </div>

      <form id="refund-booking-form" novalidate>
        <input type="hidden" id="rf_booking_id" value="">

        <div style="margin-bottom:12px; background:var(--white); border:2px solid var(--ink); border-radius:8px; padding:10px 14px; display:flex; justify-content:space-between; align-items:center;">
          <div>
            <div style="font-size:0.7rem; font-weight:800; font-family:'DM Mono',monospace; color:#5a7060;">RESERVATION</div>
            <div id="rf_target_info" style="font-weight:900; font-size:0.95rem; color:var(--ink); margin-top:2px;">#MNL-XXXXXXXX</div>
          </div>
          <div style="text-align:right;">
            <div style="font-size:0.68rem; font-weight:800; font-family:'DM Mono',monospace; color:#5a7060;">REFUND AMOUNT</div>
            <div id="rf_amount_info" style="font-weight:900; font-size:1.1rem; color:#d97706;">₱0.00</div>
          </div>
        </div>

        <div style="margin-bottom:18px;">
          <label style="display:block; margin-bottom:4px; font-family:'DM Mono', monospace; font-weight:800; font-size:0.78rem; text-transform:uppercase;">REFUND METHOD / NOTE</label>
          <input type="text" id="rf_reason" class="input-field" placeholder="e.g. Cash refund, GCash return #998877" style="width:100%; padding:9px 12px; border:2px solid var(--ink); border-radius:8px; font-weight:700;" value="Full refund issued to customer">
        </div>

        <div style="display:flex; justify-content:flex-end; gap:8px;">
          <button type="button" onclick="closeRefundBookingModal()" class="button sand" style="padding:8px 14px; font-size:0.8rem;">Cancel</button>
          <button type="submit" class="button sand" style="background:#fef3c7; color:#92400e; border:2px solid #f59e0b; padding:8px 18px; font-size:0.8rem; font-weight:900;"><i class="bi bi-arrow-counterclockwise"></i> Process Refund</button>
        </div>
      </form>
    </div>
  </div>

  <?php require_once __DIR__ . '/../../includes/scripts.php'; ?>
  <script>
    let currentPage = 1;
    let currentLimit = 10;
    let currentSearch = '';
    let currentStatus = 'all';
    let currentType = 'all';
    let searchDebounceTimer = null;
    let customerSearchDebounce = null;
    let ownerCourtsMap = {};

    let canViewBookings = false;
    let canCreateBooking = false;
    let canManageBooking = false;
    let canCancelBooking = false;
    let canRefundBooking = false;
    let canExportBookings = false;
    let canPrintBookings = false;

    document.addEventListener('DOMContentLoaded', async () => {
      await NavbarComponent.render('#navbar-container', true);
      const userCtx = await AuthHelper.checkSession();
      SidebarComponent.render('bookings', userCtx && userCtx.role === 'court_owner' ? 'owner' : 'admin');
      FooterComponent.render('#footer-container', true);

      // Determine RBAC permissions strictly from assigned user/role permissions
      const role = userCtx ? (userCtx.role || (userCtx.user ? userCtx.user.role_name : '')) : '';
      const perms = (userCtx && userCtx.permissions) ? userCtx.permissions : [];
      const isSuperAdmin = (role === 'super_admin');

      canViewBookings   = isSuperAdmin || perms.includes('booking.view') || perms.includes('bookings.view') || perms.includes('bookings.manage');
      canCreateBooking  = isSuperAdmin || perms.includes('booking.create') || perms.includes('bookings.manage');
      canManageBooking  = isSuperAdmin || perms.includes('booking.update') || perms.includes('payment.create') || perms.includes('payments.manage') || perms.includes('bookings.manage');
      canCancelBooking  = isSuperAdmin || perms.includes('booking.cancel') || perms.includes('bookings.manage');
      canRefundBooking  = isSuperAdmin || perms.includes('payment.refund') || perms.includes('payments.manage') || perms.includes('bookings.manage');
      canExportBookings = isSuperAdmin || perms.includes('booking.export') || perms.includes('audit_logs.export');
      canPrintBookings  = isSuperAdmin || perms.includes('booking.print');

      if (!canViewBookings) {
        window.location.href = '/pikvero/public/403.php?permission=booking.view';
        return;
      }

      if (canCreateBooking)  document.getElementById('btn-add-reservation').style.display = 'inline-flex';
      if (canExportBookings) document.getElementById('btn-export-csv').style.display = 'inline-flex';
      if (canPrintBookings)  document.getElementById('btn-print-report').style.display = 'inline-flex';

      // Event listeners for Server-Side DataTable controls
      document.getElementById('dt-per-page').addEventListener('change', (e) => {
        currentLimit = parseInt(e.target.value);
        currentPage = 1;
        fetchServerData();
      });

      document.getElementById('dt-status-filter').addEventListener('change', (e) => {
        currentStatus = e.target.value;
        currentPage = 1;
        fetchServerData();
      });

      document.getElementById('dt-type-filter').addEventListener('change', (e) => {
        currentType = e.target.value;
        currentPage = 1;
        fetchServerData();
      });

      document.getElementById('dt-search-input').addEventListener('input', (e) => {
        clearTimeout(searchDebounceTimer);
        searchDebounceTimer = setTimeout(() => {
          currentSearch = e.target.value.trim();
          currentPage = 1;
          fetchServerData();
        }, 300);
      });

      // Customer search input listener
      document.getElementById('ab_customer_search').addEventListener('input', (e) => {
        const query = e.target.value.trim();
        document.getElementById('ab_customer_id').value = '';
        document.getElementById('ab_customer_selected_badge').style.display = 'none';

        clearTimeout(customerSearchDebounce);
        if (query.length < 2) {
          document.getElementById('ab_customer_dropdown').style.display = 'none';
          return;
        }

        customerSearchDebounce = setTimeout(async () => {
          try {
            const res = await Api.get('/pikvero/api/owner/customers/search.php', { q: query });
            if (res.success && res.data) {
              renderCustomerDropdown(res.data);
            }
          } catch (err) { console.error(err); }
        }, 250);
      });

      // Add Booking Form Submit
      document.getElementById('add-booking-form').addEventListener('submit', async (e) => {
        e.preventDefault();

        const courtId = document.getElementById('ab_court_id').value;
        const customerId = document.getElementById('ab_customer_id').value;
        const bookingDate = document.getElementById('ab_date').value;
        const startTime = document.getElementById('ab_start_time').value;
        const endTime = document.getElementById('ab_end_time').value;

        if (!courtId) { Toast.error('Validation Error', 'Please select a court.'); return; }
        if (!customerId) { Toast.error('Validation Error', 'Please search and select a customer.'); return; }
        if (!bookingDate) { Toast.error('Validation Error', 'Please select a booking date.'); return; }
        if (!startTime || !endTime) { Toast.error('Validation Error', 'Please specify start and end times.'); return; }
        if (startTime >= endTime) { Toast.error('Validation Error', 'End time must be after start time.'); return; }

        try {
          const res = await Api.post('/pikvero/api/owner/bookings/create.php', {
            court_id: courtId,
            customer_id: customerId,
            booking_date: bookingDate,
            start_time: startTime + ':00',
            end_time: endTime + ':00',
            booking_source: document.getElementById('ab_booking_source').value,
            rate_per_hour: document.getElementById('ab_rate').value,
            payment_status: document.getElementById('ab_payment_status').value,
            booking_status: document.getElementById('ab_booking_status').value,
            notes: document.getElementById('ab_notes').value.trim()
          });

          if (res.success) {
            Toast.success('Reservation Created', `Reference: ${res.data.booking_reference}`);
            closeAddBookingModal();
            fetchServerData();
          } else {
            Toast.error('Booking Failed', res.message || 'Could not create reservation.');
          }
        } catch (err) {
          console.error(err);
          Toast.error('Error', 'An unexpected error occurred while creating booking.');
        }
      });

      // Add Payment Form Submit
      document.getElementById('add-payment-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const bookingId = document.getElementById('ap_booking_id').value;
        const paymentStatus = document.getElementById('ap_payment_status').value;
        const note = document.getElementById('ap_note').value.trim();

        if (!bookingId) { Toast.error('Error', 'Invalid booking target.'); return; }

        try {
          const res = await Api.post('/pikvero/api/owner/bookings/payment.php', {
            booking_id: bookingId,
            payment_status: paymentStatus,
            note: note
          });

          if (res.success) {
            Toast.success('Payment Updated', `Booking payment status set to ${paymentStatus.toUpperCase()}.`);
            closeAddPaymentModal();
            fetchServerData();
            // If view modal is open, refresh it
            const viewModal = document.getElementById('view-booking-modal');
            if (viewModal.style.display === 'flex' && activeViewingBookingId === parseInt(bookingId)) {
              openViewBookingModal(bookingId);
            }
          } else {
            Toast.error('Update Failed', res.message || 'Could not update payment status.');
          }
        } catch (err) {
          console.error(err);
          Toast.error('Error', 'An error occurred while updating payment.');
        }
      });

      // Cancel Booking Form Submit
      document.getElementById('cancel-booking-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const bookingId = document.getElementById('cb_booking_id').value;
        const reason = document.getElementById('cb_reason').value.trim();

        if (!bookingId) { Toast.error('Error', 'Invalid booking target.'); return; }

        try {
          const res = await Api.post('/pikvero/api/owner/bookings/cancel.php', {
            booking_id: bookingId,
            reason: reason
          });

          if (res.success) {
            Toast.success('Reservation Cancelled', 'The reservation status was updated to CANCELLED.');
            closeCancelBookingModal();
            fetchServerData();
            if (document.getElementById('view-booking-modal').style.display === 'flex' && activeViewingBookingId === parseInt(bookingId)) {
              openViewBookingModal(bookingId);
            }
          } else {
            Toast.error('Cancellation Failed', res.message || 'Could not cancel reservation.');
          }
        } catch (err) {
          console.error(err);
          Toast.error('Error', 'An error occurred while cancelling reservation.');
        }
      });

      // Refund Booking Form Submit
      document.getElementById('refund-booking-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const bookingId = document.getElementById('rf_booking_id').value;
        const reason = document.getElementById('rf_reason').value.trim();

        if (!bookingId) { Toast.error('Error', 'Invalid booking target.'); return; }

        try {
          const res = await Api.post('/pikvero/api/owner/bookings/refund.php', {
            booking_id: bookingId,
            reason: reason
          });

          if (res.success) {
            Toast.success('Refund Processed', 'Reservation status updated to REFUNDED.');
            closeRefundBookingModal();
            fetchServerData();
            if (document.getElementById('view-booking-modal').style.display === 'flex' && activeViewingBookingId === parseInt(bookingId)) {
              openViewBookingModal(bookingId);
            }
          } else {
            Toast.error('Refund Failed', res.message || 'Could not process refund.');
          }
        } catch (err) {
          console.error(err);
          Toast.error('Error', 'An error occurred while processing refund.');
        }
      });

      fetchServerData();
    });

    let activeViewingBookingId = null;

    function toggleRowActionsDropdown(event, menuId) {
      event.stopPropagation();
      const menu = document.getElementById(menuId);
      if (!menu) return;

      const isVisible = (menu.style.display === 'block');
      closeAllActionDropdowns();

      if (!isVisible) {
        const btn = event.currentTarget;
        const tr = btn.closest('tr');
        const dropdownWrap = btn.closest('.row-actions-dropdown');

        menu.style.display = 'block';

        if (tr) {
          tr.style.position = 'relative';
          tr.style.zIndex = '999999';
        }
        if (dropdownWrap) {
          dropdownWrap.style.position = 'relative';
          dropdownWrap.style.zIndex = '999999';
        }

        const btnRect = btn.getBoundingClientRect();
        const spaceBelow = window.innerHeight - btnRect.bottom;

        if (spaceBelow < 200) {
          // Open UPWARDS above button
          menu.style.top = 'auto';
          menu.style.bottom = 'calc(100% + 4px)';
        } else {
          // Open DOWNWARDS below button
          menu.style.top = 'calc(100% + 4px)';
          menu.style.bottom = 'auto';
        }
        menu.style.left = 'auto';
        menu.style.right = '0';
      }
    }

    function closeAllActionDropdowns() {
      document.querySelectorAll('.row-actions-menu').forEach(m => {
        m.style.display = 'none';
        const tr = m.closest('tr');
        if (tr) tr.style.zIndex = '';
        const dropdownWrap = m.closest('.row-actions-dropdown');
        if (dropdownWrap) dropdownWrap.style.zIndex = '';
      });
    }

    document.addEventListener('click', closeAllActionDropdowns);
    window.addEventListener('resize', closeAllActionDropdowns);

    function triggerExportCSV() {
      window.location.href = '/pikvero/public/admin/bookings/export.php?search=' + encodeURIComponent(currentSearch) + '&status=' + encodeURIComponent(currentStatus);
    }

    function triggerPrintReport() {
      window.open('/pikvero/public/admin/bookings/print-list.php?search=' + encodeURIComponent(currentSearch) + '&status=' + encodeURIComponent(currentStatus) + '&autoprint=1', '_blank');
    }

    function triggerPrintVoucher(bookingId) {
      window.open('/pikvero/public/admin/bookings/print.php?id=' + bookingId + '&autoprint=1', '_blank');
    }

    function getReservationChannel(b) {
      const ref = (b.booking_reference || '').toUpperCase();
      const notes = (b.notes || '').toLowerCase();

      if (notes.includes('messenger') || notes.includes('facebook') || notes.includes('fb') || notes.includes('source: messenger')) {
        return { label: 'MESSENGER', icon: 'bi-messenger', bg: '#e8f2ff', color: '#0064d1', border: '#0084ff' };
      }
      if (ref.startsWith('MNL') || notes.includes('walk-in') || notes.includes('source: walk-in')) {
        return { label: 'WALK-IN', icon: 'bi-person-badge-fill', bg: '#fef3c7', color: '#92400e', border: '#f59e0b' };
      }
      if (notes.includes('phone') || notes.includes('source: phone')) {
        return { label: 'PHONE CALL', icon: 'bi-telephone-fill', bg: '#e0e7ff', color: '#3730a3', border: '#6366f1' };
      }
      return { label: 'WEBSITE', icon: 'bi-globe2', bg: '#e0e2fe', color: '#075985', border: '#0284c7' };
    }

    function getBookingStatusBadge(status) {
      const s = (status || 'pending').toLowerCase();
      if (s === 'confirmed') return '<span class="badge-streetside green" style="font-size:0.63rem;">CONFIRMED</span>';
      if (s === 'refunded') return '<span class="badge-streetside sand" style="font-size:0.63rem; background:#fef3c7; color:#92400e; border:1px solid #f59e0b;">REFUNDED</span>';
      if (s === 'cancelled') return '<span class="badge-streetside coral" style="font-size:0.63rem;">CANCELLED</span>';
      if (s === 'completed') return '<span class="badge-streetside lime" style="font-size:0.63rem;">COMPLETED</span>';
      return `<span class="badge-streetside sand" style="font-size:0.63rem;">${escapeHtml(s.toUpperCase())}</span>`;
    }

    function getPaymentStatusBadge(status) {
      const s = (status || 'unpaid').toLowerCase();
      if (s === 'paid') return '<span class="badge-streetside lime" style="font-size:0.63rem;">PAY: PAID</span>';
      if (s === 'refunded') return '<span class="badge-streetside sand" style="font-size:0.63rem; background:#fef3c7; color:#92400e; border:1px solid #f59e0b;">PAY: REFUNDED</span>';
      if (s === 'pending') return '<span class="badge-streetside sand" style="font-size:0.63rem;">PAY: PENDING</span>';
      return '<span class="badge-streetside coral" style="font-size:0.63rem;">PAY: UNPAID</span>';
    }

    async function fetchServerData() {
      const tbody = document.getElementById('owner-bookings-table-body');
      const mobileContainer = document.getElementById('owner-bookings-mobile');

      tbody.innerHTML = `
        <tr>
          <td colspan="7" style="padding:24px; text-align:center;">
            <div class="mono" style="font-size:0.85rem; font-weight:700;"><i class="bi bi-arrow-repeat spin"></i> Loading bookings data...</div>
          </td>
        </tr>
      `;

      try {
        const res = await Api.get('/pikvero/api/owner/bookings.php', {
          page: currentPage,
          limit: currentLimit,
          search: currentSearch,
          status: currentStatus,
          res_type: currentType
        });

        if (res.success && res.data) {
          const { data, total, page, limit, total_pages } = res.data;

          renderTableRows(data, tbody, mobileContainer);
          renderPagination(page, total_pages, total, limit);
        }
      } catch (e) {
        console.error(e);
        tbody.innerHTML = `<tr><td colspan="8" style="padding:16px; text-align:center; color:var(--coral);">Failed to load server data.</td></tr>`;
      }
    }

    function renderTableRows(records, tbody, mobileContainer) {
      if (!records || records.length === 0) {
        const emptyHtml = `
          <div class="card-streetside sand" style="max-width:480px; margin:0 auto; text-align:center; padding:28px 20px;">
            <div class="brand-mark" style="width:48px; height:48px; font-size:1.5rem; margin:0 auto 12px; background:var(--sky);">
              <i class="bi bi-calendar-x"></i>
            </div>
            <h4 style="font-size:1.15rem; font-weight:800; text-transform:uppercase; margin:0 0 6px;">NO BOOKING RECORDS FOUND</h4>
            <p style="font-size:0.85rem; color:#3b4e48; margin:0; line-height:1.4;">
              No reservations match your current search or filter criteria.
            </p>
          </div>
        `;

        tbody.innerHTML = `<tr><td colspan="8" style="padding:40px 16px; text-align:center;">${emptyHtml}</td></tr>`;
        mobileContainer.innerHTML = emptyHtml;
        return;
      }

      // Desktop Table
      tbody.innerHTML = records.map(b => {
        const ch = getReservationChannel(b);
        const isCancelledOrRefunded = b.booking_status === 'cancelled' || b.booking_status === 'refunded';
        const canCancelRow = canCancelBooking && !isCancelledOrRefunded;
        const canRefundRow = canRefundBooking && !isCancelledOrRefunded && b.payment_status === 'paid';
        const canPayRow = canManageBooking && !isCancelledOrRefunded && b.payment_status !== 'paid' && b.payment_status !== 'refunded';
        const isOpenPlay = (b.reservation_type === 'open_play');

        const typeBadge = isOpenPlay ?
          `<span class="badge-streetside coral" style="font-size:0.6rem; padding:1px 5px;"><i class="bi bi-dribbble"></i> OPEN PLAY</span>` :
          `<span class="badge-streetside lime" style="font-size:0.6rem; padding:1px 5px;"><i class="bi bi-calendar-check-fill"></i> COURT BOOKING</span>`;

        return `
        <tr style="border-bottom:1px solid var(--line);">
          <td style="padding:8px 10px;">
            <strong style="font-family:'DM Mono', monospace; font-size:0.85rem; color:${isOpenPlay ? '#0b4d40' : '#2563eb'};">#${b.booking_reference}</strong>
            <div style="margin-top:2px;">${typeBadge}</div>
          </td>
          <td style="padding:8px 10px;">
            <strong style="color:var(--ink); font-size:0.82rem;">${escapeHtml(b.customer_name)}</strong>
            <div style="font-size:0.7rem; color:#5a7060;">${escapeHtml(b.customer_email)}</div>
          </td>
          <td style="padding:8px 10px;">
            <strong style="font-size:0.82rem;">${escapeHtml(b.court_name)}</strong>
            <div style="font-size:0.7rem; color:#5a7060;">${escapeHtml(b.facility_name)}</div>
          </td>
          <td style="padding:8px 10px;">
            <div style="font-weight:700; font-size:0.8rem;">${b.booking_date}</div>
            <div style="font-size:0.72rem; color:#5a7060; font-family:'DM Mono',monospace;">${formatTimeStr(b.start_time)} – ${formatTimeStr(b.end_time)}</div>
          </td>
          <td style="padding:8px 10px; font-weight:900; color:var(--green); font-size:0.9rem; font-family:'DM Mono',monospace;">₱${parseFloat(b.total_amount).toFixed(2)}</td>
          <td style="padding:8px 10px;">
            <div style="display:flex; flex-direction:column; gap:3px; align-items:flex-start;">
              <span class="badge-streetside" style="background:${ch.bg}; color:${ch.color}; border:1px solid ${ch.border}; font-size:0.6rem; padding:1px 5px;">
                <i class="bi ${ch.icon}"></i> ${ch.label}
              </span>
              <div style="display:flex; gap:3px; flex-wrap:wrap;">
                ${getBookingStatusBadge(b.booking_status)}
                ${getPaymentStatusBadge(b.payment_status)}
              </div>
            </div>
          </td>
          <td style="padding:8px 10px; text-align:right;">
            <div class="row-actions-dropdown" style="position:relative; display:inline-block;">
              <button type="button" class="button sand" onclick="toggleRowActionsDropdown(event, 'dt-menu-${b.id}')" style="padding:3px 10px; font-size:0.72rem; font-weight:900; display:inline-flex; align-items:center; gap:4px;">
                Actions <i class="bi bi-chevron-down" style="font-size:0.6rem;"></i>
              </button>
              <div id="dt-menu-${b.id}" class="row-actions-menu" style="display:none; position:absolute; right:0; top:calc(100% + 4px); background:var(--white); border:2px solid var(--ink); border-radius:10px; box-shadow:4px 4px 0 var(--ink); z-index:999; min-width:160px; padding:4px 0; text-align:left;">
                ${isOpenPlay ? `
                  <a href="/pikvero/public/open-play-receipt.php?registration_id=${b.id}&from=admin_bookings" target="_blank" class="dropdown-item-btn" style="text-decoration:none;">
                    <i class="bi bi-printer-fill" style="color:var(--coral);"></i> Print Entry Receipt
                  </a>` : `
                  <button type="button" onclick="closeAllActionDropdowns(); openViewBookingModal(${b.id})" class="dropdown-item-btn">
                    <i class="bi bi-eye-fill" style="color:var(--ink);"></i> View Details
                  </button>
                  ${canPrintBookings ? `
                    <button type="button" onclick="closeAllActionDropdowns(); triggerPrintVoucher(${b.id})" class="dropdown-item-btn">
                      <i class="bi bi-printer-fill" style="color:#0284c7;"></i> Print Voucher
                    </button>
                  ` : ''}
                  ${canPayRow ? `
                    <button type="button" onclick="closeAllActionDropdowns(); openAddPaymentModal(${b.id}, '${escapeHtml(b.booking_reference)}', '${b.payment_status}')" class="dropdown-item-btn">
                      <i class="bi bi-credit-card-fill" style="color:#15803d;"></i> Add Payment
                    </button>
                  ` : ''}
                  ${canCancelRow ? `
                    <button type="button" onclick="closeAllActionDropdowns(); openCancelBookingModal(${b.id}, '${escapeHtml(b.booking_reference)}')" class="dropdown-item-btn danger">
                      <i class="bi bi-x-circle-fill" style="color:#b91c1c;"></i> Cancel Reservation
                    </button>
                  ` : ''}
                `}
              </div>
            </div>
          </td>
        </tr>
      `;
      }).join('');

      // Mobile Cards View
      mobileContainer.innerHTML = records.map(b => {
        const ch = getReservationChannel(b);
        const isCancelledOrRefunded = b.booking_status === 'cancelled' || b.booking_status === 'refunded';
        const canCancelRow = canCancelBooking && !isCancelledOrRefunded;
        const canRefundRow = canRefundBooking && !isCancelledOrRefunded && b.payment_status === 'paid';
        const canPayRow = canManageBooking && !isCancelledOrRefunded && b.payment_status !== 'paid' && b.payment_status !== 'refunded';

        return `
        <div class="card-streetside" style="padding:14px; background:var(--white); position:relative;">
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
            <strong class="mono" style="font-size:0.75rem; color:var(--green);">${b.booking_reference}</strong>
            <div style="display:flex; gap:4px; align-items:center;">
              <span class="badge-streetside" style="background:${ch.bg}; color:${ch.color}; border:1px solid ${ch.border}; font-size:0.6rem; padding:1px 5px;">
                <i class="bi ${ch.icon}"></i> ${ch.label}
              </span>
              ${getBookingStatusBadge(b.booking_status)}
              ${getPaymentStatusBadge(b.payment_status)}
            </div>
          </div>
          <div style="font-size:0.95rem; font-weight:800; text-transform:uppercase; margin-bottom:4px;">${escapeHtml(b.court_name)} &bull; ${escapeHtml(b.facility_name)}</div>
          <div style="font-size:0.82rem; color:#3b4e48; margin-bottom:8px;">
            <i class="bi bi-person"></i> ${escapeHtml(b.customer_name)} (${escapeHtml(b.customer_email)})<br>
            <i class="bi bi-clock"></i> ${b.booking_date} (${formatTimeStr(b.start_time)} – ${formatTimeStr(b.end_time)})
          </div>
          <div style="display:flex; justify-content:space-between; align-items:center; border-top:1px solid var(--line); padding-top:8px;">
            <strong style="font-size:1.05rem; color:var(--green);">₱${parseFloat(b.total_amount).toFixed(2)}</strong>
            <div class="row-actions-dropdown" style="position:relative; display:inline-block;">
              <button type="button" class="button sand" onclick="toggleRowActionsDropdown(event, 'mb-menu-${b.id}')" style="padding:4px 10px; font-size:0.72rem; font-weight:900; display:inline-flex; align-items:center; gap:4px;">
                Actions <i class="bi bi-chevron-down" style="font-size:0.6rem;"></i>
              </button>
              <div id="mb-menu-${b.id}" class="row-actions-menu" style="display:none; position:absolute; right:0; bottom:calc(100% + 4px); background:var(--white); border:2px solid var(--ink); border-radius:10px; box-shadow:4px 4px 0 var(--ink); z-index:999; min-width:160px; padding:4px 0; text-align:left;">
                <button type="button" onclick="closeAllActionDropdowns(); openViewBookingModal(${b.id})" class="dropdown-item-btn">
                  <i class="bi bi-eye-fill" style="color:var(--ink);"></i> View Details
                </button>
                ${canPrintBookings ? `
                  <button type="button" onclick="closeAllActionDropdowns(); triggerPrintVoucher(${b.id})" class="dropdown-item-btn">
                    <i class="bi bi-printer-fill" style="color:#0284c7;"></i> Print Voucher
                  </button>
                ` : ''}
                ${canPayRow ? `
                  <button type="button" onclick="closeAllActionDropdowns(); openAddPaymentModal(${b.id}, '${escapeHtml(b.booking_reference)}', '${b.payment_status}')" class="dropdown-item-btn">
                    <i class="bi bi-credit-card-fill" style="color:#15803d;"></i> Add Payment
                  </button>
                ` : ''}
                ${canCancelRow ? `
                  <button type="button" onclick="closeAllActionDropdowns(); openCancelBookingModal(${b.id}, '${escapeHtml(b.booking_reference)}')" class="dropdown-item-btn danger">
                    <i class="bi bi-x-circle-fill" style="color:#b91c1c;"></i> Cancel Reservation
                  </button>
                ` : ''}
                ${canRefundRow ? `
                  <button type="button" onclick="closeAllActionDropdowns(); openRefundBookingModal(${b.id}, '${escapeHtml(b.booking_reference)}', '${parseFloat(b.total_amount).toFixed(2)}')" class="dropdown-item-btn warning">
                    <i class="bi bi-arrow-counterclockwise" style="color:#92400e;"></i> Issue Refund
                  </button>
                ` : ''}
              </div>
            </div>
          </div>
        </div>
      `;
      }).join('');
    }

    function renderPagination(page, totalPages, totalRecords, limit) {
      const counterEl = document.getElementById('dt-counter-info');
      const btnContainer = document.getElementById('dt-pagination-btns');

      const start = totalRecords === 0 ? 0 : (page - 1) * limit + 1;
      const end = Math.min(page * limit, totalRecords);

      counterEl.innerText = `Showing ${start} to ${end} of ${totalRecords} entries`;

      let btns = '';

      btns += `
        <button onclick="goToPage(${page - 1})" class="button sand" ${page <= 1 ? 'disabled style="opacity:0.5; cursor:not-allowed;"' : ''} style="padding:5px 10px; font-size:0.75rem;">
          &laquo; Prev
        </button>
      `;

      for (let p = 1; p <= totalPages; p++) {
        if (p === 1 || p === totalPages || (p >= page - 1 && p <= page + 1)) {
          btns += `
            <button onclick="goToPage(${p})" class="button ${p === page ? 'coral' : 'sand'}" style="padding:5px 10px; font-size:0.75rem;">
              ${p}
            </button>
          `;
        } else if (p === page - 2 || p === page + 2) {
          btns += `<span style="padding:4px 6px; font-weight:700;">...</span>`;
        }
      }

      btns += `
        <button onclick="goToPage(${page + 1})" class="button sand" ${page >= totalPages ? 'disabled style="opacity:0.5; cursor:not-allowed;"' : ''} style="padding:5px 10px; font-size:0.75rem;">
          Next &raquo;
        </button>
      `;

      btnContainer.innerHTML = btns;
    }

    function goToPage(targetPage) {
      if (targetPage < 1) return;
      currentPage = targetPage;
      fetchServerData();
    }

    // ── View Booking Detail Modal Logic ──────────────────────────────────────
    function closeViewBookingModal() {
      document.getElementById('view-booking-modal').style.display = 'none';
      document.body.style.overflow = '';
      activeViewingBookingId = null;
    }

    document.getElementById('view-booking-modal').addEventListener('click', e => {
      if (e.target === document.getElementById('view-booking-modal')) closeViewBookingModal();
    });

    async function openViewBookingModal(bookingId) {
      activeViewingBookingId = bookingId;
      const modal = document.getElementById('view-booking-modal');
      document.getElementById('vb-ref-title').textContent = 'Loading...';
      document.getElementById('vb-body').innerHTML = `
        <div style="text-align:center;padding:40px 0;color:#5a7060;">
          <i class="bi bi-arrow-clockwise spin" style="font-size:2rem;"></i>
          <p style="margin-top:8px;font-weight:700;">Fetching reservation details...</p>
        </div>`;
      modal.style.display = 'flex';
      document.body.style.overflow = 'hidden';

      try {
        const res = await Api.get('/pikvero/api/owner/bookings/detail.php', { booking_id: bookingId });
        if (!res.success || !res.data) {
          Toast.error('Error', res.message || 'Failed to load reservation detail.');
          closeViewBookingModal();
          return;
        }

        const b = res.data;
        const ch = getReservationChannel(b);
        document.getElementById('vb-ref-title').textContent = `#${b.booking_reference}`;

        const surfaceLabel = (b.surface_type || 'cushioned_acrylic').replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
        const isCancelledOrRefunded = b.booking_status === 'cancelled' || b.booking_status === 'refunded';
        const canCancel = !isCancelledOrRefunded;
        const canRefund = !isCancelledOrRefunded && b.payment_status === 'paid';
        const canPay = !isCancelledOrRefunded && b.payment_status !== 'paid' && b.payment_status !== 'refunded';

        document.getElementById('vb-body').innerHTML = `
          <!-- Status & Source Badges -->
          <div style="display:flex; gap:8px; margin-bottom:18px; flex-wrap:wrap; align-items:center;">
            <span class="badge-streetside" style="background:${ch.bg}; color:${ch.color}; border:1.5px solid ${ch.border}; padding:4px 12px; font-size:0.75rem; font-weight:900;">
              <i class="bi ${ch.icon}"></i> ${ch.label} RESERVATION
            </span>
            ${getBookingStatusBadge(b.booking_status)}
            ${getPaymentStatusBadge(b.payment_status)}
          </div>

          <!-- Customer Info -->
          <div style="font-family:'DM Mono',monospace; font-weight:900; font-size:0.72rem; text-transform:uppercase; color:#5a7060; margin:16px 0 8px; padding-bottom:4px; border-bottom:1.5px solid var(--line); display:flex; align-items:center; gap:6px;">
            <i class="bi bi-person-fill" style="color:var(--green);"></i> CUSTOMER DETAILS
          </div>
          <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
            <div style="background:var(--parchment); border:1.5px solid var(--line); border-radius:8px; padding:8px 12px;">
              <div style="font-family:'DM Mono',monospace; font-size:0.65rem; font-weight:800; color:#5a7060; text-transform:uppercase;">Name</div>
              <div style="font-weight:800; font-size:0.9rem; color:var(--ink);">${escapeHtml(b.customer_name)}</div>
            </div>
            <div style="background:var(--parchment); border:1.5px solid var(--line); border-radius:8px; padding:8px 12px;">
              <div style="font-family:'DM Mono',monospace; font-size:0.65rem; font-weight:800; color:#5a7060; text-transform:uppercase;">Email</div>
              <div style="font-weight:800; font-size:0.85rem; color:var(--ink); word-break:break-all;">${escapeHtml(b.customer_email)}</div>
            </div>
            ${b.customer_phone ? `
            <div style="background:var(--parchment); border:1.5px solid var(--line); border-radius:8px; padding:8px 12px; grid-column:1/-1;">
              <div style="font-family:'DM Mono',monospace; font-size:0.65rem; font-weight:800; color:#5a7060; text-transform:uppercase;">Phone</div>
              <div style="font-weight:800; font-size:0.85rem; color:var(--ink);">${escapeHtml(b.customer_phone)}</div>
            </div>` : ''}
          </div>

          <!-- Facility & Court Info -->
          <div style="font-family:'DM Mono',monospace; font-weight:900; font-size:0.72rem; text-transform:uppercase; color:#5a7060; margin:18px 0 8px; padding-bottom:4px; border-bottom:1.5px solid var(--line); display:flex; align-items:center; gap:6px;">
            <i class="bi bi-building-fill" style="color:var(--coral);"></i> FACILITY & COURT
          </div>
          <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
            <div style="background:var(--parchment); border:1.5px solid var(--line); border-radius:8px; padding:8px 12px;">
              <div style="font-family:'DM Mono',monospace; font-size:0.65rem; font-weight:800; color:#5a7060; text-transform:uppercase;">Court Name</div>
              <div style="font-weight:800; font-size:0.9rem; color:var(--ink);">${escapeHtml(b.court_name)}</div>
            </div>
            <div style="background:var(--parchment); border:1.5px solid var(--line); border-radius:8px; padding:8px 12px;">
              <div style="font-family:'DM Mono',monospace; font-size:0.65rem; font-weight:800; color:#5a7060; text-transform:uppercase;">Court Type &amp; Surface</div>
              <div style="font-weight:800; font-size:0.85rem; color:var(--ink);">${(b.court_type||'').toUpperCase()} &bull; ${surfaceLabel}</div>
            </div>
            <div style="background:var(--parchment); border:1.5px solid var(--line); border-radius:8px; padding:8px 12px; grid-column:1/-1;">
              <div style="font-family:'DM Mono',monospace; font-size:0.65rem; font-weight:800; color:#5a7060; text-transform:uppercase;">Facility &amp; Address</div>
              <div style="font-weight:800; font-size:0.85rem; color:var(--ink);">${escapeHtml(b.facility_name)} — ${[b.facility_address, b.facility_city].filter(Boolean).join(', ')}</div>
            </div>
          </div>

          <!-- Schedule Info -->
          <div style="font-family:'DM Mono',monospace; font-weight:900; font-size:0.72rem; text-transform:uppercase; color:#5a7060; margin:18px 0 8px; padding-bottom:4px; border-bottom:1.5px solid var(--line); display:flex; align-items:center; gap:6px;">
            <i class="bi bi-clock-fill" style="color:#3a7bd5;"></i> SCHEDULE & DURATION
          </div>
          <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
            <div style="background:var(--parchment); border:1.5px solid var(--line); border-radius:8px; padding:8px 12px;">
              <div style="font-family:'DM Mono',monospace; font-size:0.65rem; font-weight:800; color:#5a7060; text-transform:uppercase;">Booking Date</div>
              <div style="font-weight:800; font-size:0.9rem; color:var(--ink);">${b.booking_date}</div>
            </div>
            <div style="background:var(--parchment); border:1.5px solid var(--line); border-radius:8px; padding:8px 12px;">
              <div style="font-family:'DM Mono',monospace; font-size:0.65rem; font-weight:800; color:#5a7060; text-transform:uppercase;">Time Slot</div>
              <div style="font-weight:800; font-size:0.85rem; color:var(--ink); font-family:'DM Mono',monospace;">${formatTimeStr(b.start_time)} – ${formatTimeStr(b.end_time)} (${b.duration_hours} hr)</div>
            </div>
          </div>

          <!-- Financial Breakdown -->
          <div style="font-family:'DM Mono',monospace; font-weight:900; font-size:0.72rem; text-transform:uppercase; color:#5a7060; margin:18px 0 8px; padding-bottom:4px; border-bottom:1.5px solid var(--line); display:flex; align-items:center; gap:6px;">
            <i class="bi bi-cash-stack" style="color:var(--green);"></i> FINANCIAL SUMMARY
          </div>
          <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
            <div style="background:var(--parchment); border:1.5px solid var(--line); border-radius:8px; padding:8px 12px;">
              <div style="font-family:'DM Mono',monospace; font-size:0.65rem; font-weight:800; color:#5a7060; text-transform:uppercase;">Rate per Hour</div>
              <div style="font-weight:800; font-size:0.9rem; color:var(--ink);">₱${parseFloat(b.rate_per_hour).toFixed(2)}/hr</div>
            </div>
            <div style="background:#f0fdf4; border:1.5px solid var(--green); border-radius:8px; padding:8px 12px;">
              <div style="font-family:'DM Mono',monospace; font-size:0.65rem; font-weight:800; color:#2d6a4f; text-transform:uppercase;">Total Amount</div>
              <div style="font-weight:900; font-size:1.15rem; color:var(--green);">₱${parseFloat(b.total_amount).toFixed(2)}</div>
            </div>
          </div>

          <!-- Notes -->
          ${b.notes ? `
          <div style="font-family:'DM Mono',monospace; font-weight:900; font-size:0.72rem; text-transform:uppercase; color:#5a7060; margin:18px 0 8px; padding-bottom:4px; border-bottom:1.5px solid var(--line); display:flex; align-items:center; gap:6px;">
            <i class="bi bi-card-text" style="color:var(--ink);"></i> NOTES & MEMO
          </div>
          <div style="background:var(--parchment); border:1.5px solid var(--line); border-radius:8px; padding:10px 14px; font-size:0.85rem; font-weight:700; color:var(--ink);">
            ${escapeHtml(b.notes)}
          </div>` : ''}

          <!-- Quick Action Buttons -->
          <div style="display:flex; gap:8px; margin-top:24px; padding-top:16px; border-top:2px solid var(--line); flex-wrap:wrap;">
            ${canPrintBookings ? `
              <button onclick="closeViewBookingModal(); triggerPrintVoucher(${b.id});" class="button sand" style="background:#e0f2fe; color:#0369a1; border:2px solid #0284c7; flex:1; padding:9px; font-size:0.78rem; font-weight:900;">
                <i class="bi bi-printer-fill"></i> Print Voucher
              </button>
            ` : ''}
            ${canManageBooking && canPay ? `
              <button onclick="closeViewBookingModal(); openAddPaymentModal(${b.id}, '${escapeHtml(b.booking_reference)}', '${b.payment_status}');" class="button lime" style="flex:1; padding:9px; font-size:0.78rem;">
                <i class="bi bi-credit-card-fill"></i> Add Payment
              </button>
            ` : ''}
            ${canCancelBooking && canCancel ? `
              <button onclick="closeViewBookingModal(); openCancelBookingModal(${b.id}, '${escapeHtml(b.booking_reference)}');" class="button coral" style="flex:1; padding:9px; font-size:0.78rem;">
                <i class="bi bi-x-circle-fill"></i> Cancel
              </button>
            ` : ''}
            ${canRefundBooking && canRefund ? `
              <button onclick="closeViewBookingModal(); openRefundBookingModal(${b.id}, '${escapeHtml(b.booking_reference)}', '${parseFloat(b.total_amount).toFixed(2)}');" class="button sand" style="background:#fef3c7; color:#92400e; border:2px solid #f59e0b; flex:1; padding:9px; font-size:0.78rem; font-weight:900;">
                <i class="bi bi-arrow-counterclockwise"></i> Issue Refund
              </button>
            ` : ''}
            <button onclick="closeViewBookingModal()" class="button sand" style="flex:1; padding:9px; font-size:0.78rem;">
              Close
            </button>
          </div>
        `;

      } catch(e) {
        console.error(e);
        Toast.error('Error', 'Could not load reservation details.');
        closeViewBookingModal();
      }
    }

    // ── Add Payment Modal Controls ───────────────────────────────────────────
    function openAddPaymentModal(bookingId, bookingRef, currentStatus) {
      document.getElementById('ap_booking_id').value = bookingId;
      document.getElementById('ap_target_info').textContent = `#${bookingRef}`;
      document.getElementById('ap_payment_status').value = currentStatus === 'paid' ? 'paid' : 'paid';
      document.getElementById('ap_note').value = 'Paid in cash at counter';

      document.getElementById('add-payment-modal').style.display = 'flex';
      document.body.style.overflow = 'hidden';
    }

    function closeAddPaymentModal() {
      document.getElementById('add-payment-modal').style.display = 'none';
      document.body.style.overflow = '';
    }

    // ── Cancel & Refund Modal Controls ───────────────────────────────────────
    function openCancelBookingModal(bookingId, bookingRef) {
      document.getElementById('cb_booking_id').value = bookingId;
      document.getElementById('cb_target_info').textContent = `#${bookingRef}`;
      document.getElementById('cb_reason').value = 'Customer requested cancellation';

      document.getElementById('cancel-booking-modal').style.display = 'flex';
      document.body.style.overflow = 'hidden';
    }

    function closeCancelBookingModal() {
      document.getElementById('cancel-booking-modal').style.display = 'none';
      document.body.style.overflow = '';
    }

    function openRefundBookingModal(bookingId, bookingRef, amount) {
      document.getElementById('rf_booking_id').value = bookingId;
      document.getElementById('rf_target_info').textContent = `#${bookingRef}`;
      document.getElementById('rf_amount_info').textContent = `₱${amount}`;
      document.getElementById('rf_reason').value = 'Full refund issued to customer';

      document.getElementById('refund-booking-modal').style.display = 'flex';
      document.body.style.overflow = 'hidden';
    }

    function closeRefundBookingModal() {
      document.getElementById('refund-booking-modal').style.display = 'none';
      document.body.style.overflow = '';
    }

    document.getElementById('cancel-booking-modal').addEventListener('click', e => {
      if (e.target === document.getElementById('cancel-booking-modal')) closeCancelBookingModal();
    });

    document.getElementById('refund-booking-modal').addEventListener('click', e => {
      if (e.target === document.getElementById('refund-booking-modal')) closeRefundBookingModal();
    });

    document.getElementById('add-payment-modal').addEventListener('click', e => {
      if (e.target === document.getElementById('add-payment-modal')) closeAddPaymentModal();
    });

    // Modal Control & Helper functions
    async function openAddBookingModal() {
      document.getElementById('add-booking-form').reset();
      document.getElementById('ab_customer_id').value = '';
      document.getElementById('ab_customer_selected_badge').style.display = 'none';
      document.getElementById('ab_customer_dropdown').style.display = 'none';
      lastFetchedCourtId = null;
      lastFetchedDate = null;
      
      // Set date to today
      const today = new Date().toISOString().split('T')[0];
      document.getElementById('ab_date').value = today;
      document.getElementById('ab_start_time').value = '08:00';
      document.getElementById('ab_end_time').value = '10:00';
      document.getElementById('ab_notes').value = 'Walk-in Reservation';

      document.getElementById('add-booking-modal').style.display = 'flex';
      document.body.style.overflow = 'hidden';

      await loadCourtsForModal();
    }

    function closeAddBookingModal() {
      document.getElementById('add-booking-modal').style.display = 'none';
      document.body.style.overflow = '';
    }

    async function loadCourtsForModal() {
      const select = document.getElementById('ab_court_id');
      select.innerHTML = '<option value="">Loading courts...</option>';
      try {
        const res = await Api.get('/pikvero/api/owner/courts.php');
        if (res.success && res.data && res.data.length > 0) {
          ownerCourtsMap = {};
          select.innerHTML = res.data.map(c => {
            ownerCourtsMap[c.id] = c;
            return `<option value="${c.id}">${c.name} (${c.facility_name}) - ₱${parseFloat(c.base_price_per_hour).toFixed(2)}/hr</option>`;
          }).join('');
          
          onCourtSelectChange();
        } else {
          select.innerHTML = '<option value="">No courts registered yet</option>';
        }
      } catch(e) {
        console.error(e);
        select.innerHTML = '<option value="">Error loading courts</option>';
      }
    }

    let activeCourtPricingRules = [];
    let activeCourtBookings = [];
    let lastFetchedCourtId = null;
    let lastFetchedDate = null;

    async function onCourtSelectChange() {
      const courtId = document.getElementById('ab_court_id').value;
      const c = ownerCourtsMap[courtId];
      if (c) {
        document.getElementById('ab_rate').value = parseFloat(c.base_price_per_hour).toFixed(2);
      }

      activeCourtPricingRules = [];
      lastFetchedCourtId = null;

      if (courtId) {
        try {
          const res = await Api.get('/pikvero/api/owner/courts/pricing.php', { court_id: courtId });
          if (res.success && res.data) {
            activeCourtPricingRules = res.data;
          }
        } catch(e) { console.error(e); }
      }

      renderCourtPricingRulesSection();
      await onTimeOrDateChange();
    }

    function renderCourtPricingRulesSection() {
      const section = document.getElementById('ab_pricing_rules_section');
      const listEl = document.getElementById('ab_pricing_rules_list');

      if (!activeCourtPricingRules || activeCourtPricingRules.length === 0) {
        section.style.display = 'none';
        listEl.innerHTML = '';
        return;
      }

      section.style.display = 'block';
      listEl.innerHTML = activeCourtPricingRules.map(r => `
        <div id="prule-card-${r.id}" style="
            background: #f8fef9;
            border: 1.5px solid var(--line);
            border-radius: 8px;
            padding: 8px 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            transition: all 0.2s ease;
          ">
          <div>
            <div style="font-weight:900; font-size:0.82rem; color:var(--ink);">${escapeHtml(r.name)}</div>
            <div style="font-size:0.7rem; color:#5a7060; margin-top:2px;">
              <i class="bi bi-clock"></i> ${formatTimeStr(r.start_time)} – ${formatTimeStr(r.end_time)}
              &nbsp;&middot;&nbsp;
              <i class="bi bi-calendar3"></i> ${(r.day_type || 'all').replace(/,/g, ', ')}
            </div>
          </div>
          <div style="text-align:right;">
            <span style="font-size:0.95rem; font-weight:900; color:var(--green);">₱${parseFloat(r.price_per_hour).toFixed(2)}</span>
            <span style="font-size:0.68rem; color:#5a7060;">/hr</span>
            <span class="prule-match-tag" style="display:none; margin-top:2px; font-size:0.65rem; font-weight:900; color:#2d6a4f; background:#d8f3dc; border:1px solid #2d6a4f; border-radius:10px; padding:1px 6px;">⚡ APPLIED</span>
          </div>
        </div>
      `).join('');
    }

    async function fetchCourtAvailabilityIfNeeded() {
      const courtId = document.getElementById('ab_court_id').value;
      const dateStr = document.getElementById('ab_date').value;

      if (!courtId || !dateStr) {
        activeCourtBookings = [];
        return;
      }

      if (lastFetchedCourtId === courtId && lastFetchedDate === dateStr) {
        return;
      }

      try {
        const res = await Api.get('/pikvero/api/owner/courts/availability.php', { court_id: courtId, date: dateStr });
        if (res.success && res.data) {
          activeCourtBookings = res.data.active_bookings || [];
          lastFetchedCourtId = courtId;
          lastFetchedDate = dateStr;
        }
      } catch (e) { console.error(e); }
    }

    async function checkTimeConflict() {
      await fetchCourtAvailabilityIfNeeded();

      const banner = document.getElementById('ab_availability_banner');
      const textEl = document.getElementById('ab_availability_text');
      const submitBtn = document.getElementById('ab_submit_btn');
      const startTimeInput = document.getElementById('ab_start_time');
      const endTimeInput = document.getElementById('ab_end_time');

      const startTime = startTimeInput.value;
      const endTime = endTimeInput.value;

      if (!startTime || !endTime) {
        banner.style.display = 'none';
        submitBtn.disabled = false;
        submitBtn.style.opacity = '1';
        startTimeInput.style.borderColor = 'var(--ink)';
        endTimeInput.style.borderColor = 'var(--ink)';
        return;
      }

      const [sH, sM] = startTime.split(':').map(Number);
      const [eH, eM] = endTime.split(':').map(Number);
      const reqStartMin = sH * 60 + sM;
      const reqEndMin = eH * 60 + eM;

      if (reqEndMin <= reqStartMin) {
        banner.style.display = 'flex';
        banner.style.background = '#fee2e2';
        banner.style.borderColor = '#ef4444';
        banner.style.color = '#991b1b';
        textEl.innerHTML = `<i class="bi bi-exclamation-triangle-fill" style="font-size:1.1rem; color:#ef4444;"></i> <span>INVALID TIME RANGE: End time must be after start time.</span>`;
        submitBtn.disabled = true;
        submitBtn.style.opacity = '0.5';
        submitBtn.style.cursor = 'not-allowed';
        startTimeInput.style.borderColor = '#ef4444';
        endTimeInput.style.borderColor = '#ef4444';
        return;
      }

      // Check for overlapping active reservations
      let conflictingBooking = null;

      for (const r of activeCourtBookings) {
        const [rSH, rSM] = r.start_time.split(':').map(Number);
        const [rEH, rEM] = r.end_time.split(':').map(Number);
        const rStartMin = rSH * 60 + rSM;
        const rEndMin = rEH * 60 + rEM;

        if (reqStartMin < rEndMin && reqEndMin > rStartMin) {
          conflictingBooking = r;
          break;
        }
      }

      if (conflictingBooking) {
        banner.style.display = 'flex';
        banner.style.background = '#fee2e2';
        banner.style.borderColor = '#ef4444';
        banner.style.color = '#991b1b';
        
        const customerName = conflictingBooking.customer_name ? escapeHtml(conflictingBooking.customer_name) : 'Customer';
        const timeWindow = `${formatTimeStr(conflictingBooking.start_time)} – ${formatTimeStr(conflictingBooking.end_time)}`;
        const refTag = conflictingBooking.booking_reference ? `#${conflictingBooking.booking_reference}` : '';

        textEl.innerHTML = `
          <i class="bi bi-exclamation-octagon-fill" style="font-size:1.2rem; color:#ef4444; flex-shrink:0;"></i>
          <div>
            <strong>⚠️ TIME CONFLICT DETECTED:</strong> Court is already reserved ${timeWindow} (${refTag} &bull; ${customerName}). Please choose a different time slot.
          </div>
        `;

        submitBtn.disabled = true;
        submitBtn.style.opacity = '0.5';
        submitBtn.style.cursor = 'not-allowed';
        startTimeInput.style.borderColor = '#ef4444';
        endTimeInput.style.borderColor = '#ef4444';
      } else {
        banner.style.display = 'flex';
        banner.style.background = '#f0fdf4';
        banner.style.borderColor = 'var(--green)';
        banner.style.color = '#166534';
        textEl.innerHTML = `<i class="bi bi-check-circle-fill" style="font-size:1.1rem; color:var(--green);"></i> <span>✓ TIME SLOT AVAILABLE — No existing reservations for this time slot.</span>`;

        submitBtn.disabled = false;
        submitBtn.style.opacity = '1';
        submitBtn.style.cursor = 'pointer';
        startTimeInput.style.borderColor = 'var(--ink)';
        endTimeInput.style.borderColor = 'var(--ink)';
      }
    }

    async function onTimeOrDateChange() {
      await checkTimeConflict();

      const courtId = document.getElementById('ab_court_id').value;
      const c = ownerCourtsMap[courtId];
      const basePrice = c ? parseFloat(c.base_price_per_hour) : 400;

      const dateStr = document.getElementById('ab_date').value;
      const startTime = document.getElementById('ab_start_time').value;
      const endTime = document.getElementById('ab_end_time').value;

      let matchedRule = null;

      if (dateStr && startTime && endTime && activeCourtPricingRules.length > 0) {
        const dt = new Date(dateStr + 'T00:00:00');
        const dayNum = dt.getDay(); // 0 = Sun, 1 = Mon, ..., 6 = Sat
        const dayNames = ['sunday','monday','tuesday','wednesday','thursday','friday','saturday'];
        const dayName = dayNames[dayNum];

        matchedRule = activeCourtPricingRules.find(r => {
          // 1. Day Check
          const dtType = (r.day_type || 'all').toLowerCase();
          let dayMatches = false;
          if (dtType === 'all') {
            dayMatches = true;
          } else if (dtType === 'weekday' && dayNum >= 1 && dayNum <= 5) {
            dayMatches = true;
          } else if (dtType === 'weekend' && (dayNum === 0 || dayNum === 6)) {
            dayMatches = true;
          } else if (dtType.includes(dayName)) {
            dayMatches = true;
          }

          if (!dayMatches) return false;

          // 2. Time Check (Overlap / Within rule range)
          const rStart = r.start_time.substring(0,5);
          const rEnd = r.end_time.substring(0,5);

          // Check if selected window starts inside or overlaps with rule range
          return (startTime >= rStart && startTime < rEnd) || (endTime > rStart && endTime <= rEnd) || (startTime <= rStart && endTime >= rEnd);
        });
      }

      const badge = document.getElementById('ab_rule_badge');
      const badgeText = document.getElementById('ab_rule_badge_text');

      // Clear highlights on all rule cards
      activeCourtPricingRules.forEach(r => {
        const card = document.getElementById(`prule-card-${r.id}`);
        if (card) {
          card.style.borderColor = 'var(--line)';
          card.style.background = '#f8fef9';
          const tag = card.querySelector('.prule-match-tag');
          if (tag) tag.style.display = 'none';
        }
      });

      if (matchedRule) {
        const rulePrice = parseFloat(matchedRule.price_per_hour);
        document.getElementById('ab_rate').value = rulePrice.toFixed(2);

        badge.style.display = 'flex';
        badgeText.innerHTML = `⚡ Applied Special Pricing: <strong>${escapeHtml(matchedRule.name)}</strong> — <strong>₱${rulePrice.toFixed(2)}/hr</strong> (Court base rate: ₱${basePrice.toFixed(2)}/hr)`;

        const card = document.getElementById(`prule-card-${matchedRule.id}`);
        if (card) {
          card.style.borderColor = 'var(--green)';
          card.style.background = '#eafaf1';
          const tag = card.querySelector('.prule-match-tag');
          if (tag) tag.style.display = 'inline-block';
        }
      } else {
        if (c) {
          document.getElementById('ab_rate').value = basePrice.toFixed(2);
        }
        badge.style.display = 'none';
      }

      calculateBookingTotal();
    }

    function formatTimeStr(t) {
      if (!t) return '—';
      const [h, m] = t.split(':').map(Number);
      const ampm = h < 12 ? 'AM' : 'PM';
      const h12  = h === 0 ? 12 : h > 12 ? h - 12 : h;
      return h12 + ':' + String(m).padStart(2, '0') + ' ' + ampm;
    }

    function calculateBookingTotal() {
      const startTime = document.getElementById('ab_start_time').value;
      const endTime = document.getElementById('ab_end_time').value;
      const rate = parseFloat(document.getElementById('ab_rate').value) || 0;
      const totalEl = document.getElementById('ab_total_price');
      const textEl = document.getElementById('ab_duration_text');

      if (!startTime || !endTime || startTime >= endTime) {
        totalEl.textContent = '₱0.00';
        textEl.textContent = 'Invalid time selection';
        return;
      }

      const s = startTime.split(':').map(Number);
      const e = endTime.split(':').map(Number);
      const startMin = s[0] * 60 + s[1];
      const endMin = e[0] * 60 + e[1];
      const diffHours = (endMin - startMin) / 60;

      const total = Math.max(0, diffHours * rate);
      totalEl.textContent = `₱${total.toFixed(2)}`;
      textEl.textContent = `${diffHours} ${diffHours === 1 ? 'hour' : 'hours'} @ ₱${rate.toFixed(2)}/hr`;
    }

    function renderCustomerDropdown(customers) {
      const dropdown = document.getElementById('ab_customer_dropdown');
      if (!customers || customers.length === 0) {
        dropdown.innerHTML = '<div style="padding:10px; font-size:0.8rem; color:#888;">No matching customer found</div>';
        dropdown.style.display = 'block';
        return;
      }

      dropdown.innerHTML = customers.map(u => `
        <div onclick="selectCustomer(${u.id}, '${escapeHtml(u.first_name + ' ' + u.last_name)}', '${escapeHtml(u.email)}')" style="
            padding: 8px 12px;
            cursor: pointer;
            border-bottom: 1px solid var(--line);
            transition: background 0.15s;
          " onmouseover="this.style.background='#f0fdf4'" onmouseout="this.style.background='white'">
          <div style="font-weight:800; font-size:0.85rem;">${escapeHtml(u.first_name + ' ' + u.last_name)}</div>
          <div style="font-size:0.72rem; color:#5a7060;">${escapeHtml(u.email)} ${u.phone ? '&bull; ' + escapeHtml(u.phone) : ''}</div>
        </div>
      `).join('');
      dropdown.style.display = 'block';
    }

    function selectCustomer(id, name, email) {
      document.getElementById('ab_customer_id').value = id;
      document.getElementById('ab_customer_search').value = `${name} (${email})`;
      document.getElementById('ab_customer_dropdown').style.display = 'none';
      
      document.getElementById('ab_customer_selected_text').textContent = `Selected: ${name}`;
      document.getElementById('ab_customer_selected_badge').style.display = 'inline-block';
    }

    function escapeHtml(str) {
      if (!str) return '';
      return str.replace(/[&<>"']/g, m => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
      })[m]);
    }
  </script>
</body>
</html>

