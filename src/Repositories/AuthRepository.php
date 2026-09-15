<?php

namespace HBM\Repositories;

use HBM\Core\Database;
use PDO;

class AuthRepository {
    public function getUserByEmailOrPhone(string $identifier): ?array {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ? OR phone = ? LIMIT 1");
        $stmt->execute([$identifier, $identifier]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function getUserById(int $id): ?array {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT u.id, u.first_name, u.last_name, u.email, u.phone, u.status, u.role_id, r.slug as role_slug 
            FROM users u
            JOIN roles r ON u.role_id = r.id
            WHERE u.id = ? LIMIT 1
        ");
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function createUser(array $data): int {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            INSERT INTO users (first_name, last_name, email, phone, password_hash, role_id, status)
            VALUES (?, ?, ?, ?, ?, ?, 'inactive')
        ");
        $stmt->execute([
            $data['first_name'],
            $data['last_name'],
            $data['email'],
            $data['phone'] ?? null,
            $data['password_hash'],
            $data['role_id'] // 4 for 'user'
        ]);
        return (int)$db->lastInsertId();
    }

    public function updateUnverifiedUser(int $id, array $data): void {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            UPDATE users 
            SET first_name = ?, last_name = ?, phone = ?, password_hash = ?
            WHERE id = ? AND status = 'inactive'
        ");
        $stmt->execute([
            $data['first_name'],
            $data['last_name'],
            $data['phone'] ?? null,
            $data['password_hash'],
            $id
        ]);
    }

    public function updateUserStatus(int $id, string $status): void {
        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE users SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
    }

    public function createSession(int $userId, string $token, string $ip, string $userAgent, string $expiresAt): void {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            INSERT INTO sessions (user_id, token, ip_address, user_agent, expires_at)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $token, $ip, $userAgent, $expiresAt]);
    }

    public function getSession(string $token): ?array {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM sessions WHERE token = ? AND expires_at > NOW() LIMIT 1");
        $stmt->execute([$token]);
        $session = $stmt->fetch();
        return $session ?: null;
    }

    public function deleteSession(string $token): void {
        $db = Database::getConnection();
        $stmt = $db->prepare("DELETE FROM sessions WHERE token = ?");
        $stmt->execute([$token]);
    }

    public function deleteAllUserSessions(int $userId): void {
        $db = Database::getConnection();
        $stmt = $db->prepare("DELETE FROM sessions WHERE user_id = ?");
        $stmt->execute([$userId]);
    }

    // OTP
    public function createOtp(string $identifier, string $code, string $type, string $expiresAt): void {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            INSERT INTO otps (identifier, otp_code, type, expires_at)
            VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE))
        ");
        $stmt->execute([$identifier, $code, $type]);
    }

    public function getValidOtp(string $identifier, string $code, string $type): ?array {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT * FROM otps 
            WHERE identifier = ? AND otp_code = ? AND type = ? AND is_used = 0 AND expires_at > NOW()
            ORDER BY created_at DESC LIMIT 1
        ");
        $stmt->execute([$identifier, $code, $type]);
        $otp = $stmt->fetch();
        return $otp ?: null;
    }

    public function markOtpAsUsed(int $id): void {
        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE otps SET is_used = 1 WHERE id = ?");
        $stmt->execute([$id]);
    }

    public function incrementOtpAttempt(int $id): void {
        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE otps SET attempt_count = attempt_count + 1 WHERE id = ?");
        $stmt->execute([$id]);
    }

    // Password Reset
    public function createPasswordReset(string $email, string $token, string $expiresAt): void {
        $db = Database::getConnection();
        $stmt = $db->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))");
        $stmt->execute([$email, $token]);
    }

    public function getValidPasswordReset(string $token): ?array {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT * FROM password_resets 
            WHERE token = ? AND is_used = 0 AND expires_at > NOW() 
            LIMIT 1
        ");
        $stmt->execute([$token]);
        $reset = $stmt->fetch();
        return $reset ?: null;
    }

    public function markPasswordResetAsUsed(int $id): void {
        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE password_resets SET is_used = 1 WHERE id = ?");
        $stmt->execute([$id]);
    }

    public function updatePasswordByEmail(string $email, string $hash): void {
        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE email = ?");
        $stmt->execute([$hash, $email]);
    }

    public function updatePassword(int $userId, string $passwordHash): bool {
        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE users SET password_hash = :hash, updated_at = CURRENT_TIMESTAMP WHERE id = :id");
        return $stmt->execute([
            'hash' => $passwordHash,
            'id' => $userId
        ]);
    }
}
