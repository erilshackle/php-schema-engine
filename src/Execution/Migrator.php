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

            $this->guardOperation($operation, $force);

            $rollbackSql = $this->generator->reverse($operation);

            foreach ($this->generator->generate($operation) as $statement) {
                $sqlList[] = [
                    'sql' => $statement,
                    'operation' => get_class($operation),
                    'rollback' => implode(";\n", $rollbackSql),
                ];
            }
        }

        if ($dryRun) {
            return array_column($sqlList, 'sql');
        }

        $batch = $this->repository->nextBatch();

        foreach ($sqlList as $item) {

            $this->pdo->exec($item['sql']);

            $this->repository->log(
                $item['operation'],
                $batch
            );

            $this->repository->logOperation(
                $batch,
                $item['operation'],
                $item['sql'],
                $item['rollback']
            );
        }

        return array_column($sqlList, 'sql');
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
