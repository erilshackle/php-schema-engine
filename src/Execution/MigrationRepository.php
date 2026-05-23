<?php

namespace SchemaEngine\Execution;

use PDO;

class MigrationRepository
{
    public function __construct(
        protected PDO $pdo,
        protected string $table = 'schema_migrations'
    ) {}

    public function ensureTable(): void
    {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS `{$this->table}` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `batch` INT NOT NULL,
                `migration` VARCHAR(255) NOT NULL,
                `executed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`)
            )
            ENGINE=InnoDB
            DEFAULT CHARSET=utf8mb4
        ");
    }

    public function nextBatch(): int
    {
        $this->ensureTable();

        $stmt = $this->pdo->query("
            SELECT MAX(batch)
            FROM `{$this->table}`
        ");

        $batch = $stmt->fetchColumn();

        return $batch
            ? ((int) $batch) + 1
            : 1;
    }

    public function log(
        string $migration,
        int $batch
    ): void {
        $this->ensureTable();

        $stmt = $this->pdo->prepare("
            INSERT INTO `{$this->table}`
                (`migration`, `batch`)
            VALUES
                (?, ?)
        ");

        $stmt->execute([
            $migration,
            $batch,
        ]);
    }

    public function all(): array
    {
        $this->ensureTable();

        $stmt = $this->pdo->query("
            SELECT *
            FROM `{$this->table}`
            ORDER BY batch ASC, id ASC
        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}