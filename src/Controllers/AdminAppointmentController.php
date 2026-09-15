<?php
namespace HBM\Controllers;
use HBM\Services\AdminAppointmentService;
use HBM\Helpers\Request;
use HBM\Helpers\Response;

class AdminAppointmentController {
    private AdminAppointmentService $service;

    public function __construct() {
        $this->service = new AdminAppointmentService(); 
    }

    public function listExperts(): void {
        try {
            $page = (int)($_GET['page'] ?? 1);
            $perPage = (int)($_GET['per_page'] ?? 50);
            $result = $this->service->getAdminExperts($page, $perPage);
            Response::success("Experts retrieved", $result);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    public function updateExpert(string $id): void {
        try {
            global $authUser;
            $data = Request::getJson();
            $this->service->updateExpertProfile((int)$id, $data);
            \HBM\Services\AdminActivityLogService::log($authUser['id'] ?? 0, 'EXPERT_UPDATED', 'users', (int)$id);
            Response::success("Expert updated");
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    public function listAppointments(): void {
        try {
            $page = (int)($_GET['page'] ?? 1);
            $perPage = (int)($_GET['per_page'] ?? 50);
            $result = $this->service->getAdminAppointments($page, $perPage);
            Response::success("Appointments retrieved", $result);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    public function updateAppointment(string $id): void {
        try {
            global $authUser;
            $data = Request::getJson();
            $this->service->updateAppointmentStatus((int)$id, $data);
            \HBM\Services\AdminActivityLogService::log($authUser['id'] ?? 0, 'APPOINTMENT_UPDATED', 'appointments', (int)$id);
            Response::success("Appointment updated");
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }
}
