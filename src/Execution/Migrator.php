<?php

namespace SchemaEngine\Execution;

use PDO;
use Throwable;
use RuntimeException;
use SchemaEngine\Operations\Operation;
use SchemaEngine\Operations\Table\DropTable;
use SchemaEngine\Operations\Column\DropColumn;
use SchemaEngine\SQL\SQLGenerator;

class Migrator
{
    protected SQLGenerator $generator;

    public function __construct(
        protected PDO $pdo,
        ?SQLGenerator $generator = null
    ) {
        $this->generator =
            $generator ?? new SQLGenerator();
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

            $sql =
                $this->generator
                    ->generate($operation);

            $sqlList[] = $sql;
        }

        if ($dryRun) {
            return $sqlList;
        }

        try {

            $this->pdo->beginTransaction();

            foreach ($sqlList as $sql) {

                $this->pdo->exec($sql);
            }

            $this->pdo->commit();

        } catch (Throwable $e) {

            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
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