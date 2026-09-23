<?php
namespace HBM\Controllers;

use HBM\Core\Response;
use HBM\Core\AuthMiddleware;
use HBM\Repositories\UserAppointmentRepository;

class UserAppointmentController {
    private UserAppointmentRepository $repo;

    public function __construct() {
        $this->repo = new UserAppointmentRepository();
    }

    public function getAppointments(): void {
        try {
            $user = AuthMiddleware::getUser();
            $appointments = $this->repo->getUserAppointments($user['id']);
            Response::success("Appointments retrieved", $appointments);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    public function cancelAppointment(string $id): void {
        try {
            $user = AuthMiddleware::getUser();
            // Optional: check if appointment exists and is cancellable
            $this->repo->updateStatus($user['id'], (int)$id, 'cancelled');
            Response::success("Appointment cancelled successfully");
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }
}
