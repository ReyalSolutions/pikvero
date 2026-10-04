<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Pikvero — About Us</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="/pikvero/assets/css/streetside-theme.css">
  <link rel="stylesheet" href="/pikvero/assets/css/toast.css">
</head>
<body>

  <?php require_once __DIR__ . '/../includes/header.php'; ?>

  <div style="padding:120px max(4vw, 20px) 60px; max-width:800px; margin:0 auto;">
    <div style="margin-bottom:30px;">
      <div class="eyebrow">ABOUT PIKVERO</div>
      <h1 style="font-size:clamp(2.2rem, 5vw, 3.2rem); font-weight:800; text-transform:uppercase; margin:4px 0 0;">OUR MISSION &amp; PLATFORM</h1>
    </div>

    <div class="card-streetside sky" style="padding:28px; margin-bottom:24px;">
      <h3 style="font-size:1.3rem; font-weight:800; text-transform:uppercase; margin:0 0 10px;">THE PREMIER PICKLEBALL RESERVATION PLATFORM</h3>
      <p style="font-size:0.95rem; color:#1a3d34; line-height:1.5; margin:0;">
        Pikvero was built to streamline court discovery, real-time slot reservations, and facility management for the growing pickleball community in the Philippines. Our platform empowers court owners with multi-tenant SaaS tools while providing players with instant online booking.
      </p>
    </div>

    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); gap:18px;">
      <div class="card-streetside" style="padding:20px; background:var(--white);">
        <h4 style="font-size:1.1rem; font-weight:800; text-transform:uppercase; margin:0 0 6px;">FOR PLAYERS</h4>
        <p style="font-size:0.85rem; color:#3b4e48; margin:0;">Instant court search by city, indoor/outdoor surface, price per hour, and operating schedule with real-time confirmation.</p>
      </div>

      <div class="card-streetside" style="padding:20px; background:var(--white);">
        <h4 style="font-size:1.1rem; font-weight:800; text-transform:uppercase; margin:0 0 6px;">FOR COURT OWNERS</h4>
        <p style="font-size:0.85rem; color:#3b4e48; margin:0;">Comprehensive SaaS tools for facility configuration, schedule blockouts, revenue analytics, and automated double-booking prevention.</p>
      </div>
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
