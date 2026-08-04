<?php

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../helpers/ApiResponse.php';
require_once __DIR__ . '/../../helpers/RequestContext.php';
require_once __DIR__ . '/../../helpers/Logger.php';
require_once __DIR__ . '/../../helpers/SessionHelper.php';
require_once __DIR__ . '/../../helpers/CsrfHelper.php';
require_once __DIR__ . '/../../services/PrivacyService.php';

function folder_connection(): PDO
{
    return (new Database())->getConnection();
}

function folder_input(): array
{
    $data = json_decode(file_get_contents('php://input'), true);
    return is_array($data) ? $data : $_POST;
}

function folder_require_auth(): int
{
    $id = SessionHelper::getUserId();
    if ($id === null) {
        ApiResponse::unauthorized();
        exit;
    }
    return $id;
}

function folder_require_csrf(array $data): void
{
    if (!CsrfHelper::validate(CsrfHelper::getTokenFromRequest($data))) {
        ApiResponse::forbidden('Invalid CSRF token');
        exit;
    }
}

function folder_name($value): string
{
    return trim((string) $value);
}

function folder_privacy($value): ?string
{
    $value = (string) $value;
    return in_array($value, ['public', 'family', 'friends', 'private'], true) ? $value : null;
}

function folder_find(PDO $db, int $id, int $ownerId): ?array
{
    $stmt = $db->prepare('SELECT * FROM memory_folders WHERE id = :id AND user_id = :user_id AND deleted_at IS NULL LIMIT 1');
    $stmt->execute(['id' => $id, 'user_id' => $ownerId]);
    $folder = $stmt->fetch(PDO::FETCH_ASSOC);
    return $folder ?: null;
}

function folder_is_descendant(PDO $db, int $candidateParent, int $folderId, int $ownerId): bool
{
    $seen = [];
    $current = $candidateParent;
    while ($current > 0 && !isset($seen[$current])) {
        if ($current === $folderId) return true;
        $seen[$current] = true;
        $stmt = $db->prepare('SELECT parent_folder_id FROM memory_folders WHERE id = :id AND user_id = :user_id AND deleted_at IS NULL');
        $stmt->execute(['id' => $current, 'user_id' => $ownerId]);
        $parent = $stmt->fetchColumn();
        $current = $parent === false || $parent === null ? 0 : (int) $parent;
    }
    return false;
}

function folder_can_view(PDO $db, array $folder, ?int $viewerId): bool
{
    if ($folder['privacy_level'] === 'public') return true;
    if ($viewerId === null) return false;
    if ((int) $folder['user_id'] === $viewerId || SessionHelper::isAdmin()) return true;
    if ($folder['privacy_level'] === 'family') {
        $stmt = $db->prepare("SELECT id FROM family_members WHERE user_id = :owner AND family_member_id = :viewer AND status = 'active' LIMIT 1");
        $stmt->execute(['owner' => $folder['user_id'], 'viewer' => $viewerId]);
        return (bool) $stmt->fetchColumn();
    }
    if ($folder['privacy_level'] === 'friends') {
        $stmt = $db->prepare("SELECT id FROM friendships WHERE ((user_id = :owner AND friend_id = :viewer) OR (user_id = :viewer2 AND friend_id = :owner2)) AND status = 'accepted' LIMIT 1");
        $stmt->execute(['owner' => $folder['user_id'], 'viewer' => $viewerId, 'viewer2' => $viewerId, 'owner2' => $folder['user_id']]);
        return (bool) $stmt->fetchColumn();
    }
    return false;
}
