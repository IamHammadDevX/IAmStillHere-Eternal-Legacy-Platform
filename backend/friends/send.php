<?php
require_once __DIR__ . '/_friend_helpers.php';
require_once __DIR__ . '/../services/NotificationService.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { ApiResponse::send(false, [], 'Method not allowed.', [], 405); exit; }
    $data = friends_input();
    $connection = friends_connection();
    if (!SessionHelper::isAuthenticated()) { ApiResponse::unauthorized(); exit; }
    if (!friends_require_active($connection)) { ApiResponse::forbidden('Active account required.'); exit; }
    if (!friends_csrf($data)) { ApiResponse::forbidden('Invalid CSRF token.'); exit; }

    $senderId = SessionHelper::getUserId();
    $targetId = (int) ($data['user_id'] ?? 0);
    if ($targetId <= 0 || !friends_active($connection, $targetId)) { ApiResponse::notFound('User not found.'); exit; }
    if ($targetId === $senderId) { ApiResponse::validation(['user_id' => 'You cannot add yourself.']); exit; }
    if (friends_are_blocked($connection, $senderId, $targetId)) { ApiResponse::forbidden('Friend request blocked.'); exit; }
    if (friends_are_friends($connection, $senderId, $targetId)) { ApiResponse::send(false, [], 'Already friends.', [], 409); exit; }

    $pending = friends_pending($connection, $senderId, $targetId);
    if ($pending) { ApiResponse::send(false, ['status' => friends_status($connection, $senderId, $targetId)], 'Request already pending.', [], 409); exit; }

    $statement = $connection->prepare("INSERT INTO friend_requests (sender_id, receiver_id, status) VALUES (:sender_id, :receiver_id, 'pending')");
    $statement->execute(['sender_id' => $senderId, 'receiver_id' => $targetId]);
    $requestId = (int) $connection->lastInsertId();

    NotificationService::createOnce(
        $connection,
        $targetId,
        $senderId,
        NotificationService::TYPE_FRIEND_REQUEST,
        'friend_request',
        $requestId,
        'sent you a friend request.'
    );

    ApiResponse::success(['request_id' => $requestId], 'Friend request sent.', 201);
} catch (Throwable $exception) {
    Logger::error('Friend send failed', ['error' => $exception->getMessage()]);
    ApiResponse::serverError('Unable to send request.');
}
?>