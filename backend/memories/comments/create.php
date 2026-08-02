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

    $memoryId = (int) ($data['memory_id'] ?? 0);
    $commentText = trim((string) ($data['comment_text'] ?? ''));

    if ($memoryId <= 0) {
        ApiResponse::validation(['memory_id' => 'A valid memory_id is required.']);
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

    $memory = memory_comments_find_memory($connection, $memoryId);
    if (!$memory || !memory_comments_can_view_memory($connection, $memory)) {
        ApiResponse::notFound('Memory not found or not accessible.');
        exit;
    }

    if (!memory_comments_recent_create_allowed()) {
        ApiResponse::send(false, [], 'Please wait before posting another comment.', [], 429);
        exit;
    }

    $statement = $connection->prepare(
        "INSERT INTO memory_comments (memory_id, user_id, comment_text)
         VALUES (:memory_id, :user_id, :comment_text)"
    );
    $statement->execute([
        'memory_id' => $memoryId,
        'user_id' => SessionHelper::getUserId(),
        'comment_text' => $commentText,
    ]);

    Logger::info('Memory comment created', [
        'memory_id' => $memoryId,
        'comment_id' => $connection->lastInsertId(),
    ]);

    ApiResponse::success([
        'comment_id' => (int) $connection->lastInsertId(),
    ], 'Comment posted.', 201);
} catch (Throwable $exception) {
    Logger::error('Memory comment create failed', ['error' => $exception->getMessage()]);
    ApiResponse::serverError('Unable to post comment.');
}
