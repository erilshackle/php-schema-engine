<?php

namespace SchemaEngine\DSL;

use SchemaEngine\Metadata\IndexDefinition;
use SchemaEngine\Metadata\TableDefinition;

class Table
{
    protected TableDefinition $definition;

    public function __construct(
        string $name
    ) {
        $this->definition = new TableDefinition(
            $name
        );
    }

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

    public function id(
        string $name = 'id'
    ): Column {

        return $this->bigInt($name)
            ->autoIncrement()
            ->primary();
    }

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

    public function int(
        string $name
    ): Column {

        return $this->addColumn(
            $name,
            'int'
        );
    }

    public function bigInt(
        string $name
    ): Column {

        return $this->addColumn(
            $name,
            'bigint'
        );
    }

    public function float(
        string $name
    ): Column {

        return $this->addColumn(
            $name,
            'float'
        );
    }

    public function double(
        string $name
    ): Column {

        return $this->addColumn(
            $name,
            'double'
        );
    }

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

    public function boolean(
        string $name
    ): Column {
        return $this->addColumn(
            $name,
            'boolean'
        );
    }

    public function text(
        string $name
    ): Column {
        return $this->addColumn(
            $name,
            'text'
        );
    }

    public function uuid(
        string $name
    ): Column {

        return $this->string($name, 36);
    }

    public function json(
        string $name
    ): Column {

        return $this->addColumn(
            $name,
            'json'
        );
    }

    public function datetime(
        string $name
    ): Column {

        return $this->addColumn(
            $name,
            'datetime'
        );
    }

    public function timestamp(
        string $name
    ): Column {

        return $this->addColumn(
            $name,
            'timestamp'
        );
    }

    public function longText(
        string $name
    ): Column {

        return $this->addColumn(
            $name,
            'longtext'
        );
    }

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



    public function createdAt(): Column
    {
        return $this->timestamp('created_at')
            ->defaultCurrentTimestamp();
    }

    public function updatedAt(): Column
    {
        return $this->timestamp('updated_at')
            ->defaultCurrentTimestamp();
    }

    //** SHORTCUT FIELDS

    public function timestamps(): void
    {
        $this->createdAt();

        $this->updatedAt();
    }


    public function softDeletes(): void
    {
        $this->timestamp('deleted_at')
            ->nullable()
            ->default(null);
    }

    public function rememberToken(): void
    {
        $this->text('remember_token')
            ->nullable()
            ->default(null);
    }

    public function foreignId(
        string $name,
        ?string $references = null
    ): Column {

        $field = $this->bigInt($name)
            ->index();

        if ($references) {
            $field->foreign($references);
        }

        return $field;
    }


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


    public function toDefinition(): TableDefinition
    {
        return $this->definition;
    }
}
