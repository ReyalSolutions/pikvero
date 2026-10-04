<?php
require_once __DIR__ . '/../app/bootstrap.php';
use App\Infrastructure\Repositories\PaymentRepository;
use App\Core\Auth\Auth;

if (!Auth::check()) {
    header('Location: /pikvero/public/login.php');
    exit;
}

$paymentId = (int)($_GET['payment_id'] ?? 0);
if (!$paymentId) {
    die("Invalid Payment ID.");
}

$paymentRepo = new PaymentRepository();
$orgId = Auth::hasRole('super_admin', 'platform_admin') ? null : Auth::organizationId();
$type = $_GET['type'] ?? null;
$d = $paymentRepo->getPaymentDetail($paymentId, $orgId, $type);

if (!$d) {
    die("Payment transaction not found or access denied.");
}

if (($d['record_type'] ?? '') === 'open_play') {
    header('Location: /pikvero/public/open-play-receipt.php?registration_id=' . $d['payment_id'] . '&from=admin_bookings');
    exit;
}

$st = strtolower($d['payment_status'] ?? 'completed');
$bkSt = strtolower($d['booking_payment_status'] ?? '');
$isRefunded = ($st === 'failed' || $bkSt === 'refunded');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Receipt #<?= htmlspecialchars($d['transaction_reference'] ?: 'PAY-' . $d['payment_id']) ?> — Pikvero</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Mono:ital,wght@0,400;0,500;1,400&family=Plus+Jakarta+Sans:wght@700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  
  <style>
    :root {
      --ink: #0d211d;
      --paper: #fbfbf9;
      --line: #0d211d;
      --coral: #ff6f59;
      --lime: #eafc8d;
    }

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      background: #eef2f1;
      font-family: 'DM Mono', monospace;
      color: var(--ink);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: flex-start;
      min-height: 100vh;
      padding: 20px;
    }

    .no-print-bar {
      width: min(380px, 100%);
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 16px;
    }

    .btn-action {
      font-family: 'Plus Jakarta Sans', sans-serif;
      font-weight: 800;
      font-size: 0.8rem;
      text-transform: uppercase;
      padding: 10px 18px;
      border: 2px solid var(--ink);
      border-radius: 10px;
      cursor: pointer;
      box-shadow: 3px 3px 0 var(--ink);
      transition: all 0.15s ease;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }

    .btn-action.lime { background: var(--lime); color: var(--ink); }
    .btn-action.sand { background: #f0eee6; color: var(--ink); }
    .btn-action:hover { transform: translateY(-2px); }

    /* Compact Thermal Receipt Card */
    .receipt-card {
      width: 360px;
      max-width: 100%;
      background: var(--paper);
      border: 2px solid var(--ink);
      border-radius: 12px;
      padding: 24px 20px;
      box-shadow: 6px 6px 0 var(--ink);
      position: relative;
    }

    .receipt-header {
      text-align: center;
      margin-bottom: 16px;
      padding-bottom: 12px;
      border-bottom: 2px dashed var(--line);
    }

    .brand-title {
      font-family: 'Plus Jakarta Sans', sans-serif;
      font-size: 1.4rem;
      font-weight: 800;
      letter-spacing: 1px;
      text-transform: uppercase;
    }

    .venue-subtitle {
      font-size: 0.78rem;
      font-weight: 500;
      margin-top: 2px;
    }

    .meta-address {
      font-size: 0.68rem;
      color: #4a5c56;
      margin-top: 2px;
    }

    .dashed-divider {
      border-top: 1px dashed var(--line);
      margin: 12px 0;
    }

    .double-divider {
      border-top: 3px double var(--line);
      margin: 14px 0;
    }

    .info-row {
      display: flex;
      justify-content: space-between;
      font-size: 0.75rem;
      margin-bottom: 4px;
    }

    .info-label {
      color: #4a5c56;
    }

    .info-value {
      font-weight: 700;
      text-align: right;
    }

    .items-table {
      width: 100%;
      font-size: 0.74rem;
      border-collapse: collapse;
      margin: 10px 0;
    }

    .items-table th {
      text-align: left;
      font-weight: 700;
      padding-bottom: 4px;
      border-bottom: 1px solid var(--line);
    }

    .items-table td {
      padding: 6px 0;
      vertical-align: top;
    }

    .total-banner {
      background: var(--ink);
      color: var(--paper);
      border-radius: 8px;
      padding: 12px;
      text-align: center;
      margin-top: 12px;
    }

    .total-banner.refunded {
      background: var(--coral);
      color: #fff;
    }

    .total-label {
      font-size: 0.68rem;
      letter-spacing: 1px;
      text-transform: uppercase;
    }

    .total-amount {
      font-size: 1.6rem;
      font-weight: 800;
      font-family: 'Plus Jakarta Sans', sans-serif;
      margin-top: 2px;
    }

    .receipt-footer {
      text-align: center;
      font-size: 0.7rem;
      margin-top: 16px;
      padding-top: 12px;
      border-top: 2px dashed var(--line);
    }

    .barcode-graphic {
      margin: 12px auto 6px;
      letter-spacing: 4px;
      font-size: 1.2rem;
      font-weight: 800;
      user-select: none;
    }

    /* Print CSS */
    @media print {
      body {
        background: none;
        padding: 0;
      }
      .no-print-bar {
        display: none !important;
      }
      .receipt-card {
        border: none;
        box-shadow: none;
        width: 100%;
        max-width: 80mm;
        padding: 10px;
        border-radius: 0;
      }
    }

    /* Mobile Responsive CSS */
    @media (max-width: 576px) {
      body {
        padding: 10px;
      }
      .no-print-bar {
        width: 100%;
        flex-direction: column;
        gap: 10px;
        align-items: stretch;
      }
      .btn-group-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        width: 100%;
        justify-content: space-between;
      }
      .btn-action {
        flex: 1 1 auto;
        justify-content: center;
        padding: 8px 10px;
        font-size: 0.72rem;
        text-align: center;
      }
      .receipt-card {
        width: 100%;
        padding: 18px 14px;
        box-shadow: 4px 4px 0 var(--ink);
      }
      .info-row {
        flex-wrap: wrap;
        word-break: break-word;
      }
      .info-value {
        word-break: break-word;
      }
    }
  </style>
</head>
<body>

  <div class="no-print-bar">
    <button onclick="window.close()" class="btn-action sand"><i class="bi bi-x-lg"></i> Close</button>
    <div class="btn-group-actions" style="display:flex; gap:8px;">
      <button id="btn-download-img" onclick="downloadImage()" class="btn-action sky" style="background:#e0f2fe; color:#0369a1; border-color:#0284c7;"><i class="bi bi-file-image-fill"></i> PNG</button>
      <button id="btn-download-pdf" onclick="downloadPDF()" class="btn-action coral" style="background:var(--coral); color:#fff;"><i class="bi bi-download"></i> PDF</button>
      <button onclick="window.print()" class="btn-action lime"><i class="bi bi-printer-fill"></i> Print</button>
    </div>
  </div>

  <div class="receipt-card">
    <div class="receipt-header">
      <div class="brand-title">PIKVERO</div>
      <div class="venue-subtitle"><?= htmlspecialchars($d['facility_name'] ?: 'Main Court Network') ?></div>
      <div class="meta-address"><?= htmlspecialchars(($d['facility_address'] ?? '') ?: 'Tagbilaran City, Bohol') ?></div>
      <?php if (!empty($d['organization_tax_id'])): ?>
        <div class="meta-address">TAX ID: <?= htmlspecialchars($d['organization_tax_id']) ?></div>
      <?php endif; ?>
    </div>

    <div class="info-row">
      <span class="info-label">RECEIPT NO:</span>
      <span class="info-value"><?= htmlspecialchars(($d['transaction_reference'] ?? '') ?: 'PAY-' . $d['payment_id']) ?></span>
    </div>

    <div class="info-row">
      <span class="info-label">BOOKING REF:</span>
      <span class="info-value">#<?= htmlspecialchars($d['booking_reference'] ?? '') ?></span>
    </div>

    <div class="info-row">
      <span class="info-label">DATE RECORDED:</span>
      <span class="info-value"><?= htmlspecialchars(($d['created_at'] ?? '') ?: ($d['payment_date'] ?? date('Y-m-d H:i:s'))) ?></span>
    </div>

    <div class="info-row">
      <span class="info-label">CUSTOMER:</span>
      <span class="info-value"><?= htmlspecialchars(!empty($d['first_name']) ? $d['first_name'] . ' ' . ($d['last_name'] ?? '') : ($d['customer_name'] ?? 'Guest Player')) ?></span>
    </div>

    <div class="info-row">
      <span class="info-label">PAYMENT METHOD:</span>
      <span class="info-value" style="color:var(--ink); font-weight:800;"><?= htmlspecialchars(strtoupper(($d['payment_method'] ?? 'cash') === 'cash' ? 'Cash (Pay at Counter)' : ($d['payment_method'] ?? 'GCash / PayMongo Online'))) ?></span>
    </div>

    <div class="dashed-divider"></div>

    <table class="items-table">
      <thead>
        <tr>
          <th>ITEM / COURT</th>
          <th style="text-align:center;">QTY</th>
          <th style="text-align:right;">AMOUNT</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>
            <strong><?= htmlspecialchars($d['court_name'] ?? 'Court') ?></strong>
            <div style="font-size:0.65rem; color:#4a5c56;">
              <?= htmlspecialchars($d['booking_date'] ?? '') ?><br>
              <?= htmlspecialchars($d['start_time'] ?? '') ?> - <?= htmlspecialchars($d['end_time'] ?? '') ?>
            </div>
          </td>
          <td style="text-align:center;"><?= number_format((float)($d['duration_hours'] ?? 1), 1) ?>h</td>
          <td style="text-align:right; font-weight:700;">₱<?= number_format((float)($d['amount'] ?? 0), 2) ?></td>
        </tr>
      </tbody>
    </table>

    <div class="dashed-divider"></div>

    <div class="info-row">
      <span class="info-label">HOURLY RATE:</span>
      <span class="info-value">₱<?= number_format((float)($d['rate_per_hour'] ?? $d['amount'] ?? 0), 2) ?></span>
    </div>

    <div class="info-row">
      <span class="info-label">SUBTOTAL:</span>
      <span class="info-value">₱<?= number_format((float)$d['amount'], 2) ?></span>
    </div>

    <div class="info-row">
      <span class="info-label">TAX / FEES:</span>
      <span class="info-value">₱0.00</span>
    </div>

    <div class="info-row">
      <span class="info-label">PAYMENT METHOD:</span>
      <span class="info-value" style="text-transform:uppercase;"><?= htmlspecialchars($d['payment_method']) ?></span>
    </div>

    <div class="info-row">
      <span class="info-label">STATUS:</span>
      <span class="info-value" style="text-transform:uppercase;"><?= $isRefunded ? 'REFUNDED' : 'PAID' ?></span>
    </div>

    <div class="total-banner <?= $isRefunded ? 'refunded' : '' ?>">
      <div class="total-label"><?= $isRefunded ? 'TOTAL REFUNDED' : 'TOTAL PAID' ?></div>
      <div class="total-amount">₱<?= number_format((float)$d['amount'], 2) ?></div>
    </div>

    <div class="barcode-graphic">||||||||||||||||||||||</div>

    <div class="receipt-footer">
      <div>THANK YOU FOR PLAYING AT PIKVERO!</div>
      <div style="font-size:0.65rem; color:#4a5c56; margin-top:2px;">www.pikvero.com • Serve With Passion</div>
    </div>
  </div>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
  <script>
    function downloadImage() {
      const btn = document.getElementById('btn-download-img');
      const origText = btn ? btn.innerHTML : '';
      if (btn) {
        btn.disabled = true;
        btn.innerHTML = `<i class="bi bi-hourglass-split"></i> Generating...`;
      }

      const element = document.querySelector('.receipt-card');
      html2canvas(element, {
        scale: 3,
        useCORS: true,
        backgroundColor: '#fbfbf9',
        logging: false
      }).then(canvas => {
        const link = document.createElement('a');
        link.download = 'pikvero_receipt_<?= preg_replace('/[^a-zA-Z0-9_-]/', '', $d['booking_reference'] ?: $d['payment_id']) ?>.png';
        link.href = canvas.toDataURL('image/png', 1.0);
        link.click();
        if (btn) {
          btn.disabled = false;
          btn.innerHTML = origText;
        }
      }).catch(err => {
        console.error('Image generation error:', err);
        if (btn) {
          btn.disabled = false;
          btn.innerHTML = origText;
        }
      });
    }

    function downloadPDF() {
      const btn = document.getElementById('btn-download-pdf');
      const origText = btn ? btn.innerHTML : '';
      if (btn) {
        btn.disabled = true;
        btn.innerHTML = `<i class="bi bi-hourglass-split"></i> Generating...`;
      }

      const element = document.querySelector('.receipt-card');
      const opt = {
        margin:       [8, 8, 8, 8],
        filename:     'pikvero_receipt_<?= preg_replace('/[^a-zA-Z0-9_-]/', '', $d['booking_reference'] ?: $d['payment_id']) ?>.pdf',
        image:        { type: 'jpeg', quality: 0.98 },
        html2canvas:  { scale: 2, useCORS: true, logging: false },
        jsPDF:        { unit: 'mm', format: [100, 210], orientation: 'portrait' }
      };

      html2pdf().set(opt).from(element).save().then(() => {
        if (btn) {
          btn.disabled = false;
          btn.innerHTML = origText;
        }
      }).catch(err => {
        console.error('PDF generation error:', err);
        if (btn) {
          btn.disabled = false;
          btn.innerHTML = origText;
        }
        window.print();
      });
    }

    // Auto-trigger print if requested via query param ?autoprint=1
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('autoprint') === '1') {
      window.onload = function() {
        setTimeout(() => window.print(), 300);
      };
    }
  </script>
</body>
</html>
