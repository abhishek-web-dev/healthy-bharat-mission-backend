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
$db = \HBM\Core\Database::getConnection();

// 1. Get a customer user
$stmt = $db->query("SELECT id, email FROM users WHERE role_id = (SELECT id FROM roles WHERE slug = 'customer') AND status = 'active' LIMIT 1");
$user = $stmt->fetch();
if (!$user) die("No customer user found\n");

// 2. Create a session to get a token
$token = bin2hex(random_bytes(32));
$expiresAt = date('Y-m-d H:i:s', time() + 3600);
$db->prepare("INSERT INTO user_sessions (user_id, token, ip_address, user_agent, expires_at, created_at) VALUES (?, ?, '127.0.0.1', 'CLI', ?, NOW())")->execute([$user['id'], $token, $expiresAt]);

echo "TOKEN: " . $token . "\n";
