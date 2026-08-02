<?php

require_once __DIR__ . '/_comment_helpers.php';

try {
    $connection = memory_comments_connection();
    $memoryId = (int) ($_GET['memory_id'] ?? 0);
    $page = max(1, (int) ($_GET['page'] ?? 1));
    $limit = min(50, max(1, (int) ($_GET['limit'] ?? 20)));
    $offset = ($page - 1) * $limit;

    if ($memoryId <= 0) {
        ApiResponse::validation(['memory_id' => 'A valid memory_id is required.']);
        exit;
    }

    $memory = memory_comments_find_memory($connection, $memoryId);
    if (!$memory || !memory_comments_can_view_memory($connection, $memory)) {
        ApiResponse::notFound('Memory not found or not accessible.');
        exit;
    }

    $countStatement = $connection->prepare(
        "SELECT COUNT(*) AS total
         FROM memory_comments
         WHERE memory_id = :memory_id
         AND deleted_at IS NULL"
    );
    $countStatement->execute(['memory_id' => $memoryId]);
    $total = (int) $countStatement->fetch(PDO::FETCH_ASSOC)['total'];

    $statement = $connection->prepare(
        "SELECT
            c.id,
            c.memory_id,
            c.user_id,
            c.comment_text,
            c.created_at,
            c.updated_at,
            u.full_name AS author_name,
            u.profile_photo AS author_profile_photo
         FROM memory_comments c
         LEFT JOIN users u ON u.id = c.user_id
         WHERE c.memory_id = :memory_id
         AND c.deleted_at IS NULL
         ORDER BY c.created_at ASC, c.id ASC
         LIMIT :limit OFFSET :offset"
    );
    $statement->bindValue(':memory_id', $memoryId, PDO::PARAM_INT);
    $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
    $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
    $statement->execute();

    $comments = $statement->fetchAll(PDO::FETCH_ASSOC);
    $viewerId = SessionHelper::getUserId();
    $isAdmin = SessionHelper::isAdmin();

    foreach ($comments as &$comment) {
        $commentUserId = $comment['user_id'] !== null ? (int) $comment['user_id'] : null;
        $comment['id'] = (int) $comment['id'];
        $comment['memory_id'] = (int) $comment['memory_id'];
        $comment['user_id'] = $commentUserId;
        $comment['author_name'] = $comment['author_name'] ?: 'Deleted user';
        $comment['can_edit'] = $commentUserId !== null
            && (($viewerId !== null && $commentUserId === $viewerId) || $isAdmin);
        $comment['can_delete'] = ($viewerId !== null && $commentUserId === $viewerId)
            || ($viewerId !== null && (int) $memory['user_id'] === $viewerId)
            || $isAdmin;
    }
    unset($comment);

    ApiResponse::success([
        'comments' => $comments,
        'pagination' => [
            'current_page' => $page,
            'limit' => $limit,
            'total_items' => $total,
            'total_pages' => (int) ceil($total / $limit),
        ],
    ], 'Comments loaded.');
} catch (Throwable $exception) {
    Logger::error('Memory comments list failed', ['error' => $exception->getMessage()]);
    ApiResponse::serverError('Unable to load comments.');
}
