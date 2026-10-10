<?php
require_once __DIR__ . '/../../app/bootstrap.php';

use App\Core\Auth\Auth;
use App\Infrastructure\Repositories\FacilityRepository;

Auth::requirePermission('calendar.view');
$canManage = Auth::can('calendar.manage');

$facilityRepo = new FacilityRepository();
$role = Auth::role();
$facilities = [];
if ($role === 'super_admin' || $role === 'platform_admin') {
    $facilities = $facilityRepo->getAllActive();
} else {
    $orgId = Auth::organizationId();
    if ($orgId) {
        $facilities = $facilityRepo->findByOrganizationId($orgId);
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Pikvero — Visual Court &amp; Open Play Schedule Calendar</title>
  <?php $favLogo = class_exists(\App\Core\Auth\Auth::class) ? \App\Core\Auth\Auth::getLogoUrl() : '/pikvero/assets/images/logo.png'; ?>
  <!-- Favicon / Tab Icon -->
  <link rel="icon" type="image/png" href="<?= htmlspecialchars($favLogo) ?>">
  <link rel="shortcut icon" type="image/png" href="<?= htmlspecialchars($favLogo) ?>">
  <link rel="apple-touch-icon" href="<?= htmlspecialchars($favLogo) ?>">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="/pikvero/assets/css/streetside-theme.css">
  <link rel="stylesheet" href="/pikvero/assets/css/modal.css">
  <link rel="stylesheet" href="/pikvero/assets/css/toast.css">
  <!-- FullCalendar 6 -->
  <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
  <style>
    /* FullCalendar Streetside Styling Overrides */
    .fc {
      font-family: 'Manrope', system-ui, sans-serif;
      color: var(--ink);
    }
    .fc .fc-toolbar {
      flex-wrap: wrap;
      gap: 12px;
      margin-bottom: 20px !important;
    }
    .fc .fc-toolbar-title {
      font-weight: 800;
      font-size: clamp(1.15rem, 2.5vw, 1.45rem);
      text-transform: uppercase;
      letter-spacing: -0.03em;
      font-family: 'DM Mono', monospace;
      color: var(--ink);
    }
    .fc .fc-button-primary {
      background: var(--sand) !important;
      color: var(--ink) !important;
      border: 2px solid var(--ink) !important;
      border-radius: 8px !important;
      font-weight: 800 !important;
      font-family: 'DM Mono', monospace;
      font-size: 0.78rem !important;
      text-transform: uppercase;
      padding: 6px 14px !important;
      box-shadow: 2px 2px 0 var(--ink) !important;
      transition: all 0.15s ease;
      cursor: pointer;
    }
    .fc .fc-button-primary:hover,
    .fc .fc-button-primary:focus {
      background: var(--lime) !important;
      color: var(--ink) !important;
      box-shadow: 3px 3px 0 var(--ink) !important;
    }
    .fc .fc-button-primary:disabled {
      opacity: 0.45;
      cursor: not-allowed;
    }
    .fc .fc-button-active {
      background: var(--coral) !important;
      color: var(--white) !important;
    }
    .fc-theme-standard td, .fc-theme-standard th {
      border-color: rgba(13, 33, 29, 0.15) !important;
    }
    .fc-col-header-cell {
      background: var(--sand);
      padding: 10px 0 !important;
      font-family: 'DM Mono', monospace;
      font-weight: 800;
      font-size: 0.78rem;
      text-transform: uppercase;
      border-bottom: 2px solid var(--ink) !important;
    }
    .fc-timegrid-slot-label {
      font-family: 'DM Mono', monospace;
      font-size: 0.72rem;
      font-weight: 700;
      color: #4a5c56;
    }
    .fc-timegrid-now-indicator-line {
      border-color: var(--coral) !important;
      border-width: 2px !important;
    }
    .fc-timegrid-now-indicator-arrow {
      border-color: var(--coral) !important;
    }
    .fc-day-today {
      background: rgba(223, 255, 79, 0.12) !important;
    }

    /* Custom Event Pill Styles */
    .fc-event {
      border-radius: 8px !important;
      border: 1.5px solid var(--ink) !important;
      padding: 4px 8px !important;
      font-size: 0.78rem !important;
      font-weight: 800 !important;
      cursor: pointer !important;
      box-shadow: 2px 2px 0 var(--ink);
      transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .fc-event:hover {
      transform: translateY(-2px);
      box-shadow: 3px 3px 0 var(--ink);
      z-index: 10 !important;
    }
    .cal-ev-booking {
      background: #0b4d40 !important;
      color: #ffffff !important;
      border-left: 5px solid var(--lime) !important;
    }
    .cal-ev-openplay {
      background: #ea580c !important;
      color: #ffffff !important;
      border-left: 5px solid var(--ink) !important;
    }

    /* Calendar KPI Card */
    .cal-kpi-card {
      background: var(--white);
      border: 2px solid var(--ink);
      border-radius: 12px;
      padding: 14px 16px;
      box-shadow: 3px 3px 0 var(--ink);
      display: flex;
      flex-direction: column;
      justify-content: space-between;
    }
    .cal-kpi-val {
      font-size: 1.6rem;
      font-weight: 900;
      line-height: 1.1;
      margin: 4px 0 2px;
      font-family: 'DM Mono', monospace;
    }
    .cal-kpi-lbl {
      font-size: 0.7rem;
      font-weight: 800;
      text-transform: uppercase;
      font-family: 'DM Mono', monospace;
      color: #4a5c56;
    }

    /* Progress bar for capacity */
    .cal-cap-bar {
      height: 6px;
      border-radius: 3px;
      background: #e2e8f0;
      overflow: hidden;
      margin-top: 4px;
    }
    .cal-cap-fill {
      height: 100%;
      background: var(--coral);
      transition: width 0.3s ease;
    }
  </style>
<?php require_once __DIR__ . '/../../includes/subscription-gate.php'; ?>
</head>
<body>

  <div id="sidebar-container"></div>
  <div id="navbar-container"></div>

  <main class="portal-main">
    <div>
      <!-- TOP HEADER & ACTION BUTTONS -->
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:12px;">
        <div>
          <div class="eyebrow"><i class="bi bi-calendar3-range"></i> FACILITY OPERATIONS &amp; SCHEDULING</div>
          <h1 style="font-size: clamp(1.8rem, 4vw, 2.3rem); font-weight:800; text-transform:uppercase; margin:4px 0 0;">OPERATIONS CALENDAR</h1>
        </div>
        <div style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
          <a href="/pikvero/public/admin/open-play.php" class="button coral" style="padding:9px 16px; font-size:0.82rem;">
            <i class="bi bi-dribbble"></i> Open Play Sessions
          </a>
          <a href="/pikvero/public/admin/bookings.php" class="button lime" style="padding:9px 16px; font-size:0.82rem;">
            <i class="bi bi-calendar-check-fill"></i> Court Reservations
          </a>
          <a href="/pikvero/public/admin/courts.php" class="button sand" style="padding:9px 16px; font-size:0.82rem;">
            <i class="bi bi-slash-circle-fill"></i> Block Schedules
          </a>
        </div>
      </div>

      <!-- KPI METRIC CARDS -->
      <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap:14px; margin-bottom:20px;">
        <div class="cal-kpi-card" style="background:var(--white);">
          <div class="cal-kpi-lbl"><i class="bi bi-calendar-event"></i> Total Scheduled Events</div>
          <div class="cal-kpi-val" id="kpi-total-events" style="color:var(--ink);">0</div>
          <div style="font-size:0.75rem; color:#4a5c56;">In current calendar view</div>
        </div>

        <div class="cal-kpi-card" style="background:#f0fdf4; border-color:#15803d;">
          <div class="cal-kpi-lbl" style="color:#15803d;"><i class="bi bi-calendar-check-fill"></i> Court Reservations</div>
          <div class="cal-kpi-val" id="kpi-booking-count" style="color:#15803d;">0</div>
          <div style="font-size:0.75rem; color:#166534;" id="kpi-booking-hours">0 total court hours</div>
        </div>

        <div class="cal-kpi-card" style="background:#fff7ed; border-color:#c2410c;">
          <div class="cal-kpi-lbl" style="color:#c2410c;"><i class="bi bi-dribbble"></i> Open Play Socials</div>
          <div class="cal-kpi-val" id="kpi-openplay-count" style="color:#c2410c;">0</div>
          <div style="font-size:0.75rem; color:#9a3412;" id="kpi-openplay-players">0 players registered</div>
        </div>

        <div class="cal-kpi-card" style="background:var(--lime); border-color:var(--ink);">
          <div class="cal-kpi-lbl" style="color:var(--ink);"><i class="bi bi-cash-stack"></i> Period Revenue</div>
          <div class="cal-kpi-val" id="kpi-total-revenue" style="color:var(--ink);">₱0.00</div>
          <div style="font-size:0.75rem; color:#1a3d34;">Bookings &amp; Open Play Fees</div>
        </div>
      </div>

      <!-- FILTER & CONTROLS TOOLBAR -->
      <div class="card-streetside" style="padding:16px 20px; background:var(--white); margin-bottom:20px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px;">
        <div style="display:flex; gap:14px; flex-wrap:wrap; align-items:center;">
          <!-- Facility Filter -->
          <div>
            <label class="mono" style="font-size:0.72rem; display:block; margin-bottom:4px; font-weight:800;">
              <i class="bi bi-building"></i> FACILITY:
            </label>
            <select id="cal-facility-filter" onchange="refetchCalendarEvents()" style="padding:8px 14px; border:2px solid var(--ink); border-radius:8px; font-weight:800; font-family:inherit; font-size:0.85rem; background:var(--sand);">
              <option value="">All Facilities (<?= count($facilities) ?>)</option>
              <?php foreach ($facilities as $fac): ?>
                <option value="<?= $fac['id'] ?>"><?= htmlspecialchars($fac['name']) ?> (<?= htmlspecialchars($fac['city'] ?? 'Bohol') ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Schedule Type Filter -->
          <div>
            <label class="mono" style="font-size:0.72rem; display:block; margin-bottom:4px; font-weight:800;">
              <i class="bi bi-filter-circle-fill"></i> SCHEDULE TYPE:
            </label>
            <select id="cal-event-type-filter" onchange="refetchCalendarEvents()" style="padding:8px 14px; border:2px solid var(--ink); border-radius:8px; font-weight:800; font-family:'DM Mono', monospace; font-size:0.82rem; background:var(--white);">
              <option value="all">ALL SCHEDULES</option>
              <option value="booking">COURT BOOKINGS ONLY</option>
              <option value="open_play">OPEN PLAY SESSIONS ONLY</option>
            </select>
          </div>

          <!-- Quick Refresh Button -->
          <div style="align-self:flex-end;">
            <button type="button" onclick="refetchCalendarEvents()" class="button sand" style="padding:8px 12px; font-size:0.82rem;" title="Refresh Calendar Data">
              <i class="bi bi-arrow-clockwise"></i> Refresh
            </button>
          </div>
        </div>

        <!-- LEGEND & INDICATORS -->
        <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
          <span class="mono" style="font-size:0.72rem; font-weight:800; color:var(--ink);">LEGEND:</span>
          <span class="badge-streetside green" style="font-size:0.75rem; display:inline-flex; align-items:center; gap:5px;">
            <i class="bi bi-circle-fill" style="color:var(--lime); font-size:0.65rem;"></i> Court Reservation
          </span>
          <span class="badge-streetside coral" style="font-size:0.75rem; display:inline-flex; align-items:center; gap:5px;">
            <i class="bi bi-dribbble" style="font-size:0.75rem;"></i> Open Play Social
          </span>
          <span class="badge-streetside sky" style="font-size:0.75rem; display:inline-flex; align-items:center; gap:5px;">
            <i class="bi bi-clock-history"></i> 12-Hour View
          </span>
        </div>
      </div>

      <!-- FULLCALENDAR MAIN CANVAS -->
      <div class="card-streetside" style="padding:24px; background:var(--white); margin-bottom:24px;">
        <div id="calendar-container"></div>
      </div>
    </div>

    <div id="footer-container"></div>
  </main>

  <!-- EVENT DETAILS MODAL -->
  <div class="modal-overlay" id="event-details-modal" style="display:none; align-items:center; justify-content:center;" onclick="if(event.target===this) closeModal('event-details-modal');">
    <div class="card-streetside modal-card-scrollable" style="width:min(520px, 95vw); max-height:90vh; overflow-y:auto; padding:24px; background:var(--white); box-shadow:6px 6px 0 var(--ink);">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; border-bottom:2px solid var(--ink); padding-bottom:12px;">
        <div style="display:flex; align-items:center; gap:8px;">
          <h3 style="font-size:1.15rem; font-weight:800; text-transform:uppercase; margin:0;" id="event-modal-title">SCHEDULE DETAILS</h3>
        </div>
        <button type="button" onclick="closeModal('event-details-modal')" class="button sand" style="padding:2px 8px; font-size:0.85rem; font-weight:900;">✕</button>
      </div>

      <div id="event-modal-body" style="font-size:0.88rem;">
        <!-- Loaded via JS -->
      </div>

      <div style="margin-top:20px; border-top:1.5px dashed var(--line); padding-top:14px; display:flex; justify-content:flex-end; gap:8px; flex-wrap:wrap;" id="event-modal-footer">
        <button type="button" onclick="closeModal('event-details-modal')" class="button sand" style="padding:8px 16px; font-size:0.82rem;">Close</button>
      </div>
    </div>
  </div>

  <script src="/pikvero/assets/js/core/toast.js"></script>
  <script src="/pikvero/assets/js/core/ajax.js"></script>
  <script src="/pikvero/assets/js/core/auth.js"></script>
  <script src="/pikvero/assets/js/components/navbar.js"></script>
  <script src="/pikvero/assets/js/components/sidebar.js"></script>
  <script src="/pikvero/assets/js/components/footer.js"></script>
  <script>
    let calendar = null;
    let currentFetchedEvents = [];

    document.addEventListener('DOMContentLoaded', async () => {
      await NavbarComponent.render('#navbar-container', true);
      SidebarComponent.render('calendar', 'admin');
      FooterComponent.render('#footer-container', true);

      const calendarEl = document.getElementById('calendar-container');
      calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'timeGridWeek',
        headerToolbar: {
          left: 'prev,next today',
          center: 'title',
          right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
        },
        slotMinTime: '06:00:00',
        slotMaxTime: '23:00:00',
        allDaySlot: false,
        nowIndicator: true,
        height: 'auto',
        slotLabelFormat: {
          hour: 'numeric',
          minute: '2-digit',
          meridiem: 'short',
          hour12: true
        },
        eventTimeFormat: {
          hour: 'numeric',
          minute: '2-digit',
          meridiem: 'short',
          hour12: true
        },
        events: function(info, successCallback, failureCallback) {
          const facId = document.getElementById('cal-facility-filter').value;
          const evType = document.getElementById('cal-event-type-filter').value;

          Api.get('/pikvero/api/admin/calendar.php', {
            start: info.startStr,
            end: info.endStr,
            facility_id: facId,
            event_type: evType
          }).then(data => {
            currentFetchedEvents = Array.isArray(data) ? data : [];
            updateKpiMetrics(currentFetchedEvents);
            successCallback(currentFetchedEvents);
          }).catch(err => {
            console.error('Calendar Fetch Error:', err);
            failureCallback(err);
          });
        },
        eventClick: function(info) {
          showEventDetails(info.event);
        }
      });

      calendar.render();
    });

    function refetchCalendarEvents() {
      if (calendar) {
        calendar.refetchEvents();
      }
    }

    function formatTime12h(timeStr) {
      if (!timeStr) return '';
      if (String(timeStr).includes('AM') || String(timeStr).includes('PM')) return timeStr;
      const parts = String(timeStr).trim().split(':');
      let h = parseInt(parts[0], 10);
      if (isNaN(h)) return timeStr;
      const m = parts[1] ? parts[1].substring(0, 2) : '00';
      const suffix = h >= 12 ? 'PM' : 'AM';
      h = h % 12;
      if (h === 0) h = 12;
      return `${h}:${m} ${suffix}`;
    }

    function updateKpiMetrics(events) {
      let totalBookings = 0;
      let totalOpenPlay = 0;
      let totalBookingHours = 0;
      let totalOpenPlayPlayers = 0;
      let totalRevenue = 0;

      events.forEach(e => {
        const p = e.extendedProps || {};
        if (p.type === 'booking') {
          totalBookings++;
          const price = parseFloat(p.total_price || 0);
          totalRevenue += price;

          if (p.start_time && p.end_time) {
            const h1 = parseInt(p.start_time.split(':')[0], 10) || 0;
            const h2 = parseInt(p.end_time.split(':')[0], 10) || 0;
            const diff = Math.max(1, h2 - h1);
            totalBookingHours += diff;
          } else {
            totalBookingHours += 1;
          }
        } else if (p.type === 'open_play') {
          totalOpenPlay++;
          const regs = parseInt(p.registered_players || 0, 10);
          const fee = parseFloat(p.fee_per_player || 70);
          totalOpenPlayPlayers += regs;
          totalRevenue += (regs * fee);
        }
      });

      document.getElementById('kpi-total-events').innerText = events.length;
      document.getElementById('kpi-booking-count').innerText = totalBookings;
      document.getElementById('kpi-booking-hours').innerText = totalBookingHours + ' total court hour' + (totalBookingHours !== 1 ? 's' : '');
      document.getElementById('kpi-openplay-count').innerText = totalOpenPlay;
      document.getElementById('kpi-openplay-players').innerText = totalOpenPlayPlayers + ' player' + (totalOpenPlayPlayers !== 1 ? 's' : '') + ' registered';
      document.getElementById('kpi-total-revenue').innerText = '₱' + totalRevenue.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function openModal(id) {
      const el = document.getElementById(id);
      if (el) {
        el.classList.add('active');
        el.style.display = 'flex';
      }
    }

    function closeModal(id) {
      const el = document.getElementById(id);
      if (el) {
        el.classList.remove('active');
        el.style.display = 'none';
      }
    }

    function showEventDetails(event) {
      const props = event.extendedProps || {};
      const body = document.getElementById('event-modal-body');
      const footer = document.getElementById('event-modal-footer');

      if (props.type === 'booking') {
        const start12 = formatTime12h(props.start_time);
        const end12 = formatTime12h(props.end_time);

        document.getElementById('event-modal-title').innerHTML = `
          <i class="bi bi-calendar-check-fill" style="color:var(--green);"></i> COURT RESERVATION
        `;

        body.innerHTML = `
          <div style="display:flex; gap:8px; margin-bottom:14px; flex-wrap:wrap;">
            <span class="badge-streetside lime">${props.booking_reference}</span>
            <span class="badge-streetside green">${(props.status||'confirmed').toUpperCase()}</span>
            <span class="badge-streetside ${(props.payment_status==='paid') ? 'lime' : 'coral'}">${(props.payment_status||'paid').toUpperCase()}</span>
          </div>

          <div style="background:var(--cream); border:2px solid var(--ink); border-radius:10px; padding:14px; margin-bottom:14px;">
            <div style="font-weight:800; font-size:1.05rem; text-transform:uppercase; margin-bottom:4px;">${props.court_name}</div>
            <div style="font-size:0.82rem; color:#4a5c56;"><i class="bi bi-building"></i> ${props.facility_name}</div>
          </div>

          <div style="background:var(--white); border:2px solid var(--ink); border-radius:10px; padding:14px; margin-bottom:14px;">
            <div style="font-weight:800; font-size:0.72rem; font-family:'DM Mono', monospace; text-transform:uppercase; color:var(--green); margin-bottom:8px;">CUSTOMER &amp; CONTACT</div>
            <div style="font-size:0.95rem; font-weight:800; margin-bottom:4px;"><i class="bi bi-person-circle"></i> ${props.customer_name}</div>
            <div style="font-size:0.82rem; color:#4a5c56;"><i class="bi bi-telephone-fill"></i> ${props.customer_phone}</div>
          </div>

          <div style="background:var(--sand); border:2px solid var(--ink); border-radius:10px; padding:14px;">
            <div style="display:flex; justify-content:space-between; margin-bottom:6px; font-size:0.84rem;">
              <span><i class="bi bi-calendar3"></i> <strong>Date:</strong></span>
              <span class="mono"><strong>${props.booking_date}</strong></span>
            </div>
            <div style="display:flex; justify-content:space-between; margin-bottom:6px; font-size:0.84rem;">
              <span><i class="bi bi-clock-history"></i> <strong>Schedule:</strong></span>
              <span class="mono"><strong>${start12} &ndash; ${end12}</strong></span>
            </div>
            <div style="border-top:1.5px dashed var(--ink); margin-top:10px; padding-top:10px; display:flex; justify-content:space-between; align-items:center;">
              <span style="font-size:0.82rem; font-weight:800; font-family:'DM Mono', monospace;">TOTAL BOOKING FEE:</span>
              <span style="font-size:1.25rem; font-weight:900; color:var(--green);">₱${parseFloat(props.total_price||0).toFixed(2)}</span>
            </div>
          </div>
        `;

        footer.innerHTML = `
          <button type="button" onclick="closeModal('event-details-modal')" class="button sand" style="padding:8px 16px; font-size:0.82rem;">Close</button>
          <a href="/pikvero/public/admin/bookings.php?search=${encodeURIComponent(props.booking_reference)}" class="button lime" style="padding:8px 16px; font-size:0.82rem;">
            <i class="bi bi-eye-fill"></i> View in Bookings
          </a>
        `;
      } else {
        const start12 = formatTime12h(props.start_time);
        const end12 = formatTime12h(props.end_time);
        const pct = Math.min(100, Math.round((props.registered_players / (props.max_players || 16)) * 100));

        document.getElementById('event-modal-title').innerHTML = `
          <i class="bi bi-dribbble" style="color:var(--coral);"></i> OPEN PLAY SESSION
        `;

        body.innerHTML = `
          <div style="display:flex; gap:8px; margin-bottom:14px; flex-wrap:wrap;">
            <span class="badge-streetside coral">OPEN PLAY</span>
            <span class="badge-streetside lime">${props.registered_players} / ${props.max_players} PLAYERS (${pct}%)</span>
            <span class="badge-streetside ${(props.status==='open') ? 'sky' : 'coral'}">${(props.status||'open').toUpperCase()}</span>
          </div>

          <div style="background:var(--cream); border:2px solid var(--ink); border-radius:10px; padding:14px; margin-bottom:14px;">
            <div style="font-weight:800; font-size:1.1rem; text-transform:uppercase; margin-bottom:4px;">${props.title}</div>
            <div style="font-size:0.82rem; color:#4a5c56;"><i class="bi bi-building"></i> ${props.facility_name}</div>
          </div>

          <div style="background:var(--white); border:2px solid var(--ink); border-radius:10px; padding:14px; margin-bottom:14px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
              <span class="mono" style="font-size:0.72rem; font-weight:800; color:var(--ink);">PLAYER CAPACITY:</span>
              <span class="mono" style="font-size:0.75rem; font-weight:800; color:var(--coral);">${props.registered_players} / ${props.max_players} SLOTS</span>
            </div>
            <div class="cal-cap-bar">
              <div class="cal-cap-fill" style="width:${pct}%;"></div>
            </div>
            <div style="display:flex; justify-content:space-between; font-size:0.78rem; color:#4a5c56; margin-top:8px;">
              <span><i class="bi bi-person-check-fill"></i> Checked-In: <strong>${props.checked_in_count || 0}</strong></span>
              <span><i class="bi bi-hourglass-split"></i> Available: <strong>${Math.max(0, props.max_players - props.registered_players)}</strong></span>
            </div>
          </div>

          <div style="background:var(--sand); border:2px solid var(--ink); border-radius:10px; padding:14px;">
            <div style="display:flex; justify-content:space-between; margin-bottom:6px; font-size:0.84rem;">
              <span><i class="bi bi-calendar3"></i> <strong>Date:</strong></span>
              <span class="mono"><strong>${props.session_date}</strong></span>
            </div>
            <div style="display:flex; justify-content:space-between; margin-bottom:6px; font-size:0.84rem;">
              <span><i class="bi bi-clock-history"></i> <strong>Schedule:</strong></span>
              <span class="mono"><strong>${start12} &ndash; ${end12}</strong></span>
            </div>
            <div style="border-top:1.5px dashed var(--ink); margin-top:10px; padding-top:10px; display:flex; justify-content:space-between; align-items:center;">
              <span style="font-size:0.82rem; font-weight:800; font-family:'DM Mono', monospace;">ENTRY PASS FEE:</span>
              <span style="font-size:1.25rem; font-weight:900; color:var(--coral);">₱${parseFloat(props.fee_per_player||70).toFixed(2)} <span style="font-size:0.75rem; font-weight:600;">/ head</span></span>
            </div>
          </div>
        `;

        footer.innerHTML = `
          <button type="button" onclick="closeModal('event-details-modal')" class="button sand" style="padding:8px 16px; font-size:0.82rem;">Close</button>
          <a href="/pikvero/public/admin/open-play.php" class="button coral" style="padding:8px 16px; font-size:0.82rem;">
            <i class="bi bi-people-fill"></i> Open Play Roster
          </a>
        `;
      }

      openModal('event-details-modal');
    }
  </script>
</body>
</html>
