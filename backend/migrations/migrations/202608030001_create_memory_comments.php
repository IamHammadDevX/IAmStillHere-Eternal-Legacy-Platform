<?php

return static function (PDO $connection): void {
    $connection->exec(
        "CREATE TABLE memory_comments (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            memory_id INT UNSIGNED NOT NULL,
            user_id INT UNSIGNED NULL,
            comment_text TEXT NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            deleted_at DATETIME NULL,
            FOREIGN KEY (memory_id) REFERENCES memories(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
            INDEX idx_memory_comments_memory_id (memory_id),
            INDEX idx_memory_comments_user_id (user_id),
            INDEX idx_memory_comments_list (memory_id, deleted_at, created_at, id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
};
