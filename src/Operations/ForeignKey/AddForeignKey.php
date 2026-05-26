<?php

namespace SchemaEngine\Operations\ForeignKey;

use SchemaEngine\Metadata\ForeignKeyDefinition;
use SchemaEngine\Operations\Operation;

class AddForeignKey implements Operation
{
    public function __construct(
        public string $table,
        public ForeignKeyDefinition $foreignKey
    ) {}
}