<?php
namespace HBM\Repositories;

use HBM\Core\Database;
use PDO;

class AdminSettingRepository {
    private function getDb(): PDO {
        return Database::getConnection();
    }

    public function getAllSettings(): array {
        $db = $this->getDb();
        $stmt = $db->query("SELECT setting_key, setting_value, setting_type FROM settings");
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $settings = [];
        foreach ($results as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        return $settings;
    }

    public function updateSetting(string $key, $value, ?int $userId): void {
        $db = $this->getDb();
        $stmt = $db->prepare("
            INSERT INTO settings (setting_key, setting_value, updated_by, created_at, updated_at) 
            VALUES (?, ?, ?, NOW(), NOW())
            ON DUPLICATE KEY UPDATE setting_value = ?, updated_by = ?, updated_at = NOW()
        ");
        $stmt->execute([$key, $value, $userId, $value, $userId]);
    }
}
