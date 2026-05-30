<?php

namespace SchemaEngine\Operations\Table;

use SchemaEngine\Metadata\TableDefinition;
use SchemaEngine\Operations\Operation;

class RecreateTable implements Operation
{
    public function __construct(
        public TableDefinition $current,
        public TableDefinition $desired
    ) {}
}