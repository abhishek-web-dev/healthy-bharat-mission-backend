<?php
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/src/Helpers/Env.php';
require __DIR__ . '/src/Core/Database.php';

use HBM\Helpers\Env;
use HBM\Core\Database;

Env::load(__DIR__ . '/.env');
date_default_timezone_set('Asia/Kolkata');

$db = Database::getConnection();
$stmt = $db->query("SELECT * FROM sessions LIMIT 5");
$sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "SESSIONS:\n";
print_r($sessions);

$tzStmt = $db->query("SELECT @@global.time_zone as gt, @@session.time_zone as st, NOW() as db_now, UNIX_TIMESTAMP(NOW()) as db_ts");
$tz = $tzStmt->fetch(PDO::FETCH_ASSOC);
echo "\nTIMEZONES:\n";
print_r($tz);
echo "PHP NOW: " . date('Y-m-d H:i:s') . "\n";
echo "PHP UNIX: " . time() . "\n";
