<?php

namespace HBM\Repositories;

use HBM\Core\Database;
use PDO;
use Exception;

class AdminTeamRepository {
    public function getRoles(): array {
        $db = Database::getConnection();
        $roles = $db->query("SELECT * FROM roles ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($roles as &$role) {
            $stmt = $db->prepare("SELECT p.slug FROM role_permissions rp JOIN permissions p ON rp.permission_id = p.id WHERE rp.role_id = ?");
            $stmt->execute([$role['id']]);
            $role['permissions'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
        }
        
        return $roles;
    }

    public function getPermissionsGroupedByModule(): array {
        $db = Database::getConnection();
        $perms = $db->query("SELECT * FROM permissions")->fetchAll(PDO::FETCH_ASSOC);
        
        $grouped = [];
        foreach ($perms as $perm) {
            $parts = explode('_', $perm['slug'], 2);
            $action = $parts[0];
            $module = $parts[1] ?? 'general';
            
            if (!isset($grouped[$module])) {
                $grouped[$module] = [];
            }
            $grouped[$module][] = [
                'id' => $perm['id'],
                'name' => $perm['name'],
                'slug' => $perm['slug'],
                'action' => $action
            ];
        }
        return $grouped;
    }

    public function createRole(string $name, array $permissionSlugs): void {
        $db = Database::getConnection();
        $slug = strtolower(str_replace(' ', '_', $name));
        
        $db->beginTransaction();
        try {
            $stmt = $db->prepare("INSERT INTO roles (name, slug) VALUES (?, ?)");
            $stmt->execute([$name, $slug]);
            $roleId = $db->lastInsertId();
            
            if (!empty($permissionSlugs)) {
                $this->assignPermissionsToRole($db, $roleId, $permissionSlugs);
            }
            
            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public function updateRole(int $roleId, string $name, array $permissionSlugs): void {
        $db = Database::getConnection();
        
        $db->beginTransaction();
        try {
            $stmt = $db->prepare("UPDATE roles SET name = ? WHERE id = ?");
            $stmt->execute([$name, $roleId]);
            
            // Clear existing permissions
            $db->prepare("DELETE FROM role_permissions WHERE role_id = ?")->execute([$roleId]);
            
            if (!empty($permissionSlugs)) {
                $this->assignPermissionsToRole($db, $roleId, $permissionSlugs);
            }
            
            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    private function assignPermissionsToRole(PDO $db, int $roleId, array $permissionSlugs): void {
        $placeholders = implode(',', array_fill(0, count($permissionSlugs), '?'));
        $stmt = $db->prepare("SELECT id FROM permissions WHERE slug IN ($placeholders)");
        $stmt->execute($permissionSlugs);
        $permIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (!empty($permIds)) {
            $insertStmt = $db->prepare("INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
            foreach ($permIds as $permId) {
                $insertStmt->execute([$roleId, $permId]);
            }
        }
    }

    public function getTeamMembers(): array {
        $db = Database::getConnection();
        // Assume 'user' role is for normal customers. We want everyone else.
        $stmt = $db->query("
            SELECT u.id, u.first_name, u.last_name, u.email, u.phone, u.status, u.created_at, r.name as role_name, r.slug as role_slug, r.id as role_id
            FROM users u
            JOIN roles r ON u.role_id = r.id
            WHERE r.slug != 'user'
            ORDER BY u.id DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createTeamMember(array $data): int {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            INSERT INTO users (first_name, last_name, email, password_hash, role_id, status)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['first_name'],
            $data['last_name'],
            $data['email'],
            $data['password_hash'],
            $data['role_id'],
            $data['status'] ?? 'active'
        ]);
        return (int)$db->lastInsertId();
    }
}
