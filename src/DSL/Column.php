<?php

namespace SchemaEngine\DSL;

use SchemaEngine\Metadata\ColumnDefinition;
use SchemaEngine\Metadata\ForeignKeyDefinition;
use SchemaEngine\Metadata\IndexDefinition;
use SchemaEngine\Metadata\TableDefinition;
use SchemaEngine\SQL\Expression\Expression;

class Column
{
    protected ColumnDefinition $definition;

    public function __construct(
        string $name,
        string $type,
        protected TableDefinition $table
    ) {
        $this->definition = new ColumnDefinition(
            $name,
            $type
        );

        $this->table = $table;
    }

    public function nullable(
        bool $state = true
    ): static {
        $this->definition->nullable = $state;

        return $this;
    }

    public function autoIncrement(
        bool $state = true
    ): static {
        $this->definition->autoIncrement = $state;

        return $this;
    }

    public function default(
        mixed $value
    ): static {
        $this->definition->default = $value;

        return $this;
    }

    public function defaultRaw(
        string $expression
    ): static {

        $this->definition->default =
            new Expression($expression);

        return $this;
    }

    public function length(
        int $length
    ): static {
        $this->definition->length = $length;

        return $this;
    }

    public function precision(
        int $precision,
        ?int $scale = null
    ): static {

        $this->definition->precision = $precision;

        if ($scale !== null) {
            $this->definition->scale = $scale;
        }

        return $this;
    }

    public function scale(
        int $scale,
        ?int $precision = null
    ): static {

        $this->definition->scale = $scale;

        if ($precision !== null) {
            $this->definition->precision = $precision;
        }

        return $this;
    }

    public function defaultCurrentTimestamp(): static
    {
        return $this->defaultRaw(
            'CURRENT_TIMESTAMP'
        );
    }

    // META

    public function unique(): static
    {
        $this->definition->meta['unique'] = true;

        $index = new IndexDefinition(
            "{$this->definition->name}_unique",
            [$this->definition->name]
        );

        $index->unique = true;

        $this->table->addIndex($index);

        return $this;
    }

    public function primary(): static
    {
        $this->definition->meta['primary'] = true;

        $index = new IndexDefinition(
            'primary',
            [$this->definition->name]
        );

        $index->primary = true;

        $this->table->addIndex($index);

        return $this;
    }

    public function index(): static
    {
        $this->definition->meta['index'] = true;

        $index = new IndexDefinition(
            "{$this->definition->name}_index",
            [$this->definition->name]
        );

        $this->table->addIndex($index);

        return $this;
    }


    public function foreign(
        string $table,
        string $column = 'id',
        ?string $name = null
    ): ForeignKey {

        $name ??= "{$this->definition->name}_foreign";

        $foreignKey = new ForeignKeyDefinition(
            $name,
            [$this->definition->name]
        );

        $foreignKey->referencedTable = $table;
        $foreignKey->referencedColumns = [$column];

        $this->table->addForeignKey($foreignKey);

        return new ForeignKey($foreignKey);
    }

    // public function constrained(
    //     ?string $table = null,
    //     string $column = 'id'
    // ): ForeignKey {
    //     $table ??= $this->inferReferencedTable();
    //     return $this->foreign($table, $column);
    // }

    protected function inferReferencedTable(): string
    {
        $name = $this->definition->name;

        if (str_ends_with($name, '_id')) {
            return substr($name, 0, -3) . 's';
        }

        return $name;
    }

    public function toDefinition(): ColumnDefinition
    {
        return $this->definition;
    }
}
