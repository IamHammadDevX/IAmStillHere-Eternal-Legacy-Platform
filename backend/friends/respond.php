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

    $userId = SessionHelper::getUserId();
    $requestId = (int) ($data['request_id'] ?? 0);
    $action = $data['action'] ?? '';
    if (!in_array($action, ['accept', 'reject'], true)) { ApiResponse::validation(['action' => 'Invalid action.']); exit; }

    $statement = $connection->prepare("SELECT * FROM friend_requests WHERE id = :id AND receiver_id = :user_id AND status = 'pending' LIMIT 1");
    $statement->execute(['id' => $requestId, 'user_id' => $userId]);
    $request = $statement->fetch(PDO::FETCH_ASSOC);
    if (!$request) { ApiResponse::notFound('Request not found.'); exit; }

    if ($action === 'accept') {
        $connection->beginTransaction();
        $update = $connection->prepare("UPDATE friend_requests SET status = 'accepted', responded_at = NOW() WHERE id = :id");
        $update->execute(['id' => $requestId]);

        $insert = $connection->prepare("INSERT INTO friendships (user_id, friend_id, status) VALUES (:user_id, :friend_id, 'accepted') ON DUPLICATE KEY UPDATE status = 'accepted'");
        $insert->execute(['user_id' => $request['sender_id'], 'friend_id' => $request['receiver_id']]);
        $insert->execute(['user_id' => $request['receiver_id'], 'friend_id' => $request['sender_id']]);

        NotificationService::createOnce(
            $connection,
            (int) $request['sender_id'],
            $userId,
            NotificationService::TYPE_FRIEND_REQUEST_ACCEPTED,
            'friend_request',
            $requestId,
            'accepted your friend request.'
        );

        $connection->commit();
        ApiResponse::success([], 'Friend request accepted.');
    } else {
        $update = $connection->prepare("UPDATE friend_requests SET status = 'rejected', responded_at = NOW() WHERE id = :id");
        $update->execute(['id' => $requestId]);
        ApiResponse::success([], 'Friend request rejected.');
    }
} catch (Throwable $exception) {
    if (isset($connection) && $connection->inTransaction()) { $connection->rollBack(); }
    Logger::error('Friend respond failed', ['error' => $exception->getMessage()]);
    ApiResponse::serverError('Unable to respond.');
}
?>