<?php
require_once __DIR__ . '/../_post_helpers.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { ApiResponse::send(false, [], 'Method not allowed.', [], 405); exit; }
    $data = posts_json_input();
    $connection = posts_connection();
    if (!SessionHelper::isAuthenticated()) { ApiResponse::unauthorized(); exit; }
    if (!posts_require_csrf($data)) { ApiResponse::forbidden('Invalid CSRF token.'); exit; }
    $commentId = (int) ($data['comment_id'] ?? 0);
    $text = trim((string) ($data['comment_text'] ?? ''));
    if ($text === '') { ApiResponse::validation(['comment_text' => 'Comment cannot be empty.']); exit; }
    if (mb_strlen($text) > POST_COMMENT_MAX_LENGTH) { ApiResponse::validation(['comment_text' => 'Comment cannot exceed 2000 characters.']); exit; }
    $comment = posts_find_comment($connection, $commentId);
    if (!$comment || $comment['deleted_at'] !== null) { ApiResponse::notFound('Comment not found.'); exit; }
    if (!posts_user_can_edit_comment($comment)) { ApiResponse::forbidden('Only the comment owner can edit.'); exit; }
    $stmt = $connection->prepare('UPDATE post_comments SET comment_text = :text WHERE id = :id');
    $stmt->execute(['text' => $text, 'id' => $commentId]);
    ApiResponse::success([], 'Comment updated.');
} catch (Throwable $e) {
    Logger::error('Post comment update failed', ['error' => $e->getMessage()]);
    ApiResponse::serverError('Unable to update comment.');
}
?>
