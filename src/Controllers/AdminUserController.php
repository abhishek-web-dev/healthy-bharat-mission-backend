<?php

namespace HBM\Controllers;

use HBM\Services\AdminUserService;
use HBM\Helpers\Response;
use Exception;

class AdminUserController {
    private AdminUserService $service;

    public function __construct() {
        $this->service = new AdminUserService();
    }

    public function listUsers(): void {
        try {
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
            $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
            $users = $this->service->getAllUsers($limit, $offset);
            Response::success('Users fetched successfully', ['users' => $users]);
        } catch (Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    public function getUser(int $id): void {
        try {
            $user = $this->service->getUserById($id);
            Response::success('User fetched successfully', $user);
        } catch (Exception $e) {
            Response::error($e->getMessage(), 404);
        }
    }

    public function updateRole(int $id): void {
        global $authUser;
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        
        try {
            if (empty($input['role'])) {
                throw new Exception("Role is required");
            }
            $this->service->updateUserRole($authUser['id'], $id, $input['role']);
            \HBM\Services\AdminActivityLogService::log($authUser['id'], 'USER_ROLE_UPDATED', 'users', $id, ['role' => $input['role']]);
            Response::success('User role updated successfully');
        } catch (Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    public function updateStatus(int $id): void {
        global $authUser;
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        
        try {
            if (empty($input['status'])) {
                throw new Exception("Status is required");
            }
            $this->service->updateUserStatus($authUser['id'], $id, $input['status']);
            \HBM\Services\AdminActivityLogService::log($authUser['id'], 'USER_STATUS_UPDATED', 'users', $id, ['status' => $input['status']]);
            Response::success('User status updated successfully');
        } catch (Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }
}
