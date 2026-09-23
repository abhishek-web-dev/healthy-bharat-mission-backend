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
            
            $renderError = function($title, $msg) {
                http_response_code(404);
                echo "<!DOCTYPE html><html><head><title>Error</title><meta name='viewport' content='width=device-width, initial-scale=1.0'><script src='https://cdn.tailwindcss.com'></script><link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'></head><body class='bg-gray-50 flex items-center justify-center min-h-screen p-4'><div class='bg-white rounded-2xl shadow-xl max-w-md w-full p-8 text-center border border-gray-100'><div class='inline-flex items-center justify-center w-20 h-20 rounded-full bg-red-50 text-red-500 mb-6 border-4 border-red-100'><i class='fa-solid fa-triangle-exclamation text-3xl'></i></div><h1 class='text-2xl font-bold text-gray-800 mb-3'>{$title}</h1><p class='text-gray-500 mb-8 leading-relaxed'>{$msg}</p><button onclick='window.close()' class='w-full py-3 px-4 bg-gray-900 hover:bg-black text-white font-bold rounded-xl transition-colors'>Close Window</button></div></body></html>";
                exit;
            };

            if (!$backup) {
                $renderError("Backup Not Found", "The requested backup record could not be found.");
            }

            if ($backup['status'] !== 'SUCCESS') {
                $renderError("Invalid Backup", "Cannot download an incomplete or failed backup.");
            }

            $filePath = $backup['storage_path'];
            if (!file_exists($filePath)) {
                $renderError("Backup File Missing", "The requested backup file is missing from storage.");
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
