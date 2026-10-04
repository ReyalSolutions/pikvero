<?php
require_once __DIR__ . '/../../../app/bootstrap.php';
use App\Core\Auth\Auth;
use App\Infrastructure\Repositories\BookingRepository;

// Session & Authentication Check
if (!Auth::check()) {
    header('Location: /pikvero/public/login.php');
    exit;
}

$user = Auth::user();
$role = $user['role_name'] ?? '';

// Permission check
$canPrint = ($role === 'super_admin') || Auth::hasPermission('booking.print', 'bookings.manage');

if (!$canPrint) {
    header('Location: /pikvero/public/403.php?permission=booking.print');
    exit;
}

$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? 'all');
$tenantOrgId = in_array($role, ['super_admin', 'platform_admin']) ? 0 : (int)($user['organization_id'] ?? 0);

$bookingRepo = new BookingRepository();
$result = $bookingRepo->getPaginatedBookings($tenantOrgId, 1, 500, $search, $status);

$records = $result['data'] ?? [];
$totalRecords = count($records);
$totalRevenue = 0;
foreach ($records as $r) {
    if ($r['booking_status'] !== 'cancelled' && $r['booking_status'] !== 'refunded') {
        $totalRevenue += (float)$r['total_amount'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Court Reservations Summary Report — Pikvero</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500;700&family=Syne:wght@700;800;900&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    :root {
      --ink: #0d1f18;
      --cream: #f4efe6;
      --green: #007a4d;
      --lime: #ccff00;
      --coral: #ff5e36;
      --sand: #e6dfd3;
      --white: #ffffff;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: 'Plus Jakarta Sans', sans-serif;
      background: #eef2ef;
      color: var(--ink);
      padding: 24px;
    }
    .print-toolbar {
      max-width: 1000px;
      margin: 0 auto 20px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .btn-action {
      font-family: 'DM Mono', monospace;
      font-weight: 800;
      font-size: 0.85rem;
      padding: 10px 18px;
      border: 2px solid var(--ink);
      border-radius: 10px;
      cursor: pointer;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      box-shadow: 3px 3px 0 var(--ink);
    }
    .btn-print { background: var(--lime); color: var(--ink); }
    .btn-back { background: var(--white); color: var(--ink); }

    .report-card {
      max-width: 1000px;
      margin: 0 auto;
      background: var(--white);
      border: 3px solid var(--ink);
      border-radius: 18px;
      box-shadow: 8px 8px 0 var(--ink);
      padding: 30px;
    }

    .report-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding-bottom: 20px;
      border-bottom: 3px solid var(--ink);
      margin-bottom: 20px;
    }
    .brand-title {
      font-family: 'Syne', sans-serif;
      font-weight: 900;
      font-size: 1.5rem;
      color: var(--green);
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .stats-bar {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 12px;
      margin-bottom: 24px;
    }
    .stat-box {
      background: var(--cream);
      border: 2px solid var(--ink);
      border-radius: 12px;
      padding: 12px 16px;
    }
    .stat-label {
      font-family: 'DM Mono', monospace;
      font-size: 0.68rem;
      font-weight: 800;
      text-transform: uppercase;
      color: #5a7060;
    }
    .stat-val {
      font-weight: 900;
      font-size: 1.2rem;
      color: var(--ink);
      margin-top: 2px;
    }

    .table-report {
      width: 100%;
      border-collapse: collapse;
      font-size: 0.82rem;
    }
    .table-report th {
      font-family: 'DM Mono', monospace;
      font-weight: 900;
      text-transform: uppercase;
      background: var(--ink);
      color: var(--white);
      padding: 10px 12px;
      text-align: left;
    }
    .table-report td {
      padding: 10px 12px;
      border-bottom: 1px solid #d0e0d5;
      font-weight: 600;
    }

    .badge-tag {
      font-family: 'DM Mono', monospace;
      font-size: 0.65rem;
      font-weight: 800;
      padding: 2px 6px;
      border-radius: 4px;
      border: 1px solid var(--ink);
    }
    .bg-green { background: #dcfce7; color: #15803d; }
    .bg-coral { background: #fee2e2; color: #b91c1c; }
    .bg-lime { background: var(--lime); color: var(--ink); }
    .bg-sand { background: #fef3c7; color: #92400e; }

    @media print {
      body { background: white; padding: 0; }
      .print-toolbar { display: none !important; }
      .report-card { border: 2px solid black; box-shadow: none; max-width: 100%; border-radius: 0; padding: 15px; }
    }
  </style>
</head>
<body>

  <div class="print-toolbar">
    <a href="/pikvero/public/admin/bookings.php" class="btn-action btn-back">
      <i class="bi bi-arrow-left"></i> Back to Reservations
    </a>
    <button onclick="window.print()" class="btn-action btn-print">
      <i class="bi bi-printer-fill"></i> Print Summary Report
    </button>
  </div>

  <div class="report-card">
    <div class="report-header">
      <div>
        <div class="brand-title">
          <i class="bi bi-dribbble"></i> PIKVERO RESERVATIONS REPORT
        </div>
        <div style="font-family:'DM Mono',monospace; font-size:0.78rem; font-weight:800; color:#5a7060; margin-top:3px;">
          Generated on <?= date('F j, Y — g:i A') ?>
        </div>
      </div>
      <div style="text-align:right; font-family:'DM Mono',monospace; font-size:0.8rem; font-weight:800;">
        FILTER: <?= strtoupper(htmlspecialchars($status)) ?><br>
        SEARCH: "<?= htmlspecialchars($search ?: 'ALL') ?>"
      </div>
    </div>

    <div class="stats-bar">
      <div class="stat-box">
        <div class="stat-label">Total Reservations</div>
        <div class="stat-val"><?= number_format($totalRecords) ?></div>
      </div>
      <div class="stat-box">
        <div class="stat-label">Active Gross Revenue</div>
        <div class="stat-val" style="color:var(--green);">₱<?= number_format($totalRevenue, 2) ?></div>
      </div>
      <div class="stat-box">
        <div class="stat-label">Report Status</div>
        <div class="stat-val">CONFIRMED RECORD</div>
      </div>
    </div>

    <table class="table-report">
      <thead>
        <tr>
          <th>Ref #</th>
          <th>Customer</th>
          <th>Court &amp; Facility</th>
          <th>Schedule</th>
          <th style="text-align:right;">Amount</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($records)): ?>
        <tr>
          <td colspan="6" style="text-align:center; padding:30px; color:#5a7060;">No reservations match the specified report criteria.</td>
        </tr>
        <?php else: ?>
          <?php foreach ($records as $b): ?>
          <tr>
            <td style="font-family:'DM Mono',monospace; font-weight:800;"><?= htmlspecialchars($b['booking_reference']) ?></td>
            <td>
              <strong><?= htmlspecialchars($b['customer_name']) ?></strong><br>
              <span style="font-size:0.7rem; color:#5a7060;"><?= htmlspecialchars($b['customer_email']) ?></span>
            </td>
            <td>
              <strong><?= htmlspecialchars($b['court_name']) ?></strong><br>
              <span style="font-size:0.7rem; color:#5a7060;"><?= htmlspecialchars($b['facility_name']) ?></span>
            </td>
            <td>
              <?= date('Y-m-d', strtotime($b['booking_date'])) ?><br>
              <span style="font-family:'DM Mono',monospace; font-size:0.72rem; color:#5a7060;">
                <?= date('g:i A', strtotime($b['start_time'])) ?> – <?= date('g:i A', strtotime($b['end_time'])) ?>
              </span>
            </td>
            <td style="text-align:right; font-family:'DM Mono',monospace; font-weight:900; color:var(--green);">
              ₱<?= number_format((float)$b['total_amount'], 2) ?>
            </td>
            <td>
              <span class="badge-tag <?= $b['booking_status'] === 'confirmed' ? 'bg-green' : ($b['booking_status'] === 'refunded' ? 'bg-sand' : 'bg-coral') ?>">
                <?= strtoupper($b['booking_status']) ?>
              </span>
              <span class="badge-tag <?= $b['payment_status'] === 'paid' ? 'bg-lime' : 'bg-sand' ?>">
                PAY: <?= strtoupper($b['payment_status']) ?>
              </span>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <script>
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('autoprint') === '1') {
      window.onload = () => { window.print(); };
    }
  </script>
</body>
</html>
