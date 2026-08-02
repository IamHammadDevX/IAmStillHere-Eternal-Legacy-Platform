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
    $commentText = trim((string) ($data['comment_text'] ?? ''));

    if ($commentId <= 0) {
        ApiResponse::validation(['comment_id' => 'A valid comment_id is required.']);
        exit;
    }

    if ($commentText === '') {
        ApiResponse::validation(['comment_text' => 'Comment cannot be empty.']);
        exit;
    }

    if (mb_strlen($commentText) > MEMORY_COMMENT_MAX_LENGTH) {
        ApiResponse::validation(['comment_text' => 'Comment cannot exceed 2000 characters.']);
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

    if (!memory_comments_user_can_modify($comment)) {
        ApiResponse::forbidden('You cannot edit this comment.');
        exit;
    }

    $statement = $connection->prepare(
        "UPDATE memory_comments
         SET comment_text = :comment_text
         WHERE id = :id"
    );
    $statement->execute([
        'comment_text' => $commentText,
        'id' => $commentId,
    ]);

    Logger::info('Memory comment updated', ['comment_id' => $commentId]);
    ApiResponse::success([], 'Comment updated.');
} catch (Throwable $exception) {
    Logger::error('Memory comment update failed', ['error' => $exception->getMessage()]);
    ApiResponse::serverError('Unable to update comment.');
}
