<?php

namespace SchemaEngine\DSL;

/**
 * Fluent foreign key column builder.
 *
 * This builder is returned by {@see Table::foreign()} and provides
 * a convenient API for defining foreign key columns and their references.
 *
 * By default, a foreign column is created as:
 *
 * ```php
 * $t->foreign('user_id');
 * ```
 *
 * Which produces:
 *
 * ```sql
 * user_id BIGINT NOT NULL
 * ```
 *
 * Additional type modifiers may be applied before defining
 * the foreign key relationship:
 *
 * ```php
 * $t->foreign('author_id')
 *     ->uuid()
 *     ->references('users', 'uuid');
 * ```
 *
 * This builder delegates column-level operations to the underlying
 * {@see Column} instance and relationship creation to
 * {@see Column::constrained()}.
 */
class ForeignColumn
{
    /**
     * Create a new foreign column builder.
     *
     * @param Column $column Underlying column builder.
     */
    public function __construct(
        protected Column $column
    ) {}

    /**
     * Set the foreign key column type to INT.
     *
     * Example:
     *
     * ```php
     * $t->foreign('country_id')
     *     ->int()
     *     ->references('countries');
     * ```
     *
     * @return static
     */
    public function int(): static
    {
        $this->column
            ->toDefinition()
            ->type = 'int';

        return $this;
    }

    /**
     * Set the foreign key column type to BIGINT.
     *
     * This is the default type used by {@see Table::foreign()}.
     *
     * @return static
     */
    public function bigInt(): static
    {
        $this->column
            ->toDefinition()
            ->type = 'bigint';

        return $this;
    }

    /**
     * Set the foreign key column type to UUID.
     *
     * Internally this is represented as:
     *
     * ```sql
     * VARCHAR(36)
     * ```
     *
     * Example:
     *
     * ```php
     * $t->foreign('author_id')
     *     ->uuid()
     *     ->references('users', 'uuid');
     * ```
     *
     * @return static
     */
    public function uuid(): static
    {
        $this->column
            ->toDefinition()
            ->type = 'varchar';

        $this->column->length(36);

        return $this;
    }

    /**
     * Set the foreign key column type to VARCHAR.
     *
     * Example:
     *
     * ```php
     * $t->foreign('external_id')
     *     ->string(100)
     *     ->references('providers', 'code');
     * ```
     *
     * @param int $length Column length.
     * @return static
     */
    public function string(
        int $length = 255
    ): static {
        $this->column
            ->toDefinition()
            ->type = 'varchar';

        $this->column->length($length);

        return $this;
    }

    /**
     * Mark the foreign key column as nullable.
     *
     * Example:
     *
     * ```php
     * $t->foreign('manager_id')
     *     ->nullable()
     *     ->references('users');
     * ```
     *
     * @param bool $state Whether NULL values should be allowed.
     * @return static
     */
    public function nullable(
        bool $state = true
    ): static {
        $this->column->nullable($state);

        return $this;
    }

    /**
     * Set a default value for the foreign key column.
     *
     * Example:
     *
     * ```php
     * $t->foreign('country_id')
     *     ->default(1)
     *     ->references('countries');
     * ```
     *
     * @param mixed $value Default value.
     * @return static
     */
    public function default(
        mixed $value
    ): static {
        $this->column->default($value);

        return $this;
    }

    /**
     * Define the referenced table and column.
     *
     * If no table is provided, the table name will be inferred
     * from the foreign key column name.
     *
     * Examples:
     *
     * ```php
     * $t->foreign('user_id')
     *     ->references('users');
     * ```
     *
     * ```php
     * $t->foreign('author_id')
     *     ->uuid()
     *     ->references('users', 'uuid');
     * ```
     *
     * ```php
     * $t->foreign('user_id')
     *     ->references();
     * ```
     *
     * The last example infers:
     *
     * ```text
     * user_id → users(id)
     * ```
     *
     * @param string|null $table Referenced table name.
     * @param string $column Referenced column name.
     * @param string|null $name Foreign key constraint name.
     * @return ForeignKey Fluent foreign key constraint builder.
     */
    public function references(
        ?string $table,
        string $column = 'id',
        ?string $name = null
    ): ForeignKey {
        return $this->column->constrained(
            $table,
            $column,
            $name
        );
    }

    public function constrained(
        ?string $table = null,
        string $column = 'id',
        ?string $name = null
    ): ForeignKey {
        return $this->references($table, $column, $name);
    }

    /**
     * Get the underlying column builder.
     *
     * This may be useful for advanced customization or internal usage.
     *
     * @return Column
     */
    public function column(): Column
    {
        return $this->column;
    }
}
