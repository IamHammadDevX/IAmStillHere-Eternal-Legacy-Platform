<?php
return static function (PDO $connection): void {
    $connection->exec("CREATE TABLE IF NOT EXISTS ai_message_templates (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        owner_id INT UNSIGNED NOT NULL,
        event_type ENUM('birthday','graduation','wedding','anniversary','new_job','new_baby','custom') NOT NULL,
        title VARCHAR(180) NOT NULL,
        default_tone VARCHAR(80) NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        deleted_at TIMESTAMP NULL,
        FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_ai_msg_templates_owner (owner_id, event_type, deleted_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $connection->exec("CREATE TABLE IF NOT EXISTS ai_personalized_messages (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        owner_id INT UNSIGNED NOT NULL,
        recipient_user_id INT UNSIGNED NULL,
        recipient_email VARCHAR(255) NULL,
        recipient_name VARCHAR(255) NULL,
        relationship VARCHAR(100) NULL,
        event_type ENUM('birthday','graduation','wedding','anniversary','new_job','new_baby','custom') NOT NULL,
        trigger_at DATETIME NULL,
        delivery_method ENUM('notification','email','wall_post') NOT NULL DEFAULT 'notification',
        tone VARCHAR(80) NULL,
        instructions TEXT NULL,
        generated_message MEDIUMTEXT NULL,
        edited_message MEDIUMTEXT NULL,
        status ENUM('draft','approved','scheduled','cancelled','sent','failed') NOT NULL DEFAULT 'draft',
        automation_rule_id INT UNSIGNED NULL,
        model_used VARCHAR(100) NULL,
        prompt_tokens INT UNSIGNED NULL,
        completion_tokens INT UNSIGNED NULL,
        total_tokens INT UNSIGNED NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        deleted_at TIMESTAMP NULL,
        FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (recipient_user_id) REFERENCES users(id) ON DELETE SET NULL,
        FOREIGN KEY (automation_rule_id) REFERENCES automation_rules(id) ON DELETE SET NULL,
        INDEX idx_ai_personalized_owner (owner_id, status, trigger_at, deleted_at),
        INDEX idx_ai_personalized_recipient (recipient_user_id, status, trigger_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
};
