<?php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../helpers/ApiResponse.php';
require_once __DIR__ . '/../helpers/RequestContext.php';
require_once __DIR__ . '/../helpers/Logger.php';
require_once __DIR__ . '/../helpers/SessionHelper.php';
require_once __DIR__ . '/../helpers/CsrfHelper.php';

const POST_BODY_MAX_LENGTH = 5000;
const POST_COMMENT_MAX_LENGTH = 2000;
const POST_CREATE_COOLDOWN = 3;
const POST_COMMENT_COOLDOWN = 2;

function posts_connection(): PDO
{
    $database = new Database();
    return $database->getConnection();
}

function posts_json_input(): array
{
    $data = json_decode(file_get_contents('php://input'), true);
    return is_array($data) ? $data : [];
}

function posts_require_active_account(PDO $connection): bool
{
    $userId = SessionHelper::getUserId();
    if ($userId === null) return false;

    $statement = $connection->prepare('SELECT status FROM users WHERE id = :id LIMIT 1');
    $statement->execute(['id' => $userId]);
    $user = $statement->fetch(PDO::FETCH_ASSOC);

    return $user && $user['status'] === 'active';
}

function posts_require_csrf(array $data = []): bool
{
    return CsrfHelper::validate(CsrfHelper::getTokenFromRequest($data));
}

function posts_is_family(PDO $connection, int $ownerId, int $viewerId): bool
{
    $statement = $connection->prepare(
        "SELECT id FROM family_members
         WHERE user_id = :owner_id
           AND family_member_id = :viewer_id
           AND status = 'active'
           AND approved = 1
         LIMIT 1"
    );
    $statement->execute(['owner_id' => $ownerId, 'viewer_id' => $viewerId]);
    return (bool) $statement->fetch();
}

function posts_can_view_profile(PDO $connection, int $profileUserId): bool
{
    $viewerId = SessionHelper::getUserId();
    if ($viewerId === null) return false;
    if ($viewerId === $profileUserId || SessionHelper::isAdmin()) return true;
    return posts_is_family($connection, $profileUserId, $viewerId);
}

function posts_can_view_post(PDO $connection, array $post): bool
{
    if (($post['status'] ?? '') !== 'active') return false;

    $viewerId = SessionHelper::getUserId();
    if ($viewerId === null) return false;

    $ownerId = (int) $post['user_id'];
    if ($viewerId === $ownerId || SessionHelper::isAdmin()) return true;

    if ($post['privacy_level'] === 'public') {
        return posts_can_view_profile($connection, $ownerId);
    }

    if ($post['privacy_level'] === 'family') {
        return posts_is_family($connection, $ownerId, $viewerId);
    }

    return false;
}

function posts_visible_privacy_condition(PDO $connection, int $profileUserId): string
{
    $viewerId = SessionHelper::getUserId();
    if ($viewerId === $profileUserId || SessionHelper::isAdmin()) {
        return "p.privacy_level IN ('public','family','private')";
    }

    if ($viewerId !== null && posts_is_family($connection, $profileUserId, $viewerId)) {
        return "p.privacy_level IN ('public','family')";
    }

    return "p.privacy_level = 'public'";
}

function posts_recent_create_allowed(string $sessionKey, int $cooldown): bool
{
    $now = time();
    $last = isset($_SESSION[$sessionKey]) ? (int) $_SESSION[$sessionKey] : 0;
    if (($now - $last) < $cooldown) return false;
    $_SESSION[$sessionKey] = $now;
    return true;
}

function posts_find_post(PDO $connection, int $postId): ?array
{
    $statement = $connection->prepare(
        "SELECT p.*, u.full_name AS author_name, u.profile_photo AS author_profile_photo
         FROM posts p
         INNER JOIN users u ON u.id = p.user_id
         WHERE p.id = :id
         LIMIT 1"
    );
    $statement->execute(['id' => $postId]);
    $post = $statement->fetch(PDO::FETCH_ASSOC);
    return $post ?: null;
}

function posts_format_post(PDO $connection, array $post): array
{
    $postId = (int) $post['id'];
    $viewerId = SessionHelper::getUserId();

    $mediaStmt = $connection->prepare('SELECT id, file_path, file_type, file_size, media_type FROM post_media WHERE post_id = :post_id ORDER BY id ASC');
    $mediaStmt->execute(['post_id' => $postId]);
    $media = $mediaStmt->fetchAll(PDO::FETCH_ASSOC);

    $countStmt = $connection->prepare('SELECT COUNT(*) AS total FROM post_comments WHERE post_id = :post_id AND deleted_at IS NULL');
    $countStmt->execute(['post_id' => $postId]);
    $commentCount = (int) ($countStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

    return [
        'id' => $postId,
        'user_id' => (int) $post['user_id'],
        'author_name' => $post['author_name'] ?? 'Unknown',
        'author_profile_photo' => $post['author_profile_photo'] ?? null,
        'body' => $post['body'],
        'privacy_level' => $post['privacy_level'],
        'created_at' => $post['created_at'],
        'updated_at' => $post['updated_at'],
        'media' => $media,
        'comment_count' => $commentCount,
        'can_edit' => $viewerId !== null && $viewerId === (int) $post['user_id'],
        'can_delete' => ($viewerId !== null && $viewerId === (int) $post['user_id']) || SessionHelper::isAdmin(),
        'can_comment' => posts_can_view_post($connection, $post),
    ];
}

function posts_find_comment(PDO $connection, int $commentId): ?array
{
    $statement = $connection->prepare(
        "SELECT c.*, p.user_id AS post_owner_id, p.status AS post_status, p.privacy_level
         FROM post_comments c
         INNER JOIN posts p ON p.id = c.post_id
         WHERE c.id = :id
         LIMIT 1"
    );
    $statement->execute(['id' => $commentId]);
    $comment = $statement->fetch(PDO::FETCH_ASSOC);
    return $comment ?: null;
}

function posts_user_can_edit_comment(array $comment): bool
{
    $viewerId = SessionHelper::getUserId();
    return $viewerId !== null && $comment['user_id'] !== null && ((int) $comment['user_id'] === $viewerId);
}

function posts_user_can_delete_comment(array $comment): bool
{
    $viewerId = SessionHelper::getUserId();
    if ($viewerId === null) return false;
    return ($comment['user_id'] !== null && (int) $comment['user_id'] === $viewerId)
        || (int) $comment['post_owner_id'] === $viewerId
        || SessionHelper::isAdmin();
}

function posts_media_public_path(array $media): string
{
    $folder = $media['media_type'] === 'video' ? 'videos' : 'photos';
    return 'http://localhost/IAmStillHere/data/uploads/' . $folder . '/' . rawurlencode($media['file_path']);
}
?>
