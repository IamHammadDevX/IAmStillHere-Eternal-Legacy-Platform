<?php
require_once __DIR__ . '/../_post_helpers.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { ApiResponse::send(false, [], 'Method not allowed.', [], 405); exit; }
    $data = posts_json_input();
    $connection = posts_connection();
    if (!SessionHelper::isAuthenticated()) { ApiResponse::unauthorized(); exit; }
    if (!posts_require_active_account($connection)) { ApiResponse::forbidden('Active account required.'); exit; }
    if (!posts_require_csrf($data)) { ApiResponse::forbidden('Invalid CSRF token.'); exit; }
    if (!posts_recent_create_allowed('last_post_comment_at', POST_COMMENT_COOLDOWN)) { ApiResponse::send(false, [], 'Please wait before commenting again.', [], 429); exit; }
    $postId = (int) ($data['post_id'] ?? 0);
    $text = trim((string) ($data['comment_text'] ?? ''));
    if ($postId <= 0) { ApiResponse::validation(['post_id' => 'Valid post_id is required.']); exit; }
    if ($text === '') { ApiResponse::validation(['comment_text' => 'Comment cannot be empty.']); exit; }
    if (mb_strlen($text) > POST_COMMENT_MAX_LENGTH) { ApiResponse::validation(['comment_text' => 'Comment cannot exceed 2000 characters.']); exit; }
    $post = posts_find_post($connection, $postId);
    if (!$post || !posts_can_view_post($connection, $post)) { ApiResponse::notFound('Post not found or not accessible.'); exit; }
    $stmt = $connection->prepare('INSERT INTO post_comments (post_id, user_id, comment_text) VALUES (:post_id, :user_id, :comment_text)');
    $stmt->execute(['post_id' => $postId, 'user_id' => SessionHelper::getUserId(), 'comment_text' => $text]);
    ApiResponse::success(['comment_id' => (int) $connection->lastInsertId()], 'Comment posted.', 201);
} catch (Throwable $e) {
    Logger::error('Post comment create failed', ['error' => $e->getMessage()]);
    ApiResponse::serverError('Unable to post comment.');
}
?>
