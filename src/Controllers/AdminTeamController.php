<?php

namespace HBM\Controllers;

use HBM\Repositories\AdminTeamRepository;
use HBM\Helpers\Response;
use HBM\Services\AdminActivityLogService;
use Exception;

class AdminTeamController {
    private AdminTeamRepository $repo;

    public function __construct() {
        $this->repo = new AdminTeamRepository();
    }

    public function getRoles(): void {
        try {
            $roles = $this->repo->getRoles();
            Response::success('Roles fetched successfully', ['roles' => $roles]);
        } catch (Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    public function getPermissions(): void {
        try {
            $permissions = $this->repo->getPermissionsGroupedByModule();
            Response::success('Permissions fetched successfully', ['permissions' => $permissions]);
        } catch (Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    public function createRole(): void {
        global $authUser;
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        
        try {
            if (empty($input['name'])) {
                throw new Exception("Role name is required");
            }
            $requestedPerms = $input['permissions'] ?? [];
            if ($authUser['role_slug'] !== 'superadmin') {
                $userPerms = $authUser['permissions'] ?? [];
                $unauthorizedPerms = array_diff($requestedPerms, $userPerms);
                if (!empty($unauthorizedPerms)) {
                    throw new Exception("You cannot grant permissions that you do not possess.");
                }
            }
            
            $this->repo->createRole($input['name'], $requestedPerms);
            AdminActivityLogService::log($authUser['id'], 'ROLE_CREATED', 'roles', null, ['name' => $input['name']]);
            Response::success('Role created successfully');
        } catch (Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    public function updateRole(int $id): void {
        global $authUser;
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        
        try {
            if (empty($input['name'])) {
                throw new Exception("Role name is required");
            }
            
            // Prevent modifying superadmin role
            $roles = $this->repo->getRoles();
            foreach ($roles as $r) {
                if ($r['id'] == $id && $r['slug'] === 'superadmin') {
                    throw new Exception("Cannot modify superadmin role");
                }
            }

            $requestedPerms = $input['permissions'] ?? [];
            if ($authUser['role_slug'] !== 'superadmin') {
                $userPerms = $authUser['permissions'] ?? [];
                $unauthorizedPerms = array_diff($requestedPerms, $userPerms);
                if (!empty($unauthorizedPerms)) {
                    throw new Exception("You cannot grant permissions that you do not possess.");
                }
            }

            $this->repo->updateRole($id, $input['name'], $input['permissions'] ?? []);
            AdminActivityLogService::log($authUser['id'], 'ROLE_UPDATED', 'roles', $id, ['name' => $input['name']]);
            Response::success('Role updated successfully');
        } catch (Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    public function getTeamMembers(): void {
        try {
            $members = $this->repo->getTeamMembers();
            Response::success('Team members fetched successfully', ['members' => $members]);
        } catch (Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    public function createTeamMember(): void {
        global $authUser;
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        
        try {
            if (empty($input['first_name']) || empty($input['last_name']) || empty($input['email']) || empty($input['password']) || empty($input['role_id'])) {
                throw new Exception("All required fields must be provided.");
            }

            // Optional: Validate if email already exists
            $authRepo = new \HBM\Repositories\AuthRepository();
            $existing = $authRepo->getUserByEmailOrPhone($input['email']);
            if ($existing) {
                throw new Exception("User with this email already exists.");
            }

            // Prevent assigning superadmin or higher privilege role if not superadmin
            if ($authUser['role_slug'] !== 'superadmin') {
                $role = null;
                $roles = $this->repo->getRoles();
                foreach ($roles as $r) {
                    if ($r['id'] == $input['role_id']) {
                        $role = $r;
                        break;
                    }
                }
                if (!$role || $role['slug'] === 'superadmin') {
                    throw new Exception("You cannot assign the superadmin role.");
                }
                
                // Ensure the assigned role does not have permissions the creator lacks
                $userPerms = $authUser['permissions'] ?? [];
                $rolePerms = $role['permissions'] ?? [];
                $unauthorizedPerms = array_diff($rolePerms, $userPerms);
                if (!empty($unauthorizedPerms)) {
                    throw new Exception("You cannot assign a role that has permissions you do not possess.");
                }
            }

            $data = [
                'first_name' => $input['first_name'],
                'last_name' => $input['last_name'],
                'email' => $input['email'],
                'password_hash' => password_hash($input['password'], PASSWORD_DEFAULT),
                'role_id' => $input['role_id'],
                'status' => $input['status'] ?? 'active'
            ];

            $userId = $this->repo->createTeamMember($data);
            AdminActivityLogService::log($authUser['id'], 'TEAM_MEMBER_CREATED', 'users', $userId, ['email' => $input['email']]);
            Response::success('Team member created successfully');
        } catch (Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }
}
