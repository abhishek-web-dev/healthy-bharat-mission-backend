<?php
namespace HBM\Controllers;

use HBM\Repositories\AdminAuditLogRepository;
use HBM\Helpers\Response;

class AdminAuditLogController {
    private AdminAuditLogRepository $repo;

    public function __construct() {
        $this->repo = new AdminAuditLogRepository();
    }

    public function listLogs(): void {
        try {
            $page = (int)($_GET['page'] ?? 1);
            $perPage = (int)($_GET['per_page'] ?? 50);
            $search = $_GET['search'] ?? '';
            $action = $_GET['action'] ?? '';

            $result = $this->repo->getLogs($page, $perPage, $search, $action);
            Response::success("Audit logs retrieved", $result);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }
}
