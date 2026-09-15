<?php

/**
 * Healthy Bharat Mission - Backend Entry Point
 */

// Disable display_errors so warnings don't corrupt JSON responses
ini_set('display_errors', '0');
error_reporting(E_ALL);

// Load Composer Autoloader
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require __DIR__ . '/../vendor/autoload.php';
}

// Allow direct PHP file access (e.g. migrate.php) when using built-in server router
if (php_sapi_name() === 'cli-server') {
    $file = __DIR__ . $_SERVER['REQUEST_URI'];
    $file = strtok($file, '?'); // strip query string
    if ($file !== __FILE__ && file_exists($file) && pathinfo($file, PATHINFO_EXTENSION) === 'php') {
        require $file;
        exit;
    }
}

// Basic Class Autoloader
spl_autoload_register(function ($class) {
    $prefix = 'HBM\\';
    $base_dir = __DIR__ . '/../src/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

use HBM\Helpers\Env;
use HBM\Helpers\Response;
use HBM\Middleware\CorsMiddleware;

// 1. Load Environment Variables
Env::load(__DIR__ . '/../.env');

// 2. Set Error Reporting based on Environment
if (Env::get('APP_DEBUG', false)) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}

// 3. Global Exception Handler
set_exception_handler(function (Throwable $e) {
    \HBM\Helpers\Logger::error("Uncaught Exception: " . $e->getMessage(), [
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    Response::error($e->getMessage(), 500);
});

// 4. Session Foundation (Secure settings)
$sessionLifetime = Env::get('SESSION_LIFETIME', 120) * 60;
$sessionSecure = Env::get('SESSION_SECURE_COOKIE', false) ? true : false;
session_set_cookie_params([
    'lifetime' => $sessionLifetime,
    'path' => '/',
    'domain' => '',
    'secure' => $sessionSecure,
    'httponly' => true,
    'samesite' => Env::get('SESSION_SAME_SITE', 'Lax')
]);
session_start();

// 5. Apply CORS Middleware
CorsMiddleware::handle();

// 6. Routing
$method = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

$router = require __DIR__ . '/../src/Routes/api.php';
$router->dispatch($method, $uri);
