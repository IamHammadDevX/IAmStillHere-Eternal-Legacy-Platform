<?php
return static function (PDO $connection): void {
    if (!$connection->query("SHOW COLUMNS FROM scheduled_events LIKE 'media_path'")->fetch()) {
        $connection->exec("ALTER TABLE scheduled_events ADD COLUMN media_path VARCHAR(255) NULL AFTER message, ADD COLUMN media_mime VARCHAR(100) NULL AFTER media_path, ADD COLUMN media_type ENUM('image','video') NULL AFTER media_mime");
    }
};
