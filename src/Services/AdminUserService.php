<?php

namespace HBM\Services;

use HBM\Repositories\AdminUserRepository;
use HBM\Services\AdminActivityLogger;
use Exception;

class AdminUserService {
    private AdminUserRepository $repo;

    public function __construct() {
        $this->repo = new AdminUserRepository();
    }

    public function getAllUsers(int $limit = 50, int $offset = 0): array {
        return $this->repo->getAllUsers($limit, $offset);
    }

    public function getUserById(int $id): array {
        $user = $this->repo->getUserById($id);
        if (!$user) {
            throw new Exception("User not found");
        }
        return $user;
    }

    public function updateUserRole(int $adminId, int $userId, string $roleSlug): void {
        $admin = $this->repo->getUserById($adminId);
        $role = $this->repo->getRoleBySlug($roleSlug);
        
        if (!$role) {
            throw new Exception("Invalid role");
        }

        $user = $this->repo->getUserById($userId);
        if (!$user) {
            throw new Exception("User not found");
        }

        if ($user['role_slug'] === 'superadmin' && $roleSlug !== 'superadmin') {
            throw new Exception("Cannot demote a superadmin");
        }

        if ($admin['role_slug'] !== 'superadmin' && $roleSlug === 'superadmin') {
            throw new Exception("You cannot assign the superadmin role.");
        }

        // Fetch permissions for the role we're trying to assign
        if ($admin['role_slug'] !== 'superadmin') {
            // Need to get admin's permissions and the target role's permissions
            // Since getUserById doesn't return permissions directly (AuthRepository does),
            // we can delegate to AuthRepository to get the admin's full profile
            $authRepo = new \HBM\Repositories\AuthRepository();
            $adminFull = $authRepo->getUserById($adminId);
            $adminPerms = $adminFull['permissions'] ?? [];

            $db = \HBM\Core\Database::getConnection();
            $stmt = $db->prepare("SELECT p.slug FROM role_permissions rp JOIN permissions p ON rp.permission_id = p.id WHERE rp.role_id = ?");
            $stmt->execute([$role['id']]);
            $rolePerms = $stmt->fetchAll(\PDO::FETCH_COLUMN);

            $unauthorizedPerms = array_diff($rolePerms, $adminPerms);
            if (!empty($unauthorizedPerms)) {
                throw new Exception("You cannot assign a role that has permissions you do not possess.");
            }
        }

        $this->repo->updateUserRole($userId, $roleSlug);
        AdminActivityLogger::log($adminId, 'UPDATE_ROLE', 'users', $userId, ['new_role' => $roleSlug]);
    }

    public function updateUserStatus(int $adminId, int $userId, string $status): void {
        $validStatuses = ['active', 'inactive', 'banned'];
        if (!in_array($status, $validStatuses)) {
            throw new Exception("Invalid status");
        }

        $user = $this->repo->getUserById($userId);
        if (!$user) {
            throw new Exception("User not found");
        }

        if ($user['role_slug'] === 'superadmin') {
            throw new Exception("Cannot modify superadmin status");
        }

        $this->repo->updateUserStatus($userId, $status);
        AdminActivityLogger::log($adminId, 'UPDATE_STATUS', 'users', $userId, ['new_status' => $status]);
    }
    public function getDeletedUsers(int $limit = 50, int $offset = 0): array {
        return $this->repo->getDeletedUsers($limit, $offset);
    }

    public function softDeleteUser(int $adminId, int $userId): void {
        $user = $this->repo->getUserById($userId);
        if (!$user) {
            throw new Exception("User not found");
        }
        if ($user['role_slug'] === 'superadmin') {
            throw new Exception("Cannot delete a superadmin");
        }
        $this->repo->softDeleteUser($userId);
        AdminActivityLogger::log($adminId, 'USER_DELETED', 'users', $userId, []);
    }

    public function restoreUser(int $adminId, int $userId): void {
        $user = $this->repo->getUserById($userId);
        if (!$user) {
            throw new Exception("User not found");
        }
        $this->repo->restoreUser($userId);
        AdminActivityLogger::log($adminId, 'USER_RESTORED', 'users', $userId, []);
    }
}
