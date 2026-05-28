<?php

namespace SchemaEngine\DSL;

use SchemaEngine\Metadata\ForeignKeyDefinition;
use SchemaEngine\Metadata\TableDefinition;
use SchemaEngine\Naming\NameInflector;

class ForeignIdColumn
{
    protected ?ForeignKeyDefinition $foreignKey = null;

    public function __construct(
        protected Column $column,
        protected TableDefinition $table
    ) {}

    public function nullable(
        bool $state = true
    ): static {
        $this->column->nullable($state);

        return $this;
    }

    public function default(
        mixed $value
    ): static {
        $this->column->default($value);

        return $this;
    }

    /**
     *  REFERENCES
     */

    /**
     * Define a foreign key reference for this column.
     *
     * This is the SQL-oriented API. For conventional BIGINT foreign keys
     *
     * Example:
     *
     * ```php
     * $t->uuid('author_id')
     *     ->constrained();
     * ```
     *
     * @param string $table Referenced table name.
     * @param string $column Referenced column name.
     * @param string|null $name Foreign key constraint name.
     * @return ForeignKey
     */
    public function constrained(
        ?string $table = null,
        string $column = 'id',
        ?string $name = null
    ): ForeignKey {

        $table ??= $this->inferReferencedTable();

        if ($this->foreignKey) {
            $this->table->removeForeignKey(
                $this->foreignKey->name
            );
        }

        $name ??= $this->foreignKeyName();

        $foreignKey = new ForeignKeyDefinition(
            $name,
            [$this->columnName()]
        );

        $foreignKey->referencedTable = $table;
        $foreignKey->referencedColumns = [$column];

        $this->table->addForeignKey($foreignKey);

        $this->foreignKey = $foreignKey;

        return new ForeignKey($foreignKey);
    }

    /**
     * Define a foreign key reference for this column.
     *
     * This is the SQL-oriented API. For conventional BIGINT foreign keys,
     * prefer {@see Table::foreignId()}.
     *
     * Example:
     *
     * ```php
     * $t->uuid('author_id')
     *     ->references('users', 'uuid');
     * ```
     *
     * @param string $table Referenced table name.
     * @param string $column Referenced column name.
     * @param string|null $name Foreign key constraint name.
     * @return ForeignKey
     */
    public function references(
        ?string $table = null,
        string $column = 'id',
    ): ForeignKey {
        return $this->constrained($table, $column);
    }


    protected function columnName(): string
    {
        return $this->column
            ->toDefinition()
            ->name;
    }

    protected function foreignKeyName(): string
    {
        return $this->columnName() . '_foreign';
    }

    protected function inferReferencedTable(): string
    {
        return (new NameInflector())
            ->tableFromForeignKey(
                $this->columnName()
            );
    }
}
