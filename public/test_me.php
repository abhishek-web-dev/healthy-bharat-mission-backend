<?php
require __DIR__ . '/index.php'; // Loads the app

// Simulate a logged-in request
$token = 'fe25e7ae2cd727895a012ccf681f15056050a4d0b2cb7d196344572808d95769'; // Token from DB

$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;

$middleware = new \HBM\Middleware\AuthMiddleware();
try {
    $middleware::handle();
    global $authUser;
    echo "SUCCESS: User is " . $authUser['email'] . "\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
