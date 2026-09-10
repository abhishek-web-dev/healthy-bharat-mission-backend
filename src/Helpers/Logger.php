<?php

namespace HBM\Helpers;

class Logger {
    private static string $logFile = __DIR__ . '/../../storage/logs/app.log';

    public static function info(string $message, array $context = []): void {
        self::log('INFO', $message, $context);
    }

    public static function error(string $message, array $context = []): void {
        self::log('ERROR', $message, $context);
    }

    public static function warning(string $message, array $context = []): void {
        self::log('WARNING', $message, $context);
    }

    private static function log(string $level, string $message, array $context = []): void {
        $date = date('Y-m-d H:i:s');
        $contextStr = empty($context) ? '' : json_encode(self::sanitizeContext($context));
        $logMessage = "[$date] $level: $message $contextStr" . PHP_EOL;
        
        file_put_contents(self::$logFile, $logMessage, FILE_APPEND);
    }

    private static function sanitizeContext(array $context): array {
        $sensitiveKeys = ['password', 'password_confirmation', 'token', 'otp_code', 'razorpay_signature', 'card_number'];
        foreach ($context as $key => $value) {
            if (in_array(strtolower($key), $sensitiveKeys)) {
                $context[$key] = '********';
            } elseif (is_array($value)) {
                $context[$key] = self::sanitizeContext($value);
            }
        }
        return $context;
    }
}
