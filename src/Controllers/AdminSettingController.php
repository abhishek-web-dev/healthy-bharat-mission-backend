<?php
namespace HBM\Controllers;

use HBM\Services\AdminSettingService;
use HBM\Services\AdminActivityLogService;
use HBM\Helpers\Request;
use HBM\Helpers\Response;

class AdminSettingController {
    private AdminSettingService $service;

    public function __construct() {
        $this->service = new AdminSettingService();
    }

    public function getSettings(): void {
        try {
            $settings = $this->service->getAllSettings();
            Response::success("Settings retrieved", $settings);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    public function updateSettings(): void {
        try {
            $data = Request::getJson();
            $adminUser = $GLOBALS['authUser'] ?? null;
            $adminId = $adminUser ? (int)$adminUser['id'] : null;

            if (!$adminId) {
                Response::error("Unauthorized", 401);
                return;
            }

            $this->service->updateSettings($data, $adminId);

            // Log this action
            AdminActivityLogService::log($adminId, 'updated_settings', 'settings', null, ['keys' => array_keys($data)]);

            Response::success("Settings updated successfully");
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }
}
