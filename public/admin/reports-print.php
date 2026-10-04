<?php
require_once __DIR__ . '/../../app/bootstrap.php';
use App\Core\Auth\Auth;
use App\Infrastructure\Repositories\BookingRepository;
use App\Infrastructure\Repositories\OrganizationRepository;

if (!Auth::check()) {
    header('Location: /pikvero/public/login.php');
    exit;
}

$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate   = $_GET['end_date']   ?? date('Y-m-d');

$role = Auth::role();
$orgId = ($role === 'super_admin' || $role === 'platform_admin') ? null : Auth::organizationId();

$bookingRepo = new BookingRepository();
$data = $bookingRepo->getComprehensiveAnalytics($orgId, $startDate, $endDate);

$summary   = $data['summary'];
$trend     = $data['trend'];
$courtPerf = $data['court_performance'];
$peakHours = $data['peak_hours'];

$orgName = 'Pikvero Network';
if ($orgId) {
    $orgRepo = new OrganizationRepository();
    $org = $orgRepo->findById($orgId);
    if ($org) $orgName = $org['name'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pikvero Revenue &amp; Performance Report (<?= htmlspecialchars($startDate) ?> to <?= htmlspecialchars($endDate) ?>)</title>
  <link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@500;700&family=Plus+Jakarta+Sans:wght@700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    :root {
      --ink: #0d211d;
      --sand: #eee9d8;
      --lime: #dfff4f;
      --coral: #ff745c;
      --sky: #a7efff;
      --cream: #fbf9ef;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: 'DM Mono', monospace;
      color: var(--ink);
      background: #f4f7f6;
      padding: 24px;
      display: flex;
      flex-direction: column;
      align-items: center;
    }
    .no-print-bar {
      width: min(900px, 100%);
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
    }
    .btn-action {
      font-family: 'Plus Jakarta Sans', sans-serif;
      font-weight: 800;
      font-size: 0.82rem;
      text-transform: uppercase;
      padding: 10px 18px;
      border: 2px solid var(--ink);
      border-radius: 10px;
      cursor: pointer;
      box-shadow: 3px 3px 0 var(--ink);
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      transition: all 0.15s ease;
    }
    .btn-action:hover { transform: translateY(-2px); }
    .btn-action.lime { background: var(--lime); color: var(--ink); }
    .btn-action.sand { background: #f0eee6; color: var(--ink); }

    .print-sheet {
      width: min(900px, 100%);
      background: #fff;
      border: 2px solid var(--ink);
      border-radius: 16px;
      box-shadow: 6px 6px 0 var(--ink);
      padding: 32px;
    }
    .sheet-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      border-bottom: 2.5px solid var(--ink);
      padding-bottom: 18px;
      margin-bottom: 24px;
    }
    .brand-title {
      font-family: 'Plus Jakarta Sans', sans-serif;
      font-weight: 800;
      font-size: 1.5rem;
      text-transform: uppercase;
      letter-spacing: -0.02em;
    }
    .grid-kpi {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 12px;
      margin-bottom: 24px;
    }
    .kpi-box {
      border: 2px solid var(--ink);
      border-radius: 10px;
      padding: 12px 14px;
      background: var(--cream);
    }
    .kpi-box.lime { background: var(--lime); }
    .kpi-box.sky { background: var(--sky); }
    .kpi-box.sand { background: var(--sand); }
    .kpi-label {
      font-size: 0.65rem;
      font-weight: 800;
      text-transform: uppercase;
      color: #3b4e49;
    }
    .kpi-value {
      font-family: 'Plus Jakarta Sans', sans-serif;
      font-size: 1.35rem;
      font-weight: 800;
      margin-top: 4px;
    }
    .section-title {
      font-family: 'Plus Jakarta Sans', sans-serif;
      font-size: 1rem;
      font-weight: 800;
      text-transform: uppercase;
      margin: 24px 0 10px;
      padding-bottom: 6px;
      border-bottom: 2px solid var(--ink);
      display: flex;
      align-items: center;
      gap: 8px;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 20px;
    }
    th, td {
      border: 1.5px solid var(--ink);
      padding: 8px 10px;
      font-size: 0.78rem;
      text-align: left;
    }
    th {
      background: var(--sand);
      font-weight: 800;
      text-transform: uppercase;
    }
    .total-row td {
      background: #f0eee6;
      font-weight: 800;
    }

    @media print {
      body { background: none; padding: 0; }
      .no-print-bar { display: none !important; }
      .print-sheet { box-shadow: none; border: none; width: 100%; padding: 0; }
      th { background: #e5e5e5 !important; }
    }
  </style>
</head>
<body>

  <div class="no-print-bar">
    <button onclick="handleBack()" class="btn-action sand"><i class="bi bi-arrow-left"></i> Back to Dashboard</button>
    <button onclick="window.print()" class="btn-action lime"><i class="bi bi-printer-fill"></i> Print Report Page</button>
  </div>

  <div class="print-sheet">
    <!-- Header -->
    <div class="sheet-header">
      <div>
        <div class="brand-title"><i class="bi bi-graph-up-arrow"></i> PIKVERO BUSINESS PERFORMANCE</div>
        <div style="font-size:0.88rem; font-weight:800; margin-top:4px; color:#0b4d40;">
          <?= htmlspecialchars($orgName) ?> — Revenue &amp; Operations Audit Report
        </div>
        <div style="font-size:0.75rem; color:#4a5c56; margin-top:2px;">
          Period: <strong><?= date('M d, Y', strtotime($startDate)) ?></strong> to <strong><?= date('M d, Y', strtotime($endDate)) ?></strong>
        </div>
      </div>
      <div style="text-align:right;">
        <div style="font-size:0.78rem; font-weight:800;">REPORT ID: REP-<?= date('Ymd-His') ?></div>
        <div style="font-size:0.72rem; color:#4a5c56; margin-top:2px;">Generated: <?= date('F d, Y • h:i A') ?></div>
        <div style="font-size:0.72rem; color:#4a5c56;">Generated By: <?= htmlspecialchars(Auth::user()['name'] ?? 'Admin') ?></div>
      </div>
    </div>

    <!-- Executive Financial Summary KPI Grid -->
    <div class="grid-kpi">
      <div class="kpi-box lime">
        <div class="kpi-label">TOTAL NET REVENUE</div>
        <div class="kpi-value">₱<?= number_format($summary['total_revenue'], 2) ?></div>
        <div style="font-size:0.65rem; margin-top:2px; font-weight:700; color:#0b4d40;"><?= $summary['total_reservations'] ?> Total Bookings</div>
      </div>
      <div class="kpi-box sky">
        <div class="kpi-label">COURT BOOKINGS</div>
        <div class="kpi-value">₱<?= number_format($summary['court_revenue'], 2) ?></div>
        <div style="font-size:0.65rem; margin-top:2px; font-weight:700; color:#0369a1;"><?= $summary['court_bookings_count'] ?> Court Reservations</div>
      </div>
      <div class="kpi-box" style="background:#fef9c3;">
        <div class="kpi-label">OPEN PLAY SOCIALS</div>
        <div class="kpi-value">₱<?= number_format($summary['open_play_revenue'], 2) ?></div>
        <div style="font-size:0.65rem; margin-top:2px; font-weight:700; color:#854d0e;"><?= $summary['open_play_players_count'] ?> Player Registrations</div>
      </div>
      <div class="kpi-box" style="background:#ffedd5;">
        <div class="kpi-label">GEAR &amp; PRODUCTS</div>
        <div class="kpi-value">₱<?= number_format($summary['product_revenue'], 2) ?></div>
        <div style="font-size:0.65rem; margin-top:2px; font-weight:700; color:#9a3412;"><?= $summary['products_sold_count'] ?> Items Sold</div>
      </div>
    </div>

    <!-- Court Performance Breakdown Table -->
    <div class="section-title"><i class="bi bi-trophy-fill"></i> COURT REVENUE &amp; UTILIZATION RANKING</div>
    <table>
      <thead>
        <tr>
          <th style="width:40px;">#</th>
          <th>COURT NAME</th>
          <th style="text-align:center;">CONFIRMED RESERVATIONS</th>
          <th style="text-align:right;">REVENUE GENERATED</th>
          <th style="text-align:right;">REVENUE SHARE</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($courtPerf)): ?>
          <tr><td colspan="5" style="text-align:center; padding:16px;">No court booking records for selected date range.</td></tr>
        <?php else: ?>
          <?php foreach ($courtPerf as $idx => $c): 
            $rev = (float)($c['revenue'] ?? 0);
            $share = $summary['court_revenue'] > 0 ? ($rev / $summary['court_revenue']) * 100 : 0;
          ?>
            <tr>
              <td><strong><?= $idx + 1 ?></strong></td>
              <td><strong style="font-family:'Plus Jakarta Sans', sans-serif;"><?= htmlspecialchars($c['court_name']) ?></strong></td>
              <td style="text-align:center;"><strong><?= (int)$c['total_bookings'] ?></strong></td>
              <td style="text-align:right; font-weight:800; color:#0b4d40;">₱<?= number_format($rev, 2) ?></td>
              <td style="text-align:right;"><strong><?= number_format($share, 1) ?>%</strong></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>

    <!-- Daily Revenue Trend Breakdown Table -->
    <div class="section-title"><i class="bi bi-calendar3"></i> DAILY REVENUE &amp; CHANNEL BREAKDOWN</div>
    <table>
      <thead>
        <tr>
          <th>DATE</th>
          <th style="text-align:right;">COURT BOOKINGS (₱)</th>
          <th style="text-align:right;">OPEN PLAY SOCIALS (₱)</th>
          <th style="text-align:right;">PRODUCT SALES (₱)</th>
          <th style="text-align:right;">DAILY TOTAL (₱)</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($trend)): ?>
          <tr><td colspan="5" style="text-align:center; padding:16px;">No daily trend data available.</td></tr>
        <?php else: ?>
          <?php foreach ($trend as $t): ?>
            <tr>
              <td><strong><?= date('M d, Y (D)', strtotime($t['date'])) ?></strong></td>
              <td style="text-align:right;">₱<?= number_format($t['court_bookings'], 2) ?></td>
              <td style="text-align:right;">₱<?= number_format($t['open_play'], 2) ?></td>
              <td style="text-align:right;">₱<?= number_format($t['product_sales'], 2) ?></td>
              <td style="text-align:right; font-weight:800; color:#0b4d40;">₱<?= number_format($t['total'], 2) ?></td>
            </tr>
          <?php endforeach; ?>
          <tr class="total-row">
            <td>TOTAL PERIOD REVENUE</td>
            <td style="text-align:right;">₱<?= number_format($summary['court_revenue'], 2) ?></td>
            <td style="text-align:right;">₱<?= number_format($summary['open_play_revenue'], 2) ?></td>
            <td style="text-align:right;">₱<?= number_format($summary['product_revenue'], 2) ?></td>
            <td style="text-align:right; font-size:0.9rem; color:#0b4d40;">₱<?= number_format($summary['total_revenue'], 2) ?></td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>

    <!-- Signatures & Audit Stamp -->
    <div style="margin-top:36px; display:flex; justify-content:space-between; font-size:0.75rem; color:#444; padding-top:16px; border-top:1.5px dashed var(--ink);">
      <div>Prepared By: ___________________________</div>
      <div>Venue Operations Manager: ___________________________</div>
      <div>Official Pikvero Audit Stamp</div>
    </div>
  </div>

  <script>
    function handleBack() {
      let closed = false;
      try {
        if (window.opener) {
          window.close();
          closed = true;
        }
      } catch (e) {}

      if (!closed) {
        if (document.referrer && document.referrer.includes('/pikvero/') && window.history.length > 1) {
          window.history.back();
        } else {
          window.location.href = '/pikvero/public/admin/reports.php';
        }
      }

      setTimeout(() => {
        window.location.href = '/pikvero/public/admin/reports.php';
      }, 150);
    }

    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('autoprint') === '1') {
      window.onload = function() {
        setTimeout(() => window.print(), 350);
      };
    }
  </script>
</body>
</html>
