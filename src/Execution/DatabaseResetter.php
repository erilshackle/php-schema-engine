<?php

namespace SchemaEngine\Execution;

use PDO;

class DatabaseResetter
{
    public function __construct(
        protected PDO $pdo,
        protected array $ignoredTables = [
            'schema_migrations',
        ]
    ) {}

    public function fresh(): void
    {
        $tables = $this->tables();

        $this->pdo->exec('SET FOREIGN_KEY_CHECKS=0');

        foreach ($tables as $table) {
            $this->pdo->exec(
                "DROP TABLE IF EXISTS `{$table}`"
            );
        }

        $this->pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    }

    public function clearHistory(
        string $table = 'schema_migrations'
    ): void {

        $this->pdo->exec(
            "DROP TABLE IF EXISTS `{$table}`"
        );
    }

    protected function tables(): array
    {
        $stmt = $this->pdo->query('SHOW TABLES');

        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

        return array_values(
            array_filter(
                $tables,
                fn($table) => !in_array(
                    $table,
                    $this->ignoredTables,
                    true
                )
            )
        );
    }
}
