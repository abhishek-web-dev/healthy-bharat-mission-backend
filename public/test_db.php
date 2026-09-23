<?php
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../src/Core/Database.php';

use HBM\Core\Database;

try {
    $db = Database::getConnection();
    
    // Check users
    $stmt = $db->query("SELECT id, email, password_hash, role_id, status FROM users LIMIT 5");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "USERS:\n";
    print_r($users);
    
    // Check MySQL Timezone and PHP timezone
    date_default_timezone_set('Asia/Kolkata');
    $stmt2 = $db->query("SELECT @@global.time_zone as gt, @@session.time_zone as st, NOW() as db_now, UNIX_TIMESTAMP(NOW()) as db_ts");
    $tz = $stmt2->fetch(PDO::FETCH_ASSOC);
    echo "\nTIMEZONES:\n";
    print_r($tz);
    echo "PHP NOW: " . date('Y-m-d H:i:s') . "\n";
    echo "PHP UNIX: " . time() . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
