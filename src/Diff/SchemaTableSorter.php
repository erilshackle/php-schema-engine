<?php

namespace SchemaEngine\Diff;

use RuntimeException;
use SchemaEngine\Metadata\SchemaDefinition;
use SchemaEngine\Metadata\TableDefinition;

class SchemaTableSorter
{

    /**
     * @return TableDefinition[]
     */
    public function sortForCreation(
        SchemaDefinition $schema
    ): array {

        $tables = $schema->tables;

        $visited = [];

        $temp = [];

        $sorted = [];

        foreach ($tables as $table) {
            $this->visit(
                $table,
                $tables,
                $visited,
                $temp,
                $sorted
            );
        }

        return $sorted;
    }

    /**
     * Summary of sortForCreation
     * @param SchemaDefinition $schema_or_sorted_array
     */
    public function sortForDeletion(
        SchemaDefinition|array $schema_or_sorted_array
    ): array {

        if (!is_array($schema_or_sorted_array)) {
            $schema_or_sorted_array = $this->sortForCreation($schema_or_sorted_array);
        }
        return array_reverse($schema_or_sorted_array);
    }

    protected function visit(
        TableDefinition $table,
        array $tables,
        array &$visited,
        array &$temp,
        array &$sorted
    ): void {

        $name = $table->name;

        if (isset($visited[$name])) {
            return;
        }

        if (isset($temp[$name])) {

            throw new RuntimeException(
                "Circular foreign key dependency detected involving '{$name}'."
            );
        }

        $temp[$name] = true;

        foreach ($table->foreignKeys as $foreignKey) {

            $dependency =
                $foreignKey->referencedTable;

            if (
                isset($tables[$dependency])
            ) {

                $this->visit(
                    $tables[$dependency],
                    $tables,
                    $visited,
                    $temp,
                    $sorted
                );
            }
        }

        unset($temp[$name]);

        $visited[$name] = true;

        $sorted[] = $table;
    }
}
