<?php
return static function (PDO $connection): void {
    $connection->exec("CREATE TABLE IF NOT EXISTS journeys (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        owner_id INT UNSIGNED NOT NULL,
        title VARCHAR(180) NOT NULL,
        description TEXT NULL,
        start_date DATE NULL,
        end_date DATE NULL,
        cover_image VARCHAR(255) NULL,
        privacy_level ENUM('public','family','friends','private') NOT NULL DEFAULT 'private',
        status ENUM('draft','published','archived') NOT NULL DEFAULT 'draft',
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        deleted_at TIMESTAMP NULL,
        FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_journeys_owner_status (owner_id, status, deleted_at),
        INDEX idx_journeys_dates (start_date, end_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $connection->exec("CREATE TABLE IF NOT EXISTS journey_participants (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        journey_id INT UNSIGNED NOT NULL,
        user_id INT UNSIGNED NOT NULL,
        role ENUM('owner','participant') NOT NULL DEFAULT 'participant',
        status ENUM('pending','accepted','rejected','removed') NOT NULL DEFAULT 'pending',
        invited_by INT UNSIGNED NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        responded_at TIMESTAMP NULL,
        FOREIGN KEY (journey_id) REFERENCES journeys(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (invited_by) REFERENCES users(id) ON DELETE SET NULL,
        UNIQUE KEY unique_journey_participant (journey_id, user_id),
        INDEX idx_journey_participants_user (user_id, status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $connection->exec("CREATE TABLE IF NOT EXISTS journey_invitations (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        journey_id INT UNSIGNED NOT NULL,
        inviter_id INT UNSIGNED NOT NULL,
        invitee_id INT UNSIGNED NOT NULL,
        status ENUM('pending','accepted','rejected','removed') NOT NULL DEFAULT 'pending',
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        responded_at TIMESTAMP NULL,
        FOREIGN KEY (journey_id) REFERENCES journeys(id) ON DELETE CASCADE,
        FOREIGN KEY (inviter_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (invitee_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_journey_invitation (journey_id, invitee_id),
        INDEX idx_journey_invitations_invitee (invitee_id, status, created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $connection->exec("CREATE TABLE IF NOT EXISTS journey_items (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        journey_id INT UNSIGNED NOT NULL,
        contributor_id INT UNSIGNED NOT NULL,
        item_type ENUM('memory','milestone','event') NOT NULL,
        source_id INT UNSIGNED NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT NULL,
        item_date DATE NULL,
        status ENUM('pending','approved','rejected','removed') NOT NULL DEFAULT 'pending',
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        deleted_at TIMESTAMP NULL,
        FOREIGN KEY (journey_id) REFERENCES journeys(id) ON DELETE CASCADE,
        FOREIGN KEY (contributor_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_journey_source (journey_id, item_type, source_id),
        INDEX idx_journey_items_order (journey_id, status, item_date, id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
};
