<?php

namespace SchemaEngine\SQL;

use SchemaEngine\Operations\Column\AddColumn;
use SchemaEngine\Operations\Column\DropColumn;
use SchemaEngine\Operations\Column\ModifyColumn;
use SchemaEngine\Operations\ForeignKey\AddForeignKey;
use SchemaEngine\Operations\Index\AddIndex;
use SchemaEngine\Operations\Index\DropIndex;
use SchemaEngine\Operations\Operation;
use SchemaEngine\Operations\Table\CreateTable;
use SchemaEngine\Operations\Table\DropTable;
use SchemaEngine\SQL\Grammar\MySQLGrammar;
use SchemaEngine\SQL\Grammar\SQLiteGrammar;

class SQLGenerator
{
    protected MySQLGrammar|SQLiteGrammar $grammar;

    public function __construct(
        protected string $driver = 'mysql'
    ) {
        $this->grammar = match ($this->driver) {
            'sqlite' => new SQLiteGrammar(),
            'postgres' => new MySQLGrammar(),
            default => new MySQLGrammar(),
        };
    }

    /**
     * Generate normalized SQL statements for an operation.
     *
     * @return string[]
     */
    public function generate(
        Operation $operation
    ): array {

        return $this->normalize(
            $this->grammar->compile($operation)
        );
    }

    /**
     * Generate rollback SQL statements for an operation.
     *
     * @return string[]
     */
    public function reverse(
        Operation $operation
    ): array {

        $reverseOperation =
            $this->reverseOperation($operation);

        if (!$reverseOperation) {
            return [];
        }

        return $this->generate($reverseOperation);
    }

    /**
     * Normalize grammar output into a flat SQL statement list.
     *
     * @param string|array<int, string> $sql
     * @return string[]
     */
    protected function normalize(
        string|array $sql
    ): array {

        $items = is_array($sql)
            ? $sql
            : [$sql];

        $statements = [];

        foreach ($items as $statement) {

            $statement = trim($statement);

            if ($statement === '') {
                continue;
            }

            $statements[] =
                rtrim($statement, ';') . ';';
        }

        return $statements;
    }

    protected function reverseOperation(
        Operation $operation
    ): ?Operation {

        return match (true) {

            $operation instanceof AddColumn =>
                new DropColumn(
                    $operation->table,
                    $operation->column->name
                ),

            $operation instanceof DropColumn => null,

            $operation instanceof ModifyColumn =>
                new ModifyColumn(
                    $operation->table,
                    $operation->desired,
                    $operation->current
                ),

            $operation instanceof CreateTable =>
                new DropTable(
                    $operation->table->name
                ),

            $operation instanceof DropTable => null,

            $operation instanceof AddIndex =>
                new DropIndex(
                    $operation->table,
                    $operation->index->name
                ),

            $operation instanceof DropIndex => null,

            $operation instanceof AddForeignKey => null,

            default => null,
        };
    }
}