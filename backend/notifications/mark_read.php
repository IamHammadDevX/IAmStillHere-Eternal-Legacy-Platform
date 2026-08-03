<?php
require_once __DIR__ . '/_notification_helpers.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { ApiResponse::send(false, [], 'Method not allowed.', [], 405); exit; }
    $data = notifications_json_input();
    $userId = notifications_require_user();
    if ($userId === null) { exit; }
    if (!notifications_require_csrf($data)) { ApiResponse::forbidden('Invalid CSRF token.'); exit; }

    $notificationId = (int) ($data['notification_id'] ?? 0);
    if ($notificationId <= 0) { ApiResponse::validation(['notification_id' => 'Valid notification_id is required.']); exit; }

    $connection = notifications_connection();
    $statement = $connection->prepare(
        'UPDATE notifications SET is_read = 1, read_at = COALESCE(read_at, CURRENT_TIMESTAMP) WHERE id = :id AND recipient_user_id = :user_id'
    );
    $statement->execute(['id' => $notificationId, 'user_id' => $userId]);

    ApiResponse::success([], 'Notification marked read.');
} catch (Throwable $exception) {
    Logger::error('Notification mark read failed', ['error' => $exception->getMessage()]);
    ApiResponse::serverError('Unable to mark notification read.');
}