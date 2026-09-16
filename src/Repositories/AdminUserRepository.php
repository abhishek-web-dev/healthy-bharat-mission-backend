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
        $stmt = $db->prepare("SELECT u.id, u.first_name, u.last_name, u.email, u.phone, r.slug as role_slug, u.status, u.created_at FROM users u JOIN roles r ON u.role_id = r.id WHERE u.deleted_at IS NULL ORDER BY u.created_at DESC LIMIT :limit OFFSET :offset");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDeletedUsers(int $limit = 50, int $offset = 0): array {
        $db = $this->getDb();
        $stmt = $db->prepare("SELECT u.id, u.first_name, u.last_name, u.email, u.phone, r.slug as role_slug, u.status, u.deleted_at FROM users u JOIN roles r ON u.role_id = r.id WHERE u.deleted_at IS NOT NULL ORDER BY u.deleted_at DESC LIMIT :limit OFFSET :offset");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUserById(int $id): ?array {
        $stmt = $this->getDb()->prepare("SELECT u.id, u.first_name, u.last_name, u.email, u.phone, r.slug as role_slug, u.status, u.created_at, u.deleted_at FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = :id");
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ?: null;
    }

    public function getRoleBySlug(string $roleSlug): ?array {
        $stmt = $this->getDb()->prepare("SELECT id, name, slug FROM roles WHERE slug = :slug");
        $stmt->execute(['slug' => $roleSlug]);
        $role = $stmt->fetch(PDO::FETCH_ASSOC);
        return $role ?: null;
    }

    public function updateUserRole(int $id, string $roleSlug): void {
        $role = $this->getRoleBySlug($roleSlug);
        if (!$role) {
            throw new \Exception("Invalid role slug");
        }
        
        $stmt = $this->getDb()->prepare("UPDATE users SET role_id = :role_id WHERE id = :id");
        $stmt->execute(['role_id' => $role['id'], 'id' => $id]);
    }

    public function updateUserStatus(int $id, string $status): void {
        $stmt = $this->getDb()->prepare("UPDATE users SET status = :status WHERE id = :id");
        $stmt->execute(['status' => $status, 'id' => $id]);
    }

    public function softDeleteUser(int $id): void {
        $db = $this->getDb();
        $db->beginTransaction();
        try {
            // Soft delete user
            $stmt = $db->prepare("UPDATE users SET deleted_at = NOW(), status = 'inactive' WHERE id = :id");
            $stmt->execute(['id' => $id]);
            
            // Delete active sessions
            $sessionStmt = $db->prepare("DELETE FROM sessions WHERE user_id = :id");
            $sessionStmt->execute(['id' => $id]);
            
            $db->commit();
        } catch (\Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public function restoreUser(int $id): void {
        $stmt = $this->getDb()->prepare("UPDATE users SET deleted_at = NULL, status = 'active' WHERE id = :id");
        $stmt->execute(['id' => $id]);
    }
}
