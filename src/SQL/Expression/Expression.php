<?php

namespace SchemaEngine\SQL\Expression;

class Expression
{
    public function __construct(
        protected string $value
    ) {}

    public function getValue(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}