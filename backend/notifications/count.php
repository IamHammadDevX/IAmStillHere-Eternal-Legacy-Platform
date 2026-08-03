<?php
require_once __DIR__ . '/_notification_helpers.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') { ApiResponse::send(false, [], 'Method not allowed.', [], 405); exit; }
    $userId = notifications_require_user();
    if ($userId === null) { exit; }

    $connection = notifications_connection();
    $statement = $connection->prepare('SELECT COUNT(*) AS unread_count FROM notifications WHERE recipient_user_id = :user_id AND is_read = 0');
    $statement->execute(['user_id' => $userId]);
    $count = (int) ($statement->fetch(PDO::FETCH_ASSOC)['unread_count'] ?? 0);

    ApiResponse::success(['unread_count' => $count], 'Unread count loaded.');
} catch (Throwable $exception) {
    Logger::error('Notification count failed', ['error' => $exception->getMessage()]);
    ApiResponse::serverError('Unable to load notification count.');
}