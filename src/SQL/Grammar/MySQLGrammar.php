<?php

namespace SchemaEngine\SQL\Grammar;

use RuntimeException;
use SchemaEngine\Metadata\ColumnDefinition;
use SchemaEngine\Metadata\ForeignKeyDefinition;
use SchemaEngine\Metadata\TableDefinition;
use SchemaEngine\Metadata\IndexDefinition;
use SchemaEngine\Operations\Column\RenameColumn;
use SchemaEngine\Operations\Column\AddColumn;
use SchemaEngine\Operations\Column\DropColumn;
use SchemaEngine\Operations\Column\ModifyColumn;
use SchemaEngine\Operations\ForeignKey\AddForeignKey;
use SchemaEngine\Operations\Operation;
use SchemaEngine\Operations\Table\CreateTable;
use SchemaEngine\Operations\Table\DropTable;
use SchemaEngine\Operations\Index\AddIndex;
use SchemaEngine\Operations\Index\DropIndex;
use SchemaEngine\SQL\Expression\Expression;

class MySQLGrammar
{
    public function compile(
        Operation $operation
    ): string {

        return match (true) {

            $operation instanceof CreateTable =>
            $this->compileCreateTable(
                $operation
            ),

            $operation instanceof DropTable =>
            $this->compileDropTable(
                $operation
            ),

            $operation instanceof AddColumn =>
            $this->compileAddColumn(
                $operation
            ),

            $operation instanceof ModifyColumn =>
            $this->compileModifyColumn(
                $operation
            ),

            $operation instanceof DropColumn =>
            $this->compileDropColumn(
                $operation
            ),

            $operation instanceof RenameColumn =>
            $this->compileRenameColumn(
                $operation
            ),

            $operation instanceof AddForeignKey =>
            $this->compileAddForeignKey($operation),

            $operation instanceof AddIndex =>
            $this->compileAddIndex($operation),

            $operation instanceof DropIndex =>
            $this->compileDropIndex($operation),

            default => throw new RuntimeException(
                'Unsupported operation: '
                    . get_class($operation)
            )
        };
    }

    protected function compileColumn(
        ColumnDefinition $column
    ): string {

        $sql = "`{$column->name}` ";

        $sql .= $this->compileType($column);

        $sql .= $column->nullable
            ? ' NULL'
            : ' NOT NULL';

        if ($column->default !== null) {

            $default =
                $this->compileDefault(
                    $column->default
                );

            $sql .= " DEFAULT {$default}";
        }

        if ($column->autoIncrement) {
            $sql .= ' AUTO_INCREMENT';
        }


        return $sql;
    }

    protected function compileType(
        ColumnDefinition $column
    ): string {

        return match ($column->type) {

            'varchar' => 'VARCHAR('
                . ($column->length ?? 255)
                . ')',

            'int' => 'INT',

            'text' => 'TEXT',

            'datetime' => 'DATETIME',

            'timestamp' => 'TIMESTAMP',

            'boolean' => 'BOOLEAN',

            'decimal' => sprintf(
                'DECIMAL(%d,%d)',
                $column->precision ?? 10,
                $column->scale ?? 2
            ),

            'double' => 'DOUBLE',

            'float' => 'FLOAT',

            'char' => 'CHAR('
                . ($column->length ?? 1)
                . ')',

            'bigint' => 'BIGINT',

            'longtext' => 'LONGTEXT',

            'json' => 'JSON',

            default => strtoupper(
                $column->type
            )
        };
    }

    protected function compileIndex(
        IndexDefinition $index
    ): string {

        $columns = implode(', ', array_map(
            fn($column) => "`{$column}`",
            $index->columns
        ));

        if ($index->primary) {
            return "PRIMARY KEY ({$columns})";
        }

        if ($index->unique) {
            return "UNIQUE KEY `{$index->name}` ({$columns})";
        }

        return "KEY `{$index->name}` ({$columns})";
    }

    protected function compileForeignKey(
        ForeignKeyDefinition $foreignKey
    ): string {

        $columns = implode(', ', array_map(
            fn($column) => "`{$column}`",
            $foreignKey->columns
        ));

        $referencedColumns = implode(', ', array_map(
            fn($column) => "`{$column}`",
            $foreignKey->referencedColumns
        ));

        $sql = "CONSTRAINT `{$foreignKey->name}` ";
        $sql .= "FOREIGN KEY ({$columns}) ";
        $sql .= "REFERENCES `{$foreignKey->referencedTable}` ({$referencedColumns})";

        if ($foreignKey->onDelete) {
            $sql .= " ON DELETE {$foreignKey->onDelete}";
        }

        if ($foreignKey->onUpdate) {
            $sql .= " ON UPDATE {$foreignKey->onUpdate}";
        }

        return $sql;
    }

    protected function compileCreateTable(
        CreateTable $operation
    ): string {

        $table = $operation->table;

        $columns = [];

        foreach ($table->columns as $column) {
            $columns[] = $this->compileColumn($column);
        }

        $indexes = $this->compileIndexes(
            $table->indexes
        );

        $foreignKeys = $this->compileForeignKeys(
            $table->foreignKeys
        );

        $definitions = array_merge(
            $columns,
            $indexes,
            $foreignKeys
        );

        $columnsSql = implode(",\n", $definitions);

        return "
CREATE TABLE `{$table->name}` (
{$columnsSql}
)
ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
";
    }

    protected function compileAddColumn(
        AddColumn $operation
    ): string {

        $column = $this->compileColumn(
            $operation->column
        );

        return "
ALTER TABLE `{$operation->table}`
ADD COLUMN {$column}
";
    }


    protected function compileModifyColumn(
        ModifyColumn $operation
    ): string {

        $column = $this->compileColumn(
            $operation->desired
        );

        return "
ALTER TABLE `{$operation->table}`
MODIFY COLUMN {$column}
";
    }

    protected function compileRenameColumn(
        RenameColumn $operation
    ): string {

        return "
ALTER TABLE `{$operation->table}`
RENAME COLUMN `{$operation->from}`
TO `{$operation->to}`
";
    }

    protected function compileDropColumn(
        DropColumn $operation
    ): string {

        return "
ALTER TABLE `{$operation->table}`
DROP COLUMN `{$operation->column}`
";
    }

    protected function compileDropTable(
        DropTable $operation
    ): string {

        return "
DROP TABLE `{$operation->table}`
";
    }



    protected function compileAddIndex(
        AddIndex $operation
    ): string {

        $index = $this->compileIndex(
            $operation->index
        );

        return "
ALTER TABLE `{$operation->table}` 
ADD {$index}";
    }

    protected function compileDropIndex(
        DropIndex $operation
    ): string {

        if (strtolower($operation->name) === 'primary') {
            // ALTER TABLE `{$operation->table}` DROP PRIMARY KEY `{$operation->name}`
            return "
ALTER TABLE `{$operation->table}`
DROP PRIMARY KEY
";
        }

        return "
ALTER TABLE `{$operation->table}`
DROP INDEX `{$operation->name}`
";
    }

    protected function compileAddForeignKey(
        AddForeignKey $operation
    ): string {

        $foreignKey = $this->compileForeignKey(
            $operation->foreignKey
        );

        return "
ALTER TABLE `{$operation->table}` 
ADD {$foreignKey}";
    }

    protected function compileDefault(
        mixed $value
    ): string {

        if ($value instanceof Expression) {

            return $value->getValue();
        }

        if (is_string($value)) {

            return "'{$value}'";
        }

        if (is_bool($value)) {

            return $value ? '1' : '0';
        }

        if ($value === null) {

            return 'NULL';
        }

        return (string) $value;
    }

    protected function compileForeignKeys(
        array $foreignKeys
    ): array {

        $sql = [];

        foreach ($foreignKeys as $foreignKey) {
            $sql[] = $this->compileForeignKey($foreignKey);
        }

        return $sql;
    }


    /**
     * Summary of compileIndexes
     * @param array $indexes
     * @return string[]
     */
    protected function compileIndexes(
        array $indexes
    ): array {

        $sql = [];

        foreach ($indexes as $index) {
            $sql[] = $this->compileIndex($index);
        }

        return $sql;
    }
}
