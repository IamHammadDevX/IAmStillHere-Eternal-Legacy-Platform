<?php

return static function (PDO $connection): void {
    $connection->exec(
        "ALTER TABLE family_requests
         ADD COLUMN responded_at TIMESTAMP NULL AFTER created_at"
    );
};
