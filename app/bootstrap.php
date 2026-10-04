<?php
/**
 * Pikvero Master Application Bootstrap
 */

// Define root directory
define('ROOT_DIR', dirname(__DIR__));
define('APP_DIR', __DIR__);

// 1. Error Reporting & Debugging Handler
$isDebug = (isset($_GET['debug']) && $_GET['debug'] === '1')
    || (getenv('APP_DEBUG') === 'true')
    || (isset($_COOKIE['pikvero_debug']) && $_COOKIE['pikvero_debug'] === '1');

if ($isDebug) {
    @ini_set('display_errors', '1');
    @ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    @ini_set('display_errors', '1'); // Enable during deployment verification so 500 errors can be diagnosed
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
}

// Global Exception Handler so uncaught exceptions render a clear diagnostic card instead of a blank HTTP 500
set_exception_handler(function (\Throwable $e) {
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/html; charset=utf-8');
    }
    $reqUri = $_SERVER['REQUEST_URI'] ?? '';
    $diagBase = (strpos($reqUri, '/pikvero') === 0) ? '/pikvero' : '';
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Pikvero — Application Notice</title>
        <style>
            body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #0c1a15; color: #f8fafc; padding: 40px 20px; margin: 0; }
            .err-card { max-width: 820px; margin: 0 auto; background: #162a22; border: 2px solid #ff5733; border-radius: 16px; padding: 32px; box-shadow: 0 20px 50px rgba(0,0,0,0.6); }
            h2 { color: #ff5733; margin-top: 0; font-size: 1.6rem; display: flex; align-items: center; gap: 10px; }
            p { line-height: 1.6; color: #cbd5e1; }
            .code-box { background: #071711; border: 1px solid #234235; border-radius: 8px; padding: 14px; font-family: monospace; font-size: 0.9rem; color: #d6f827; overflow-x: auto; margin: 16px 0; }
            .tip-box { background: rgba(214, 248, 39, 0.1); border: 1px solid #d6f827; border-radius: 8px; padding: 16px; margin: 16px 0; }
            .btn-action { display: inline-block; background: #ff5733; color: #fff; text-decoration: none; padding: 10px 20px; border-radius: 8px; font-weight: 700; margin-top: 14px; margin-right: 10px; }
            .btn-secondary { display: inline-block; background: #234235; color: #d6f827; text-decoration: none; padding: 10px 20px; border-radius: 8px; font-weight: 700; margin-top: 14px; }
        </style>
    </head>
    <body>
        <div class="err-card">
            <h2>⚠️ Application Notice / Exception</h2>
            <p><strong>Message:</strong> <?= htmlspecialchars($e->getMessage()) ?></p>
            <div class="code-box"><?= htmlspecialchars($e->getFile()) ?> : Line <?= $e->getLine() ?></div>
            
            <?php if (stripos($e->getMessage(), 'Database') !== false || stripos($e->getMessage(), 'mysqli') !== false || stripos($e->getMessage(), 'Connection refused') !== false || stripos($e->getMessage(), 'Access denied') !== false): ?>
                <div class="tip-box">
                    <strong style="color: #d6f827;">💡 Database Configuration on Online Hosting:</strong>
                    <p style="color: #e2e8f0; margin-top: 6px; font-size: 0.95rem;">
                        If you are running on InfinityFree, ensure you have created <code>app/Config/DatabaseConfig.local.php</code> with your InfinityFree MySQL hostname (e.g. <code>sqlXXX.infinityfree.com</code>), database name, username, and password.
                    </p>
                </div>
            <?php endif; ?>
            
            <p><strong>Stack Trace:</strong></p>
            <pre class="code-box" style="color: #94a3b8; font-size: 0.8rem;"><?= htmlspecialchars($e->getTraceAsString()) ?></pre>
            <div>
                <a href="<?= $diagBase ?>/debug.php" class="btn-action">Run Diagnostic Tool (debug.php)</a>
                <a href="<?= $diagBase ?>/" class="btn-secondary">Return Home</a>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
});

// Polyfills for PHP < 8.0 compatibility
if (!function_exists('str_starts_with')) {
    function str_starts_with(string $haystack, string $needle): bool {
        return $needle === '' || strpos($haystack, $needle) === 0;
    }
}
if (!function_exists('str_ends_with')) {
    function str_ends_with(string $haystack, string $needle): bool {
        return $needle === '' || substr($haystack, -strlen($needle)) === $needle;
    }
}
if (!function_exists('str_contains')) {
    function str_contains(string $haystack, string $needle): bool {
        return $needle === '' || strpos($haystack, $needle) !== false;
    }
}

// Load Composer autoloader if present, else fallback to custom PSR-4 autoloader
if (file_exists(ROOT_DIR . '/vendor/autoload.php')) {
    require_once ROOT_DIR . '/vendor/autoload.php';
} else {
    spl_autoload_register(function ($class) {
        $prefix = 'App\\';
        $base_dir = APP_DIR . '/';
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            return;
        }
        $relative_class = substr($class, $len);
        $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    });
}

// Start PHP Session securely
if (session_status() === PHP_SESSION_NONE) {
    if (!headers_sent()) {
        @ini_set('session.cookie_httponly', 1);
        @ini_set('session.use_only_cookies', 1);
    }
    @session_start();
}

// Load Application Configurations
require_once APP_DIR . '/Config/AppConfig.php';

