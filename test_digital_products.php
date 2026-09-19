<?php
require_once __DIR__ . '/vendor/autoload.php';
// Bootstrapping the application
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

// Setup mock session/DB if needed
use HBM\Core\Database;
use HBM\Services\AuthService;
use HBM\Services\CheckoutService;
use HBM\Services\AdminProductService;

$db = Database::getConnection();

function pass($msg) { echo "[PASS] $msg\n"; }
function fail($msg) { echo "[FAIL] $msg\n"; exit(1); }

echo "Running Digital Products E2E Tests...\n\n";

// TEST 14: PHP Syntax Checks
$files = [
    'src/Controllers/AdminProductController.php',
    'src/Services/AdminProductService.php',
    'src/Controllers/CheckoutController.php',
    'src/Services/CheckoutService.php',
    'src/Services/EmailTemplateService.php',
    '../frontend/js/checkout.js',
    '../frontend/js/order-details.js'
];
foreach ($files as $file) {
    if (!file_exists(__DIR__ . '/' . $file)) {
        fail("File not found: $file");
    }
    if (str_ends_with($file, '.php')) {
        $output = shell_exec("php -l " . escapeshellarg(__DIR__ . '/' . $file) . " 2>&1");
        if (strpos($output, 'No syntax errors') !== false) {
            pass("Syntax check passed: $file");
        } else {
            fail("Syntax check failed: $file\n$output");
        }
    } else {
        // Just verify file exists and is readable
        $content = file_get_contents(__DIR__ . '/' . $file);
        if (empty($content)) fail("File is empty: $file");
        pass("File exists and not empty: $file");
    }
}

// TEST 9: Direct Storage Access
$htaccessPath = __DIR__ . '/storage/digital_products/.htaccess';
if (file_exists($htaccessPath) && trim(file_get_contents($htaccessPath)) === 'Deny from all') {
    pass("Storage .htaccess is present and correctly configured.");
} else {
    fail("Storage .htaccess missing or incorrect.");
}

// Ensure the storage directory exists
$storageDir = __DIR__ . '/storage/digital_products';
if (!is_dir($storageDir)) {
    mkdir($storageDir, 0755, true);
}

// We'll write a temporary file to test file uploads
$tempPdfPath = __DIR__ . '/storage/test_upload.pdf';
file_put_contents($tempPdfPath, '%PDF-1.4 Mock PDF Content');

echo "\nTests completed (Syntax & Direct Access). For full E2E, we must mock HTTP requests or use the Service classes directly.\n";
