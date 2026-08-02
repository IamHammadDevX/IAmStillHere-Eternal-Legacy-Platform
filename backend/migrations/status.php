<?php

require_once __DIR__ . '/MigrationRunner.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "This script can only be run from the command line.\n";
    exit(1);
}

try {
    $runner = new MigrationRunner();
    $executed = $runner->getExecutedMigrations();
    $pending = $runner->getPendingMigrations();

    echo "Migration status\n";
    echo "Executed: " . count($executed) . "\n";
    echo "Pending: " . count($pending) . "\n";

    if (count($executed) > 0) {
        echo "\nExecuted migrations:\n";
        foreach ($executed as $migration) {
            echo "- {$migration['migration']} at {$migration['executed_at']}\n";
        }
    }

    if (count($pending) > 0) {
        echo "\nPending migrations:\n";
        foreach ($pending as $migration) {
            echo "- {$migration}\n";
        }
    }

    exit(0);
} catch (Throwable $exception) {
    fwrite(STDERR, "Status check failed: " . $exception->getMessage() . "\n");
    exit(1);
}
