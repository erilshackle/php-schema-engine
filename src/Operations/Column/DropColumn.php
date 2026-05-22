<?php

namespace SchemaEngine\Operations\Column;

use SchemaEngine\Operations\Operation;

class DropColumn implements Operation
{
    public function __construct(
        public string $table,
        public string $column
    ) {}
}