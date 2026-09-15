<?php

namespace HBM\Services;

use HBM\Core\Database;

class AdminActivityLogger {
    public static function log(int $userId, string $action, ?string $entityType = null, ?int $entityId = null, ?array $details = null): void {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            INSERT INTO admin_activity_logs (user_id, action, entity_type, entity_id, details, ip_address) 
            VALUES (:user_id, :action, :entity_type, :entity_id, :details, :ip_address)
        ");
        
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        
        $stmt->execute([
            'user_id' => $userId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'details' => $details ? json_encode($details) : null,
            'ip_address' => $ip
        ]);
    }
}
