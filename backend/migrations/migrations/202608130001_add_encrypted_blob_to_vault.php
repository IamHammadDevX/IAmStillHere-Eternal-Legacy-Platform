<?php
return static function (PDO $connection): void {
    $connection->exec("ALTER TABLE vault_documents ADD COLUMN encrypted_blob LONGBLOB NULL AFTER encrypted_filename");
};