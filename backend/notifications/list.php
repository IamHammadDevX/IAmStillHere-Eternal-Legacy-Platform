<?php
require_once __DIR__ . '/_notification_helpers.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') { ApiResponse::send(false, [], 'Method not allowed.', [], 405); exit; }
    $userId = notifications_require_user();
    if ($userId === null) { exit; }

    $connection = notifications_connection();
    $page = max(1, (int) ($_GET['page'] ?? 1));
    $limit = min(50, max(1, (int) ($_GET['limit'] ?? 20)));
    $offset = ($page - 1) * $limit;

    $count = $connection->prepare('SELECT COUNT(*) AS total FROM notifications WHERE recipient_user_id = :user_id');
    $count->execute(['user_id' => $userId]);
    $total = (int) ($count->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

    $statement = $connection->prepare(
        "SELECT n.*, u.full_name AS actor_name, u.profile_photo AS actor_profile_photo
         FROM notifications n
         LEFT JOIN users u ON u.id = n.actor_user_id
         WHERE n.recipient_user_id = :user_id
         ORDER BY n.created_at DESC, n.id DESC
         LIMIT :limit OFFSET :offset"
    );
    $statement->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
    $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
    $statement->execute();

    $notifications = array_map('notifications_format', $statement->fetchAll(PDO::FETCH_ASSOC));

    ApiResponse::success([
        'notifications' => $notifications,
        'pagination' => [
            'current_page' => $page,
            'per_page' => $limit,
            'total_items' => $total,
            'total_pages' => (int) ceil($total / $limit),
        ],
    ], 'Notifications loaded.');
} catch (Throwable $exception) {
    Logger::error('Notifications list failed', ['error' => $exception->getMessage()]);
    ApiResponse::serverError('Unable to load notifications.');
}