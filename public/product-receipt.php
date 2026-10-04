<?php
require_once __DIR__ . '/../app/bootstrap.php';
use App\Core\Database\Connection;
use App\Core\Auth\Auth;

if (!Auth::check()) {
    header('Location: /pikvero/public/login.php');
    exit;
}

$saleId = (int)($_GET['sale_id'] ?? 0);
if (!$saleId) {
    die("Invalid Sale Receipt ID.");
}

$db = Connection::getInstance();
$sql = "SELECT s.*, p.name AS product_name, p.category, p.type, f.name AS facility_name, f.address AS facility_address, f.city AS facility_city, f.phone AS facility_phone
        FROM product_sales s
        JOIN products p ON s.product_id = p.id
        LEFT JOIN facilities f ON s.facility_id = f.id
        WHERE s.id = ? LIMIT 1";

$d = $db->selectOne($sql, [$saleId], 'i');
if (!$d) {
    die("Product sales receipt transaction not found.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Receipt #SALE-<?= sprintf('%05d', $d['id']) ?> — Pikvero Shop</title>
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
      --lime: #dfff4f;
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
      width: 380px;
      max-width: 100%;
      background: var(--paper);
      border: 2px solid var(--ink);
      border-radius: 16px;
      box-shadow: 6px 6px 0 var(--ink);
      padding: 24px;
      position: relative;
    }

    .shop-header {
      text-align: center;
      border-bottom: 2px dashed var(--line);
      padding-bottom: 16px;
      margin-bottom: 16px;
    }

    .shop-title {
      font-family: 'Plus Jakarta Sans', sans-serif;
      font-weight: 800;
      font-size: 1.3rem;
      text-transform: uppercase;
      letter-spacing: -0.02em;
    }

    .receipt-badge {
      display: inline-block;
      background: var(--lime);
      border: 1.5px solid var(--ink);
      border-radius: 6px;
      font-weight: 700;
      font-size: 0.68rem;
      padding: 2px 8px;
      margin-top: 4px;
    }

    .info-row {
      display: flex;
      justify-content: space-between;
      font-size: 0.78rem;
      margin-bottom: 6px;
    }

    .divider-line {
      border-top: 2px dashed var(--line);
      margin: 14px 0;
    }

    .item-row {
      display: flex;
      justify-content: space-between;
      font-size: 0.84rem;
      font-weight: 500;
      margin-bottom: 8px;
    }

    .total-card {
      background: #f4f3eb;
      border: 2px solid var(--ink);
      border-radius: 10px;
      padding: 12px;
      margin-top: 14px;
    }

    .total-row {
      display: flex;
      justify-content: space-between;
      font-size: 1.1rem;
      font-weight: 700;
      font-family: 'Plus Jakarta Sans', sans-serif;
    }

    .receipt-footer {
      text-align: center;
      font-size: 0.72rem;
      color: #555;
      margin-top: 20px;
      line-height: 1.4;
    }

    @media print {
      body {
        background: none;
        padding: 0;
      }
      .no-print-bar {
        display: none !important;
      }
      .receipt-card {
        box-shadow: none;
        border: none;
        width: 100%;
        padding: 10px;
      }
    }

    /* Mobile Responsive CSS */
    @media (max-width: 576px) {
      body { padding: 10px; }
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
    }
  </style>
</head>
<body>

  <div class="no-print-bar">
    <button onclick="handleBack()" class="btn-action sand"><i class="bi bi-arrow-left"></i> Back</button>
    <div class="btn-group-actions" style="display:flex; gap:8px;">
      <button id="btn-download-img" onclick="downloadImage()" class="btn-action sky" style="background:#e0f2fe; color:#0369a1; border-color:#0284c7;"><i class="bi bi-file-image-fill"></i> PNG</button>
      <button id="btn-download-pdf" onclick="downloadPDF()" class="btn-action coral" style="background:var(--coral); color:#fff;"><i class="bi bi-download"></i> PDF</button>
      <button onclick="window.print()" class="btn-action lime"><i class="bi bi-printer-fill"></i> Print</button>
    </div>
  </div>

  <div class="receipt-card">
    <div class="shop-header">
      <div style="display:flex; justify-content:center; align-items:center; gap:6px; margin-bottom:4px;">
        <i class="bi bi-shop" style="font-size:1.3rem;"></i>
        <div class="shop-title">PIKVERO PRO SHOP</div>
      </div>
      <div style="font-size:0.75rem; color:#4a5c56;">
        <?= htmlspecialchars($d['facility_name'] ?: 'Official Pickleball Facility') ?><br>
        <?= htmlspecialchars($d['facility_address'] ?: 'Bohol, Philippines') ?>
      </div>
      <div class="receipt-badge">SALES RECEIPT</div>
    </div>

    <div class="info-row">
      <span>RECEIPT NO:</span>
      <strong>#SALE-<?= sprintf('%05d', $d['id']) ?></strong>
    </div>
    <div class="info-row">
      <span>DATE &amp; TIME:</span>
      <span><?= date('M d, Y • h:i A', strtotime($d['sale_date'])) ?></span>
    </div>
    <div class="info-row">
      <span>CUSTOMER:</span>
      <strong><?= htmlspecialchars($d['customer_name']) ?></strong>
    </div>

    <div class="divider-line"></div>

    <div style="font-size:0.72rem; font-weight:700; color:#4a5c56; margin-bottom:8px; text-transform:uppercase;">PURCHASED ITEMS</div>

    <div class="item-row">
      <div>
        <strong style="font-family:'Plus Jakarta Sans', sans-serif; text-transform:uppercase; font-size:0.88rem;"><?= htmlspecialchars($d['product_name']) ?></strong><br>
        <span style="font-size:0.74rem; color:#555;"><?= htmlspecialchars($d['category']) ?> (<?= strtoupper($d['type']) ?>)</span>
      </div>
      <div style="text-align:right;">
        <strong>₱<?= number_format($d['total_amount'], 2) ?></strong><br>
        <span style="font-size:0.74rem; color:#555;"><?= (int)$d['quantity'] ?> x ₱<?= number_format($d['unit_price'], 2) ?></span>
      </div>
    </div>

    <div class="divider-line"></div>

    <div class="info-row">
      <span>PAYMENT METHOD:</span>
      <strong style="text-transform:uppercase; color:var(--ink);"><?= htmlspecialchars($d['payment_method']) ?></strong>
    </div>

    <div class="total-card">
      <div class="total-row">
        <span>TOTAL PAID:</span>
        <span style="color:#0b4d40;">₱<?= number_format($d['total_amount'], 2) ?></span>
      </div>
    </div>

    <div class="receipt-footer">
      Thank you for playing at Pikvero!<br>
      Keep this receipt for equipment warranty &amp; records.<br>
      *** OFFICIAL SHOP RECEIPT ***
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
        link.download = 'pikvero_shop_receipt_<?= sprintf('%05d', $d['id']) ?>.png';
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
        filename:     'pikvero_shop_receipt_<?= sprintf('%05d', $d['id']) ?>.pdf',
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

    function handleBack() {
      if (window.opener) {
        window.close();
      } else if (document.referrer && document.referrer.includes('/pikvero/')) {
        window.history.back();
      } else {
        window.location.href = '/pikvero/public/admin/products.php';
      }
    }
  </script>
</body>
</html>
