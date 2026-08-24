<?php
return static function (PDO $connection): void {
    if (!$connection->query("SHOW COLUMNS FROM users LIKE 'username_changed_at'")->fetch()) {
        $connection->exec("ALTER TABLE users ADD COLUMN username_changed_at DATETIME NULL AFTER status");
    }
};