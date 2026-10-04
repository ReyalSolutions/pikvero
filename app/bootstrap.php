<?php
/**
 * Pikvero Master Application Bootstrap
 */

// Define root directory
define('ROOT_DIR', dirname(__DIR__));
define('APP_DIR', __DIR__);

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

