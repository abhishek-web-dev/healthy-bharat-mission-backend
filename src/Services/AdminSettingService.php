<?php
namespace HBM\Services;

use HBM\Repositories\AdminSettingRepository;
use Exception;

class AdminSettingService {
    private AdminSettingRepository $repo;
    
    // Safelist of allowed settings to prevent arbitrary key injection
    private const ALLOWED_SETTINGS = [
        'site_name',
        'site_description',
        'contact_email',
        'contact_phone',
        'support_email',
        'maintenance_mode',
        'currency'
    ];

    public function __construct() {
        $this->repo = new AdminSettingRepository();
    }

    public function getAllSettings(): array {
        return $this->repo->getAllSettings();
    }

    public function updateSettings(array $data, ?int $userId): void {
        foreach ($data as $key => $value) {
            if (!in_array($key, self::ALLOWED_SETTINGS)) {
                throw new Exception("Invalid setting key: {$key}");
            }
            
            // Basic sanitization
            $value = is_string($value) ? trim($value) : $value;
            
            $this->repo->updateSetting($key, $value, $userId);
        }
    }
}
