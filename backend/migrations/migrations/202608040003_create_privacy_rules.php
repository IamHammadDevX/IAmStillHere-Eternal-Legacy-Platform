<?php

return static function (PDO $connection): void {
    $connection->exec("CREATE TABLE IF NOT EXISTS privacy_rules (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        resource_type VARCHAR(50) NOT NULL,
        resource_id INT UNSIGNED NOT NULL,
        visibility_type ENUM('public','family','friends','specific_people','private','release_date','release_event') NOT NULL,
        release_at DATETIME NULL,
        release_event_id INT UNSIGNED NULL,
        inherit_from_folder TINYINT(1) NOT NULL DEFAULT 0,
        created_by INT UNSIGNED NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_privacy_resource (resource_type, resource_id),
        INDEX idx_privacy_created_by (created_by),
        INDEX idx_privacy_release_event (release_event_id),
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (release_event_id) REFERENCES scheduled_events(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $connection->exec("CREATE TABLE IF NOT EXISTS privacy_rule_users (
        privacy_rule_id INT UNSIGNED NOT NULL,
        user_id INT UNSIGNED NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (privacy_rule_id, user_id),
        FOREIGN KEY (privacy_rule_id) REFERENCES privacy_rules(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_privacy_rule_users_user (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
};
