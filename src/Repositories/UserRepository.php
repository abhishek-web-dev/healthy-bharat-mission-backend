<?php

namespace HBM\Repositories;

use HBM\Core\Database;
use PDO;

class UserRepository {
    private function getDb(): PDO {
        return Database::getConnection();
    }

    public function getUserProfile(int $userId): array {
        // Ensure user_profiles row exists
        $stmtCheck = $this->getDb()->prepare("SELECT id FROM user_profiles WHERE user_id = :user_id");
        $stmtCheck->execute(['user_id' => $userId]);
        if (!$stmtCheck->fetch()) {
            $stmtInsert = $this->getDb()->prepare("INSERT INTO user_profiles (user_id) VALUES (:user_id)");
            $stmtInsert->execute(['user_id' => $userId]);
        }

        $stmt = $this->getDb()->prepare("
            SELECT u.id, u.first_name, u.last_name, u.email, u.phone, u.created_at as member_since,
                   up.gender, up.dob, up.blood_group, up.height_cm, up.weight_kg, up.health_goals
            FROM users u
            LEFT JOIN user_profiles up ON u.id = up.user_id
            WHERE u.id = :user_id
        ");
        $stmt->execute(['user_id' => $userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Fetch Aggregations
        // Total Orders
        $stmtOrders = $this->getDb()->prepare("SELECT COUNT(*) as total FROM orders WHERE user_id = :user_id");
        $stmtOrders->execute(['user_id' => $userId]);
        $user['total_orders'] = (int)$stmtOrders->fetchColumn();

        // Active Programs
        $stmtPrograms = $this->getDb()->prepare("SELECT COUNT(*) as total FROM user_enrollments WHERE user_id = :user_id AND status = 'active'");
        $stmtPrograms->execute(['user_id' => $userId]);
        $user['active_programs'] = (int)$stmtPrograms->fetchColumn();

        // Appointments
        $stmtAppointments = $this->getDb()->prepare("SELECT COUNT(*) as total FROM appointments WHERE user_id = :user_id AND status != 'cancelled'");
        $stmtAppointments->execute(['user_id' => $userId]);
        $user['appointments'] = (int)$stmtAppointments->fetchColumn();

        // Wishlist Count
        $stmtWishlist = $this->getDb()->prepare("SELECT COUNT(*) as total FROM wishlist_items WHERE user_id = :user_id");
        $stmtWishlist->execute(['user_id' => $userId]);
        $user['saved_items'] = (int)$stmtWishlist->fetchColumn();

        // Latest Active Program
        $stmtLatestProgram = $this->getDb()->prepare("
            SELECT p.title as name, ue.enrolled_at,
                   (SELECT COUNT(*) FROM program_modules pm WHERE pm.program_id = p.id) as total_modules,
                   (SELECT COUNT(*) FROM user_progress upr WHERE upr.enrollment_id = ue.id AND upr.is_completed = 1) as completed_modules
            FROM user_enrollments ue
            JOIN programs p ON ue.program_id = p.id
            WHERE ue.user_id = :user_id AND ue.status = 'active'
            ORDER BY ue.enrolled_at DESC LIMIT 1
        ");
        $stmtLatestProgram->execute(['user_id' => $userId]);
        $latestProgram = $stmtLatestProgram->fetch(PDO::FETCH_ASSOC);
        if ($latestProgram) {
            $total = (int)$latestProgram['total_modules'];
            $completed = (int)$latestProgram['completed_modules'];
            $progress = $total > 0 ? round(($completed / $total) * 100) : 0;
            $user['active_program'] = [
                'name' => $latestProgram['name'],
                'enrolled_at' => $latestProgram['enrolled_at'],
                'progress' => $progress
            ];
        } else {
            $user['active_program'] = null;
        }

        // Upcoming Appointment
        $stmtUpcomingAppt = $this->getDb()->prepare("
            SELECT a.appointment_date as date, a.appointment_time as time, a.status, u.first_name, u.last_name
            FROM appointments a
            JOIN users u ON a.expert_id = u.id
            WHERE a.user_id = :user_id AND a.appointment_date >= CURDATE() AND a.status != 'cancelled'
            ORDER BY a.appointment_date ASC, a.appointment_time ASC LIMIT 1
        ");
        $stmtUpcomingAppt->execute(['user_id' => $userId]);
        $upcomingAppt = $stmtUpcomingAppt->fetch(PDO::FETCH_ASSOC);
        if ($upcomingAppt) {
            $user['upcoming_appointment'] = [
                'date' => $upcomingAppt['date'],
                'time' => $upcomingAppt['time'],
                'status' => $upcomingAppt['status'],
                'doctor_name' => $upcomingAppt['first_name'] . ' ' . $upcomingAppt['last_name']
            ];
        } else {
            $user['upcoming_appointment'] = null;
        }

        // Health Conditions
        $stmtConditions = $this->getDb()->prepare("SELECT id, condition_name, created_at FROM user_health_conditions WHERE user_id = :user_id ORDER BY created_at DESC");
        $stmtConditions->execute(['user_id' => $userId]);
        $user['health_conditions'] = $stmtConditions->fetchAll(PDO::FETCH_ASSOC);

        // Allergies
        $stmtAllergies = $this->getDb()->prepare("SELECT id, allergy_name, created_at FROM user_allergies WHERE user_id = :user_id ORDER BY created_at DESC");
        $stmtAllergies->execute(['user_id' => $userId]);
        $user['allergies'] = $stmtAllergies->fetchAll(PDO::FETCH_ASSOC);

        // Emergency Contacts
        $stmtEmergency = $this->getDb()->prepare("SELECT id, contact_name, relationship, phone_number, created_at FROM user_emergency_contacts WHERE user_id = :user_id ORDER BY created_at DESC");
        $stmtEmergency->execute(['user_id' => $userId]);
        $user['emergency_contacts'] = $stmtEmergency->fetchAll(PDO::FETCH_ASSOC);

        return $user;
    }

    public function updateProfile(int $userId, array $data): void {
        // Update user core info
        $stmtUser = $this->getDb()->prepare("
            UPDATE users SET first_name = :first_name, last_name = :last_name, phone = :phone
            WHERE id = :user_id
        ");
        $stmtUser->execute([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'phone' => $data['phone'] ?? null,
            'user_id' => $userId
        ]);

        // Update extended profile
        $stmtProfile = $this->getDb()->prepare("
            UPDATE user_profiles 
            SET gender = :gender, dob = :dob, blood_group = :blood_group, height_cm = :height_cm, weight_kg = :weight_kg
            WHERE user_id = :user_id
        ");
        $stmtProfile->execute([
            'gender' => $data['gender'] ?? null,
            'dob' => $data['dob'] ?? null,
            'blood_group' => $data['blood_group'] ?? null,
            'height_cm' => isset($data['height_cm']) ? (float)$data['height_cm'] : null,
            'weight_kg' => isset($data['weight_kg']) ? (float)$data['weight_kg'] : null,
            'user_id' => $userId
        ]);
    }

    public function addHealthCondition(int $userId, string $conditionName): void {
        $stmt = $this->getDb()->prepare("INSERT INTO user_health_conditions (user_id, condition_name) VALUES (:user_id, :condition_name)");
        $stmt->execute(['user_id' => $userId, 'condition_name' => $conditionName]);
    }

    public function addAllergy(int $userId, string $allergyName): void {
        $stmt = $this->getDb()->prepare("INSERT INTO user_allergies (user_id, allergy_name) VALUES (:user_id, :allergy_name)");
        $stmt->execute(['user_id' => $userId, 'allergy_name' => $allergyName]);
    }

    public function addEmergencyContact(int $userId, array $data): void {
        $stmt = $this->getDb()->prepare("INSERT INTO user_emergency_contacts (user_id, contact_name, relationship, phone_number) VALUES (:user_id, :contact_name, :relationship, :phone_number)");
        $stmt->execute([
            'user_id' => $userId,
            'contact_name' => $data['contact_name'],
            'relationship' => $data['relationship'] ?? null,
            'phone_number' => $data['phone_number']
        ]);
    }

    public function getUserPrograms(int $userId): array {
        $stmt = $this->getDb()->prepare("
            SELECT p.id, p.title as name, p.image_url, p.slug, p.duration_days, p.features, 
                   ue.status, ue.enrolled_at,
                   (SELECT COUNT(*) FROM program_modules pm WHERE pm.program_id = p.id) as total_modules,
                   (SELECT COUNT(*) FROM user_progress upr WHERE upr.enrollment_id = ue.id AND upr.is_completed = 1) as completed_modules
            FROM user_enrollments ue
            JOIN programs p ON ue.program_id = p.id
            WHERE ue.user_id = :user_id
            ORDER BY ue.enrolled_at DESC
        ");
        $stmt->execute(['user_id' => $userId]);
        $programs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $result = [
            'active' => [],
            'completed' => []
        ];

        foreach ($programs as $prog) {
            $total = (int)$prog['total_modules'];
            $completed = (int)$prog['completed_modules'];
            $progress = $total > 0 ? round(($completed / $total) * 100) : 0;
            
            $item = [
                'id' => $prog['id'],
                'name' => $prog['name'],
                'slug' => $prog['slug'],
                'image_url' => $prog['image_url'],
                'status' => $prog['status'],
                'enrolled_at' => $prog['enrolled_at'],
                'duration_days' => $prog['duration_days'],
                'features' => json_decode($prog['features'] ?? '[]', true),
                'total_modules' => $total,
                'completed_modules' => $completed,
                'progress' => $progress
            ];

            if ($prog['status'] === 'completed' || $progress === 100) {
                $item['status'] = 'completed'; // force completed if 100%
                $result['completed'][] = $item;
            } else {
                $result['active'][] = $item;
            }
        }

        return $result;
    }

    public function getUserDocuments(int $userId): array {
        $stmt = $this->getDb()->prepare("
            SELECT id, title, document_type, file_path, file_size, mime_type, created_at
            FROM user_documents
            WHERE user_id = :user_id
            ORDER BY created_at DESC
        ");
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function saveDocument(int $userId, array $data): array {
        $stmt = $this->getDb()->prepare("
            INSERT INTO user_documents (user_id, title, document_type, file_path, file_size, mime_type)
            VALUES (:user_id, :title, :document_type, :file_path, :file_size, :mime_type)
        ");
        $stmt->execute([
            'user_id' => $userId,
            'title' => $data['title'],
            'document_type' => $data['document_type'],
            'file_path' => $data['file_path'],
            'file_size' => $data['file_size'] ?? 0,
            'mime_type' => $data['mime_type'] ?? 'application/octet-stream'
        ]);
        
        $data['id'] = $this->getDb()->lastInsertId();
        $data['created_at'] = date('Y-m-d H:i:s');
        return $data;
    }

    public function getDocumentById(int $userId, int $docId): ?array {
        $stmt = $this->getDb()->prepare("
            SELECT * FROM user_documents
            WHERE id = :id AND user_id = :user_id
        ");
        $stmt->execute(['id' => $docId, 'user_id' => $userId]);
        $doc = $stmt->fetch(PDO::FETCH_ASSOC);
        return $doc ?: null;
    }

    public function deleteDocument(int $userId, int $docId): bool {
        $stmt = $this->getDb()->prepare("
            DELETE FROM user_documents
            WHERE id = :id AND user_id = :user_id
        ");
        $stmt->execute(['id' => $docId, 'user_id' => $userId]);
        return $stmt->rowCount() > 0;
    }

    public function getSettings(int $userId): array {
        $stmt = $this->getDb()->prepare("SELECT two_factor_enabled FROM users WHERE id = :user_id");
        $stmt->execute(['user_id' => $userId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: ['two_factor_enabled' => false];
    }

    public function updateTwoFactor(int $userId, bool $enabled): void {
        $stmt = $this->getDb()->prepare("UPDATE users SET two_factor_enabled = :enabled WHERE id = :user_id");
        $stmt->execute(['enabled' => $enabled ? 1 : 0, 'user_id' => $userId]);
    }
}
