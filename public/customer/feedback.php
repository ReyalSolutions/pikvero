<?php
require_once __DIR__ . '/../../app/bootstrap.php';
use App\Core\Auth\Auth;

if (!Auth::check()) {
    header('Location: /pikvero/public/login.php');
    exit;
}

$favLogo = Auth::getLogoUrl();
$user = Auth::user();
$role = Auth::role();
$isOwner = ($role === 'court_owner');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Pikvero — Report a Bug or Request a Feature</title>
  <link rel="icon" type="image/png" href="<?= htmlspecialchars($favLogo) ?>?v=<?= time() ?>">
  <link rel="shortcut icon" type="image/png" href="<?= htmlspecialchars($favLogo) ?>?v=<?= time() ?>">
  <link rel="apple-touch-icon" href="<?= htmlspecialchars($favLogo) ?>?v=<?= time() ?>">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="/pikvero/assets/css/streetside-theme.css?v=<?= filemtime(__DIR__ . '/../../assets/css/streetside-theme.css') ?>">
  <link rel="stylesheet" href="/pikvero/assets/css/toast.css">
  <style>
    .fb-tab-btn {
      padding: 8px 18px;
      font-size: 0.85rem;
      font-weight: 800;
      cursor: pointer;
    }

    .badge-priority-critical { background:#fee2e2; color:#b91c1c; border:1.5px solid #b91c1c; font-weight:800; }
    .badge-priority-high     { background:#ffedd5; color:#c2410c; border:1.5px solid #c2410c; font-weight:800; }
    .badge-priority-medium   { background:#e0f2fe; color:#0369a1; border:1.5px solid #0369a1; font-weight:800; }
    .badge-priority-low      { background:#f1f5f9; color:#475569; border:1.5px solid #475569; font-weight:800; }

    .badge-status-pending      { background:#fef9c3; color:#a16207; border:1.5px solid #a16207; font-weight:800; }
    .badge-status-under_review { background:#e0e7ff; color:#4338ca; border:1.5px solid #4338ca; font-weight:800; }
    .badge-status-in_progress  { background:#e0f2fe; color:#0284c7; border:1.5px solid #0284c7; font-weight:800; }
    .badge-status-resolved     { background:#dcfce7; color:#15803d; border:1.5px solid #15803d; font-weight:800; }
    .badge-status-declined     { background:#fee2e2; color:#be123c; border:1.5px solid #be123c; font-weight:800; }

    .user-report-card {
      border: 2px solid var(--ink);
      border-radius: 12px;
      background: var(--white);
      padding: 18px;
      margin-bottom: 14px;
      box-shadow: 3px 3px 0 var(--ink);
    }
  </style>
<link rel="manifest" href="/pikvero/manifest.webmanifest">
<meta name="theme-color" content="#003d2d">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="Pikvero">
<link rel="apple-touch-icon" href="/pikvero/assets/images/pwa/icon-180.png">
<script defer src="/pikvero/assets/js/components/pwa.js?v=20261010"></script>
</head>
<body style="min-height:100vh; display:flex; flex-direction:column;">

  <?php if ($isOwner): ?>
    <aside id="sidebar-container"></aside>
  <?php endif; ?>
  <div id="navbar-container"></div>

  <main class="<?= $isOwner ? 'portal-main' : '' ?>" style="<?= $isOwner ? '' : 'flex:1; width:100%; max-width:1100px; margin:0 auto; padding:110px max(4vw, 20px) 40px; box-sizing:border-box;' ?>">
    <div>
      <!-- TOP HEADER -->
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:12px;">
        <div>
          <div class="eyebrow"><i class="bi bi-chat-right-dots-fill"></i> SUPPORT &amp; FEEDBACK</div>
          <h1 style="font-size: clamp(1.6rem, 4vw, 2.2rem); font-weight:800; text-transform:uppercase; margin:4px 0 0;">REPORTS &amp; FEATURE REQUESTS</h1>
        </div>
        <div style="display:flex; gap:10px;">
          <button type="button" onclick="switchTab('new')" id="btn-create-top" class="button lime" style="padding:8px 16px; font-size:0.82rem;">
            <i class="bi bi-plus-circle-fill"></i> Submit New Report
          </button>
        </div>
      </div>

      <!-- TABS -->
      <div style="display:flex; gap:10px; margin-bottom:20px; border-bottom:2px solid var(--ink); padding-bottom:10px; flex-wrap:wrap;">
        <button type="button" onclick="switchTab('list')" id="tab-btn-list" class="button lime fb-tab-btn">
          <i class="bi bi-card-checklist"></i> My Submitted Reports (<span id="user-report-count">0</span>)
        </button>
        <button type="button" onclick="switchTab('new')" id="tab-btn-new" class="button sand fb-tab-btn">
          <i class="bi bi-pencil-square"></i> Submit Bug or Feature Request
        </button>
      </div>

      <!-- TAB 1: USER'S SUBMITTED REPORTS -->
      <div id="tab-list-panel" style="display:block;">
        <div id="user-reports-container">
          <!-- Loaded via JS -->
        </div>
      </div>

      <!-- TAB 2: SUBMIT REPORT FORM -->
      <div id="tab-new-panel" style="display:none;">
        <div class="card-streetside" style="max-width:760px; padding:24px; background:var(--white); margin:0 auto;">
          <div style="margin-bottom:18px; border-bottom:2px solid var(--ink); padding-bottom:12px;">
            <h3 style="font-size:1.15rem; font-weight:800; text-transform:uppercase; margin:0 0 4px;">SUBMIT A REPORT OR REQUEST</h3>
            <p style="font-size:0.82rem; color:#4a5c56; margin:0;">
              Help us improve Pikvero. Found a bug or have an idea for a new feature? Tell us below and track its status in real-time.
            </p>
          </div>

          <form onsubmit="submitUserReport(event)">
            <!-- Type Selector Radios -->
            <div style="margin-bottom:16px;">
              <label class="mono" style="display:block; margin-bottom:6px; font-size:0.75rem; font-weight:800;">SELECT REPORT TYPE *</label>
              <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                <label id="lbl-type-bug" class="card-streetside" style="cursor:pointer; padding:12px 14px; background:var(--sand); display:flex; align-items:center; gap:10px; margin:0; border:2px solid var(--ink);">
                  <input type="radio" name="report_type" value="bug" checked onchange="toggleTypeUI()" style="transform:scale(1.2);">
                  <div>
                    <strong style="font-size:0.88rem; display:block;"><i class="bi bi-bug-fill" style="color:var(--coral);"></i> Bug Report</strong>
                    <span style="font-size:0.72rem; color:#4a5c56;">Something is broken or not working as expected</span>
                  </div>
                </label>

                <label id="lbl-type-feature" class="card-streetside" style="cursor:pointer; padding:12px 14px; background:var(--white); display:flex; align-items:center; gap:10px; margin:0; border:2px solid #ccc;">
                  <input type="radio" name="report_type" value="feature_request" onchange="toggleTypeUI()" style="transform:scale(1.2);">
                  <div>
                    <strong style="font-size:0.88rem; display:block;"><i class="bi bi-lightbulb-fill" style="color:#059669;"></i> Feature Request</strong>
                    <span style="font-size:0.72rem; color:#4a5c56;">Idea, tool, or feature you'd like to see added</span>
                  </div>
                </label>
              </div>
            </div>

            <!-- Title -->
            <div style="margin-bottom:14px;">
              <label class="mono" for="report-title" style="display:block; margin-bottom:4px; font-size:0.75rem; font-weight:800;">TITLE / SUMMARY *</label>
              <input type="text" id="report-title" required placeholder="Short summary of the bug or requested feature" style="width:100%; padding:9px 12px; border:2px solid var(--ink); border-radius:8px; font-weight:700; font-family:inherit; font-size:0.85rem;">
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px; margin-bottom:14px;">
              <!-- Category -->
              <div>
                <label class="mono" for="report-category" style="display:block; margin-bottom:4px; font-size:0.75rem; font-weight:800;">CATEGORY</label>
                <select id="report-category" style="width:100%; padding:9px 12px; border:2px solid var(--ink); border-radius:8px; font-weight:800; font-family:'DM Mono', monospace; font-size:0.82rem;">
                  <option value="general">General / Portal</option>
                  <option value="booking">Court Booking &amp; Time Slots</option>
                  <option value="open_play">Open Play Sessions</option>
                  <option value="payments">Payments &amp; PayMongo Checkout</option>
                  <option value="mobile_ui">Mobile UI &amp; Responsiveness</option>
                  <option value="facility_management">Facility &amp; Court Setup</option>
                  <option value="account">Account &amp; Login</option>
                </select>
              </div>

              <!-- Priority / Severity -->
              <div>
                <label class="mono" for="report-priority" style="display:block; margin-bottom:4px; font-size:0.75rem; font-weight:800;">SEVERITY / PRIORITY</label>
                <select id="report-priority" style="width:100%; padding:9px 12px; border:2px solid var(--ink); border-radius:8px; font-weight:800; font-family:'DM Mono', monospace; font-size:0.82rem;">
                  <option value="low">Low (Nice to have / Minor glitch)</option>
                  <option value="medium" selected>Medium (Standard priority)</option>
                  <option value="high">High (Impacting reservation flow)</option>
                  <option value="critical">Critical (Blocking usage / System error)</option>
                </select>
              </div>
            </div>

            <!-- Description -->
            <div style="margin-bottom:18px;">
              <label class="mono" for="report-description" style="display:block; margin-bottom:4px; font-size:0.75rem; font-weight:800;">DETAILED DESCRIPTION *</label>
              <textarea id="report-description" rows="5" required placeholder="Please provide steps to reproduce the bug, error messages, or details on how you want the feature to work..." style="width:100%; padding:10px 12px; border:2px solid var(--ink); border-radius:8px; font-weight:600; font-family:inherit; font-size:0.85rem; resize:vertical;"></textarea>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px;">
              <button type="button" onclick="switchTab('list')" class="button sand" style="padding:9px 16px; font-size:0.82rem;">Cancel</button>
              <button type="submit" id="btn-submit-report" class="button lime" style="padding:9px 22px; font-size:0.82rem;">
                <i class="bi bi-send-fill"></i> Submit Report
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <div id="footer-container" style="margin-top:30px;"></div>
  </main>

  <script src="/pikvero/assets/js/core/toast.js"></script>
  <script src="/pikvero/assets/js/core/ajax.js"></script>
  <script src="/pikvero/assets/js/core/auth.js"></script>
  <script src="/pikvero/assets/js/components/navbar.js"></script>
  <?php if ($isOwner): ?>
    <script src="/pikvero/assets/js/components/sidebar.js"></script>
  <?php endif; ?>
  <script src="/pikvero/assets/js/components/footer.js"></script>
  <script>
    let myReports = [];
    const isOwner = <?= $isOwner ? 'true' : 'false' ?>;

    document.addEventListener('DOMContentLoaded', async () => {
      await NavbarComponent.render('#navbar-container', isOwner);
      if (isOwner && typeof SidebarComponent !== 'undefined') {
        SidebarComponent.render('feedback', 'owner');
      }
      FooterComponent.render('#footer-container', isOwner);

      loadMyReports();
    });

    function switchTab(tab) {
      if (tab === 'list') {
        document.getElementById('tab-list-panel').style.display = 'block';
        document.getElementById('tab-new-panel').style.display = 'none';
        document.getElementById('tab-btn-list').className = 'button lime fb-tab-btn';
        document.getElementById('tab-btn-new').className = 'button sand fb-tab-btn';
        loadMyReports();
      } else {
        document.getElementById('tab-list-panel').style.display = 'none';
        document.getElementById('tab-new-panel').style.display = 'block';
        document.getElementById('tab-btn-list').className = 'button sand fb-tab-btn';
        document.getElementById('tab-btn-new').className = 'button lime fb-tab-btn';
      }
    }

    function toggleTypeUI() {
      const isBug = document.querySelector('input[name="report_type"]:checked').value === 'bug';
      const lblBug = document.getElementById('lbl-type-bug');
      const lblFeature = document.getElementById('lbl-type-feature');

      if (isBug) {
        lblBug.style.background = 'var(--sand)';
        lblBug.style.border = '2px solid var(--ink)';
        lblFeature.style.background = 'var(--white)';
        lblFeature.style.border = '2px solid #ccc';
      } else {
        lblFeature.style.background = 'var(--sand)';
        lblFeature.style.border = '2px solid var(--ink)';
        lblBug.style.background = 'var(--white)';
        lblBug.style.border = '2px solid #ccc';
      }
    }

    async function loadMyReports() {
      const container = document.getElementById('user-reports-container');
      container.innerHTML = `
        <div style="padding:30px; text-align:center;">
          <div class="skeleton" style="height:70px; margin-bottom:10px; border-radius:10px;"></div>
          <div class="skeleton" style="height:70px; border-radius:10px;"></div>
        </div>
      `;

      try {
        const res = await Api.get('/pikvero/api/feedback-reports.php');
        if (res && res.success && Array.isArray(res.data)) {
          myReports = res.data;
          document.getElementById('user-report-count').innerText = myReports.length;
          renderMyReports(myReports);
        } else {
          container.innerHTML = `<div class="card-streetside sand" style="padding:20px; text-align:center;">Failed to load your reports.</div>`;
        }
      } catch (err) {
        console.error(err);
        container.innerHTML = `<div class="card-streetside sand" style="padding:20px; text-align:center; color:var(--coral);">Error loading reports.</div>`;
      }
    }

    function renderMyReports(reports) {
      const container = document.getElementById('user-reports-container');

      if (!reports || reports.length === 0) {
        container.innerHTML = `
          <div class="card-streetside sand" style="padding:40px; text-align:center; max-width:600px; margin:20px auto;">
            <i class="bi bi-inbox" style="font-size:2.5rem; color:#888; display:block; margin-bottom:8px;"></i>
            <h3 style="font-size:1.15rem; font-weight:800; text-transform:uppercase; margin:0 0 6px;">No Reports Submitted Yet</h3>
            <p style="font-size:0.85rem; color:#4a5c56; margin-bottom:16px;">
              You haven't submitted any bug reports or feature requests. Have an issue or feedback for us?
            </p>
            <button type="button" onclick="switchTab('new')" class="button lime" style="padding:8px 18px; font-size:0.82rem;">
              <i class="bi bi-plus-circle-fill"></i> Submit a Report Now
            </button>
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
        const statusBadge = `<span class="badge-streetside badge-status-${r.status}" style="font-size:0.72rem;">STATUS: ${r.status.replace('_', ' ').toUpperCase()}</span>`;

        const formattedDate = new Date(r.created_at).toLocaleString('en-US', {
          month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit', hour12: true
        });

        const hasResponse = !!r.admin_response;

        return `
          <div class="user-report-card">
            <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:10px; margin-bottom:8px;">
              <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                ${typeBadge}
                ${statusBadge}
                ${priorityBadge}
                <span class="badge-streetside" style="background:#f1f5f9; font-size:0.68rem;">CAT: ${r.category.toUpperCase()}</span>
              </div>
              <div class="mono" style="font-size:0.72rem; color:#5a7060;">
                <i class="bi bi-clock-history"></i> Submitted ${formattedDate}
              </div>
            </div>

            <h3 style="font-size:1.15rem; font-weight:800; text-transform:uppercase; margin:4px 0 6px;">${escapeHtml(r.title)}</h3>
            <p style="font-size:0.88rem; color:#3b4e48; margin:0 0 12px; line-height:1.45; white-space:pre-line;">${escapeHtml(r.description)}</p>

            ${hasResponse ? `
              <div style="background:#f0fdf4; border:2px solid #16a34a; border-radius:10px; padding:12px 16px; margin-top:10px;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
                  <strong style="color:#15803d; font-size:0.85rem;">
                    <i class="bi bi-chat-quote-fill"></i> Admin Response &amp; Status Update:
                  </strong>
                  ${r.resolver_name ? `<span class="mono" style="font-size:0.7rem; color:#166534;">By: ${escapeHtml(r.resolver_name)}</span>` : ''}
                </div>
                <div style="color:#14532d; font-size:0.85rem; font-weight:600; line-height:1.4;">${escapeHtml(r.admin_response)}</div>
              </div>
            ` : `
              <div style="font-size:0.75rem; color:#888; font-style:italic; border-top:1px dashed var(--line); padding-top:8px;">
                <i class="bi bi-hourglass-split"></i> Awaiting administrative review. You will see status updates and response remarks here once reviewed.
              </div>
            `}
          </div>
        `;
      }).join('');

      container.innerHTML = html;
    }

    async function submitUserReport(e) {
      e.preventDefault();

      const btn = document.getElementById('btn-submit-report');
      btn.disabled = true;
      btn.innerHTML = `<i class="bi bi-hourglass-split"></i> Submitting...`;

      const type = document.querySelector('input[name="report_type"]:checked').value;
      const title = document.getElementById('report-title').value.trim();
      const category = document.getElementById('report-category').value;
      const priority = document.getElementById('report-priority').value;
      const description = document.getElementById('report-description').value.trim();

      try {
        const res = await Api.post('/pikvero/api/feedback-reports.php', {
          report_type: type,
          title: title,
          category: category,
          priority: priority,
          description: description
        });

        if (res && res.success) {
          Toast.success('Report Submitted!', res.message || 'Your report has been submitted.');
          document.getElementById('report-title').value = '';
          document.getElementById('report-description').value = '';
          switchTab('list');
        } else {
          Toast.error('Submission Failed', (res && res.message) || 'Failed to submit report.');
        }
      } catch (err) {
        console.error(err);
        Toast.error('Network Error', 'An error occurred while submitting.');
      } finally {
        btn.disabled = false;
        btn.innerHTML = `<i class="bi bi-send-fill"></i> Submit Report`;
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
