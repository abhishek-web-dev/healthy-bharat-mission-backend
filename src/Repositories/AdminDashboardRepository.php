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

        // Users
        $stats['total_users'] = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $stats['active_users'] = $db->query("SELECT COUNT(*) FROM users WHERE status = 'active'")->fetchColumn();

        // Experts
        $stats['total_experts'] = $db->query("
            SELECT COUNT(*) FROM users u 
            JOIN roles r ON u.role_id = r.id 
            WHERE r.slug = 'expert'
        ")->fetchColumn();
        
        $stats['active_experts'] = $db->query("
            SELECT COUNT(*) FROM users u 
            JOIN roles r ON u.role_id = r.id 
            WHERE r.slug = 'expert' AND u.status = 'active'
        ")->fetchColumn();

        // Programs & Content
        $stats['total_programs'] = $db->query("SELECT COUNT(*) FROM programs")->fetchColumn();
        $stats['published_articles'] = $db->query("SELECT COUNT(*) FROM articles WHERE status = 'published'")->fetchColumn();
        $stats['active_health_conditions'] = $db->query("SELECT COUNT(*) FROM health_conditions WHERE is_active = 1")->fetchColumn();

        // Store
        $stats['active_products'] = $db->query("SELECT COUNT(*) FROM products WHERE is_active = 1")->fetchColumn();
        $stats['total_orders'] = $db->query("SELECT COUNT(*) FROM orders")->fetchColumn();
        $stats['pending_orders'] = $db->query("SELECT COUNT(*) FROM orders WHERE order_status = 'processing'")->fetchColumn();

        // Appointments
        $stats['total_appointments'] = $db->query("SELECT COUNT(*) FROM appointments")->fetchColumn();
        $stats['pending_appointments'] = $db->query("SELECT COUNT(*) FROM appointments WHERE status = 'pending'")->fetchColumn();

        // CMS
        $stats['pending_inquiries'] = $db->query("SELECT COUNT(*) FROM contact_inquiries WHERE status = 'pending'")->fetchColumn();
        $stats['active_subscribers'] = $db->query("SELECT COUNT(*) FROM newsletter_subscribers WHERE is_active = 1")->fetchColumn();

        return $stats;
    }

    public function getRecentActivity(): array {
        $db = $this->getDb();
        
        // Recent Orders
        $recentOrders = $db->query("
            SELECT o.id, o.total_amount, o.order_status as status, o.created_at, u.first_name, u.last_name 
            FROM orders o
            JOIN users u ON o.user_id = u.id
            ORDER BY o.created_at DESC LIMIT 5
        ")->fetchAll(PDO::FETCH_ASSOC);

        // Recent Appointments
        $recentAppointments = $db->query("
            SELECT a.id, a.appointment_date, a.appointment_time, a.status, a.created_at,
                   p.first_name as patient_first_name, p.last_name as patient_last_name,
                   e.first_name as expert_first_name, e.last_name as expert_last_name
            FROM appointments a
            JOIN users p ON a.user_id = p.id
            JOIN users e ON a.expert_id = e.id
            ORDER BY a.created_at DESC LIMIT 5
        ")->fetchAll(PDO::FETCH_ASSOC);

        // Recent Inquiries
        $recentInquiries = $db->query("
            SELECT id, name, subject, status, created_at
            FROM contact_inquiries
            ORDER BY created_at DESC LIMIT 5
        ")->fetchAll(PDO::FETCH_ASSOC);

        return [
            'orders' => $recentOrders,
            'appointments' => $recentAppointments,
            'inquiries' => $recentInquiries
        ];
    }
}
