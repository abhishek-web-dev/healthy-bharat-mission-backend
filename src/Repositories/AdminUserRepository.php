<?php

namespace HBM\Repositories;

use HBM\Core\Database;
use PDO;

class AdminUserRepository {
    private function getDb(): PDO {
        return Database::getConnection();
    }

    public function getAllUsers(int $limit = 50, int $offset = 0): array {
        $db = $this->getDb();
        $stmt = $db->prepare("SELECT u.id, u.first_name, u.last_name, u.email, u.phone, r.slug as role_slug, u.status, u.created_at FROM users u JOIN roles r ON u.role_id = r.id ORDER BY u.created_at DESC LIMIT :limit OFFSET :offset");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUserById(int $id): ?array {
        $stmt = $this->getDb()->prepare("SELECT u.id, u.first_name, u.last_name, u.email, u.phone, r.slug as role_slug, u.status, u.created_at FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = :id");
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ?: null;
    }

    public function updateUserRole(int $id, string $roleSlug): void {
        $stmtRole = $this->getDb()->prepare("SELECT id FROM roles WHERE slug = :slug");
        $stmtRole->execute(['slug' => $roleSlug]);
        $roleId = $stmtRole->fetchColumn();
        
        $stmt = $this->getDb()->prepare("UPDATE users SET role_id = :role_id WHERE id = :id");
        $stmt->execute(['role_id' => $roleId, 'id' => $id]);
    }

    public function updateUserStatus(int $id, string $status): void {
        $stmt = $this->getDb()->prepare("UPDATE users SET status = :status WHERE id = :id");
        $stmt->execute(['status' => $status, 'id' => $id]);
    }
}
