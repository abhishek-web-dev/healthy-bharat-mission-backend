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
$stmt = $db->query("DESCRIBE product_reviews");
print_r($stmt->fetchAll(\PDO::FETCH_ASSOC));
