<?php

if (PHP_SAPI === 'cli') {
    ini_set('session.save_path', sys_get_temp_dir());
}

require_once __DIR__ . '/../../config/config.php';

class MigrationRunner
{
    private PDO $connection;
    private string $migrationDirectory;

    public function __construct(?PDO $connection = null, ?string $migrationDirectory = null)
    {
        if ($connection === null) {
            $database = new Database();
            $connection = $database->getConnection();
        }

        $this->connection = $connection;
        $this->migrationDirectory = $migrationDirectory ?: __DIR__ . '/migrations';
    }

    public function ensureMigrationsTable(): void
    {
        $this->connection->exec(
            "CREATE TABLE IF NOT EXISTS migrations (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                migration VARCHAR(255) NOT NULL UNIQUE,
                executed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    public function getExecutedMigrations(): array
    {
        $this->ensureMigrationsTable();

        $statement = $this->connection->query('SELECT migration, executed_at FROM migrations ORDER BY migration ASC');
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPendingMigrations(): array
    {
        $executed = array_column($this->getExecutedMigrations(), 'migration');
        $available = $this->getAvailableMigrations();

        return array_values(array_filter($available, static function ($migration) use ($executed) {
            return !in_array($migration, $executed, true);
        }));
    }

    public function getAvailableMigrations(): array
    {
        if (!is_dir($this->migrationDirectory)) {
            return [];
        }

        $files = scandir($this->migrationDirectory);
        if ($files === false) {
            return [];
        }

        $migrations = array_values(array_filter($files, static function ($file) {
            return pathinfo($file, PATHINFO_EXTENSION) === 'php';
        }));

        sort($migrations, SORT_STRING);
        return $migrations;
    }

    public function migrate(): array
    {
        $this->ensureMigrationsTable();

        $executedNow = [];
        $pending = $this->getPendingMigrations();

        foreach ($pending as $migration) {
            $path = $this->migrationDirectory . DIRECTORY_SEPARATOR . $migration;
            $callback = require $path;

            if (!is_callable($callback)) {
                throw new RuntimeException("Migration {$migration} must return a callable.");
            }

            try {
                $this->connection->beginTransaction();
                $callback($this->connection);
                $this->recordMigration($migration);
                if ($this->connection->inTransaction()) {
                    $this->connection->commit();
                }
                $executedNow[] = $migration;
            } catch (Throwable $exception) {
                if ($this->connection->inTransaction()) {
                    $this->connection->rollBack();
                }

                throw new RuntimeException("Migration failed: {$migration}. " . $exception->getMessage(), 0, $exception);
            }
        }

        return $executedNow;
    }

    private function recordMigration(string $migration): void
    {
        $statement = $this->connection->prepare('INSERT INTO migrations (migration) VALUES (:migration)');
        $statement->execute(['migration' => $migration]);
    }
}
