<?php
require_once __DIR__ . '/vendor/autoload.php';
use HBM\Helpers\Env;
Env::load(__DIR__ . '/.env');
try {
    $db = \HBM\Core\Database::getConnection();
    echo "Connected successfully\n";
} catch (Exception $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
}
