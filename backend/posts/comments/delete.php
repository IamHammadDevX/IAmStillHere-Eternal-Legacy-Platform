<?php
require_once __DIR__ . '/../_post_helpers.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { ApiResponse::send(false, [], 'Method not allowed.', [], 405); exit; }
    $data = posts_json_input();
    $connection = posts_connection();
    if (!SessionHelper::isAuthenticated()) { ApiResponse::unauthorized(); exit; }
    if (!posts_require_csrf($data)) { ApiResponse::forbidden('Invalid CSRF token.'); exit; }
    $commentId = (int) ($data['comment_id'] ?? 0);
    $comment = posts_find_comment($connection, $commentId);
    if (!$comment || $comment['deleted_at'] !== null) { ApiResponse::notFound('Comment not found.'); exit; }
    if (!posts_user_can_delete_comment($comment)) { ApiResponse::forbidden('Not allowed to delete this comment.'); exit; }
    $stmt = $connection->prepare('UPDATE post_comments SET deleted_at = CURRENT_TIMESTAMP WHERE id = :id');
    $stmt->execute(['id' => $commentId]);
    ApiResponse::success([], 'Comment deleted.');
} catch (Throwable $e) {
    Logger::error('Post comment delete failed', ['error' => $e->getMessage()]);
    ApiResponse::serverError('Unable to delete comment.');
}
?>
