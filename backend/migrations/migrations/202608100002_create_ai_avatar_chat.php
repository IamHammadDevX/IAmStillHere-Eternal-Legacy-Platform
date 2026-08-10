<?php

return static function (PDO $connection): void {
    $connection->exec("CREATE TABLE IF NOT EXISTS ai_conversations (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        owner_id INT UNSIGNED NOT NULL,
        viewer_id INT UNSIGNED NOT NULL,
        title VARCHAR(180) NOT NULL DEFAULT 'AI Avatar Chat',
        status ENUM('active','deleted') NOT NULL DEFAULT 'active',
        model_used VARCHAR(100) NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        deleted_at TIMESTAMP NULL,
        FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (viewer_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_ai_conversations_viewer (viewer_id, owner_id, status, updated_at),
        INDEX idx_ai_conversations_owner (owner_id, status, updated_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $connection->exec("CREATE TABLE IF NOT EXISTS ai_messages (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        conversation_id BIGINT UNSIGNED NOT NULL,
        owner_id INT UNSIGNED NOT NULL,
        viewer_id INT UNSIGNED NOT NULL,
        role ENUM('user','assistant') NOT NULL,
        message_text MEDIUMTEXT NOT NULL,
        model_used VARCHAR(100) NULL,
        source_references_json TEXT NULL,
        prompt_tokens INT UNSIGNED NULL,
        completion_tokens INT UNSIGNED NULL,
        total_tokens INT UNSIGNED NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (conversation_id) REFERENCES ai_conversations(id) ON DELETE CASCADE,
        FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (viewer_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_ai_messages_conversation (conversation_id, id),
        INDEX idx_ai_messages_viewer (viewer_id, owner_id, created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
};
