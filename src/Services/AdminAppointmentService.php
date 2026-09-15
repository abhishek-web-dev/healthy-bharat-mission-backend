<?php
namespace HBM\Services;
use HBM\Repositories\AdminAppointmentRepository;

class AdminAppointmentService {
    private AdminAppointmentRepository $repo;

    public function __construct() {
        $this->repo = new AdminAppointmentRepository();
    }

    public function getAdminExperts(int $page, int $perPage): array {
        return $this->repo->getAdminExperts($page, $perPage);
    }

    public function updateExpertProfile(int $userId, array $data): void {
        $this->repo->updateExpertProfile($userId, $data);
    }

    public function getAdminAppointments(int $page, int $perPage): array {
        return $this->repo->getAdminAppointments($page, $perPage);
    }

    public function updateAppointmentStatus(int $id, array $data): void {
        $this->repo->updateAppointmentStatus($id, $data);
    }
}
