<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Pikvero — Contact &amp; Support</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="/pikvero/assets/css/streetside-theme.css">
  <link rel="stylesheet" href="/pikvero/assets/css/toast.css">
</head>
<body>

  <?php require_once __DIR__ . '/../includes/header.php'; ?>

  <div style="padding:120px max(4vw, 20px) 60px; max-width:800px; margin:0 auto;">
    <div style="margin-bottom:30px;">
      <div class="eyebrow">HELP &amp; INQUIRIES</div>
      <h1 style="font-size:clamp(2.2rem, 5vw, 3.2rem); font-weight:800; text-transform:uppercase; margin:4px 0 0;">GET IN TOUCH</h1>
    </div>

    <div class="card-streetside" style="padding:28px; background:var(--white); margin-bottom:24px;">
      <form id="contact-form">
        <div style="margin-bottom:14px;">
          <label class="mono" style="display:block; margin-bottom:4px;">YOUR NAME</label>
          <input type="text" required placeholder="Juan Dela Cruz" style="width:100%; padding:10px; border:2px solid var(--ink); border-radius:10px; font-family:inherit;">
        </div>

        <div style="margin-bottom:14px;">
          <label class="mono" style="display:block; margin-bottom:4px;">EMAIL ADDRESS</label>
          <input type="email" required placeholder="player@gmail.com" style="width:100%; padding:10px; border:2px solid var(--ink); border-radius:10px; font-family:inherit;">
        </div>

        <div style="margin-bottom:18px;">
          <label class="mono" style="display:block; margin-bottom:4px;">MESSAGE / INQUIRY</label>
          <textarea rows="4" required placeholder="How can we assist you with court reservations or owner onboarding?" style="width:100%; padding:10px; border:2px solid var(--ink); border-radius:10px; font-family:inherit;"></textarea>
        </div>

        <button type="submit" class="button lime" style="padding:10px 20px; font-size:0.88rem;">
          <i class="bi bi-send-fill"></i> Send Message
        </button>
      </form>
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

      document.getElementById('contact-form').addEventListener('submit', (e) => {
        e.preventDefault();
        Toast.success('Message Sent', 'Thank you for contacting Pikvero support. We will get back to you shortly.');
        e.target.reset();
      });
    });
  </script>
</body>
</html>
