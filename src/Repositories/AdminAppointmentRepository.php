<?php
namespace HBM\Repositories;

class AdminAppointmentRepository {
    private \PDO $db;

    public function __construct() {
        $this->db = \HBM\Core\Database::getConnection();
    }

    private function paginate(string $query, array $params, int $page, int $perPage): array {
        $countQuery = "SELECT COUNT(*) FROM (" . $query . ") as count_table";
        $stmtCount = $this->db->prepare($countQuery);
        $stmtCount->execute($params);
        $total = $stmtCount->fetchColumn();

        $offset = ($page - 1) * $perPage;
        $query .= " LIMIT $perPage OFFSET $offset";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return [
            'data' => $data,
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => ceil($total / $perPage)
            ]
        ];
    }

    public function getAdminExperts(int $page = 1, int $perPage = 50): array {
        $query = "
            SELECT u.id as user_id, u.first_name, u.last_name, u.email, u.phone, u.status as user_status,
                   ep.id as profile_id, ep.specialization, ep.experience_years, ep.bio, ep.profile_image_url
            FROM users u
            JOIN roles r ON u.role_id = r.id
            LEFT JOIN expert_profiles ep ON u.id = ep.user_id
            WHERE r.slug = 'expert'
            ORDER BY u.created_at DESC
        ";
        return $this->paginate($query, [], $page, $perPage);
    }

    public function updateExpertProfile(int $userId, array $data): void {
        $stmt = $this->db->prepare("SELECT id FROM expert_profiles WHERE user_id = ?");
        $stmt->execute([$userId]);
        $exists = $stmt->fetchColumn();

        if ($exists) {
            $stmt = $this->db->prepare("
                UPDATE expert_profiles 
                SET specialization = :specialization, experience_years = :experience, bio = :bio, profile_image_url = :image 
                WHERE user_id = :user_id
            ");
        } else {
            $stmt = $this->db->prepare("
                INSERT INTO expert_profiles (user_id, specialization, experience_years, bio, profile_image_url)
                VALUES (:user_id, :specialization, :experience, :bio, :image)
            ");
        }

        $stmt->execute([
            'specialization' => $data['specialization'] ?? '',
            'experience' => $data['experience_years'] ?? 0,
            'bio' => $data['bio'] ?? null,
            'image' => $data['profile_image_url'] ?? null,
            'user_id' => $userId
        ]);
        
        if (isset($data['status'])) {
            $stmtU = $this->db->prepare("UPDATE users SET status = ? WHERE id = ?");
            $stmtU->execute([$data['status'], $userId]);
        }
    }

    public function getAdminAppointments(int $page = 1, int $perPage = 50): array {
        $query = "
            SELECT a.id, a.user_id, a.expert_id, a.appointment_date, a.appointment_time, a.status, a.mode, a.zoom_link, a.notes, a.created_at,
                   u.first_name as user_first_name, u.last_name as user_last_name, u.email as user_email,
                   e.first_name as expert_first_name, e.last_name as expert_last_name
            FROM appointments a
            JOIN users u ON a.user_id = u.id
            JOIN users e ON a.expert_id = e.id
            ORDER BY a.appointment_date DESC, a.appointment_time DESC
        ";
        return $this->paginate($query, [], $page, $perPage);
    }

    public function updateAppointmentStatus(int $id, array $data): void {
        $fields = [];
        $params = ['id' => $id];
        
        if (isset($data['status'])) {
            $fields[] = "status = :status";
            $params['status'] = $data['status'];
        }
        if (isset($data['zoom_link'])) {
            $fields[] = "zoom_link = :zoom_link";
            $params['zoom_link'] = $data['zoom_link'];
        }
        if (isset($data['notes'])) {
            $fields[] = "notes = :notes";
            $params['notes'] = $data['notes'];
        }
        if (isset($data['appointment_date'])) {
            $fields[] = "appointment_date = :appointment_date";
            $params['appointment_date'] = $data['appointment_date'];
        }
        if (isset($data['appointment_time'])) {
            $fields[] = "appointment_time = :appointment_time";
            $params['appointment_time'] = $data['appointment_time'];
        }

        if (empty($fields)) return;
        
        $query = "UPDATE appointments SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
    }
}
