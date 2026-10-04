<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Pikvero — Terms of Service</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="/pikvero/assets/css/streetside-theme.css">
  <link rel="stylesheet" href="/pikvero/assets/css/toast.css">
</head>
<body>

  <?php require_once __DIR__ . '/../includes/header.php'; ?>

  <div style="padding:120px max(4vw, 20px) 60px; max-width:800px; margin:0 auto;">
    <div style="margin-bottom:30px;">
      <div class="eyebrow">LEGAL AGREEMENT</div>
      <h1 style="font-size:clamp(2.2rem, 5vw, 3.2rem); font-weight:800; text-transform:uppercase; margin:4px 0 0;">TERMS OF SERVICE</h1>
    </div>

    <div class="card-streetside" style="padding:28px; background:var(--white);">
      <h4 style="font-weight:800;">1. Acceptance of Terms</h4>
      <p style="font-size:0.88rem; color:#3b4e48; line-height:1.5;">By accessing or using Pikvero, you agree to comply with and be bound by these Terms of Service. Pikvero provides an online reservation platform connecting players and court owners.</p>

      <h4 style="font-weight:800; margin-top:20px;">2. Court Reservations &amp; Cancellation Policy</h4>
      <p style="font-size:0.88rem; color:#3b4e48; line-height:1.5;">All reservations are subject to facility availability and atomic double-booking checks. Cancellations must adhere to individual facility operating policies.</p>
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
