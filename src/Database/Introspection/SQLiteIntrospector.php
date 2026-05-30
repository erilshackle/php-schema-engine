<?php

namespace SchemaEngine\Database\Introspection;

use PDO;
use SchemaEngine\Database\Normalization\SQLiteTypeNormalizer;
use SchemaEngine\Metadata\ColumnDefinition;
use SchemaEngine\Metadata\ForeignKeyDefinition;
use SchemaEngine\Metadata\IndexDefinition;
use SchemaEngine\Metadata\SchemaDefinition;
use SchemaEngine\Metadata\TableDefinition;

class SQLiteIntrospector
{
    protected SQLiteTypeNormalizer $normalizer;

    protected array $ignoredTables = [
        'sqlite_sequence',
        'schema_migrations',
        'schema_migration_operations',
    ];

    public function __construct(
        protected PDO $pdo,
        ?SQLiteTypeNormalizer $normalizer = null
    ) {
        $this->normalizer =
            $normalizer ?? new SQLiteTypeNormalizer();

        $this->pdo->exec('PRAGMA foreign_keys = ON');
    }

    public function getSchema(): SchemaDefinition
    {
        $schema = new SchemaDefinition();

        foreach ($this->getTables() as $tableName) {
            $table = new TableDefinition($tableName);

            foreach ($this->getColumns($tableName) as $column) {
                $table->addColumn($column);
            }

            foreach ($this->getIndexes($tableName) as $index) {
                $table->addIndex($index);
            }

            foreach ($this->getForeignKeys($tableName) as $foreignKey) {
                $table->addForeignKey($foreignKey);
            }

            $schema->addTable($table);
        }

        return $schema;
    }

    protected function getTables(): array
    {
        $stmt = $this->pdo->query("
            SELECT name
            FROM sqlite_master
            WHERE type = 'table'
              AND name NOT LIKE 'sqlite_%'
            ORDER BY name
        ");

        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

        return array_values(
            array_filter(
                $tables,
                fn ($table) => !$this->isIgnoredTable($table)
            )
        );
    }

    /**
     * @return ColumnDefinition[]
     */
    protected function getColumns(string $table): array
    {
        $stmt = $this->pdo->query(
            "PRAGMA table_info(`{$table}`)"
        );

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $columns = [];

        foreach ($rows as $row) {
            $columns[] = $this->makeColumn($row);
        }

        return $columns;
    }

    protected function makeColumn(array $row): ColumnDefinition
    {
        $column = new ColumnDefinition(
            $row['name'],
            $this->normalizer->normalize(
                $row['type'] ?? ''
            )
        );

        $column->nullable =
            ((int) $row['notnull']) === 0
            && ((int) $row['pk']) === 0;

        $column->default =
            $this->normalizeDefault(
                $row['dflt_value'] ?? null
            );

        $column->autoIncrement =
            ((int) $row['pk']) > 0
            && $this->isIntegerType($row['type'] ?? '');

        return $column;
    }

    /**
     * @return IndexDefinition[]
     */
    protected function getIndexes(string $table): array
    {
        $stmt = $this->pdo->query(
            "PRAGMA index_list(`{$table}`)"
        );

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $indexes = [];

        foreach ($rows as $row) {
            $name = $row['name'];

            if (str_starts_with($name, 'sqlite_autoindex_')) {
                continue;
            }

            $index = new IndexDefinition(
                $name,
                $this->getIndexColumns($name)
            );

            $index->unique =
                ((int) $row['unique']) === 1;

            $indexes[$index->name] = $index;
        }

        $primaryColumns = $this->getPrimaryColumns($table);

        if ($primaryColumns) {
            $primary = new IndexDefinition(
                'primary',
                $primaryColumns
            );

            $primary->primary = true;

            $indexes['primary'] = $primary;
        }

        return array_values($indexes);
    }

    protected function getIndexColumns(string $index): array
    {
        $stmt = $this->pdo->query(
            "PRAGMA index_info(`{$index}`)"
        );

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(
            fn ($row) => $row['name'],
            $rows
        );
    }

    protected function getPrimaryColumns(string $table): array
    {
        $stmt = $this->pdo->query(
            "PRAGMA table_info(`{$table}`)"
        );

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $primary = [];

        foreach ($rows as $row) {
            if ((int) $row['pk'] > 0) {
                $primary[(int) $row['pk']] = $row['name'];
            }
        }

        ksort($primary);

        return array_values($primary);
    }

    /**
     * @return ForeignKeyDefinition[]
     */
    protected function getForeignKeys(string $table): array
    {
        $stmt = $this->pdo->query(
            "PRAGMA foreign_key_list(`{$table}`)"
        );

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $grouped = [];

        foreach ($rows as $row) {
            $id = (string) $row['id'];

            $name = "{$table}_fk_{$id}";

            $grouped[$id] ??= [
                'name' => $name,
                'columns' => [],
                'referencedTable' => $row['table'],
                'referencedColumns' => [],
                'onDelete' => $this->normalizeAction($row['on_delete'] ?? null),
                'onUpdate' => $this->normalizeAction($row['on_update'] ?? null),
            ];

            $grouped[$id]['columns'][] =
                $row['from'];

            $grouped[$id]['referencedColumns'][] =
                $row['to'];
        }

        $foreignKeys = [];

        foreach ($grouped as $data) {
            $fk = new ForeignKeyDefinition(
                $data['name'],
                $data['columns']
            );

            $fk->referencedTable =
                $data['referencedTable'];

            $fk->referencedColumns =
                $data['referencedColumns'];

            $fk->onDelete =
                $data['onDelete'];

            $fk->onUpdate =
                $data['onUpdate'];

            $foreignKeys[] = $fk;
        }

        return $foreignKeys;
    }

    protected function normalizeDefault(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value, "'\"");

        return strtoupper($value) === 'NULL'
            ? null
            : $value;
    }

    protected function normalizeAction(?string $action): ?string
    {
        if (!$action || strtoupper($action) === 'NO ACTION') {
            return null;
        }

        return strtoupper($action);
    }

    protected function isIntegerType(string $type): bool
    {
        return str_contains(
            strtolower($type),
            'int'
        );
    }

    protected function isIgnoredTable(string $table): bool
    {
        return in_array(
            $table,
            $this->ignoredTables,
            true
        );
    }
}