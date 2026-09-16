<?php
namespace HBM\Services;

use HBM\Repositories\DatabaseBackupRepository;
use HBM\Services\AdminActivityLogService;
use Exception;

class DatabaseBackupService {
    private DatabaseBackupRepository $repo;

    public function __construct() {
        $this->repo = new DatabaseBackupRepository();
    }

    public function createBackup(?int $userId = null): array {
        $dateStr = date('Y-m-d_H-i-s');
        $filename = "hbm_backup_{$dateStr}.sql.gz";
        $storageDir = realpath(__DIR__ . '/../../storage/private/backups');
        
        if (!$storageDir) {
            // Attempt to create if it doesn't exist
            $targetDir = __DIR__ . '/../../storage/private/backups';
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0700, true);
            }
            $storageDir = realpath($targetDir);
            if (!$storageDir) {
                throw new Exception("Backup storage directory is not accessible.");
            }
        }

        $filePath = $storageDir . DIRECTORY_SEPARATOR . $filename;
        
        // 1. Create IN_PROGRESS record
        $backupId = $this->repo->createBackupRecord($filename, $filePath, $userId);

        // 2. Fetch DB Credentials
        $dbHost = $_ENV['DB_HOST'] ?? '127.0.0.1';
        $dbPort = $_ENV['DB_PORT'] ?? '3306';
        $dbName = $_ENV['DB_DATABASE'] ?? '';
        $dbUser = $_ENV['DB_USERNAME'] ?? '';
        $dbPass = $_ENV['DB_PASSWORD'] ?? '';

        if (empty($dbName) || empty($dbUser)) {
            $this->repo->updateBackupStatus($backupId, 'FAILED', 0, 'Database credentials missing from environment.');
            if ($userId) {
                AdminActivityLogService::log($userId, 'BACKUP_FAILED', 'database_backups', $backupId, ['error' => 'Missing DB credentials']);
            }
            throw new Exception("Database configuration is missing.");
        }

        // 3. Construct command securely
        // Using mysqldump piped into gzip
        $cmd = sprintf(
            'mysqldump --no-tablespaces -h %s -P %s -u %s %s %s | gzip > %s',
            escapeshellarg($dbHost),
            escapeshellarg($dbPort),
            escapeshellarg($dbUser),
            !empty($dbPass) ? '-p' . escapeshellarg($dbPass) : '',
            escapeshellarg($dbName),
            escapeshellarg($filePath)
        );

        // 4. Execute command
        $output = [];
        $returnVar = 0;
        exec($cmd . ' 2>&1', $output, $returnVar);

        // 5. Verify Backup
        if ($returnVar !== 0 || !file_exists($filePath) || filesize($filePath) === 0) {
            $errorMsg = "Command failed with code $returnVar.";
            if (file_exists($filePath) && filesize($filePath) === 0) {
                $errorMsg = "Created backup file is empty.";
                unlink($filePath); // Clean up empty file
            }
            
            $this->repo->updateBackupStatus($backupId, 'FAILED', 0, $errorMsg);
            if ($userId) {
                AdminActivityLogService::log($userId, 'BACKUP_FAILED', 'database_backups', $backupId, ['error' => $errorMsg]);
            }
            throw new Exception("Backup failed: " . $errorMsg);
        }

        // 6. Success
        $fileSize = filesize($filePath);
        $this->repo->updateBackupStatus($backupId, 'SUCCESS', $fileSize);
        if ($userId) {
            AdminActivityLogService::log($userId, 'BACKUP_CREATED', 'database_backups', $backupId, ['filename' => $filename, 'size' => $fileSize]);
        }

        return [
            'id' => $backupId,
            'filename' => $filename,
            'status' => 'SUCCESS',
            'file_size' => $fileSize
        ];
    }
}
