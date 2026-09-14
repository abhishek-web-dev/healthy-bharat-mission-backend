<?php

namespace HBM\Core;

use PDO;
use PDOException;
use Exception;
use HBM\Helpers\Env;

class Database {
    private static ?PDO $instance = null;

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            // 1. First, check if Railway's MYSQL_URL is available (most reliable)
            $mysqlUrl = Env::get('MYSQL_URL', '');
            if (!empty($mysqlUrl)) {
                $parsedUrl = parse_url($mysqlUrl);
                $host = $parsedUrl['host'] ?? '127.0.0.1';
                $port = $parsedUrl['port'] ?? '3306';
                $db   = ltrim($parsedUrl['path'] ?? '/healthy_bharat_mission', '/');
                $user = $parsedUrl['user'] ?? 'root';
                $pass = $parsedUrl['pass'] ?? '';
            } else {
                // 2. Fallback to individual variables or local .env
                $host = Env::get('MYSQLHOST', Env::get('DB_HOST', '127.0.0.1'));
                $port = Env::get('MYSQLPORT', Env::get('DB_PORT', '3306'));
                $db   = Env::get('MYSQLDATABASE', Env::get('DB_DATABASE', 'healthy_bharat_mission'));
                $user = Env::get('MYSQLUSER', Env::get('DB_USERNAME', 'root'));
                $pass = Env::get('MYSQLPASSWORD', Env::get('DB_PASSWORD', ''));
            }
            
            // Allow native localhost for Unix socket (Hostinger requirement)
            // if ($host === 'localhost') {
            //     $host = '127.0.0.1';
            // }

            $charset = 'utf8mb4';

            $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false, // True prepared statements
            ];

            try {
                self::$instance = new PDO($dsn, $user, $pass, $options);
            } catch (PDOException $e) {
                // Do NOT expose credentials or detailed stack trace to clients
                error_log("Database Connection Error: " . $e->getMessage());
                throw new Exception("Database connection failed. Please check the logs.");
            }
        }

        return self::$instance;
    }
}
