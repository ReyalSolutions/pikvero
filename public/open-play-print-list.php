<?php
require_once __DIR__ . '/../app/bootstrap.php';
use App\Core\Database\Connection;
use App\Core\Auth\Auth;

if (!Auth::check()) {
    header('Location: /pikvero/public/login.php');
    exit;
}

$sessionId = (int)($_GET['session_id'] ?? 0);
if (!$sessionId) {
    die("Invalid Session ID.");
}

$db = Connection::getInstance();
$session = $db->selectOne("
    SELECT s.*, f.name AS facility_name, f.city, f.address,
           (SELECT COUNT(*) FROM open_play_registrations r WHERE r.session_id = s.id) AS registered_players,
           (SELECT COUNT(*) FROM open_play_registrations r WHERE r.session_id = s.id AND r.checkin_status = 'checked_in') AS checked_in_count,
           (SELECT COALESCE(SUM(r.amount_paid), 0) FROM open_play_registrations r WHERE r.session_id = s.id AND r.payment_status = 'paid') AS total_revenue
    FROM open_play_sessions s
    JOIN facilities f ON s.facility_id = f.id
    WHERE s.id = ? LIMIT 1
", [$sessionId], 'i');

if (!$session) {
    die("Open Play session not found.");
}

$players = $db->select("
    SELECT r.*, u.email AS user_email
    FROM open_play_registrations r
    LEFT JOIN users u ON r.user_id = u.id
    WHERE r.session_id = ?
    ORDER BY r.id ASC
", [$sessionId], 'i');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Open Play Attendance Sheet — #OP-<?= $session['id'] ?></title>
  <link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@500&family=Plus+Jakarta+Sans:wght@700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    :root {
      --ink: #0d211d;
      --sand: #eee9d8;
      --lime: #dfff4f;
      --coral: #ff745c;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: 'DM Mono', monospace;
      color: var(--ink);
      background: #f8faf9;
      padding: 24px;
      display: flex;
      flex-direction: column;
      align-items: center;
    }
    .no-print-bar {
      width: min(800px, 100%);
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
    }
    .btn-action.lime { background: var(--lime); color: var(--ink); }
    .btn-action.sand { background: #f0eee6; color: var(--ink); }

    .print-sheet {
      width: min(800px, 100%);
      background: #fff;
      border: 2px solid var(--ink);
      border-radius: 16px;
      box-shadow: 6px 6px 0 var(--ink);
      padding: 30px;
    }
    .sheet-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      border-bottom: 2px solid var(--ink);
      padding-bottom: 16px;
      margin-bottom: 20px;
    }
    .title-main {
      font-family: 'Plus Jakarta Sans', sans-serif;
      font-weight: 800;
      font-size: 1.4rem;
      text-transform: uppercase;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 14px;
    }
    th, td {
      border: 1.5px solid var(--ink);
      padding: 10px 12px;
      font-size: 0.82rem;
      text-align: left;
    }
    th {
      background: var(--sand);
      font-weight: 800;
      text-transform: uppercase;
    }
    @media print {
      body { background: none; padding: 0; }
      .no-print-bar { display: none !important; }
      .print-sheet { box-shadow: none; border: none; width: 100%; padding: 0; }
    }
  </style>
</head>
<body>

  <div class="no-print-bar">
    <button onclick="handleBack()" class="btn-action sand"><i class="bi bi-arrow-left"></i> Back</button>
    <button onclick="window.print()" class="btn-action lime"><i class="bi bi-printer-fill"></i> Print Roster Sheet</button>
  </div>

  <div class="print-sheet">
    <div class="sheet-header">
      <div>
        <div class="title-main"><i class="bi bi-dribbble"></i> OPEN PLAY ROSTER &amp; ATTENDANCE</div>
        <div style="font-size:0.85rem; font-weight:700; margin-top:4px;">
          <?= htmlspecialchars($session['title']) ?> (#OP-<?= $session['id'] ?>)
        </div>
        <div style="font-size:0.78rem; color:#4a5c56;">
          <?= htmlspecialchars($session['facility_name']) ?> (<?= htmlspecialchars($session['city']) ?>)
        </div>
      </div>
      <div style="text-align:right;">
        <div style="font-size:0.82rem; font-weight:800;">Date: <?= date('M d, Y', strtotime($session['session_date'])) ?></div>
        <div style="font-size:0.78rem; color:#4a5c56;">Time: <?= substr($session['start_time'], 0, 5) ?> - <?= substr($session['end_time'], 0, 5) ?></div>
        <div style="font-size:0.82rem; font-weight:800; color:#0b4d40; margin-top:4px;">
          Attendance: <?= (int)$session['checked_in_count'] ?> / <?= (int)$session['registered_players'] ?> Checked-In (Max <?= (int)$session['max_players'] ?>)
        </div>
      </div>
    </div>

    <table>
      <thead>
        <tr>
          <th style="width:40px;">#</th>
          <th>PLAYER NAME</th>
          <th>PHONE NUMBER</th>
          <th>FEE PAID</th>
          <th>CHECK-IN STATUS</th>
          <th style="width:120px;">SIGNATURE</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($players)): ?>
          <tr><td colspan="6" style="text-align:center; padding:20px;">No players registered for this session.</td></tr>
        <?php else: ?>
          <?php foreach ($players as $idx => $p): ?>
            <tr>
              <td><strong><?= $idx + 1 ?></strong></td>
              <td><strong style="font-family:'Plus Jakarta Sans', sans-serif;"><?= htmlspecialchars($p['player_name']) ?></strong></td>
              <td><?= htmlspecialchars($p['player_phone'] ?: 'N/A') ?></td>
              <td><strong>₱<?= number_format($p['amount_paid'], 2) ?></strong></td>
              <td>
                <span style="font-weight:800; color:<?= $p['checkin_status'] === 'checked_in' ? '#0b4d40' : '#c2410c' ?>;">
                  <?= strtoupper($p['checkin_status']) ?>
                </span>
              </td>
              <td></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>

    <div style="margin-top:30px; display:flex; justify-content:space-between; font-size:0.78rem; color:#555; padding-top:14px; border-top:1.5px dashed var(--ink);">
      <div>Facility Coordinator Signature: _______________________</div>
      <div>Date Printed: <?= date('M d, Y • h:i A') ?></div>
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
          window.location.href = '/pikvero/public/admin/open-play.php';
        }
      }

      setTimeout(() => {
        window.location.href = '/pikvero/public/admin/open-play.php';
      }, 150);
    }
  </script>
</body>
</html>
