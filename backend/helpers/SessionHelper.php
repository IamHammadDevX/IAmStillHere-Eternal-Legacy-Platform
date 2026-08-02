<?php

class SessionHelper
{
    public static function getUserId(): ?int
    {
        return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    }

    public static function getUserRole(): string
    {
        if (!empty($_SESSION['user_role'])) {
            return (string) $_SESSION['user_role'];
        }

        return !empty($_SESSION['role']) ? (string) $_SESSION['role'] : 'visitor';
    }

    public static function isAuthenticated(): bool
    {
        return self::getUserId() !== null;
    }

    public static function isAdmin(): bool
    {
        return self::getUserRole() === 'admin';
    }
}
