<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Pikvero — Verify Email Address</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="/pikvero/assets/css/streetside-theme.css">
  <link rel="stylesheet" href="/pikvero/assets/css/toast.css">
</head>
<body>

  <?php require_once __DIR__ . '/../includes/header.php'; ?>

  <div style="min-height:80vh; display:grid; place-items:center; padding:110px 16px 40px;">
    <div class="card-streetside sand" style="width:min(480px, 100%); padding:32px; text-align:center;">
      <div class="brand-mark" style="width:54px; height:54px; font-size:1.8rem; margin:0 auto 16px; background:var(--lime);">
        <i class="bi bi-shield-check"></i>
      </div>
      <h2 style="font-size:1.6rem; font-weight:800; text-transform:uppercase; margin:0 0 8px;">EMAIL VERIFIED!</h2>
      <p style="font-size:0.9rem; color:#3b4e48; margin-bottom:24px; line-height:1.4;">
        Your email address has been successfully verified. You now have full access to reserve pickleball courts and manage your profile.
      </p>

      <a href="/pikvero/public/login.php" class="button coral" style="padding:10px 24px; font-size:0.9rem;">
        <i class="bi bi-box-arrow-in-right"></i> Proceed to Login
      </a>
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
