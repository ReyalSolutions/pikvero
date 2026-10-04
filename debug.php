<?php
/**
 * Pikvero System & Database Diagnostic Tool
 * Access at: /debug.php or /debug
 */

// Enable full error reporting for diagnostics
@ini_set('display_errors', '1');
@ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

$reqUri = $_SERVER['REQUEST_URI'] ?? '';
$basePath = (strpos($reqUri, '/pikvero') === 0) ? '/pikvero' : '';

// 1. Check bootstrap loading
$bootstrapLoaded = false;
$bootstrapError = null;
try {
    if (file_exists(__DIR__ . '/app/bootstrap.php')) {
        require_once __DIR__ . '/app/bootstrap.php';
        $bootstrapLoaded = true;
    } else {
        $bootstrapError = 'app/bootstrap.php not found in ' . __DIR__;
    }
} catch (\Throwable $e) {
    $bootstrapError = $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine();
}

use App\Config\DatabaseConfig;
use App\Core\Database\Connection;

// 2. Check local database config file existence
$localConfigPath = __DIR__ . '/app/Config/DatabaseConfig.local.php';
$hasLocalConfig = file_exists($localConfigPath);

// 3. Handle interactive live DB test form submission
$testHost = $_POST['test_host'] ?? (class_exists(DatabaseConfig::class) ? DatabaseConfig::$host : '127.0.0.1');
$testPort = (int)($_POST['test_port'] ?? (class_exists(DatabaseConfig::class) ? DatabaseConfig::$port : 3306));
$testDb   = $_POST['test_db']   ?? (class_exists(DatabaseConfig::class) ? DatabaseConfig::$dbname : 'picklehub_db');
$testUser = $_POST['test_user'] ?? (class_exists(DatabaseConfig::class) ? DatabaseConfig::$username : 'root');
$testPass = $_POST['test_pass'] ?? (class_exists(DatabaseConfig::class) ? DatabaseConfig::$password : '');

$testResult = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'test_db') {
    $startTime = microtime(true);
    try {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $conn = new mysqli($testHost, $testUser, $testPass, $testDb, $testPort);
        $latency = round((microtime(true) - $startTime) * 1000, 2);
        
        $serverVer = $conn->server_info;
        
        // Check tables
        $tablesRes = $conn->query("SHOW TABLES");
        $tables = [];
        while ($row = $tablesRes->fetch_array()) {
            $tables[] = $row[0];
        }
        
        $testResult = [
            'success' => true,
            'latency' => $latency,
            'server_version' => $serverVer,
            'tables' => $tables
        ];
        $conn->close();
    } catch (\Throwable $e) {
        $testResult = [
            'success' => false,
            'error_code' => $e->getCode(),
            'message' => $e->getMessage()
        ];
    }
} else {
    // Default automated test using configured settings
    if (class_exists(DatabaseConfig::class)) {
        try {
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            $conn = new mysqli(DatabaseConfig::$host, DatabaseConfig::$username, DatabaseConfig::$password, DatabaseConfig::$dbname, DatabaseConfig::$port);
            $serverVer = $conn->server_info;
            $tablesRes = $conn->query("SHOW TABLES");
            $tables = [];
            while ($row = $tablesRes->fetch_array()) {
                $tables[] = $row[0];
            }
            $testResult = [
                'success' => true,
                'latency' => 0,
                'server_version' => $serverVer,
                'tables' => $tables
            ];
            $conn->close();
        } catch (\Throwable $e) {
            $testResult = [
                'success' => false,
                'error_code' => $e->getCode(),
                'message' => $e->getMessage()
            ];
        }
    }
}

// 4. Check critical PHP Extensions
$extensions = [
    'mysqli' => extension_loaded('mysqli'),
    'pdo_mysql' => extension_loaded('pdo_mysql'),
    'session' => extension_loaded('session'),
    'json' => extension_loaded('json'),
    'mbstring' => extension_loaded('mbstring'),
    'curl' => extension_loaded('curl'),
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pikvero Diagnostic & Online Hosting Health Center</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500;700&family=Outfit:wght@600;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    :root {
      --bg: #071711;
      --card-bg: #0f241c;
      --card-border: #1a382c;
      --ink: #f8fafc;
      --coral: #ff5733;
      --lime: #d6f827;
      --emerald: #10b981;
      --red: #ef4444;
      --amber: #f59e0b;
    }
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      background: var(--bg);
      color: var(--ink);
      font-family: 'Plus Jakarta Sans', sans-serif;
      padding: 30px 20px 80px;
      line-height: 1.5;
    }
    .diag-container {
      max-width: 1000px;
      margin: 0 auto;
    }
    .diag-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 16px;
      margin-bottom: 28px;
      padding-bottom: 20px;
      border-bottom: 1.5px solid var(--card-border);
    }
    .diag-title-wrap {
      display: flex;
      align-items: center;
      gap: 14px;
    }
    .diag-logo {
      width: 44px;
      height: 44px;
      border-radius: 12px;
      background: #fff;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.4rem;
      border: 2px solid #000;
    }
    .diag-title {
      font-family: 'Outfit', sans-serif;
      font-weight: 900;
      font-size: 1.7rem;
      letter-spacing: -0.02em;
    }
    .diag-title span { color: var(--coral); }
    .diag-nav-links {
      display: flex;
      gap: 10px;
    }
    .btn-diag {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 9px 18px;
      border-radius: 9999px;
      font-size: 0.88rem;
      font-weight: 700;
      text-decoration: none;
      transition: all 0.2s;
    }
    .btn-diag-primary {
      background: var(--coral);
      color: #fff;
    }
    .btn-diag-primary:hover { opacity: 0.9; }
    .btn-diag-outline {
      background: transparent;
      color: var(--lime);
      border: 1.5px solid var(--lime);
    }
    .btn-diag-outline:hover { background: rgba(214, 248, 39, 0.1); }

    .grid-2 {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
      gap: 20px;
      margin-bottom: 24px;
    }

    .diag-card {
      background: var(--card-bg);
      border: 1.5px solid var(--card-border);
      border-radius: 18px;
      padding: 24px;
    }
    .card-title {
      font-family: 'Outfit', sans-serif;
      font-size: 1.15rem;
      font-weight: 800;
      margin-bottom: 16px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
    }
    .status-badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 4px 10px;
      border-radius: 9999px;
      font-size: 0.75rem;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: 0.05em;
    }
    .badge-ok { background: rgba(16, 185, 129, 0.2); color: #34d399; border: 1px solid #10b981; }
    .badge-fail { background: rgba(239, 68, 68, 0.2); color: #f87171; border: 1px solid #ef4444; }
    .badge-warn { background: rgba(245, 158, 11, 0.2); color: #fbbf24; border: 1px solid #f59e0b; }

    .info-list {
      display: flex;
      flex-direction: column;
      gap: 10px;
      font-size: 0.9rem;
    }
    .info-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding-bottom: 8px;
      border-bottom: 1px solid rgba(255, 255, 255, 0.06);
    }
    .info-label { color: #94a3b8; }
    .info-val { font-family: 'DM Mono', monospace; color: #fff; font-weight: 500; font-size: 0.85rem; }

    .code-block {
      background: #050f0b;
      border: 1px solid #1f3b2f;
      border-radius: 10px;
      padding: 14px;
      font-family: 'DM Mono', monospace;
      font-size: 0.84rem;
      color: var(--lime);
      overflow-x: auto;
      margin: 12px 0;
      white-space: pre-wrap;
      word-break: break-all;
    }

    .form-group {
      margin-bottom: 14px;
    }
    .form-label {
      display: block;
      font-size: 0.82rem;
      font-weight: 700;
      color: #94a3b8;
      margin-bottom: 6px;
    }
    .form-input {
      width: 100%;
      background: #071711;
      border: 1.5px solid #234235;
      border-radius: 8px;
      padding: 9px 12px;
      color: #fff;
      font-family: 'DM Mono', monospace;
      font-size: 0.9rem;
    }
    .form-input:focus {
      outline: none;
      border-color: var(--lime);
    }
    .btn-submit {
      background: var(--lime);
      color: #0c1a15;
      border: none;
      padding: 10px 20px;
      border-radius: 8px;
      font-weight: 800;
      font-size: 0.9rem;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }
    .btn-submit:hover { background: #c4e61b; }

    .guide-box {
      background: rgba(255, 87, 51, 0.08);
      border: 1.5px solid rgba(255, 87, 51, 0.4);
      border-radius: 14px;
      padding: 20px;
      margin-top: 20px;
    }
    .guide-title {
      font-family: 'Outfit', sans-serif;
      font-weight: 800;
      font-size: 1.15rem;
      color: var(--coral);
      margin-bottom: 10px;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .guide-steps {
      padding-left: 20px;
      color: #cbd5e1;
      font-size: 0.92rem;
      line-height: 1.7;
    }
    .guide-steps li { margin-bottom: 8px; }
    .guide-steps code {
      background: #071711;
      padding: 2px 6px;
      border-radius: 4px;
      color: var(--lime);
      font-family: 'DM Mono', monospace;
    }
  </style>
</head>
<body>
  <div class="diag-container">
    
    <!-- HEADER -->
    <header class="diag-header">
      <div class="diag-title-wrap">
        <div class="diag-logo">🎾</div>
        <div>
          <h1 class="diag-title">PIKVERO <span>HEALTH & DIAGNOSTICS</span></h1>
          <p style="color: #94a3b8; font-size: 0.85rem;">Online Hosting & Database Diagnostic Monitor</p>
        </div>
      </div>
      <div class="diag-nav-links">
        <a href="<?= $basePath ?>/" class="btn-diag btn-diag-primary">
          <i class="bi bi-house-door-fill"></i> Go to Homepage
        </a>
        <a href="<?= $basePath ?>/debug" class="btn-diag btn-diag-outline">
          <i class="bi bi-arrow-clockwise"></i> Refresh Test
        </a>
      </div>
    </header>

    <!-- TOP ALERT FOR DATABASE CONNECTION -->
    <?php if ($testResult && $testResult['success']): ?>
      <div style="background: rgba(16, 185, 129, 0.15); border: 2px solid #10b981; border-radius: 14px; padding: 18px 24px; margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
        <div style="display: flex; align-items: center; gap: 14px;">
          <i class="bi bi-check-circle-fill" style="color: #10b981; font-size: 1.8rem;"></i>
          <div>
            <h3 style="color: #34d399; font-size: 1.15rem; font-weight: 800;">MySQL Database Connected Successfully!</h3>
            <p style="color: #cbd5e1; font-size: 0.88rem;">Server: <?= htmlspecialchars($testResult['server_version']) ?> | Tables Found: <?= count($testResult['tables']) ?></p>
          </div>
        </div>
        <span class="status-badge badge-ok"><i class="bi bi-check2"></i> ONLINE</span>
      </div>
    <?php else: ?>
      <div style="background: rgba(239, 68, 68, 0.15); border: 2px solid #ef4444; border-radius: 14px; padding: 18px 24px; margin-bottom: 24px;">
        <div style="display: flex; align-items: flex-start; gap: 14px;">
          <i class="bi bi-exclamation-triangle-fill" style="color: #ef4444; font-size: 1.8rem; flex-shrink: 0; margin-top: 2px;"></i>
          <div style="flex-grow: 1;">
            <h3 style="color: #f87171; font-size: 1.15rem; font-weight: 800;">Database Connection Offline</h3>
            <p style="color: #e2e8f0; font-size: 0.9rem; margin-top: 4px;">
              <strong>Error:</strong> <?= htmlspecialchars($testResult['message'] ?? 'Unable to connect to MySQL') ?>
              <?php if (isset($testResult['error_code'])): ?>
                (Code: <?= (int)$testResult['error_code'] ?>)
              <?php endif; ?>
            </p>
            <p style="color: #94a3b8; font-size: 0.85rem; margin-top: 8px;">
              💡 <em>The homepage is configured with smart fallback data so the site remains accessible. Follow the instructions below to link your InfinityFree database.</em>
            </p>
          </div>
          <span class="status-badge badge-fail"><i class="bi bi-x-circle"></i> OFFLINE</span>
        </div>
      </div>
    <?php endif; ?>

    <!-- STATUS GRID -->
    <div class="grid-2">
      
      <!-- CARD 1: PHP & SERVER ENVIRONMENT -->
      <div class="diag-card">
        <div class="card-title">
          <span><i class="bi bi-hdd-rack-fill" style="color: var(--lime);"></i> Server Environment</span>
          <span class="status-badge badge-ok">PHP <?= phpversion() ?></span>
        </div>
        <div class="info-list">
          <div class="info-row">
            <span class="info-label">Host Name</span>
            <span class="info-val"><?= htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'unknown') ?></span>
          </div>
          <div class="info-row">
            <span class="info-label">Server Software</span>
            <span class="info-val"><?= htmlspecialchars($_SERVER['SERVER_SOFTWARE'] ?? 'Apache') ?></span>
          </div>
          <div class="info-row">
            <span class="info-label">Operating System</span>
            <span class="info-val"><?= PHP_OS_FAMILY ?> (<?= PHP_OS ?>)</span>
          </div>
          <div class="info-row">
            <span class="info-label">Document Root</span>
            <span class="info-val"><?= htmlspecialchars($_SERVER['DOCUMENT_ROOT'] ?? __DIR__) ?></span>
          </div>
          <div class="info-row">
            <span class="info-label">Clean URL Rewriting</span>
            <span class="info-val" style="color:var(--lime);">
              <?= (isset($_SERVER['REDIRECT_STATUS']) || strpos($_SERVER['REQUEST_URI'] ?? '', '.php') === false) ? 'Active (.htaccess)' : 'Available' ?>
            </span>
          </div>
        </div>
      </div>

      <!-- CARD 2: REQUIRED EXTENSIONS -->
      <div class="diag-card">
        <div class="card-title">
          <span><i class="bi bi-cpu-fill" style="color: var(--coral);"></i> PHP Modules</span>
          <span class="status-badge <?= $extensions['mysqli'] ? 'badge-ok' : 'badge-fail' ?>">
            <?= $extensions['mysqli'] ? 'Ready' : 'Incomplete' ?>
          </span>
        </div>
        <div class="info-list">
          <?php foreach ($extensions as $ext => $loaded): ?>
            <div class="info-row">
              <span class="info-label"><?= htmlspecialchars($ext) ?></span>
              <span class="info-val" style="color: <?= $loaded ? '#34d399' : '#f87171' ?>;">
                <i class="bi bi-<?= $loaded ? 'check-circle-fill' : 'x-circle-fill' ?>"></i>
                <?= $loaded ? 'Loaded' : 'Missing' ?>
              </span>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

    </div>

    <!-- CARD 3: DATABASE CONFIGURATION & OVERRIDE STATUS -->
    <div class="diag-card" style="margin-bottom: 24px;">
      <div class="card-title">
        <span><i class="bi bi-database-fill-gear" style="color: var(--lime);"></i> Active Database Configuration</span>
        <span class="status-badge <?= $hasLocalConfig ? 'badge-ok' : 'badge-warn' ?>">
          <?= $hasLocalConfig ? 'DatabaseConfig.local.php [FOUND]' : 'Default / Local Settings' ?>
        </span>
      </div>

      <div class="grid-2" style="margin-bottom: 0;">
        <div class="info-list">
          <div class="info-row">
            <span class="info-label">Config Host</span>
            <span class="info-val"><?= htmlspecialchars(class_exists(DatabaseConfig::class) ? DatabaseConfig::$host : '127.0.0.1') ?></span>
          </div>
          <div class="info-row">
            <span class="info-label">Config Port</span>
            <span class="info-val"><?= (int)(class_exists(DatabaseConfig::class) ? DatabaseConfig::$port : 3306) ?></span>
          </div>
          <div class="info-row">
            <span class="info-label">Database Name</span>
            <span class="info-val"><?= htmlspecialchars(class_exists(DatabaseConfig::class) ? DatabaseConfig::$dbname : 'picklehub_db') ?></span>
          </div>
          <div class="info-row">
            <span class="info-label">Username</span>
            <span class="info-val"><?= htmlspecialchars(class_exists(DatabaseConfig::class) ? DatabaseConfig::$username : 'root') ?></span>
          </div>
          <div class="info-row">
            <span class="info-label">Password Set?</span>
            <span class="info-val" style="color: <?= !empty(class_exists(DatabaseConfig::class) ? DatabaseConfig::$password : '') ? '#34d399' : '#fbbf24' ?>;">
              <?= !empty(class_exists(DatabaseConfig::class) ? DatabaseConfig::$password : '') ? 'Yes (Hidden)' : 'Empty / Not Set' ?>
            </span>
          </div>
        </div>

        <div>
          <p style="color: #94a3b8; font-size: 0.88rem; margin-bottom: 10px;">
            <strong>Config File Location:</strong><br>
            <code style="color: var(--lime); font-size: 0.82rem;"><?= htmlspecialchars($localConfigPath) ?></code>
          </p>
          <?php if (!$hasLocalConfig): ?>
            <div style="background: rgba(245, 158, 11, 0.1); border: 1px solid #f59e0b; border-radius: 8px; padding: 12px; font-size: 0.84rem; color: #fbbf24;">
              <i class="bi bi-info-circle-fill"></i> <strong>Notice:</strong> <code>DatabaseConfig.local.php</code> does not exist on this server. Create it in InfinityFree File Manager to link your live MySQL database.
            </div>
          <?php else: ?>
            <div style="background: rgba(16, 185, 129, 0.1); border: 1px solid #10b981; border-radius: 8px; padding: 12px; font-size: 0.84rem; color: #34d399;">
              <i class="bi bi-check-circle-fill"></i> <strong>Notice:</strong> <code>DatabaseConfig.local.php</code> is loaded.
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- TABLES LIST IF CONNECTED -->
      <?php if ($testResult && $testResult['success'] && !empty($testResult['tables'])): ?>
        <div style="margin-top: 20px; border-top: 1px solid var(--card-border); padding-top: 16px;">
          <h4 style="font-size: 0.95rem; margin-bottom: 10px; color: var(--lime);">
            <i class="bi bi-table"></i> Verified Database Tables (<?= count($testResult['tables']) ?>):
          </h4>
          <div style="display: flex; flex-wrap: wrap; gap: 8px;">
            <?php foreach ($testResult['tables'] as $tbl): ?>
              <span style="background: #071711; border: 1px solid #1a382c; padding: 4px 10px; border-radius: 6px; font-family: 'DM Mono', monospace; font-size: 0.8rem; color: #e2e8f0;">
                <?= htmlspecialchars($tbl) ?>
              </span>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>
    </div>

    <!-- INTERACTIVE LIVE TEST FORM -->
    <div class="diag-card" style="margin-bottom: 24px;">
      <div class="card-title">
        <span><i class="bi bi-lightning-charge-fill" style="color: var(--lime);"></i> Test MySQL Connection Live</span>
      </div>
      <p style="color: #94a3b8; font-size: 0.88rem; margin-bottom: 16px;">
        Test any MySQL host, user, and password directly from this page without needing to reload or edit files.
      </p>

      <form method="POST" action="">
        <input type="hidden" name="action" value="test_db">
        <div class="grid-2" style="margin-bottom: 14px;">
          <div class="form-group">
            <label class="form-label">MySQL Hostname (from InfinityFree)</label>
            <input type="text" name="test_host" class="form-input" value="<?= htmlspecialchars($testHost) ?>" placeholder="e.g. sql300.infinityfree.com" required>
          </div>
          <div class="form-group">
            <label class="form-label">MySQL Port</label>
            <input type="number" name="test_port" class="form-input" value="<?= $testPort ?>" placeholder="3306" required>
          </div>
          <div class="form-group">
            <label class="form-label">Database Name</label>
            <input type="text" name="test_db" class="form-input" value="<?= htmlspecialchars($testDb) ?>" placeholder="e.g. epiz_12345678_pikvero" required>
          </div>
          <div class="form-group">
            <label class="form-label">MySQL Username</label>
            <input type="text" name="test_user" class="form-input" value="<?= htmlspecialchars($testUser) ?>" placeholder="e.g. epiz_12345678" required>
          </div>
          <div class="form-group" style="grid-column: 1 / -1;">
            <label class="form-label">MySQL Password</label>
            <input type="password" name="test_pass" class="form-input" value="<?= htmlspecialchars($testPass) ?>" placeholder="Your vPanel/Account Password">
          </div>
        </div>

        <button type="submit" class="btn-submit">
          <i class="bi bi-play-circle-fill"></i> Test Connection Now
        </button>
      </form>
    </div>

    <!-- INFINITYFREE SETUP STEP-BY-STEP GUIDE -->
    <div class="guide-box">
      <div class="guide-title">
        <i class="bi bi-tools"></i> How to Connect InfinityFree Database:
      </div>
      <ol class="guide-steps">
        <li>
          Log in to your <strong>InfinityFree Client Area</strong> &rarr; click your account (<code>pikvero.wuaze.com</code>).
        </li>
        <li>
          Under <strong>Database Details</strong> or in <strong>Control Panel &rarr; MySQL Databases</strong>, copy:
          <br>&bull; <strong>MySQL Hostname:</strong> (e.g. <code>sql123.infinityfree.com</code>)
          <br>&bull; <strong>MySQL Database Name:</strong> (e.g. <code>epiz_12345678_pikvero</code>)
          <br>&bull; <strong>MySQL Username:</strong> (e.g. <code>epiz_12345678</code>)
          <br>&bull; <strong>MySQL Password:</strong> (Your InfinityFree account/vPanel password)
        </li>
        <li>
          In InfinityFree <strong>Online File Manager</strong>, open <code>/htdocs/app/Config/</code>.
        </li>
        <li>
          Create a new file named <code>DatabaseConfig.local.php</code> with the following content:
          <div class="code-block">&lt;?php
use App\Config\DatabaseConfig;

DatabaseConfig::$host     = '<?= htmlspecialchars($testHost !== '127.0.0.1' ? $testHost : 'sqlXXX.infinityfree.com') ?>';
DatabaseConfig::$port     = 3306;
DatabaseConfig::$dbname   = '<?= htmlspecialchars($testDb !== 'picklehub_db' ? $testDb : 'epiz_XXXXXXXX_pikvero') ?>';
DatabaseConfig::$username = '<?= htmlspecialchars($testUser !== 'root' ? $testUser : 'epiz_XXXXXXXX') ?>';
DatabaseConfig::$password = 'YOUR_INFINITYFREE_PASSWORD';
DatabaseConfig::$charset  = 'utf8mb4';</div>
        </li>
        <li>
          In Control Panel, open <strong>phpMyAdmin</strong>, select your database, and import <code>database/schema.sql</code> so your courts and facilities tables are populated!
        </li>
      </ol>
    </div>

  </div>
</body>
</html>
