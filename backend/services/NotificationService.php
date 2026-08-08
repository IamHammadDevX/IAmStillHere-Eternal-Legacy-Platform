<?php

class NotificationService
{
    public const TYPE_FRIEND_REQUEST = 'friend_request';
    public const TYPE_FRIEND_REQUEST_ACCEPTED = 'friend_request_accepted';
    public const TYPE_MEMORY_COMMENT = 'memory_comment';
    public const TYPE_POST_COMMENT = 'post_comment';
    public const TYPE_JOURNEY_INVITATION = 'journey_invitation';
    public const TYPE_SCHEDULED_EVENT_STATUS = 'scheduled_event_status';

    private const ALLOWED_TYPES = [
        self::TYPE_FRIEND_REQUEST,
        self::TYPE_FRIEND_REQUEST_ACCEPTED,
        self::TYPE_MEMORY_COMMENT,
        self::TYPE_POST_COMMENT,
        self::TYPE_JOURNEY_INVITATION,
        self::TYPE_SCHEDULED_EVENT_STATUS,
    ];

    public static function createOnce(
        PDO $connection,
        int $recipientUserId,
        ?int $actorUserId,
        string $type,
        string $relatedResourceType,
        ?int $relatedResourceId,
        string $message
    ): void {
        if ($recipientUserId <= 0 || !in_array($type, self::ALLOWED_TYPES, true)) {
            return;
        }

        if ($actorUserId !== null && $actorUserId === $recipientUserId) {
            return;
        }

        $safeMessage = trim($message);
        if ($safeMessage === '') {
            return;
        }
        $safeMessage = mb_substr($safeMessage, 0, 255);
        $relatedResourceType = mb_substr(trim($relatedResourceType), 0, 50);

        $statement = $connection->prepare(
            "INSERT IGNORE INTO notifications
             (recipient_user_id, actor_user_id, type, related_resource_type, related_resource_id, message)
             VALUES (:recipient_user_id, :actor_user_id, :type, :related_resource_type, :related_resource_id, :message)"
        );
        $statement->execute([
            'recipient_user_id' => $recipientUserId,
            'actor_user_id' => $actorUserId,
            'type' => $type,
            'related_resource_type' => $relatedResourceType,
            'related_resource_id' => $relatedResourceId,
            'message' => $safeMessage,
        ]);
    }

    public static function linkFor(array $notification, ?PDO $connection = null): string
    {
        $type = $notification['type'] ?? '';
        $resourceType = $notification['related_resource_type'] ?? '';
        $resourceId = (int) ($notification['related_resource_id'] ?? 0);
        $actorId = (int) ($notification['actor_user_id'] ?? 0);

        if (($type === self::TYPE_FRIEND_REQUEST || $type === self::TYPE_FRIEND_REQUEST_ACCEPTED) && $actorId > 0) {
            return "profile.php?user_id={$actorId}#friends-tab";
        }

        if ($connection && $resourceId > 0) {
            $ownerId = self::ownerIdForResource($connection, $resourceType, $resourceId);
            if ($ownerId > 0) {
                if (in_array($resourceType, ['memory', 'memory_comment'], true)) {
                    return "profile.php?user_id={$ownerId}#memories-tab";
                }
                if (in_array($resourceType, ['post', 'post_comment'], true)) {
                    return "profile.php?user_id={$ownerId}#posts-tab";
                }
                if ($resourceType === 'journey') {
                    return "profile.php?user_id={$ownerId}#journeys-tab";
                }
            }
        }

        if (in_array($resourceType, ['memory', 'memory_comment'], true)) return 'profile.php#memories-tab';
        if (in_array($resourceType, ['post', 'post_comment'], true)) return 'profile.php#posts-tab';
        if ($resourceType === 'journey') return 'profile.php#journeys-tab';

        return 'profile.php';
    }

    private static function ownerIdForResource(PDO $connection, string $resourceType, int $resourceId): int
    {
        try {
            if ($resourceType === 'memory') {
                $statement = $connection->prepare('SELECT user_id FROM memories WHERE id = :id LIMIT 1');
                $statement->execute(['id' => $resourceId]);
                return (int) $statement->fetchColumn();
            }
            if ($resourceType === 'memory_comment') {
                $statement = $connection->prepare('SELECT m.user_id FROM memory_comments c INNER JOIN memories m ON m.id = c.memory_id WHERE c.id = :id LIMIT 1');
                $statement->execute(['id' => $resourceId]);
                return (int) $statement->fetchColumn();
            }
            if ($resourceType === 'post') {
                $statement = $connection->prepare('SELECT user_id FROM posts WHERE id = :id LIMIT 1');
                $statement->execute(['id' => $resourceId]);
                return (int) $statement->fetchColumn();
            }
            if ($resourceType === 'post_comment') {
                $statement = $connection->prepare('SELECT p.user_id FROM post_comments c INNER JOIN posts p ON p.id = c.post_id WHERE c.id = :id LIMIT 1');
                $statement->execute(['id' => $resourceId]);
                return (int) $statement->fetchColumn();
            }
            if ($resourceType === 'journey') {
                $statement = $connection->prepare('SELECT owner_id FROM journeys WHERE id = :id LIMIT 1');
                $statement->execute(['id' => $resourceId]);
                return (int) $statement->fetchColumn();
            }
        } catch (Throwable $exception) {
            return 0;
        }
        return 0;
    }
}
