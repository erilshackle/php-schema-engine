<?php

namespace SchemaEngine\Diff;

use SchemaEngine\Metadata\SchemaDefinition;
use SchemaEngine\Operations\Column\AddColumn;
use SchemaEngine\Operations\Column\DropColumn;
use SchemaEngine\Operations\Column\ModifyColumn;
use SchemaEngine\Operations\Column\RenameColumn;
use SchemaEngine\Operations\ForeignKey\AddForeignKey;
use SchemaEngine\Operations\Index\AddIndex;
use SchemaEngine\Operations\Index\DropIndex;
use SchemaEngine\Operations\Operation;
use SchemaEngine\Operations\Table\CreateTable;
use SchemaEngine\Operations\Table\DropTable;


class SchemaDiffer
{
    protected ColumnComparator $comparator;
    protected IndexComparator $indexComparator;
    protected ForeignKeyComparator $foreignKeyComparator;
    protected SchemaTableSorter $tableSorter;
    protected DiffReport $report;

    public function __construct()
    {
        $this->comparator = new ColumnComparator();
        $this->indexComparator = new IndexComparator();
        $this->foreignKeyComparator = new ForeignKeyComparator();
        $this->tableSorter = new SchemaTableSorter();
        $this->report = new DiffReport();
    }

    /**
     * @return Operation[]
     */
    public function diff(
        SchemaDefinition $current,
        SchemaDefinition $desired
    ): array {

        $operations = [];

        // tables
        $operations = array_merge(
            $operations,
            $this->diffTables($current, $desired)
        );

        // columns
        $operations = array_merge(
            $operations,
            $this->diffColumns($current, $desired)
        );

        // Indexes
        $operations = array_merge(
            $operations,
            $this->diffIndexes($current, $desired)
        );

        // foreign
        $operations = array_merge(
            $operations,
            $this->diffForeignKeys($current, $desired)
        );

        return $operations;
    }

    public function report(): DiffReport
    {
        return $this->report;
    }

    protected function diffTables(
        SchemaDefinition $current,
        SchemaDefinition $desired
    ): array {

        $this->report?->warn(
            'V1 automatically adds missing indexes and foreign keys, but does not modify existing indexes or foreign keys.
Destructive index changes require --force.'

        );

        $operations = [];

        // novas tabelas
        foreach ($this->tableSorter->sortForCreation($desired) as $table) {
            $tableName = $table->name;
            if (!$current->hasTable($tableName)) {
                $operations[] = new CreateTable($table);
            }
        }

        // tabelas removidas
        foreach ($current->tables as $tableName => $table) {
            if (!$desired->hasTable($tableName)) {
                $operations[] = new DropTable($tableName);
            }
        }

        return $operations;
    }

    protected function diffColumns(
        SchemaDefinition $current,
        SchemaDefinition $desired
    ): array {

        $operations = [];

        foreach ($desired->tables as $tableName => $desiredTable) {

            $currentTable = $current->getTable($tableName);

            // tabela nova
            if (!$currentTable) {
                continue;
            }

            // $renameData =  $this->detectRenamedColumns(
            //     $currentTable,
            //     $desiredTable,
            //     $tableName
            // );

            // $operations = array_merge($operations, $renameData['operations']);

            $usedCurrent = $renameData['usedCurrent'] ?? [];

            $usedDesired = $renameData['usedDesired'] ?? [];

            // colunas novas/modificadas
            foreach ($desiredTable->columns as $columnName => $desiredColumn) {

                if (in_array($columnName, $usedDesired, true)) {
                    continue;
                }

                $currentColumn = $currentTable->getColumn($columnName);

                // nova coluna
                if (!$currentColumn) {

                    $operations[] =
                        new AddColumn(
                            $tableName,
                            $desiredColumn
                        );

                    continue;
                }

                // coluna modificada
                if (
                    !$this->comparator->equals(
                        $currentColumn,
                        $desiredColumn
                    )
                ) {

                    $operations[] =
                        new ModifyColumn(
                            $tableName,
                            $currentColumn,
                            $desiredColumn
                        );
                }
            }

            // colunas removidas
            foreach ($currentTable->columns as $columnName => $currentColumn) {

                if (in_array($columnName, $usedCurrent, true)) {
                    continue;
                }

                if (!$desiredTable->hasColumn($columnName)) {

                    $operations[] =
                        new DropColumn(
                            $tableName,
                            $columnName
                        );
                }
            }
        }

        return $operations;
    }

    protected function diffIndexes(
        SchemaDefinition $current,
        SchemaDefinition $desired
    ): array {

        $operations = [];

        foreach ($desired->tables as $tableName => $desiredTable) {

            $currentTable = $current->getTable($tableName);

            if (!$currentTable) {
                continue;
            }

            /**
             * ADD / WARNING
             */
            foreach (
                $desiredTable->indexes
                as $indexName => $desiredIndex
            ) {

                $currentIndex = $currentTable->getIndex($indexName);

                // missing index
                if (!$currentIndex) {

                    $operations[] = new AddIndex(
                        $tableName,
                        $desiredIndex
                    );

                    continue;
                }

                // existing but different
                if (!$this->indexComparator->equals(
                    $currentIndex,
                    $desiredIndex
                )) {

                    $this->report->warn(
                        "Index '{$indexName}' on table '{$tableName}' differs from desired schema and was ignored."
                    );
                }
            }

            /**
             * DROP
             */
            foreach (
                $currentTable->indexes
                as $indexName => $currentIndex
            ) {

                if (
                    !$desiredTable->hasIndex(
                        $indexName
                    )
                ) {

                    $operations[] = new DropIndex(
                        $tableName,
                        $indexName
                    );
                }
            }
        }

        return $operations;
    }

    protected function diffForeignKeys(
        SchemaDefinition $current,
        SchemaDefinition $desired
    ): array {

        $operations = [];

        foreach ($desired->tables as $tableName => $desiredTable) {

            $currentTable = $current->getTable($tableName);

            if (!$currentTable) {
                continue;
            }

            foreach ($desiredTable->foreignKeys as $foreignKeyName => $desiredForeignKey) {

                $currentForeignKey = $currentTable->getForeignKey(
                    $foreignKeyName
                );

                if (!$currentForeignKey) {

                    $operations[] = new AddForeignKey(
                        $tableName,
                        $desiredForeignKey
                    );

                    continue;
                }

                if (
                    !$this->foreignKeyComparator->equals(
                        $currentForeignKey,
                        $desiredForeignKey
                    )
                ) {
                    $this->report->warn(
                        "Foreign key '{$foreignKeyName}' on table '{$tableName}' differs from desired schema and was ignored."
                    );
                }
            }
        }

        return $operations;
    }

    protected function detectRenamedColumns(
        $currentTable,
        $desiredTable,
        string $tableName
    ): array {

        $operations = [];

        $usedCurrent = [];

        $usedDesired = [];

        foreach (
            $currentTable->columns
            as $currentName => $currentColumn
        ) {

            foreach (
                $desiredTable->columns
                as $desiredName => $desiredColumn
            ) {

                if ($currentName === $desiredName) {
                    continue;
                }

                if (
                    $this->comparator->equals(
                        $currentColumn,
                        $desiredColumn
                    )
                ) {

                    $operations[] = new RenameColumn(
                        $tableName,
                        $currentName,
                        $desiredName
                    );

                    $usedCurrent[] = $currentName;
                    $usedDesired[] = $desiredName;

                    break;
                }
            }
        }

        return [
            'operations' => $operations,
            'usedCurrent' => $usedCurrent,
            'usedDesired' => $usedDesired,
        ];
    }
}
