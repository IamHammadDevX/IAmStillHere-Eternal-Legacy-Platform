<?php

return static function (PDO $connection): void {
    $connection->exec(
        "CREATE TABLE notifications (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            recipient_user_id INT UNSIGNED NOT NULL,
            actor_user_id INT UNSIGNED NULL,
            type ENUM('friend_request','friend_request_accepted','memory_comment','post_comment','journey_invitation','scheduled_event_status') NOT NULL,
            related_resource_type VARCHAR(50) NOT NULL,
            related_resource_id INT UNSIGNED NULL,
            message VARCHAR(255) NOT NULL,
            is_read TINYINT(1) NOT NULL DEFAULT 0,
            read_at TIMESTAMP NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (recipient_user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (actor_user_id) REFERENCES users(id) ON DELETE SET NULL,
            UNIQUE KEY unique_notification_action (recipient_user_id, actor_user_id, type, related_resource_type, related_resource_id),
            INDEX idx_notifications_recipient_read_created (recipient_user_id, is_read, created_at, id),
            INDEX idx_notifications_type_resource (type, related_resource_type, related_resource_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
};