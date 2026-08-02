<?php

require_once __DIR__ . '/../../config/config.php';

class AuthMiddleware
{
    public static function userId(): ?int
    {
        return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    }

    public static function userRole(): string
    {
        return $_SESSION['user_role'] ?? ROLE_VISITOR;
    }

    public static function isAuthenticated(): bool
    {
        return self::userId() !== null;
    }

    public static function requireAuthenticated(): bool
    {
        return self::isAuthenticated();
    }

    public static function hasActiveSession(): bool
    {
        if (!self::isAuthenticated()) {
            return false;
        }

        if (!isset($_SESSION['last_activity'])) {
            return true;
        }

        return (time() - (int) $_SESSION['last_activity']) <= SESSION_TIMEOUT;
    }
}
