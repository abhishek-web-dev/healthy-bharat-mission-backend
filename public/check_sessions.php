<?php
require __DIR__ . '/index.php'; // This loads env and autoloader
use HBM\Core\Database;

try {
    $db = Database::getConnection();
    $stmt = $db->query("SELECT * FROM sessions LIMIT 5");
    $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "SESSIONS:\n";
    print_r($sessions);
    
    // Check timezone
    $tzStmt = $db->query("SELECT @@global.time_zone as gt, @@session.time_zone as st, NOW() as db_now, UNIX_TIMESTAMP(NOW()) as db_ts");
    $tz = $tzStmt->fetch(PDO::FETCH_ASSOC);
    echo "\nTIMEZONES:\n";
    print_r($tz);
    echo "PHP NOW: " . date('Y-m-d H:i:s') . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
