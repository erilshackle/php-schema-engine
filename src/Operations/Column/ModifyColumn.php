<?php

namespace SchemaEngine\Operations\Column;

use SchemaEngine\Metadata\ColumnDefinition;
use SchemaEngine\Operations\Operation;

class ModifyColumn implements Operation
{
    public function __construct(
        public string $table,
        public ColumnDefinition $current,
        public ColumnDefinition $desired
    ) {}
}