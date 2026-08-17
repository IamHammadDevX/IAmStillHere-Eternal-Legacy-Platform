<?php
require_once __DIR__ . '/_post_helpers.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { ApiResponse::send(false, [], 'Method not allowed.', [], 405); exit; }
    $data = posts_json_input();
    $connection = posts_connection();
    if (!SessionHelper::isAuthenticated()) { ApiResponse::unauthorized(); exit; }
    if (!posts_require_active_account($connection)) { ApiResponse::forbidden('Active account required.'); exit; }
    if (!posts_require_csrf($data)) { ApiResponse::forbidden('Invalid CSRF token.'); exit; }

    $postId = (int) ($data['post_id'] ?? 0);
    $body = posts_sanitize_body((string) ($data['body'] ?? ''));
    $privacy = (string) ($data['privacy_level'] ?? 'public');
    if (!in_array($privacy, ['public','family','friends','specific_people','private','release_date','release_event'], true)) $privacy = 'public';
    $legacyPrivacy = in_array($privacy, ['public','family','private'], true) ? $privacy : 'private';
    if ($postId <= 0) { ApiResponse::validation(['post_id' => 'Valid post_id is required.']); exit; }
    if ($body === '') { ApiResponse::validation(['body' => 'Post cannot be empty.']); exit; }
    if (mb_strlen($body) > POST_BODY_MAX_LENGTH) { ApiResponse::validation(['body' => 'Post cannot exceed 5000 characters.']); exit; }

    $post = posts_find_post($connection, $postId);
    if (!$post || $post['status'] !== 'active') { ApiResponse::notFound('Post not found.'); exit; }
    if (SessionHelper::getUserId() !== (int) $post['user_id']) { ApiResponse::forbidden('Only the owner can edit this post.'); exit; }

    $stmt = $connection->prepare('UPDATE posts SET body = :body, privacy_level = :privacy_level WHERE id = :id');
    $stmt->execute(['body' => $body, 'privacy_level' => $legacyPrivacy, 'id' => $postId]);
    ApiResponse::success(['post' => posts_format_post($connection, posts_find_post($connection, $postId))], 'Post updated.');
} catch (Throwable $e) {
    Logger::error('Post update failed', ['error' => $e->getMessage()]);
    ApiResponse::serverError('Unable to update post.');
}
?>
