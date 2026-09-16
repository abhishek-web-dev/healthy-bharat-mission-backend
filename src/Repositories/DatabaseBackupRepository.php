<?php
namespace HBM\Repositories;

use HBM\Core\Database;
use PDO;

class DatabaseBackupRepository {
    private function getDb(): PDO {
        return Database::getConnection();
    }

    public function createBackupRecord(string $filename, string $storagePath, ?int $createdBy = null): int {
        $db = $this->getDb();
        $stmt = $db->prepare("
            INSERT INTO database_backups (filename, storage_path, status, created_by) 
            VALUES (?, ?, 'IN_PROGRESS', ?)
        ");
        $stmt->execute([$filename, $storagePath, $createdBy]);
        return (int)$db->lastInsertId();
    }

    public function updateBackupStatus(int $id, string $status, int $fileSize = 0, ?string $errorMessage = null): void {
        $db = $this->getDb();
        $stmt = $db->prepare("
            UPDATE database_backups 
            SET status = ?, file_size = ?, error_message = ?, completed_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$status, $fileSize, $errorMessage, $id]);
    }

    public function getBackups(int $limit = 50, int $offset = 0): array {
        $db = $this->getDb();
        $stmt = $db->prepare("
            SELECT b.*, u.first_name, u.last_name 
            FROM database_backups b 
            LEFT JOIN users u ON b.created_by = u.id 
            ORDER BY b.created_at DESC 
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getBackupById(int $id): ?array {
        $db = $this->getDb();
        $stmt = $db->prepare("SELECT * FROM database_backups WHERE id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }
}
