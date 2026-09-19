<?php

require_once __DIR__ . '/../vendor/autoload.php';

use HBM\Core\Database;

// Load .env
$dotenvPath = __DIR__ . '/../.env';
if (file_exists($dotenvPath)) {
    $lines = file($dotenvPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2) + [NULL, NULL];
        if (!empty($name)) {
            putenv(trim($name) . '=' . trim($value));
        }
    }
}

$dbHost = getenv('DB_HOST') ?: 'localhost';
$dbPort = getenv('DB_PORT') ?: '3306';
$dbName = getenv('DB_DATABASE') ?: 'healthy_bharat_mission';
$dbUser = getenv('DB_USERNAME') ?: 'hbm_app';
$dbPass = getenv('DB_PASSWORD') ?: 'HBM_Dev_2026_Strong!';

try {
    $db = new PDO("mysql:host=$dbHost;port=$dbPort;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    // Create table
    $sql = "CREATE TABLE IF NOT EXISTS contact_interest_options (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        label VARCHAR(255) NOT NULL,
        value VARCHAR(255) NOT NULL UNIQUE,
        is_active TINYINT(1) DEFAULT 1,
        sort_order INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $db->exec($sql);
    echo "Table contact_interest_options created or already exists.\n";
    
    // Seed options
    $options = [
        ['Health Consultation', 'consultation', 1],
        ['Customized Diet Plan', 'diet', 2],
        ['Supplements & Products', 'products', 3],
        ['General Inquiry', 'other', 4]
    ];
    
    $stmt = $db->prepare("INSERT IGNORE INTO contact_interest_options (label, value, sort_order) VALUES (?, ?, ?)");
    
    foreach ($options as $opt) {
        $stmt->execute([$opt[0], $opt[1], $opt[2]]);
    }
    
    echo "Seed completed successfully.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
