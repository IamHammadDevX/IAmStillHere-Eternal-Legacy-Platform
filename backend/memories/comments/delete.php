<?php

require_once __DIR__ . '/_comment_helpers.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        ApiResponse::send(false, [], 'Method not allowed.', [], 405);
        exit;
    }

    $data = memory_comments_json_input();
    $connection = memory_comments_connection();

    if (!SessionHelper::isAuthenticated()) {
        ApiResponse::unauthorized();
        exit;
    }

    if (!memory_comments_require_active_account($connection)) {
        ApiResponse::forbidden('Active account required.');
        exit;
    }

    if (!memory_comments_require_csrf($data)) {
        ApiResponse::forbidden('Invalid CSRF token.');
        exit;
    }

    $commentId = (int) ($data['comment_id'] ?? 0);
    if ($commentId <= 0) {
        ApiResponse::validation(['comment_id' => 'A valid comment_id is required.']);
        exit;
    }

    $comment = memory_comments_find_comment($connection, $commentId);
    if (!$comment || $comment['deleted_at'] !== null) {
        ApiResponse::notFound('Comment not found.');
        exit;
    }

    $memory = memory_comments_find_memory($connection, (int) $comment['memory_id']);
    if (!$memory || !memory_comments_can_view_memory($connection, $memory)) {
        ApiResponse::notFound('Memory not found or not accessible.');
        exit;
    }

    if (!memory_comments_user_can_delete($comment)) {
        ApiResponse::forbidden('You cannot delete this comment.');
        exit;
    }

    $statement = $connection->prepare(
        "UPDATE memory_comments
         SET deleted_at = NOW()
         WHERE id = :id"
    );
    $statement->execute(['id' => $commentId]);

    Logger::info('Memory comment deleted', ['comment_id' => $commentId]);
    ApiResponse::success([], 'Comment deleted.');
} catch (Throwable $exception) {
    Logger::error('Memory comment delete failed', ['error' => $exception->getMessage()]);
    ApiResponse::serverError('Unable to delete comment.');
}
