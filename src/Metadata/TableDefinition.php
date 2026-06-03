<?php

namespace SchemaEngine\Metadata;

class TableDefinition
{
    public string $name;
    public array $indexes = [];
    public array $foreignKeys = [];
    public array $checks = [];


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
        $this->hydrateColumnMetadata($column);
    }

    public function addIndex(
        IndexDefinition $index
    ): void {

        $this->indexes[$index->name] = $index;
    }

    public function getColumn(
        string $name
    ): ?ColumnDefinition {
        return $this->columns[$name] ?? null;
    }

    public function getIndex(
        string $name
    ): ?IndexDefinition {

        return $this->indexes[$name]
            ?? null;
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

    public function addCheck(
        CheckDefinition $check
    ): void {
        $this->checks[$check->name] = $check;
    }

    public function addForeignKey(
        ForeignKeyDefinition $foreignKey
    ): void {

        $this->foreignKeys[$foreignKey->name] = $foreignKey;
    }

    public function removeForeignKey(
        string $name
    ): void {

        unset(
            $this->foreignKeys[$name]
        );
    }

    public function getForeignKey(
        string $name
    ): ?ForeignKeyDefinition {

        return $this->foreignKeys[$name] ?? null;
    }

    public function hasForeignKey(
        string $name
    ): bool {

        return isset($this->foreignKeys[$name]);
    }

    public function addIndexFromArray(array $data): void
    {
        $index = IndexDefinition::fromArray($data);

        $this->addIndex($index);
    }


    protected function hydrateColumnMetadata(
        ColumnDefinition $column
    ): void {

        if ($column->meta['primary'] ?? false) {

            $index = new IndexDefinition(
                'primary',
                [$column->name]
            );

            $index->primary = true;

            $this->addIndex($index);
        }

        if ($column->meta['unique'] ?? false) {

            $index = new IndexDefinition(
                "{$column->name}_unique",
                [$column->name]
            );

            $index->unique = true;

            $this->addIndex($index);
        }

        if ($column->meta['index'] ?? false) {

            $index = new IndexDefinition(
                "{$column->name}_index",
                [$column->name]
            );

            $this->addIndex($index);
        }
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

            'checks' => array_map(
                fn($check) => $check->toArray(),
                $this->checks
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

        foreach ($data['checks'] ?? [] as $check) {
            $table->addCheck(
                CheckDefinition::fromArray($check)
            );
        }

        return $table;
    }
}
