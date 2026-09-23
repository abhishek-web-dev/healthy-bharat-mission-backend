<?php
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/src/Helpers/Env.php';
use HBM\Helpers\Env;
Env::load(__DIR__ . '/.env');
date_default_timezone_set('Asia/Kolkata');

$token = 'fe25e7ae2cd727895a012ccf681f15056050a4d0b2cb7d196344572808d95769';

$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;

$middleware = new \HBM\Middleware\AuthMiddleware();
try {
    $middleware::handle();
    global $authUser;
    echo "SUCCESS: User is " . $authUser['email'] . "\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
