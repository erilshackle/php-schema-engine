<?php

namespace SchemaEngine\Operations\Index;

use SchemaEngine\Operations\Operation;

class DropIndex implements Operation
{
    public function __construct(
        public string $table,
        public string $name
    ) {}
}