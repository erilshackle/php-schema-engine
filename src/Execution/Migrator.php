<?php

namespace SchemaEngine\Execution;

use PDO;
use RuntimeException;
use SchemaEngine\Operations\Operation;
use SchemaEngine\Operations\Table\DropTable;
use SchemaEngine\Operations\Column\DropColumn;
use SchemaEngine\Operations\Index\DropIndex;
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

        $rollbackSqlList = [];

        foreach ($operations as $operation) {
            $rollbackSqlList[] =
                $this->generator->reverse($operation);
        }

        if ($dryRun) {
            return $sqlList;
        }

        $batch = $this->repository->nextBatch();

        foreach ($sqlList as $index => $sql) {

            $this->pdo->exec($sql);

            $operation = basename(str_replace('\\', '/', get_class($operations[$index])));
            $this->repository->log($operation, $batch);

            $this->repository->logOperation(
                $batch,
                get_class($operations[$index]),
                $sql,
                $rollbackSqlList[$index]
            );
        }

        return $sqlList;
    }


    /**
     * rollback:
     * CreateTable -> DropTable
     * AddColumn -> DropColumn
     * ModifyColumn -> ModifyColumn antigo
     * AddIndex -> DropIndex

     * @param bool $dryRun
     * @throws RuntimeException
     * @return array
     */
    public function rollback(
        bool $dryRun = false
    ): array {

        $batch = $this->repository->lastBatch();

        if (!$batch) {
            return [];
        }

        $operations =
            $this->repository->rollbackOperations(
                $batch
            );

        $sqlList = [];

        foreach ($operations as $operation) {

            if (!$operation['rollback_sql']) {
                throw new RuntimeException(
                    "Operation {$operation['operation']} cannot be rolled back automatically."
                );
            }

            $sqlList[] =
                $operation['rollback_sql'];
        }

        if ($dryRun) {
            return $sqlList;
        }

        foreach ($sqlList as $sql) {
            $this->pdo->exec($sql);
        }

        $this->repository->deleteBatch($batch);

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
            || $operation instanceof DropIndex
        ) {

            throw new RuntimeException(
                'Destructive operations require force=true'
            );
        }
    }
}
