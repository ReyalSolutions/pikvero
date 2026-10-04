<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Pikvero — Privacy Policy</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="/pikvero/assets/css/streetside-theme.css">
  <link rel="stylesheet" href="/pikvero/assets/css/toast.css">
</head>
<body>

  <?php require_once __DIR__ . '/../includes/header.php'; ?>

  <div style="padding:120px max(4vw, 20px) 60px; max-width:800px; margin:0 auto;">
    <div style="margin-bottom:30px;">
      <div class="eyebrow">DATA PROTECTION</div>
      <h1 style="font-size:clamp(2.2rem, 5vw, 3.2rem); font-weight:800; text-transform:uppercase; margin:4px 0 0;">PRIVACY POLICY</h1>
    </div>

    <div class="card-streetside" style="padding:28px; background:var(--white);">
      <h4 style="font-weight:800;">1. Information We Collect</h4>
      <p style="font-size:0.88rem; color:#3b4e48; line-height:1.5;">We collect name, email address, 11-digit phone number, and reservation history required to process court bookings and multi-tenant authentication.</p>

      <h4 style="font-weight:800; margin-top:20px;">2. Security &amp; Tenant Isolation</h4>
      <p style="font-size:0.88rem; color:#3b4e48; line-height:1.5;">Personal data and payment transaction records are secured using MySQLi prepared statements, password hashing, and server-side multi-tenant isolation rules.</p>
    </div>
  </div>

  <div id="footer-container"></div>

  <script src="/pikvero/assets/js/core/toast.js"></script>
  <script src="/pikvero/assets/js/core/ajax.js"></script>
  <script src="/pikvero/assets/js/core/auth.js"></script>
  <script src="/pikvero/assets/js/components/navbar.js"></script>
  <script src="/pikvero/assets/js/components/footer.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', async () => {
      await NavbarComponent.render();
      FooterComponent.render();
    });
  </script>
</body>
</html>
