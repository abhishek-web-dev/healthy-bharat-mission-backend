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
        $validRoles = ['user', 'expert', 'admin', 'superadmin'];
        if (!in_array($roleSlug, $validRoles)) {
            throw new Exception("Invalid role");
        }

        $user = $this->repo->getUserById($userId);
        if (!$user) {
            throw new Exception("User not found");
        }

        if ($user['role_slug'] === 'superadmin' && $roleSlug !== 'superadmin') {
            throw new Exception("Cannot demote a superadmin");
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
}
