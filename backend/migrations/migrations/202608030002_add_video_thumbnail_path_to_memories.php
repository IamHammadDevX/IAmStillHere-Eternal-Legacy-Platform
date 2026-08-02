<?php

return static function (PDO $connection): void {
    $connection->exec(
        "ALTER TABLE memories
         ADD COLUMN video_thumbnail_path VARCHAR(500) NULL AFTER file_path"
    );
};
