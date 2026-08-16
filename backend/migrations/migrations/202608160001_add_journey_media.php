<?php
return static function (PDO $connection): void {
    $hasCoverType = $connection->query("SHOW COLUMNS FROM journeys LIKE 'cover_media_type'")->fetch();
    if (!$hasCoverType) $connection->exec("ALTER TABLE journeys ADD COLUMN cover_media_type ENUM('image','video') NULL AFTER cover_image");
    $hasMediaPath = $connection->query("SHOW COLUMNS FROM journey_items LIKE 'media_path'")->fetch();
    if (!$hasMediaPath) $connection->exec("ALTER TABLE journey_items ADD COLUMN media_path VARCHAR(255) NULL AFTER source_id, ADD COLUMN media_mime VARCHAR(100) NULL AFTER media_path");
    $type = $connection->query("SHOW COLUMNS FROM journey_items LIKE 'item_type'")->fetch(PDO::FETCH_ASSOC);
    if ($type && strpos((string)$type['Type'], "'media'") === false) $connection->exec("ALTER TABLE journey_items MODIFY item_type ENUM('memory','milestone','event','media') NOT NULL");
};
