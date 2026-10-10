<?php
require_once __DIR__ . '/../../app/bootstrap.php';

use App\Core\Auth\Auth;
use App\Infrastructure\Repositories\FacilityRepository;

Auth::requirePermission('open_play.view');
$canCreate = Auth::can('open_play.create');
$canEdit = Auth::can('open_play.edit');
$canCheckin = Auth::can('open_play.checkin');
$canManagePlayers = Auth::can('open_play.manage_players');
$canRevenue = Auth::can('open_play.revenue');

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
  <title>Pikvero — Open Play &amp; Social Drop-in Sessions</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
  <link rel="stylesheet" href="/pikvero/assets/css/streetside-theme.css">
  <link rel="stylesheet" href="/pikvero/assets/css/modal.css">
  <link rel="stylesheet" href="/pikvero/assets/css/toast.css">
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
    .dataTables_wrapper {
      font-family: inherit;
      font-size: 0.85rem;
      color: var(--ink);
    }
    .dataTables_wrapper .dataTables_length select,
    .dataTables_wrapper .dataTables_filter input {
      border: 2px solid var(--ink) !important;
      border-radius: 8px !important;
      padding: 6px 10px !important;
      font-weight: 700 !important;
      background: var(--white);
      outline: none;
    }
    .dataTables_wrapper .dataTables_filter input { margin-left: 8px; }
    .dataTables_wrapper .dataTables_info {
      font-family: 'DM Mono', monospace;
      font-size: 0.78rem;
      color: #4a5c56;
      padding-top: 14px;
    }
    .dataTables_wrapper .dataTables_paginate { padding-top: 10px; }
    .dataTables_wrapper .dataTables_paginate .paginate_button {
      border: 2px solid var(--ink) !important;
      border-radius: 8px !important;
      background: var(--sand) !important;
      color: var(--ink) !important;
      font-weight: 800 !important;
      font-family: 'DM Mono', monospace;
      font-size: 0.78rem !important;
      padding: 5px 12px !important;
      margin: 0 3px !important;
      transition: all 0.15s ease;
      cursor: pointer !important;
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
      background: var(--lime) !important;
      color: var(--ink) !important;
      box-shadow: 2px 2px 0 var(--ink) !important;
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button.current,
    .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
      background: var(--coral) !important;
      color: var(--white) !important;
      border: 2px solid var(--ink) !important;
      box-shadow: 2px 2px 0 var(--ink) !important;
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button.disabled,
    .dataTables_wrapper .dataTables_paginate .paginate_button.disabled:hover {
      opacity: 0.4 !important;
      background: var(--sand) !important;
      color: var(--ink) !important;
      box-shadow: none !important;
      cursor: not-allowed !important;
    }
  </style>
<?php require_once __DIR__ . '/../../includes/subscription-gate.php'; ?>
</head>
<body>

  <div id="sidebar-container"></div>
  <div id="navbar-container"></div>

  <main class="portal-main">
    <div>
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:12px;">
        <div>
          <div class="eyebrow">VENUE OPERATIONS</div>
          <h1 style="font-size: clamp(1.8rem, 4vw, 2.2rem); font-weight:800; text-transform:uppercase; margin:4px 0 0;">OPEN PLAY SESSIONS</h1>
        </div>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
          <?php if ($canCreate): ?>
            <button type="button" onclick="openCreateSessionModal()" class="button coral" style="padding:9px 18px; font-size:0.84rem;">
              <i class="bi bi-plus-circle-fill"></i> Create Open Play Session
            </button>
          <?php endif; ?>
        </div>
      </div>

      <!-- METRIC CARDS -->
      <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:16px; margin-bottom:24px;">
        <div class="stat-card-mini" style="background:#e0f2fe;">
          <div style="display:flex; justify-content:space-between; align-items:center;">
            <span class="mono" style="font-size:0.75rem; font-weight:800; color:#0369a1;">SESSIONS</span>
            <i class="bi bi-dribbble" style="font-size:1.4rem; color:#0284c7;"></i>
          </div>
          <div style="font-size:1.8rem; font-weight:900; margin-top:8px;" id="stat-total-sessions">0</div>
        </div>

        <div class="stat-card-mini" style="background:#fef9c3;">
          <div style="display:flex; justify-content:space-between; align-items:center;">
            <span class="mono" style="font-size:0.75rem; font-weight:800; color:#a16207;">REGISTERED PLAYERS</span>
            <i class="bi bi-people-fill" style="font-size:1.4rem; color:#ca8a04;"></i>
          </div>
          <div style="font-size:1.8rem; font-weight:900; margin-top:8px;" id="stat-total-registered">0</div>
        </div>

        <div class="stat-card-mini" style="background:#dcfce7;">
          <div style="display:flex; justify-content:space-between; align-items:center;">
            <span class="mono" style="font-size:0.75rem; font-weight:800; color:#15803d;">CHECKED-IN ATTENDANCE</span>
            <i class="bi bi-person-check-fill" style="font-size:1.4rem; color:#16a34a;"></i>
          </div>
          <div style="font-size:1.8rem; font-weight:900; margin-top:8px;" id="stat-total-checkedin">0</div>
        </div>

        <?php if ($canRevenue): ?>
          <div class="stat-card-mini" style="background:#ffedd5;">
            <div style="display:flex; justify-content:space-between; align-items:center;">
              <span class="mono" style="font-size:0.75rem; font-weight:800; color:#c2410c;">OPEN PLAY REVENUE</span>
              <i class="bi bi-cash-stack" style="font-size:1.4rem; color:#ea580c;"></i>
            </div>
            <div style="font-size:1.8rem; font-weight:900; margin-top:8px; color:var(--coral);" id="stat-total-revenue">₱0.00</div>
          </div>
        <?php endif; ?>
      </div>

      <!-- DATATABLES CARD -->
      <div class="card-streetside" style="padding:20px; background:var(--white);">
        <div style="overflow-x:auto; -webkit-overflow-scrolling:touch;">
          <table id="open-play-table" class="table-streetside" style="width:100%; border-collapse:collapse;">
            <thead class="desktop-table-header">
              <tr style="border-bottom:2px solid var(--ink); text-align:left; font-family:'DM Mono', monospace; font-size:0.75rem; text-transform:uppercase;">
                <th style="padding:10px;">SESSION &amp; FACILITY</th>
                <th style="padding:10px;">DATE &amp; TIME</th>
                <th style="padding:10px;">FEE / PLAYER</th>
                <th style="padding:10px;">CAPACITY &amp; ATTENDANCE</th>
                <th style="padding:10px;">STATUS</th>
                <th style="padding:10px; text-align:right;">ACTIONS</th>
              </tr>
            </thead>
            <tbody>
              <!-- Loaded via DataTables -->
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div id="footer-container"></div>
  </main>

  <!-- CREATE / EDIT SESSION MODAL -->
  <div class="modal-overlay" id="session-modal" style="display:none;">
    <div class="card-streetside modal-card-scrollable" style="width:min(500px, 100%); padding:24px; background:var(--white);">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; border-bottom:2px solid var(--ink); padding-bottom:10px;">
        <h3 style="font-size:1.1rem; font-weight:800; text-transform:uppercase; margin:0;" id="session-modal-title"><i class="bi bi-dribbble"></i> CREATE OPEN PLAY SESSION</h3>
        <button onclick="closeModal('session-modal')" class="button sand" style="padding:2px 8px; font-size:0.8rem;">✕</button>
      </div>

      <form id="session-form" onsubmit="submitSessionForm(event)">
        <input type="hidden" id="session-id">

        <div style="margin-bottom:14px;">
          <label class="mono" style="display:block; margin-bottom:4px; font-size:0.75rem;">TARGET FACILITY *</label>
          <select id="session-facility-id" required style="width:100%; padding:9px 12px; border:2px solid var(--ink); border-radius:8px; font-weight:800; font-family:inherit;">
            <option value="">-- Choose Facility --</option>
            <?php foreach ($facilities as $fac): ?>
              <option value="<?= $fac['id'] ?>"><?= htmlspecialchars($fac['name']) ?> (<?= htmlspecialchars($fac['city'] ?? 'Bohol') ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>

        <div style="margin-bottom:14px;">
          <label class="mono" style="display:block; margin-bottom:4px; font-size:0.75rem;">SESSION TITLE *</label>
          <input type="text" id="session-title" required placeholder="e.g. Friday Night Open Play (₱70/head)" style="width:100%; padding:9px 12px; border:2px solid var(--ink); border-radius:8px; font-weight:700; font-family:inherit;">
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:14px;">
          <div>
            <label class="mono" style="display:block; margin-bottom:4px; font-size:0.75rem;">SESSION DATE *</label>
            <input type="date" id="session-date" required style="width:100%; padding:9px 12px; border:2px solid var(--ink); border-radius:8px; font-weight:700; font-family:inherit;">
          </div>
          <div>
            <label class="mono" style="display:block; margin-bottom:4px; font-size:0.75rem;">FEE / PLAYER (₱) *</label>
            <input type="number" step="0.01" id="session-fee" required value="70.00" style="width:100%; padding:9px 12px; border:2px solid var(--ink); border-radius:8px; font-weight:800; font-family:inherit;">
          </div>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:14px;">
          <div>
            <label class="mono" style="display:block; margin-bottom:4px; font-size:0.75rem;">START TIME *</label>
            <input type="time" id="session-start" required value="17:00" style="width:100%; padding:9px 12px; border:2px solid var(--ink); border-radius:8px; font-weight:700; font-family:inherit;">
          </div>
          <div>
            <label class="mono" style="display:block; margin-bottom:4px; font-size:0.75rem;">END TIME *</label>
            <input type="time" id="session-end" required value="20:00" style="width:100%; padding:9px 12px; border:2px solid var(--ink); border-radius:8px; font-weight:700; font-family:inherit;">
          </div>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:18px;">
          <div>
            <label class="mono" style="display:block; margin-bottom:4px; font-size:0.75rem;">MAX PLAYER CAPACITY *</label>
            <input type="number" id="session-max-players" required value="16" min="1" max="100" style="width:100%; padding:9px 12px; border:2px solid var(--ink); border-radius:8px; font-weight:800; font-family:inherit;">
          </div>
          <div>
            <label class="mono" style="display:block; margin-bottom:4px; font-size:0.75rem;">SESSION STATUS</label>
            <select id="session-status" style="width:100%; padding:9px 12px; border:2px solid var(--ink); border-radius:8px; font-weight:800; font-family:'DM Mono', monospace;">
              <option value="open">OPEN</option>
              <option value="full">FULL / CLOSED</option>
              <option value="completed">COMPLETED</option>
              <option value="cancelled">CANCELLED</option>
            </select>
          </div>
        </div>

        <div style="display:flex; justify-content:flex-end; gap:8px;">
          <button type="button" onclick="closeModal('session-modal')" class="button sand" style="padding:8px 14px; font-size:0.8rem;">Cancel</button>
          <button type="submit" class="button coral" style="padding:8px 18px; font-size:0.8rem;"><i class="bi bi-save-fill"></i> Save Session</button>
        </div>
      </form>
    </div>
  </div>

  <!-- PLAYER ROSTER & CHECK-IN MODAL -->
  <div class="modal-overlay" id="roster-modal" style="display:none;">
    <div class="card-streetside modal-card-scrollable" style="width:min(720px, 100%); padding:24px; background:var(--white);">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px; border-bottom:2px solid var(--ink); padding-bottom:10px;">
        <div>
          <h3 style="font-size:1.1rem; font-weight:800; text-transform:uppercase; margin:0; color:var(--green);" id="roster-title">PLAYER ROSTER</h3>
          <div style="font-size:0.75rem; color:#4a5c56;" id="roster-subtitle">Open Play Attendance &amp; Check-In</div>
        </div>
        <button onclick="closeModal('roster-modal')" class="button sand" style="padding:2px 8px; font-size:0.8rem;">✕</button>
      </div>

      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; flex-wrap:wrap; gap:8px;">
        <span class="badge-streetside lime" id="roster-count-badge">0 / 0 PLAYERS REGISTERED</span>
        <div style="display:flex; gap:8px; flex-wrap:wrap;">
          <a href="#" id="print-roster-btn" target="_blank" class="button lime" style="padding:6px 12px; font-size:0.75rem;">
            <i class="bi bi-printer-fill"></i> Print All Open Play List
          </a>
          <?php if ($canManagePlayers): ?>
            <button type="button" onclick="openRegisterPlayerModal()" class="button coral" style="padding:6px 12px; font-size:0.75rem;">
              <i class="bi bi-person-plus-fill"></i> Register Walk-in Player
            </button>
          <?php endif; ?>
        </div>
      </div>

      <div style="overflow-x:auto; -webkit-overflow-scrolling:touch;">
        <table id="roster-table" class="table-streetside" style="width:100%; border-collapse:collapse;">
          <thead class="desktop-table-header">
            <tr style="border-bottom:2px solid var(--ink); text-align:left; font-family:'DM Mono', monospace; font-size:0.75rem; text-transform:uppercase;">
              <th style="padding:8px;">PLAYER NAME</th>
              <th style="padding:8px;">FEE PAID</th>
              <th style="padding:8px;">STATUS</th>
              <th style="padding:8px; text-align:right;">ACTIONS</th>
            </tr>
          </thead>
          <tbody>
            <!-- Loaded via Server-side DataTables -->
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- REGISTER WALK-IN PLAYER MODAL -->
  <div class="modal-overlay" id="register-player-modal" style="display:none; z-index:60000 !important;">
    <div class="card-streetside modal-card-scrollable" style="width:min(440px, 100%); padding:22px; background:var(--cream);">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px; border-bottom:2px solid var(--ink); padding-bottom:8px;">
        <h4 style="font-size:1rem; font-weight:800; text-transform:uppercase; margin:0;"><i class="bi bi-person-plus-fill"></i> REGISTER PLAYER</h4>
        <button onclick="closeModal('register-player-modal')" class="button sand" style="padding:2px 8px; font-size:0.8rem;">✕</button>
      </div>

      <form id="register-player-form" onsubmit="submitRegisterPlayer(event)">
        <input type="hidden" id="reg-session-id">
        <input type="hidden" id="reg-user-id">

        <div style="margin-bottom:12px;">
          <label class="mono" style="display:block; margin-bottom:4px; font-size:0.75rem;">PLAYER NAME * (SELECT OR TYPE MANUAL)</label>
          <input type="text" id="reg-player-name" list="player-suggestions-list" required placeholder="Search user directory or type player name..." style="width:100%; padding:8px 12px; border:2px solid var(--ink); border-radius:8px; font-weight:700; font-family:inherit;" oninput="handlePlayerNameInput(this.value)" autocomplete="off">
          <datalist id="player-suggestions-list"></datalist>
          <div style="font-size:0.7rem; color:#4a5c56; margin-top:3px;"><i class="bi bi-info-circle"></i> Select from player directory or type a new walk-in player manually.</div>
        </div>

        <div style="margin-bottom:12px;">
          <label class="mono" style="display:block; margin-bottom:4px; font-size:0.75rem;">PHONE NUMBER</label>
          <input type="text" id="reg-player-phone" placeholder="09181234567" style="width:100%; padding:8px 12px; border:2px solid var(--ink); border-radius:8px; font-weight:700; font-family:inherit;">
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:16px;">
          <div>
            <label class="mono" style="display:block; margin-bottom:4px; font-size:0.75rem;">FEE PAID (₱) *</label>
            <input type="number" step="0.01" id="reg-amount-paid" required value="70.00" style="width:100%; padding:8px 12px; border:2px solid var(--ink); border-radius:8px; font-weight:800; font-family:inherit;">
          </div>
          <div>
            <label class="mono" style="display:block; margin-bottom:4px; font-size:0.75rem;">CHECK-IN NOW?</label>
            <select id="reg-checkin-now" style="width:100%; padding:8px 12px; border:2px solid var(--ink); border-radius:8px; font-weight:800; font-family:'DM Mono', monospace;">
              <option value="checked_in">YES (CHECKED IN)</option>
              <option value="registered">NO (REGISTERED)</option>
            </select>
          </div>
        </div>

        <div style="display:flex; justify-content:flex-end; gap:8px;">
          <button type="button" onclick="closeModal('register-player-modal')" class="button sand" style="padding:7px 12px; font-size:0.78rem;">Cancel</button>
          <button type="submit" class="button lime" style="padding:7px 16px; font-size:0.78rem;"><i class="bi bi-check-circle-fill"></i> Add Player</button>
        </div>
      </form>
    </div>
  </div>

  <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
  <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
  <script src="/pikvero/assets/js/core/toast.js"></script>
  <script src="/pikvero/assets/js/core/ajax.js"></script>
  <script src="/pikvero/assets/js/core/auth.js"></script>
  <script src="/pikvero/assets/js/components/navbar.js"></script>
  <script src="/pikvero/assets/js/components/sidebar.js?v=2"></script>
  <script src="/pikvero/assets/js/components/footer.js"></script>
  <script>
    let openPlayTable = null;
    let activeSessionId = null;
    const canCheckin = <?= json_encode($canCheckin) ?>;
    const canEdit = <?= json_encode($canEdit) ?>;

    document.addEventListener('DOMContentLoaded', async () => {
      await NavbarComponent.render('#navbar-container', true);
      SidebarComponent.render('open-play', 'admin');
      FooterComponent.render('#footer-container', true);

      loadMetrics();

      openPlayTable = $('#open-play-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
          url: '/pikvero/api/admin/open-play.php',
          type: 'GET'
        },
        order: [[1, 'desc']],
        columns: [
          {
            data: 'title',
            render: function (data, type, row) {
              return `
                <div>
                  <span class="mono" style="font-size:0.72rem; color:var(--green); font-weight:800;">#OP-${row.id}</span>
                  <div style="font-weight:800; font-size:0.95rem; text-transform:uppercase;">${data}</div>
                  <div style="font-size:0.78rem; color:#4a5c56;"><i class="bi bi-building"></i> ${row.facility_name || 'Facility'} (${row.city || 'Bohol'})</div>
                </div>
              `;
            }
          },
          {
            data: 'session_date',
            render: function (data, type, row) {
              return `
                <div style="font-size:0.84rem; font-weight:700;">
                  <i class="bi bi-calendar-event"></i> ${data}<br>
                  <span class="mono" style="font-size:0.75rem; color:#4a5c56;">${(row.start_time||'').substring(0,5)} - ${(row.end_time||'').substring(0,5)}</span>
                </div>
              `;
            }
          },
          {
            data: 'fee_per_player',
            render: function (data) {
              return `<strong class="mono" style="font-size:0.95rem; color:var(--green);">₱${parseFloat(data||0).toFixed(2)}</strong> / head`;
            }
          },
          {
            data: 'registered_players',
            render: function (data, type, row) {
              const registered = parseInt(data || 0, 10);
              const maxPlayers = parseInt(row.max_players || 16, 10);
              const checkedIn = parseInt(row.checked_in_count || 0, 10);
              let badgeCls = 'lime';
              if (registered >= maxPlayers) badgeCls = 'coral';
              else if (registered >= maxPlayers * 0.75) badgeCls = 'sand';

              return `
                <div>
                  <span class="badge-streetside ${badgeCls}">${registered} / ${maxPlayers} SLOTS</span>
                  <div style="font-size:0.72rem; color:#4a5c56; margin-top:3px;"><i class="bi bi-person-check-fill"></i> ${checkedIn} Checked-in</div>
                </div>
              `;
            }
          },
          {
            data: 'status',
            render: function (data) {
              const st = (data || 'open').toLowerCase();
              let badgeCls = 'lime';
              if (st === 'full') badgeCls = 'sand';
              else if (st === 'cancelled') badgeCls = 'coral';
              else if (st === 'completed') badgeCls = 'sky';
              return `<span class="badge-streetside ${badgeCls}">${st.toUpperCase()}</span>`;
            }
          },
          {
            data: null,
            orderable: false,
            render: function (data, type, row) {
              return `
                <div style="display:flex; gap:6px; justify-content:flex-end;">
                  <button type="button" onclick="openRosterModal(${row.id})" class="button sky" style="padding:5px 10px; font-size:0.75rem;" title="View Roster & Check-In">
                    <i class="bi bi-people-fill"></i> Roster
                  </button>
                  ${canEdit ? `
                    <button type="button" onclick="openEditSessionModal(${row.id})" class="button sand" style="padding:5px 10px; font-size:0.75rem;" title="Edit Session">
                      <i class="bi bi-pencil-fill"></i>
                    </button>
                  ` : ''}
                </div>
              `;
            }
          }
        ]
      });
    });

    async function loadMetrics() {
      try {
        const res = await Api.get('/pikvero/api/admin/open-play.php', { action: 'metrics' });
        if (res.success && res.data) {
          $('#stat-total-sessions').text(res.data.total_sessions || 0);
          $('#stat-total-registered').text(res.data.total_registered || 0);
          $('#stat-total-checkedin').text(res.data.total_checked_in || 0);
          if ($('#stat-total-revenue').length) {
            $('#stat-total-revenue').text('₱' + parseFloat(res.data.total_revenue || 0).toFixed(2));
          }
        }
      } catch (e) { console.error(e); }
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

    function openCreateSessionModal() {
      document.getElementById('session-modal-title').innerHTML = `<i class="bi bi-dribbble"></i> CREATE OPEN PLAY SESSION`;
      document.getElementById('session-id').value = '';
      document.getElementById('session-title').value = '';
      const today = new Date().toISOString().split('T')[0];
      document.getElementById('session-date').value = today;
      document.getElementById('session-fee').value = '70.00';
      document.getElementById('session-start').value = '17:00';
      document.getElementById('session-end').value = '20:00';
      document.getElementById('session-max-players').value = '16';
      document.getElementById('session-status').value = 'open';
      openModal('session-modal');
    }

    async function openEditSessionModal(id) {
      try {
        const res = await Api.get('/pikvero/api/admin/open-play.php', { action: 'details', id: id });
        if (res.success && res.data) {
          const s = res.data;
          document.getElementById('session-modal-title').innerHTML = `<i class="bi bi-pencil-square"></i> EDIT OPEN PLAY SESSION`;
          document.getElementById('session-id').value = s.id;
          document.getElementById('session-facility-id').value = s.facility_id;
          document.getElementById('session-title').value = s.title;
          document.getElementById('session-date').value = s.session_date;
          document.getElementById('session-fee').value = s.fee_per_player;
          document.getElementById('session-start').value = (s.start_time || '').substring(0,5);
          document.getElementById('session-end').value = (s.end_time || '').substring(0,5);
          document.getElementById('session-max-players').value = s.max_players;
          document.getElementById('session-status').value = s.status;
          openModal('session-modal');
        }
      } catch (err) { console.error(err); }
    }

    async function submitSessionForm(e) {
      e.preventDefault();
      const id = document.getElementById('session-id').value;
      const action = id ? 'update' : 'create';

      const payload = {
        id: id,
        facility_id: document.getElementById('session-facility-id').value,
        title: document.getElementById('session-title').value.trim(),
        session_date: document.getElementById('session-date').value,
        fee_per_player: document.getElementById('session-fee').value,
        start_time: document.getElementById('session-start').value,
        end_time: document.getElementById('session-end').value,
        max_players: document.getElementById('session-max-players').value,
        status: document.getElementById('session-status').value
      };

      try {
        const res = await Api.post('/pikvero/api/admin/open-play.php?action=' + action, payload);
        if (res.success) {
          Toast.success('Saved', res.message || 'Open play session saved.');
          closeModal('session-modal');
          openPlayTable.ajax.reload(null, false);
          loadMetrics();
        }
      } catch (err) { console.error(err); }
    }

    let rosterTable = null;

    async function openRosterModal(sessionId) {
      activeSessionId = sessionId;
      document.getElementById('print-roster-btn').href = `/pikvero/public/open-play-print-list.php?session_id=${sessionId}`;
      openModal('roster-modal');
      loadRoster(sessionId);
    }

    async function loadRoster(sessionId) {
      try {
        const res = await Api.get('/pikvero/api/admin/open-play.php', { action: 'details', id: sessionId });
        if (res.success && res.data) {
          const s = res.data;
          activeSessionFee = parseFloat(s.fee_per_player || 70.00);
          document.getElementById('roster-title').innerText = `${s.title} (#OP-${s.id})`;
          document.getElementById('roster-subtitle').innerText = `${s.facility_name} • ${s.session_date} (${(s.start_time||'').substring(0,5)} - ${(s.end_time||'').substring(0,5)})`;
          document.getElementById('roster-count-badge').innerText = `${s.registered_players} / ${s.max_players} PLAYERS REGISTERED (₱${parseFloat(s.fee_per_player||70).toFixed(2)}/head)`;
        }
      } catch (err) { console.error(err); }

      if (rosterTable) {
        rosterTable.ajax.url(`/pikvero/api/admin/open-play.php?action=roster_dt&session_id=${sessionId}`).load();
      } else {
        rosterTable = $('#roster-table').DataTable({
          processing: true,
          serverSide: true,
          ajax: {
            url: `/pikvero/api/admin/open-play.php?action=roster_dt&session_id=${sessionId}`,
            type: 'GET'
          },
          order: [[0, 'desc']],
          columns: [
            {
              data: 'player_name',
              render: function (data, type, row) {
                return `
                  <div>
                    <strong style="font-size:0.9rem; text-transform:uppercase;">${data}</strong>
                    <div style="font-size:0.75rem; color:#4a5c56;"><i class="bi bi-telephone"></i> ${row.player_phone || 'No Phone'}</div>
                  </div>
                `;
              }
            },
            {
              data: 'amount_paid',
              render: function (data, type, row) {
                return `
                  <div>
                    <strong class="mono" style="color:var(--green); font-size:0.88rem;">₱${parseFloat(data||0).toFixed(2)}</strong>
                    <div style="font-size:0.72rem; color:#4a5c56;">${(row.payment_status||'paid').toUpperCase()}</div>
                  </div>
                `;
              }
            },
            {
              data: 'checkin_status',
              render: function (data) {
                const isCheckedIn = (data === 'checked_in');
                return `<span class="badge-streetside ${isCheckedIn ? 'lime' : 'sand'}" style="font-size:0.72rem;">${isCheckedIn ? '<i class="bi bi-check-circle-fill"></i> CHECKED IN' : 'REGISTERED'}</span>`;
              }
            },
            {
              data: null,
              orderable: false,
              render: function (data, type, row) {
                const isCheckedIn = (row.checkin_status === 'checked_in');
                return `
                  <div style="display:flex; gap:6px; justify-content:flex-end;">
                    ${!isCheckedIn && canCheckin ? `
                      <button type="button" onclick="checkinPlayerAct(${row.id})" class="button coral" style="padding:4px 10px; font-size:0.75rem;">
                        <i class="bi bi-box-arrow-in-right"></i> Check-in
                      </button>
                    ` : ''}
                    <a href="/pikvero/public/open-play-receipt.php?registration_id=${row.id}" target="_blank" class="button lime" style="padding:4px 10px; font-size:0.75rem;" title="Print Receipt">
                      <i class="bi bi-printer-fill"></i> Receipt
                    </a>
                  </div>
                `;
              }
            }
          ]
        });
      }
    }

    async function checkinPlayerAct(registrationId) {
      try {
        const res = await Api.post('/pikvero/api/admin/open-play.php?action=checkin', { registration_id: registrationId });
        if (res.success) {
          Toast.success('Checked-in', 'Player checked in successfully.');
          if (activeSessionId) loadRoster(activeSessionId);
          openPlayTable.ajax.reload(null, false);
          loadMetrics();
        }
      } catch (err) { console.error(err); }
    }

    let activeSessionFee = 70.00;
    let playerSuggestionsCache = [];

    async function loadPlayerSuggestions(sessionId) {
      try {
        const params = { action: 'player_suggestions' };
        if (sessionId) params.session_id = sessionId;
        const res = await Api.get('/pikvero/api/admin/open-play.php', params);
        if (res.success && res.data) {
          playerSuggestionsCache = res.data;
          const datalist = document.getElementById('player-suggestions-list');
          if (datalist) {
            datalist.innerHTML = playerSuggestionsCache.map(p => {
              const phoneText = p.player_phone ? ` (${p.player_phone})` : '';
              return `<option value="${p.player_name}">${p.player_name}${phoneText}</option>`;
            }).join('');
          }
        }
      } catch (e) { console.error('Failed to load player suggestions:', e); }
    }

    function handlePlayerNameInput(val) {
      const valClean = (val || '').trim().toLowerCase();
      const match = playerSuggestionsCache.find(p => p.player_name.toLowerCase() === valClean);
      if (match) {
        if (match.player_phone) {
          document.getElementById('reg-player-phone').value = match.player_phone;
        }
        if (match.user_id) {
          document.getElementById('reg-user-id').value = match.user_id;
        }
      } else {
        document.getElementById('reg-user-id').value = '';
      }
    }

    async function openRegisterPlayerModal() {
      if (!activeSessionId) return;
      document.getElementById('reg-session-id').value = activeSessionId;
      document.getElementById('reg-user-id').value = '';
      document.getElementById('reg-player-name').value = '';
      document.getElementById('reg-player-phone').value = '';
      document.getElementById('reg-amount-paid').value = activeSessionFee.toFixed(2);
      document.getElementById('reg-checkin-now').value = 'checked_in';

      await loadPlayerSuggestions(activeSessionId);
      openModal('register-player-modal');
    }

    async function submitRegisterPlayer(e) {
      e.preventDefault();
      const sessionId = document.getElementById('reg-session-id').value;
      const payload = {
        session_id: sessionId,
        user_id: document.getElementById('reg-user-id').value || null,
        player_name: document.getElementById('reg-player-name').value.trim(),
        player_phone: document.getElementById('reg-player-phone').value.trim(),
        amount_paid: document.getElementById('reg-amount-paid').value,
        checkin_status: document.getElementById('reg-checkin-now').value,
        payment_status: 'paid'
      };

      try {
        const res = await Api.post('/pikvero/api/admin/open-play.php?action=register_player', payload);
        if (res.success) {
          Toast.success('Player Registered', 'Player added to Open Play session roster.');
          closeModal('register-player-modal');
          loadRoster(sessionId);
          openPlayTable.ajax.reload(null, false);
          loadMetrics();
        }
      } catch (err) { console.error(err); }
    }
  </script>
</body>
</html>
