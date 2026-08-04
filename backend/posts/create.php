<?php
require_once __DIR__ . '/_post_helpers.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { ApiResponse::send(false, [], 'Method not allowed.', [], 405); exit; }
    $connection = posts_connection();
    if (!SessionHelper::isAuthenticated()) { ApiResponse::unauthorized(); exit; }
    if (!posts_require_active_account($connection)) { ApiResponse::forbidden('Active account required.'); exit; }
    if (!posts_require_csrf($_POST)) { ApiResponse::forbidden('Invalid CSRF token.'); exit; }
    if (!posts_recent_create_allowed('last_post_created_at', POST_CREATE_COOLDOWN)) { ApiResponse::send(false, [], 'Please wait before posting again.', [], 429); exit; }

    $userId = SessionHelper::getUserId();
    $body = trim((string) ($_POST['body'] ?? ''));
    $privacy = (string) ($_POST['privacy_level'] ?? 'public');
    if (!in_array($privacy, ['public', 'family', 'friends', 'specific_people', 'private', 'release_date', 'release_event'], true)) $privacy = 'public';
    $legacyPrivacy = in_array($privacy, ['public','family','private'], true) ? $privacy : 'private';
    if ($body === '' && empty($_FILES['media']['name'])) { ApiResponse::validation(['body' => 'Post text or media is required.']); exit; }
    if (mb_strlen($body) > POST_BODY_MAX_LENGTH) { ApiResponse::validation(['body' => 'Post cannot exceed 5000 characters.']); exit; }

    $connection->beginTransaction();
    $stmt = $connection->prepare('INSERT INTO posts (user_id, body, privacy_level) VALUES (:user_id, :body, :privacy_level)');
    $stmt->execute(['user_id' => $userId, 'body' => $body, 'privacy_level' => $legacyPrivacy]);
    $postId = (int) $connection->lastInsertId();

    if (!empty($_FILES['media']['name'])) {
        $file = $_FILES['media'];
        $fileType = $file['type'] ?? '';
        $fileSize = (int) ($file['size'] ?? 0);
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $isImage = in_array($fileType, ALLOWED_IMAGE_TYPES, true) || in_array($ext, ['jpg','jpeg','png','gif','webp','bmp','svg','tiff'], true);
        $isVideo = in_array($fileType, ALLOWED_VIDEO_TYPES, true) || in_array($ext, ['mp4','avi','mov','mkv','webm','mpeg','mpg','3gp','flv','wmv'], true);
        if (!$isImage && !$isVideo) { throw new RuntimeException('Only image or video media is allowed.'); }
        if ($fileSize > MAX_FILE_SIZE) { throw new RuntimeException('File size exceeds maximum allowed.'); }

        $mediaType = $isVideo ? 'video' : 'image';
        $uploadDir = UPLOAD_PATH . '/' . ($isVideo ? 'videos' : 'photos') . '/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0775, true);
        $filename = uniqid('post_') . '.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) { throw new RuntimeException('Media upload failed.'); }

        $mediaStmt = $connection->prepare('INSERT INTO post_media (post_id, file_path, file_type, file_size, media_type) VALUES (:post_id, :file_path, :file_type, :file_size, :media_type)');
        $mediaStmt->execute(['post_id' => $postId, 'file_path' => $filename, 'file_type' => $fileType, 'file_size' => $fileSize, 'media_type' => $mediaType]);
    }

    $connection->commit();
    $post = posts_find_post($connection, $postId);
    ApiResponse::success(['post' => posts_format_post($connection, $post)], 'Post created.', 201);
} catch (Throwable $e) {
    if (isset($connection) && $connection->inTransaction()) $connection->rollBack();
    Logger::error('Post create failed', ['error' => $e->getMessage()]);
    ApiResponse::serverError($e instanceof RuntimeException ? $e->getMessage() : 'Unable to create post.');
}
?>
