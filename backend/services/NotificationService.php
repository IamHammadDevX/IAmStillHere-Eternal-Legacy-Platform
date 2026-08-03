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

    public static function linkFor(array $notification): string
    {
        $type = $notification['type'] ?? '';
        $resourceType = $notification['related_resource_type'] ?? '';
        $resourceId = (int) ($notification['related_resource_id'] ?? 0);
        $actorId = (int) ($notification['actor_user_id'] ?? 0);

        if ($type === self::TYPE_FRIEND_REQUEST && $actorId > 0) {
            return "profile.php?user_id={$actorId}#friends-tab";
        }

        if ($type === self::TYPE_FRIEND_REQUEST_ACCEPTED && $actorId > 0) {
            return "profile.php?user_id={$actorId}#friends-tab";
        }

        if ($resourceType === 'memory' && $resourceId > 0) {
            return "profile.php#memories-tab";
        }

        if ($resourceType === 'post' && $resourceId > 0) {
            return "profile.php#posts-tab";
        }

        return 'profile.php';
    }
}