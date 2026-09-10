<?php

namespace HBM\Services;

use HBM\Repositories\AuthRepository;
use HBM\Helpers\Logger;
use Exception;

class AuthService {
    private AuthRepository $authRepo;
    private EmailService $emailService;

    public function __construct() {
        $this->authRepo = new AuthRepository();
        $this->emailService = new EmailService();
    }

    public function register(array $data): array {
        // Validation
        if (empty($data['first_name']) || empty($data['last_name']) || empty($data['email']) || empty($data['password'])) {
            throw new Exception("All required fields must be provided.");
        }
        
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format.");
        }

        if (!empty($data['phone']) && !preg_match('/^[0-9]{10,15}$/', $data['phone'])) {
            throw new Exception("Invalid phone format.");
        }

        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $data['password'])) {
            throw new Exception("Password must be at least 8 characters, and include one uppercase letter, one lowercase letter, one number, and one special character.");
        }

        $data['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
        $data['role_id'] = 4; // User role

        // Check Duplicates
        $existingUser = $this->authRepo->getUserByEmailOrPhone($data['email']);
        if ($existingUser) {
            // If the existing user is inactive, we can override their registration
            if ($existingUser['status'] === 'inactive') {
                $this->authRepo->updateUnverifiedUser($existingUser['id'], $data);
                $userId = $existingUser['id'];
            } else {
                throw new Exception("Email already registered and active.");
            }
        } else {
            $userId = $this->authRepo->createUser($data);
        }

        $this->generateOtp($data['email'], 'registration');
        Logger::info("New user registered", ['user_id' => $userId, 'email' => $data['email']]);

        return [
            'id' => $userId,
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email']
        ];
    }

    public function login(string $identifier, string $password, string $ip, string $userAgent): string {
        $user = $this->authRepo->getUserByEmailOrPhone($identifier);
        
        if (!$user) {
            Logger::warning("Failed login attempt - user not found", ['identifier' => $identifier]);
            throw new Exception("Invalid credentials."); // Generic message
        }

        if ($user['status'] !== 'active') {
            Logger::warning("Failed login attempt - inactive user", ['user_id' => $user['id']]);
            throw new Exception("Account is inactive or suspended.");
        }

        if (!password_verify($password, $user['password_hash'])) {
            Logger::warning("Failed login attempt - wrong password", ['user_id' => $user['id']]);
            throw new Exception("Invalid credentials.");
        }

        // Generate secure token
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));

        $this->authRepo->createSession($user['id'], $token, $ip, $userAgent, $expiresAt);
        
        Logger::info("User logged in", ['user_id' => $user['id']]);

        return $token;
    }

    public function logout(string $token): void {
        $this->authRepo->deleteSession($token);
    }

    public function generateOtp(string $identifier, string $type): void {
        $code = (string)random_int(100000, 999999);
        $expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));

        $this->authRepo->createOtp($identifier, $code, $type, $expiresAt);

        // Log safely (mock sending SMS/Email for local dev)
        Logger::info("OTP Generated", ['identifier' => $identifier, 'type' => $type, 'otp_mock' => $code]);

        // Send OTP via Email using ZeptoMail
        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            $subject = "Your Healthy Bharat Mission OTP";
            $message = "<div>Hello, <br><br>Your OTP for $type is: <b>$code</b>.<br>This OTP is valid for 10 minutes.<br><br>Thank you,<br>Healthy Bharat Mission Team</div>";
            try {
                $this->emailService->sendEmail($identifier, $subject, $message);
            } catch (Exception $e) {
                Logger::error("Failed to send OTP email to $identifier", ['error' => $e->getMessage()]);
            }
        }
    }

    public function verifyOtp(string $identifier, string $code, string $type): bool|string {
        // Prevent brute force check
        // Real implementation would track attempts per identifier outside the specific OTP record as well
        
        $otp = $this->authRepo->getValidOtp($identifier, $code, $type);
        
        if (!$otp) {
            // Find recent for incrementing attempt to prevent brute force on same code logic
            // Not strictly needed for simple demo, just return false
            Logger::warning("OTP Verification failed - invalid or expired", ['identifier' => $identifier]);
            throw new Exception("Invalid or expired OTP.");
        }

        if ($otp['attempt_count'] >= 3) {
            Logger::warning("OTP Verification failed - too many attempts", ['identifier' => $identifier]);
            throw new Exception("Too many attempts. Request a new OTP.");
        }

        $this->authRepo->markOtpAsUsed($otp['id']);
        Logger::info("OTP Verified successfully", ['identifier' => $identifier]);
        
        // If purpose is registration, log the user in automatically
        if ($type === 'registration') {
            $user = $this->authRepo->getUserByEmailOrPhone($identifier);
            if ($user) {
                // Activate the account
                if ($user['status'] === 'inactive') {
                    $this->authRepo->updateUserStatus($user['id'], 'active');
                }
                
                $token = bin2hex(random_bytes(32));
                $expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));
                $this->authRepo->createSession($user['id'], $token, $_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_USER_AGENT'] ?? '', $expiresAt);
                return $token;
            }
        }
        return true;
    }

    public function forgotPassword(string $email): void {
        $user = $this->authRepo->getUserByEmailOrPhone($email);
        
        // Always return success to prevent email enumeration
        if ($user) {
            $token = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            $this->authRepo->createPasswordReset($user['email'], $token, $expiresAt);
            
            // Log the generated token
            Logger::info("Password reset token generated", ['email' => $user['email'], 'token_mock' => $token]);

            // Send Reset Link via Email using ZeptoMail
            if (filter_var($user['email'], FILTER_VALIDATE_EMAIL)) {
                $baseUrl = $_ENV['FRONTEND_URL'] ?? $_ENV['APP_URL'];
                $resetLink = $baseUrl . "/auth/reset-password.html?token=" . $token;
                $subject = "Reset your Healthy Bharat Mission password";
                $message = "<div>Hello,<br><br>You requested to reset your password. Click the link below to set a new password:<br><br><a href='$resetLink'>$resetLink</a><br><br>This link is valid for 1 hour.<br><br>Thank you,<br>Healthy Bharat Mission Team</div>";
                try {
                    $this->emailService->sendEmail($user['email'], $subject, $message);
                } catch (Exception $e) {
                    Logger::error("Failed to send password reset email to " . $user['email'], ['error' => $e->getMessage()]);
                }
            }
        }
    }

    public function resetPassword(string $token, string $newPassword, string $confirmPassword): void {
        if ($newPassword !== $confirmPassword) {
            throw new Exception("Passwords do not match.");
        }

        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $newPassword)) {
            throw new Exception("Password must be at least 8 characters, and include one uppercase letter, one lowercase letter, one number, and one special character.");
        }

        $reset = $this->authRepo->getValidPasswordReset($token);
        
        if (!$reset) {
            throw new Exception("Invalid or expired reset token.");
        }

        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $this->authRepo->updatePasswordByEmail($reset['email'], $hash);
        $this->authRepo->markPasswordResetAsUsed($reset['id']);

        // Invalidate all existing sessions for this user
        $user = $this->authRepo->getUserByEmailOrPhone($reset['email']);
        if ($user) {
            $this->authRepo->deleteAllUserSessions($user['id']);
        }
        
        Logger::info("Password reset successful", ['email' => $reset['email']]);
    }
}
