<?php
namespace App\Config;

class AppConfig {
    public static string $name = 'Pikvero';
    public static string $tagline = 'Streetside Pickleball Court Management & Booking';
    public static string $baseUrl = 'http://localhost/pikvero';
    public static string $timezone = 'Asia/Manila';
    public static string $currency = 'PHP';
    public static string $currencySymbol = '₱';

    public static function init(): void {
        if (isset($_SERVER['HTTP_HOST'])) {
            $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
            $protocol = $isHttps ? 'https://' : 'http://';
            $host = $_SERVER['HTTP_HOST'];
            
            $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
            $subfolder = (strpos($scriptName, '/pikvero/') === 0 || strpos($scriptName, '/pikvero') === 0) ? '/pikvero' : '';
            
            self::$baseUrl = rtrim($protocol . $host . $subfolder, '/');
        }

        if (getenv('APP_BASE_URL')) {
            self::$baseUrl = rtrim(getenv('APP_BASE_URL'), '/');
        }

        date_default_timezone_set(self::$timezone);
    }
}

// Initialize application configuration
AppConfig::init();

