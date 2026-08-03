<?php
require_once __DIR__ . '/_notification_helpers.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { ApiResponse::send(false, [], 'Method not allowed.', [], 405); exit; }
    $data = notifications_json_input();
    $userId = notifications_require_user();
    if ($userId === null) { exit; }
    if (!notifications_require_csrf($data)) { ApiResponse::forbidden('Invalid CSRF token.'); exit; }

    $connection = notifications_connection();
    $statement = $connection->prepare(
        'UPDATE notifications SET is_read = 1, read_at = COALESCE(read_at, CURRENT_TIMESTAMP) WHERE recipient_user_id = :user_id AND is_read = 0'
    );
    $statement->execute(['user_id' => $userId]);

    ApiResponse::success(['updated' => $statement->rowCount()], 'Notifications marked read.');
} catch (Throwable $exception) {
    Logger::error('Notification mark all read failed', ['error' => $exception->getMessage()]);
    ApiResponse::serverError('Unable to mark notifications read.');
}