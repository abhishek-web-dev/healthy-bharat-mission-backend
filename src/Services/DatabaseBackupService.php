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

        // 3. Generate backup using native PHP PDO
        try {
            $pdo = new \PDO("mysql:host=$dbHost;port=$dbPort;dbname=$dbName", $dbUser, $dbPass);
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            
            $fp = gzopen($filePath, 'w9');
            if (!$fp) {
                throw new Exception("Could not open backup file for writing.");
            }
            
            $stmt = $pdo->query('SHOW TABLES');
            $tables = [];
            while ($row = $stmt->fetch(\PDO::FETCH_NUM)) {
                $tables[] = $row[0];
            }
            
            gzwrite($fp, "-- Database Backup: $dbName\n-- Generated on " . date('Y-m-d H:i:s') . "\n\n");
            gzwrite($fp, "SET FOREIGN_KEY_CHECKS=0;\n\n");
            
            foreach ($tables as $table) {
                $stmt = $pdo->query("SHOW CREATE TABLE `$table`");
                $row = $stmt->fetch(\PDO::FETCH_NUM);
                gzwrite($fp, "DROP TABLE IF EXISTS `$table`;\n");
                gzwrite($fp, $row[1] . ";\n\n");
                
                $stmt = $pdo->query("SELECT * FROM `$table`");
                while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                    $keys = array_keys($row);
                    $values = [];
                    foreach ($row as $val) {
                        if ($val === null) {
                            $values[] = "NULL";
                        } else {
                            $values[] = $pdo->quote($val);
                        }
                    }
                    $sql = "INSERT INTO `$table` (`" . implode("`, `", $keys) . "`) VALUES (" . implode(", ", $values) . ");\n";
                    gzwrite($fp, $sql);
                }
                gzwrite($fp, "\n");
            }
            
            gzwrite($fp, "SET FOREIGN_KEY_CHECKS=1;\n");
            gzclose($fp);
            $returnVar = 0;
            $errorMsg = "";
        } catch (\Exception $e) {
            $returnVar = 1;
            $errorMsg = $e->getMessage();
            if (isset($fp) && is_resource($fp)) {
                gzclose($fp);
            }
        }

        // 5. Verify Backup
        if ($returnVar !== 0 || !file_exists($filePath) || filesize($filePath) === 0) {
            if (empty($errorMsg)) {
                $errorMsg = "Command failed with code $returnVar.";
            }
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
