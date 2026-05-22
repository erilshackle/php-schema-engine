<?php

namespace SchemaEngine\Operations\Table;

use SchemaEngine\Metadata\TableDefinition;
use SchemaEngine\Operations\Operation;

class CreateTable implements Operation
{
    public function __construct(
        public TableDefinition $table
    ) {}
}