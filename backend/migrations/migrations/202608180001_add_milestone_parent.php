<?php
return static function (PDO $connection): void {
    if (!$connection->query("SHOW COLUMNS FROM milestones LIKE 'parent_id'")->fetch()) {
        $connection->exec("ALTER TABLE milestones ADD COLUMN parent_id INT NULL AFTER id, ADD INDEX idx_milestones_parent_id (parent_id)");
    }
};
