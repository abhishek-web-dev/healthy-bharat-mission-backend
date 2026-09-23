<?php
namespace HBM\Repositories;

class UserAppointmentRepository {
    private \PDO $db;

    public function __construct() {
        $this->db = \HBM\Core\Database::getConnection();
    }

    public function getUserAppointments(int $userId): array {
        $query = "
            SELECT a.id, a.user_id, a.expert_id, a.appointment_date, a.appointment_time, 
                   a.status, a.mode, a.zoom_link, a.notes,
                   e.name as expert_name, e.profile_image as expert_image, e.role as expert_role
            FROM appointments a
            JOIN users e ON a.expert_id = e.id
            WHERE a.user_id = :user_id
            ORDER BY a.appointment_date DESC, a.appointment_time DESC
        ";
        $stmt = $this->db->prepare($query);
        $stmt->execute(['user_id' => $userId]);
        $appointments = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $upcoming = [];
        $past = [];
        $now = date('Y-m-d H:i:s');

        foreach ($appointments as $apt) {
            $aptDateTime = $apt['appointment_date'] . ' ' . $apt['appointment_time'];
            if ($aptDateTime >= $now && in_array($apt['status'], ['pending', 'confirmed'])) {
                $upcoming[] = $apt;
            } else {
                $past[] = $apt;
            }
        }

        // Sort upcoming ascending (nearest first)
        usort($upcoming, function($a, $b) {
            $dtA = $a['appointment_date'] . ' ' . $a['appointment_time'];
            $dtB = $b['appointment_date'] . ' ' . $b['appointment_time'];
            return strcmp($dtA, $dtB);
        });

        return [
            'upcoming' => $upcoming,
            'past' => $past
        ];
    }

    public function updateStatus(int $userId, int $appointmentId, string $status): void {
        $stmt = $this->db->prepare("UPDATE appointments SET status = :status WHERE id = :id AND user_id = :user_id");
        $stmt->execute([
            'status' => $status,
            'id' => $appointmentId,
            'user_id' => $userId
        ]);
    }
}
