<?php

require_once __DIR__ . '/RequestContext.php';

class Logger
{
    private const SENSITIVE_KEYS = [
        'password',
        'pass',
        'token',
        'reset_token',
        'verification_code',
        'api_key',
        'apikey',
        'secret',
        'cookie',
        'smtp_username',
        'smtp_password',
        'authorization',
        'file',
        'file_content',
        'email',
        'full_name',
        'name',
    ];

    public static function info(string $message, array $context = []): void
    {
        self::write('info', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::write('warning', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::write('error', $message, $context);
    }

    public static function write(string $level, string $message, array $context = []): void
    {
        $entry = [
            'timestamp' => gmdate('c'),
            'level' => strtolower($level),
            'request_id' => RequestContext::getRequestId(),
            'endpoint' => RequestContext::getEndpoint(),
            'user_id' => RequestContext::getUserId(),
            'message' => $message,
            'context' => self::sanitize($context),
        ];

        $line = json_encode($entry, JSON_UNESCAPED_SLASHES) . PHP_EOL;
        $logFile = self::logFile();

        if ($logFile !== null) {
            $written = @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
            if ($written !== false) {
                return;
            }
        }

        error_log($line);
    }

    private static function sanitize(array $context): array
    {
        $safe = [];

        foreach ($context as $key => $value) {
            $normalizedKey = strtolower((string) $key);

            if (self::isSensitiveKey($normalizedKey)) {
                $safe[$key] = '[redacted]';
                continue;
            }

            if (is_array($value)) {
                $safe[$key] = self::sanitize($value);
                continue;
            }

            if (is_object($value)) {
                $safe[$key] = '[object]';
                continue;
            }

            if (is_string($value) && strlen($value) > 500) {
                $safe[$key] = substr($value, 0, 500) . '...';
                continue;
            }

            $safe[$key] = $value;
        }

        return $safe;
    }

    private static function isSensitiveKey(string $key): bool
    {
        foreach (self::SENSITIVE_KEYS as $sensitiveKey) {
            if (strpos($key, $sensitiveKey) !== false) {
                return true;
            }
        }

        return false;
    }

    private static function logFile(): ?string
    {
        $basePath = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 2);
        $preferred = dirname($basePath) . DIRECTORY_SEPARATOR . 'iamalwayshere_logs';

        if (self::ensureDirectory($preferred)) {
            return $preferred . DIRECTORY_SEPARATOR . 'application.log';
        }

        $fallback = $basePath . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'logs';
        if (self::ensureDirectory($fallback)) {
            return $fallback . DIRECTORY_SEPARATOR . 'application.log';
        }

        return null;
    }

    private static function ensureDirectory(string $directory): bool
    {
        if (is_dir($directory)) {
            return is_writable($directory);
        }

        return @mkdir($directory, 0775, true);
    }
}
