<?php

namespace SchemaEngine\DSL;

use SchemaEngine\Metadata\IndexDefinition;
use SchemaEngine\Metadata\TableDefinition;

/**
 * Fluent table schema builder.
 *
 * This class is used inside schema definitions to declare columns,
 * indexes, shortcuts, and foreign key fields for a database table.
 *
 * Example:
 *
 * ```php
 * $schema->table('users', function (Table $t) {
 *     $t->id();
 *     $t->string('email')->unique();
 *     $t->timestamps();
 * });
 * ```
 */
class Table
{
    protected TableDefinition $definition;

    /**
     * Create a new table builder.
     *
     * @param string $name Table name.
     */
    public function __construct(
        string $name
    ) {
        $this->definition = new TableDefinition(
            $name
        );
    }

    /**
     * Add a column to the table.
     *
     * @param string $name Column name.
     * @param string $type Internal column type.
     * @return Column
     */
    protected function addColumn(
        string $name,
        string $type
    ): Column {

        $column = new Column(
            $name,
            $type,
            $this->definition
        );

        $this->definition->addColumn(
            $column->toDefinition()
        );

        return $column;
    }

    /**
     * Add an auto-incrementing BIGINT primary key.
     *
     * @param string $name Primary key column name.
     * @return Column
     */
    public function id(
        string $name = 'id'
    ): Column {

        return $this->bigInt($name)
            ->autoIncrement()
            ->primary();
    }

    /**
     * Add a UUID primary key column.
     *
     * @param string $name Primary key column name.
     * @return Column
     */
    public function uuidPrimary(
        string $name = 'id'
    ): Column {

        return $this->uuid($name)
            ->primary();
    }

    /**
     * Add a VARCHAR column.
     *
     * @param string $name Column name.
     * @param int $length Column length.
     * @return Column
     */
    public function string(
        string $name,
        int $length = 255
    ): Column {
        $column = $this->addColumn(
            $name,
            'varchar'
        );

        $column->length($length);

        return $column;
    }

    /**
     * Add an INT column.
     *
     * @param string $name Column name.
     * @return Column
     */
    public function int(
        string $name
    ): Column {

        return $this->addColumn(
            $name,
            'int'
        );
    }

    /**
     * Add a BIGINT column.
     *
     * @param string $name Column name.
     * @return Column
     */
    public function bigInt(
        string $name
    ): Column {

        return $this->addColumn(
            $name,
            'bigint'
        );
    }

    /**
     * Add a FLOAT column.
     *
     * @param string $name Column name.
     * @return Column
     */
    public function float(
        string $name
    ): Column {

        return $this->addColumn(
            $name,
            'float'
        );
    }

    /**
     * Add a DOUBLE column.
     *
     * @param string $name Column name.
     * @return Column
     */
    public function double(
        string $name
    ): Column {

        return $this->addColumn(
            $name,
            'double'
        );
    }

    /**
     * Add a DECIMAL column.
     *
     * @param string $name Column name.
     * @param int $precision Total number of digits.
     * @param int $scale Number of decimal digits.
     * @return Column
     */
    public function decimal(
        string $name,
        int $precision = 10,
        int $scale = 2
    ): Column {

        $column = $this->addColumn(
            $name,
            'decimal'
        );

        $column
            ->precision($precision)
            ->scale($scale);

        return $column;
    }

    /**
     * Add a BOOLEAN column.
     *
     * @param string $name Column name.
     * @return Column
     */
    public function boolean(
        string $name
    ): Column {
        return $this->addColumn(
            $name,
            'boolean'
        );
    }

    /**
     * Add a TEXT column.
     *
     * @param string $name Column name.
     * @return Column
     */
    public function text(
        string $name
    ): Column {
        return $this->addColumn(
            $name,
            'text'
        );
    }

    /**
     * Add a UUID column.
     *
     * Stored as VARCHAR(36).
     *
     * @param string $name Column name.
     * @return Column
     */
    public function uuid(
        string $name
    ): Column {

        return $this->string($name, 36);
    }

    /**
     * Add a JSON column.
     *
     * @param string $name Column name.
     * @return Column
     */
    public function json(
        string $name
    ): Column {

        return $this->addColumn(
            $name,
            'json'
        );
    }

    /**
     * Add a DATETIME column.
     *
     * @param string $name Column name.
     * @return Column
     */
    public function datetime(
        string $name
    ): Column {

        return $this->addColumn(
            $name,
            'datetime'
        );
    }

    /**
     * Add a TIMESTAMP column.
     *
     * @param string $name Column name.
     * @return Column
     */
    public function timestamp(
        string $name
    ): Column {

        return $this->addColumn(
            $name,
            'timestamp'
        );
    }

    /**
     * Add a LONGTEXT column.
     *
     * @param string $name Column name.
     * @return Column
     */
    public function longText(
        string $name
    ): Column {

        return $this->addColumn(
            $name,
            'longtext'
        );
    }

    /**
     * Add a CHAR column.
     *
     * @param string $name Column name.
     * @param int $length Column length.
     * @return Column
     */
    public function char(
        string $name,
        int $length = 1
    ): Column {

        $column = $this->addColumn(
            $name,
            'char'
        );

        $column->length($length);

        return $column;
    }


    /**
     * Add a created_at timestamp column with CURRENT_TIMESTAMP default.
     *
     * @return Column
     */
    public function createdAt(): Column
    {
        return $this->timestamp('created_at')
            ->defaultCurrentTimestamp();
    }

    /**
     * Add an updated_at timestamp column with CURRENT_TIMESTAMP default.
     *
     * @return Column
     */
    public function updatedAt(): Column
    {
        return $this->timestamp('updated_at')
            ->defaultCurrentTimestamp();
    }

    /**
     * Add a nullable deleted_at timestamp column.
     *
     * @return Column
     */
    public function deletedAt(): Column
    {
        return $this->timestamp('deleted_at')
            ->nullable();
    }

    //** SHORTCUT FIELDS

    /**
     * Add created_at and updated_at timestamp columns.
     *
     * @return void
     */
    public function timestamps(): void
    {
        $this->createdAt();

        $this->updatedAt();
    }

    /**
     * Add a nullable deleted_at timestamp column for soft deletes.
     *
     * @return void
     */
    public function softDeletes(): void
    {
        $this->timestamp('deleted_at')
            ->nullable()
            ->default(null);
    }

    /**
     * Add a nullable remember token column.
     *
     * @param string $name Column name.
     * @return Column
     */
    public function rememberToken(
        string $name = 'remember_token'
    ): Column {

        return $this->string($name, 100)
            ->nullable();
    }

    /**
     * Add an indexed status column.
     *
     * @param string $name Column name.
     * @param string $default Default status value.
     * @return Column
     */
    public function status(
        string $name = 'status',
        string $default = 'active'
    ): Column {

        return $this->string($name)
            ->default($default)
            ->index();
    }

    /**
     * Add a unique slug column.
     *
     * @param string $name Column name.
     * @return Column
     */
    public function slug(
        string $name = 'slug'
    ): Column {

        return $this->string($name)
            ->unique();
    }

    // INDEXES

    /**
     * Add a BIGINT foreign id column with an index.
     *
     * The returned bridge object can define the referenced table/column
     * and foreign key actions.
     *
     * Example:
     *
     * ```php
     * $t->foreignId('user_id')
     *     ->constrained()
     *     ->cascadeOnDelete();
     * ```
     *
     * @param string $name Column name.
     * @return ForeignIdColumn
     */
    public function foreignId(
        string $name
    ): ForeignIdColumn {

        $column = $this->bigInt($name)
            ->index();

        return new ForeignIdColumn(
            $column,
            $this->definition
        );
    }

    /**
     * Add a table-level index.
     *
     * @param string|array<int, string> $columns Indexed column or columns.
     * @param string|null $name Index name. Generated automatically when omitted.
     * @return void
     */
    public function index(
        string|array $columns,
        ?string $name = null
    ): void {

        $columns = (array) $columns;

        $name ??=
            implode('_', $columns)
            . '_index';

        $index = new IndexDefinition(
            $name,
            $columns
        );

        $this->definition->addIndex(
            $index
        );
    }

    /**
     * Add a table-level unique index.
     *
     * @param string|array<int, string> $columns Unique column or columns.
     * @param string|null $name Index name. Generated automatically when omitted.
     * @return void
     */
    public function unique(
        string|array $columns,
        ?string $name = null
    ): void {

        $columns = (array) $columns;

        $name ??=
            implode('_', $columns)
            . '_unique';

        $index = new IndexDefinition(
            $name,
            $columns
        );

        $index->unique = true;

        $this->definition->addIndex(
            $index
        );
    }

    /**
     * Add a table-level primary key.
     *
     * Useful for composite primary keys.
     *
     * @param string|array<int, string> $columns Primary key column or columns.
     * @param string $name Internal index name.
     * @return void
     */
    public function primary(
        string|array $columns,
        string $name = 'primary'
    ): void {

        $columns = (array) $columns;

        $index = new IndexDefinition(
            $name,
            $columns
        );

        $index->primary = true;

        $this->definition->addIndex(
            $index
        );
    }


    /**
     * Get the internal table definition.
     *
     * @return TableDefinition
     */
    public function toDefinition(): TableDefinition
    {
        return $this->definition;
    }
}
