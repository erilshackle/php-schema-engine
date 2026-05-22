<?php

namespace SchemaEngine\Metadata;

class TableDefinition
{
    public string $name;
    public array $indexes = [];


    /**
     * @var array<string, ColumnDefinition>
     */
    public array $columns = [];

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function addColumn(
        ColumnDefinition $column
    ): void {
        $this->columns[$column->name] = $column;
    }

    public function addIndex(
        IndexDefinition $index
    ): void {

        $this->indexes[$index->name] =
            $index;
    }

    public function getColumn(
        string $name
    ): ?ColumnDefinition {
        return $this->columns[$name] ?? null;
    }

    public function hasColumn(
        string $name
    ): bool {
        return isset($this->columns[$name]);
    }

    public function hasIndex(
        string $name
    ): bool {

        return isset(
            $this->indexes[$name]
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,

            'columns' => array_map(
                fn(ColumnDefinition $column) => $column->toArray(),
                $this->columns
            ),

            'indexes' => array_map(
                fn($index) => $index->toArray(),
                $this->indexes
            ),
        ];
    }

    public static function fromArray(
        array $data
    ): static {

        $table = new static(
            $data['name']
        );

        foreach ($data['columns'] as $column) {

            $table->addColumn(
                ColumnDefinition::fromArray(
                    $column
                )
            );
        }

        foreach ($data['indexes'] ?? [] as $index) {

            $table->addIndex(
                IndexDefinition::fromArray(
                    $index
                )
            );
        }

        return $table;
    }
}
