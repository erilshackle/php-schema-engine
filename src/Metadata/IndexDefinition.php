<?php

namespace SchemaEngine\Metadata;

class IndexDefinition
{
    public string $name;

    public array $columns = [];

    public bool $unique = false;

    public bool $primary = false;

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

            'unique' => $this->unique,

            'primary' => $this->primary,
        ];
    }

    public static function fromArray(
        array $data
    ): static {

        $index = new static(
            $data['name'],
            $data['columns']
        );

        $index->unique =
            $data['unique'];

        $index->primary =
            $data['primary'];

        return $index;
    }
}