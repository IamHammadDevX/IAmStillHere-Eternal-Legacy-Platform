<?php
return static function (PDO $connection): void {
    $connection->exec("CREATE TABLE IF NOT EXISTS vault_folders (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        owner_id INT UNSIGNED NOT NULL,
        parent_folder_id INT UNSIGNED NULL,
        name VARCHAR(150) NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        deleted_at TIMESTAMP NULL,
        FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (parent_folder_id) REFERENCES vault_folders(id) ON DELETE SET NULL,
        INDEX idx_vault_folders_owner (owner_id, deleted_at),
        INDEX idx_vault_folders_parent (parent_folder_id, deleted_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $connection->exec("CREATE TABLE IF NOT EXISTS vault_documents (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        owner_id INT UNSIGNED NOT NULL,
        folder_id INT UNSIGNED NULL,
        display_name VARCHAR(180) NOT NULL,
        original_filename VARCHAR(255) NOT NULL,
        encrypted_filename VARCHAR(255) NOT NULL UNIQUE,
        mime_type VARCHAR(150) NOT NULL,
        file_size BIGINT UNSIGNED NOT NULL,
        plaintext_sha256 CHAR(64) NOT NULL,
        ciphertext_sha256 CHAR(64) NOT NULL,
        encryption_version VARCHAR(20) NOT NULL DEFAULT 'aes-256-gcm-v1',
        iv VARBINARY(12) NOT NULL,
        auth_tag VARBINARY(16) NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        deleted_at TIMESTAMP NULL,
        FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (folder_id) REFERENCES vault_folders(id) ON DELETE SET NULL,
        INDEX idx_vault_docs_owner (owner_id, deleted_at),
        INDEX idx_vault_docs_folder (folder_id, deleted_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $connection->exec("CREATE TABLE IF NOT EXISTS vault_permissions (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        owner_id INT UNSIGNED NOT NULL,
        authorized_user_id INT UNSIGNED NOT NULL,
        role ENUM('legal_counsel') NOT NULL DEFAULT 'legal_counsel',
        status ENUM('active','revoked') NOT NULL DEFAULT 'active',
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        revoked_at TIMESTAMP NULL,
        FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (authorized_user_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_vault_permission (owner_id, authorized_user_id),
        INDEX idx_vault_permissions_user (authorized_user_id, status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $connection->exec("CREATE TABLE IF NOT EXISTS vault_access_logs (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        owner_id INT UNSIGNED NOT NULL,
        actor_user_id INT UNSIGNED NULL,
        document_id INT UNSIGNED NULL,
        action ENUM('upload','view','download','rename','delete','permission_change','folder_create','folder_update','folder_delete','tamper_detected') NOT NULL,
        ip_address VARCHAR(45) NULL,
        user_agent VARCHAR(255) NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (actor_user_id) REFERENCES users(id) ON DELETE SET NULL,
        FOREIGN KEY (document_id) REFERENCES vault_documents(id) ON DELETE SET NULL,
        INDEX idx_vault_logs_owner_created (owner_id, created_at),
        INDEX idx_vault_logs_actor_created (actor_user_id, created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
};
