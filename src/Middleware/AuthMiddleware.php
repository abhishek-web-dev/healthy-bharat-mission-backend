<?php

namespace HBM\Middleware;

use HBM\Helpers\Response;
use HBM\Helpers\Request;
use HBM\Repositories\AuthRepository;

class AuthMiddleware {
    public static function handle(): void {
        $token = Request::getBearerToken() ?? $_COOKIE['auth_token'] ?? null;
        
        if (!$token) {
            Response::error('Unauthorized - Token missing', 401);
        }

        $repo = new AuthRepository();
        $session = $repo->getSession($token);
        
        if (!$session) {
            Response::error('Unauthorized - Invalid or expired session', 401);
        }

        $user = $repo->getUserById($session['user_id']);
        
        if (!$user || $user['status'] !== 'active') {
            Response::error('Unauthorized - User inactive', 401);
        }

        // Store user in global scope for this request cycle
        global $authUser;
        $authUser = $user;
    }

    public static function handleRole(array $allowedRoles): void {
        self::handle(); // Ensure authenticated first
        
        global $authUser;
        
        if (!in_array($authUser['role_slug'], $allowedRoles) && $authUser['role_slug'] !== 'superadmin') {
            Response::error('Forbidden - Insufficient permissions', 403);
        }
    }

    public static function handleAdmin(): void {
        self::handleRole(['admin']);
    }

    public static function handleSuperAdmin(): void {
        self::handleRole([]); // Empty array means ONLY superadmin can access since handleRole explicitly checks for 'superadmin' separately.
    }
}
