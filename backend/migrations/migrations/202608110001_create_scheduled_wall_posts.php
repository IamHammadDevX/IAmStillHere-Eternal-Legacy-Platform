<?php
return static function (PDO $connection): void {
    $connection->exec("CREATE TABLE IF NOT EXISTS scheduled_wall_posts (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        owner_id INT UNSIGNED NOT NULL,
        body MEDIUMTEXT NOT NULL,
        privacy_level ENUM('public','family','friends','specific_people','private','release_date','release_event') NOT NULL DEFAULT 'private',
        trigger_type ENUM('specific_datetime','birthday','anniversary','custom_recurring','linked_milestone_event') NOT NULL DEFAULT 'specific_datetime',
        trigger_at DATETIME NULL,
        linked_resource_type ENUM('milestone','event') NULL,
        linked_resource_id INT UNSIGNED NULL,
        media_file_path VARCHAR(255) NULL,
        media_file_type VARCHAR(150) NULL,
        media_file_size BIGINT UNSIGNED NULL,
        media_type ENUM('image','video') NULL,
        status ENUM('draft','scheduled','processing','published','failed','cancelled') NOT NULL DEFAULT 'draft',
        automation_rule_id INT UNSIGNED NULL,
        published_post_id INT UNSIGNED NULL,
        last_error VARCHAR(500) NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        deleted_at TIMESTAMP NULL,
        FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (automation_rule_id) REFERENCES automation_rules(id) ON DELETE SET NULL,
        FOREIGN KEY (published_post_id) REFERENCES posts(id) ON DELETE SET NULL,
        INDEX idx_scheduled_wall_owner (owner_id, status, trigger_at, deleted_at),
        INDEX idx_scheduled_wall_automation (automation_rule_id, status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
};
