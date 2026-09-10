<?php

namespace HBM\Services;

use HBM\Repositories\AdminDashboardRepository;

class AdminDashboardService {
    private AdminDashboardRepository $repo;

    public function __construct() {
        $this->repo = new AdminDashboardRepository();
    }

    public function getDashboardStats(): array {
        return [
            'stats' => $this->repo->getStats(),
            'recent_activity' => $this->repo->getRecentActivity()
        ];
    }
}
