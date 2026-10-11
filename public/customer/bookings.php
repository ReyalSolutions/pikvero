<?php
require_once __DIR__ . '/../../app/bootstrap.php';
use App\Core\Auth\Auth;
use App\Core\Http\Request;

$request = new Request();
$paymentStatus = $request->get('payment');
$bookingRef = $request->get('booking_ref') ?? $request->get('ref');

$paymentSuccessNotice = false;
$confirmedBookingRef = '';
$confirmationData = null;

if ($paymentStatus === 'success' && !empty($bookingRef) && Auth::check()) {
    try {
        $bookingService = new \App\Application\Services\BookingService();
        $updatedBooking = $bookingService->markPaymentSuccess((string)$bookingRef, Auth::id());
        if ($updatedBooking) {
            $paymentSuccessNotice = true;
            $confirmedBookingRef = htmlspecialchars($updatedBooking['booking_reference'] ?? $bookingRef);
            $confirmationData = $updatedBooking;
        }
    } catch (\Throwable $e) {}
}
if (!$confirmationData && $request->get('confirmed') === '1' && !empty($bookingRef) && Auth::check()) {
    $candidate = (new \App\Infrastructure\Repositories\BookingRepository())->findByReference((string)$bookingRef);
    if ($candidate && (int)$candidate['customer_id'] === Auth::id() && $candidate['booking_status'] === 'confirmed') {
        $confirmationData = $candidate;
    }
}
if ($confirmationData) {
    $images = (new \App\Infrastructure\Repositories\FacilityRepository())->getFacilityImages((int)$confirmationData['facility_id']);
    $confirmationData = array_intersect_key($confirmationData, array_flip(['id', 'booking_reference', 'facility_name', 'court_name', 'booking_date', 'start_time', 'end_time', 'duration_hours', 'total_amount', 'booking_status', 'address', 'city']));
    $confirmationData['image'] = $images[0]['image_path'] ?? '/pikvero/assets/images/logo.png';
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Pikvero — My Reservations &amp; Bookings</title>
  <?php $favLogo = class_exists(\App\Core\Auth\Auth::class) ? \App\Core\Auth\Auth::getLogoUrl() : '/pikvero/assets/images/logo.png'; ?>
  <link rel="icon" type="image/png" href="<?= htmlspecialchars($favLogo) ?>?v=<?= time() ?>">
  <link rel="shortcut icon" type="image/png" href="<?= htmlspecialchars($favLogo) ?>?v=<?= time() ?>">
  <link rel="apple-touch-icon" href="<?= htmlspecialchars($favLogo) ?>?v=<?= time() ?>">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
  <link rel="stylesheet" href="/pikvero/assets/css/streetside-theme.css?v=<?= filemtime(__DIR__ . '/../../assets/css/streetside-theme.css') ?>">
  <link rel="stylesheet" href="/pikvero/assets/css/toast.css">
  <style>
    /* DataTables Streetside styling */
    .dataTables_wrapper .dataTables_length,
    .dataTables_wrapper .dataTables_filter,
    .dataTables_wrapper .dataTables_info,
    .dataTables_wrapper .dataTables_processing,
    .dataTables_wrapper .dataTables_paginate {
      font-family: 'DM Mono', monospace;
      font-size: 0.8rem;
      font-weight: 700;
      color: var(--ink) !important;
      margin-bottom: 12px;
    }
    .dataTables_wrapper .dataTables_filter input,
    .dataTables_wrapper .dataTables_length select {
      border: 2px solid var(--ink) !important;
      border-radius: 8px !important;
      padding: 6px 10px !important;
      font-family: inherit;
      font-weight: 700;
      background: var(--white);
      outline: none;
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button.current {
      background: var(--lime) !important;
      border: 2px solid var(--ink) !important;
      border-radius: 8px !important;
      color: var(--ink) !important;
      font-weight: 800 !important;
    }
    table.dataTable tbody tr {
      background-color: transparent !important;
    }

    /* Filters Bar */
    .filter-card-bar {
      display: flex;
      gap: 12px;
      flex-wrap: wrap;
      align-items: center;
      margin-bottom: 20px;
      padding: 16px;
      background: var(--white);
      border: 2px solid var(--ink);
      border-radius: 14px;
      box-shadow: 3px 3px 0 var(--ink);
    }
    .filter-card-bar label {
      font-family: 'DM Mono', monospace;
      font-weight: 800;
      font-size: 0.72rem;
      text-transform: uppercase;
      display: block;
      margin-bottom: 4px;
    }
    .filter-ctrl {
      padding: 8px 12px;
      border: 2px solid var(--ink);
      border-radius: 8px;
      font-family: inherit;
      font-weight: 700;
      font-size: 0.82rem;
      background: var(--sand);
      outline: none;
    }

    /* Mobile Responsive Compact Cards Transformation */
    @media (max-width: 768px) {
      .portal-main {
        padding: 12px 10px 85px !important;
      }
      .bookings-header-wrap {
        margin-bottom: 10px !important;
      }
      .bookings-title {
        font-size: 1.35rem !important;
      }
      .filter-card-bar {
        display: grid !important;
        grid-template-columns: 1fr 1fr !important;
        gap: 6px !important;
        padding: 8px 10px !important;
        margin-bottom: 10px !important;
        box-shadow: 2px 2px 0 var(--ink) !important;
        border-radius: 10px !important;
      }
      .filter-card-bar label {
        font-size: 0.62rem !important;
        margin-bottom: 2px !important;
      }
      .filter-ctrl {
        padding: 5px 8px !important;
        font-size: 0.75rem !important;
        border-radius: 6px !important;
        width: 100% !important;
        box-sizing: border-box !important;
      }
      .filter-btn-wrap {
        grid-column: 1 / -1 !important;
        margin-left: 0 !important;
      }
      .filter-btn-wrap button {
        width: 100% !important;
        padding: 6px 10px !important;
        font-size: 0.72rem !important;
      }

      .card-streetside-table {
        padding: 8px !important;
        border-radius: 12px !important;
        box-shadow: 2px 2px 0 var(--ink) !important;
      }
      .desktop-table-header {
        display: none !important;
      }
      #customer-bookings-table, 
      #customer-bookings-table tbody, 
      #customer-bookings-table tr, 
      #customer-bookings-table td {
        display: block !important;
        width: 100% !important;
        box-sizing: border-box !important;
      }
      #customer-bookings-table tbody tr {
        background: var(--white) !important;
        border: 2px solid var(--ink) !important;
        border-radius: 10px !important;
        padding: 8px 10px !important;
        margin-bottom: 8px !important;
        box-shadow: 2px 2px 0 var(--ink) !important;
      }
      #customer-bookings-table td {
        border: none !important;
        padding: 2px 0 !important;
      }
      .mobile-hide-col {
        display: none !important;
      }
      .mobile-card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 4px;
        padding-bottom: 4px;
        border-bottom: 1px dashed var(--line);
      }
      .mobile-card-actions {
        display: flex;
        gap: 6px;
        justify-content: flex-end;
        align-items: center;
        padding-top: 4px;
        margin-top: 4px;
        border-top: 1px dashed var(--line);
      }
      .mobile-card-actions .button {
        padding: 4px 8px !important;
        font-size: 0.72rem !important;
      }

      /* Mobile Modal Overlay & Card Responsiveness */
      #view-booking-modal,
      #cancel-booking-modal {
        align-items: flex-start !important;
        justify-content: center !important;
        overflow-y: auto !important;
        -webkit-overflow-scrolling: touch !important;
        padding: 16px 10px 85px !important;
        box-sizing: border-box !important;
      }

      #view-booking-modal .card-streetside,
      #cancel-booking-modal .card-streetside {
        width: 100% !important;
        max-width: 480px !important;
        padding: 14px 12px !important;
        border-radius: 12px !important;
        box-shadow: 3px 3px 0 var(--ink) !important;
        margin: auto !important;
        box-sizing: border-box !important;
      }

      #modal-booking-title {
        font-size: 0.98rem !important;
      }

      .voucher-detail-grid {
        grid-template-columns: 1fr !important;
        gap: 8px !important;
      }

      .voucher-barcode-box {
        padding: 10px !important;
        font-size: 0.82rem !important;
      }

      .modal-footer-btns {
        flex-direction: row !important;
        gap: 6px !important;
        margin-top: 14px !important;
      }
      .modal-footer-btns button {
        flex: 1 !important;
        padding: 8px 10px !important;
        font-size: 0.78rem !important;
        justify-content: center !important;
      }
    }
  </style>
<link rel="stylesheet" href="/pikvero/assets/css/player-pages.css?v=1">
<meta name="theme-color" content="#003d2d">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="Pikvero">
<link rel="apple-touch-icon" href="/pikvero/assets/images/pwa/icon-180.png">
<script defer src="/pikvero/assets/js/components/pwa.js?v=20261011-shortcut"></script>
</head>
<body class="customer-portal player-bookings">

  <div id="sidebar-container"></div>
  <div id="navbar-container"></div>

  <main class="portal-main">
    <div>
      <div class="bookings-header-wrap" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; flex-wrap:wrap; gap:10px;">
        <div>
          <div class="eyebrow">YOUR RESERVATIONS</div>
          <h1 class="bookings-title" style="font-size: clamp(1.5rem, 4vw, 2.1rem); font-weight:800; text-transform:uppercase; margin:2px 0 0;">My bookings</h1>
        </div>
        <a href="/pikvero/public/customer/search.php" class="button coral" style="padding:8px 14px; font-size:0.80rem;">
          <i class="bi bi-plus-circle-fill"></i> Book a court
        </a>
      </div>

      <!-- FILTER BAR -->
      <div class="filter-card-bar">
        <div>
          <label for="filter-status">STATUS FILTER</label>
          <select id="filter-status" class="filter-ctrl" onchange="reloadBookingsTable()">
            <option value="all">ALL STATUSES</option>
            <option value="confirmed">CONFIRMED</option>
            <option value="pending">PENDING</option>
            <option value="cancelled">CANCELLED</option>
          </select>
        </div>

        <div>
          <label for="filter-start-date">START DATE</label>
          <input type="date" id="filter-start-date" class="filter-ctrl" onchange="reloadBookingsTable()">
        </div>

        <div>
          <label for="filter-end-date">END DATE</label>
          <input type="date" id="filter-end-date" class="filter-ctrl" onchange="reloadBookingsTable()">
        </div>

        <div class="filter-btn-wrap" style="margin-left:auto; align-self:flex-end;">
          <button type="button" onclick="resetBookingsFilters()" class="button sand" style="padding:7px 12px; font-size:0.75rem;">
            <i class="bi bi-arrow-counterclockwise"></i> Reset Filters
          </button>
        </div>
      </div>

      <!-- SERVER-SIDE DATATABLES CARD -->
      <div class="card-streetside card-streetside-table" style="padding:16px; background:var(--white);">
        <div style="overflow-x:auto; -webkit-overflow-scrolling:touch;">
          <table id="customer-bookings-table" class="table-streetside" style="width:100%; border-collapse:collapse;">
            <thead class="desktop-table-header">
              <tr style="border-bottom:2px solid var(--ink); text-align:left; font-family:'DM Mono', monospace; font-size:0.75rem; text-transform:uppercase;">
                <th style="padding:10px;">BOOKING REF &amp; STATUS</th>
                <th style="padding:10px;">FACILITY &amp; COURT</th>
                <th style="padding:10px;">RESERVATION DATE &amp; TIME</th>
                <th style="padding:10px;">AMOUNT &amp; PAYMENT</th>
                <th style="padding:10px;">DATE CREATED</th>
                <th style="padding:10px; text-align:right;">ACTIONS</th>
              </tr>
            </thead>
            <tbody>
              <!-- Server-side loaded -->
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div id="footer-container"></div>
  </main>

  <!-- VIEW BOOKING DETAIL MODAL -->
  <div id="view-booking-modal" style="display:none; position:fixed; inset:0; z-index:9999; background:rgba(13,33,29,0.65); backdrop-filter:blur(6px); place-items:center; padding:16px;">
    <div class="card-streetside" style="width:min(540px, 100%); padding:26px; background:var(--cream);">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; border-bottom:2px solid var(--ink); padding-bottom:10px;">
        <h3 style="margin:0; font-size:1.2rem; text-transform:uppercase;" id="modal-booking-title">BOOKING RESERVATION VOUCHER</h3>
        <button onclick="document.getElementById('view-booking-modal').style.display='none'" class="button sand" style="padding:2px 8px; font-size:0.8rem;">✕</button>
      </div>

      <div id="modal-booking-content">
        <!-- JS rendered -->
      </div>

      <div class="modal-footer-btns" style="margin-top:20px; display:flex; justify-content:flex-end; gap:10px;">
        <button type="button" onclick="document.getElementById('view-booking-modal').style.display='none'" class="button sand" style="padding:8px 16px; font-size:0.8rem;">Close</button>
        <button type="button" id="modal-print-btn" onclick="printSelectedReceipt()" class="button lime" style="padding:8px 18px; font-size:0.8rem;"><i class="bi bi-printer-fill"></i> Print Receipt</button>
      </div>
    </div>
  </div>

  <!-- CANCEL RESERVATION CONFIRMATION MODAL -->
  <div id="cancel-booking-modal" style="display:none; position:fixed; inset:0; z-index:9999; background:rgba(13,33,29,0.65); backdrop-filter:blur(6px); place-items:center; padding:16px;">
    <div class="card-streetside" style="width:min(480px, 100%); padding:26px; background:var(--cream);">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; border-bottom:2px solid var(--ink); padding-bottom:10px;">
        <h3 style="margin:0; font-size:1.15rem; text-transform:uppercase; color:var(--coral);"><i class="bi bi-exclamation-triangle-fill"></i> CANCEL RESERVATION</h3>
        <button onclick="document.getElementById('cancel-booking-modal').style.display='none'" class="button sand" style="padding:2px 8px; font-size:0.8rem;">✕</button>
      </div>

      <input type="hidden" id="cancel-modal-booking-id">

      <div style="background:var(--white); border:2px solid var(--ink); border-radius:12px; padding:16px; margin-bottom:18px;">
        <p style="font-size:0.95rem; font-weight:800; color:var(--ink); margin:0; line-height:1.45;">
          Are you sure you want to cancel reservation <span id="cancel-modal-ref" style="color:var(--coral); font-family:'DM Mono', monospace;">PB-20260816-0003</span>?
        </p>
      </div>

      <div style="margin-bottom:18px;">
        <label for="cancel-modal-reason" style="font-family:'DM Mono', monospace; font-weight:800; font-size:0.75rem; text-transform:uppercase; display:block; margin-bottom:6px;">
          CANCELLATION REASON / NOTE (OPTIONAL)
        </label>
        <textarea id="cancel-modal-reason" rows="3" placeholder="e.g., Unexpected schedule conflict, Weather condition, Personal emergency..." class="filter-ctrl" style="width:100%; box-sizing:border-box; background:var(--white);"></textarea>
      </div>

      <div class="modal-footer-btns" style="display:flex; justify-content:flex-end; gap:10px;">
        <button type="button" onclick="document.getElementById('cancel-booking-modal').style.display='none'" class="button sand" style="padding:9px 16px; font-size:0.82rem;">Nevermind</button>
        <button type="button" onclick="submitCancelReservationModal()" class="button coral" style="padding:9px 20px; font-size:0.82rem;"><i class="bi bi-x-circle-fill"></i> Confirm Cancellation</button>
      </div>
    </div>
  </div>

  <!-- jQuery & DataTables CDN -->
  <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
  <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
  <script src="/pikvero/assets/js/core/toast.js"></script>
  <script src="/pikvero/assets/js/core/ajax.js"></script>
  <script src="/pikvero/assets/js/core/auth.js"></script>
  <script src="/pikvero/assets/js/components/navbar.js?v=2"></script>
  <script src="/pikvero/assets/js/components/bottom-nav.js?v=<?= filemtime(__DIR__ . '/../../assets/js/components/bottom-nav.js') ?>"></script>
  <script src="/pikvero/assets/js/components/sidebar.js"></script>
  <script src="/pikvero/assets/js/components/footer.js"></script>
  <link rel="stylesheet" href="/pikvero/assets/css/bookings-mobile.css?v=<?= filemtime(__DIR__.'/../../assets/css/bookings-mobile.css') ?>">
  <script src="/pikvero/assets/js/components/bookings-mobile.js?v=<?= filemtime(__DIR__.'/../../assets/js/components/bookings-mobile.js') ?>"></script>
  <link rel="stylesheet" href="/pikvero/assets/css/booking-details-mobile.css?v=<?= filemtime(__DIR__.'/../../assets/css/booking-details-mobile.css') ?>">
  <script src="/pikvero/assets/js/vendor/qrcode.min.js"></script>
  <link rel="stylesheet" href="/pikvero/assets/js/vendor/leaflet.css">
  <script src="/pikvero/assets/js/vendor/leaflet.js"></script>
  <script src="/pikvero/assets/js/components/booking-details-mobile.js?v=<?= filemtime(__DIR__.'/../../assets/js/components/booking-details-mobile.js') ?>"></script>
  <script>
    let bookingsDataTable = null;
    let currentBookingsMap = {};
    let activeBookingId = null;

    document.addEventListener('DOMContentLoaded', async () => {
      await NavbarComponent.render('#navbar-container', true);
      SidebarComponent.render('bookings', 'customer');
      FooterComponent.render('#footer-container', true);
      if (matchMedia('(max-width:768px)').matches) {
        window.initMobileBookings();
        return;
      }

      // Initialize Server-side DataTables
      bookingsDataTable = $('#customer-bookings-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
          url: '/pikvero/api/customer/bookings.php',
          type: 'GET',
          data: function (d) {
            d.status = $('#filter-status').val();
            d.start_date = $('#filter-start-date').val();
            d.end_date = $('#filter-end-date').val();
          },
          dataSrc: function (json) {
            currentBookingsMap = {};
            if (json.data && Array.isArray(json.data)) {
              json.data.forEach(b => { currentBookingsMap[b.id] = b; });
            }
            return json.data || [];
          }
        },
        order: [[4, 'desc']],
        columns: [
          // Column 0: Booking Ref & Status Badges
          {
            data: 'booking_reference',
            render: function (data, type, row) {
              const st = (row.booking_status || 'confirmed').toLowerCase();
              const badgeCls = (st === 'confirmed') ? 'lime' : (st === 'pending' ? 'sky' : 'coral');
              const paySt = (row.payment_status || 'unpaid').toLowerCase();
              const payBadgeCls = (paySt === 'paid' || paySt === 'completed') ? 'lime' : 'coral';
              
              return `
                <div class="mobile-card-header">
                  <span style="font-weight:800; font-family:'DM Mono', monospace; font-size:0.85rem; color:var(--ink);">${data}</span>
                  <div style="display:flex; gap:4px; align-items:center;">
                    <span class="badge-streetside ${badgeCls}" style="font-size:0.62rem; padding:2px 6px;">${st.toUpperCase()}</span>
                    <span class="badge-streetside ${payBadgeCls}" style="font-size:0.60rem; padding:2px 6px;">${paySt.toUpperCase()}</span>
                  </div>
                </div>
              `;
            }
          },
          // Column 1: Facility & Court
          {
            data: 'court_name',
            render: function (data, type, row) {
              const facName = row.facility_name || 'Facility';
              return `
                <div>
                  <div style="font-weight:800; font-size:0.9rem; color:var(--ink);">${data}</div>
                  <div style="font-size:0.75rem; color:#4a5c56;"><i class="bi bi-building"></i> ${facName}</div>
                </div>
              `;
            }
          },
          // Column 2: Reservation Date & Time
          {
            data: 'booking_date',
            render: function (data, type, row) {
              const startTime = (row.start_time || '').substring(0, 5);
              const endTime = (row.end_time || '').substring(0, 5);
              return `
                <div style="font-size:0.78rem; font-weight:700; color:var(--green);">
                  <i class="bi bi-calendar3"></i> ${data} &bull; <i class="bi bi-clock"></i> ${startTime}-${endTime}
                </div>
              `;
            }
          },
          // Column 3: Amount
          {
            data: 'total_amount',
            render: function (data) {
              const amt = parseFloat(data || 0).toFixed(2);
              return `<div style="font-weight:900; font-size:0.95rem; color:var(--ink);">₱${amt}</div>`;
            }
          },
          // Column 4: Date Created
          {
            data: 'created_at',
            className: 'mobile-hide-col',
            render: function (data) {
              if (!data) return '<span style="color:#aaa;">—</span>';
              const dt = new Date(data);
              return `<span style="font-family:'DM Mono', monospace; font-size:0.75rem;">${dt.toLocaleDateString('en-PH', { month: 'short', day: 'numeric' })}</span>`;
            }
          },
          // Column 5: Actions
          {
            data: null,
            orderable: false,
            render: function (data, type, row) {
              const st = (row.booking_status || 'confirmed').toLowerCase();
              const canCancel = (st === 'confirmed' || st === 'pending');

              return `
                <div class="mobile-card-actions">
                  <button type="button" onclick="openBookingVoucherModal(${row.id})" class="button lime" style="padding:4px 8px; font-size:0.72rem;" title="View Voucher Details">
                    <i class="bi bi-ticket-detailed-fill"></i> Voucher
                  </button>
                  <a href="/pikvero/public/receipt.php?payment_id=${row.id}" target="_blank" class="button sand" style="padding:4px 8px; font-size:0.72rem;" title="Print Receipt">
                    <i class="bi bi-printer"></i>
                  </a>
                  ${canCancel ? `
                    <button type="button" onclick="cancelReservation(${row.id}, '${row.booking_reference}')" class="button coral" style="padding:4px 6px; font-size:0.72rem;" title="Cancel">
                      <i class="bi bi-x-circle"></i>
                    </button>
                  ` : ''}
                </div>
              `;
            }
          }
        ],
        language: {
          emptyTable: `
            <div style="padding:30px; text-align:center;">
              <i class="bi bi-ticket-perforated" style="font-size:2rem; color:#888; display:block; margin-bottom:8px;"></i>
              <strong style="font-size:1rem; text-transform:uppercase;">No Court Bookings Found</strong>
              <p style="font-size:0.82rem; color:#5a7060; margin-top:4px;">You have no reservations matching your filter search.</p>
              <a href="/pikvero/public/customer/search.php" class="button coral" style="padding:8px 16px; font-size:0.8rem; margin-top:10px; inline-block;">Find Courts &amp; Book</a>
            </div>
          `
        }
      });
    });

    function reloadBookingsTable() {
      if (matchMedia('(max-width:768px)').matches && window.reloadMobileBookings) {
        window.reloadMobileBookings();
        return;
      }
      if (bookingsDataTable) {
        bookingsDataTable.ajax.reload();
      }
    }

    function resetBookingsFilters() {
      document.getElementById('filter-status').value = 'all';
      document.getElementById('filter-start-date').value = '';
      document.getElementById('filter-end-date').value = '';
      reloadBookingsTable();
    }

    function openBookingVoucherModal(bookingId) {
      activeBookingId = bookingId;
      const b = currentBookingsMap[bookingId];
      if (!b) return;

      const st = (b.booking_status || 'confirmed').toLowerCase();
      const badgeCls = (st === 'confirmed') ? 'lime' : (st === 'pending' ? 'sky' : 'coral');
      const startTime = (b.start_time || '').substring(0, 5);
      const endTime = (b.end_time || '').substring(0, 5);

      let pmText = 'Cash (Pay at Counter)';
      let pmIcon = 'bi-cash-coin';
      const rawPm = String(b.payment_method || b.actual_payment_method || '').toLowerCase();

      if (rawPm === 'online' || rawPm === 'gcash' || rawPm === 'paymaya' || rawPm === 'maya' || rawPm === 'card' || rawPm === 'qrph') {
        pmText = 'GCash / PayMongo Online';
        pmIcon = 'bi-phone';
      } else if (rawPm === 'cash') {
        pmText = 'Cash (Pay at Counter)';
        pmIcon = 'bi-cash-coin';
      } else if (b.payment_status === 'paid') {
        pmText = 'GCash / PayMongo Online';
        pmIcon = 'bi-phone';
      }

      document.getElementById('modal-booking-content').innerHTML = `
        <div style="background:#f8faf9; border:2px solid var(--ink); border-radius:12px; padding:14px; margin-bottom:14px;">
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; flex-wrap:wrap; gap:6px;">
            <div>
              <div style="font-size:0.7rem; font-weight:800; font-family:'DM Mono', monospace; color:#4a5c56;">BOOKING REFERENCE</div>
              <div style="font-weight:900; font-family:'DM Mono', monospace; font-size:1.1rem; color:var(--ink);">${b.booking_reference}</div>
            </div>
            <div>
              <span class="badge-streetside ${badgeCls}" style="font-size:0.75rem;">${st.toUpperCase()}</span>
            </div>
          </div>

          <div class="voucher-detail-grid" style="border-top:1px dashed var(--ink); padding-top:10px; display:grid; grid-template-columns:1fr 1fr; gap:10px; font-size:0.85rem;">
            <div>
              <span style="font-size:0.7rem; font-weight:800; color:#4a5c56; display:block;">FACILITY</span>
              <strong>${b.facility_name || 'Facility'}</strong>
            </div>
            <div>
              <span style="font-size:0.7rem; font-weight:800; color:#4a5c56; display:block;">COURT</span>
              <strong style="color:#2563eb;">${b.court_name || 'Court'}</strong>
            </div>
            <div>
              <span style="font-size:0.7rem; font-weight:800; color:#4a5c56; display:block;">RESERVATION DATE</span>
              <strong>${b.booking_date}</strong>
            </div>
            <div>
              <span style="font-size:0.7rem; font-weight:800; color:#4a5c56; display:block;">TIME SLOT</span>
              <strong style="color:var(--green);">${startTime} - ${endTime}</strong>
            </div>
            <div>
              <span style="font-size:0.7rem; font-weight:800; color:#4a5c56; display:block;">PAYMENT METHOD</span>
              <strong style="font-size:0.82rem;"><i class="bi ${pmIcon}"></i> ${pmText}</strong>
            </div>
            <div>
              <span style="font-size:0.7rem; font-weight:800; color:#4a5c56; display:block;">PAYMENT STATUS</span>
              <span class="badge-streetside ${(b.payment_status === 'paid' || b.payment_status === 'completed') ? 'lime' : 'coral'}" style="font-size:0.65rem;">${(b.payment_status || 'unpaid').toUpperCase()}</span>
            </div>
            <div>
              <span style="font-size:0.7rem; font-weight:800; color:#4a5c56; display:block;">DATE CREATED</span>
              <strong style="font-size:0.78rem; font-family:'DM Mono', monospace; color:var(--ink);">${b.created_at || '—'}</strong>
            </div>
            <div style="grid-column: 1 / -1; border-top:1px dashed #ccc; padding-top:8px;">
              <span style="font-size:0.7rem; font-weight:800; color:#4a5c56; display:block;">TOTAL AMOUNT</span>
              <strong style="font-size:1.1rem; color:var(--ink);">₱${parseFloat(b.total_amount || 0).toFixed(2)}</strong>
            </div>
          </div>
        </div>

        <div class="voucher-barcode-box" style="background:var(--white); border:2px solid var(--ink); border-radius:12px; padding:12px; text-align:center; overflow:hidden;">
          <div style="font-family:'DM Mono', monospace; font-size:1.1rem; font-weight:900; letter-spacing:3px; word-break:break-all;">||||||||||||||||||||||||</div>
          <div style="font-size:0.7rem; color:#4a5c56; font-weight:800; margin-top:4px;">OFFICIAL PIKVERO RESERVATION VOUCHER</div>
        </div>
      `;

      const modal = document.getElementById('view-booking-modal');
      if (modal) {
        modal.style.display = 'grid';
      }
    }

    function printSelectedReceipt() {
      if (activeBookingId) {
        window.open(`/pikvero/public/receipt.php?payment_id=${activeBookingId}`, '_blank');
      }
    }

    function cancelReservation(bookingId, ref) {
      document.getElementById('cancel-modal-booking-id').value = bookingId;
      document.getElementById('cancel-modal-ref').innerText = ref || `ID #${bookingId}`;
      document.getElementById('cancel-modal-reason').value = '';

      const modal = document.getElementById('cancel-booking-modal');
      if (modal) {
        modal.style.display = 'grid';
      }
    }

    async function submitCancelReservationModal() {
      const bookingId = document.getElementById('cancel-modal-booking-id').value;
      const ref = document.getElementById('cancel-modal-ref').innerText;
      const reason = document.getElementById('cancel-modal-reason').value.trim();

      if (!bookingId) return;

      try {
        const formData = new FormData();
        formData.append('action', 'cancel');
        formData.append('booking_id', bookingId);
        formData.append('reason', reason);

        const res = await Api.post('/pikvero/api/customer/bookings.php', formData);
        if (res && res.success) {
          Toast.success('Reservation Cancelled', `Booking ${ref} has been cancelled.`);
          document.getElementById('cancel-booking-modal').style.display = 'none';
          reloadBookingsTable();
        } else {
          Toast.error('Cancellation Failed', (res && res.message) ? res.message : 'Could not cancel booking.');
        }
      } catch (err) {
        console.error('Cancel Error:', err);
        Toast.error('Cancellation Error', err.message || 'An unexpected error occurred while cancelling.');
      }
    }

    <?php if ($paymentSuccessNotice): ?>
      $(document).ready(function() {
        setTimeout(function() {
          if (!matchMedia('(max-width:768px)').matches) Toast.success('Payment Confirmed!', 'Reservation <?= $confirmedBookingRef ?> is now paid & confirmed.');
        }, 400);
      });
    <?php endif; ?>
  </script>
  <?php if ($confirmationData): ?>
  <link rel="stylesheet" href="/pikvero/assets/css/booking-confirmed.css?v=<?= filemtime(__DIR__.'/../../assets/css/booking-confirmed.css') ?>">
  <script src="/pikvero/assets/js/components/booking-confirmed.js?v=<?= filemtime(__DIR__.'/../../assets/js/components/booking-confirmed.js') ?>"></script>
  <script>if (matchMedia('(max-width:768px)').matches) showBookingConfirmed(<?= json_encode($confirmationData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>);</script>
  <?php endif; ?>
</body>
</html>
