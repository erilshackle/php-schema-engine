<?php

namespace SchemaEngine\DSL;

use SchemaEngine\Metadata\ForeignKeyDefinition;

class ForeignKey
{
    public function __construct(
        protected ForeignKeyDefinition $definition
    ) {}

    public function onDelete(
        string $action
    ): static {
        $this->definition->onDelete = strtoupper($action);

        return $this;
    }

    public function onUpdate(
        string $action
    ): static {
        $this->definition->onUpdate = strtoupper($action);

        return $this;
    }

    public function cascadeOnDelete(): static
    {
        return $this->onDelete('CASCADE');
    }

    public function cascadeOnUpdate(): static
    {
        return $this->onUpdate('CASCADE');
    }

    public function restrictOnDelete(): static
    {
        return $this->onDelete('RESTRICT');
    }

    public function restrictOnUpdate(): static
    {
        return $this->onUpdate('RESTRICT');
    }

    public function nullOnDelete(): static
    {
        return $this->onDelete('SET NULL');
    }

    public function nullOnUpdate(): static
    {
        return $this->onUpdate('SET NULL');
    }
}
