<?php

namespace SchemaEngine\DSL;

use SchemaEngine\Metadata\ColumnDefinition;
use SchemaEngine\SQL\Expression\Expression;

class Column
{
    protected ColumnDefinition $definition;

    public function __construct(
        string $name,
        string $type
    ) {
        $this->definition = new ColumnDefinition(
            $name,
            $type
        );
    }

    public function nullable(
        bool $state = true
    ): static {
        $this->definition->nullable = $state;

        return $this;
    }


    public function autoIncrement(
        bool $state = true
    ): static {
        $this->definition->autoIncrement = $state;

        return $this;
    }

    public function default(
        mixed $value
    ): static {
        $this->definition->default = $value;

        return $this;
    }

    public function defaultRaw(
        string $expression
    ): static {

        $this->definition->default =
            new Expression($expression);

        return $this;
    }

    public function length(
        int $length
    ): static {
        $this->definition->length = $length;

        return $this;
    }

    public function precision(
        int $precision,
        ?int $scale = null
    ): static {

        $this->definition->precision = $precision;

        if ($scale !== null) {
            $this->definition->scale = $scale;
        }

        return $this;
    }

    public function scale(
        int $scale,
        ?int $precision = null
    ): static {

        $this->definition->scale = $scale;

        if ($precision !== null) {
            $this->definition->precision = $precision;
        }

        return $this;
    }

    public function defaultCurrentTimestamp(): static
    {
        return $this->defaultRaw(
            'CURRENT_TIMESTAMP'
        );
    }
    

    public function toDefinition(): ColumnDefinition
    {
        return $this->definition;
    }
}
