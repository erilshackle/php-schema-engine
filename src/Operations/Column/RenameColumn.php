<?php

namespace SchemaEngine\Operations\Column;

use SchemaEngine\Operations\Operation;

class RenameColumn implements Operation
{
    public function __construct(
        public string $table,
        public string $from,
        public string $to
    ) {}
}