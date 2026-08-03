<?php
require_once __DIR__ . '/_post_helpers.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { ApiResponse::send(false, [], 'Method not allowed.', [], 405); exit; }
    $data = posts_json_input();
    $connection = posts_connection();
    if (!SessionHelper::isAuthenticated()) { ApiResponse::unauthorized(); exit; }
    if (!posts_require_csrf($data)) { ApiResponse::forbidden('Invalid CSRF token.'); exit; }
    $postId = (int) ($data['post_id'] ?? 0);
    $post = posts_find_post($connection, $postId);
    if (!$post || $post['status'] !== 'active') { ApiResponse::notFound('Post not found.'); exit; }
    $viewerId = SessionHelper::getUserId();
    if ($viewerId !== (int) $post['user_id'] && !SessionHelper::isAdmin()) { ApiResponse::forbidden('Not allowed to delete this post.'); exit; }
    $stmt = $connection->prepare("UPDATE posts SET status = 'deleted', deleted_at = CURRENT_TIMESTAMP WHERE id = :id");
    $stmt->execute(['id' => $postId]);
    ApiResponse::success([], 'Post deleted.');
} catch (Throwable $e) {
    Logger::error('Post delete failed', ['error' => $e->getMessage()]);
    ApiResponse::serverError('Unable to delete post.');
}
?>
