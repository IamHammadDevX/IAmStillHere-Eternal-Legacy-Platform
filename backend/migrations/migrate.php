<?php

require_once __DIR__ . '/MigrationRunner.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "This script can only be run from the command line.\n";
    exit(1);
}

try {
    $runner = new MigrationRunner();
    $executed = $runner->migrate();

    echo "Migration table ready.\n";

    if (count($executed) === 0) {
        echo "No pending migrations.\n";
        exit(0);
    }

    foreach ($executed as $migration) {
        echo "Executed: {$migration}\n";
    }

    echo "Migrations complete.\n";
    exit(0);
} catch (Throwable $exception) {
    fwrite(STDERR, "Migration failed: " . $exception->getMessage() . "\n");
    exit(1);
}
