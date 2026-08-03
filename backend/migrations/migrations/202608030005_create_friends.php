<?php

return static function (PDO $connection): void {
    $connection->exec(
        "CREATE TABLE friend_requests (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            sender_id INT UNSIGNED NOT NULL,
            receiver_id INT UNSIGNED NOT NULL,
            status ENUM('pending','accepted','rejected','cancelled','blocked') NOT NULL DEFAULT 'pending',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            responded_at TIMESTAMP NULL,
            FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_friend_requests_sender (sender_id, status, created_at),
            INDEX idx_friend_requests_receiver (receiver_id, status, created_at),
            INDEX idx_friend_requests_pair (sender_id, receiver_id, status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $connection->exec(
        "CREATE TABLE friendships (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            friend_id INT UNSIGNED NOT NULL,
            status ENUM('accepted','removed','blocked') NOT NULL DEFAULT 'accepted',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (friend_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY unique_friend_pair (user_id, friend_id),
            INDEX idx_friendships_user_status (user_id, status),
            INDEX idx_friendships_friend_status (friend_id, status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
};
