<?php
require_once __DIR__ . '/../_post_helpers.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') { ApiResponse::send(false, [], 'Method not allowed.', [], 405); exit; }
    $connection = posts_connection();
    if (!SessionHelper::isAuthenticated()) { ApiResponse::unauthorized(); exit; }
    $postId = (int) ($_GET['post_id'] ?? 0);
    $page = max(1, (int) ($_GET['page'] ?? 1));
    $limit = min(50, max(1, (int) ($_GET['limit'] ?? 20)));
    $offset = ($page - 1) * $limit;
    $post = posts_find_post($connection, $postId);
    if (!$post || !posts_can_view_post($connection, $post)) { ApiResponse::notFound('Post not found or not accessible.'); exit; }

    $count = $connection->prepare('SELECT COUNT(*) AS total FROM post_comments WHERE post_id = :post_id AND deleted_at IS NULL');
    $count->execute(['post_id' => $postId]);
    $total = (int) ($count->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

    $stmt = $connection->prepare(
        "SELECT c.id, c.post_id, c.user_id, c.comment_text, c.created_at, c.updated_at,
                u.full_name AS author_name, u.profile_photo AS author_profile_photo
         FROM post_comments c
         LEFT JOIN users u ON u.id = c.user_id
         WHERE c.post_id = :post_id AND c.deleted_at IS NULL
         ORDER BY c.created_at ASC, c.id ASC
         LIMIT :limit OFFSET :offset"
    );
    $stmt->bindValue(':post_id', $postId, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $comments = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $comment) {
        $comment['id'] = (int) $comment['id'];
        $comment['post_id'] = (int) $comment['post_id'];
        $comment['user_id'] = $comment['user_id'] !== null ? (int) $comment['user_id'] : null;
        $comment['author_name'] = $comment['author_name'] ?: 'Deleted user';
        $comment['can_edit'] = posts_user_can_edit_comment($comment);
        $comment['post_owner_id'] = (int) $post['user_id'];
        $comment['can_delete'] = posts_user_can_delete_comment($comment);
        unset($comment['post_owner_id']);
        $comments[] = $comment;
    }
    ApiResponse::success(['comments' => $comments, 'pagination' => ['current_page' => $page, 'per_page' => $limit, 'total_items' => $total, 'total_pages' => (int) ceil($total / $limit)]], 'Comments loaded.');
} catch (Throwable $e) {
    Logger::error('Post comments list failed', ['error' => $e->getMessage()]);
    ApiResponse::serverError('Unable to load comments.');
}
?>
