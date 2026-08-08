<?php
return static function (PDO $connection): void {
    $connection->exec("CREATE TABLE automation_rules (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        owner_id INT UNSIGNED NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT NULL,
        trigger_type ENUM('specific_datetime','birthday','anniversary','custom_recurring','linked_milestone_event') NOT NULL,
        trigger_datetime DATETIME NULL,
        recurring_month TINYINT UNSIGNED NULL,
        recurring_day TINYINT UNSIGNED NULL,
        linked_resource_type ENUM('milestone','event') NULL,
        linked_resource_id INT UNSIGNED NULL,
        timezone VARCHAR(64) NOT NULL DEFAULT 'UTC',
        next_run_at DATETIME NULL,
        status ENUM('draft','scheduled','processing','completed','failed','cancelled') NOT NULL DEFAULT 'draft',
        retry_count TINYINT UNSIGNED NOT NULL DEFAULT 0,
        max_retries TINYINT UNSIGNED NOT NULL DEFAULT 3,
        last_error VARCHAR(500) NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        deleted_at TIMESTAMP NULL,
        FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_automation_due (status, next_run_at),
        INDEX idx_automation_owner (owner_id, status, deleted_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $connection->exec("CREATE TABLE automation_actions (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        rule_id INT UNSIGNED NOT NULL,
        action_type ENUM('email','wall_post','notification') NOT NULL,
        payload JSON NULL,
        status ENUM('active','disabled') NOT NULL DEFAULT 'active',
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (rule_id) REFERENCES automation_rules(id) ON DELETE CASCADE,
        INDEX idx_automation_actions_rule (rule_id, status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $connection->exec("CREATE TABLE automation_runs (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        rule_id INT UNSIGNED NOT NULL,
        action_id INT UNSIGNED NULL,
        idempotency_key VARCHAR(191) NOT NULL,
        status ENUM('processing','completed','failed','skipped') NOT NULL DEFAULT 'processing',
        error_message VARCHAR(500) NULL,
        started_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        completed_at TIMESTAMP NULL,
        FOREIGN KEY (rule_id) REFERENCES automation_rules(id) ON DELETE CASCADE,
        FOREIGN KEY (action_id) REFERENCES automation_actions(id) ON DELETE SET NULL,
        UNIQUE KEY unique_automation_run (idempotency_key),
        INDEX idx_automation_runs_rule (rule_id, started_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
};
