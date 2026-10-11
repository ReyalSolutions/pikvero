<?php
require_once __DIR__ . '/../app/bootstrap.php';
use App\Core\Database\Connection;
use App\Core\Auth\Auth;

if (!Auth::check()) {
    header('Location: /pikvero/public/login.php');
    exit;
}

$regId = (int)($_GET['registration_id'] ?? 0);
if (!$regId) {
    die("Invalid Open Play Registration ID.");
}

$db = Connection::getInstance();
$sql = "SELECT r.*, s.title AS session_title, s.session_date, s.start_time, s.end_time, s.fee_per_player,
               f.name AS facility_name, f.address AS facility_address, f.city AS facility_city, c.name AS court_name
        FROM open_play_registrations r
        JOIN open_play_sessions s ON r.session_id = s.id
        JOIN facilities f ON s.facility_id = f.id
        LEFT JOIN courts c ON c.id = s.court_id
        WHERE r.id = ? LIMIT 1";

$d = $db->selectOne($sql, [$regId], 'i');
if (!$d) {
    die("Open Play player registration receipt not found.");
}

$userRole = Auth::user()['role_name'] ?? 'customer';
$isAdmin = in_array($userRole, ['super_admin', 'court_owner', 'facility_manager', 'staff', 'platform_admin'], true);

$from = $_GET['from'] ?? '';
$defaultBackUrl = '/pikvero/public/customer/open-play.php';

if ($isAdmin) {
    $defaultBackUrl = '/pikvero/public/admin/bookings.php';
}

if ($from === 'admin_bookings' || $from === 'bookings') {
    $defaultBackUrl = '/pikvero/public/admin/bookings.php';
} elseif ($from === 'admin_open_play') {
    $defaultBackUrl = '/pikvero/public/admin/open-play.php';
} elseif ($from === 'customer_passes') {
    $defaultBackUrl = '/pikvero/public/customer/open-play.php';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Receipt #OP-REG-<?= sprintf('%05d', $d['id']) ?> — Open Play Ticket</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Mono:ital,wght@0,400;0,500;1,400&family=Plus+Jakarta+Sans:wght@700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    :root {
      --ink: #0d211d;
      --paper: #fbfbf9;
      --coral: #ff745c;
      --lime: #dfff4f;
    }

    * { box-sizing: border-box; margin: 0; padding: 0; }

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

    .receipt-card {
      width: 380px;
      max-width: 100%;
      background: var(--paper);
      border: 2px solid var(--ink);
      border-radius: 16px;
      box-shadow: 6px 6px 0 var(--ink);
      padding: 24px;
    }

    .shop-header {
      text-align: center;
      border-bottom: 2px dashed var(--ink);
      padding-bottom: 16px;
      margin-bottom: 16px;
    }

    .receipt-badge {
      display: inline-block;
      background: var(--coral);
      color: #fff;
      border: 1.5px solid var(--ink);
      border-radius: 6px;
      font-weight: 800;
      font-size: 0.68rem;
      padding: 3px 8px;
      margin-top: 4px;
    }

    .info-row {
      display: flex;
      justify-content: space-between;
      font-size: 0.78rem;
      margin-bottom: 6px;
    }

    .divider-line {
      border-top: 2px dashed var(--ink);
      margin: 14px 0;
    }

    .total-card {
      background: #fff3f0;
      border: 2px solid var(--ink);
      border-radius: 10px;
      padding: 12px;
      margin-top: 14px;
    }

    @media print {
      body { background: none; padding: 0; }
      .no-print-bar { display: none !important; }
      .receipt-card { box-shadow: none; border: none; width: 100%; padding: 10px; }
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
      <div style="display:flex; align-items:center; justify-content:center; gap:6px; font-family:'Plus Jakarta Sans', sans-serif; font-weight:900; font-size:1.3rem; text-transform:uppercase; letter-spacing:1px; color:var(--ink);">
        <i class="bi bi-dribbble" style="color:var(--coral);"></i> PIKVERO
      </div>
      <div style="font-family:'Plus Jakarta Sans', sans-serif; font-weight:800; font-size:0.95rem; text-transform:uppercase; margin-top:2px; color:var(--ink);">
        <?= htmlspecialchars($d['facility_name']) ?>
      </div>
      <div style="font-size:0.75rem; color:#4a5c56; margin-top:2px;">
        <?= htmlspecialchars($d['facility_address'] ?: 'Tagbilaran City, Bohol') ?>
      </div>
      <div class="receipt-badge" style="margin-top:8px;">OPEN PLAY ENTRY PASS</div>
    </div>

    <div class="info-row">
      <span>ENTRY PASS NO:</span>
      <strong>#OP-REG-<?= sprintf('%05d', $d['id']) ?></strong>
    </div>

    <div class="info-row">
      <span>DATE RECORDED:</span>
      <span><?= date('M d, Y H:i', strtotime($d['created_at'])) ?></span>
    </div>

    <div class="info-row">
      <span>PLAYER NAME:</span>
      <strong><?= htmlspecialchars($d['player_name']) ?></strong>
    </div>

    <div class="info-row">
      <span>PHONE:</span>
      <span><?= htmlspecialchars($d['player_phone'] ?: 'N/A') ?></span>
    </div>

    <div class="divider-line"></div>

    <div style="font-size:0.72rem; font-weight:700; color:#4a5c56; margin-bottom:8px; text-transform:uppercase;">SESSION DETAILS</div>
    <div style="font-family:'Plus Jakarta Sans', sans-serif; font-weight:800; font-size:0.95rem; text-transform:uppercase; margin-bottom:4px;">
      <?= htmlspecialchars($d['session_title']) ?>
    </div>
    <div class="info-row">
      <span>DATE:</span>
      <span><?= date('M d, Y', strtotime($d['session_date'])) ?></span>
    </div>
    <div class="info-row">
      <span>COURT:</span>
      <span><?= htmlspecialchars($d['court_name'] ?? 'Not assigned') ?></span>
    </div>
    <div class="info-row">
      <span>OPERATING TIME:</span>
      <span><?= substr($d['start_time'], 0, 5) ?> - <?= substr($d['end_time'], 0, 5) ?></span>
    </div>
    <div class="info-row">
      <span>PAYMENT METHOD:</span>
      <strong style="text-transform:uppercase; color:var(--ink);"><?= htmlspecialchars(strtoupper(($d['payment_method'] ?? '') === 'gcash' ? 'PayMongo (GCash)' : (($d['payment_method'] ?? '') === 'card' ? 'PayMongo (Card)' : 'Cash at Court'))) ?></strong>
    </div>
    <div class="info-row">
      <span>CHECK-IN STATUS:</span>
      <strong style="color:var(--green); text-transform:uppercase;"><?= htmlspecialchars($d['checkin_status']) ?></strong>
    </div>

    <div class="total-card">
      <div style="display:flex; justify-content:space-between; align-items:center; font-family:'Plus Jakarta Sans', sans-serif; font-weight:800;">
        <span>ENTRY FEE PAID:</span>
        <span style="font-size:1.2rem; color:var(--coral);">₱<?= number_format(!empty($d['fee_per_player']) ? $d['fee_per_player'] : $d['amount_paid'], 2) ?></span>
      </div>
    </div>

    <div style="text-align:center; margin:14px auto 6px; letter-spacing:4px; font-size:1.1rem; font-weight:800; user-select:none;">||||||||||||||||||||||</div>

    <div style="text-align:center; font-size:0.72rem; color:#555; margin-top:10px; line-height:1.4;">
      <div>Present this pass at court entrance for check-in.</div>
      <div style="font-weight:800; text-transform:uppercase; color:var(--ink); margin-top:4px;">*** <?= htmlspecialchars(strtoupper($d['facility_name'])) ?> ***</div>
      <div style="margin-top:10px; display:flex; align-items:center; justify-content:center; gap:6px; font-family:'Plus Jakarta Sans', sans-serif; font-weight:900; font-size:0.85rem; text-transform:uppercase; color:var(--ink);">
        <i class="bi bi-dribbble" style="color:var(--coral); font-size:1rem;"></i> PIKVERO
      </div>
      <div style="font-size:0.65rem; color:#4a5c56; margin-top:2px;">www.pikvero.com • Serve With Passion</div>
    </div>
  </div>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
  <script>
    const defaultBackUrl = <?= json_encode($defaultBackUrl) ?>;

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
        link.download = 'pikvero_openplay_pass_<?= sprintf('%05d', $d['id']) ?>.png';
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
        filename:     'pikvero_openplay_pass_<?= sprintf('%05d', $d['id']) ?>.pdf',
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
      if (document.referrer && document.referrer.includes('admin/bookings.php')) {
        window.location.href = '/pikvero/public/admin/bookings.php';
        return;
      }
      if (document.referrer && document.referrer.includes('admin/open-play.php')) {
        window.location.href = '/pikvero/public/admin/open-play.php';
        return;
      }
      if (document.referrer && document.referrer.includes('customer/open-play.php')) {
        window.location.href = '/pikvero/public/customer/open-play.php';
        return;
      }

      let closed = false;
      try {
        if (window.opener && !window.opener.closed) {
          window.close();
          closed = true;
        }
      } catch (e) {}

      if (!closed) {
        window.location.href = defaultBackUrl;
      }
    }
  </script>
</body>
</html>
