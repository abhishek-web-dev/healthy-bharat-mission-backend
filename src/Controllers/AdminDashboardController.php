<?php

namespace HBM\Controllers;

use HBM\Services\AdminDashboardService;
use HBM\Helpers\Response;
use Exception;

class AdminDashboardController {
    private AdminDashboardService $service;

    public function __construct() {
        $this->service = new AdminDashboardService();
    }

    public function getStats(): void {
        try {
            $data = $this->service->getDashboardStats();
            Response::success('Dashboard stats fetched successfully', $data);
        } catch (Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }
}
