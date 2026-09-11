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
            // Use Railway's native injected variables if they exist, otherwise fallback to local .env
            $host = Env::get('MYSQLHOST', Env::get('DB_HOST', '127.0.0.1'));
            $port = Env::get('MYSQLPORT', Env::get('DB_PORT', '3306'));
            $db   = Env::get('MYSQLDATABASE', Env::get('DB_DATABASE', 'healthy_bharat_mission'));
            $user = Env::get('MYSQLUSER', Env::get('DB_USERNAME', 'root'));
            $pass = Env::get('MYSQLPASSWORD', Env::get('DB_PASSWORD', ''));
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
