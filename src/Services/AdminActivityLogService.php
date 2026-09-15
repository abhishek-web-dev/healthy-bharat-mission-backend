<?php
namespace HBM\Services;
use HBM\Core\Database;

class AdminActivityLogService {
    public static function log(int $userId, string $action, ?string $entityType = null, ?int $entityId = null, ?array $details = null): void {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                INSERT INTO admin_activity_logs (user_id, action, entity_type, entity_id, details, ip_address, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $ip = $_SERVER['REMOTE_ADDR'] ?? null;
            $detailsJson = $details ? json_encode($details) : null;
            
            $stmt->execute([$userId, $action, $entityType, $entityId, $detailsJson, $ip]);
        } catch (\Exception $e) {
            // Silently fail if logging fails so it doesn't break the main business logic
            error_log("Failed to write to admin_activity_logs: " . $e->getMessage());
        }
    }
}
