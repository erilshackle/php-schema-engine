<?php

namespace SchemaEngine\Database\Introspection;

use PDO;
use SchemaEngine\Metadata\ColumnDefinition;
use SchemaEngine\Metadata\SchemaDefinition;
use SchemaEngine\Metadata\TableDefinition;
use SchemaEngine\Database\Normalization\TypeNormalizer;
use SchemaEngine\Metadata\ForeignKeyDefinition;

class MySQLIntrospector
{

    protected TypeNormalizer $normalizer;

    protected array $indexBuffer = [];
    protected array $foreignKeyBuffer = [];

    public function __construct(
        protected PDO $pdo,
        ?TypeNormalizer $normalizer = null,
        protected ?string $database = null
    ) {
        $this->normalizer =
            $normalizer ?? new TypeNormalizer();
    }

    protected function database(): string
    {
        return $this->database ?? $this->pdo->query(
            'SELECT DATABASE()'
        )->fetchColumn();
    }

    public function getSchema(): SchemaDefinition
    {
        $schema = new SchemaDefinition();

        $this->indexBuffer = [];
        $this->foreignKeyBuffer = [];

        $tables = [];

        foreach ($this->getTables() as $tableName) {
            $table = new TableDefinition($tableName);
            $tables[$tableName] = $table;
            $schema->addTable($table);
        }

        // columns
        foreach ($this->getColumns() as $columnData) {

            $table = $tables[$columnData['TABLE_NAME']];
            $column = $this->makeColumn($columnData);
            $table->addColumn($column);
        }

        // indexes (NOVO BLOCO)
        foreach ($this->getIndexes() as $indexData) {
            $this->indexBuffer[] = $indexData;
        }

        $this->hydrateIndexes($tables);

        // foreign keys
        foreach ($this->getForeignKeys() as $foreignKeyData) {
            $this->foreignKeyBuffer[] = $foreignKeyData;
        }

        $this->hydrateForeignKeys($tables);


        return $schema;
    }


    protected function getTables(): array
    {
        $stmt = $this->pdo->query("
        SELECT TABLE_NAME
        FROM INFORMATION_SCHEMA.TABLES
        WHERE TABLE_SCHEMA = DATABASE()
        ");

        $stmt->execute([
            $this->database()
        ]);

        return $stmt->fetchAll(
            PDO::FETCH_COLUMN
        );
    }

    protected function getColumns(): array
    {
        $stmt = $this->pdo->query("
        SELECT
            TABLE_NAME,
            COLUMN_NAME,
            COLUMN_TYPE,
            DATA_TYPE,
            IS_NULLABLE,
            COLUMN_KEY,
            COLUMN_DEFAULT,
            EXTRA,
            NUMERIC_PRECISION,
            NUMERIC_SCALE,
            CHARACTER_MAXIMUM_LENGTH
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
        ");

        $stmt->execute([
            $this->database()
        ]);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    protected function getIndexes(): array
    {
        $stmt = $this->pdo->query("
        SELECT
            TABLE_NAME,
            INDEX_NAME,
            COLUMN_NAME,
            NON_UNIQUE
        FROM INFORMATION_SCHEMA.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
        ORDER BY TABLE_NAME, INDEX_NAME, SEQ_IN_INDEX
    ");

        $stmt->execute([
            $this->database()
        ]);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    protected function getForeignKeys(): array
    {
        $stmt = $this->pdo->prepare("
        SELECT
            kcu.TABLE_NAME,
            kcu.CONSTRAINT_NAME,
            kcu.COLUMN_NAME,
            kcu.REFERENCED_TABLE_NAME,
            kcu.REFERENCED_COLUMN_NAME,
            rc.UPDATE_RULE,
            rc.DELETE_RULE,
            kcu.ORDINAL_POSITION
        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE kcu
        LEFT JOIN INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS rc
            ON rc.CONSTRAINT_SCHEMA = kcu.CONSTRAINT_SCHEMA
            AND rc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME
            AND rc.TABLE_NAME = kcu.TABLE_NAME
        WHERE kcu.TABLE_SCHEMA = ?
            AND kcu.REFERENCED_TABLE_NAME IS NOT NULL
        ORDER BY kcu.TABLE_NAME, kcu.CONSTRAINT_NAME, kcu.ORDINAL_POSITION
    ");

        $stmt->execute([
            $this->database()
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    protected function normalizeForeignAction(
        ?string $action
    ): ?string {

        if ($action === null) {
            return null;
        }

        $action = strtoupper($action);

        return match ($action) {
            'RESTRICT',
            'CASCADE',
            'SET NULL',
            'NO ACTION' => $action,

            default => $action,
        };
    }


    protected function makeColumn(
        array $data
    ): ColumnDefinition {

        $type = $this->normalizer->normalize(
            $data['DATA_TYPE'],
            $data['COLUMN_TYPE']
        );

        $column = new ColumnDefinition(
            $data['COLUMN_NAME'],
            $type
        );

        $column->nullable =
            $data['IS_NULLABLE'] === 'YES';


        $column->default =
            $data['COLUMN_DEFAULT'] !== null
            ? $data['COLUMN_DEFAULT']
            : null;

        $column->autoIncrement =
            str_contains(
                $data['EXTRA'],
                'auto_increment'
            );

        $column->length =
            $data['CHARACTER_MAXIMUM_LENGTH']
            ? (int) $data['CHARACTER_MAXIMUM_LENGTH']
            : null;

        $column->precision =
            $data['NUMERIC_PRECISION']
            ? (int) $data['NUMERIC_PRECISION']
            : null;

        $column->scale =
            $data['NUMERIC_SCALE']
            ? (int) $data['NUMERIC_SCALE']
            : null;

        return $column;
    }

    protected function hydrateIndexes(array $tables): void
    {
        $grouped = [];

        foreach ($this->indexBuffer as $row) {

            $table = $row['TABLE_NAME'];
            $index = $row['INDEX_NAME'];

            $grouped[$table][$index] ??= [
                'columns' => [],
                'unique' => !$row['NON_UNIQUE'],
                'primary' => $index === 'PRIMARY',
            ];

            $grouped[$table][$index]['columns'][] = $row['COLUMN_NAME'];

            if (!isset($grouped[$table][$index]['unique'])) {
                $grouped[$table][$index]['unique'] = !$row['NON_UNIQUE'];
            }

            if (!isset($grouped[$table][$index]['primary'])) {
                $grouped[$table][$index]['primary'] = $index === 'PRIMARY';
            }
        }

        foreach ($grouped as $tableName => $indexes) {

            $table = $tables[$tableName];

            foreach ($indexes as $indexName => $data) {

                $table->addIndexFromArray([
                    'name' => $indexName,
                    'columns' => $data['columns'],
                    'unique' => $data['unique'] ?? false,
                    'primary' => $data['primary'] ?? false,
                ]);
            }
        }
    }

    protected function hydrateForeignKeys(
        array $tables
    ): void {

        $grouped = [];

        foreach ($this->foreignKeyBuffer as $row) {

            $table = $row['TABLE_NAME'];
            $name = $row['CONSTRAINT_NAME'];

            $grouped[$table][$name] ??= [
                'columns' => [],
                'referencedTable' => $row['REFERENCED_TABLE_NAME'],
                'referencedColumns' => [],
                'onDelete' => $this->normalizeForeignAction(
                    $row['DELETE_RULE'] ?? null
                ),
                'onUpdate' => $this->normalizeForeignAction(
                    $row['UPDATE_RULE'] ?? null
                ),
            ];

            $grouped[$table][$name]['columns'][] =
                $row['COLUMN_NAME'];

            $grouped[$table][$name]['referencedColumns'][] =
                $row['REFERENCED_COLUMN_NAME'];
        }

        foreach ($grouped as $tableName => $foreignKeys) {

            if (!isset($tables[$tableName])) {
                continue;
            }

            $table = $tables[$tableName];

            foreach ($foreignKeys as $name => $data) {

                $foreignKey = new ForeignKeyDefinition(
                    $name,
                    $data['columns']
                );

                $foreignKey->referencedTable =
                    $data['referencedTable'];

                $foreignKey->referencedColumns =
                    $data['referencedColumns'];

                $foreignKey->onDelete =
                    $data['onDelete'];

                $foreignKey->onUpdate =
                    $data['onUpdate'];

                $table->addForeignKey($foreignKey);
            }
        }
    }
}
