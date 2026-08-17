<?php
return static function (PDO $connection): void {
    $connection->exec("ALTER TABLE vault_access_logs MODIFY action ENUM('upload','view','download','download_code_sent','download_verified','rename','delete','permission_change','folder_create','folder_update','folder_delete','tamper_detected') NOT NULL");
};