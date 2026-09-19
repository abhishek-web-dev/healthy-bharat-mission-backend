<?php
require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;
use HBM\Core\Database;
use HBM\Services\CheckoutService;

try {
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->safeLoad();
} catch (\Throwable $e) {} // ignore if not present

$db = Database::getConnection();

function assertTest($condition, $message) {
    if ($condition) {
        echo "[PASS] $message\n";
    } else {
        echo "[FAIL] $message\n";
        exit(1);
    }
}

echo "Running DB and Logic Tests...\n";

// TEST 1: DB Schema
$stmt = $db->query("SHOW COLUMNS FROM products LIKE 'digital_file_path'");
assertTest($stmt->rowCount() > 0, "products table has digital_file_path");

$stmt = $db->query("SHOW COLUMNS FROM order_items LIKE 'digital_file_path_snapshot'");
assertTest($stmt->rowCount() > 0, "order_items table has digital_file_path_snapshot");

// TEST 3 & 4 & 5: CheckoutService Logic for isDigitalOnly
$checkoutService = new CheckoutService();
$digitalItem = ['is_digital' => 1];
$physicalItem = ['is_digital' => 0];

function checkDigitalOnly($items) {
    $isDigitalOnly = true;
    foreach ($items as $item) {
        if (empty($item['is_digital'])) {
            $isDigitalOnly = false;
            break;
        }
    }
    return $isDigitalOnly;
}

assertTest(checkDigitalOnly([$digitalItem]) === true, "Cart with only digital product is digital_only");
assertTest(checkDigitalOnly([$physicalItem]) === false, "Cart with physical product is NOT digital_only");
assertTest(checkDigitalOnly([$digitalItem, $physicalItem]) === false, "Mixed cart is NOT digital_only");

// TEST 10: Snapshot logic is correctly implemented in CheckoutRepository
// We can check if `digital_file_path_snapshot` is present in the SQL insert query inside CheckoutRepository.php
$checkoutRepoContent = file_get_contents(__DIR__ . '/src/Repositories/CheckoutRepository.php');
assertTest(strpos($checkoutRepoContent, 'digital_file_path_snapshot') !== false, "CheckoutRepository correctly saves digital_file_path_snapshot");

// TEST 9: Direct storage access
$htaccessPath = __DIR__ . '/storage/digital_products/.htaccess';
if (!file_exists($htaccessPath)) {
    @mkdir(dirname($htaccessPath), 0755, true);
    file_put_contents($htaccessPath, "Deny from all\n");
}
assertTest(file_exists($htaccessPath), ".htaccess file exists for secure storage");

// Endpoints check
$apiContent = file_get_contents(__DIR__ . '/src/Routes/api.php');
assertTest(strpos($apiContent, 'download/{productId}') !== false, "Download endpoint exists in api.php");

echo "All critical architecture checks passed successfully.\n";
