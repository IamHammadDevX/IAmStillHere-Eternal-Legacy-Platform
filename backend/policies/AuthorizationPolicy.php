<?php

require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../middleware/RoleMiddleware.php';

class AuthorizationPolicy
{
    public static function isOwner(int $resourceOwnerId): bool
    {
        return AuthMiddleware::userId() === $resourceOwnerId;
    }

    public static function canViewPublicContent(string $privacyLevel): bool
    {
        return $privacyLevel === PRIVACY_PUBLIC;
    }

    public static function canViewPrivateContent(int $resourceOwnerId): bool
    {
        return self::isOwner($resourceOwnerId) || RoleMiddleware::requireAdmin();
    }

    public static function canViewFamilyContent(PDO $connection, int $resourceOwnerId): bool
    {
        $viewerId = AuthMiddleware::userId();

        if ($viewerId === null) {
            return false;
        }

        if (self::isOwner($resourceOwnerId) || RoleMiddleware::requireAdmin()) {
            return true;
        }

        $statement = $connection->prepare(
            "SELECT id FROM family_members
             WHERE user_id = :owner_id
             AND family_member_id = :viewer_id
             AND status = 'active'
             LIMIT 1"
        );
        $statement->execute([
            'owner_id' => $resourceOwnerId,
            'viewer_id' => $viewerId,
        ]);

        return (bool) $statement->fetch();
    }

    public static function canViewByPrivacy(PDO $connection, int $resourceOwnerId, string $privacyLevel): bool
    {
        if ($privacyLevel === PRIVACY_PUBLIC) {
            return true;
        }

        if ($privacyLevel === PRIVACY_PRIVATE) {
            return self::canViewPrivateContent($resourceOwnerId);
        }

        if ($privacyLevel === PRIVACY_FAMILY) {
            return self::canViewFamilyContent($connection, $resourceOwnerId);
        }

        return false;
    }

    public static function requireActiveAccount(PDO $connection): bool
    {
        $userId = AuthMiddleware::userId();

        if ($userId === null) {
            return false;
        }

        $statement = $connection->prepare("SELECT status FROM users WHERE id = :id LIMIT 1");
        $statement->execute(['id' => $userId]);
        $user = $statement->fetch(PDO::FETCH_ASSOC);

        return $user && $user['status'] === 'active';
    }
}
