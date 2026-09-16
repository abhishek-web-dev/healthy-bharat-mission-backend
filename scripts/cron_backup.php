<?php
// Secure CLI-only check
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die("Forbidden - This script can only be run from the command line.");
}

$backendDir = dirname(__DIR__);

// Load Composer Autoloader
require_once $backendDir . '/vendor/autoload.php';

// Setup Custom Autoloader for HBM namespace
spl_autoload_register(function ($class) use ($backendDir) {
    $prefix = 'HBM\\';
    $base_dir = $backendDir . '/src/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) require $file;
});

// Load Environment Variables
\HBM\Helpers\Env::load($backendDir . '/.env');

use HBM\Services\DatabaseBackupService;
use HBM\Repositories\AdminSettingRepository;

echo "[".date('Y-m-d H:i:s')."] Starting automated database backup check...\n";

try {
    $settingsRepo = new AdminSettingRepository();
    $settings = $settingsRepo->getAllSettings();
    
    $isEnabled = filter_var($settings['backup_automated_enabled'] ?? 'false', FILTER_VALIDATE_BOOLEAN);
    
    if (!$isEnabled) {
        echo "[".date('Y-m-d H:i:s')."] Automated backups are disabled in settings. Exiting.\n";
        exit(0);
    }
    
    // Check if the current time matches the scheduled time (within a small window or exact hour depending on cron setup)
    // Since this script is ideally run ONCE a day via Hostinger cron at the exact time configured,
    // we assume if it's executed, it's time to run it.
    // However, if the cron runs every minute or hour, we would need to check the time.
    // Assuming a generic daily cron, we will just run the backup.
    
    echo "[".date('Y-m-d H:i:s')."] Creating backup...\n";
    
    $service = new DatabaseBackupService();
    // Pass null for created_by since it's automated
    $result = $service->createBackup(null);
    
    echo "[".date('Y-m-d H:i:s')."] Backup created successfully: " . $result['filename'] . " (" . $result['file_size'] . " bytes)\n";
    exit(0);
    
} catch (\Exception $e) {
    echo "[".date('Y-m-d H:i:s')."] Backup failed: " . $e->getMessage() . "\n";
    exit(1);
}
