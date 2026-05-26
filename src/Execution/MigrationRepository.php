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

    public function ensureOperationsTable(): void
    {
        $this->pdo->exec("
        CREATE TABLE IF NOT EXISTS `schema_migration_operations` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `batch` INT NOT NULL,
            `operation` VARCHAR(255) NOT NULL,
            `sql` TEXT NOT NULL,
            `rollback_sql` TEXT NULL,
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

    public function lastBatch(): ?int
    {
        $this->ensureTable();

        $stmt = $this->pdo->query("
        SELECT MAX(batch)
        FROM `schema_migrations`
        ");

        $batch = $stmt->fetchColumn();

        return $batch ? (int) $batch : null;
    }

    public function deleteBatch(
        int $batch
    ): void {

        $this->ensureTable();
        $this->ensureOperationsTable();

        $stmt = $this->pdo->prepare("
        DELETE FROM `schema_migrations`
        WHERE batch = ?
    ");

        $stmt->execute([$batch]);

        $stmt = $this->pdo->prepare("
        DELETE FROM `schema_migration_operations`
        WHERE batch = ?
    ");

        $stmt->execute([$batch]);
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

    public function logOperation(
        int $batch,
        string $operation,
        string $sql,
        ?string $rollbackSql
    ): void {

        $this->ensureOperationsTable();

        $stmt = $this->pdo->prepare("
        INSERT INTO `schema_migration_operations`
            (`batch`, `operation`, `sql`, `rollback_sql`)
        VALUES
            (?, ?, ?, ?)
        ");

        $stmt->execute([
            $batch,
            $operation,
            $sql,
            $rollbackSql,
        ]);
    }

    public function rollbackOperations(
        int $batch
    ): array {

        $this->ensureOperationsTable();

        $stmt = $this->pdo->prepare("
        SELECT *
        FROM `schema_migration_operations`
        WHERE batch = ?
        ORDER BY id DESC
    ");

        $stmt->execute([
            $batch
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
