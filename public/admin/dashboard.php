<?php
$pageTitle  = 'Pikvero — Management Dashboard & Analytics';
$headExtras = ['chartjs'];
require_once __DIR__ . '/../../includes/head.php';
?>
<body>

  <!-- Full 100vh Fixed Sidebar Container -->
  <aside id="sidebar-container"></aside>

  <!-- Header offset from Sidebar -->
  <header id="navbar-container"></header>

  <!-- Main Content Area -->
  <main class="portal-main">
    <div>
      <!-- Header & Date Filter Controls Bar -->
      <div class="card-streetside" style="padding:16px 20px; margin-bottom:20px; background:#fff;">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px;">
          <div>
            <div id="dash-eyebrow" class="eyebrow">PORTAL DASHBOARD</div>
            <h1 id="dash-title" style="font-size: clamp(1.4rem, 3.5vw, 2rem); font-weight:800; text-transform:uppercase; margin:2px 0 0;">BUSINESS OVERVIEW</h1>
          </div>

          <!-- Filter & Action Controls -->
          <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
            <!-- Date Filter Box -->
            <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap; background:#f8faf9; border:2px solid var(--line); border-radius:12px; padding:6px 10px;">
              <div class="preset-btn-group" style="display:flex; gap:4px;">
                <button type="button" class="button sand btn-preset" onclick="setPresetRange('today')" style="padding:6px 10px; font-size:0.72rem;">Today</button>
                <button type="button" class="button sand btn-preset" onclick="setPresetRange('week')" style="padding:6px 10px; font-size:0.72rem;">7 Days</button>
                <button type="button" id="btn-preset-month" class="button lime btn-preset active" onclick="setPresetRange('month')" style="padding:6px 10px; font-size:0.72rem;">This Month</button>
                <button type="button" class="button sand btn-preset" onclick="setPresetRange('year')" style="padding:6px 10px; font-size:0.72rem;">Year</button>
              </div>

              <div style="display:flex; align-items:center; gap:4px; font-family:'DM Mono', monospace; font-size:0.75rem;">
                <input type="date" id="filter-start-date" class="form-input" style="padding:4px 8px; font-size:0.75rem; width:125px; border-radius:8px;">
                <span>to</span>
                <input type="date" id="filter-end-date" class="form-input" style="padding:4px 8px; font-size:0.75rem; width:125px; border-radius:8px;">
                <button type="button" onclick="applyDashboardDateFilter()" class="button lime" style="padding:5px 12px; font-size:0.75rem;">
                  <i class="bi bi-filter"></i> Apply
                </button>
              </div>
            </div>

            <!-- Action Buttons -->
            <div id="owner-action-btns" style="display:none; gap:8px; flex-wrap:wrap;">
              <a href="/pikvero/public/admin/courts.php" class="button lime" style="padding:8px 12px; font-size:0.78rem;">
                <i class="bi bi-plus-lg"></i> Add Court
              </a>
              <a href="/pikvero/public/admin/facilities.php" class="button sand" style="padding:8px 12px; font-size:0.78rem;">
                <i class="bi bi-building-add"></i> Add Facility
              </a>
            </div>
          </div>
        </div>
      </div>

      <!-- Stat Cards Grid (6 Stats) -->
      <div id="dash-stat-grid" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap:12px; margin-bottom:24px;">
        <!-- Dynamically rendered -->
      </div>

      <!-- Main Analytics Charts Grid -->
      <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap:16px; margin-bottom:24px;">
        <!-- Chart 1: Revenue Trend Line Chart -->
        <div class="card-streetside" style="padding:20px; background:#fff; grid-column: span 2;">
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px; flex-wrap:wrap; gap:8px;">
            <div>
              <h3 class="mono" style="margin:0; font-size:0.9rem; color:var(--green);">REVENUE BREAKDOWN TREND</h3>
              <div style="font-size:0.72rem; color:#5a7060;">Daily comparison of Court Bookings, Open Play, and POS Sales</div>
            </div>
            <span class="badge-streetside lime" style="font-size:0.65rem;">COURT vs OPEN PLAY vs SHOP</span>
          </div>
          <div style="position:relative; height:280px; width:100%;">
            <canvas id="chart-revenue-trend"></canvas>
          </div>
        </div>

        <!-- Chart 2: Revenue Distribution Doughnut Chart -->
        <div class="card-streetside" style="padding:20px; background:#fff;">
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px; flex-wrap:wrap; gap:8px;">
            <div>
              <h3 class="mono" style="margin:0; font-size:0.9rem; color:var(--green);">REVENUE SOURCES</h3>
              <div style="font-size:0.72rem; color:#5a7060;">Share percentage across business units</div>
            </div>
            <span class="badge-streetside sand" style="font-size:0.65rem;">SHARE DISTRIBUTION</span>
          </div>
          <div style="position:relative; height:280px; width:100%; display:flex; align-items:center; justify-content:center;">
            <canvas id="chart-revenue-doughnut"></canvas>
          </div>
        </div>
      </div>

      <!-- Secondary Charts Grid -->
      <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap:16px; margin-bottom:24px;">
        <!-- Chart 3: Peak Reservation Hours Bar Chart -->
        <div class="card-streetside" style="padding:20px; background:#fff;">
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px; flex-wrap:wrap; gap:8px;">
            <div>
              <h3 class="mono" style="margin:0; font-size:0.9rem; color:var(--green);">PEAK RESERVATION HOURS</h3>
              <div style="font-size:0.72rem; color:#5a7060;">Frequency of court bookings by start time</div>
            </div>
            <span class="badge-streetside sky" style="font-size:0.65rem;">HOURLY DEMAND</span>
          </div>
          <div style="position:relative; height:240px; width:100%;">
            <canvas id="chart-peak-hours"></canvas>
          </div>
        </div>

        <!-- Chart 4: Court Utilization Performance -->
        <div class="card-streetside" style="padding:20px; background:#fff;">
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px; flex-wrap:wrap; gap:8px;">
            <div>
              <h3 class="mono" style="margin:0; font-size:0.9rem; color:var(--green);">COURT PERFORMANCE RANKING</h3>
              <div style="font-size:0.72rem; color:#5a7060;">Earnings generated per court</div>
            </div>
            <span class="badge-streetside lime" style="font-size:0.65rem;">EARNINGS LEADERBOARD</span>
          </div>
          <div style="position:relative; height:240px; width:100%;">
            <canvas id="chart-court-performance"></canvas>
          </div>
        </div>
      </div>

      <!-- Main Content Container (Recent Activity Table) -->
      <div id="dash-main-container" class="card-streetside" style="padding:20px; background:#fff;">
        <!-- Dynamically loaded via JS -->
      </div>
    </div>

    <!-- Footer -->
    <footer id="footer-container"></footer>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
  <?php require_once __DIR__ . '/../../includes/scripts.php'; ?>
  <script>
    let chartTrend = null;
    let chartDoughnut = null;
    let chartPeak = null;
    let chartCourts = null;

    let currentRole = '';
    let currentUserPerms = [];

    document.addEventListener('DOMContentLoaded', async () => {
      await NavbarComponent.render('#navbar-container', true);
      FooterComponent.render('#footer-container', true);

      const userCtx = await AuthHelper.checkSession();
      if (!userCtx || !userCtx.user) {
        window.location.href = '/pikvero/public/login.php';
        return;
      }

      currentRole = userCtx.role || (userCtx.user ? userCtx.user.role_name : '');
      currentUserPerms = userCtx.permissions || [];
      const isSuperAdmin = (currentRole === 'super_admin');

      const canViewDashboard = isSuperAdmin || 
                               currentUserPerms.includes('organization.view') || currentUserPerms.includes('facility.view') || 
                               currentUserPerms.includes('court.view') || currentUserPerms.includes('booking.view') || 
                               currentUserPerms.includes('bookings.view') || currentUserPerms.includes('reports.view') || 
                               currentUserPerms.includes('report.view') || currentUserPerms.includes('system.manage') ||
                               currentUserPerms.includes('users.view');

      if (!canViewDashboard) {
        window.location.href = '/pikvero/public/403.php?permission=reports.view';
        return;
      }

      SidebarComponent.render('dashboard', (currentRole === 'court_owner' || currentRole === 'facility_manager' || currentRole === 'receptionist') ? 'owner' : 'admin');

      // Initialize default date range: 1st of current month to Today
      const now = new Date();
      const firstDay = new Date(now.getFullYear(), now.getMonth(), 1).toISOString().split('T')[0];
      const todayStr = now.toISOString().split('T')[0];

      document.getElementById('filter-start-date').value = firstDay;
      document.getElementById('filter-end-date').value = todayStr;

      fetchAndRenderDashboard();
    });

    function setPresetRange(type) {
      const now = new Date();
      const todayStr = now.toISOString().split('T')[0];
      let startStr = todayStr;

      if (type === 'today') {
        startStr = todayStr;
      } else if (type === 'week') {
        const weekAgo = new Date(now);
        weekAgo.setDate(now.getDate() - 6);
        startStr = weekAgo.toISOString().split('T')[0];
      } else if (type === 'month') {
        startStr = new Date(now.getFullYear(), now.getMonth(), 1).toISOString().split('T')[0];
      } else if (type === 'year') {
        startStr = new Date(now.getFullYear(), 0, 1).toISOString().split('T')[0];
      }

      document.getElementById('filter-start-date').value = startStr;
      document.getElementById('filter-end-date').value = todayStr;

      document.querySelectorAll('.btn-preset').forEach(btn => {
        btn.classList.remove('active', 'lime');
        btn.classList.add('sand');
      });
      event.target.classList.remove('sand');
      event.target.classList.add('active', 'lime');

      fetchAndRenderDashboard();
    }

    function applyDashboardDateFilter() {
      fetchAndRenderDashboard();
    }

    async function fetchAndRenderDashboard() {
      const startDate = document.getElementById('filter-start-date').value;
      const endDate = document.getElementById('filter-end-date').value;

      if (currentRole === 'super_admin' || currentRole === 'platform_admin') {
        await loadAdminDashboardData(startDate, endDate);
      } else {
        await loadOwnerDashboardData(startDate, endDate);
      }
    }

    async function loadOwnerDashboardData(startDate, endDate) {
      document.getElementById('dash-eyebrow').innerText = 'COURT OWNER SAAS PORTAL';
      document.getElementById('dash-title').innerText = 'BUSINESS OVERVIEW';

      const isSuperAdmin = (currentRole === 'super_admin');
      const canAddCourt = isSuperAdmin || currentUserPerms.includes('court.create') || currentUserPerms.includes('courts.manage');
      const canAddFacility = isSuperAdmin || currentUserPerms.includes('facility.create') || currentUserPerms.includes('facilities.manage');
      const canViewBookings = isSuperAdmin || currentUserPerms.includes('booking.view') || currentUserPerms.includes('bookings.view') || currentUserPerms.includes('bookings.manage');

      const actionBtnsContainer = document.getElementById('owner-action-btns');
      actionBtnsContainer.innerHTML = `
        ${canAddCourt ? `<a href="/pikvero/public/admin/courts.php" class="button lime" style="padding:8px 12px; font-size:0.78rem;"><i class="bi bi-plus-lg"></i> Add Court</a>` : ''}
        ${canAddFacility ? `<a href="/pikvero/public/admin/facilities.php" class="button sand" style="padding:8px 12px; font-size:0.78rem;"><i class="bi bi-building-add"></i> Add Facility</a>` : ''}
      `;
      if (canAddCourt || canAddFacility) actionBtnsContainer.style.display = 'flex';

      try {
        const url = `/pikvero/api/owner/dashboard.php?start_date=${encodeURIComponent(startDate)}&end_date=${encodeURIComponent(endDate)}`;
        const res = await Api.get(url);

        if (res.success) {
          const a = res.data.analytics || {};
          const s = a.summary || {};

          // Render Stat Cards (6 cards)
          renderStatCards({
            total_revenue: s.total_revenue || 0,
            court_revenue: s.court_revenue || 0,
            court_count: s.court_bookings_count || 0,
            open_play_revenue: s.open_play_revenue || 0,
            open_play_players: s.open_play_players_count || 0,
            product_revenue: s.product_revenue || 0,
            products_sold: s.products_sold_count || 0,
            total_reservations: s.total_reservations || 0,
            total_courts: res.data.total_courts || 0
          });

          // Render Charts
          renderCharts(a);

          // Render Main Container (Recent Reservations Table)
          renderRecentReservationsTable(res.data.recent_bookings || [], canViewBookings);
        }
      } catch (e) {
        console.error('Failed to load owner dashboard:', e);
      }
    }

    async function loadAdminDashboardData(startDate, endDate) {
      document.getElementById('dash-eyebrow').innerText = 'SUPER ADMIN CONTROL CENTER';
      document.getElementById('dash-title').innerText = 'SYSTEM ANALYTICS';

      try {
        const url = `/pikvero/api/admin/dashboard.php?start_date=${encodeURIComponent(startDate)}&end_date=${encodeURIComponent(endDate)}`;
        const res = await Api.get(url);

        if (res.success) {
          const a = res.data.analytics || {};
          const s = a.summary || {};

          renderStatCards({
            total_revenue: s.total_revenue || 0,
            court_revenue: s.court_revenue || 0,
            court_count: s.court_bookings_count || 0,
            open_play_revenue: s.open_play_revenue || 0,
            open_play_players: s.open_play_players_count || 0,
            product_revenue: s.product_revenue || 0,
            products_sold: s.products_sold_count || 0,
            total_reservations: res.data.total_bookings || 0,
            total_users: res.data.total_users || 0
          });

          renderCharts(a);
          renderAdminUsersTable(res.data.recent_users || []);
        }
      } catch (e) {
        console.error('Failed to load admin dashboard:', e);
      }
    }

    function renderStatCards(data) {
      const statGrid = document.getElementById('dash-stat-grid');
      statGrid.innerHTML = `
        <div class="card-streetside lime" style="padding:14px 16px;">
          <div style="display:flex; justify-content:space-between; align-items:center;">
            <span class="mono" style="font-size:0.65rem; color:var(--ink);">TOTAL REVENUE</span>
            <i class="bi bi-wallet2" style="font-size:1.1rem; color:var(--ink);"></i>
          </div>
          <strong style="display:block; font-size:clamp(1.2rem, 3.2vw, 1.7rem); font-weight:800; margin-top:4px;">₱${data.total_revenue.toFixed(2)}</strong>
          <span style="font-size:0.65rem; color:#4a5c56;">All Revenue Streams</span>
        </div>

        <div class="card-streetside" style="padding:14px 16px; background:#f0fdf4; border-color:var(--green);">
          <div style="display:flex; justify-content:space-between; align-items:center;">
            <span class="mono" style="font-size:0.65rem; color:var(--green);">COURT BOOKINGS</span>
            <i class="bi bi-calendar-check" style="font-size:1.1rem; color:var(--green);"></i>
          </div>
          <strong style="display:block; font-size:clamp(1.2rem, 3.2vw, 1.7rem); font-weight:800; margin-top:4px; color:var(--green);">₱${data.court_revenue.toFixed(2)}</strong>
          <span style="font-size:0.68rem; color:#2d6a4f; font-weight:700;">${data.court_count} Reservations</span>
        </div>

        <div class="card-streetside coral" style="padding:14px 16px; background:#fff3f0;">
          <div style="display:flex; justify-content:space-between; align-items:center;">
            <span class="mono" style="font-size:0.65rem; color:var(--ink);">OPEN PLAY REVENUE</span>
            <i class="bi bi-dribbble" style="font-size:1.1rem; color:var(--coral);"></i>
          </div>
          <strong style="display:block; font-size:clamp(1.2rem, 3.2vw, 1.7rem); font-weight:800; margin-top:4px; color:var(--coral);">₱${data.open_play_revenue.toFixed(2)}</strong>
          <span style="font-size:0.68rem; color:#991b1b; font-weight:700;">${data.open_play_players} Players Registered</span>
        </div>

        <div class="card-streetside sky" style="padding:14px 16px; background:#f0f9ff;">
          <div style="display:flex; justify-content:space-between; align-items:center;">
            <span class="mono" style="font-size:0.65rem; color:#0369a1;">PRO SHOP SALES</span>
            <i class="bi bi-bag-check" style="font-size:1.1rem; color:#0284c7;"></i>
          </div>
          <strong style="display:block; font-size:clamp(1.2rem, 3.2vw, 1.7rem); font-weight:800; margin-top:4px; color:#0369a1;">₱${data.product_revenue.toFixed(2)}</strong>
          <span style="font-size:0.68rem; color:#075985; font-weight:700;">${data.products_sold} Items Sold</span>
        </div>

        <div class="card-streetside sand" style="padding:14px 16px;">
          <div style="display:flex; justify-content:space-between; align-items:center;">
            <span class="mono" style="font-size:0.65rem; color:var(--ink);">TOTAL RESERVATIONS</span>
            <i class="bi bi-people" style="font-size:1.1rem; color:var(--ink);"></i>
          </div>
          <strong style="display:block; font-size:clamp(1.2rem, 3.2vw, 1.7rem); font-weight:800; margin-top:4px;">${data.total_reservations}</strong>
          <span style="font-size:0.65rem; color:#4a5c56;">Bookings + Drop-ins</span>
        </div>

        <div class="card-streetside" style="padding:14px 16px;">
          <div style="display:flex; justify-content:space-between; align-items:center;">
            <span class="mono" style="font-size:0.65rem; color:var(--ink);">${data.total_users !== undefined ? 'TOTAL USERS' : 'ACTIVE COURTS'}</span>
            <i class="bi ${data.total_users !== undefined ? 'bi-person-badge' : 'bi-geo-alt'}" style="font-size:1.1rem; color:var(--ink);"></i>
          </div>
          <strong style="display:block; font-size:clamp(1.2rem, 3.2vw, 1.7rem); font-weight:800; margin-top:4px;">${data.total_users !== undefined ? data.total_users : data.total_courts}</strong>
          <span style="font-size:0.65rem; color:#4a5c56;">${data.total_users !== undefined ? 'Platform System Users' : 'Managed Courts'}</span>
        </div>
      `;
    }

    function renderCharts(analytics) {
      const trendData = analytics.trend || [];
      const summary = analytics.summary || {};
      const peakHours = analytics.peak_hours || [];
      const courtPerf = analytics.court_performance || [];

      // 1. Revenue Breakdown Trend (Line Chart)
      const labelsTrend = trendData.map(t => t.date.substring(5)); // MM-DD
      const courtSeries = trendData.map(t => t.court_bookings);
      const openPlaySeries = trendData.map(t => t.open_play);
      const productSeries = trendData.map(t => t.product_sales);

      if (chartTrend) chartTrend.destroy();
      const ctxTrend = document.getElementById('chart-revenue-trend').getContext('2d');
      chartTrend = new Chart(ctxTrend, {
        type: 'line',
        data: {
          labels: labelsTrend,
          datasets: [
            {
              label: 'Court Bookings (₱)',
              data: courtSeries,
              borderColor: '#10b981',
              backgroundColor: 'rgba(16, 185, 129, 0.1)',
              tension: 0.3,
              fill: true,
              borderWidth: 2
            },
            {
              label: 'Open Play (₱)',
              data: openPlaySeries,
              borderColor: '#ff6f59',
              backgroundColor: 'rgba(255, 111, 89, 0.1)',
              tension: 0.3,
              fill: true,
              borderWidth: 2
            },
            {
              label: 'Pro Shop Sales (₱)',
              data: productSeries,
              borderColor: '#0284c7',
              backgroundColor: 'rgba(2, 132, 199, 0.1)',
              tension: 0.3,
              fill: true,
              borderWidth: 2
            }
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { position: 'top', labels: { font: { family: 'DM Mono', size: 11 } } },
            tooltip: {
              callbacks: {
                label: function(context) { return context.dataset.label + ': ₱' + context.parsed.y.toFixed(2); }
              }
            }
          },
          scales: {
            x: { grid: { display: false } },
            y: { ticks: { callback: v => '₱' + v } }
          }
        }
      });

      // 2. Revenue Distribution Doughnut Chart
      if (chartDoughnut) chartDoughnut.destroy();
      const ctxDoughnut = document.getElementById('chart-revenue-doughnut').getContext('2d');
      chartDoughnut = new Chart(ctxDoughnut, {
        type: 'doughnut',
        data: {
          labels: ['Court Bookings', 'Open Play Sessions', 'Pro Shop Equipment'],
          datasets: [{
            data: [
              summary.court_revenue || 0,
              summary.open_play_revenue || 0,
              summary.product_revenue || 0
            ],
            backgroundColor: ['#10b981', '#ff6f59', '#0284c7'],
            borderWidth: 2,
            borderColor: '#0d211d'
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { position: 'bottom', labels: { font: { family: 'DM Mono', size: 10 } } },
            tooltip: {
              callbacks: {
                label: function(context) { return context.label + ': ₱' + context.parsed.toFixed(2); }
              }
            }
          }
        }
      });

      // 3. Peak Hours Bar Chart
      const hourLabels = [];
      const hourCounts = [];
      for (let h = 6; h <= 22; h++) {
        const ampm = h < 12 ? 'AM' : 'PM';
        const h12 = h === 0 ? 12 : h > 12 ? h - 12 : h;
        hourLabels.push(`${h12} ${ampm}`);
        const found = peakHours.find(p => parseInt(p.hour_num) === h);
        hourCounts.push(found ? parseInt(found.count) : 0);
      }

      if (chartPeak) chartPeak.destroy();
      const ctxPeak = document.getElementById('chart-peak-hours').getContext('2d');
      chartPeak = new Chart(ctxPeak, {
        type: 'bar',
        data: {
          labels: hourLabels,
          datasets: [{
            label: 'Reservations',
            data: hourCounts,
            backgroundColor: '#eafc8d',
            borderColor: '#0d211d',
            borderWidth: 2,
            borderRadius: 6
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: { legend: { display: false } },
          scales: {
            x: { grid: { display: false } },
            y: { ticks: { precision: 0 } }
          }
        }
      });

      // 4. Court Performance Ranking (Horizontal Bar Chart)
      const courtNames = courtPerf.map(c => c.court_name);
      const courtRevs = courtPerf.map(c => parseFloat(c.revenue));

      if (chartCourts) chartCourts.destroy();
      const ctxCourts = document.getElementById('chart-court-performance').getContext('2d');
      chartCourts = new Chart(ctxCourts, {
        type: 'bar',
        data: {
          labels: courtNames.length > 0 ? courtNames : ['No Courts'],
          datasets: [{
            label: 'Revenue (₱)',
            data: courtRevs.length > 0 ? courtRevs : [0],
            backgroundColor: '#0284c7',
            borderColor: '#0d211d',
            borderWidth: 2,
            borderRadius: 6
          }]
        },
        options: {
          indexAxis: 'y',
          responsive: true,
          maintainAspectRatio: false,
          plugins: { legend: { display: false } },
          scales: {
            x: { ticks: { callback: v => '₱' + v } }
          }
        }
      });
    }

    function renderRecentReservationsTable(recentBookings, canViewBookings) {
      const mainContainer = document.getElementById('dash-main-container');

      if (!canViewBookings) {
        mainContainer.innerHTML = `
          <div style="text-align:center; padding:24px;">
            <div class="brand-mark" style="width:48px; height:48px; font-size:1.5rem; margin:0 auto 12px; background:var(--sand);">
              <i class="bi bi-shield-lock"></i>
            </div>
            <h4 style="font-size:1.1rem; font-weight:800; text-transform:uppercase; margin:0 0 6px;">RESERVATIONS VIEW RESTRICTED</h4>
            <p style="font-size:0.85rem; color:#4a5c56; margin:0;">
              Your assigned user permissions do not include <code>booking.view</code> to display recent court reservations.
            </p>
          </div>
        `;
        return;
      }

      mainContainer.innerHTML = `
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px; flex-wrap:wrap; gap:8px;">
          <h3 class="mono" style="margin:0; color:var(--green); font-size:0.95rem;">RECENT RESERVATIONS (COURT BOOKINGS &amp; OPEN PLAY)</h3>
          <a href="/pikvero/public/admin/bookings.php" class="mono" style="font-size:0.75rem; text-decoration:underline;">VIEW ALL RESERVATIONS &rarr;</a>
        </div>
        <div style="overflow-x:auto; -webkit-overflow-scrolling:touch;">
          <table style="width:100%; min-width:700px; border-collapse:collapse; text-align:left; font-size:0.82rem;">
            <thead>
              <tr style="border-bottom:2px solid var(--ink); font-family:'DM Mono', monospace; font-size:0.72rem;">
                <th style="padding:8px 10px;">REF # &amp; TYPE</th>
                <th style="padding:8px 10px;">CUSTOMER</th>
                <th style="padding:8px 10px;">COURT / SESSION</th>
                <th style="padding:8px 10px;">SCHEDULE</th>
                <th style="padding:8px 10px;">AMOUNT</th>
                <th style="padding:8px 10px;">STATUS</th>
              </tr>
            </thead>
            <tbody>
              ${recentBookings.length > 0 ? recentBookings.map(b => {
                const isOp = (b.reservation_type === 'open_play' || (b.booking_reference && b.booking_reference.startsWith('OP-')));
                const typeBadge = isOp 
                  ? `<span class="badge-streetside coral" style="font-size:0.62rem; padding:2px 6px;">🏀 OPEN PLAY</span>`
                  : `<span class="badge-streetside sky" style="font-size:0.62rem; padding:2px 6px;">📅 COURT BOOKING</span>`;

                const bkSt = (b.booking_status || 'confirmed').toLowerCase();
                const paySt = (b.payment_status || 'paid').toLowerCase();

                let statusBadgeClass = 'lime';
                if (bkSt === 'cancelled' || paySt === 'refunded') {
                  statusBadgeClass = 'coral';
                } else if (bkSt === 'pending' || paySt === 'unpaid' || paySt === 'awaiting_payment') {
                  statusBadgeClass = 'sand';
                }

                const refDisplay = b.booking_reference ? (b.booking_reference.startsWith('#') || b.booking_reference.startsWith('OP-') ? b.booking_reference : '#' + b.booking_reference) : 'REF-' + b.id;

                return `
                  <tr style="border-bottom:1px solid var(--line);">
                    <td style="padding:8px 10px;">
                      <strong class="mono" style="font-size:0.8rem; color:var(--ink);">${escapeHtml(refDisplay)}</strong>
                      <div style="margin-top:2px;">${typeBadge}</div>
                    </td>
                    <td style="padding:8px 10px; font-weight:700;">${escapeHtml(b.customer_name || 'Guest Player')}</td>
                    <td style="padding:8px 10px; font-weight:700;">${escapeHtml(b.court_name || 'Court')}</td>
                    <td style="padding:8px 10px;">
                      <div style="font-weight:700;">${escapeHtml(b.booking_date || '')}</div>
                      <div style="font-size:0.72rem; color:#5a7060;">${formatTimeStr(b.start_time)} ${b.end_time ? ' - ' + formatTimeStr(b.end_time) : ''}</div>
                    </td>
                    <td style="padding:8px 10px; font-weight:800; color:var(--ink);">₱${parseFloat(b.total_amount || 0).toFixed(2)}</td>
                    <td style="padding:8px 10px;">
                      <span class="badge-streetside ${statusBadgeClass}" style="font-size:0.68rem; text-transform:uppercase;">${escapeHtml(bkSt)} &bull; ${escapeHtml(paySt)}</span>
                    </td>
                  </tr>
                `;
              }).join('') : `
                <tr>
                  <td colspan="6" style="padding:28px; text-align:center; color:#5a7060;">No recent court bookings or open play reservations recorded for selected period.</td>
                </tr>
              `}
            </tbody>
          </table>
        </div>
      `;
    }

    function formatTimeStr(t) {
      if (!t) return '';
      const [h, m] = t.split(':').map(Number);
      const ampm = h < 12 ? 'AM' : 'PM';
      const h12 = h === 0 ? 12 : h > 12 ? h - 12 : h;
      return h12 + ':' + String(m || 0).padStart(2, '0') + ' ' + ampm;
    }

    function renderAdminUsersTable(recentUsers) {
      const mainContainer = document.getElementById('dash-main-container');
      mainContainer.innerHTML = `
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px; flex-wrap:wrap; gap:8px;">
          <h3 class="mono" style="margin:0; color:var(--green);">RECENT REGISTERED USERS</h3>
          <a href="/pikvero/public/admin/users.php" class="mono" style="font-size:0.75rem; text-decoration:underline;">VIEW ALL USERS &rarr;</a>
        </div>
        <div style="overflow-x:auto; -webkit-overflow-scrolling:touch;">
          <table style="width:100%; min-width:600px; border-collapse:collapse; text-align:left; font-size:0.85rem;">
            <thead>
              <tr style="border-bottom:2px solid var(--ink); font-family:'DM Mono', monospace; font-size:0.72rem;">
                <th style="padding:8px;">ID</th>
                <th style="padding:8px;">NAME</th>
                <th style="padding:8px;">EMAIL</th>
                <th style="padding:8px;">ROLE</th>
                <th style="padding:8px;">STATUS</th>
              </tr>
            </thead>
            <tbody>
              ${recentUsers.length > 0 ? recentUsers.map(u => `
                <tr style="border-bottom:1px solid var(--line);">
                  <td style="padding:8px; font-weight:700; font-family:'DM Mono', monospace;">#${u.id}</td>
                  <td style="padding:8px; font-weight:700;">${escapeHtml(u.first_name + ' ' + u.last_name)}</td>
                  <td style="padding:8px;">${escapeHtml(u.email)}</td>
                  <td style="padding:8px;"><span class="badge-streetside dark">${escapeHtml(u.role_display)}</span></td>
                  <td style="padding:8px;"><span class="badge-streetside ${u.status === 'active' ? 'lime' : 'coral'}">${u.status.toUpperCase()}</span></td>
                </tr>
              `).join('') : `
                <tr><td colspan="5" style="padding:24px; text-align:center; color:#5a7060;">No recent users registered.</td></tr>
              `}
            </tbody>
          </table>
        </div>
      `;
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
