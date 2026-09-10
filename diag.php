<?php
require_once __DIR__ . '/src/Helpers/Env.php';
require_once __DIR__ . '/src/Core/Database.php';

use HBM\Helpers\Env;
use HBM\Core\Database;

Env::load(__DIR__ . '/.env');

$host = Env::get('DB_HOST', '127.0.0.1');
$port = Env::get('DB_PORT', '3306');
$db   = Env::get('DB_DATABASE', 'healthy_bharat_mission');
$user = Env::get('DB_USERNAME', 'root');
$pass = Env::get('DB_PASSWORD', '');

echo "DB_HOST = " . ($host ? "configured" : "not configured") . "\n";
echo "DB_PORT = " . ($port ? "configured" : "not configured") . "\n";
echo "DB_DATABASE = " . ($db === 'healthy_bharat_mission' ? "healthy_bharat_mission" : "not configured") . "\n";
echo "DB_USERNAME = " . ($user === 'hbm_app' ? "hbm_app" : "not configured (" . $user . ")") . "\n";
echo "DB_PASSWORD = " . ($pass ? "set" : "not set") . "\n";

try {
    $pdo = Database::getConnection();
    echo "SUCCESS\n";
} catch (Exception $e) {
    echo "FAILED\n";
    echo $e->getMessage() . "\n";
    // Get more details
    try {
        $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass);
    } catch (\PDOException $ex) {
        echo "SQLSTATE: " . $ex->getCode() . "\n";
        echo "Message: " . $ex->getMessage() . "\n";
        echo "Host: " . $host . "\n";
        echo "Port: " . $port . "\n";
        echo "Database: " . $db . "\n";
        echo "Username: " . $user . "\n";
    }
}
