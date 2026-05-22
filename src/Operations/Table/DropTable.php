<?php

namespace SchemaEngine\Operations\Table;

use SchemaEngine\Operations\Operation;

class DropTable implements Operation
{
    public function __construct(
        public string $table
    ) {}
}