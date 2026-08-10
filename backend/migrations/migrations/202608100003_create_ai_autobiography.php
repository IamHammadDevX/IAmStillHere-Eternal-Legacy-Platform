<?php

return static function (PDO $connection): void {
    $connection->exec("CREATE TABLE IF NOT EXISTS ai_autobiographies (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        owner_id INT UNSIGNED NOT NULL,
        title VARCHAR(180) NOT NULL DEFAULT 'My Life Story',
        status ENUM('draft','published','unpublished','deleted') NOT NULL DEFAULT 'draft',
        model_used VARCHAR(100) NULL,
        source_references_json MEDIUMTEXT NULL,
        prompt_tokens INT UNSIGNED NULL,
        completion_tokens INT UNSIGNED NULL,
        total_tokens INT UNSIGNED NULL,
        published_at TIMESTAMP NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        deleted_at TIMESTAMP NULL,
        FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_ai_autobiographies_owner (owner_id, status, updated_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $connection->exec("CREATE TABLE IF NOT EXISTS ai_autobiography_sections (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        autobiography_id BIGINT UNSIGNED NOT NULL,
        owner_id INT UNSIGNED NOT NULL,
        section_key VARCHAR(80) NOT NULL,
        section_title VARCHAR(120) NOT NULL,
        content MEDIUMTEXT NOT NULL,
        source_references_json MEDIUMTEXT NULL,
        sort_order INT UNSIGNED NOT NULL DEFAULT 0,
        manually_edited TINYINT(1) NOT NULL DEFAULT 0,
        model_used VARCHAR(100) NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (autobiography_id) REFERENCES ai_autobiographies(id) ON DELETE CASCADE,
        FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY uniq_ai_autobio_section (autobiography_id, section_key),
        INDEX idx_ai_autobio_sections_owner (owner_id, sort_order)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
};
