<?php
$pageTitle  = 'Pikvero — Revenue & Analytics';
$headExtras = ['jquery', 'chartjs'];
require_once __DIR__ . '/../../includes/head.php';
?>
<body>

  <aside id="sidebar-container"></aside>
  <header id="navbar-container"></header>

  <main class="portal-main">
    <div>
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:12px;">
        <div>
          <div class="eyebrow">REVENUE ANALYTICS &amp; REPORTS</div>
          <h1 style="font-size: clamp(1.8rem, 4vw, 2.2rem); font-weight:800; text-transform:uppercase; margin:4px 0 0;">BUSINESS PERFORMANCE</h1>
        </div>
        <div style="display:flex; gap:8px;">
          <button type="button" onclick="openPrintReport()" class="button lime" style="padding:8px 16px; font-size:0.82rem;">
            <i class="bi bi-printer-fill"></i> Print / Export Report
          </button>
        </div>
      </div>

      <!-- DATE RANGE FILTER TOOLBAR CARD -->
      <div class="card-streetside" style="padding:18px 20px; background:var(--white); margin-bottom:24px;">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:14px;">
          <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
            <span class="mono" style="font-size:0.75rem; font-weight:800; color:var(--green);"><i class="bi bi-funnel-fill"></i> QUICK PRESETS:</span>
            <button type="button" onclick="setPreset('today')" class="button sand btn-preset" id="preset-today" style="padding:5px 12px; font-size:0.75rem;">Today</button>
            <button type="button" onclick="setPreset('this_week')" class="button sand btn-preset" id="preset-this_week" style="padding:5px 12px; font-size:0.75rem;">This Week</button>
            <button type="button" onclick="setPreset('this_month')" class="button coral btn-preset" id="preset-this_month" style="padding:5px 12px; font-size:0.75rem;">This Month</button>
            <button type="button" onclick="setPreset('last_30')" class="button sand btn-preset" id="preset-last_30" style="padding:5px 12px; font-size:0.75rem;">Last 30 Days</button>
            <button type="button" onclick="setPreset('this_year')" class="button sand btn-preset" id="preset-this_year" style="padding:5px 12px; font-size:0.75rem;">This Year</button>
          </div>

          <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
            <div style="display:flex; align-items:center; gap:6px;">
              <label class="mono" style="font-size:0.72rem;">FROM:</label>
              <input type="date" id="filter-start-date" style="padding:6px 10px; border:2px solid var(--ink); border-radius:8px; font-weight:700; font-family:inherit;">
            </div>
            <div style="display:flex; align-items:center; gap:6px;">
              <label class="mono" style="font-size:0.72rem;">TO:</label>
              <input type="date" id="filter-end-date" style="padding:6px 10px; border:2px solid var(--ink); border-radius:8px; font-weight:700; font-family:inherit;">
            </div>
            <button type="button" onclick="loadAnalyticsData()" class="button lime" style="padding:7px 16px; font-size:0.8rem;"><i class="bi bi-filter"></i> Apply Filter</button>
          </div>
        </div>
      </div>

      <!-- KPI METRIC CARDS -->
      <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:16px; margin-bottom:24px;">
        <div class="card-streetside lime" style="padding:18px;">
          <div style="display:flex; justify-content:space-between; align-items:center;">
            <span class="mono" style="font-size:0.72rem; color:var(--ink); font-weight:800;">TOTAL NET REVENUE</span>
            <i class="bi bi-cash-stack" style="font-size:1.4rem; color:var(--ink);"></i>
          </div>
          <strong id="rep-total-rev" style="display:block; font-size:1.8rem; font-weight:900; margin-top:6px;">₱0.00</strong>
          <span class="badge-streetside sand" style="margin-top:6px; font-size:0.68rem;" id="rep-date-span">Selected Period</span>
        </div>

        <div class="card-streetside sky" style="padding:18px;">
          <div style="display:flex; justify-content:space-between; align-items:center;">
            <span class="mono" style="font-size:0.72rem; color:var(--ink); font-weight:800;">COURT BOOKINGS</span>
            <i class="bi bi-ticket-detailed-fill" style="font-size:1.4rem; color:#0284c7;"></i>
          </div>
          <strong id="rep-court-rev" style="display:block; font-size:1.8rem; font-weight:900; margin-top:6px;">₱0.00</strong>
          <div style="font-size:0.75rem; font-weight:700; color:#1e3a8a; margin-top:4px;"><span id="rep-court-count">0</span> Confirmed Reservations</div>
        </div>

        <div class="card-streetside" style="padding:18px; background:#fef9c3;">
          <div style="display:flex; justify-content:space-between; align-items:center;">
            <span class="mono" style="font-size:0.72rem; color:var(--ink); font-weight:800;">OPEN PLAY SOCIALS</span>
            <i class="bi bi-dribbble" style="font-size:1.4rem; color:#ca8a04;"></i>
          </div>
          <strong id="rep-openplay-rev" style="display:block; font-size:1.8rem; font-weight:900; margin-top:6px;">₱0.00</strong>
          <div style="font-size:0.75rem; font-weight:700; color:#854d0e; margin-top:4px;"><span id="rep-openplay-count">0</span> Player Registrations</div>
        </div>

        <div class="card-streetside" style="padding:18px; background:#ffedd5;">
          <div style="display:flex; justify-content:space-between; align-items:center;">
            <span class="mono" style="font-size:0.72rem; color:var(--ink); font-weight:800;">GEAR &amp; PRODUCTS</span>
            <i class="bi bi-shop" style="font-size:1.4rem; color:#ea580c;"></i>
          </div>
          <strong id="rep-product-rev" style="display:block; font-size:1.8rem; font-weight:900; margin-top:6px;">₱0.00</strong>
          <div style="font-size:0.75rem; font-weight:700; color:#9a3412; margin-top:4px;"><span id="rep-product-count">0</span> Items Sold</div>
        </div>
      </div>

      <!-- CHARTS ROW 1: Trend & Stream Breakdown -->
      <div style="display:grid; grid-template-columns: 2fr 1fr; gap:20px; margin-bottom:24px;">
        <div class="card-streetside" style="padding:24px; background:var(--white);">
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; flex-wrap:wrap; gap:10px;">
            <h3 style="margin:0; font-size:1.1rem; font-weight:800; text-transform:uppercase;"><i class="bi bi-graph-up-arrow"></i> DAILY REVENUE TREND &amp; STREAMS</h3>
            <span class="badge-streetside lime">STACKED REVENUE BREAKDOWN</span>
          </div>
          <div style="position:relative; height:300px; width:100%;">
            <canvas id="trendChart"></canvas>
          </div>
        </div>

        <div class="card-streetside" style="padding:24px; background:var(--white);">
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
            <h3 style="margin:0; font-size:1.1rem; font-weight:800; text-transform:uppercase;"><i class="bi bi-pie-chart-fill"></i> STREAM SHARE</h3>
          </div>
          <div style="position:relative; height:300px; width:100%; display:flex; align-items:center; justify-content:center;">
            <canvas id="streamChart"></canvas>
          </div>
        </div>
      </div>

      <!-- CHARTS ROW 2: Peak Hours & Court Performance Ranking -->
      <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px; margin-bottom:24px;">
        <div class="card-streetside" style="padding:24px; background:var(--white);">
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
            <h3 style="margin:0; font-size:1.1rem; font-weight:800; text-transform:uppercase;"><i class="bi bi-clock-history"></i> HOURLY PEAK DEMAND</h3>
            <span class="badge-streetside sky">BOOKINGS BY START HOUR</span>
          </div>
          <div style="position:relative; height:280px; width:100%;">
            <canvas id="peakHoursChart"></canvas>
          </div>
        </div>

        <div class="card-streetside" style="padding:24px; background:var(--white);">
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
            <h3 style="margin:0; font-size:1.1rem; font-weight:800; text-transform:uppercase;"><i class="bi bi-trophy-fill"></i> COURT REVENUE RANKING</h3>
            <span class="badge-streetside coral">TOP PERFORMING COURTS</span>
          </div>
          <div style="position:relative; height:280px; width:100%;">
            <canvas id="courtRankChart"></canvas>
          </div>
        </div>
      </div>

      <!-- COURT PERFORMANCE TABLE -->
      <div class="card-streetside" style="padding:24px; background:var(--white); margin-bottom:24px;">
        <h3 style="margin:0 0 16px; font-size:1.1rem; font-weight:800; text-transform:uppercase;"><i class="bi bi-table"></i> COURT PERFORMANCE &amp; REVENUE SUMMARY TABLE</h3>
        <div style="overflow-x:auto;">
          <table class="table-streetside" style="width:100%; border-collapse:collapse; font-size:0.85rem;">
            <thead>
              <tr style="border-bottom:2px solid var(--ink); text-align:left; font-family:'DM Mono', monospace; font-size:0.75rem;">
                <th style="padding:10px;">COURT NAME</th>
                <th style="padding:10px; text-align:center;">TOTAL RESERVATIONS</th>
                <th style="padding:10px; text-align:right;">REVENUE GENERATED</th>
                <th style="padding:10px; text-align:right;">REVENUE SHARE %</th>
              </tr>
            </thead>
            <tbody id="court-performance-tbody">
              <!-- Rendered via JS -->
            </tbody>
          </table>
        </div>
      </div>

    </div>

    <footer id="footer-container"></footer>
  </main>

  <?php require_once __DIR__ . '/../../includes/scripts.php'; ?>
  <script>
    let trendChartInstance = null;
    let streamChartInstance = null;
    let peakChartInstance = null;
    let rankChartInstance = null;

    document.addEventListener('DOMContentLoaded', async () => {
      await NavbarComponent.render('#navbar-container', true);
      const userCtx = await AuthHelper.checkSession();
      SidebarComponent.render('reports', userCtx && userCtx.role === 'court_owner' ? 'owner' : 'admin');
      FooterComponent.render('#footer-container', true);

      // Default to "This Month" preset
      setPreset('this_month');
    });

    function openPrintReport() {
      const startDate = document.getElementById('filter-start-date').value;
      const endDate   = document.getElementById('filter-end-date').value;
      window.open(`/pikvero/public/admin/reports-print.php?start_date=${startDate}&end_date=${endDate}&autoprint=1`, '_blank');
    }

    function setPreset(preset) {
      const today = new Date();
      let start = new Date();
      let end = new Date();

      $('.btn-preset').removeClass('coral').addClass('sand');
      $(`#preset-${preset}`).removeClass('sand').addClass('coral');

      if (preset === 'today') {
        start = today;
        end = today;
      } else if (preset === 'this_week') {
        const day = today.getDay() || 7;
        start.setDate(today.getDate() - day + 1);
        end = today;
      } else if (preset === 'this_month') {
        start = new Date(today.getFullYear(), today.getMonth(), 1);
        end = today;
      } else if (preset === 'last_30') {
        start.setDate(today.getDate() - 30);
        end = today;
      } else if (preset === 'this_year') {
        start = new Date(today.getFullYear(), 0, 1);
        end = today;
      }

      document.getElementById('filter-start-date').value = formatDateIso(start);
      document.getElementById('filter-end-date').value = formatDateIso(end);

      loadAnalyticsData();
    }

    function formatDateIso(d) {
      const yr = d.getFullYear();
      const mo = String(d.getMonth() + 1).padStart(2, '0');
      const da = String(d.getDate()).padStart(2, '0');
      return `${yr}-${mo}-${da}`;
    }

    async function loadAnalyticsData() {
      const startDate = document.getElementById('filter-start-date').value;
      const endDate   = document.getElementById('filter-end-date').value;

      try {
        const res = await Api.get('/pikvero/api/owner/reports.php', { start_date: startDate, end_date: endDate });
        if (res.success && res.data) {
          const { summary, trend, peak_hours, court_performance, period } = res.data;

          // Update KPI Cards
          document.getElementById('rep-total-rev').innerText    = `₱${parseFloat(summary.total_revenue || 0).toFixed(2)}`;
          document.getElementById('rep-court-rev').innerText    = `₱${parseFloat(summary.court_revenue || 0).toFixed(2)}`;
          document.getElementById('rep-court-count').innerText  = summary.court_bookings_count || 0;
          document.getElementById('rep-openplay-rev').innerText = `₱${parseFloat(summary.open_play_revenue || 0).toFixed(2)}`;
          document.getElementById('rep-openplay-count').innerText = summary.open_play_players_count || 0;
          document.getElementById('rep-product-rev').innerText = `₱${parseFloat(summary.product_revenue || 0).toFixed(2)}`;
          document.getElementById('rep-product-count').innerText = summary.products_sold_count || 0;
          document.getElementById('rep-date-span').innerText = `${period.start_date} to ${period.end_date}`;

          // Render All 4 Charts
          renderTrendChart(trend);
          renderStreamChart(summary);
          renderPeakHoursChart(peak_hours);
          renderCourtRankChart(court_performance);

          // Render Performance Table
          renderCourtTable(court_performance, summary.court_revenue);
        }
      } catch (e) { console.error(e); }
    }

    // 1. Stacked Revenue Trend Chart
    function renderTrendChart(trendData) {
      const ctx = document.getElementById('trendChart').getContext('2d');
      if (trendChartInstance) trendChartInstance.destroy();

      const labels = trendData && trendData.length > 0 ? trendData.map(d => d.date.substring(5)) : [];
      const courtRev = trendData ? trendData.map(d => d.court_bookings) : [];
      const openPlayRev = trendData ? trendData.map(d => d.open_play) : [];
      const productRev = trendData ? trendData.map(d => d.product_sales) : [];

      trendChartInstance = new Chart(ctx, {
        type: 'bar',
        data: {
          labels: labels,
          datasets: [
            { label: 'Court Bookings (₱)', data: courtRev, backgroundColor: '#ff745c', borderWidth: 1 },
            { label: 'Open Play Socials (₱)', data: openPlayRev, backgroundColor: '#eab308', borderWidth: 1 },
            { label: 'Products & Gear (₱)', data: productRev, backgroundColor: '#0284c7', borderWidth: 1 }
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          scales: {
            x: { stacked: true, grid: { display: false } },
            y: { stacked: true, beginAtZero: true, grid: { color: 'rgba(13,33,29,0.08)' } }
          },
          plugins: {
            legend: { position: 'top', labels: { font: { family: 'DM Mono', size: 11, weight: 'bold' } } }
          }
        }
      });
    }

    // 2. Revenue Stream Share Doughnut Chart
    function renderStreamChart(summary) {
      const ctx = document.getElementById('streamChart').getContext('2d');
      if (streamChartInstance) streamChartInstance.destroy();

      const cRev = parseFloat(summary.court_revenue || 0);
      const oRev = parseFloat(summary.open_play_revenue || 0);
      const pRev = parseFloat(summary.product_revenue || 0);

      streamChartInstance = new Chart(ctx, {
        type: 'doughnut',
        data: {
          labels: ['Court Bookings', 'Open Play Socials', 'Products & Gear'],
          datasets: [{
            data: [cRev, oRev, pRev],
            backgroundColor: ['#ff745c', '#eab308', '#0284c7'],
            borderWidth: 3,
            borderColor: '#fffefa'
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { position: 'bottom', labels: { font: { family: 'DM Mono', size: 10, weight: 'bold' } } }
          }
        }
      });
    }

    // 3. Peak Hours Booking Demand Chart
    function renderPeakHoursChart(peakData) {
      const ctx = document.getElementById('peakHoursChart').getContext('2d');
      if (peakChartInstance) peakChartInstance.destroy();

      const hoursMap = {};
      for (let h = 6; h <= 22; h++) {
        hoursMap[h] = 0;
      }
      if (peakData && peakData.length > 0) {
        peakData.forEach(p => {
          const h = parseInt(p.hour_num, 10);
          if (hoursMap[h] !== undefined) hoursMap[h] += parseInt(p.count, 10);
        });
      }

      const labels = Object.keys(hoursMap).map(h => `${h}:00`);
      const counts = Object.values(hoursMap);

      peakChartInstance = new Chart(ctx, {
        type: 'bar',
        data: {
          labels: labels,
          datasets: [{
            label: 'Reservations',
            data: counts,
            backgroundColor: '#a7efff',
            borderColor: '#0d211d',
            borderWidth: 2,
            borderRadius: 4
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: { legend: { display: false } },
          scales: {
            x: { grid: { display: false } },
            y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: 'rgba(13,33,29,0.08)' } }
          }
        }
      });
    }

    // 4. Court Revenue Ranking Chart
    function renderCourtRankChart(courtData) {
      const ctx = document.getElementById('courtRankChart').getContext('2d');
      if (rankChartInstance) rankChartInstance.destroy();

      const labels = courtData && courtData.length > 0 ? courtData.map(c => c.court_name) : ['No Data'];
      const revenues = courtData && courtData.length > 0 ? courtData.map(c => parseFloat(c.revenue || 0)) : [0];

      rankChartInstance = new Chart(ctx, {
        type: 'bar',
        indexAxis: 'y',
        data: {
          labels: labels,
          datasets: [{
            label: 'Revenue (₱)',
            data: revenues,
            backgroundColor: '#dfff4f',
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
            x: { beginAtZero: true, grid: { color: 'rgba(13,33,29,0.08)' } },
            y: { grid: { display: false } }
          }
        }
      });
    }

    // Render Court Performance Table
    function renderCourtTable(courtData, totalCourtRev) {
      const tbody = document.getElementById('court-performance-tbody');
      if (!tbody) return;

      if (!courtData || courtData.length === 0) {
        tbody.innerHTML = `<tr><td colspan="4" style="padding:16px; text-align:center; color:#666;">No court performance records for selected date range.</td></tr>`;
        return;
      }

      const totalRev = parseFloat(totalCourtRev || 0);

      const html = courtData.map(c => {
        const rev = parseFloat(c.revenue || 0);
        const pct = totalRev > 0 ? ((rev / totalRev) * 100).toFixed(1) : '0.0';
        return `
          <tr style="border-bottom:1px solid rgba(13,33,29,0.1);">
            <td style="padding:10px; font-weight:800;"><i class="bi bi-geo-alt-fill" style="color:var(--coral);"></i> ${c.court_name}</td>
            <td style="padding:10px; text-align:center; font-family:'DM Mono', monospace; font-weight:700;">${c.total_bookings || 0}</td>
            <td style="padding:10px; text-align:right; font-weight:800; color:var(--green);">₱${rev.toFixed(2)}</td>
            <td style="padding:10px; text-align:right;"><span class="badge-streetside lime" style="font-size:0.7rem;">${pct}%</span></td>
          </tr>
        `;
      }).join('');

      tbody.innerHTML = html;
    }
  </script>
</body>
</html>

