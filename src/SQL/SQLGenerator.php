<?php

namespace SchemaEngine\SQL;

use SchemaEngine\Operations\Operation;
use SchemaEngine\SQL\Grammar\MySQLGrammar;

class SQLGenerator
{
    protected MySQLGrammar $grammar;

    public function __construct(
        ?MySQLGrammar $grammar = null
    ) {
        $this->grammar =
            $grammar ?? new MySQLGrammar();
    }

    public function generate(
        Operation $operation
    ): string {

        return trim($this->grammar->compile(
            $operation
        )) . ';';
    }
}
