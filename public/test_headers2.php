<?php
require __DIR__ . '/../src/Helpers/Request.php';
// Simulate getallheaders returning lowercase
if (!function_exists('getallheaders')) {
    function getallheaders() {
        return ['authorization' => 'Bearer 123456789'];
    }
}
$token = \HBM\Helpers\Request::getBearerToken();
echo "Token: " . var_export($token, true) . "\n";
