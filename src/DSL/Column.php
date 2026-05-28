<?php

namespace SchemaEngine\DSL;

use SchemaEngine\Metadata\ColumnDefinition;
use SchemaEngine\Metadata\ForeignKeyDefinition;
use SchemaEngine\Metadata\IndexDefinition;
use SchemaEngine\Metadata\TableDefinition;
use SchemaEngine\Naming\NameInflector;
use SchemaEngine\SQL\Expression\Expression;


/**
 * Fluent column schema builder.
 *
 * This class represents a single column being declared inside a table schema.
 * It exposes modifiers for nullability, default values, indexes, uniqueness,
 * primary keys, and foreign key references.
 *
 * Example:
 *
 * ```php
 * $t->string('email')
 *     ->unique();
 *
 * $t->timestamp('created_at')
 *     ->defaultCurrentTimestamp();
 *
 * $t->uuid('author_id')
 *     ->references('users', 'uuid');
 * ```
 */
class Column
{
    protected ColumnDefinition $definition;

    /**
     * Create a new column builder.
     *
     * @param string $name Column name.
     * @param string $type Internal column type.
     * @param TableDefinition $table Parent table definition.
     */
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

    /**
     * Mark the column as nullable or not nullable.
     *
     * @param bool $state Whether the column should allow NULL values.
     * @return static
     */
    public function nullable(
        bool $state = true
    ): static {
        $this->definition->nullable = $state;

        return $this;
    }

    /**
     * Mark the column as auto-incrementing.
     *
     * Usually used with integer primary keys.
     *
     * @param bool $state Whether the column should auto-increment.
     * @return static
     */
    public function autoIncrement(
        bool $state = true
    ): static {
        $this->definition->autoIncrement = $state;

        return $this;
    }

    /**
     * Set a literal default value for the column.
     *
     * Strings passed to this method are treated as string literals and will
     * be quoted by the SQL grammar.
     *
     * Use {@see defaultRaw()} for SQL expressions such as CURRENT_TIMESTAMP.
     *
     * @param mixed $value Default value.
     * @return static
     */
    public function default(
        mixed $value
    ): static {
        $this->definition->default = $value;

        return $this;
    }

    /**
     * Set a raw SQL expression as the default value.
     *
     * Example:
     *
     * ```php
     * $t->timestamp('created_at')
     *     ->defaultRaw('CURRENT_TIMESTAMP');
     * ```
     *
     * @param string $expression Raw SQL expression.
     * @return static
     */
    public function defaultRaw(
        string $expression
    ): static {

        $this->definition->default =
            new Expression($expression);

        return $this;
    }

    /**
     * Set the column length.
     *
     * Commonly used by VARCHAR and CHAR columns.
     *
     * @param int $length Column length.
     * @return static
     */
    public function length(
        int $length
    ): static {
        $this->definition->length = $length;

        return $this;
    }

    /**
     * Set numeric precision.
     *
     * Commonly used by DECIMAL columns.
     *
     * Optionally accepts scale as a second argument.
     *
     * @param int $precision Total number of digits.
     * @param int|null $scale Number of decimal digits.
     * @return static
     */
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

    /**
     * Set numeric scale.
     *
     * Commonly used by DECIMAL columns.
     *
     * Optionally accepts precision as a second argument.
     *
     * @param int $scale Number of decimal digits.
     * @param int|null $precision Total number of digits.
     * @return static
     */
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

    /**
     * Set CURRENT_TIMESTAMP as the default value.
     *
     * This is a shortcut for:
     *
     * ```php
     * ->defaultRaw('CURRENT_TIMESTAMP')
     * ```
     *
     * @return static
     */
    public function defaultCurrentTimestamp(): static
    {
        return $this->defaultRaw(
            'CURRENT_TIMESTAMP'
        );
    }

    //* META

    /**
     * Add a unique index for this column.
     *
     * This method stores the intent in the column metadata and registers
     * a corresponding table-level {@see IndexDefinition}.
     *
     * @return static
     */
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

    /**
     * Add a primary key index for this column.
     *
     * For regular auto-incrementing primary keys, prefer {@see Table::id()}.
     *
     * @return static
     */
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

    /**
     * Add a normal index for this column.
     *
     * This method stores the intent in the column metadata and registers
     * a corresponding table-level {@see IndexDefinition}.
     *
     * @return static
     */
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


    /**
     * Alias for {@see references()}.
     *
     * Kept for users who prefer the term "foreign" when declaring
     * non-conventional foreign key columns.
     *
     * @param string $table Referenced table name.
     * @param string $column Referenced column name.
     * @param string|null $name Foreign key constraint name.
     * @return ForeignKey
     */
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
        return (new NameInflector())->tableFromForeignKey(
            $this->definition->name
        );
    }

    /**
     * Get the internal column definition.
     *
     * @return ColumnDefinition
     */
    public function toDefinition(): ColumnDefinition
    {
        return $this->definition;
    }
}
