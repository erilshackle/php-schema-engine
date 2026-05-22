<?php

namespace SchemaEngine\Operations\Index;

use SchemaEngine\Metadata\IndexDefinition;
use SchemaEngine\Operations\Operation;

class AddIndex implements Operation
{
    public function __construct(
        public string $table,
        public IndexDefinition $index
    ) {}
}