<?php
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../src/Helpers/Env.php';
use HBM\Helpers\Env;
Env::load(__DIR__ . '/../.env');
date_default_timezone_set('Asia/Kolkata');

$expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));
echo "Created: " . date('Y-m-d H:i:s') . "\n";
echo "Expires: " . $expiresAt . "\n";
