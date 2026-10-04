<?php
require_once __DIR__ . '/../../app/bootstrap.php';

use App\Core\Auth\Auth;

$pageTitle  = 'Pikvero — Bug Reports & Feature Requests';
$headExtras = ['datatables'];
require_once __DIR__ . '/../../includes/head.php';

$role = Auth::role();
if ($role !== 'super_admin' && $role !== 'platform_admin' && !Auth::can('system.manage')) {
    header('Location: /pikvero/public/403.php');
    exit;
}
?>
  <style>
    .fb-kpi-card {
      background: var(--white);
      border: 2px solid var(--ink);
      border-radius: 12px;
      padding: 14px 16px;
      box-shadow: 3px 3px 0 var(--ink);
      display: flex;
      flex-direction: column;
      justify-content: space-between;
    }
    .fb-kpi-val {
      font-size: 1.6rem;
      font-weight: 900;
      line-height: 1.1;
      margin: 4px 0 2px;
      font-family: 'DM Mono', monospace;
    }
    .fb-kpi-lbl {
      font-size: 0.7rem;
      font-weight: 800;
      text-transform: uppercase;
      font-family: 'DM Mono', monospace;
      color: #4a5c56;
    }

    /* Badge & Tag Helpers */
    .badge-priority-critical { background:#fee2e2; color:#b91c1c; border:1.5px solid #b91c1c; font-weight:800; }
    .badge-priority-high     { background:#ffedd5; color:#c2410c; border:1.5px solid #c2410c; font-weight:800; }
    .badge-priority-medium   { background:#e0f2fe; color:#0369a1; border:1.5px solid #0369a1; font-weight:800; }
    .badge-priority-low      { background:#f1f5f9; color:#475569; border:1.5px solid #475569; font-weight:800; }

    .badge-status-pending      { background:#fef9c3; color:#a16207; border:1.5px solid #a16207; font-weight:800; }
    .badge-status-under_review { background:#e0e7ff; color:#4338ca; border:1.5px solid #4338ca; font-weight:800; }
    .badge-status-in_progress  { background:#e0f2fe; color:#0284c7; border:1.5px solid #0284c7; font-weight:800; }
    .badge-status-resolved     { background:#dcfce7; color:#15803d; border:1.5px solid #15803d; font-weight:800; }
    .badge-status-declined     { background:#fee2e2; color:#be123c; border:1.5px solid #be123c; font-weight:800; }

    .report-card-item {
      border: 2px solid var(--ink);
      border-radius: 12px;
      background: var(--white);
      padding: 16px;
      margin-bottom: 12px;
      box-shadow: 3px 3px 0 var(--ink);
      transition: all 0.15s ease;
    }
    .report-card-item:hover {
      transform: translateY(-2px);
      box-shadow: 4px 4px 0 var(--ink);
    }
  </style>
</head>
<body>

  <aside id="sidebar-container"></aside>
  <header id="navbar-container"></header>

  <main class="portal-main">
    <div>
      <!-- TOP HEADER & TITLE -->
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:12px;">
        <div>
          <div class="eyebrow"><i class="bi bi-shield-exclamation"></i> PLATFORM SUPPORT &amp; FEEDBACK</div>
          <h1 style="font-size: clamp(1.8rem, 4vw, 2.3rem); font-weight:800; text-transform:uppercase; margin:4px 0 0;">BUG &amp; FEATURE REPORTS</h1>
        </div>
        <div style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
          <button type="button" onclick="loadAdminReports()" class="button sand" style="padding:9px 16px; font-size:0.82rem;">
            <i class="bi bi-arrow-clockwise"></i> Refresh Feed
          </button>
        </div>
      </div>

      <!-- KPI SUMMARY METRICS -->
      <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap:14px; margin-bottom:22px;">
        <div class="fb-kpi-card" style="background:var(--white);">
          <div class="fb-kpi-lbl"><i class="bi bi-inbox-fill"></i> Total Reports</div>
          <div class="fb-kpi-val" id="kpi-total">0</div>
          <div style="font-size:0.72rem; color:#4a5c56;">Bugs &amp; Feature Requests</div>
        </div>

        <div class="fb-kpi-card" style="background:#fff1f2; border-color:#be123c;">
          <div class="fb-kpi-lbl" style="color:#be123c;"><i class="bi bi-bug-fill"></i> Bug Reports</div>
          <div class="fb-kpi-val" id="kpi-bugs" style="color:#be123c;">0</div>
          <div style="font-size:0.72rem; color:#9f1239;" id="kpi-critical-sub">0 critical priority</div>
        </div>

        <div class="fb-kpi-card" style="background:#f0fdf4; border-color:#15803d;">
          <div class="fb-kpi-lbl" style="color:#15803d;"><i class="bi bi-lightbulb-fill"></i> Feature Requests</div>
          <div class="fb-kpi-val" id="kpi-features" style="color:#15803d;">0</div>
          <div style="font-size:0.72rem; color:#166534;">Enhancement ideas</div>
        </div>

        <div class="fb-kpi-card" style="background:#fefce8; border-color:#a16207;">
          <div class="fb-kpi-lbl" style="color:#a16207;"><i class="bi bi-hourglass-split"></i> Pending Review</div>
          <div class="fb-kpi-val" id="kpi-pending" style="color:#a16207;">0</div>
          <div style="font-size:0.72rem; color:#854d0e;">Needs action</div>
        </div>

        <div class="fb-kpi-card" style="background:#f0f9ff; border-color:#0284c7;">
          <div class="fb-kpi-lbl" style="color:#0284c7;"><i class="bi bi-gear-wide-connected"></i> In Progress</div>
          <div class="fb-kpi-val" id="kpi-inprogress" style="color:#0284c7;">0</div>
          <div style="font-size:0.72rem; color:#0369a1;">Being addressed</div>
        </div>

        <div class="fb-kpi-card" style="background:var(--lime); border-color:var(--ink);">
          <div class="fb-kpi-lbl" style="color:var(--ink);"><i class="bi bi-check2-circle"></i> Resolved</div>
          <div class="fb-kpi-val" id="kpi-resolved" style="color:var(--ink);">0</div>
          <div style="font-size:0.72rem; color:#1a3d34;">Completed &amp; Fixed</div>
        </div>
      </div>

      <!-- FILTER TOOLBAR -->
      <div class="card-streetside" style="padding:16px 20px; background:var(--white); margin-bottom:20px;">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
          <div style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
            <!-- Type Filter -->
            <div>
              <label class="mono" style="font-size:0.72rem; display:block; margin-bottom:4px; font-weight:800;">TYPE:</label>
              <select id="filter-type" onchange="loadAdminReports()" style="padding:7px 12px; border:2px solid var(--ink); border-radius:8px; font-weight:800; font-family:'DM Mono', monospace; font-size:0.78rem;">
                <option value="all">ALL TYPES</option>
                <option value="bug">🐛 BUGS ONLY</option>
                <option value="feature_request">💡 FEATURE REQUESTS ONLY</option>
              </select>
            </div>

            <!-- Status Filter -->
            <div>
              <label class="mono" style="font-size:0.72rem; display:block; margin-bottom:4px; font-weight:800;">STATUS:</label>
              <select id="filter-status" onchange="loadAdminReports()" style="padding:7px 12px; border:2px solid var(--ink); border-radius:8px; font-weight:800; font-family:'DM Mono', monospace; font-size:0.78rem;">
                <option value="all">ALL STATUSES</option>
                <option value="pending">PENDING</option>
                <option value="under_review">UNDER REVIEW</option>
                <option value="in_progress">IN PROGRESS</option>
                <option value="resolved">RESOLVED</option>
                <option value="declined">DECLINED</option>
              </select>
            </div>

            <!-- Priority Filter -->
            <div>
              <label class="mono" style="font-size:0.72rem; display:block; margin-bottom:4px; font-weight:800;">PRIORITY:</label>
              <select id="filter-priority" onchange="loadAdminReports()" style="padding:7px 12px; border:2px solid var(--ink); border-radius:8px; font-weight:800; font-family:'DM Mono', monospace; font-size:0.78rem;">
                <option value="all">ALL PRIORITIES</option>
                <option value="critical">CRITICAL</option>
                <option value="high">HIGH</option>
                <option value="medium">MEDIUM</option>
                <option value="low">LOW</option>
              </select>
            </div>

            <!-- Role Filter -->
            <div>
              <label class="mono" style="font-size:0.72rem; display:block; margin-bottom:4px; font-weight:800;">SUBMITTER:</label>
              <select id="filter-role" onchange="loadAdminReports()" style="padding:7px 12px; border:2px solid var(--ink); border-radius:8px; font-weight:800; font-family:'DM Mono', monospace; font-size:0.78rem;">
                <option value="all">ALL USERS</option>
                <option value="court_owner">COURT OWNERS</option>
                <option value="customer">PLAYERS / CUSTOMERS</option>
              </select>
            </div>
          </div>

          <!-- Search Input -->
          <div style="min-width:240px; flex:1; max-width:360px;">
            <label class="mono" style="font-size:0.72rem; display:block; margin-bottom:4px; font-weight:800;">SEARCH:</label>
            <div style="position:relative;">
              <input type="text" id="filter-search" oninput="debounceSearch()" placeholder="Search title, description, user..." style="width:100%; padding:7px 30px 7px 12px; border:2px solid var(--ink); border-radius:8px; font-weight:700; font-family:inherit; font-size:0.82rem;">
              <i class="bi bi-search" style="position:absolute; right:10px; top:50%; transform:translateY(-50%); color:#666;"></i>
            </div>
          </div>
        </div>
      </div>

      <!-- REPORTS LIST CONTAINER -->
      <div id="reports-container">
        <!-- Loaded via JS -->
      </div>
    </div>

    <div id="footer-container"></div>
  </main>

  <!-- ADDRESS & UPDATE REPORT MODAL -->
  <div class="modal-overlay" id="review-modal" style="display:none; align-items:center; justify-content:center;" onclick="if(event.target===this) closeModal('review-modal');">
    <div class="card-streetside modal-card-scrollable" style="width:min(600px, 95vw); max-height:92vh; overflow-y:auto; padding:24px; background:var(--white); box-shadow:6px 6px 0 var(--ink);">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; border-bottom:2px solid var(--ink); padding-bottom:12px;">
        <div style="display:flex; align-items:center; gap:8px;">
          <h3 style="font-size:1.15rem; font-weight:800; text-transform:uppercase; margin:0;" id="modal-title">REVIEW REPORT</h3>
        </div>
        <button type="button" onclick="closeModal('review-modal')" class="button sand" style="padding:2px 8px; font-size:0.85rem; font-weight:900;">✕</button>
      </div>

      <div id="modal-body">
        <!-- Loaded via JS -->
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
    let currentReports = [];
    let activeReport = null;
    let searchTimer = null;

    document.addEventListener('DOMContentLoaded', async () => {
      await NavbarComponent.render('#navbar-container', true);
      SidebarComponent.render('feedback_reports', 'admin');
      FooterComponent.render('#footer-container', true);

      loadAdminReports();
    });

    function debounceSearch() {
      clearTimeout(searchTimer);
      searchTimer = setTimeout(() => {
        loadAdminReports();
      }, 350);
    }

    async function loadAdminReports() {
      const type = document.getElementById('filter-type').value;
      const status = document.getElementById('filter-status').value;
      const priority = document.getElementById('filter-priority').value;
      const role = document.getElementById('filter-role').value;
      const search = document.getElementById('filter-search').value.trim();

      const container = document.getElementById('reports-container');
      container.innerHTML = `
        <div style="padding:40px; text-align:center;">
          <div class="skeleton" style="height:80px; margin-bottom:12px; border-radius:12px;"></div>
          <div class="skeleton" style="height:80px; margin-bottom:12px; border-radius:12px;"></div>
          <div class="skeleton" style="height:80px; border-radius:12px;"></div>
        </div>
      `;

      try {
        const res = await Api.get('/pikvero/api/admin/feedback-reports.php', {
          report_type: type,
          status: status,
          priority: priority,
          role: role,
          search: search
        });

        if (res && res.success && res.data) {
          currentReports = res.data.reports || [];
          renderStats(res.data.stats || {});
          renderReportsList(currentReports);
        } else {
          container.innerHTML = `<div class="card-streetside sand" style="padding:24px; text-align:center;">Failed to load reports.</div>`;
        }
      } catch (err) {
        console.error(err);
        container.innerHTML = `<div class="card-streetside sand" style="padding:24px; text-align:center; color:var(--coral);">Error loading reports feed.</div>`;
      }
    }

    function renderStats(stats) {
      document.getElementById('kpi-total').innerText = stats.total || 0;
      document.getElementById('kpi-bugs').innerText = stats.bugs || 0;
      document.getElementById('kpi-critical-sub').innerText = (stats.critical || 0) + ' critical priority';
      document.getElementById('kpi-features').innerText = stats.features || 0;
      document.getElementById('kpi-pending').innerText = stats.pending || 0;
      document.getElementById('kpi-inprogress').innerText = stats.in_progress || 0;
      document.getElementById('kpi-resolved').innerText = stats.resolved || 0;
    }

    function renderReportsList(reports) {
      const container = document.getElementById('reports-container');

      if (!reports || reports.length === 0) {
        container.innerHTML = `
          <div class="card-streetside sand" style="padding:40px; text-align:center; margin-top:10px;">
            <i class="bi bi-clipboard2-check" style="font-size:2.4rem; color:#888; display:block; margin-bottom:8px;"></i>
            <h3 style="font-size:1.15rem; font-weight:800; text-transform:uppercase; margin:0 0 6px;">No Reports Found</h3>
            <p style="font-size:0.85rem; color:#4a5c56; margin:0;">There are no bug reports or feature requests matching your current filters.</p>
          </div>
        `;
        return;
      }

      const html = reports.map(r => {
        const isBug = r.report_type === 'bug';
        const typeBadge = isBug 
          ? `<span class="badge-streetside coral" style="font-size:0.7rem;"><i class="bi bi-bug-fill"></i> BUG REPORT</span>`
          : `<span class="badge-streetside lime" style="font-size:0.7rem;"><i class="bi bi-lightbulb-fill"></i> FEATURE REQUEST</span>`;

        const priorityBadge = `<span class="badge-streetside badge-priority-${r.priority}" style="font-size:0.7rem;">${r.priority.toUpperCase()}</span>`;
        const statusBadge = `<span class="badge-streetside badge-status-${r.status}" style="font-size:0.72rem;">${r.status.replace('_', ' ').toUpperCase()}</span>`;
        
        const roleLabel = (r.submitter_role === 'court_owner') ? 'Court Owner' : 'Player';
        const roleBadge = (r.submitter_role === 'court_owner') 
          ? `<span class="badge-streetside sky" style="font-size:0.65rem;">OWNER</span>`
          : `<span class="badge-streetside sand" style="font-size:0.65rem;">PLAYER</span>`;

        const formattedDate = new Date(r.created_at).toLocaleString('en-US', {
          month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit', hour12: true
        });

        const hasResponse = !!r.admin_response;

        return `
          <div class="report-card-item">
            <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:10px; margin-bottom:10px;">
              <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                ${typeBadge}
                ${priorityBadge}
                ${statusBadge}
                <span class="badge-streetside" style="background:#f1f5f9; font-size:0.68rem;">CAT: ${r.category.toUpperCase()}</span>
              </div>
              <div class="mono" style="font-size:0.72rem; color:#5a7060;">
                <i class="bi bi-clock-history"></i> ${formattedDate}
              </div>
            </div>

            <div style="margin-bottom:10px;">
              <h3 style="font-size:1.15rem; font-weight:800; text-transform:uppercase; margin:0 0 6px;">${escapeHtml(r.title)}</h3>
              <p style="font-size:0.88rem; color:#3b4e48; margin:0; line-height:1.45; white-space:pre-line;">${escapeHtml(r.description)}</p>
            </div>

            ${hasResponse ? `
              <div style="background:#f0fdf4; border:1.5px solid #16a34a; border-radius:8px; padding:10px 14px; margin-bottom:12px; font-size:0.82rem;">
                <strong style="color:#15803d; display:block; margin-bottom:2px;">
                  <i class="bi bi-chat-quote-fill"></i> Admin Response Remarks:
                </strong>
                <div style="color:#14532d;">${escapeHtml(r.admin_response)}</div>
              </div>
            ` : ''}

            <div style="display:flex; justify-content:space-between; align-items:center; border-top:1px dashed var(--line); padding-top:10px; flex-wrap:wrap; gap:10px;">
              <div style="display:flex; align-items:center; gap:8px; font-size:0.8rem;">
                <div class="brand-mark" style="width:26px; height:26px; font-size:0.75rem; background:var(--sand);">
                  ${r.submitter_name.charAt(0).toUpperCase()}
                </div>
                <span><strong>${escapeHtml(r.submitter_name)}</strong> ${roleBadge}</span>
                <span style="color:#666;">&bull; ${escapeHtml(r.submitter_email)}</span>
                ${r.organization_name ? `<span style="color:#666;">(${escapeHtml(r.organization_name)})</span>` : ''}
              </div>

              <div>
                <button type="button" onclick="openReviewModal(${r.id})" class="button lime" style="padding:6px 14px; font-size:0.78rem;">
                  <i class="bi bi-pencil-square"></i> Review &amp; Update Status
                </button>
              </div>
            </div>
          </div>
        `;
      }).join('');

      container.innerHTML = html;
    }

    function openReviewModal(reportId) {
      activeReport = currentReports.find(r => r.id === reportId);
      if (!activeReport) return;

      const modalBody = document.getElementById('modal-body');
      const isBug = activeReport.report_type === 'bug';

      modalBody.innerHTML = `
        <div style="background:var(--sand); border:2px solid var(--ink); border-radius:10px; padding:14px; margin-bottom:16px;">
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
            <span class="badge-streetside ${isBug ? 'coral' : 'lime'}" style="font-size:0.72rem;">
              ${isBug ? '🐛 BUG REPORT' : '💡 FEATURE REQUEST'}
            </span>
            <span class="mono" style="font-size:0.75rem;">ID #${activeReport.id}</span>
          </div>
          <h4 style="font-size:1.15rem; font-weight:800; text-transform:uppercase; margin:4px 0 4px;">${escapeHtml(activeReport.title)}</h4>
          <p style="font-size:0.85rem; color:#3b4e48; margin:0 0 10px; line-height:1.45; white-space:pre-line;">${escapeHtml(activeReport.description)}</p>
          <div style="font-size:0.75rem; color:#4a5c56; border-top:1px dashed var(--ink); padding-top:6px;">
            <strong>Submitted By:</strong> ${escapeHtml(activeReport.submitter_name)} (${activeReport.submitter_role}) &bull; ${escapeHtml(activeReport.submitter_email)}
          </div>
        </div>

        <form onsubmit="saveReportChanges(event)">
          <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:14px;">
            <div>
              <label class="mono" style="display:block; margin-bottom:4px; font-size:0.75rem; font-weight:800;">STATUS *</label>
              <select id="modal-status" style="width:100%; padding:9px 12px; border:2px solid var(--ink); border-radius:8px; font-weight:800; font-family:'DM Mono', monospace; font-size:0.82rem;">
                <option value="pending" ${activeReport.status === 'pending' ? 'selected' : ''}>Pending</option>
                <option value="under_review" ${activeReport.status === 'under_review' ? 'selected' : ''}>Under Review</option>
                <option value="in_progress" ${activeReport.status === 'in_progress' ? 'selected' : ''}>In Progress</option>
                <option value="resolved" ${activeReport.status === 'resolved' ? 'selected' : ''}>Resolved / Completed</option>
                <option value="declined" ${activeReport.status === 'declined' ? 'selected' : ''}>Declined</option>
              </select>
            </div>

            <div>
              <label class="mono" style="display:block; margin-bottom:4px; font-size:0.75rem; font-weight:800;">PRIORITY *</label>
              <select id="modal-priority" style="width:100%; padding:9px 12px; border:2px solid var(--ink); border-radius:8px; font-weight:800; font-family:'DM Mono', monospace; font-size:0.82rem;">
                <option value="low" ${activeReport.priority === 'low' ? 'selected' : ''}>Low</option>
                <option value="medium" ${activeReport.priority === 'medium' ? 'selected' : ''}>Medium</option>
                <option value="high" ${activeReport.priority === 'high' ? 'selected' : ''}>High</option>
                <option value="critical" ${activeReport.priority === 'critical' ? 'selected' : ''}>Critical</option>
              </select>
            </div>
          </div>

          <div style="margin-bottom:18px;">
            <label class="mono" style="display:block; margin-bottom:4px; font-size:0.75rem; font-weight:800;">
              ADMIN RESPONSE / RESOLUTION REMARKS
              <span style="font-weight:400; color:#5a7060;">(Visible to the submitter)</span>
            </label>
            <textarea id="modal-response" rows="4" placeholder="Explain the resolution or status update to the submitter..." style="width:100%; padding:10px 12px; border:2px solid var(--ink); border-radius:8px; font-weight:600; font-family:inherit; font-size:0.85rem; resize:vertical;">${escapeHtml(activeReport.admin_response || '')}</textarea>
          </div>

          <div style="display:flex; justify-content:flex-end; gap:10px;">
            <button type="button" onclick="closeModal('review-modal')" class="button sand" style="padding:9px 16px; font-size:0.82rem;">Cancel</button>
            <button type="submit" id="btn-save-report" class="button lime" style="padding:9px 20px; font-size:0.82rem;">
              <i class="bi bi-check2-circle"></i> Save &amp; Update Status
            </button>
          </div>
        </form>
      `;

      openModal('review-modal');
    }

    async function saveReportChanges(e) {
      e.preventDefault();
      if (!activeReport) return;

      const btn = document.getElementById('btn-save-report');
      btn.disabled = true;
      btn.innerHTML = `<i class="bi bi-hourglass-split"></i> Saving...`;

      const status = document.getElementById('modal-status').value;
      const priority = document.getElementById('modal-priority').value;
      const adminResponse = document.getElementById('modal-response').value.trim();

      try {
        const res = await Api.post('/pikvero/api/admin/feedback-reports.php', {
          report_id: activeReport.id,
          status: status,
          priority: priority,
          admin_response: adminResponse
        });

        if (res && res.success) {
          Toast.success('Status Updated', 'Report status and response remarks have been saved.');
          closeModal('review-modal');
          loadAdminReports();
        } else {
          Toast.error('Update Failed', (res && res.message) || 'Failed to update report.');
        }
      } catch (err) {
        console.error(err);
        Toast.error('Network Error', 'An error occurred while saving.');
      } finally {
        btn.disabled = false;
        btn.innerHTML = `<i class="bi bi-check2-circle"></i> Save &amp; Update Status`;
      }
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

    function escapeHtml(str) {
      if (!str) return '';
      return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    }
  </script>
</body>
</html>
