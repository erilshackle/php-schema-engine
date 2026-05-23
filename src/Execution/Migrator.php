<?php

namespace SchemaEngine\Execution;

use PDO;
use RuntimeException;
use SchemaEngine\Operations\Operation;
use SchemaEngine\Operations\Table\DropTable;
use SchemaEngine\Operations\Column\DropColumn;
use SchemaEngine\SQL\SQLGenerator;

class Migrator
{
    protected SQLGenerator $generator;
    protected MigrationRepository $repository;

    public function __construct(
        protected PDO $pdo,
        ?SQLGenerator $generator = null,
        ?MigrationRepository $repository = null
    ) {
        $this->generator =
            $generator ?? new SQLGenerator();

        $this->repository = $repository ?? new MigrationRepository($pdo);
    }

    /**
     * @param Operation[] $operations
     */
    public function run(
        array $operations,
        bool $dryRun = false,
        bool $force = false
    ): array {

        $sqlList = [];

        foreach ($operations as $operation) {

            $this->guardOperation(
                $operation,
                $force
            );

            $sql = $this->generator
                ->generate($operation);

            $sqlList[] = $sql;
        }

        if ($dryRun) {
            return $sqlList;
        }

        $batch = $this->repository->nextBatch();

        foreach ($sqlList as $index => $sql) {

            $this->pdo->exec($sql);

            $this->repository->log(
                get_class($operations[$index]),
                $batch
            );
        }

        return $sqlList;
    }

    protected function guardOperation(
        Operation $operation,
        bool $force
    ): void {

        if ($force) {
            return;
        }

        if (
            $operation instanceof DropTable
            || $operation instanceof DropColumn
        ) {

            throw new RuntimeException(
                'Destructive operations require force=true'
            );
        }
    }
}
