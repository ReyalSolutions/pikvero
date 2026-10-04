<?php
require_once __DIR__ . '/../app/bootstrap.php';
use App\Core\Database\Connection;
use App\Core\Auth\Auth;

if (!Auth::check()) {
    header('Location: /pikvero/public/login.php');
    exit;
}

$db = Connection::getInstance();
$userId = Auth::id();
$orgId = Auth::hasRole('super_admin', 'platform_admin') ? (int)($_GET['org_id'] ?? 0) : Auth::organizationId();

if (!$orgId && $userId) {
    $orgRow = $db->selectOne("SELECT id FROM organizations WHERE owner_id = ? LIMIT 1", [$userId]);
    if ($orgRow) $orgId = (int)$orgRow['id'];
}

if (!$orgId) {
    die("Organization profile not found.");
}

// Fetch Organization & Owner details
$org = $db->selectOne("
    SELECT o.*, u.first_name, u.last_name, u.email AS owner_email, u.phone AS owner_phone 
    FROM organizations o 
    LEFT JOIN users u ON o.owner_id = u.id 
    WHERE o.id = ? 
    LIMIT 1
", [$orgId], 'i');

// Fetch Active Subscription & Plan
$sub = $db->selectOne("
    SELECT s.*, sp.name AS plan_name, sp.monthly_price, sp.yearly_price 
    FROM subscriptions s 
    LEFT JOIN subscription_plans sp ON s.plan_id = sp.id 
    WHERE s.organization_id = ? 
    ORDER BY s.id DESC LIMIT 1
", [$orgId], 'i');

// Fetch All Subscription Payment History
$subId = (int)($sub['id'] ?? 0);
$payments = [];
if ($subId > 0) {
    $payments = $db->select("
        SELECT * FROM subscription_payments 
        WHERE subscription_id = ? 
        ORDER BY id ASC
    ", [$subId], 'i');
}

// Calculate summary totals
$totalPaid = 0.00;
$totalCharges = 0.00;
foreach ($payments as $p) {
    $amt = (float)$p['amount'];
    if (strtolower($p['payment_status']) === 'paid' || strtolower($p['payment_status']) === 'completed') {
        $totalPaid += $amt;
        $totalCharges += $amt;
    }
}

if (empty($payments) && $sub) {
    // If trial or active without explicit payments row
    $isYearly = ($sub['billing_cycle'] === 'yearly');
    $rate = $isYearly ? (float)$sub['yearly_price'] : (float)$sub['monthly_price'];
    $totalPaid = $rate;
    $totalCharges = $rate;
}

$statementRef = 'STMT-' . date('Ym') . '-' . str_pad($orgId, 4, '0', STR_PAD_LEFT);
$statementDate = date('F d, Y');
$periodStart = date('M 01, Y');
$periodEnd = date('M t, Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Official Billing Statement — <?= htmlspecialchars($org['name'] ?? 'Organization') ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500;700;800&family=Plus+Jakarta+Sans:wght@700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

  <style>
    :root {
      --ink: #0d211d;
      --paper: #ffffff;
      --sand: #f8faf9;
      --line: #0d211d;
      --green: #10b981;
      --lime: #eafc8d;
      --coral: #ff6f59;
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
      padding: 24px;
      display: flex;
      flex-direction: column;
      align-items: center;
    }

    /* Action bar */
    .action-bar {
      width: min(850px, 100%);
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
    }

    .btn-btn {
      font-family: 'Plus Jakarta Sans', sans-serif;
      font-weight: 800;
      font-size: 0.82rem;
      text-transform: uppercase;
      padding: 10px 20px;
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
    .btn-btn.lime { background: var(--lime); color: var(--ink); }
    .btn-btn.sand { background: #ffffff; color: var(--ink); }
    .btn-btn:hover { transform: translateY(-2px); }

    /* Statement Container Sheet */
    .statement-sheet {
      width: 850px;
      max-width: 100%;
      background: var(--paper);
      border: 3px solid var(--ink);
      border-radius: 16px;
      padding: 40px;
      box-shadow: 8px 8px 0 var(--ink);
      position: relative;
    }

    /* Header */
    .stmt-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      border-bottom: 3px solid var(--ink);
      padding-bottom: 20px;
      margin-bottom: 24px;
    }

    .brand-title {
      font-family: 'Plus Jakarta Sans', sans-serif;
      font-size: 1.6rem;
      font-weight: 900;
      text-transform: uppercase;
      letter-spacing: 1px;
    }

    .brand-sub {
      font-size: 0.78rem;
      color: #4a5c56;
      margin-top: 4px;
    }

    .stmt-meta-box {
      text-align: right;
    }

    .stmt-title {
      font-family: 'Plus Jakarta Sans', sans-serif;
      font-size: 1.2rem;
      font-weight: 900;
      color: #2563eb;
      text-transform: uppercase;
    }

    .meta-line {
      font-size: 0.78rem;
      margin-top: 3px;
    }

    /* Two Column Address & Account Info */
    .info-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 20px;
      margin-bottom: 24px;
    }

    .info-card {
      background: var(--sand);
      border: 2px solid var(--ink);
      border-radius: 12px;
      padding: 16px 20px;
    }

    .info-card-title {
      font-size: 0.7rem;
      font-weight: 800;
      color: #4a5c56;
      text-transform: uppercase;
      margin-bottom: 6px;
      border-bottom: 1px dashed var(--ink);
      padding-bottom: 4px;
    }

    .info-name {
      font-family: 'Plus Jakarta Sans', sans-serif;
      font-size: 1.1rem;
      font-weight: 800;
      margin-bottom: 4px;
    }

    .info-detail {
      font-size: 0.78rem;
      color: #334155;
      line-height: 1.4;
    }

    /* Bank Account Financial Summary Grid */
    .bank-summary-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 12px;
      background: var(--ink);
      color: #ffffff;
      border-radius: 12px;
      padding: 16px 20px;
      margin-bottom: 28px;
    }

    .summary-item {
      display: flex;
      flex-direction: column;
    }

    .summary-label {
      font-size: 0.65rem;
      color: #a3b8b0;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .summary-val {
      font-family: 'Plus Jakarta Sans', sans-serif;
      font-size: 1.25rem;
      font-weight: 800;
      margin-top: 4px;
    }

    .summary-val.highlight {
      color: var(--lime);
    }

    /* Statement Table */
    .stmt-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 28px;
    }

    .stmt-table th {
      background: var(--sand);
      border-top: 2px solid var(--ink);
      border-bottom: 2px solid var(--ink);
      font-size: 0.72rem;
      font-weight: 800;
      text-align: left;
      padding: 10px 12px;
    }

    .stmt-table td {
      border-bottom: 1px solid #e2e8f0;
      font-size: 0.78rem;
      padding: 12px;
      vertical-align: middle;
    }

    .stmt-table tr:hover {
      background: #f1f5f9;
    }

    .badge-tag {
      font-size: 0.68rem;
      padding: 3px 8px;
      border-radius: 6px;
      border: 1px solid var(--ink);
      font-weight: 800;
      display: inline-block;
    }
    .badge-tag.lime { background: var(--lime); }
    .badge-tag.coral { background: #ffd9d9; color: #dc2626; }

    /* Footer & Watermark Stamp */
    .stmt-footer {
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-top: 2px dashed var(--ink);
      padding-top: 20px;
      margin-top: 20px;
    }

    .paid-stamp {
      border: 3px solid #10b981;
      color: #10b981;
      border-radius: 12px;
      padding: 8px 18px;
      font-family: 'Plus Jakarta Sans', sans-serif;
      font-size: 1.1rem;
      font-weight: 900;
      text-transform: uppercase;
      letter-spacing: 2px;
      transform: rotate(-4deg);
      display: inline-block;
    }

    .barcode-area {
      text-align: right;
    }

    .barcode-lines {
      font-size: 1.4rem;
      font-weight: 800;
      letter-spacing: 5px;
    }

    /* Print styling */
    @media print {
      body {
        background: none;
        padding: 0;
      }
      .action-bar {
        display: none !important;
      }
      .statement-sheet {
        border: none;
        box-shadow: none;
        width: 100%;
        padding: 0;
      }
    }

    /* Mobile Responsive styling */
    @media (max-width: 768px) {
      body {
        padding: 12px;
      }
      .action-bar {
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
      .btn-btn {
        flex: 1 1 auto;
        justify-content: center;
        padding: 8px 12px;
        font-size: 0.72rem;
        text-align: center;
      }
      .statement-sheet {
        width: 100%;
        padding: 20px 14px;
        border-radius: 12px;
        box-shadow: 4px 4px 0 var(--ink);
      }
      .stmt-header {
        flex-direction: column;
        gap: 12px;
        align-items: stretch;
      }
      .stmt-meta-box {
        text-align: left;
      }
      .info-grid {
        grid-template-columns: 1fr;
        gap: 12px;
      }
      .bank-summary-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
        padding: 12px;
      }
      .stmt-table th, .stmt-table td {
        padding: 8px 6px;
        font-size: 0.7rem;
      }
      .stmt-footer {
        flex-direction: column;
        gap: 16px;
        align-items: center;
        text-align: center;
      }
      .barcode-area {
        text-align: center;
      }
    }
  </style>
</head>
<body>

  <!-- ACTION BAR -->
  <div class="action-bar">
    <a href="/pikvero/public/owner/my-plan.php" class="btn-btn sand"><i class="bi bi-arrow-left"></i> Back to My Plan</a>
    <div class="btn-group-actions" style="display:flex; gap:8px;">
      <button id="btn-download-img" onclick="downloadImage()" class="btn-btn sky" style="background:#e0f2fe; color:#0369a1; border-color:#0284c7;"><i class="bi bi-file-image-fill"></i> PNG</button>
      <button id="btn-download-pdf" onclick="downloadPDF()" class="btn-btn coral" style="background:var(--coral); color:#fff;"><i class="bi bi-download"></i> PDF</button>
      <button onclick="window.print()" class="btn-btn lime"><i class="bi bi-printer-fill"></i> Print</button>
    </div>
  </div>

  <!-- STATEMENT SHEET -->
  <div class="statement-sheet">
    <!-- HEADER -->
    <div class="stmt-header">
      <div>
        <div class="brand-title">PIKVERO ENTERPRISE</div>
        <div class="brand-sub">Platform SaaS Billing &amp; Court Management Systems</div>
        <div class="brand-sub">www.pikvero.com • Billing Support: billing@pikvero.com</div>
      </div>

      <div class="stmt-meta-box">
        <div class="stmt-title">STATEMENT OF ACCOUNT</div>
        <div class="meta-line">Ref: <strong style="color:#2563eb;"><?= htmlspecialchars($statementRef) ?></strong></div>
        <div class="meta-line">Date: <strong><?= htmlspecialchars($statementDate) ?></strong></div>
      </div>
    </div>

    <!-- TWO COLUMN ADDRESS & ACCOUNT INFO -->
    <div class="info-grid">
      <div class="info-card">
        <div class="info-card-title">ACCOUNT / TENANT DETAILS</div>
        <div class="info-name"><?= htmlspecialchars($org['name'] ?? 'Facility Organization') ?></div>
        <div class="info-detail">Owner: <?= htmlspecialchars($org['first_name'] ? $org['first_name'] . ' ' . $org['last_name'] : 'Court Owner') ?></div>
        <div class="info-detail">Email: <?= htmlspecialchars($org['owner_email'] ?: 'No email on record') ?></div>
        <div class="info-detail">Phone: <?= htmlspecialchars($org['owner_phone'] ?: 'N/A') ?></div>
        <div class="info-detail">Tax ID / Reg: <?= htmlspecialchars($org['tax_id'] ?: 'DTI/SEC Registered') ?></div>
      </div>

      <div class="info-card">
        <div class="info-card-title">BILLING SUMMARY</div>
        <div class="info-name"><?= htmlspecialchars($sub['plan_name'] ?? 'Pro Tier') ?></div>
        <div class="info-detail">Cycle: <strong><?= strtoupper(htmlspecialchars($sub['billing_cycle'] ?? 'monthly')) ?></strong></div>
        <div class="info-detail">Status: <strong style="color:var(--green);"><?= strtoupper(htmlspecialchars($sub['status'] ?? 'active')) ?></strong></div>
      </div>
    </div>

    <!-- BANK ACCOUNT FINANCIAL SUMMARY GRID -->
    <div class="bank-summary-grid">
      <div class="summary-item">
        <span class="summary-label">Opening Balance</span>
        <span class="summary-val">₱0.00</span>
      </div>
      <div class="summary-item">
        <span class="summary-label">Total Plan Charges</span>
        <span class="summary-val">₱<?= number_format($totalCharges, 2) ?></span>
      </div>
      <div class="summary-item">
        <span class="summary-label">Total Payments Received</span>
        <span class="summary-val highlight">₱<?= number_format($totalPaid, 2) ?></span>
      </div>
      <div class="summary-item">
        <span class="summary-label">Net Balance Due</span>
        <span class="summary-val highlight">₱0.00</span>
      </div>
    </div>

    <!-- ITEMIZED STATEMENT OF ACCOUNT TABLE -->
    <div style="font-size:0.8rem; font-weight:800; font-family:'DM Mono', monospace; margin-bottom:8px; text-transform:uppercase; color:var(--ink);">
      <i class="bi bi-list-task"></i> STATEMENT ACTIVITY &amp; PAYMENT HISTORY
    </div>

    <div style="width:100%; overflow-x:auto; -webkit-overflow-scrolling:touch;">
    <table class="stmt-table">
      <thead>
        <tr>
          <th>DATE</th>
          <th>REF NUMBER</th>
          <th>DESCRIPTION &amp; PAYMENT CHANNEL</th>
          <th style="text-align:right;">CHARGES</th>
          <th style="text-align:right;">PAYMENTS</th>
          <th style="text-align:right;">NET BALANCE</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($payments)): ?>
          <?php foreach ($payments as $p): ?>
            <?php 
              $pAmt = (float)$p['amount'];
              $isPaid = (strtolower($p['payment_status']) === 'paid' || strtolower($p['payment_status']) === 'completed');
            ?>
            <tr>
              <td><?= date('Y-m-d', strtotime($p['created_at'])) ?></td>
              <td><strong style="color:#2563eb;">SUB-<?= htmlspecialchars($p['id']) ?></strong></td>
              <td>
                <div><strong><?= htmlspecialchars($sub['plan_name'] ?? 'SaaS Subscription') ?> Renewal</strong></div>
                <div style="font-size:0.7rem; color:#4a5c56; margin-top:2px;">
                  Gateway: <?= htmlspecialchars($p['payment_method']) ?>
                  &bull; <span class="badge-tag <?= $isPaid ? 'lime' : 'coral' ?>"><?= strtoupper(htmlspecialchars($p['payment_status'])) ?></span>
                </div>
              </td>
              <td style="text-align:right;">₱<?= number_format($pAmt, 2) ?></td>
              <td style="text-align:right; font-weight:700; color:var(--green);">₱<?= number_format($isPaid ? $pAmt : 0, 2) ?></td>
              <td style="text-align:right; font-weight:800;">₱0.00</td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td><?= date('Y-m-d') ?></td>
            <td><strong style="color:#2563eb;">SUB-<?= htmlspecialchars($subId ?: '1') ?></strong></td>
            <td>
              <div><strong><?= htmlspecialchars($sub['plan_name'] ?? 'Pro Plan') ?> Subscription</strong></div>
              <div style="font-size:0.7rem; color:#4a5c56; margin-top:2px;">
                Cycle: <?= strtoupper(htmlspecialchars($sub['billing_cycle'] ?? 'monthly')) ?> &bull; <span class="badge-tag lime">PAID</span>
              </div>
            </td>
            <td style="text-align:right;">₱<?= number_format($totalPaid, 2) ?></td>
            <td style="text-align:right; font-weight:700; color:var(--green);">₱<?= number_format($totalPaid, 2) ?></td>
            <td style="text-align:right; font-weight:800;">₱0.00</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
    </div>

    <!-- FOOTER & STAMP -->
    <div class="stmt-footer">
      <div>
        <div class="paid-stamp"><i class="bi bi-patch-check-fill"></i> ACCOUNT PAID IN FULL</div>
        <div style="font-size:0.68rem; color:#4a5c56; margin-top:8px;">
          This statement reflects all processed subscription transactions up to <?= htmlspecialchars($statementDate) ?>.
        </div>
      </div>

      <div class="barcode-area">
        <div class="barcode-lines">||||||||||||||||||||||||</div>
        <div style="font-size:0.68rem; color:#4a5c56; margin-top:2px;">OFFICIAL SAAS BILLING DOCUMENT</div>
      </div>
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

      const element = document.querySelector('.statement-sheet');
      html2canvas(element, {
        scale: 2,
        useCORS: true,
        backgroundColor: '#ffffff',
        logging: false
      }).then(canvas => {
        const link = document.createElement('a');
        link.download = 'pikvero_billing_statement_<?= preg_replace('/[^a-zA-Z0-9_-]/', '', $statementRef) ?>.png';
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

      const element = document.querySelector('.statement-sheet');
      const opt = {
        margin:       [10, 10, 10, 10],
        filename:     'pikvero_billing_statement_<?= preg_replace('/[^a-zA-Z0-9_-]/', '', $statementRef) ?>.pdf',
        image:        { type: 'jpeg', quality: 0.98 },
        html2canvas:  { scale: 2, useCORS: true, logging: false },
        jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
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

    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('autoprint') === '1') {
      window.onload = function() {
        setTimeout(() => window.print(), 300);
      };
    }
  </script>
</body>
</html>
