<?php
require_once __DIR__ . '/../../app/bootstrap.php';

use App\Core\Auth\Auth;
use App\Infrastructure\Repositories\FacilityRepository;

Auth::requirePermission('amenities.view');
$canManage = Auth::can('amenities.manage');

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
  <title>Pikvero — Amenities &amp; Facility Features</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
  <link rel="stylesheet" href="/pikvero/assets/css/streetside-theme.css">
  <link rel="stylesheet" href="/pikvero/assets/css/modal.css">
  <link rel="stylesheet" href="/pikvero/assets/css/toast.css">
  <style>
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
    .icon-preview-box {
      width: 36px;
      height: 36px;
      border-radius: 8px;
      border: 2px solid var(--ink);
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: 1.1rem;
      background: var(--sand);
      color: var(--ink);
    }
    .amenity-check-tile {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 10px 14px;
      border: 2px solid var(--ink);
      border-radius: 10px;
      background: var(--white);
      cursor: pointer;
      user-select: none;
      transition: all 0.2s ease;
    }
    .amenity-check-tile:hover {
      background: #f0fdf4;
    }
    /* DataTables Reusable Streetside Pagination & Controls Override */
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
    .dataTables_wrapper .dataTables_filter input {
      margin-left: 8px;
    }
    .dataTables_wrapper .dataTables_info {
      font-family: 'DM Mono', monospace;
      font-size: 0.78rem;
      color: #4a5c56;
      padding-top: 14px;
    }
    .dataTables_wrapper .dataTables_paginate {
      padding-top: 10px;
    }
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
</head>
<body>

  <div id="sidebar-container"></div>
  <div id="navbar-container"></div>

  <main class="portal-main">
    <div>
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:12px;">
        <div>
          <div class="eyebrow">VENUE MANAGEMENT</div>
          <h1 style="font-size: clamp(1.8rem, 4vw, 2.2rem); font-weight:800; text-transform:uppercase; margin:4px 0 0;">AMENITIES &amp; FEATURES</h1>
        </div>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
          <?php if ($canManage): ?>
            <button type="button" onclick="openAssignFacilityModal()" class="button sand" style="padding:9px 16px; font-size:0.84rem;">
              <i class="bi bi-building-check"></i> Assign to Facility
            </button>
            <button type="button" onclick="openAddAmenityModal()" class="button coral" style="padding:9px 18px; font-size:0.84rem;">
              <i class="bi bi-plus-circle-fill"></i> Add New Amenity
            </button>
          <?php endif; ?>
        </div>
      </div>

      <!-- DATATABLES CARD -->
      <div class="card-streetside" style="padding:20px; background:var(--white);">
        <div style="overflow-x:auto; -webkit-overflow-scrolling:touch;">
          <table id="amenities-table" class="table-streetside" style="width:100%; border-collapse:collapse;">
            <thead class="desktop-table-header">
              <tr style="border-bottom:2px solid var(--ink); text-align:left; font-family:'DM Mono', monospace; font-size:0.75rem; text-transform:uppercase;">
                <th style="padding:10px;">ICON &amp; ID</th>
                <th style="padding:10px;">AMENITY NAME</th>
                <th style="padding:10px;">ICON CODE</th>
                <th style="padding:10px;">ASSIGNED FACILITIES</th>
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

  <!-- ADD / EDIT AMENITY MODAL -->
  <div class="modal-overlay" id="amenity-modal">
    <div class="card-streetside modal-card-scrollable" style="width:min(480px, 100%); padding:24px; background:var(--white);">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; border-bottom:2px solid var(--ink); padding-bottom:10px;">
        <h3 style="font-size:1.1rem; font-weight:800; text-transform:uppercase; margin:0;" id="amenity-modal-title"><i class="bi bi-stars"></i> ADD NEW AMENITY</h3>
        <button onclick="closeModal('amenity-modal')" class="button sand" style="padding:2px 8px; font-size:0.8rem;">✕</button>
      </div>

      <form id="amenity-form" onsubmit="submitAmenityForm(event)">
        <input type="hidden" id="amenity-id">

        <div style="margin-bottom:14px;">
          <label class="mono" style="display:block; margin-bottom:4px; font-size:0.75rem;">AMENITY NAME *</label>
          <input type="text" id="amenity-name" required placeholder="e.g. Night Lighting, Parking..." style="width:100%; padding:9px 12px; border:2px solid var(--ink); border-radius:8px; font-weight:700; font-family:inherit;">
        </div>

        <div style="margin-bottom:18px;">
          <label class="mono" style="display:block; margin-bottom:6px; font-size:0.75rem;">SELECT AMENITY ICON *</label>
          <input type="hidden" id="amenity-icon" value="bi-check-circle-fill">
          
          <div id="icon-picker-grid" style="display:grid; grid-template-columns:repeat(5, 1fr); gap:8px; max-height:220px; overflow-y:auto; padding:8px; background:var(--sand); border:2px solid var(--ink); border-radius:10px;">
            <!-- Icon picker tiles generated by JS -->
          </div>
        </div>

        <div style="display:flex; justify-content:flex-end; gap:8px;">
          <button type="button" onclick="closeModal('amenity-modal')" class="button sand" style="padding:8px 14px; font-size:0.8rem;">Cancel</button>
          <button type="submit" class="button coral" style="padding:8px 18px; font-size:0.8rem;"><i class="bi bi-save-fill"></i> Save Amenity</button>
        </div>
      </form>
    </div>
  </div>

  <!-- ASSIGN AMENITIES TO FACILITY MODAL -->
  <div class="modal-overlay" id="assign-facility-modal">
    <div class="card-streetside modal-card-scrollable" style="width:min(620px, 100%); padding:24px; background:var(--white);">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; border-bottom:2px solid var(--ink); padding-bottom:10px;">
        <h3 style="font-size:1.1rem; font-weight:800; text-transform:uppercase; margin:0; color:#0284c7;"><i class="bi bi-building-check"></i> ASSIGN AMENITIES TO FACILITY</h3>
        <button onclick="closeModal('assign-facility-modal')" class="button sand" style="padding:2px 8px; font-size:0.8rem;">✕</button>
      </div>

      <form id="assign-facility-form" onsubmit="submitFacilityAssignments(event)">
        <div style="margin-bottom:16px;">
          <label class="mono" style="display:block; margin-bottom:4px; font-size:0.75rem;">SELECT TARGET FACILITY *</label>
          <select id="assign-facility-id" required style="width:100%; padding:10px 12px; border:2px solid var(--ink); border-radius:8px; font-weight:800; font-family:inherit;" onchange="loadFacilityAssignments(this.value)">
            <option value="">-- Choose a Facility --</option>
            <?php foreach ($facilities as $fac): ?>
              <option value="<?= $fac['id'] ?>"><?= htmlspecialchars($fac['name']) ?> (<?= htmlspecialchars($fac['city'] ?? 'Bohol') ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>

        <div style="margin-bottom:18px;">
          <label class="mono" style="display:block; margin-bottom:6px; font-size:0.75rem;">SELECT APPLICABLE AMENITIES &amp; FEATURES</label>
          <div id="assign-amenities-grid" style="display:grid; grid-template-columns:repeat(auto-fill, minmax(240px, 1fr)); gap:10px; max-height:300px; overflow-y:auto; padding:4px;">
            <!-- Loaded dynamically -->
          </div>
        </div>

        <div style="display:flex; justify-content:flex-end; gap:8px; border-top:1px dashed var(--ink); padding-top:14px;">
          <button type="button" onclick="closeModal('assign-facility-modal')" class="button sand" style="padding:8px 14px; font-size:0.8rem;">Cancel</button>
          <button type="submit" class="button coral" style="padding:8px 18px; font-size:0.8rem;"><i class="bi bi-check-circle-fill"></i> Save Facility Assignments</button>
        </div>
      </form>
    </div>
  </div>

  <!-- jQuery & DataTables CDN -->
  <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
  <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
  <script src="/pikvero/assets/js/core/toast.js"></script>
  <script src="/pikvero/assets/js/core/ajax.js"></script>
  <script src="/pikvero/assets/js/core/auth.js"></script>
  <script src="/pikvero/assets/js/components/navbar.js?v=2"></script>
  <script src="/pikvero/assets/js/components/sidebar.js"></script>
  <script src="/pikvero/assets/js/components/footer.js"></script>
  <script>
    let amenitiesDataTable = null;
    let allAmenitiesList = [];
    const canManage = <?= json_encode($canManage) ?>;

    document.addEventListener('DOMContentLoaded', async () => {
      await NavbarComponent.render('#navbar-container', true);
      SidebarComponent.render('amenities', 'admin');
      FooterComponent.render('#footer-container', true);

      // Initialize DataTables
      amenitiesDataTable = $('#amenities-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
          url: '/pikvero/api/admin/amenities.php',
          type: 'GET'
        },
        order: [[0, 'asc']],
        columns: [
          // Column 0: Icon & ID
          {
            data: 'id',
            render: function (data, type, row) {
              const iconClass = row.icon || 'bi-check-circle';
              return `
                <div style="display:flex; align-items:center; gap:10px;">
                  <div class="icon-preview-box">
                    <i class="bi ${iconClass}"></i>
                  </div>
                  <span class="mono" style="font-weight:800; font-size:0.78rem;">#AM-${data}</span>
                </div>
              `;
            }
          },
          // Column 1: Amenity Name
          {
            data: 'name',
            render: function (data) {
              return `<div style="font-weight:800; font-size:0.95rem; color:var(--ink);">${data}</div>`;
            }
          },
          // Column 2: Icon Code
          {
            data: 'icon',
            render: function (data) {
              return `<code class="mono" style="background:var(--sand); padding:3px 7px; border-radius:4px; font-size:0.75rem; border:1px solid var(--line);">${data || 'bi-check-circle'}</code>`;
            }
          },
          // Column 3: Assigned Facilities Count
          {
            data: 'assigned_facilities_count',
            render: function (data) {
              const count = parseInt(data || 0, 10);
              return `<span class="badge-streetside ${count > 0 ? 'lime' : 'sand'}">${count} ${count === 1 ? 'FACILITY' : 'FACILITIES'} ASSIGNED</span>`;
            }
          },
          // Column 4: Actions
          {
            data: null,
            orderable: false,
            render: function (data, type, row) {
              if (!canManage) return `<span style="color:#aaa;">Read Only</span>`;
              return `
                <div style="display:flex; gap:6px; justify-content:flex-end;">
                  <button type="button" onclick="openEditAmenityModal(${row.id}, '${escapeJsStr(row.name)}', '${escapeJsStr(row.icon)}')" class="button sand" style="padding:4px 10px; font-size:0.75rem;" title="Edit Amenity">
                    <i class="bi bi-pencil-fill"></i> Edit
                  </button>
                  <button type="button" onclick="deleteAmenity(${row.id}, '${escapeJsStr(row.name)}')" class="button coral" style="padding:4px 10px; font-size:0.75rem;" title="Delete Amenity">
                    <i class="bi bi-trash-fill"></i> Delete
                  </button>
                </div>
              `;
            }
          }
        ]
      });
    });

    function escapeJsStr(str) {
      return (str || '').replace(/'/g, "\\'").replace(/"/g, '&quot;');
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

    const PRESET_ICONS = [
      { code: 'bi-lightbulb-fill', label: 'Lighting' },
      { code: 'bi-p-square-fill', label: 'Parking' },
      { code: 'bi-dribbble', label: 'Rental' },
      { code: 'bi-droplet-fill', label: 'Shower' },
      { code: 'bi-clock-fill', label: 'Hours' },
      { code: 'bi-wifi', label: 'Wi-Fi' },
      { code: 'bi-cup-hot-fill', label: 'Café' },
      { code: 'bi-shield-check', label: 'Security' },
      { code: 'bi-snow', label: 'Air Con' },
      { code: 'bi-trophy-fill', label: 'Trophy' },
      { code: 'bi-shop', label: 'Pro Shop' },
      { code: 'bi-prescription2', label: 'First Aid' },
      { code: 'bi-volume-up-fill', label: 'PA Sound' },
      { code: 'bi-car-front-fill', label: 'Valet' },
      { code: 'bi-file-person', label: 'Coach' },
      { code: 'bi-heart-pulse-fill', label: 'Gym' },
      { code: 'bi-sun-fill', label: 'Outdoor' },
      { code: 'bi-house-door-fill', label: 'Indoor' },
      { code: 'bi-person-arms-up', label: 'Locker' },
      { code: 'bi-check-circle-fill', label: 'Standard' }
    ];

    function renderIconPicker(selectedIcon = 'bi-lightbulb-fill') {
      const grid = document.getElementById('icon-picker-grid');
      if (!grid) return;
      document.getElementById('amenity-icon').value = selectedIcon;

      grid.innerHTML = PRESET_ICONS.map(ic => {
        const isSel = (ic.code === selectedIcon);
        return `
          <button type="button" onclick="selectAmenityIcon('${ic.code}')" 
                  style="display:flex; flex-direction:column; align-items:center; justify-content:center; gap:4px; padding:10px 4px; border:2px solid ${isSel ? 'var(--coral)' : 'var(--line)'}; border-radius:8px; background:${isSel ? 'var(--lime)' : 'var(--white)'}; cursor:pointer; transition:all 0.15s ease; ${isSel ? 'transform:scale(1.05); font-weight:800;' : ''}">
            <i class="bi ${ic.code}" style="font-size:1.3rem; color:var(--ink);"></i>
            <span style="font-size:0.65rem; color:var(--ink); font-weight:700; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:100%;">${ic.label}</span>
          </button>
        `;
      }).join('');
    }

    function selectAmenityIcon(iconCode) {
      renderIconPicker(iconCode);
    }

    function openAddAmenityModal() {
      document.getElementById('amenity-modal-title').innerHTML = `<i class="bi bi-stars"></i> ADD NEW AMENITY`;
      document.getElementById('amenity-id').value = '';
      document.getElementById('amenity-name').value = '';
      renderIconPicker('bi-lightbulb-fill');
      openModal('amenity-modal');
    }

    function openEditAmenityModal(id, name, icon) {
      document.getElementById('amenity-modal-title').innerHTML = `<i class="bi bi-pencil-square"></i> EDIT AMENITY`;
      document.getElementById('amenity-id').value = id;
      document.getElementById('amenity-name').value = name;
      renderIconPicker(icon || 'bi-lightbulb-fill');
      openModal('amenity-modal');
    }

    async function submitAmenityForm(e) {
      e.preventDefault();
      const id = document.getElementById('amenity-id').value;
      const name = document.getElementById('amenity-name').value;
      const icon = document.getElementById('amenity-icon').value;

      const action = id ? 'update' : 'create';
      try {
        const res = await Api.post('/pikvero/api/admin/amenities.php?action=' + action, {
          id: id,
          name: name,
          icon: icon
        });
        if (res.success) {
          Toast.success('Saved', res.message || 'Amenity saved successfully.');
          closeModal('amenity-modal');
          amenitiesDataTable.ajax.reload(null, false);
        }
      } catch (err) { console.error(err); }
    }

    async function deleteAmenity(id, name) {
      if (!confirm(`Are you sure you want to delete amenity "${name}"? This will also remove it from assigned facilities.`)) {
        return;
      }
      try {
        const res = await Api.post('/pikvero/api/admin/amenities.php?action=delete', { id: id });
        if (res.success) {
          Toast.success('Deleted', 'Amenity deleted successfully.');
          amenitiesDataTable.ajax.reload(null, false);
        }
      } catch (err) { console.error(err); }
    }

    async function openAssignFacilityModal() {
      openModal('assign-facility-modal');
      const select = document.getElementById('assign-facility-id');
      if (select.options.length > 1) {
        select.selectedIndex = 1;
        loadFacilityAssignments(select.value);
      }
    }

    async function loadFacilityAssignments(facilityId) {
      const grid = document.getElementById('assign-amenities-grid');
      if (!facilityId) {
        grid.innerHTML = `<div style="grid-column:1/-1; color:#aaa; text-align:center; padding:20px;">Please select a facility above to manage assigned amenities.</div>`;
        return;
      }

      grid.innerHTML = `<div style="grid-column:1/-1; color:#aaa; text-align:center; padding:20px;"><i class="bi bi-hourglass-split"></i> Loading amenities...</div>`;

      try {
        const res = await Api.get('/pikvero/api/admin/amenities.php', { action: 'get_facility_assignments', facility_id: facilityId });
        if (res.success && res.data) {
          const assignedIds = (res.data.assigned_ids || []).map(id => parseInt(id, 10));
          const amenities = res.data.amenities || [];

          if (amenities.length === 0) {
            grid.innerHTML = `<div style="grid-column:1/-1; color:#aaa; text-align:center; padding:20px;">No amenities created yet. Click "Add New Amenity" to create features.</div>`;
            return;
          }

          grid.innerHTML = amenities.map(am => {
            const isChecked = assignedIds.includes(parseInt(am.id, 10));
            return `
              <label class="amenity-check-tile">
                <input type="checkbox" name="assign_amenities" value="${am.id}" ${isChecked ? 'checked' : ''} style="width:18px; height:18px; accent-color:var(--coral);">
                <div class="icon-preview-box" style="width:30px; height:30px; font-size:0.9rem;">
                  <i class="bi ${am.icon || 'bi-check-circle'}"></i>
                </div>
                <span style="font-weight:800; font-size:0.85rem; color:var(--ink);">${am.name}</span>
              </label>
            `;
          }).join('');
        }
      } catch (err) { console.error(err); }
    }

    async function submitFacilityAssignments(e) {
      e.preventDefault();
      const facilityId = document.getElementById('assign-facility-id').value;
      if (!facilityId) {
        Toast.error('Validation Error', 'Please select a target facility.');
        return;
      }

      const checkboxes = document.querySelectorAll('input[name="assign_amenities"]:checked');
      const selectedIds = Array.from(checkboxes).map(cb => parseInt(cb.value, 10));

      try {
        const res = await Api.post('/pikvero/api/admin/amenities.php?action=save_facility_assignments', {
          facility_id: facilityId,
          amenity_ids: selectedIds
        });

        if (res.success) {
          Toast.success('Saved', res.message || 'Facility amenities assigned successfully.');
          closeModal('assign-facility-modal');
          amenitiesDataTable.ajax.reload(null, false);
        }
      } catch (err) { console.error(err); }
    }
  </script>
</body>
</html>
