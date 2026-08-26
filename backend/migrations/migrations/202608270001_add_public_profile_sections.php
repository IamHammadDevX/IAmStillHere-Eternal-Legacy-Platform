<?php

return function (PDO $db): void {
    $column = $db->query("SHOW COLUMNS FROM users LIKE 'public_profile_sections'")->fetch(PDO::FETCH_ASSOC);
    if (!$column) {
        $db->exec("ALTER TABLE users ADD COLUMN public_profile_sections TEXT NULL AFTER username_changed_at");
    }
};
