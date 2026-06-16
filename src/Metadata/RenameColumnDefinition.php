<?php

namespace SchemaEngine\Metadata;

class RenameColumnDefinition
{
    public function __construct(
        public string $table,
        public string $from,
        public string $to
    ) {}

    public function toArray(): array
    {
        return [
            'table' => $this->table,
            'from' => $this->from,
            'to' => $this->to,
        ];
    }
}