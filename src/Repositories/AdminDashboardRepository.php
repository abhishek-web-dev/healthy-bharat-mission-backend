<?php

namespace HBM\Repositories;

use HBM\Core\Database;
use PDO;

class AdminDashboardRepository {
    private function getDb(): PDO {
        return Database::getConnection();
    }

    public function getStats(): array {
        $db = $this->getDb();
        
        $stats = [];
        
        $stats['total_users'] = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $stats['total_orders'] = $db->query("SELECT COUNT(*) FROM orders")->fetchColumn();
        $stats['total_products'] = $db->query("SELECT COUNT(*) FROM products")->fetchColumn();
        $stats['active_programs'] = $db->query("SELECT COUNT(*) FROM programs WHERE is_active = 1")->fetchColumn();
        $stats['total_articles'] = $db->query("SELECT COUNT(*) FROM articles WHERE status = 'published'")->fetchColumn();
        $stats['pending_inquiries'] = $db->query("SELECT COUNT(*) FROM contact_inquiries WHERE status = 'pending'")->fetchColumn();
        $stats['total_subscribers'] = $db->query("SELECT COUNT(*) FROM newsletter_subscribers WHERE is_active = 1")->fetchColumn();
        
        return $stats;
    }

    public function getRecentActivity(): array {
        $db = $this->getDb();
        $stmt = $db->query("
            SELECT a.*, u.email as user_email 
            FROM admin_activity_logs a
            JOIN users u ON a.user_id = u.id
            ORDER BY a.created_at DESC 
            LIMIT 10
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
