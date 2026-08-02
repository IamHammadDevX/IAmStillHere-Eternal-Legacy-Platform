<?php

class CsrfHelper
{
    private const SESSION_KEY = 'csrf_token';

    public static function getToken(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }

        return $_SESSION[self::SESSION_KEY];
    }

    public static function validate(?string $token): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return is_string($token)
            && isset($_SESSION[self::SESSION_KEY])
            && hash_equals($_SESSION[self::SESSION_KEY], $token);
    }

    public static function getTokenFromRequest(array $data = []): ?string
    {
        $headerToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (is_string($headerToken) && $headerToken !== '') {
            return $headerToken;
        }

        return isset($data['csrf_token']) && is_string($data['csrf_token']) ? $data['csrf_token'] : null;
    }
}
