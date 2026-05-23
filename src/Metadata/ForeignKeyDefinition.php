<?php

namespace SchemaEngine\Metadata;

class ForeignKeyDefinition
{
    public string $name;

    public array $columns = [];

    public string $referencedTable;

    public array $referencedColumns = [];

    public ?string $onDelete = null;

    public ?string $onUpdate = null;

    public function __construct(
        string $name,
        array $columns
    ) {

        $this->name = $name;

        $this->columns = $columns;
    }

    public function toArray(): array
    {
        return [

            'name' => $this->name,

            'columns' => $this->columns,

            'referencedTable' =>
                $this->referencedTable,

            'referencedColumns' =>
                $this->referencedColumns,

            'onDelete' =>
                $this->onDelete,

            'onUpdate' =>
                $this->onUpdate,
        ];
    }

    public static function fromArray(
        array $data
    ): static {

        $fk = new static(
            $data['name'],
            $data['columns']
        );

        $fk->referencedTable =
            $data['referencedTable'];

        $fk->referencedColumns =
            $data['referencedColumns'];

        $fk->onDelete =
            $data['onDelete'];

        $fk->onUpdate =
            $data['onUpdate'];

        return $fk;
    }
}