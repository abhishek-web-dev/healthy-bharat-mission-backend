<?php
require __DIR__ . '/../src/Helpers/Request.php';
$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer 12345';
$token = \HBM\Helpers\Request::getBearerToken();
echo "Token: " . $token . "\n";
