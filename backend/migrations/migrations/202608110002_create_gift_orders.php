<?php
return static function (PDO $connection): void {
    $connection->exec("CREATE TABLE IF NOT EXISTS gift_orders (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        owner_id INT UNSIGNED NOT NULL,
        recipient_user_id INT UNSIGNED NULL,
        recipient_name VARCHAR(255) NULL,
        recipient_email VARCHAR(255) NULL,
        recipient_phone VARCHAR(50) NULL,
        recipient_address TEXT NULL,
        occasion ENUM('birthday','anniversary','graduation','wedding','new_job','new_baby','custom') NOT NULL DEFAULT 'custom',
        gift_external_id VARCHAR(191) NOT NULL,
        gift_name VARCHAR(255) NOT NULL,
        gift_price DECIMAL(10,2) NULL,
        gift_currency VARCHAR(10) NULL,
        message_id BIGINT UNSIGNED NULL,
        message_text TEXT NULL,
        delivery_at DATETIME NOT NULL,
        status ENUM('draft','pending_payment','scheduled','processing','placed','delivered','failed','cancelled') NOT NULL DEFAULT 'scheduled',
        external_order_id VARCHAR(191) NULL,
        idempotency_key VARCHAR(191) NOT NULL,
        provider_payload JSON NULL,
        last_error VARCHAR(500) NULL,
        automation_rule_id INT UNSIGNED NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        deleted_at TIMESTAMP NULL,
        FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (recipient_user_id) REFERENCES users(id) ON DELETE SET NULL,
        FOREIGN KEY (message_id) REFERENCES ai_personalized_messages(id) ON DELETE SET NULL,
        FOREIGN KEY (automation_rule_id) REFERENCES automation_rules(id) ON DELETE SET NULL,
        UNIQUE KEY unique_gift_idempotency (idempotency_key),
        UNIQUE KEY unique_gift_external_order (external_order_id),
        INDEX idx_gift_owner_status (owner_id,status,deleted_at),
        INDEX idx_gift_delivery (status,delivery_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $connection->exec("CREATE TABLE IF NOT EXISTS gift_order_events (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        gift_order_id INT UNSIGNED NOT NULL,
        event_type VARCHAR(100) NOT NULL,
        status VARCHAR(100) NULL,
        payload JSON NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (gift_order_id) REFERENCES gift_orders(id) ON DELETE CASCADE,
        INDEX idx_gift_events_order (gift_order_id, created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
};

