<?php

class RequestContext
{
    private static ?string $requestId = null;

    public static function getRequestId(): string
    {
        if (self::$requestId === null) {
            self::$requestId = self::generateRequestId();
        }

        return self::$requestId;
    }

    public static function getUserId(): ?int
    {
        if (session_status() === PHP_SESSION_NONE) {
            return null;
        }

        return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    }

    public static function getEndpoint(): string
    {
        return basename($_SERVER['SCRIPT_NAME'] ?? PHP_SAPI);
    }

    private static function generateRequestId(): string
    {
        return date('YmdHis') . '-' . bin2hex(random_bytes(8));
    }
}
