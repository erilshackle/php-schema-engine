<?php

namespace SchemaEngine\DSL;

use SchemaEngine\Metadata\ForeignKeyDefinition;
use SchemaEngine\Metadata\TableDefinition;

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
        $name = $this->columnName();

        if (str_ends_with($name, '_id')) {
            return substr($name, 0, -3) . 's';
        }

        return $name;
    }
}
