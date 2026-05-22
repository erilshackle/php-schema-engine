<?php

namespace SchemaEngine\Operations\Column;

use SchemaEngine\Metadata\ColumnDefinition;
use SchemaEngine\Operations\Operation;

class AddColumn implements Operation
{
    public function __construct(
        public string $table,
        public ColumnDefinition $column
    ) {}
}