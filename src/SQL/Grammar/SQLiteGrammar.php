<?php

namespace SchemaEngine\SQL\Grammar;

use RuntimeException;
use SchemaEngine\Metadata\ColumnDefinition;
use SchemaEngine\Metadata\ForeignKeyDefinition;
use SchemaEngine\Metadata\IndexDefinition;
use SchemaEngine\Metadata\TableDefinition;
use SchemaEngine\Operations\Column\AddColumn;
use SchemaEngine\Operations\Column\DropColumn;
use SchemaEngine\Operations\Column\ModifyColumn;
use SchemaEngine\Operations\Column\RenameColumn;
use SchemaEngine\Operations\ForeignKey\AddForeignKey;
use SchemaEngine\Operations\Index\AddIndex;
use SchemaEngine\Operations\Index\DropIndex;
use SchemaEngine\Operations\Operation;
use SchemaEngine\Operations\Table\CreateTable;
use SchemaEngine\Operations\Table\DropTable;
use SchemaEngine\Operations\Table\RecreateTable;
use SchemaEngine\SQL\Expression\Expression;

class SQLiteGrammar
{
    public function compile(Operation $operation): string
    {
        return match (true) {
            $operation instanceof CreateTable =>
            $this->compileCreateTable($operation),

            $operation instanceof DropTable =>
            "DROP TABLE `{$operation->table}`",

            $operation instanceof AddColumn =>
            $this->compileAddColumn($operation),

            $operation instanceof RenameColumn =>
            "ALTER TABLE `{$operation->table}` RENAME COLUMN `{$operation->from}` TO `{$operation->to}`",

            $operation instanceof DropColumn =>
            "ALTER TABLE `{$operation->table}` DROP COLUMN `{$operation->column}`",

            $operation instanceof ModifyColumn =>
            throw new RuntimeException(
                'SQLite does not support MODIFY COLUMN directly. Use table recreation.'
            ),

            $operation instanceof AddIndex =>
            $this->compileAddIndex($operation),

            $operation instanceof DropIndex =>
            $this->compileDropIndex($operation),

            $operation instanceof AddForeignKey =>
            throw new RuntimeException(
                'SQLite does not support ADD FOREIGN KEY via ALTER TABLE. Use table recreation.'
            ),

            $operation instanceof RecreateTable =>
            $this->compileRecreateTable($operation),

            default => throw new RuntimeException(
                'Unsupported SQLite operation: ' . get_class($operation)
            ),
        };
    }

    protected function compileCreateTable(CreateTable $operation): string
    {
        $table = $operation->table;

        $columns = [];

        foreach ($table->columns as $column) {
            $columns[] = $this->compileColumn($column);
        }

        foreach ($table->indexes as $index) {
            if ($index->primary) {
                $columns[] = $this->compilePrimaryKey($index);
            }
        }

        foreach ($table->foreignKeys as $foreignKey) {
            $columns[] = $this->compileForeignKey($foreignKey);
        }

        $sql = "CREATE TABLE `{$table->name}` (\n";
        $sql .= implode(",\n", $columns);
        $sql .= "\n)";

        $indexSql = [];

        foreach ($table->indexes as $index) {
            if ($index->primary) {
                continue;
            }

            $indexSql[] = $this->compileCreateIndex(
                $table->name,
                $index
            );
        }

        if ($indexSql) {
            $sql .= ";\n" . implode(";\n", $indexSql);
        }

        return $sql;
    }

    protected function compileColumn(ColumnDefinition $column): string
    {
        if (
            $column->autoIncrement
            && strtolower($column->name) === 'id'
        ) {
            return "`{$column->name}` INTEGER PRIMARY KEY AUTOINCREMENT";
        }

        $sql = "`{$column->name}` ";
        $sql .= $this->compileType($column);

        $sql .= $column->nullable
            ? ' NULL'
            : ' NOT NULL';

        if ($column->default !== null) {
            $sql .= ' DEFAULT ' . $this->compileDefault($column->default);
        }

        return $sql;
    }

    protected function compileType(ColumnDefinition $column): string
    {
        return match ($column->type) {
            'int',
            'bigint' => 'INTEGER',

            'varchar',
            'char',
            'text',
            'longtext' => 'TEXT',

            'float',
            'double',
            'decimal' => 'REAL',

            'boolean' => 'INTEGER',

            'json' => 'TEXT',

            'datetime',
            'timestamp' => 'TEXT',

            default => strtoupper($column->type),
        };
    }

    protected function compilePrimaryKey(IndexDefinition $index): string
    {
        $columns = $this->columnList($index->columns);

        return "PRIMARY KEY ({$columns})";
    }

    protected function compileForeignKey(
        ForeignKeyDefinition $foreignKey
    ): string {
        $columns = $this->columnList($foreignKey->columns);
        $referencedColumns = $this->columnList($foreignKey->referencedColumns);

        $sql = "FOREIGN KEY ({$columns}) ";
        $sql .= "REFERENCES `{$foreignKey->referencedTable}` ({$referencedColumns})";

        if ($foreignKey->onDelete) {
            $sql .= " ON DELETE {$foreignKey->onDelete}";
        }

        if ($foreignKey->onUpdate) {
            $sql .= " ON UPDATE {$foreignKey->onUpdate}";
        }

        return $sql;
    }

    protected function compileAddColumn(AddColumn $operation): string
    {
        return "ALTER TABLE `{$operation->table}` ADD COLUMN "
            . $this->compileColumn($operation->column);
    }

    protected function compileAddIndex(AddIndex $operation): string
    {
        return $this->compileCreateIndex(
            $operation->table,
            $operation->index
        );
    }

    protected function compileDropIndex(DropIndex $operation): string
    {
        return "DROP INDEX `{$operation->name}`";
    }

    protected function compileCreateIndex(
        string $table,
        IndexDefinition $index
    ): string {
        $unique = $index->unique ? 'UNIQUE ' : '';

        return "CREATE {$unique}INDEX `{$index->name}` ON `{$table}` ({$this->columnList($index->columns)})";
    }

    protected function columnList(array $columns): string
    {
        return implode(
            ', ',
            array_map(
                fn($column) => "`{$column}`",
                $columns
            )
        );
    }

    protected function compileDefault(mixed $value): string
    {
        if ($value instanceof Expression) {
            return $value->getValue();
        }

        if (is_string($value)) {
            return "'{$value}'";
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return (string) $value;
    }

    protected function compileRecreateTable(
        RecreateTable $operation
    ): array {

        $current = $operation->current;
        $desired = $operation->desired;

        $temporaryName = "__schema_tmp_{$desired->name}";

        $temporaryTable = clone $desired;
        $temporaryTable->name = $temporaryName;

        $createSql = $this->compileCreateTable(
            new CreateTable($temporaryTable)
        );

        $copyColumns = $this->sharedColumns(
            $current,
            $desired
        );

        $columnsSql = implode(
            ', ',
            array_map(
                fn($column) => "`{$column}`",
                $copyColumns
            )
        );

        return [
            'PRAGMA foreign_keys = OFF',
            $createSql,
            "INSERT INTO `{$temporaryName}` ({$columnsSql}) SELECT {$columnsSql} FROM `{$current->name}`",
            "DROP TABLE `{$current->name}`",
            "ALTER TABLE `{$temporaryName}` RENAME TO `{$desired->name}`",
            'PRAGMA foreign_keys = ON',
        ];
    }

    protected function sharedColumns(
        TableDefinition $current,
        TableDefinition $desired
    ): array {

        $columns = [];

        foreach ($desired->columns as $name => $column) {

            if ($current->hasColumn($name)) {
                $columns[] = $name;
            }
        }

        return $columns;
    }
}
