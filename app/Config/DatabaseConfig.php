<?php
namespace App\Config;

class DatabaseConfig {
    public static string $host = '127.0.0.1';
    public static int $port = 3306;
    public static string $dbname = 'picklehub_db';
    public static string $username = 'root';
    public static string $password = '';
    public static string $charset = 'utf8mb4';

    public static function loadEnvironment(): void {
        $localFile = __DIR__ . '/DatabaseConfig.local.php';
        if (file_exists($localFile)) {
            require_once $localFile;
        }

        if (getenv('DB_HOST')) self::$host = getenv('DB_HOST');
        if (getenv('DB_PORT')) self::$port = (int)getenv('DB_PORT');
        if (getenv('DB_NAME')) self::$dbname = getenv('DB_NAME');
        if (getenv('DB_USER')) self::$username = getenv('DB_USER');
        if (getenv('DB_PASS') !== false) self::$password = getenv('DB_PASS');
    }
}

// Auto-initialize configuration
DatabaseConfig::loadEnvironment();

