<?php

require_once __DIR__ . '/AuthMiddleware.php';

class RoleMiddleware
{
    public static function requireAdmin(): bool
    {
        return AuthMiddleware::userRole() === ROLE_ADMIN;
    }

    public static function requireClient(): bool
    {
        return AuthMiddleware::userRole() === ROLE_CLIENT;
    }

    public static function hasRole(string $role): bool
    {
        return AuthMiddleware::userRole() === $role;
    }
}
