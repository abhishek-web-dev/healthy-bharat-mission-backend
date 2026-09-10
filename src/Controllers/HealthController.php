<?php

namespace HBM\Controllers;

use HBM\Helpers\Response;
use HBM\Core\Database;
use Exception;

class HealthController {
    public function check(): void {
        $status = [
            'app' => 'running',
            'database' => 'disconnected',
            'timestamp' => date('Y-m-d H:i:s')
        ];

        try {
            $db = Database::getConnection();
            if ($db) {
                $status['database'] = 'connected';
            }
        } catch (Exception $e) {
            // Intentionally not passing the exception message to the client
            Response::error('Health check failed', 500, $status);
        }

        Response::success('System is healthy', $status);
    }
}
