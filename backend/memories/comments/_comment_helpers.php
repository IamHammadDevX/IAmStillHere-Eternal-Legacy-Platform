<?php

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../helpers/ApiResponse.php';
require_once __DIR__ . '/../../helpers/RequestContext.php';
require_once __DIR__ . '/../../helpers/Logger.php';
require_once __DIR__ . '/../../helpers/SessionHelper.php';
require_once __DIR__ . '/../../helpers/CsrfHelper.php';

const MEMORY_COMMENT_MAX_LENGTH = 2000;
const MEMORY_COMMENT_CREATE_COOLDOWN = 2;

function memory_comments_connection(): PDO
{
    $database = new Database();
    return $database->getConnection();
}

function memory_comments_json_input(): array
{
    $data = json_decode(file_get_contents('php://input'), true);
    return is_array($data) ? $data : [];
}

function memory_comments_find_memory(PDO $connection, int $memoryId): ?array
{
    $statement = $connection->prepare(
        "SELECT id, user_id, privacy_level, status
         FROM memories
         WHERE id = :id
         LIMIT 1"
    );
    $statement->execute(['id' => $memoryId]);
    $memory = $statement->fetch(PDO::FETCH_ASSOC);

    return $memory ?: null;
}

function memory_comments_can_view_memory(PDO $connection, array $memory): bool
{
    if ($memory['status'] !== 'active') {
        return false;
    }

    if ($memory['privacy_level'] === 'public') {
        return true;
    }

    $viewerId = SessionHelper::getUserId();
    if ($viewerId === null) {
        return false;
    }

    if ((int) $memory['user_id'] === $viewerId || SessionHelper::isAdmin()) {
        return true;
    }

    if ($memory['privacy_level'] === 'family') {
        $statement = $connection->prepare(
            "SELECT id
             FROM family_members
             WHERE user_id = :owner_id
             AND family_member_id = :viewer_id
             AND status = 'active'
             LIMIT 1"
        );
        $statement->execute([
            'owner_id' => (int) $memory['user_id'],
            'viewer_id' => $viewerId,
        ]);

        return (bool) $statement->fetch();
    }

    return false;
}

function memory_comments_require_active_account(PDO $connection): bool
{
    $userId = SessionHelper::getUserId();
    if ($userId === null) {
        return false;
    }

    $statement = $connection->prepare("SELECT status FROM users WHERE id = :id LIMIT 1");
    $statement->execute(['id' => $userId]);
    $user = $statement->fetch(PDO::FETCH_ASSOC);

    return $user && $user['status'] === 'active';
}

function memory_comments_require_csrf(array $data): bool
{
    return CsrfHelper::validate(CsrfHelper::getTokenFromRequest($data));
}

function memory_comments_recent_create_allowed(): bool
{
    $now = time();
    $last = isset($_SESSION['last_memory_comment_at']) ? (int) $_SESSION['last_memory_comment_at'] : 0;

    if (($now - $last) < MEMORY_COMMENT_CREATE_COOLDOWN) {
        return false;
    }

    $_SESSION['last_memory_comment_at'] = $now;
    return true;
}

function memory_comments_find_comment(PDO $connection, int $commentId): ?array
{
    $statement = $connection->prepare(
        "SELECT c.id, c.memory_id, c.user_id, c.comment_text, c.deleted_at, m.user_id AS memory_owner_id
         FROM memory_comments c
         INNER JOIN memories m ON m.id = c.memory_id
         WHERE c.id = :id
         LIMIT 1"
    );
    $statement->execute(['id' => $commentId]);
    $comment = $statement->fetch(PDO::FETCH_ASSOC);

    return $comment ?: null;
}

function memory_comments_user_can_modify(array $comment): bool
{
    $userId = SessionHelper::getUserId();
    if ($userId === null || $comment['user_id'] === null) {
        return false;
    }

    return ((int) $comment['user_id'] === $userId) || SessionHelper::isAdmin();
}

function memory_comments_user_can_delete(array $comment): bool
{
    $userId = SessionHelper::getUserId();
    if ($userId === null) {
        return false;
    }

    return ((int) $comment['user_id'] === $userId)
        || ((int) $comment['memory_owner_id'] === $userId)
        || SessionHelper::isAdmin();
}
