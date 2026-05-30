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
    protected MySQLGrammar $grammar;

    public function __construct(
        protected string $driver = 'mysql'
    ) {
        $this->grammar ??= match ($this->driver) {
            'sqlite' => new SQLiteGrammar(),
            'postgres' => new MySQLGrammar(),
            default => new MySQLGrammar(),
        };
    }

    public function generate(
        Operation $operation
    ): string {

        return trim($this->grammar->compile(
            $operation
        )) . ';';
    }

    public function reverse(
        Operation $operation
    ): ?string {

        $reverseOperation =
            $this->reverseOperation($operation);

        if (!$reverseOperation) {
            return null;
        }

        return $this->generate($reverseOperation);
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

            $operation instanceof ModifyColumn => new ModifyColumn(
                $operation->table,
                $operation->desired,
                $operation->current
            ),

            $operation instanceof CreateTable => new DropTable(
                $operation->table->name
            ),

            $operation instanceof DropTable => null,

            $operation instanceof AddIndex => new DropIndex(
                $operation->table,
                $operation->index->name
            ),

            $operation instanceof DropIndex => null,

            $operation instanceof AddForeignKey => null,

            default => null,
        };
    }
}
