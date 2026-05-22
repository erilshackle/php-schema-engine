<?php

namespace SchemaEngine\Metadata;

class SchemaDefinition
{
    /**
     * @var array<string, TableDefinition>
     */
    public array $tables = [];

    public function addTable(
        TableDefinition $table
    ): void {
        $this->tables[$table->name] = $table;
    }

    public function getTable(
        string $name
    ): ?TableDefinition {
        return $this->tables[$name] ?? null;
    }

    public function hasTable(
        string $name
    ): bool {
        return isset($this->tables[$name]);
    }

    public function toArray(): array
    {
        return [
            'tables' => array_map(
                fn(TableDefinition $table) => $table->toArray(),
                $this->tables
            ),
        ];
    }


    public static function fromArray(
        array $data
    ): static {

        $schema = new static();

        foreach ($data['tables'] as $table) {

            $schema->addTable(
                TableDefinition::fromArray(
                    $table
                )
            );
        }

        return $schema;
    }
}
