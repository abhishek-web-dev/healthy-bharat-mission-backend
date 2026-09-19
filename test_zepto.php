<?php
require __DIR__ . '/vendor/autoload.php';

spl_autoload_register(function ($class) {
    $prefix = 'HBM\\';
    $base_dir = __DIR__ . '/src/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) require $file;
});

\HBM\Helpers\Env::load(__DIR__ . '/.env');

$service = new \HBM\Services\EmailService();

// Test Admin OTP
$adminBody = \HBM\Services\EmailTemplateService::getAdminOtpEmail('123456');
$result1 = $service->sendEmail('testadmin2@healthybharatmission.com', 'HBM Admin Login Verification Code', $adminBody, 'Admin');

// Test Order Confirmation
$dummyOrder = [
    'order_number' => 'ORD-123',
    'shipping_first_name' => 'John',
    'shipping_last_name' => 'Doe',
    'shipping_email' => 'testcustomer2@healthybharatmission.com',
    'total_amount' => 599.00,
    'payment_method' => 'upi',
    'created_at' => date('Y-m-d H:i:s'),
    'items' => [
        ['product_name' => 'Test Product', 'quantity' => 1, 'price' => 599.00]
    ]
];
$orderBody = \HBM\Services\EmailTemplateService::getOrderConfirmationEmail($dummyOrder);
$result2 = $service->sendEmail('testcustomer2@healthybharatmission.com', 'Order Confirmation - ORD-123', $orderBody, 'John');

echo "Admin OTP: " . ($result1 ? 'SUCCESS' : 'FAILED') . "\n";
echo "Order Conf: " . ($result2 ? 'SUCCESS' : 'FAILED') . "\n";
