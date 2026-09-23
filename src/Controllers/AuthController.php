<?php

namespace HBM\Controllers;

use HBM\Services\AuthService;
use HBM\Helpers\Request;
use HBM\Helpers\Response;
use HBM\Middleware\AuthMiddleware;
use Exception;

class AuthController {
    private AuthService $authService;

    public function __construct() {
        $this->authService = new AuthService();
    }

    public function register(): void {
        try {
            $data = Request::getJson();
            $user = $this->authService->register($data);
            Response::success("Registration successful.", $user, 201);
        } catch (Exception $e) {
            Response::error($e->getMessage(), 422);
        }
    }

    public function login(): void {
        try {
            $data = Request::getJson();
            $identifier = $data['email'] ?? $data['phone'] ?? '';
            $password = $data['password'] ?? '';
            
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';

            $result = $this->authService->login($identifier, $password, $ip, $ua);
            
            if (is_array($result) && isset($result['requires_2fa'])) {
                Response::success("2FA Required.", $result);
                return;
            }
            
            $token = $result;

            // Set HttpOnly Cookie for security
            setcookie('auth_token', $token, [
                'expires' => time() + (30 * 24 * 60 * 60),
                'path' => '/',
                'secure' => isset($_SERVER['HTTPS']),
                'httponly' => true,
                'samesite' => 'Lax'
            ]);

            Response::success("Login successful.", ['token' => $token]);
        } catch (Exception $e) {
            Response::error($e->getMessage(), 401);
        }
    }

    public function me(): void {
        // Authenticated user is injected by AuthMiddleware
        global $authUser;
        
        if (!$authUser) {
            Response::error("Unauthorized", 401);
        }

        // Return safe profile info (no password, status, or role internal IDs)
        Response::success("User profile", [
            'id' => $authUser['id'],
            'first_name' => $authUser['first_name'],
            'last_name' => $authUser['last_name'],
            'email' => $authUser['email'],
            'phone' => $authUser['phone'],
            'role' => $authUser['role_slug']
        ]);
    }

    public function logout(): void {
        try {
            $token = Request::getBearerToken() ?? $_COOKIE['auth_token'] ?? null;
            if ($token) {
                $this->authService->logout($token);
            }
            
            // Clear cookie
            setcookie('auth_token', '', [
                'expires' => time() - 3600,
                'path' => '/',
                'secure' => isset($_SERVER['HTTPS']),
                'httponly' => true,
                'samesite' => 'Lax'
            ]);

            Response::success("Logged out successfully.");
        } catch (Exception $e) {
            Response::error("Logout failed", 500);
        }
    }

    public function verifyOtp(): void {
        try {
            $data = Request::getJson();
            $token = $this->authService->verifyOtp(
                $data['identifier'] ?? '',
                $data['otp'] ?? '',
                $data['purpose'] ?? ''
            );
            
            if ($token && is_string($token)) {
                // Set HttpOnly Cookie for security
                setcookie('auth_token', $token, [
                    'expires' => time() + (30 * 24 * 60 * 60),
                    'path' => '/',
                    'secure' => isset($_SERVER['HTTPS']),
                    'httponly' => true,
                    'samesite' => 'Lax'
                ]);
                Response::success("OTP verified successfully.", ['token' => $token]);
            } else {
                Response::success("OTP verified successfully.");
            }
        } catch (Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    public function resendOtp(): void {
        try {
            $data = Request::getJson();
            $identifier = $data['identifier'] ?? '';
            $type = $data['purpose'] ?? 'registration';
            
            if (empty($identifier)) {
                throw new Exception("Identifier is required.");
            }

            $this->authService->generateOtp($identifier, $type);
            Response::success("OTP resent successfully.");
        } catch (Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    public function forgotPassword(): void {
        try {
            $data = Request::getJson();
            $this->authService->forgotPassword($data['email'] ?? $data['phone'] ?? '');
            
            // Always return success to prevent enumeration
            Response::success("If that account exists, a reset link/OTP has been sent.");
        } catch (Exception $e) {
            Response::error("An error occurred.", 500);
        }
    }

    public function resetPassword(): void {
        try {
            $data = Request::getJson();
            $this->authService->resetPassword(
                $data['token'] ?? '',
                $data['new_password'] ?? '',
                $data['confirm_password'] ?? ''
            );
            Response::success("Password has been reset successfully.");
        } catch (Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    public function changePassword(): void {
        try {
            global $authUser;
            if (!$authUser) {
                Response::error("Unauthorized", 401);
                return;
            }

            $data = Request::getJson();
            $this->authService->changePassword(
                $authUser['id'],
                $data['current_password'] ?? '',
                $data['new_password'] ?? '',
                $data['confirm_password'] ?? ''
            );
            Response::success("Password has been changed successfully.");
        } catch (Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    public function deleteAccount(): void {
        try {
            global $authUser;
            if (!$authUser) {
                Response::error("Unauthorized", 401);
                return;
            }

            $data = Request::getJson();
            $this->authService->deleteAccount(
                $authUser['id'],
                $data['password'] ?? ''
            );
            Response::success("Account has been deleted successfully.");
        } catch (Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    // Admin Auth
    public function adminLogin(): void {
        try {
            $data = Request::getJson();
            $email = $data['email'] ?? '';
            $password = $data['password'] ?? '';
            
            $challenge = $this->authService->adminLoginStart($email, $password);
            
            Response::success("OTP sent to your email.", $challenge);
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Unable to send OTP') !== false) {
                Response::error($e->getMessage(), 500);
            } else {
                Response::error($e->getMessage(), 401);
            }
        }
    }

    public function verifyAdminOtp(): void {
        try {
            $data = Request::getJson();
            $challengeId = $data['challenge_id'] ?? '';
            $otp = $data['otp'] ?? '';
            
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';

            $token = $this->authService->adminLoginVerify($challengeId, $otp, $ip, $ua);
            
            setcookie('auth_token', $token, [
                'expires' => time() + (30 * 24 * 60 * 60),
                'path' => '/',
                'secure' => isset($_SERVER['HTTPS']),
                'httponly' => true,
                'samesite' => 'Lax'
            ]);

            Response::success("Login successful.", ['token' => $token]);
        } catch (Exception $e) {
            Response::error($e->getMessage(), 401);
        }
    }

    public function resendAdminOtp(): void {
        try {
            $data = Request::getJson();
            $challengeId = $data['challenge_id'] ?? '';
            
            $challenge = $this->authService->adminLoginResend($challengeId);
            
            Response::success("OTP resent to your email.", $challenge);
        } catch (Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }
}
