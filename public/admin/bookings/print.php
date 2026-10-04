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

$bookingId = (int)($_GET['id'] ?? 0);
if (!$bookingId) {
    die("Invalid reservation request ID.");
}

$bookingRepo = new BookingRepository();
$booking = $bookingRepo->getBookingDetail($bookingId);

if (!$booking) {
    die("Reservation record not found or has been removed.");
}

// Tenant Scoping for Court Owners
if (!in_array($role, ['super_admin', 'platform_admin'])) {
    $tenantOrgId = (int)($user['organization_id'] ?? 0);
    if ((int)$booking['organization_id'] !== $tenantOrgId) {
        header('Location: /pikvero/public/403.php');
        exit;
    }
}

$ref = $booking['booking_reference'];
$surfaceLabel = ucwords(str_replace('_', ' ', $booking['surface_type'] ?? 'cushioned_acrylic'));
$dateFormatted = date('F j, Y (l)', strtotime($booking['booking_date']));
$startTimeStr = date('g:i A', strtotime($booking['start_time']));
$endTimeStr = date('g:i A', strtotime($booking['end_time']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reservation Voucher #<?= htmlspecialchars($ref) ?> — Pikvero</title>
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
      padding: 30px 16px;
      display: flex;
      flex-direction: column;
      align-items: center;
    }
    .print-toolbar {
      width: 100%;
      max-width: 680px;
      margin-bottom: 20px;
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
      transition: all 0.15s;
    }
    .btn-print { background: var(--lime); color: var(--ink); }
    .btn-back { background: var(--white); color: var(--ink); }
    .btn-action:hover { transform: translate(-1px, -1px); box-shadow: 4px 4px 0 var(--ink); }

    /* Voucher Card Design */
    .voucher-card {
      width: 100%;
      max-width: 680px;
      background: var(--white);
      border: 3px solid var(--ink);
      border-radius: 20px;
      box-shadow: 8px 8px 0 var(--ink);
      overflow: hidden;
      position: relative;
    }
    .voucher-header {
      background: var(--ink);
      color: var(--white);
      padding: 26px 30px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-bottom: 3px solid var(--ink);
    }
    .brand-title {
      font-family: 'Syne', sans-serif;
      font-weight: 900;
      font-size: 1.4rem;
      letter-spacing: -0.02em;
      color: var(--lime);
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .ref-badge {
      font-family: 'DM Mono', monospace;
      background: rgba(255,255,255,0.12);
      border: 2px solid rgba(255,255,255,0.3);
      border-radius: 8px;
      padding: 6px 12px;
      font-size: 0.85rem;
      font-weight: 900;
      letter-spacing: 0.05em;
      color: var(--white);
    }
    .voucher-body { padding: 30px; }

    .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px; }
    .info-box {
      background: #f8fbf9;
      border: 2px solid #d0e0d5;
      border-radius: 12px;
      padding: 12px 16px;
    }
    .info-label {
      font-family: 'DM Mono', monospace;
      font-size: 0.68rem;
      font-weight: 800;
      text-transform: uppercase;
      color: #5a7060;
      margin-bottom: 4px;
    }
    .info-val { font-weight: 800; font-size: 0.95rem; color: var(--ink); }

    .section-title {
      font-family: 'DM Mono', monospace;
      font-size: 0.75rem;
      font-weight: 900;
      text-transform: uppercase;
      color: #5a7060;
      margin: 24px 0 10px;
      padding-bottom: 4px;
      border-bottom: 2px dashed #d0e0d5;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .table-calc {
      width: 100%;
      border-collapse: collapse;
      margin-top: 10px;
      font-size: 0.9rem;
    }
    .table-calc th {
      font-family: 'DM Mono', monospace;
      font-size: 0.72rem;
      font-weight: 900;
      text-transform: uppercase;
      background: var(--sand);
      padding: 8px 12px;
      text-align: left;
      border: 1.5px solid var(--ink);
    }
    .table-calc td {
      padding: 10px 12px;
      border: 1.5px solid var(--ink);
      font-weight: 700;
    }

    .voucher-footer {
      background: var(--cream);
      border-top: 3px solid var(--ink);
      padding: 20px 30px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .badge-status {
      font-family: 'DM Mono', monospace;
      font-weight: 900;
      font-size: 0.75rem;
      padding: 4px 10px;
      border-radius: 6px;
      border: 1.5px solid var(--ink);
      text-transform: uppercase;
    }
    .badge-green { background: #dcfce7; color: #15803d; }
    .badge-coral { background: #fee2e2; color: #b91c1c; }
    .badge-lime { background: var(--lime); color: var(--ink); }
    .badge-sand { background: #fef3c7; color: #92400e; }

    /* Barcode Simulator */
    .barcode-sim {
      font-family: 'DM Mono', monospace;
      font-size: 1.6rem;
      letter-spacing: 4px;
      font-weight: 900;
      color: var(--ink);
      line-height: 1;
      opacity: 0.85;
    }

    @media print {
      body { background: white; padding: 0; }
      .print-toolbar { display: none !important; }
      .voucher-card { border: 2px solid black; box-shadow: none; max-width: 100%; border-radius: 0; }
    }
  </style>
</head>
<body>

  <!-- Print Control Bar -->
  <div class="print-toolbar">
    <a href="/pikvero/public/admin/bookings.php" class="btn-action btn-back">
      <i class="bi bi-arrow-left"></i> Back to Reservations
    </a>
    <button onclick="window.print()" class="btn-action btn-print">
      <i class="bi bi-printer-fill"></i> Print Official Voucher
    </button>
  </div>

  <!-- Printable Voucher Card -->
  <div class="voucher-card">
    <div class="voucher-header">
      <div>
        <div class="brand-title">
          <i class="bi bi-dribbble"></i> PIKVERO COURTS
        </div>
        <div style="font-size:0.75rem; opacity:0.8; margin-top:2px; font-family:'DM Mono',monospace;">OFFICIAL RESERVATION VOUCHER</div>
      </div>
      <div class="ref-badge">#<?= htmlspecialchars($ref) ?></div>
    </div>

    <div class="voucher-body">
      <!-- Customer & Status Section -->
      <div class="grid-2">
        <div class="info-box">
          <div class="info-label">Customer Name</div>
          <div class="info-val"><?= htmlspecialchars($booking['customer_name']) ?></div>
          <div style="font-size:0.75rem; color:#5a7060; margin-top:2px;"><?= htmlspecialchars($booking['customer_email']) ?></div>
        </div>
        <div class="info-box">
          <div class="info-label">Facility &amp; Location</div>
          <div class="info-val"><?= htmlspecialchars($booking['facility_name']) ?></div>
          <div style="font-size:0.75rem; color:#5a7060; margin-top:2px;"><?= htmlspecialchars($booking['facility_city'] ?? 'Bohol') ?></div>
        </div>
      </div>

      <!-- Schedule Breakdown -->
      <div class="section-title">
        <i class="bi bi-calendar-check-fill" style="color:var(--green);"></i> RESERVATION SCHEDULE
      </div>
      <div class="grid-2">
        <div class="info-box">
          <div class="info-label">Court Name &amp; Surface</div>
          <div class="info-val"><?= htmlspecialchars($booking['court_name']) ?></div>
          <div style="font-size:0.75rem; color:#5a7060; margin-top:2px;"><?= strtoupper($booking['court_type'] ?? 'indoor') ?> &bull; <?= htmlspecialchars($surfaceLabel) ?></div>
        </div>
        <div class="info-box">
          <div class="info-label">Date &amp; Time Window</div>
          <div class="info-val"><?= $dateFormatted ?></div>
          <div style="font-size:0.85rem; color:var(--green); font-family:'DM Mono',monospace; font-weight:800; margin-top:2px;">
            <?= $startTimeStr ?> – <?= $endTimeStr ?> (<?= $booking['duration_hours'] ?> hrs)
          </div>
        </div>
      </div>

      <!-- Financial Calculation -->
      <div class="section-title">
        <i class="bi bi-receipt-cutoff" style="color:var(--coral);"></i> FINANCIAL STATEMENT &amp; RECEIPT
      </div>
      <table class="table-calc">
        <thead>
          <tr>
            <th>Description</th>
            <th style="text-align:center;">Duration</th>
            <th style="text-align:right;">Hourly Rate</th>
            <th style="text-align:right;">Total Amount</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>Court Rental Fee — <?= htmlspecialchars($booking['court_name']) ?></td>
            <td style="text-align:center; font-family:'DM Mono',monospace;"><?= $booking['duration_hours'] ?> hrs</td>
            <td style="text-align:right; font-family:'DM Mono',monospace;">₱<?= number_format((float)$booking['rate_per_hour'], 2) ?></td>
            <td style="text-align:right; font-family:'DM Mono',monospace; color:var(--green); font-weight:900;">₱<?= number_format((float)$booking['total_amount'], 2) ?></td>
          </tr>
        </tbody>
      </table>

      <?php if (!empty($booking['notes'])): ?>
      <div style="margin-top:16px; background:#f4efe6; border:1.5px solid var(--ink); border-radius:10px; padding:10px 14px;">
        <div class="info-label" style="color:var(--ink);">Reservation Memo / Notes</div>
        <div style="font-size:0.85rem; font-weight:700;"><?= htmlspecialchars($booking['notes']) ?></div>
      </div>
      <?php endif; ?>
    </div>

    <!-- Voucher Footer -->
    <div class="voucher-footer">
      <div>
        <div style="font-size:0.7rem; font-family:'DM Mono',monospace; color:#5a7060; font-weight:800; text-transform:uppercase; margin-bottom:4px;">RESERVATION STATUS</div>
        <div style="display:flex; gap:6px;">
          <span class="badge-status <?= $booking['booking_status'] === 'confirmed' ? 'badge-green' : ($booking['booking_status'] === 'refunded' ? 'badge-sand' : 'badge-coral') ?>">
            <?= strtoupper($booking['booking_status']) ?>
          </span>
          <span class="badge-status <?= $booking['payment_status'] === 'paid' ? 'badge-lime' : 'badge-sand' ?>">
            PAYMENT: <?= strtoupper($booking['payment_status']) ?>
          </span>
        </div>
      </div>

      <div style="text-align:right;">
        <div class="barcode-sim">|||| ||| |||||||</div>
        <div style="font-family:'DM Mono',monospace; font-size:0.65rem; font-weight:800; color:#5a7060; margin-top:2px;">VERIFIED SYSTEM REF</div>
      </div>
    </div>
  </div>

  <script>
    // Auto trigger print dialog if ?autoprint=1
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('autoprint') === '1') {
      window.onload = () => { window.print(); };
    }
  </script>
</body>
</html>
