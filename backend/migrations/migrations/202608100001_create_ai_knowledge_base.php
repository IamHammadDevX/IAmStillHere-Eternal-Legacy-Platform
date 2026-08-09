<?php

return static function (PDO $connection): void {
    $connection->exec("CREATE TABLE IF NOT EXISTS ai_sources (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        resource_type VARCHAR(40) NOT NULL,
        resource_id INT UNSIGNED NOT NULL,
        title VARCHAR(255) NOT NULL,
        extracted_text MEDIUMTEXT NULL,
        source_date DATETIME NULL,
        ingestion_status ENUM('pending','processing','indexed','failed','disabled') NOT NULL DEFAULT 'pending',
        ai_enabled TINYINT(1) NOT NULL DEFAULT 1,
        consented_at DATETIME NULL,
        content_hash CHAR(64) NULL,
        last_error_code VARCHAR(80) NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        deleted_at TIMESTAMP NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_ai_source (user_id, resource_type, resource_id),
        INDEX idx_ai_sources_owner_status (user_id, ai_enabled, ingestion_status, deleted_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $connection->exec("CREATE TABLE IF NOT EXISTS ai_chunks (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        source_id INT UNSIGNED NOT NULL,
        user_id INT UNSIGNED NOT NULL,
        chunk_index INT UNSIGNED NOT NULL,
        chunk_text TEXT NOT NULL,
        chunk_hash CHAR(64) NOT NULL,
        embedding LONGTEXT NOT NULL,
        metadata_json TEXT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (source_id) REFERENCES ai_sources(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_ai_source_chunk (source_id, chunk_index),
        UNIQUE KEY unique_ai_chunk_hash (source_id, chunk_hash),
        INDEX idx_ai_chunks_owner (user_id, source_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $connection->exec("CREATE TABLE IF NOT EXISTS ai_ingestion_jobs (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        source_id INT UNSIGNED NOT NULL,
        status ENUM('pending','processing','completed','failed') NOT NULL DEFAULT 'pending',
        attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
        max_attempts TINYINT UNSIGNED NOT NULL DEFAULT 3,
        available_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        locked_at DATETIME NULL,
        completed_at DATETIME NULL,
        error_code VARCHAR(80) NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (source_id) REFERENCES ai_sources(id) ON DELETE CASCADE,
        INDEX idx_ai_jobs_queue (status, available_at, id),
        INDEX idx_ai_jobs_source (source_id, status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
};
