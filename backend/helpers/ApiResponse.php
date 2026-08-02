<?php

class ApiResponse
{
    public static function success(array $data = [], string $message = 'OK', int $statusCode = 200): void
    {
        self::send(true, $data, $message, [], $statusCode);
    }

    public static function validation(array $errors, string $message = 'Validation failed'): void
    {
        self::send(false, [], $message, $errors, 422);
    }

    public static function unauthorized(string $message = 'Unauthorized'): void
    {
        self::send(false, [], $message, [], 401);
    }

    public static function forbidden(string $message = 'Forbidden'): void
    {
        self::send(false, [], $message, [], 403);
    }

    public static function notFound(string $message = 'Not found'): void
    {
        self::send(false, [], $message, [], 404);
    }

    public static function serverError(string $message = 'Server error'): void
    {
        self::send(false, [], $message, [], 500);
    }

    public static function send(bool $success, array $data, string $message, array $errors, int $statusCode): void
    {
        if (!headers_sent()) {
            http_response_code($statusCode);
            header('Content-Type: application/json');
        }

        echo json_encode([
            'success' => $success,
            'data' => $data,
            'message' => $message,
            'errors' => $errors,
            'request_id' => self::requestId(),
        ]);
    }

    private static function requestId(): string
    {
        if (class_exists('RequestContext')) {
            return RequestContext::getRequestId();
        }

        return bin2hex(random_bytes(16));
    }
}
