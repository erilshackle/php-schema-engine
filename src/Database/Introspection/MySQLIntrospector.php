<?php

namespace SchemaEngine\Database\Introspection;

use PDO;
use SchemaEngine\Metadata\ColumnDefinition;
use SchemaEngine\Metadata\SchemaDefinition;
use SchemaEngine\Metadata\TableDefinition;
use SchemaEngine\Database\Normalization\TypeNormalizer;

class MySQLIntrospector
{

    protected TypeNormalizer $normalizer;

    protected array $indexBuffer = [];

    public function __construct(
        protected PDO $pdo,
        ?TypeNormalizer $normalizer = null,
        protected ?string $database = null
    ) {
        $this->normalizer =
            $normalizer ?? new TypeNormalizer();
    }

    public function getSchema(): SchemaDefinition
    {
        $schema = new SchemaDefinition();

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
        // foreach ($this->getIndexes() as $indexData) {

        //     $table = $tables[$indexData['TABLE_NAME']];
        //     $table->addRawIndexRow($indexData);
        // }

        foreach ($this->getIndexes() as $indexData) {

            $this->indexBuffer[] = $indexData;
        }

        $this->hydrateIndexes($tables);
        return $schema;
    }

    protected function getTables(): array
    {
        $stmt = $this->pdo->query("
        SELECT TABLE_NAME
        FROM INFORMATION_SCHEMA.TABLES
        WHERE TABLE_SCHEMA = DATABASE()
    ");

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

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    protected function getStatistics(): array
    {
        $stmt = $this->pdo->query("
        SELECT *
        FROM INFORMATION_SCHEMA.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
    ");

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
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
            $data['COLUMN_DEFAULT'];

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

            $grouped[$table][$index]['columns'][] =
                $row['COLUMN_NAME'];

            $grouped[$table][$index]['unique'] =
                !$row['NON_UNIQUE'];

            $grouped[$table][$index]['primary'] =
                $index === 'PRIMARY';
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
}
