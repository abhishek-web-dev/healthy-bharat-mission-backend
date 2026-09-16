<?php
namespace HBM\Controllers;

use HBM\Services\DatabaseBackupService;
use HBM\Repositories\DatabaseBackupRepository;
use HBM\Repositories\AdminSettingRepository;
use HBM\Services\AdminActivityLogService;
use HBM\Helpers\Response;
use Exception;

class DatabaseBackupController {
    private DatabaseBackupService $service;
    private DatabaseBackupRepository $repo;
    private AdminSettingRepository $settingRepo;

    public function __construct() {
        $this->service = new DatabaseBackupService();
        $this->repo = new DatabaseBackupRepository();
        $this->settingRepo = new AdminSettingRepository();
    }

    public function listBackups(): void {
        try {
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
            $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
            $backups = $this->repo->getBackups($limit, $offset);
            
            $settings = $this->settingRepo->getAllSettings();
            
            Response::success('Backups fetched successfully', [
                'backups' => $backups,
                'settings' => [
                    'backup_automated_enabled' => $settings['backup_automated_enabled'] ?? 'false',
                    'backup_daily_time' => $settings['backup_daily_time'] ?? '02:00'
                ]
            ]);
        } catch (Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    public function createManualBackup(): void {
        global $authUser;
        try {
            $result = $this->service->createBackup($authUser['id']);
            Response::success('Backup created successfully', $result);
        } catch (Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    public function downloadBackup(int $id): void {
        global $authUser;
        try {
            $backup = $this->repo->getBackupById($id);
            if (!$backup) {
                Response::error('Backup not found', 404);
            }

            if ($backup['status'] !== 'SUCCESS') {
                Response::error('Cannot download an incomplete or failed backup.', 400);
            }

            $filePath = $backup['storage_path'];
            if (!file_exists($filePath)) {
                Response::error('Backup file is missing from storage.', 404);
            }

            // Log download action
            AdminActivityLogService::log($authUser['id'], 'BACKUP_DOWNLOADED', 'database_backups', $id, ['filename' => $backup['filename']]);

            // Stream file safely
            header('Content-Description: File Transfer');
            header('Content-Type: application/gzip');
            header('Content-Disposition: attachment; filename="'.basename($filePath).'"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($filePath));
            flush(); // Flush system output buffer
            readfile($filePath);
            exit;
        } catch (Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    public function updateSettings(): void {
        global $authUser;
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        
        try {
            if (isset($input['backup_automated_enabled'])) {
                $enabled = filter_var($input['backup_automated_enabled'], FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false';
                $this->settingRepo->updateSetting('backup_automated_enabled', $enabled, $authUser['id']);
            }
            if (isset($input['backup_daily_time'])) {
                // simple time validation
                $time = preg_match('/^(?:2[0-3]|[01][0-9]):[0-5][0-9]$/', $input['backup_daily_time']) ? $input['backup_daily_time'] : '02:00';
                $this->settingRepo->updateSetting('backup_daily_time', $time, $authUser['id']);
            }
            
            AdminActivityLogService::log($authUser['id'], 'BACKUP_SETTINGS_UPDATED', 'settings', null, $input);
            Response::success('Backup settings updated successfully');
        } catch (Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }
}
