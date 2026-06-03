<?php

namespace SchemaEngine\Metadata;

class CheckDefinition
{
    public function __construct(
        public string $name,
        public string $expression
    ) {}

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'expression' => $this->expression,
        ];
    }

    public static function fromArray(array $data): static
    {
        return new static(
            $data['name'],
            $data['expression']
        );
    }
}