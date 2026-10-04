<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Pikvero — 403 Access Restricted</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="/pikvero/assets/css/streetside-theme.css?v=<?= time() ?>">
  <link rel="stylesheet" href="/pikvero/assets/css/toast.css?v=<?= time() ?>">
</head>
<body style="background:var(--sand); min-height:100vh; display:grid; place-items:center; padding:20px;">

  <main style="width:100%; display:grid; place-items:center;">
    <div class="card-streetside" style="width:min(560px, 100%); padding:36px; background:var(--white); text-align:center; box-shadow:8px 8px 0 var(--ink);">
      <div style="width:80px; height:80px; background:#ffd1ca; border:3px solid var(--ink); border-radius:50%; display:grid; place-items:center; margin:0 auto 20px; box-shadow:4px 4px 0 var(--ink);">
        <i class="bi bi-shield-lock-fill" style="font-size:2.5rem; color:#5a0b00;"></i>
      </div>

      <div class="eyebrow" style="color:var(--coral); font-size:0.75rem;">SECURITY GUARD — 403 FORBIDDEN</div>
      <h1 style="font-size: clamp(1.8rem, 4vw, 2.2rem); font-weight:800; text-transform:uppercase; margin:8px 0 12px;">ACCESS RESTRICTED</h1>

      <p style="font-size:0.95rem; color:#4a5c56; line-height:1.5; margin-bottom:20px;">
        Your system account role does not possess the necessary authorization permissions to view or interact with this restricted page.
      </p>

      <?php
      $perm = isset($_GET['permission']) ? htmlspecialchars($_GET['permission']) : null;
      if ($perm):
      ?>
      <div style="background:#f8faf9; border:2px solid var(--ink); border-radius:10px; padding:12px 16px; margin-bottom:24px; text-align:left;">
        <div style="font-size:0.72rem; font-weight:800; font-family:'DM Mono', monospace; color:#4a5c56; margin-bottom:4px;">REQUIRED PERMISSION</div>
        <div style="font-family:'DM Mono', monospace; font-weight:800; font-size:0.9rem; color:var(--coral);">
          <i class="bi bi-key-fill"></i> <?= $perm ?>
        </div>
      </div>
      <?php endif; ?>

      <div style="display:flex; justify-content:center; gap:12px; flex-wrap:wrap;">
        <button onclick="handleBackToSafety()" class="button sand" style="padding:12px 20px; font-size:0.88rem;">
          <i class="bi bi-arrow-left"></i> Back to Safety
        </button>
        <a id="btn-redirect-dashboard" href="/pikvero/public/admin/dashboard.php" class="button lime" style="padding:12px 22px; font-size:0.88rem;">
          <i class="bi bi-grid-1x2-fill"></i> Go to Dashboard
        </a>
      </div>
    </div>
  </main>

  <script src="/pikvero/assets/js/core/toast.js?v=<?= time() ?>"></script>
  <script src="/pikvero/assets/js/core/ajax.js?v=<?= time() ?>"></script>
  <script src="/pikvero/assets/js/core/auth.js?v=<?= time() ?>"></script>
  <script>
    let fallbackDashboardUrl = '/pikvero/public/admin/dashboard.php';

    document.addEventListener('DOMContentLoaded', async () => {
      const userCtx = await AuthHelper.checkSession();
      if (userCtx) {
        const role = userCtx.role || (userCtx.user ? userCtx.user.role_name : '');
        if (role === 'customer') {
          fallbackDashboardUrl = '/pikvero/public/customer/dashboard.php';
        } else {
          fallbackDashboardUrl = '/pikvero/public/admin/dashboard.php';
        }
        
        const btnDash = document.getElementById('btn-redirect-dashboard');
        if (btnDash) {
          btnDash.href = fallbackDashboardUrl;
        }
      }
    });

    function handleBackToSafety() {
      if (document.referrer && !document.referrer.includes('403.php')) {
        window.history.back();
      } else {
        window.location.href = fallbackDashboardUrl;
      }
    }
  </script>
</body>
</html>
