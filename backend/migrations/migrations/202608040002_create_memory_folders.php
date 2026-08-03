<?php

return static function (PDO $connection): void {
    $connection->exec("CREATE TABLE IF NOT EXISTS memory_folders (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        parent_folder_id INT UNSIGNED NULL,
        name VARCHAR(150) NOT NULL,
        description TEXT NULL,
        cover_image VARCHAR(255) NULL,
        privacy_level ENUM('public','family','friends','private') NOT NULL DEFAULT 'private',
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        deleted_at TIMESTAMP NULL,
        CONSTRAINT fk_memory_folders_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        CONSTRAINT fk_memory_folders_parent FOREIGN KEY (parent_folder_id) REFERENCES memory_folders(id) ON DELETE SET NULL,
        INDEX idx_memory_folders_owner (user_id, deleted_at),
        INDEX idx_memory_folders_parent (parent_folder_id, deleted_at),
        INDEX idx_memory_folders_privacy (privacy_level, deleted_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $connection->exec("ALTER TABLE memories
        ADD COLUMN IF NOT EXISTS folder_id INT UNSIGNED NULL,
        ADD COLUMN IF NOT EXISTS privacy_override TINYINT(1) NOT NULL DEFAULT 0,
        ADD INDEX IF NOT EXISTS idx_memories_folder (folder_id, status),
        ADD CONSTRAINT fk_memories_folder FOREIGN KEY (folder_id) REFERENCES memory_folders(id) ON DELETE SET NULL");
};
