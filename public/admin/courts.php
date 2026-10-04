<?php
$pageTitle  = 'Pikvero — Court Manager';
$headExtras = ['select2'];
require_once __DIR__ . '/../../includes/head.php';
?>
  <style>
    .form-group-wrap {
      margin-bottom: 12px;
    }
    .form-group-wrap label {
      display: block;
      margin-bottom: 4px;
      font-family: 'DM Mono', monospace;
      font-weight: 800;
      font-size: 0.78rem;
      text-transform: uppercase;
      color: var(--ink);
    }
    .form-input-ctrl {
      width: 100%;
      padding: 9px 12px;
      border: 2px solid var(--ink);
      border-radius: 8px;
      font-family: inherit;
      font-weight: 700;
      background: var(--white);
      outline: none;
      transition: border-color 0.2s ease, background 0.2s ease;
    }
    .form-input-ctrl.is-valid {
      border-color: var(--green) !important;
      background: #f0fdf4 !important;
    }
    .form-input-ctrl.is-invalid {
      border-color: var(--coral) !important;
      background: #fff5f3 !important;
    }
    .field-err-msg {
      display: block;
      font-size: 0.72rem;
      font-weight: 800;
      color: var(--coral);
      margin-top: 3px;
      min-height: 16px;
    }
    /* ── Pricing Panel ───────────────────────────────────── */
    .pricing-panel {
      position: fixed;
      inset: 0;
      background: rgba(10, 20, 15, 0.55);
      backdrop-filter: blur(4px);
      display: none;
      z-index: 1200;
      align-items: flex-start;
      justify-content: flex-end;
    }
    .pricing-drawer {
      width: min(520px, 100vw);
      height: 100vh;
      background: var(--white);
      border-left: 3px solid var(--ink);
      display: flex;
      flex-direction: column;
      overflow: hidden;
    }
    .pricing-drawer-header {
      background: var(--ink);
      color: var(--white);
      padding: 18px 20px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-shrink: 0;
    }
    .pricing-drawer-header h3 {
      margin: 0;
      font-size: 1rem;
      font-weight: 900;
      text-transform: uppercase;
      letter-spacing: 0.08em;
    }
    .pricing-drawer-body {
      flex: 1;
      overflow-y: auto;
      padding: 20px;
    }
    .pricing-rule-row {
      border: 2px solid var(--ink);
      border-radius: 10px;
      padding: 12px 14px;
      margin-bottom: 10px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
      background: var(--parchment);
    }
    .pricing-rule-row:hover { background: #e8f4ed; }
    .pricing-rule-info { flex: 1; min-width: 0; }
    .pricing-rule-name {
      font-weight: 900;
      font-size: 0.88rem;
      text-transform: uppercase;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .pricing-rule-meta {
      font-size: 0.75rem;
      color: #5a7060;
      margin-top: 2px;
    }
    .pricing-rule-actions { display: flex; gap: 6px; flex-shrink: 0; }
    .add-rule-form {
      border: 2px dashed var(--ink);
      border-radius: 10px;
      padding: 14px;
      margin-bottom: 18px;
      background: #f8fef9;
    }
    .add-rule-form .add-rule-title {
      font-weight: 900;
      font-size: 0.8rem;
      text-transform: uppercase;
      margin-bottom: 10px;
      color: var(--ink);
    }
    .price-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
    .price-grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; }
    /* ── Select2 Streetside Theme ──────────────────────────────────── */
    .select2-container--default .select2-selection--single {
      border: 2px solid var(--ink) !important;
      border-radius: 8px !important;
      height: 40px !important;
      display: flex !important;
      align-items: center !important;
      padding: 0 10px !important;
      background: var(--white) !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
      color: var(--ink) !important;
      font-family: inherit !important;
      font-weight: 800 !important;
      font-size: 0.88rem !important;
      line-height: 1 !important;
      padding: 0 !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
      height: 38px !important;
      top: 0 !important;
      right: 8px !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow b {
      border-color: var(--ink) transparent transparent transparent !important;
    }
    .select2-dropdown {
      border: 2px solid var(--ink) !important;
      border-radius: 8px !important;
      font-family: inherit !important;
      overflow: hidden !important;
      box-shadow: 4px 4px 0 var(--ink) !important;
    }
    .select2-container--default .select2-results__option {
      font-weight: 700 !important;
      font-size: 0.85rem !important;
      padding: 10px 12px !important;
    }
    .select2-container--default .select2-results__option--highlighted[aria-selected] {
      background-color: var(--ink) !important;
      color: var(--white) !important;
    }
    .select2-container--default .select2-results__option[aria-selected=true] {
      background-color: #e0ede5 !important;
      color: var(--ink) !important;
      font-weight: 900 !important;
    }
    .select2-container--default .select2-selection--multiple {
      border: 2px solid var(--ink) !important;
      border-radius: 8px !important;
      min-height: 40px !important;
      padding: 3px 6px !important;
      background: var(--white) !important;
    }
    .select2-container--default .select2-selection--multiple .select2-selection__choice {
      background-color: var(--ink) !important;
      color: var(--white) !important;
      border: 1px solid var(--ink) !important;
      border-radius: 6px !important;
      font-weight: 800 !important;
      font-size: 0.78rem !important;
      padding: 2px 8px !important;
      margin-top: 3px !important;
    }
    .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
      color: var(--white) !important;
      margin-right: 4px !important;
    }
    .select2-container { width: 100% !important; }

    /* ── Blockout Modal Day Chips ─────────────────────────── */
    .bm-day-chip {
      background: var(--parchment);
      border: 2.5px solid var(--ink);
      border-radius: 20px;
      padding: 5px 14px;
      font-family: 'DM Mono', monospace;
      font-weight: 800;
      font-size: 0.8rem;
      cursor: pointer;
      transition: background 0.15s, color 0.15s, transform 0.1s;
      user-select: none;
    }
    .bm-day-chip:hover { background: #d4edda; transform: translateY(-1px); }
    .bm-day-chip.active {
      background: var(--ink);
      color: var(--white);
      border-color: var(--ink);
    }

    /* -- Court View Modal ------------------------------------ */
    .vm-section-title {
      font-family: 'DM Mono', monospace;
      font-weight: 900;
      font-size: 0.72rem;
      text-transform: uppercase;
      letter-spacing: 0.1em;
      color: #5a7060;
      margin: 20px 0 10px;
      padding-bottom: 6px;
      border-bottom: 1.5px solid var(--line);
      display: flex;
      align-items: center;
      gap: 6px;
    }
    .vm-info-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 10px;
      margin-bottom: 4px;
    }
    .vm-info-cell {
      background: var(--parchment);
      border: 1.5px solid var(--line);
      border-radius: 8px;
      padding: 8px 12px;
    }
    .vm-info-label {
      font-family: 'DM Mono', monospace;
      font-size: 0.65rem;
      font-weight: 800;
      text-transform: uppercase;
      color: #5a7060;
      margin-bottom: 2px;
    }
    .vm-info-value {
      font-weight: 800;
      font-size: 0.9rem;
      color: var(--ink);
    }
    .vm-badge {
      display: inline-block;
      padding: 3px 10px;
      border-radius: 20px;
      font-size: 0.72rem;
      font-weight: 900;
      text-transform: uppercase;
      border: 2px solid var(--ink);
    }
    .vm-pricing-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      background: #f8fef9;
      border: 1.5px solid var(--line);
      border-radius: 8px;
      padding: 8px 12px;
      margin-bottom: 6px;
      gap: 8px;
    }
    .vm-blockout-row {
      display: flex;
      align-items: center;
      gap: 10px;
      background: #fff5f3;
      border: 1.5px solid #f5c3ba;
      border-radius: 8px;
      padding: 8px 12px;
      margin-bottom: 6px;
    }
    .vm-blockout-date {
      font-family: 'DM Mono', monospace;
      font-weight: 900;
      font-size: 0.85rem;
      color: var(--ink);
      min-width: 100px;
    }
    .vm-hours-grid {
      display: grid;
      grid-template-columns: repeat(7, 1fr);
      gap: 6px;
      text-align: center;
    }
    .vm-day-col {
      background: var(--parchment);
      border: 1.5px solid var(--line);
      border-radius: 8px;
      padding: 6px 4px;
    }
    .vm-day-name {
      font-family: 'DM Mono', monospace;
      font-size: 0.65rem;
      font-weight: 900;
      text-transform: uppercase;
      color: #5a7060;
      margin-bottom: 4px;
    }
    .vm-day-hours {
      font-size: 0.7rem;
      font-weight: 800;
      color: var(--ink);
      line-height: 1.3;
    }

    /* ── Shared responsive 2-col grid for form modals ───────── */
    .modal-grid-2 {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 10px;
    }
    /* ── Responsive modal footer buttons ───────────────────── */
    .modal-actions {
      display: flex;
      justify-content: flex-end;
      gap: 8px;
      margin-top: 10px;
      flex-wrap: wrap;
    }
    .modal-actions button { flex-shrink: 0; }

    /* ── Mobile breakpoint (≤ 480px) ───────────────────────── */
    @media (max-width: 480px) {
      /* Form modals: card padding */
      #create-court-modal .card-streetside,
      #edit-court-modal   .card-streetside {
        padding: 18px 16px;
      }
      /* 2-col grids collapse to 1 col */
      .modal-grid-2 {
        grid-template-columns: 1fr;
      }
      /* Action buttons go full-width */
      .modal-actions {
        flex-direction: column;
      }
      .modal-actions button {
        width: 100%;
        text-align: center;
      }

      /* View modal: header padding */
      #view-court-modal > div > div:first-child {
        padding: 16px 16px 12px;
      }
      /* View modal: body padding */
      #vm-body {
        padding: 16px 16px 20px !important;
      }
      /* View modal: info grid → full row on very small */
      .vm-info-grid {
        grid-template-columns: 1fr;
      }
      /* Operating hours: 4 cols on mobile, wraps to 2 rows */
      .vm-hours-grid {
        grid-template-columns: repeat(4, 1fr);
        gap: 4px;
      }
      /* Blockout rows: stack vertically */
      .vm-blockout-row {
        flex-wrap: wrap;
        gap: 6px;
      }
      .vm-blockout-date {
        min-width: unset;
        width: 100%;
        font-size: 0.8rem;
      }
      /* Pricing row: stack */
      .vm-pricing-row {
        flex-direction: column;
        align-items: flex-start;
        gap: 4px;
      }
      /* Quick-action buttons at bottom of view modal */
      .vm-quick-actions {
        flex-direction: column;
      }
      .vm-quick-actions button { width: 100%; }

      /* Blockout modal: header padding */
      #blockout-modal > div {
        padding: 20px 16px 18px;
      }
      /* Blockout modal time sliders: stack on mobile */
      .bm-time-row {
        flex-direction: column;
        gap: 8px;
      }
      .bm-time-row > div.bm-arrow {
        display: none;
      }
      /* Edit pricing modal */
      #edit-pricing-modal > div {
        padding: 18px 14px;
      }
      .price-grid-2 {
        grid-template-columns: 1fr;
      }
    }

    /* ── Very small (≤ 360px): hours grid to 3 cols ─────────── */
    @media (max-width: 360px) {
      .vm-hours-grid {
        grid-template-columns: repeat(3, 1fr);
      }
      .vm-info-grid {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>
<body>

  <aside id="sidebar-container"></aside>
  <header id="navbar-container"></header>

  <main class="portal-main">
    <div>
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:12px;">
        <div>
          <div class="eyebrow">SAAS MANAGEMENT</div>
          <h1 style="font-size: clamp(1.8rem, 4vw, 2.2rem); font-weight:800; text-transform:uppercase; margin:4px 0 0;">COURT MANAGER</h1>
        </div>
        <button onclick="openCreateCourtModal()" class="button coral" style="padding:9px 16px; font-size:0.82rem;">
          <i class="bi bi-plus-lg"></i> Add New Court
        </button>
      </div>

      <div id="owner-courts-list" style="display:grid; grid-template-columns:repeat(auto-fill, minmax(300px, 1fr)); gap:18px;">
        <!-- Loaded via JS -->
      </div>
    </div>

    <footer id="footer-container"></footer>
  </main>

  <!-- Create Court Modal -->
  <div id="create-court-modal" style="display:none; position:fixed; inset:0; z-index:9999; background:rgba(13,33,29,0.65); backdrop-filter:blur(6px); place-items:center; padding:16px;">
    <div class="card-streetside" style="width:min(500px, 100%); padding:26px; background:var(--cream);">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; border-bottom:2px solid var(--ink); padding-bottom:10px;">
        <h3 style="margin:0; font-size:1.2rem; text-transform:uppercase;">ADD NEW COURT</h3>
        <button onclick="document.getElementById('create-court-modal').style.display='none'" class="button sand" style="padding:2px 8px; font-size:0.8rem;">✕</button>
      </div>

      <form id="create-court-form" novalidate>
        <div class="form-group-wrap">
          <label for="court_facility_id">TARGET FACILITY *</label>
          <select id="court_facility_id" class="form-input-ctrl">
            <!-- Options loaded dynamically -->
          </select>
        </div>

        <div class="form-group-wrap">
          <label for="court_name">COURT NAME *</label>
          <input type="text" id="court_name" placeholder="e.g. Court 1 — Pro Championship" class="form-input-ctrl" oninput="validateCreateCourtField('name')">
          <span id="court_name_err" class="field-err-msg"></span>
        </div>

        <div class="modal-grid-2">
          <div class="form-group-wrap">
            <label for="court_type">COURT TYPE</label>
            <select id="court_type" class="form-input-ctrl">
              <option value="indoor">Indoor</option>
              <option value="outdoor" selected>Outdoor</option>
              <option value="covered">Covered</option>
            </select>
          </div>
          <div class="form-group-wrap">
            <label for="court_status">STATUS</label>
            <select id="court_status" class="form-input-ctrl" style="font-family:'DM Mono', monospace; font-weight:800;">
              <option value="active">ACTIVE</option>
              <option value="inactive">INACTIVE</option>
              <option value="maintenance">MAINTENANCE</option>
            </select>
          </div>
        </div>

        <div class="modal-grid-2">
          <div class="form-group-wrap">
            <label for="court_surface">SURFACE MATERIAL</label>
            <select id="court_surface" class="form-input-ctrl">
              <option value="cushioned_acrylic">Cushioned Acrylic</option>
              <option value="concrete">Hard Concrete</option>
              <option value="asphalt">Asphalt</option>
              <option value="modular_tile">Modular Tiles</option>
            </select>
          </div>
          <div class="form-group-wrap">
            <label for="base_price">HOURLY RATE (₱) *</label>
            <input type="number" step="10" id="base_price" value="400.00" class="form-input-ctrl" oninput="validateCreateCourtField('price')">
            <span id="base_price_err" class="field-err-msg"></span>
          </div>
        </div>

        <div class="modal-actions">
          <button type="button" onclick="document.getElementById('create-court-modal').style.display='none'" class="button sand" style="padding:8px 14px; font-size:0.8rem;">Cancel</button>
          <button type="submit" class="button lime" style="padding:8px 18px; font-size:0.8rem;"><i class="bi bi-floppy-fill"></i> Save Court</button>
        </div>
      </form>
    </div>
  </div>


  <!-- Edit Court Modal -->
  <div id="edit-court-modal" style="display:none; position:fixed; inset:0; z-index:9999; background:rgba(13,33,29,0.65); backdrop-filter:blur(6px); place-items:center; padding:16px;">
    <div class="card-streetside" style="width:min(500px, 100%); padding:26px; background:var(--cream);">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; border-bottom:2px solid var(--ink); padding-bottom:10px;">
        <h3 style="margin:0; font-size:1.2rem; text-transform:uppercase;">EDIT COURT DETAILS</h3>
        <button onclick="document.getElementById('edit-court-modal').style.display='none'" class="button sand" style="padding:2px 8px; font-size:0.8rem;">✕</button>
      </div>

      <form id="edit-court-form" novalidate>
        <input type="hidden" id="edit_court_id">

        <div class="form-group-wrap">
          <label for="edit_court_name">COURT NAME *</label>
          <input type="text" id="edit_court_name" class="form-input-ctrl" oninput="validateEditCourtField('name')">
          <span id="edit_court_name_err" class="field-err-msg"></span>
        </div>

        <div class="modal-grid-2">
          <div class="form-group-wrap">
            <label for="edit_court_type">COURT TYPE</label>
            <select id="edit_court_type" class="form-input-ctrl">
              <option value="indoor">Indoor</option>
              <option value="outdoor">Outdoor</option>
              <option value="covered">Covered</option>
            </select>
          </div>
          <div class="form-group-wrap">
            <label for="edit_court_status">STATUS</label>
            <select id="edit_court_status" class="form-input-ctrl" style="font-family:'DM Mono', monospace; font-weight:800;">
              <option value="active">ACTIVE</option>
              <option value="inactive">INACTIVE</option>
              <option value="maintenance">MAINTENANCE</option>
            </select>
          </div>
        </div>

        <div class="modal-grid-2">
          <div class="form-group-wrap">
            <label for="edit_court_surface">SURFACE MATERIAL</label>
            <select id="edit_court_surface" class="form-input-ctrl">
              <option value="cushioned_acrylic">Cushioned Acrylic</option>
              <option value="concrete">Hard Concrete</option>
              <option value="asphalt">Asphalt</option>
              <option value="modular_tile">Modular Tiles</option>
            </select>
          </div>
          <div class="form-group-wrap">
            <label for="edit_base_price">HOURLY RATE (₱) *</label>
            <input type="number" step="10" id="edit_base_price" class="form-input-ctrl" oninput="validateEditCourtField('price')">
            <span id="edit_base_price_err" class="field-err-msg"></span>
          </div>
        </div>

        <div class="modal-actions">
          <button type="button" onclick="document.getElementById('edit-court-modal').style.display='none'" class="button sand" style="padding:8px 14px; font-size:0.8rem;">Cancel</button>
          <button type="submit" class="button lime" style="padding:8px 18px; font-size:0.8rem;"><i class="bi bi-floppy-fill"></i> Save Changes</button>
        </div>
      </form>
    </div>
  </div>

  <!-- ── Pricing Rules Drawer ───────────────────────────────── -->
  <div id="pricing-panel" class="pricing-panel" onclick="if(event.target===this)closePricingPanel()">
    <div class="pricing-drawer">
      <div class="pricing-drawer-header">
        <h3><i class="bi bi-clock-history" style="margin-right:8px;"></i>Time-Based Pricing Rules</h3>
        <button onclick="closePricingPanel()" style="background:none;border:none;color:var(--white);font-size:1.4rem;cursor:pointer;line-height:1;">&times;</button>
      </div>
      <div class="pricing-drawer-body">
        <p id="pricing-panel-courtname" style="font-weight:800;font-size:0.85rem;text-transform:uppercase;color:#5a7060;margin:0 0 14px;"></p>

        <!-- Add New Rule Form -->
        <div class="add-rule-form" id="add-rule-form">
          <div class="add-rule-title"><i class="bi bi-plus-circle-fill" style="color:var(--green);"></i> Add Pricing Rule</div>

          <div class="form-group-wrap">
            <label for="pr_name">RULE NAME *</label>
            <input type="text" id="pr_name" class="form-input-ctrl" placeholder="e.g. Morning Off-Peak">
            <span id="pr_name_err" class="field-err-msg"></span>
          </div>

          <div class="price-grid-2">
            <div class="form-group-wrap">
              <label for="pr_start">START TIME *</label>
              <input type="time" id="pr_start" class="form-input-ctrl" value="06:00">
              <span id="pr_start_err" class="field-err-msg"></span>
            </div>
            <div class="form-group-wrap">
              <label for="pr_end">END TIME *</label>
              <input type="time" id="pr_end" class="form-input-ctrl" value="12:00">
              <span id="pr_end_err" class="field-err-msg"></span>
            </div>
          </div>

          <div class="form-group-wrap">
            <label for="pr_price">RATE (₱/hr) *</label>
            <input type="number" id="pr_price" class="form-input-ctrl" min="1" step="10" placeholder="350">
            <span id="pr_price_err" class="field-err-msg"></span>
          </div>

          <div class="form-group-wrap">
            <label for="pr_day_type">APPLIES TO (SELECT ONE OR MORE)</label>
            <select id="pr_day_type" multiple="multiple">
              <option value="all">All Days</option>
              <option value="weekday">Weekdays (Mon–Fri)</option>
              <option value="weekend">Weekends (Sat–Sun)</option>
              <optgroup label="Individual Days">
                <option value="monday">Every Monday</option>
                <option value="tuesday">Every Tuesday</option>
                <option value="wednesday">Every Wednesday</option>
                <option value="thursday">Every Thursday</option>
                <option value="friday">Every Friday</option>
                <option value="saturday">Every Saturday</option>
                <option value="sunday">Every Sunday</option>
              </optgroup>
            </select>
          </div>

          <button onclick="submitAddPricingRule()" class="button lime" style="width:100%;padding:9px;font-size:0.82rem;">
            <i class="bi bi-plus-lg"></i> Add Rule
          </button>
        </div>

        <!-- Rule List -->
        <div style="font-weight:900;font-size:0.8rem;text-transform:uppercase;color:#5a7060;margin-bottom:8px;">
          <i class="bi bi-list-ul"></i> Current Rules
        </div>
        <div id="pricing-rules-list">
          <p style="color:#888;font-size:0.82rem;">Loading…</p>
        </div>
      </div>
    </div>
  </div>

  <!-- View Court Modal (z-index 1500) -->
  <div id="view-court-modal" style="
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
        max-width: 680px;
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
            Court Overview
          </div>
          <h2 id="vm-court-name" style="margin:0;font-size:1.2rem;font-weight:900;text-transform:uppercase;color:var(--white);">Loading...</h2>
        </div>
        <button onclick="closeViewModal()" style="
            background:rgba(255,255,255,0.12);border:2px solid rgba(255,255,255,0.35);border-radius:50%;
            width:34px;height:34px;font-size:1.1rem;cursor:pointer;
            display:flex;align-items:center;justify-content:center;font-weight:900;color:var(--white);
          ">&times;</button>
      </div>
      <!-- Body -->
      <div id="vm-body" style="padding:22px 26px 26px;">
        <div style="text-align:center;padding:40px 0;color:#5a7060;">
          <i class="bi bi-arrow-clockwise" style="font-size:2rem;"></i>
          <p style="margin-top:8px;font-weight:700;">Loading court details...</p>
        </div>
      </div>
    </div>
  </div>

  <!-- Blockout Modal
  ═══════════════════════════════════════════════════════════════════ -->
  <div id="blockout-modal" style="
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(10,20,15,0.65);
      backdrop-filter: blur(6px);
      z-index: 1400;
      align-items: center;
      justify-content: center;
      padding: 16px;
    ">
    <div style="
        background: var(--white);
        border: 3px solid var(--ink);
        border-radius: 20px;
        padding: 28px 28px 24px;
        width: 100%;
        max-width: 520px;
        max-height: 92vh;
        overflow-y: auto;
        position: relative;
        box-shadow: 6px 6px 0 var(--ink);
      ">

      <!-- Header -->
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;padding-bottom:14px;border-bottom:2.5px solid var(--ink);">
        <h2 style="margin:0;font-size:1.08rem;font-weight:900;text-transform:uppercase;letter-spacing:0.07em;">
          <i class="bi bi-calendar-x-fill" style="color:#e05c3a;margin-right:7px;"></i>Add Blockout
        </h2>
        <button onclick="closeBlockoutModal()" style="
            background:none;border:2.5px solid var(--ink);border-radius:50%;
            width:32px;height:32px;font-size:1.1rem;cursor:pointer;
            display:flex;align-items:center;justify-content:center;font-weight:900;line-height:1;
          ">&times;</button>
      </div>

      <!-- Mode toggle: specific date vs. recurring days -->
      <div style="display:flex;gap:8px;margin-bottom:18px;">
        <button id="bm-mode-date" onclick="setBlockoutMode('date')" class="button lime" style="flex:1;padding:7px;font-size:0.78rem;">📅 Specific Date(s)</button>
        <button id="bm-mode-days" onclick="setBlockoutMode('days')" class="button sand" style="flex:1;padding:7px;font-size:0.78rem;">🔁 Recurring Days</button>
      </div>

      <!-- ── SPECIFIC DATE mode ── -->
      <div id="bm-section-date">
        <div class="form-group-wrap">
          <label>DATE(S) — click to toggle multiple</label>
          <input type="date" id="bm_date_input" class="form-input-ctrl" style="margin-bottom:6px;">
          <div id="bm_date_chips" style="display:flex;flex-wrap:wrap;gap:6px;min-height:28px;"></div>
        </div>
      </div>

      <!-- ── RECURRING DAYS mode ── -->
      <div id="bm-section-days" style="display:none;">
        <div class="form-group-wrap">
          <label>DAY(S) OF WEEK — select one or more</label>
          <div id="bm_day_chips" style="display:flex;flex-wrap:wrap;gap:6px;">
            <button type="button" class="bm-day-chip" data-day="Monday">Mon</button>
            <button type="button" class="bm-day-chip" data-day="Tuesday">Tue</button>
            <button type="button" class="bm-day-chip" data-day="Wednesday">Wed</button>
            <button type="button" class="bm-day-chip" data-day="Thursday">Thu</button>
            <button type="button" class="bm-day-chip" data-day="Friday">Fri</button>
            <button type="button" class="bm-day-chip" data-day="Saturday">Sat</button>
            <button type="button" class="bm-day-chip" data-day="Sunday">Sun</button>
          </div>
        </div>
        <div class="form-group-wrap">
          <label>OCCURRENCES (next N weeks)</label>
          <input type="number" id="bm_weeks" class="form-input-ctrl" value="4" min="1" max="52" style="max-width:120px;">
        </div>
      </div>

      <!-- ── TIME RANGE ── -->
      <div style="margin-bottom:16px;">
        <label style="display:block;margin-bottom:8px;font-family:'DM Mono',monospace;font-weight:800;font-size:0.78rem;text-transform:uppercase;color:var(--ink);">TIME RANGE</label>
        <div class="bm-time-row" style="display:flex;gap:14px;align-items:flex-end;">
          <div style="flex:1;">
            <div style="font-size:0.72rem;font-weight:700;color:#5a7060;margin-bottom:4px;">START HOUR</div>
            <input type="range" id="bm_start_hour" min="0" max="23" value="6" style="width:100%;accent-color:var(--green);">
            <div id="bm_start_label" style="text-align:center;font-weight:900;font-size:1rem;margin-top:4px;font-family:'DM Mono',monospace;">6:00 AM</div>
          </div>
          <div class="bm-arrow" style="color:var(--ink);font-weight:900;font-size:1.3rem;padding-bottom:22px;">→</div>
          <div style="flex:1;">
            <div style="font-size:0.72rem;font-weight:700;color:#5a7060;margin-bottom:4px;">END HOUR</div>
            <input type="range" id="bm_end_hour" min="0" max="24" value="22" style="width:100%;accent-color:#e05c3a;">
            <div id="bm_end_label" style="text-align:center;font-weight:900;font-size:1rem;margin-top:4px;font-family:'DM Mono',monospace;">10:00 PM</div>
          </div>
        </div>
        <div id="bm_time_err" style="color:#e05c3a;font-size:0.72rem;font-weight:800;margin-top:4px;"></div>
      </div>

      <!-- ── REASON ── -->
      <div class="form-group-wrap" style="margin-bottom:20px;">
        <label for="bm_reason">REASON</label>
        <input type="text" id="bm_reason" class="form-input-ctrl" placeholder="e.g. Tournament, Maintenance…" value="Tournament / Maintenance">
      </div>

      <!-- Summary badge -->
      <div id="bm_summary" style="
          background:#f0fdf4;border:2px solid var(--green);
          border-radius:10px;padding:10px 14px;
          font-size:0.78rem;font-weight:700;color:#1a3d2a;
          margin-bottom:18px;min-height:38px;
          display:flex;align-items:center;gap:6px;
        ">
        <i class="bi bi-info-circle-fill" style="color:var(--green);"></i>
        <span id="bm_summary_text">Select dates/days and a time range to preview.</span>
      </div>

      <!-- Actions -->
      <div style="display:flex;justify-content:flex-end;gap:10px;">
        <button type="button" onclick="closeBlockoutModal()" class="button sand" style="padding:9px 18px;font-size:0.82rem;">Cancel</button>
        <button type="button" onclick="submitBlockout()" class="button lime" style="padding:9px 22px;font-size:0.82rem;">
          <i class="bi bi-calendar-x-fill"></i> Block Schedule
        </button>
      </div>
    </div>
  </div>

  <!-- Edit Pricing Rule Modal — z-index 1300 so it sits above the pricing drawer (1200) -->
  <div id="edit-pricing-modal" style="
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(10, 20, 15, 0.6);
      backdrop-filter: blur(4px);
      z-index: 1300;
      align-items: center;
      justify-content: center;
      padding: 20px;
    ">
    <div style="
        background: var(--white);
        border: 3px solid var(--ink);
        border-radius: 16px;
        padding: 24px;
        width: 100%;
        max-width: 460px;
        max-height: 90vh;
        overflow-y: auto;
        position: relative;
      ">
      <!-- Header -->
      <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:18px; padding-bottom:12px; border-bottom:2px solid var(--ink);">
        <h2 style="margin:0; font-size:1.05rem; font-weight:900; text-transform:uppercase; letter-spacing:0.06em;">
          <i class="bi bi-clock-fill" style="color:var(--green); margin-right:6px;"></i>Edit Pricing Rule
        </h2>
        <button onclick="document.getElementById('edit-pricing-modal').style.display='none'" style="
            background: none; border: 2px solid var(--ink); border-radius: 50%;
            width: 30px; height: 30px; font-size: 1rem; cursor: pointer;
            display:flex; align-items:center; justify-content:center; font-weight:900; line-height:1;
          ">&times;</button>
      </div>

      <input type="hidden" id="epr_rule_id">

      <div class="form-group-wrap">
        <label for="epr_name">RULE NAME *</label>
        <input type="text" id="epr_name" class="form-input-ctrl" placeholder="e.g. Morning Off-Peak">
        <span id="epr_name_err" class="field-err-msg"></span>
      </div>

      <div class="price-grid-2">
        <div class="form-group-wrap">
          <label for="epr_start">START TIME *</label>
          <input type="time" id="epr_start" class="form-input-ctrl">
          <span id="epr_start_err" class="field-err-msg"></span>
        </div>
        <div class="form-group-wrap">
          <label for="epr_end">END TIME *</label>
          <input type="time" id="epr_end" class="form-input-ctrl">
          <span id="epr_end_err" class="field-err-msg"></span>
        </div>
      </div>

      <div class="form-group-wrap">
        <label for="epr_price">RATE (₱/hr) *</label>
        <input type="number" id="epr_price" class="form-input-ctrl" min="1" step="10">
        <span id="epr_price_err" class="field-err-msg"></span>
      </div>

      <div class="form-group-wrap">
        <label for="epr_day_type">APPLIES TO (SELECT ONE OR MORE)</label>
        <select id="epr_day_type" multiple="multiple">
          <option value="all">All Days</option>
          <option value="weekday">Weekdays (Mon–Fri)</option>
          <option value="weekend">Weekends (Sat–Sun)</option>
          <optgroup label="Individual Days">
            <option value="monday">Every Monday</option>
            <option value="tuesday">Every Tuesday</option>
            <option value="wednesday">Every Wednesday</option>
            <option value="thursday">Every Thursday</option>
            <option value="friday">Every Friday</option>
            <option value="saturday">Every Saturday</option>
            <option value="sunday">Every Sunday</option>
          </optgroup>
        </select>
      </div>

      <div style="display:flex; justify-content:flex-end; gap:8px; margin-top:16px;">
        <button type="button" onclick="document.getElementById('edit-pricing-modal').style.display='none'" class="button sand" style="padding:8px 16px; font-size:0.82rem;">Cancel</button>
        <button type="button" onclick="submitUpdatePricingRule()" class="button lime" style="padding:8px 20px; font-size:0.82rem;"><i class="bi bi-floppy-fill"></i> Save Changes</button>
      </div>
    </div>
  </div>

  <!-- ── Manage Court Photos Modal (Up to 10 photos) ── -->
  <div id="court-photos-modal" style="
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(10, 20, 15, 0.65);
      backdrop-filter: blur(4px);
      z-index: 1250;
      align-items: center;
      justify-content: center;
      padding: 20px;
    ">
    <div style="
        background: var(--white);
        border: 3px solid var(--ink);
        border-radius: 16px;
        padding: 24px;
        width: 100%;
        max-width: 680px;
        max-height: 90vh;
        overflow-y: auto;
        position: relative;
        box-shadow: 6px 6px 0 var(--ink);
      ">
      <!-- Header -->
      <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:18px; padding-bottom:12px; border-bottom:2px solid var(--ink);">
        <div>
          <h2 style="margin:0; font-size:1.1rem; font-weight:900; text-transform:uppercase; letter-spacing:0.04em;">
            <i class="bi bi-images" style="color:var(--coral); margin-right:6px;"></i>Court Photos Carousel Manager
          </h2>
          <div id="cpm-court-title" style="font-size:0.78rem; color:#4a5c56; font-weight:700; margin-top:2px;">Court: Loading...</div>
        </div>
        <button onclick="closeCourtPhotosModal()" style="
            background: none; border: 2px solid var(--ink); border-radius: 50%;
            width: 32px; height: 32px; font-size: 1rem; cursor: pointer;
            display:flex; align-items:center; justify-content:center; font-weight:900; line-height:1;
          ">&times;</button>
      </div>

      <input type="hidden" id="cpm_court_id">

      <!-- Upload / Add Image Controls -->
      <div style="background:#f4f7f5; border:2px solid var(--ink); border-radius:12px; padding:16px; margin-bottom:20px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
          <strong style="font-size:0.85rem; text-transform:uppercase;"><i class="bi bi-cloud-upload-fill" style="color:var(--green);"></i> Add New Photo</strong>
          <span id="cpm-count-badge" class="badge-streetside lime" style="font-size:0.75rem;">0 / 10 Photos</span>
        </div>

        <!-- Tab Toggle: File Upload vs URL -->
        <div style="display:flex; gap:8px; margin-bottom:12px;">
          <button type="button" id="cpm-tab-file" onclick="toggleCpmAddTab('file')" class="button lime" style="padding:4px 12px; font-size:0.75rem;">File Upload</button>
          <button type="button" id="cpm-tab-url" onclick="toggleCpmAddTab('url')" class="button sand" style="padding:4px 12px; font-size:0.75rem;">Image URL</button>
        </div>

        <!-- File Upload Form -->
        <form id="cpm-file-form" onsubmit="handleCpmFileUpload(event)" style="display:block;">
          <div style="display:flex; gap:10px; align-items:center;">
            <input type="file" id="cpm_file_input" accept="image/jpeg,image/png,image/webp,image/avif" multiple class="form-input-ctrl" style="padding:6px; flex:1;">
            <button type="submit" class="button coral" style="padding:8px 16px; font-size:0.8rem; white-space:nowrap;">
              <i class="bi bi-upload"></i> Upload Selected
            </button>
          </div>
          <div style="font-size:0.68rem; color:#5a7060; margin-top:4px;">Allowed formats: JPG, PNG, WEBP, AVIF (Max 5MB each &bull; Select multiple files)</div>
        </form>

        <!-- Image URL Form -->
        <form id="cpm-url-form" onsubmit="handleCpmUrlSubmit(event)" style="display:none;">
          <div style="display:flex; gap:10px; align-items:center;">
            <input type="url" id="cpm_url_input" placeholder="https://example.com/court-photo.jpg" class="form-input-ctrl" style="flex:1;">
            <button type="submit" class="button lime" style="padding:8px 16px; font-size:0.8rem; white-space:nowrap;">
              <i class="bi bi-plus-circle-fill"></i> Add URL
            </button>
          </div>
          <div style="font-size:0.68rem; color:#5a7060; margin-top:4px;">Paste any valid HTTPS image web address</div>
        </form>
      </div>

      <!-- 10 Slots Grid -->
      <div style="margin-bottom:12px; display:flex; justify-content:space-between; align-items:center;">
        <strong style="font-size:0.82rem; text-transform:uppercase;">Court Carousel Photos (10 Max)</strong>
        <span style="font-size:0.72rem; color:#5a7060;">Photos display in the order added</span>
      </div>
      <div id="cpm-grid-container" style="display:grid; grid-template-columns: repeat(auto-fill, minmax(110px, 1fr)); gap:12px;">
        <!-- Dynamically rendered photo cards/placeholders -->
      </div>
    </div>
  </div>

  <!-- Subscription Plan Limit Reached Upgrade Modal -->
  <div id="upgrade-plan-modal" style="display:none; position:fixed; inset:0; z-index:9999; background:rgba(10,20,15,0.7); backdrop-filter:blur(6px); place-items:center; padding:16px;">
    <div class="card-streetside" style="width:min(520px, 100%); padding:28px; background:var(--cream); text-align:center; position:relative;">
      <div style="width:64px; height:64px; border-radius:50%; background:#fef2f2; border:3px solid var(--coral); display:flex; align-items:center; justify-content:center; margin:0 auto 16px; box-shadow:3px 3px 0 var(--ink);">
        <i class="bi bi-rocket-takeoff-fill" style="font-size:2rem; color:var(--coral);"></i>
      </div>

      <h3 style="margin:0 0 8px; font-size:1.4rem; text-transform:uppercase; font-weight:900; color:var(--ink);">
        SUBSCRIPTION PLAN LIMIT REACHED
      </h3>

      <div style="background:var(--white); border:2px solid var(--ink); border-radius:12px; padding:18px; margin-bottom:20px; text-align:left;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
          <span style="font-size:0.78rem; font-family:'DM Mono', monospace; font-weight:900; color:#5a7060; text-transform:uppercase;">CURRENT PLAN TIER:</span>
          <span id="upgrade-modal-plan-name" class="badge-streetside coral">STARTER PLAN</span>
        </div>
        <p id="upgrade-modal-msg" style="font-size:0.88rem; color:#2c3e38; margin:0; line-height:1.45;">
          You have reached the maximum number of courts allowed by your current subscription plan.
        </p>
      </div>

      <div style="display:flex; gap:10px; justify-content:center;">
        <button type="button" onclick="closeUpgradePlanModal()" class="button sand" style="padding:10px 18px; font-size:0.84rem;">Cancel</button>
        <button type="button" onclick="redirectToMyPlanPage()" class="button coral" style="padding:10px 24px; font-size:0.84rem;">
          <i class="bi bi-award-fill"></i> Upgrade Plan Now &rarr;
        </button>
      </div>
    </div>
  </div>

<?php require_once __DIR__ . '/../../includes/scripts.php'; ?>
  <script>
    let currentCourtsMap = {};
    let canCreateCourt = false;
    let canEditCourt = false;
    let canManagePricing = false;
    let canBlockCourt = false;
    let currentSubscriptionSummary = null;

    async function checkSubscriptionLimits() {
      try {
        const res = await Api.get('/pikvero/api/owner/subscription/index.php');
        if (res && res.success && res.data) {
          currentSubscriptionSummary = res.data;
        }
      } catch(err) { console.error(err); }
    }

    document.addEventListener('DOMContentLoaded', async () => {
      // Init Select2 on APPLIES TO dropdowns with multi-select support
      $('#pr_day_type').select2({
        placeholder: 'Select applicable day(s)...',
        dropdownParent: $('#add-rule-form')
      });
      $('#epr_day_type').select2({
        placeholder: 'Select applicable day(s)...',
        dropdownParent: $('#edit-pricing-modal')
      });

      // Blockout modal: slider + date picker event wiring
      document.getElementById('bm_start_hour').addEventListener('input', () => updateHourLabel('bm_start_hour', 'bm_start_label'));
      document.getElementById('bm_end_hour').addEventListener('input',   () => updateHourLabel('bm_end_hour',   'bm_end_label'));
      document.getElementById('bm_date_input').addEventListener('change', bmAddDate);
      document.getElementById('bm_weeks').addEventListener('input', updateBlockoutSummary);
      document.querySelectorAll('.bm-day-chip').forEach(btn => btn.addEventListener('click', () => toggleDayChip(btn)));
      // Close blockout modal on backdrop click
      document.getElementById('blockout-modal').addEventListener('click', e => {
        if (e.target === document.getElementById('blockout-modal')) closeBlockoutModal();
      });

      // Clean raw ID query parameters from URL bar if present for privacy
      if (window.location.search.includes('facility_id=')) {
        const urlParams = new URLSearchParams(window.location.search);
        const facId = urlParams.get('facility_id');
        if (facId) sessionStorage.setItem('selected_facility_id', facId);
        window.history.replaceState({}, document.title, window.location.pathname);
      }

      await NavbarComponent.render('#navbar-container', true);
      const userCtx = await AuthHelper.checkSession();
      if (!userCtx || !userCtx.user) {
        window.location.href = '/pikvero/public/login.php';
        return;
      }

      const role = userCtx.role || (userCtx.user ? userCtx.user.role_name : '');
      const perms = userCtx.permissions || [];
      const isSuperAdmin = (role === 'super_admin');

      const canViewCourts = isSuperAdmin || perms.includes('court.view') || perms.includes('courts.view') || perms.includes('court.create') || perms.includes('courts.manage') || perms.includes('system.manage');

      if (!canViewCourts) {
        window.location.href = '/pikvero/public/403.php?permission=court.view';
        return;
      }

      canCreateCourt   = isSuperAdmin || perms.includes('court.create') || perms.includes('courts.manage') || perms.includes('system.manage');
      canEditCourt     = isSuperAdmin || perms.includes('court.update') || perms.includes('courts.manage') || perms.includes('system.manage');
      canManagePricing = isSuperAdmin || perms.includes('pricing.manage') || perms.includes('courts.manage') || perms.includes('system.manage');
      canBlockCourt    = isSuperAdmin || perms.includes('availability.manage') || perms.includes('courts.manage') || perms.includes('system.manage');

      const btnAdd = document.querySelector('button[onclick="openCreateCourtModal()"]');
      if (btnAdd) {
        btnAdd.style.display = canCreateCourt ? 'inline-flex' : 'none';
      }

      SidebarComponent.render('courts', (role === 'court_owner' || role === 'facility_manager' || role === 'receptionist') ? 'owner' : 'admin');
      FooterComponent.render('#footer-container', true);

      await loadFacilitiesDropdown();

      const storedFacId = sessionStorage.getItem('selected_facility_id');
      if (storedFacId) {
        const select = document.getElementById('court_facility_id');
        if (select) select.value = storedFacId;
      }

      loadCourts();
      checkSubscriptionLimits();

      // Create Court Form Submit Handler
      document.getElementById('create-court-form').addEventListener('submit', async (e) => {
        e.preventDefault();

        const isNameValid = validateCreateCourtField('name');
        const isPriceValid = validateCreateCourtField('price');

        if (!isNameValid || !isPriceValid) {
          Toast.error('Validation Error', 'Please resolve errors before creating the court.');
          return;
        }

        try {
          const res = await Api.post('/pikvero/api/owner/courts.php', {
            facility_id: document.getElementById('court_facility_id').value,
            name: document.getElementById('court_name').value.trim(),
            court_type: document.getElementById('court_type').value,
            surface_type: document.getElementById('court_surface').value,
            status: document.getElementById('court_status').value,
            base_price_per_hour: document.getElementById('base_price').value
          });

          if (res.success) {
            Toast.success('Court Created', 'New court added successfully.');
            document.getElementById('create-court-modal').style.display = 'none';
            resetCreateCourtForm();
            loadCourts();
            checkSubscriptionLimits();
          } else if (res && (res.code === 'PLAN_LIMIT_REACHED' || (res.message && res.message.includes('limit reached')))) {
            document.getElementById('create-court-modal').style.display = 'none';
            openUpgradePlanModal(res.message);
          } else {
            Toast.error('Creation Failed', (res && res.message) ? res.message : 'Could not add court.');
          }
        } catch (err) { console.error(err); }
      });

      // Edit Court Form Submit Handler
      document.getElementById('edit-court-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        if (!canEditCourt) {
          Toast.error('Access Denied', 'You do not have permission (court.update) to edit courts.');
          return;
        }

        const isNameValid = validateEditCourtField('name');
        const isPriceValid = validateEditCourtField('price');

        if (!isNameValid || !isPriceValid) {
          Toast.error('Validation Error', 'Please resolve errors before saving changes.');
          return;
        }

        try {
          const res = await Api.post('/pikvero/api/owner/courts/update.php', {
            court_id: document.getElementById('edit_court_id').value,
            name: document.getElementById('edit_court_name').value.trim(),
            court_type: document.getElementById('edit_court_type').value,
            surface_type: document.getElementById('edit_court_surface').value,
            status: document.getElementById('edit_court_status').value,
            base_price_per_hour: document.getElementById('edit_base_price').value
          });

          if (res.success) {
            Toast.success('Court Updated', res.message);
            document.getElementById('edit-court-modal').style.display = 'none';
            loadCourts();
          }
        } catch (err) { console.error(err); }
      });
    });

    // Validation Helpers
    function setFieldState(inputEl, errEl, isValid, errorMsg = '') {
      if (isValid) {
        inputEl.classList.remove('is-invalid');
        inputEl.classList.add('is-valid');
        errEl.innerText = '';
      } else {
        inputEl.classList.remove('is-valid');
        inputEl.classList.add('is-invalid');
        errEl.innerText = errorMsg;
      }
      return isValid;
    }

    function clearFieldState(inputEl, errEl) {
      inputEl.classList.remove('is-valid', 'is-invalid');
      errEl.innerText = '';
    }

    function validateCreateCourtField(field) {
      if (field === 'name') {
        const el = document.getElementById('court_name');
        const err = document.getElementById('court_name_err');
        const val = el.value.trim();
        if (!val) return setFieldState(el, err, false, 'Court name is required.');
        if (val.length < 2) return setFieldState(el, err, false, 'Court name must be at least 2 characters.');
        return setFieldState(el, err, true);
      }

      if (field === 'price') {
        const el = document.getElementById('base_price');
        const err = document.getElementById('base_price_err');
        const val = parseFloat(el.value);
        if (isNaN(val) || val <= 0) return setFieldState(el, err, false, 'Hourly rate must be a positive number.');
        return setFieldState(el, err, true);
      }
      return true;
    }

    function validateEditCourtField(field) {
      if (field === 'name') {
        const el = document.getElementById('edit_court_name');
        const err = document.getElementById('edit_court_name_err');
        const val = el.value.trim();
        if (!val) return setFieldState(el, err, false, 'Court name is required.');
        if (val.length < 2) return setFieldState(el, err, false, 'Court name must be at least 2 characters.');
        return setFieldState(el, err, true);
      }

      if (field === 'price') {
        const el = document.getElementById('edit_base_price');
        const err = document.getElementById('edit_base_price_err');
        const val = parseFloat(el.value);
        if (isNaN(val) || val <= 0) return setFieldState(el, err, false, 'Hourly rate must be a positive number.');
        return setFieldState(el, err, true);
      }
      return true;
    }

    async function openCreateCourtModal() {
      if (!currentSubscriptionSummary) {
        await checkSubscriptionLimits();
      }

      const sub = currentSubscriptionSummary ? currentSubscriptionSummary.subscription : null;
      const usage = currentSubscriptionSummary ? currentSubscriptionSummary.usage : null;

      const maxCourts = sub ? parseInt(sub.max_courts || 2) : 2;
      const currCourts = (usage && usage.courts !== undefined) ? parseInt(usage.courts) : Object.keys(currentCourtsMap).length;

      if (currCourts >= maxCourts) {
        openUpgradePlanModal();
        return;
      }

      resetCreateCourtForm();
      const modal = document.getElementById('create-court-modal');
      if (modal) modal.style.display = 'grid';
    }

    function openUpgradePlanModal(customMsg = '') {
      UpgradePlanModal.open(customMsg);
    }

    function closeUpgradePlanModal() {
      UpgradePlanModal.close();
    }

    function redirectToMyPlanPage() {
      window.location.href = '/pikvero/public/owner/my-plan.php';
    }

    function resetCreateCourtForm() {
      document.getElementById('create-court-form').reset();
      document.getElementById('court_type').value   = 'outdoor';
      document.getElementById('court_surface').value = 'cushioned_acrylic';
      document.getElementById('court_status').value  = 'active';
      document.getElementById('base_price').value    = '400.00';
      clearFieldState(document.getElementById('court_name'), document.getElementById('court_name_err'));
      clearFieldState(document.getElementById('base_price'), document.getElementById('base_price_err'));
    }

    function openEditCourtModal(courtId) {
      if (!canEditCourt) {
        Toast.error('Access Denied', 'You do not have permission (court.update) to edit courts.');
        return;
      }
      const c = currentCourtsMap[courtId];
      if (!c) return;

      document.getElementById('edit_court_id').value = c.id;
      document.getElementById('edit_court_name').value = c.name || '';
      document.getElementById('edit_court_type').value = c.court_type || 'outdoor';
      document.getElementById('edit_court_surface').value = c.surface_type || 'cushioned_acrylic';
      document.getElementById('edit_court_status').value = c.status || 'active';
      document.getElementById('edit_base_price').value = parseFloat(c.base_price_per_hour).toFixed(2);

      clearFieldState(document.getElementById('edit_court_name'), document.getElementById('edit_court_name_err'));
      clearFieldState(document.getElementById('edit_base_price'), document.getElementById('edit_base_price_err'));

      document.getElementById('edit-court-modal').style.display = 'grid';
    }

    async function loadFacilitiesDropdown() {
      try {
        const res = await Api.get('/pikvero/api/owner/facilities.php');
        if (res.success && res.data.length > 0) {
          document.getElementById('court_facility_id').innerHTML = res.data.map(f => `<option value="${f.id}">${f.name}</option>`).join('');
        }
      } catch (e) { console.error(e); }
    }

    async function loadCourts() {
      try {
        const res = await Api.get('/pikvero/api/owner/courts.php');
        const container = document.getElementById('owner-courts-list');

        if (res.success && res.data.length > 0) {
          currentCourtsMap = {};
          res.data.forEach(c => { currentCourtsMap[c.id] = c; });

          container.innerHTML = res.data.map(c => {
            let statusBadgeClass = 'green';
            if (c.status === 'inactive') statusBadgeClass = 'coral';
            if (c.status === 'maintenance') statusBadgeClass = 'sand';

            return `
              <div class="card-streetside" style="display:flex; flex-direction:column; justify-content:space-between;">
                <div>
                  ${renderCourtCarouselHtml(c)}
                  <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                    <span class="badge-streetside lime">${c.court_type.toUpperCase()}</span>
                    <span class="badge-streetside ${statusBadgeClass}">${c.status.toUpperCase()}</span>
                  </div>
                  <h3 style="font-size:1.2rem; font-weight:800; text-transform:uppercase; margin:0 0 4px;">${c.name}</h3>
                  <p style="font-size:0.85rem; color:#4a5c56; margin-bottom:12px;"><i class="bi bi-building-fill" style="color:var(--coral);"></i> ${c.facility_name}</p>
                </div>

                <div>
                  <div style="display:flex; justify-content:space-between; align-items:center; border-top:1px solid var(--line); padding-top:10px; margin-bottom:12px;">
                    <div>
                      <span class="mono" style="font-size:0.65rem; display:block; color:#4a5c56;">HOURLY RATE</span>
                      <strong style="font-size:1.1rem; color:var(--green);">₱${parseFloat(c.base_price_per_hour).toFixed(2)}</strong><span style="font-size:0.75rem;">/hr</span>
                    </div>
                    <div>
                      <span class="mono" style="font-size:0.65rem; display:block; color:#4a5c56; text-align:right;">SURFACE</span>
                      <span style="font-weight:700; font-size:0.75rem;">${(c.surface_type || 'Acrylic').replace('_', ' ').toUpperCase()}</span>
                    </div>
                  </div>

                  <div style="display:flex; gap:6px; flex-wrap:wrap;">
                    <button onclick="openViewCourtModal(${c.id})" class="button sand" style="flex:1; padding:6px 10px; font-size:0.75rem;">
                      <i class="bi bi-eye-fill"></i> View
                    </button>
                    ${canEditCourt ? `
                      <button onclick="openEditCourtModal(${c.id})" class="button sand" style="flex:1; padding:6px 10px; font-size:0.75rem;">
                        <i class="bi bi-pencil-fill"></i> Edit
                      </button>
                    ` : ''}
                    ${canManagePricing ? `
                      <button onclick="openPricingPanel(${c.id})" class="button lime" style="flex:1; padding:6px 10px; font-size:0.75rem;">
                        <i class="bi bi-clock-history"></i> Pricing
                      </button>
                    ` : ''}
                    ${canBlockCourt ? `
                      <button onclick="blockCourtSlot(${c.id})" class="button coral" style="flex:1; padding:6px 10px; font-size:0.75rem;">
                        <i class="bi bi-calendar-x-fill"></i> Block
                      </button>
                    ` : ''}
                  </div>
                </div>
              </div>
            `;
          }).join('');
        } else {
          container.innerHTML = `
            <div class="card-streetside sand" style="grid-column: 1 / -1; max-width: 520px; margin: 40px auto; text-align: center; padding: 36px 24px;">
              <div class="brand-mark" style="width: 56px; height: 56px; font-size: 1.8rem; margin: 0 auto 16px; background: var(--sky);">
                <i class="bi bi-layers-fill"></i>
              </div>
              <h3 style="font-size: 1.35rem; font-weight: 800; text-transform: uppercase; margin: 0 0 8px;">NO COURTS REGISTERED YET</h3>
              <p style="font-size: 0.88rem; color: #3b4e48; margin-bottom: 20px; line-height: 1.4;">
                No courts found in this facility. Add your first court to set hourly rates and allow players to reserve slots.
              </p>
              ${canCreateCourt ? `
                <button onclick="openCreateCourtModal()" class="button lime" style="padding: 10px 20px; font-size: 0.88rem;">
                  <i class="bi bi-plus-lg"></i> Add Your First Court
                </button>
              ` : ''}
            </div>
          `;
        }
      } catch (e) { console.error(e); }
    }

    // ── Time-Based Pricing Panel ──────────────────────────────────────────────
    let activePricingCourtId = null;
    let currentPricingRules = {}; // keyed by rule id

    async function openPricingPanel(courtId) {
      if (!canManagePricing) {
        Toast.error('Access Denied', 'You do not have permission (pricing.manage) to manage court pricing rules.');
        return;
      }
      activePricingCourtId = courtId;
      const c = currentCourtsMap[courtId];
      document.getElementById('pricing-panel-courtname').textContent = `Court: ${c ? c.name : '#' + courtId} — ${c ? c.facility_name : ''}`;
      document.getElementById('pricing-panel').style.display = 'flex';
      document.body.style.overflow = 'hidden';
      // Clear add form
      ['pr_name','pr_start','pr_end','pr_price'].forEach(id => {
        const el = document.getElementById(id);
        if (el) { el.value = id === 'pr_start' ? '06:00' : id === 'pr_end' ? '12:00' : ''; el.classList.remove('is-valid','is-invalid'); }
      });
      // Reset APPLIES TO Select2 to "All Days"
      $('#pr_day_type').val(['all']).trigger('change');
      ['pr_name_err','pr_start_err','pr_end_err','pr_price_err'].forEach(id => { const el = document.getElementById(id); if(el) el.textContent = ''; });
      await loadPricingRules(courtId);
    }

    function closePricingPanel() {
      document.getElementById('pricing-panel').style.display = 'none';
      document.body.style.overflow = '';
      activePricingCourtId = null;
    }

    async function loadPricingRules(courtId) {
      const listEl = document.getElementById('pricing-rules-list');
      listEl.innerHTML = `<p style="color:#888;font-size:0.82rem;">Loading…</p>`;
      try {
        const res = await Api.get('/pikvero/api/owner/courts/pricing.php', { court_id: courtId });
        if (res.success) {
          currentPricingRules = {};
          (res.data || []).forEach(r => { currentPricingRules[r.id] = r; });
          renderPricingRules(res.data || []);
        } else {
          listEl.innerHTML = `<p style="color:var(--coral);font-size:0.82rem;">${res.message || 'Failed to load rules.'}</p>`;
        }
      } catch(e) {
        listEl.innerHTML = `<p style="color:var(--coral);font-size:0.82rem;">Error loading rules.</p>`;
      }
    }

    function fmt12(t) {
      if (!t) return '';
      const [h, m] = t.split(':').map(Number);
      const ampm = h >= 12 ? 'PM' : 'AM';
      const h12 = h % 12 || 12;
      return `${h12}:${String(m).padStart(2,'0')} ${ampm}`;
    }

    const DAY_TYPE_LABELS = {
      all: 'All Days',
      weekday: 'Weekdays',
      weekend: 'Weekends',
      monday: 'Mondays',
      tuesday: 'Tuesdays',
      wednesday: 'Wednesdays',
      thursday: 'Thursdays',
      friday: 'Fridays',
      saturday: 'Saturdays',
      sunday: 'Sundays'
    };

    function formatDayTypeLabel(raw) {
      if (!raw) return 'All Days';
      const parts = raw.split(',');
      return parts.map(p => DAY_TYPE_LABELS[p.trim()] || p.trim()).join(', ');
    }

    function renderPricingRules(rules) {
      const listEl = document.getElementById('pricing-rules-list');
      if (!rules.length) {
        listEl.innerHTML = `<div style="text-align:center;padding:24px 0;color:#888;">
          <i class="bi bi-clock" style="font-size:2rem;display:block;margin-bottom:8px;"></i>
          <p style="font-size:0.82rem;">No pricing rules yet.<br>The base hourly rate from the court settings will apply.</p>
        </div>`;
        return;
      }
      listEl.innerHTML = rules.map(r => {
        const isActive = parseInt(r.is_active) === 1;
        return `
          <div class="pricing-rule-row" id="pricing-rule-row-${r.id}">
            <div class="pricing-rule-info">
              <div class="pricing-rule-name">${r.name}</div>
              <div class="pricing-rule-meta">
                <i class="bi bi-clock"></i> ${fmt12(r.start_time)} – ${fmt12(r.end_time)}
                &nbsp;·&nbsp;
                <i class="bi bi-calendar3"></i> ${formatDayTypeLabel(r.day_type)}
                &nbsp;·&nbsp;
                <span style="color:var(--green);font-weight:800;">₱${parseFloat(r.price_per_hour).toFixed(2)}/hr</span>
                ${!isActive ? '<span style="color:var(--coral);font-weight:800;"> · INACTIVE</span>' : ''}
              </div>
            </div>
            <div class="pricing-rule-actions">
              <button onclick="openEditPricingRule(${r.id})" class="button sand" style="padding:5px 9px;font-size:0.72rem;">
                <i class="bi bi-pencil-fill"></i>
              </button>
              <button onclick="deletePricingRule(${r.id})" class="button coral" style="padding:5px 9px;font-size:0.72rem;">
                <i class="bi bi-trash-fill"></i>
              </button>
            </div>
          </div>
        `;
      }).join('');
    }

    function validatePricingField(id, errId, label) {
      const el = document.getElementById(id);
      const err = document.getElementById(errId);
      const val = el.value.trim();
      if (!val) return setFieldState(el, err, false, `${label} is required.`);
      if (id === 'pr_price' || id === 'epr_price') {
        const n = parseFloat(val);
        if (isNaN(n) || n <= 0) return setFieldState(el, err, false, 'Rate must be greater than 0.');
      }
      if ((id === 'pr_end' || id === 'epr_end')) {
        const startId = id === 'pr_end' ? 'pr_start' : 'epr_start';
        const startVal = document.getElementById(startId).value;
        if (startVal && val <= startVal) return setFieldState(el, err, false, 'End time must be after start time.');
      }
      return setFieldState(el, err, true);
    }

    async function submitAddPricingRule() {
      const v1 = validatePricingField('pr_name', 'pr_name_err', 'Rule name');
      const v2 = validatePricingField('pr_start', 'pr_start_err', 'Start time');
      const v3 = validatePricingField('pr_end', 'pr_end_err', 'End time');
      const v4 = validatePricingField('pr_price', 'pr_price_err', 'Rate');
      if (!v1 || !v2 || !v3 || !v4) return;

      try {
        const res = await Api.post('/pikvero/api/owner/courts/pricing-add.php', {
          court_id: activePricingCourtId,
          name: document.getElementById('pr_name').value.trim(),
          start_time: document.getElementById('pr_start').value + ':00',
          end_time: document.getElementById('pr_end').value + ':00',
          price_per_hour: document.getElementById('pr_price').value,
          day_type: $('#pr_day_type').val()
        });
        if (res.success) {
          Toast.success('Rule Added', 'Pricing rule saved successfully.');
          document.getElementById('pr_name').value = '';
          document.getElementById('pr_price').value = '';
          ['pr_name','pr_start','pr_end','pr_price'].forEach(id => { const el = document.getElementById(id); el.classList.remove('is-valid','is-invalid'); });
          await loadPricingRules(activePricingCourtId);
        } else {
          Toast.error('Failed', res.message || 'Could not add rule.');
        }
      } catch(e) { Toast.error('Error', 'An unexpected error occurred.'); }
    }

    function openEditPricingRule(ruleId) {
      const r = currentPricingRules[ruleId];
      if (!r) return;
      document.getElementById('epr_rule_id').value = r.id;
      document.getElementById('epr_name').value = r.name || '';
      document.getElementById('epr_start').value = (r.start_time || '').slice(0, 5);
      document.getElementById('epr_end').value = (r.end_time || '').slice(0, 5);
      document.getElementById('epr_price').value = parseFloat(r.price_per_hour).toFixed(2);
      // Set APPLIES TO via Select2 (supports multi-select array)
      const dayVal = r.day_type ? (r.day_type.includes(',') ? r.day_type.split(',') : [r.day_type]) : ['all'];
      $('#epr_day_type').val(dayVal).trigger('change');
      ['epr_name','epr_start','epr_end','epr_price'].forEach(id => { document.getElementById(id).classList.remove('is-valid','is-invalid'); });
      ['epr_name_err','epr_start_err','epr_end_err','epr_price_err'].forEach(id => { document.getElementById(id).textContent = ''; });
      document.getElementById('edit-pricing-modal').style.display = 'flex';
    }

    async function submitUpdatePricingRule() {
      const v1 = validatePricingField('epr_name', 'epr_name_err', 'Rule name');
      const v2 = validatePricingField('epr_start', 'epr_start_err', 'Start time');
      const v3 = validatePricingField('epr_end', 'epr_end_err', 'End time');
      const v4 = validatePricingField('epr_price', 'epr_price_err', 'Rate');
      if (!v1 || !v2 || !v3 || !v4) return;

      try {
        const res = await Api.post('/pikvero/api/owner/courts/pricing-update.php', {
          rule_id: document.getElementById('epr_rule_id').value,
          name: document.getElementById('epr_name').value.trim(),
          start_time: document.getElementById('epr_start').value + ':00',
          end_time: document.getElementById('epr_end').value + ':00',
          price_per_hour: document.getElementById('epr_price').value,
          day_type: $('#epr_day_type').val()
        });
        if (res.success) {
          Toast.success('Rule Updated', 'Pricing rule updated.');
          document.getElementById('edit-pricing-modal').style.display = 'none';
          await loadPricingRules(activePricingCourtId);
        } else {
          Toast.error('Failed', res.message || 'Could not update rule.');
        }
      } catch(e) { Toast.error('Error', 'An unexpected error occurred.'); }
    }

    async function deletePricingRule(ruleId) {
      if (!confirm('Delete this pricing rule? This cannot be undone.')) return;
      try {
        const res = await Api.post('/pikvero/api/owner/courts/pricing-delete.php', { rule_id: ruleId });
        if (res.success) {
          Toast.success('Deleted', 'Pricing rule removed.');
          await loadPricingRules(activePricingCourtId);
        } else {
          Toast.error('Failed', res.message || 'Could not delete rule.');
        }
      } catch(e) { Toast.error('Error', 'An unexpected error occurred.'); }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Court View Modal
    // ─────────────────────────────────────────────────────────────────────────
    function closeViewModal() {
      document.getElementById('view-court-modal').style.display = 'none';
      document.body.style.overflow = '';
    }
    document.getElementById('view-court-modal').addEventListener('click', e => {
      if (e.target === document.getElementById('view-court-modal')) closeViewModal();
    });

    function fmtTime(t) {
      if (!t) return '—';
      const [h, m] = t.split(':').map(Number);
      const ampm = h < 12 ? 'AM' : 'PM';
      const h12  = h === 0 ? 12 : h > 12 ? h - 12 : h;
      return h12 + ':' + String(m).padStart(2, '0') + ' ' + ampm;
    }

    function fmtDate(d) {
      if (!d) return '—';
      const dt = new Date(d + 'T00:00:00');
      return dt.toLocaleDateString('en-PH', { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' });
    }

    async function openViewCourtModal(courtId) {
      const modal = document.getElementById('view-court-modal');
      document.getElementById('vm-court-name').textContent = 'Loading...';
      document.getElementById('vm-body').innerHTML = `
        <div style="text-align:center;padding:40px 0;color:#5a7060;">
          <i class="bi bi-arrow-clockwise" style="font-size:2rem;"></i>
          <p style="margin-top:8px;font-weight:700;">Fetching court details...</p>
        </div>`;
      modal.style.display = 'flex';
      document.body.style.overflow = 'hidden';

      try {
        const res = await Api.get('/pikvero/api/owner/courts/detail.php', { court_id: courtId });
        if (!res.success) {
          Toast.error('Error', res.message || 'Failed to load court.');
          closeViewModal(); return;
        }

        const { court, pricing, blockouts, operating_hours } = res.data;

        // Status colour
        const statusColor = court.status === 'active' ? '#2d6a4f' : court.status === 'inactive' ? '#c0392b' : '#8b6914';
        const statusBg    = court.status === 'active' ? '#d8f3dc' : court.status === 'inactive' ? '#fde8e4' : '#fef9e7';

        // Surface label
        const surfaceLabel = (court.surface_type || '').replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()) || 'Acrylic';

        // ── Operating hours grid ──
        const dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        const ohMap = {};
        (operating_hours || []).forEach(h => { ohMap[h.day_of_week] = h; });
        const ohHtml = dayNames.map((dn, i) => {
          const h = ohMap[i];
          return `<div class="vm-day-col">
            <div class="vm-day-name">${dn}</div>
            <div class="vm-day-hours">${h ? fmtTime(h.open_time) + '<br>' + fmtTime(h.close_time) : 'Closed'}</div>
          </div>`;
        }).join('');

        // ── Pricing rules ──
        const pricingHtml = pricing.length > 0
          ? pricing.map(r => `
            <div class="vm-pricing-row">
              <div>
                <div style="font-weight:900;font-size:0.88rem;">${r.name}</div>
                <div style="font-size:0.72rem;color:#5a7060;margin-top:2px;">
                  <i class="bi bi-clock"></i> ${fmtTime(r.start_time)} – ${fmtTime(r.end_time)}
                  &nbsp;·&nbsp;
                  <i class="bi bi-calendar3"></i> ${(r.day_type || 'all').replace(/,/g, ', ')}
                </div>
              </div>
              <div style="text-align:right;white-space:nowrap;">
                <span style="font-size:1.08rem;font-weight:900;color:var(--green);">&#8369;${parseFloat(r.price_per_hour).toFixed(2)}</span>
                <span style="font-size:0.72rem;color:#5a7060;">/hr</span>
              </div>
            </div>`).join('')
          : '<p style="color:#aaa;font-size:0.82rem;margin:0;">No pricing rules defined — base rate applies to all hours.</p>';

        // ── Blockouts ──
        const now      = new Date().toISOString().split('T')[0];
        const upcoming = (blockouts || []).filter(b => b.block_date >= now);
        const past     = (blockouts || []).filter(b => b.block_date < now);

        const blockoutRow = b => `
          <div class="vm-blockout-row">
            <div style="min-width:18px;"><i class="bi bi-calendar-x-fill" style="color:#e05c3a;"></i></div>
            <div class="vm-blockout-date">${fmtDate(b.block_date)}</div>
            <div style="flex:1;">
              <div style="font-size:0.75rem;font-weight:800;color:#5a7060;">${fmtTime(b.start_time)} – ${fmtTime(b.end_time)}</div>
              <div style="font-size:0.72rem;color:#a05a40;margin-top:1px;">${b.reason || 'Blocked'}</div>
            </div>
            ${b.created_at ? `<div style="font-size:0.65rem;color:#bbb;white-space:nowrap;text-align:right;">Added<br>${new Date(b.created_at).toLocaleDateString('en-PH',{month:'short',day:'numeric'})}</div>` : ''}
          </div>`;

        const blockoutsHtml = blockouts.length === 0
          ? '<p style="color:#aaa;font-size:0.82rem;margin:0;">No blockouts recorded for this court.</p>'
          : [
              upcoming.length ? `<div style="font-size:0.7rem;font-weight:900;color:#e05c3a;text-transform:uppercase;margin-bottom:7px;"><i class="bi bi-alarm-fill"></i> Upcoming (${upcoming.length})</div>${upcoming.map(blockoutRow).join('')}` : '',
              past.length     ? `<div style="font-size:0.7rem;font-weight:900;color:#aaa;text-transform:uppercase;margin:12px 0 7px;">Past (${past.length})</div>${past.map(blockoutRow).join('')}` : ''
            ].join('');

        document.getElementById('vm-court-name').textContent = court.name;
        document.getElementById('vm-body').innerHTML = `

          <!-- Court Info -->
          <div class="vm-section-title"><i class="bi bi-layers-fill" style="color:var(--green);"></i> Court Information</div>
          <div class="vm-info-grid">
            <div class="vm-info-cell">
              <div class="vm-info-label">Court ID</div>
              <div class="vm-info-value">#${court.id}</div>
            </div>
            <div class="vm-info-cell">
              <div class="vm-info-label">Court No.</div>
              <div class="vm-info-value">${court.court_number || '—'}</div>
            </div>
            <div class="vm-info-cell">
              <div class="vm-info-label">Type</div>
              <div class="vm-info-value">${(court.court_type || '').toUpperCase()}</div>
            </div>
            <div class="vm-info-cell">
              <div class="vm-info-label">Surface</div>
              <div class="vm-info-value">${surfaceLabel}</div>
            </div>
            <div class="vm-info-cell">
              <div class="vm-info-label">Base Rate</div>
              <div class="vm-info-value" style="color:var(--green);">&#8369;${parseFloat(court.base_price_per_hour).toFixed(2)}<span style="font-size:0.72rem;font-weight:700;color:#5a7060;">/hr</span></div>
            </div>
            <div class="vm-info-cell">
              <div class="vm-info-label">Status</div>
              <div class="vm-info-value">
                <span class="vm-badge" style="background:${statusBg};color:${statusColor};border-color:${statusColor};">${court.status.toUpperCase()}</span>
              </div>
            </div>
          </div>

          <!-- Facility Info -->
          <div class="vm-section-title"><i class="bi bi-building-fill" style="color:var(--coral);"></i> Facility</div>
          <div class="vm-info-grid">
            <div class="vm-info-cell" style="grid-column:1/-1;">
              <div class="vm-info-label">Facility Name</div>
              <div class="vm-info-value">${court.facility_name || '—'}</div>
            </div>
            <div class="vm-info-cell" style="grid-column:1/-1;">
              <div class="vm-info-label">Address</div>
              <div class="vm-info-value" style="font-size:0.82rem;">${[court.address, court.city].filter(Boolean).join(', ') || '—'}</div>
            </div>
          </div>

          <!-- Operating Hours -->
          <div class="vm-section-title"><i class="bi bi-clock-fill" style="color:#3a7bd5;"></i> Operating Hours</div>
          <div class="vm-hours-grid">${ohHtml}</div>

          <!-- Pricing Rules -->
          <div class="vm-section-title">
            <i class="bi bi-tag-fill" style="color:var(--green);"></i> Pricing Rules
            <span style="background:#eafaf1;color:#2d6a4f;border-radius:20px;padding:1px 8px;font-size:0.7rem;margin-left:4px;">${pricing.length}</span>
          </div>
          ${pricingHtml}

          <!-- Blockouts -->
          <div class="vm-section-title">
            <i class="bi bi-calendar-x-fill" style="color:#e05c3a;"></i> Schedule Blockouts
            <span style="background:#fde8e4;color:#c0392b;border-radius:20px;padding:1px 8px;font-size:0.7rem;margin-left:4px;">${blockouts.length}</span>
          </div>
          ${blockoutsHtml}

          <!-- Quick Actions -->
          ${(canEditCourt || canManagePricing || canBlockCourt) ? `
            <div class="vm-quick-actions" style="display:flex;gap:10px;margin-top:24px;padding-top:16px;border-top:2px solid var(--line);">
              ${canEditCourt ? `
                <button onclick="closeViewModal(); openEditCourtModal(${court.id});" class="button sand" style="flex:1;padding:9px;font-size:0.78rem;">
                  <i class="bi bi-pencil-fill"></i> Edit Court
                </button>
              ` : ''}
              ${canManagePricing ? `
                <button onclick="closeViewModal(); openPricingPanel(${court.id});" class="button lime" style="flex:1;padding:9px;font-size:0.78rem;">
                  <i class="bi bi-clock-history"></i> Manage Pricing
                </button>
              ` : ''}
              ${canBlockCourt ? `
                <button onclick="closeViewModal(); blockCourtSlot(${court.id});" class="button coral" style="flex:1;padding:9px;font-size:0.78rem;">
                  <i class="bi bi-calendar-x-fill"></i> Add Blockout
                </button>
              ` : ''}
            </div>
          ` : ''}
        `;

      } catch(e) {
        console.error(e);
        Toast.error('Error', 'Could not load court details.');
        closeViewModal();
      }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Blockout Modal logic
    // ─────────────────────────────────────────────────────────────────────────
    let _blockoutCourtId = null;
    let _blockoutMode    = 'date';   // 'date' | 'days'
    let _selectedDates   = [];       // ['2026-08-18', ...]
    let _selectedDays    = [];       // ['Monday', 'Wednesday', ...]

    function blockCourtSlot(courtId) {
      if (!canBlockCourt) {
        Toast.error('Access Denied', 'You do not have permission (availability.manage) to manage court blockouts.');
        return;
      }
      _blockoutCourtId = courtId;
      _blockoutMode    = 'date';
      _selectedDates   = [];
      _selectedDays    = [];

      // Reset UI
      document.getElementById('bm_date_input').value = '';
      document.getElementById('bm_date_chips').innerHTML = '';
      document.getElementById('bm_weeks').value = 4;
      document.getElementById('bm_start_hour').value = 6;
      document.getElementById('bm_end_hour').value = 22;
      document.getElementById('bm_reason').value = 'Tournament / Maintenance';
      document.getElementById('bm_time_err').textContent = '';
      document.querySelectorAll('.bm-day-chip').forEach(c => c.classList.remove('active'));
      updateHourLabel('bm_start_hour', 'bm_start_label');
      updateHourLabel('bm_end_hour',   'bm_end_label');
      setBlockoutMode('date');
      updateBlockoutSummary();

      document.getElementById('blockout-modal').style.display = 'flex';
    }

    function closeBlockoutModal() {
      document.getElementById('blockout-modal').style.display = 'none';
    }

    function setBlockoutMode(mode) {
      _blockoutMode = mode;
      document.getElementById('bm-section-date').style.display = mode === 'date' ? '' : 'none';
      document.getElementById('bm-section-days').style.display = mode === 'days' ? '' : 'none';
      document.getElementById('bm-mode-date').className = 'button ' + (mode === 'date' ? 'lime' : 'sand');
      document.getElementById('bm-mode-days').className = 'button ' + (mode === 'days' ? 'lime' : 'sand');
      updateBlockoutSummary();
    }

    function formatHour(h) {
      if (h === 24) return '12:00 AM (next day)';
      const ampm = h < 12 ? 'AM' : 'PM';
      const h12  = h === 0 ? 12 : h > 12 ? h - 12 : h;
      return h12 + ':00 ' + ampm;
    }

    function updateHourLabel(sliderId, labelId) {
      const v = parseInt(document.getElementById(sliderId).value);
      document.getElementById(labelId).textContent = formatHour(v);
      updateBlockoutSummary();
    }

    function bmAddDate() {
      const val = document.getElementById('bm_date_input').value;
      if (!val) return;
      if (!_selectedDates.includes(val)) {
        _selectedDates.push(val);
        _selectedDates.sort();
        renderDateChips();
      }
      document.getElementById('bm_date_input').value = '';
      updateBlockoutSummary();
    }

    function bmRemoveDate(date) {
      _selectedDates = _selectedDates.filter(d => d !== date);
      renderDateChips();
      updateBlockoutSummary();
    }

    function renderDateChips() {
      const container = document.getElementById('bm_date_chips');
      container.innerHTML = '';
      _selectedDates.forEach(d => {
        const chip = document.createElement('span');
        chip.style.cssText = 'display:inline-flex;align-items:center;gap:5px;background:var(--ink);color:var(--white);padding:4px 10px;border-radius:20px;font-size:0.75rem;font-weight:800;cursor:pointer;';
        chip.innerHTML = d + ' <span onclick="bmRemoveDate(\'' + d + '\')">✕</span>';
        container.appendChild(chip);
      });
    }

    function toggleDayChip(btn) {
      const day = btn.dataset.day;
      if (_selectedDays.includes(day)) {
        _selectedDays = _selectedDays.filter(d => d !== day);
        btn.classList.remove('active');
      } else {
        _selectedDays.push(day);
        btn.classList.add('active');
      }
      updateBlockoutSummary();
    }

    // Generate future dates for each selected weekday across N weeks
    function generateRecurringDates() {
      const dayIndexMap = { Sunday:0, Monday:1, Tuesday:2, Wednesday:3, Thursday:4, Friday:5, Saturday:6 };
      const weeks  = parseInt(document.getElementById('bm_weeks').value) || 4;
      const today  = new Date();
      today.setHours(0,0,0,0);
      const dates  = [];
      for (let w = 0; w < weeks; w++) {
        _selectedDays.forEach(dayName => {
          const targetDay = dayIndexMap[dayName];
          const d = new Date(today);
          let diff = targetDay - d.getDay();
          if (diff < 0 || (diff === 0 && w === 0)) diff += 7;
          d.setDate(d.getDate() + diff + w * 7);
          const key = d.toISOString().split('T')[0];
          if (!dates.includes(key)) dates.push(key);
        });
      }
      return dates.sort();
    }

    function updateBlockoutSummary() {
      const startH = parseInt(document.getElementById('bm_start_hour').value);
      const endH   = parseInt(document.getElementById('bm_end_hour').value);
      const err    = document.getElementById('bm_time_err');
      if (endH <= startH) {
        err.textContent = '⚠ End hour must be after start hour.';
      } else {
        err.textContent = '';
      }
      let count = 0;
      let preview = '';
      if (_blockoutMode === 'date') {
        count = _selectedDates.length;
        preview = count > 0 ? count + ' date(s) selected' : 'No dates selected';
      } else {
        const recurring = generateRecurringDates();
        count = recurring.length;
        preview = _selectedDays.length > 0
          ? _selectedDays.join(', ') + ' — ' + count + ' occurrence(s) over ' + (document.getElementById('bm_weeks').value || 4) + ' weeks'
          : 'No days selected';
      }
      const timeStr = formatHour(startH) + ' → ' + formatHour(endH);
      document.getElementById('bm_summary_text').textContent = preview + ' · ' + timeStr;
    }

    async function submitBlockout() {
      if (!canBlockCourt) {
        Toast.error('Access Denied', 'You do not have permission (availability.manage) to manage court blockouts.');
        return;
      }
      const startH = parseInt(document.getElementById('bm_start_hour').value);
      const endH   = parseInt(document.getElementById('bm_end_hour').value);
      if (endH <= startH) { Toast.error('Invalid Time', 'End hour must be after start hour.'); return; }

      const pad = n => String(n).padStart(2,'0');
      const startTime = pad(startH) + ':00:00';
      const endTime   = endH === 24 ? '23:59:59' : pad(endH) + ':00:00';
      const reason    = document.getElementById('bm_reason').value.trim() || 'Blocked by Owner';

      const dates = _blockoutMode === 'date' ? _selectedDates : generateRecurringDates();
      if (dates.length === 0) { Toast.error('No Dates', 'Please select at least one date or day.'); return; }

      closeBlockoutModal();

      let ok = 0, fail = 0;
      for (const d of dates) {
        try {
          const res = await Api.post('/pikvero/api/owner/schedule.php', {
            court_id:   _blockoutCourtId,
            block_date: d,
            start_time: startTime,
            end_time:   endTime,
            reason:     reason
          });
          if (res.success) ok++; else fail++;
        } catch(e) { fail++; }
      }

      if (ok > 0 && fail === 0)  Toast.success('Blocked', ok + ' blockout(s) recorded successfully.');
      else if (ok > 0)           Toast.success('Partial', ok + ' blocked, ' + fail + ' failed.');
      else                       Toast.error('Failed', 'Could not record any blockouts.');
    }

    // ── Court Images Carousel & Photo Manager Modal (Up to 10 photos) ──────────

    function renderCourtCarouselHtml(court) {
      const images = court.images || [];
      const courtId = court.id;
      const count = images.length;

      if (count === 0) {
        return `
          <div class="court-carousel-container" style="position:relative; width:100%; height:180px; border-radius:12px; overflow:hidden; margin-bottom:12px; background:linear-gradient(135deg, #1b2e2b 0%, #2d4f48 100%); border:2px solid var(--ink); display:flex; flex-direction:column; align-items:center; justify-content:center; color:var(--white); text-align:center; padding:16px;">
            <i class="bi bi-images" style="font-size:2.2rem; color:var(--lime); margin-bottom:6px;"></i>
            <span style="font-weight:800; font-size:0.85rem; text-transform:uppercase; letter-spacing:0.04em;">NO PHOTOS ADDED YET</span>
            <span style="font-size:0.72rem; color:#b0c4de; margin-bottom:10px;">Add up to 10 photos for this court</span>
            ${canEditCourt ? `
              <button onclick="openCourtPhotosModal(${courtId})" class="button lime" style="padding:4px 12px; font-size:0.72rem; border-radius:8px;">
                <i class="bi bi-camera-fill"></i> Upload Photos
              </button>
            ` : ''}
          </div>
        `;
      }

      const slidesHtml = images.map((img, idx) => `
        <div class="carousel-slide" style="flex:0 0 100%; width:100%; height:180px; position:relative; background:#111;">
          <img src="${img.image_path}" alt="${court.name} Photo ${idx + 1}" style="width:100%; height:100%; object-fit:cover;" onerror="this.onerror=null; this.src='/pikvero/assets/images/logo.png';">
        </div>
      `).join('');

      const dotsHtml = images.map((_, idx) => `
        <span id="dot-${courtId}-${idx}" class="carousel-dot ${idx === 0 ? 'active' : ''}" onclick="setCourtCarouselSlide(${courtId}, ${idx})" style="width:7px; height:7px; border-radius:50%; background:${idx === 0 ? 'var(--lime)' : 'rgba(255,255,255,0.5)'}; cursor:pointer; transition:all 0.2s ease;"></span>
      `).join('');

      return `
        <div class="court-carousel-wrapper" id="carousel-wrapper-${courtId}" data-index="0" data-total="${count}" style="position:relative; width:100%; height:180px; border-radius:12px; overflow:hidden; margin-bottom:12px; border:2px solid var(--ink); box-shadow:2px 2px 0 var(--ink);">
          <div class="court-carousel-track" id="carousel-track-${courtId}" style="display:flex; width:100%; height:100%; transition:transform 0.35s cubic-bezier(0.25, 1, 0.5, 1);">
            ${slidesHtml}
          </div>

          <!-- Counter Overlay Badge -->
          <div style="position:absolute; top:8px; right:8px; background:rgba(0,0,0,0.75); backdrop-filter:blur(4px); color:var(--white); font-size:0.68rem; font-weight:800; padding:3px 8px; border-radius:20px; border:1px solid rgba(255,255,255,0.2);">
            <i class="bi bi-images" style="color:var(--lime);"></i> <span id="carousel-counter-${courtId}">1/${count}</span> (Max 10)
          </div>

          <!-- Manage Photos Action Overlay -->
          ${canEditCourt ? `
            <button onclick="openCourtPhotosModal(${courtId})" class="button sand" style="position:absolute; top:8px; left:8px; padding:3px 8px; font-size:0.68rem; border-radius:8px; opacity:0.92; background:rgba(255,255,255,0.9);">
              <i class="bi bi-camera-fill"></i> Manage (${count}/10)
            </button>
          ` : ''}

          <!-- Navigation Arrows (only if > 1 photo) -->
          ${count > 1 ? `
            <button onclick="prevCourtCarouselSlide(${courtId})" style="position:absolute; left:6px; top:50%; transform:translateY(-50%); width:28px; height:28px; border-radius:50%; background:rgba(0,0,0,0.65); color:white; border:1px solid rgba(255,255,255,0.3); display:flex; align-items:center; justify-content:center; cursor:pointer; font-size:0.8rem; z-index:2;">
              <i class="bi bi-chevron-left"></i>
            </button>
            <button onclick="nextCourtCarouselSlide(${courtId})" style="position:absolute; right:6px; top:50%; transform:translateY(-50%); width:28px; height:28px; border-radius:50%; background:rgba(0,0,0,0.65); color:white; border:1px solid rgba(255,255,255,0.3); display:flex; align-items:center; justify-content:center; cursor:pointer; font-size:0.8rem; z-index:2;">
              <i class="bi bi-chevron-right"></i>
            </button>
            <!-- Dots -->
            <div style="position:absolute; bottom:8px; left:50%; transform:translateX(-50%); display:flex; gap:4px; z-index:2; background:rgba(0,0,0,0.5); padding:3px 8px; border-radius:12px;">
              ${dotsHtml}
            </div>
          ` : ''}
        </div>
      `;
    }

    function setCourtCarouselSlide(courtId, index) {
      const wrapper = document.getElementById(`carousel-wrapper-${courtId}`);
      if (!wrapper) return;
      const total = parseInt(wrapper.dataset.total) || 1;
      if (index < 0) index = total - 1;
      if (index >= total) index = 0;
      
      wrapper.dataset.index = index;
      const track = document.getElementById(`carousel-track-${courtId}`);
      if (track) {
        track.style.transform = `translateX(-${index * 100}%)`;
      }
      
      const counter = document.getElementById(`carousel-counter-${courtId}`);
      if (counter) {
        counter.textContent = `${index + 1}/${total}`;
      }

      for (let i = 0; i < total; i++) {
        const dot = document.getElementById(`dot-${courtId}-${i}`);
        if (dot) {
          dot.style.background = (i === index) ? 'var(--lime)' : 'rgba(255,255,255,0.5)';
        }
      }
    }

    function prevCourtCarouselSlide(courtId) {
      const wrapper = document.getElementById(`carousel-wrapper-${courtId}`);
      if (!wrapper) return;
      let idx = parseInt(wrapper.dataset.index) || 0;
      setCourtCarouselSlide(courtId, idx - 1);
    }

    function nextCourtCarouselSlide(courtId) {
      const wrapper = document.getElementById(`carousel-wrapper-${courtId}`);
      if (!wrapper) return;
      let idx = parseInt(wrapper.dataset.index) || 0;
      setCourtCarouselSlide(courtId, idx + 1);
    }

    // Photo Manager Modal Functions
    let activeCpmCourtId = null;

    function openCourtPhotosModal(courtId) {
      if (!canEditCourt) {
        Toast.error('Access Denied', 'You do not have permission to manage court photos.');
        return;
      }
      activeCpmCourtId = courtId;
      const court = currentCourtsMap[courtId];
      document.getElementById('cpm_court_id').value = courtId;
      document.getElementById('cpm-court-title').textContent = `Court: ${court ? court.name : '#' + courtId} (${court ? court.facility_name : ''})`;
      
      toggleCpmAddTab('file');
      renderCpmGrid(courtId);

      document.getElementById('court-photos-modal').style.display = 'flex';
      document.body.style.overflow = 'hidden';
    }

    function closeCourtPhotosModal() {
      document.getElementById('court-photos-modal').style.display = 'none';
      document.body.style.overflow = '';
      loadCourts(); // reload to reflect carousel updates
    }

    function toggleCpmAddTab(tab) {
      const fileForm = document.getElementById('cpm-file-form');
      const urlForm  = document.getElementById('cpm-url-form');
      const btnFile  = document.getElementById('cpm-tab-file');
      const btnUrl   = document.getElementById('cpm-tab-url');

      if (tab === 'file') {
        fileForm.style.display = 'block';
        urlForm.style.display  = 'none';
        btnFile.className = 'button lime';
        btnUrl.className  = 'button sand';
      } else {
        fileForm.style.display = 'none';
        urlForm.style.display  = 'block';
        btnFile.className = 'button sand';
        btnUrl.className  = 'button lime';
      }
    }

    function renderCpmGrid(courtId) {
      const court = currentCourtsMap[courtId];
      const images = (court && court.images) ? court.images : [];
      const count = images.length;

      const badge = document.getElementById('cpm-count-badge');
      if (badge) {
        badge.textContent = `${count} / 10 Photos`;
        badge.className = `badge-streetside ${count >= 10 ? 'coral' : 'lime'}`;
      }

      const container = document.getElementById('cpm-grid-container');
      let html = '';

      // Render 10 slots
      for (let i = 0; i < 10; i++) {
        if (i < count) {
          const img = images[i];
          html += `
            <div style="position:relative; width:100%; height:110px; border-radius:10px; overflow:hidden; border:2px solid var(--ink); background:#111;">
              <img src="${img.image_path}" style="width:100%; height:100%; object-fit:cover;" onerror="this.onerror=null; this.src='/pikvero/assets/images/logo.png';">
              <span style="position:absolute; top:4px; left:4px; background:rgba(0,0,0,0.75); color:white; font-size:0.62rem; font-weight:900; padding:2px 6px; border-radius:10px;">
                #${i + 1}
              </span>
              <button onclick="deleteCpmImage(${img.id})" style="position:absolute; top:4px; right:4px; background:rgba(224,92,58,0.9); color:white; border:1px solid var(--ink); border-radius:6px; width:24px; height:24px; display:flex; align-items:center; justify-content:center; cursor:pointer; font-size:0.75rem;">
                <i class="bi bi-trash-fill"></i>
              </button>
            </div>
          `;
        } else {
          html += `
            <div style="position:relative; width:100%; height:110px; border-radius:10px; border:2px dashed #b0c4de; background:#f8faf9; display:flex; flex-direction:column; align-items:center; justify-content:center; color:#7a8c85; font-size:0.72rem; text-align:center; padding:6px;">
              <i class="bi bi-image-fill" style="font-size:1.4rem; color:#b0c4de; margin-bottom:4px;"></i>
              <span style="font-weight:700;">Slot #${i + 1}</span>
              <span style="font-size:0.62rem; color:#aaa;">(Empty)</span>
            </div>
          `;
        }
      }
      container.innerHTML = html;
    }

    async function handleCpmFileUpload(e) {
      e.preventDefault();
      const input = document.getElementById('cpm_file_input');
      if (!input.files || input.files.length === 0) {
        Toast.error('No Files Selected', 'Please select one or more image files to upload.');
        return;
      }

      const courtId = document.getElementById('cpm_court_id').value;
      const files = Array.from(input.files);
      const totalFiles = files.length;
      let successCount = 0;
      let failCount = 0;

      const submitBtn = e.target.querySelector('button[type="submit"]');
      if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = `<i class="bi bi-arrow-repeat spin"></i> Uploading ${totalFiles} file(s)...`;
      }

      for (let i = 0; i < files.length; i++) {
        const file = files[i];
        const formData = new FormData();
        formData.append('court_id', courtId);
        formData.append('image', file);

        try {
          const res = await Api.post('/pikvero/api/owner/courts/images-add.php', formData);
          if (res && res.success) {
            successCount++;
          } else {
            failCount++;
            if (res && res.message) {
              Toast.error('Upload Warning', res.message);
            }
          }
        } catch(err) {
          console.error('File Upload Error:', err);
          failCount++;
        }
      }

      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.innerHTML = `<i class="bi bi-upload"></i> Upload Selected`;
      }

      input.value = '';

      if (successCount > 0) {
        Toast.success('Photos Uploaded', `${successCount} photo(s) successfully added to court carousel.` + (failCount > 0 ? ` (${failCount} failed)` : ''));
        const courtsRes = await Api.get('/pikvero/api/owner/courts.php');
        if (courtsRes && courtsRes.success) {
          courtsRes.data.forEach(c => { currentCourtsMap[c.id] = c; });
        }
        renderCpmGrid(courtId);
      } else {
        Toast.error('Upload Failed', 'Could not upload selected photos.');
      }
    }

    async function handleCpmUrlSubmit(e) {
      e.preventDefault();
      const input = document.getElementById('cpm_url_input');
      const url = input.value.trim();
      if (!url) {
        Toast.error('Empty URL', 'Please enter a valid image web URL.');
        return;
      }

      const courtId = document.getElementById('cpm_court_id').value;
      try {
        const res = await Api.post('/pikvero/api/owner/courts/images-add.php', {
          court_id: courtId,
          image_url: url
        });

        if (res.success) {
          Toast.success('Photo Added', 'Image URL added to court carousel.');
          input.value = '';
          const courtsRes = await Api.get('/pikvero/api/owner/courts.php');
          if (courtsRes.success) {
            courtsRes.data.forEach(c => { currentCourtsMap[c.id] = c; });
          }
          renderCpmGrid(courtId);
        } else {
          Toast.error('Failed', res.message || 'Could not add image URL.');
        }
      } catch(err) {
        Toast.error('Error', 'An unexpected error occurred.');
      }
    }

    async function deleteCpmImage(imageId) {
      if (!confirm('Delete this photo from court carousel?')) return;
      const courtId = document.getElementById('cpm_court_id').value;
      try {
        const res = await Api.post('/pikvero/api/owner/courts/images-delete.php', { image_id: imageId });
        if (res.success) {
          Toast.success('Photo Deleted', 'Photo removed from court carousel.');
          const courtsRes = await Api.get('/pikvero/api/owner/courts.php');
          if (courtsRes.success) {
            courtsRes.data.forEach(c => { currentCourtsMap[c.id] = c; });
          }
          renderCpmGrid(courtId);
        } else {
          Toast.error('Failed', res.message || 'Could not delete photo.');
        }
      } catch(err) {
        Toast.error('Error', 'An unexpected error occurred.');
      }
    }
  </script>
</body>
</html>
