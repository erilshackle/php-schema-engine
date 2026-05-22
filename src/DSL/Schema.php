<?php

namespace SchemaEngine\DSL;

use SchemaEngine\Metadata\SchemaDefinition;

class Schema
{
    protected SchemaDefinition $definition;

    public function __construct()
    {
        $this->definition = new SchemaDefinition();
    }

    /**
     * Summary of table
     * @param string $name
     * @param callable(Table): void $callback
     * @return Schema
     */
    public function table(
        string $name,
        callable $callback
    ): static {

        $table = new Table($name);

        $callback($table);

        $this->definition->addTable(
            $table->toDefinition()
        );

        return $this;
    }

    public function toDefinition(): SchemaDefinition
    {
        return $this->definition;
    }
}