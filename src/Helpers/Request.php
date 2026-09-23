<?php

namespace HBM\Helpers;

class Request {
    public static function getJson(): array {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        return is_array($data) ? $data : [];
    }
    
    public static function getBearerToken(): ?string {
        $headers = self::getHeaders();
        
        // Find Authorization header case-insensitively
        $authHeader = null;
        foreach ($headers as $name => $value) {
            if (strtolower($name) === 'authorization') {
                $authHeader = $value;
                break;
            }
        }

        if ($authHeader) {
            if (preg_match('/Bearer\s(\S+)/i', $authHeader, $matches)) {
                return $matches[1];
            }
        }
        return null;
    }
    
    public static function getHeaders(): array {
        if (function_exists('getallheaders')) {
            return getallheaders();
        }
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $header = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
                $headers[$header] = $value;
            }
        }
        return $headers;
    }
}
