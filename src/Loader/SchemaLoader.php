<?php

namespace SchemaEngine\Loader;

use RuntimeException;
use SchemaEngine\DSL\Schema;
use SchemaEngine\Metadata\SchemaDefinition;

class SchemaLoader
{
    public function load(
        string $path
    ): SchemaDefinition {

        if (!file_exists($path)) {
            throw new RuntimeException(
                "Schema file not found: {$path}"
            );
        }

        $callback = require $path;

        if (!is_callable($callback)) {
            throw new RuntimeException(
                'Schema file must return a callable.'
            );
        }

        $schema = new Schema();

        $callback($schema);

        return $schema->toDefinition();
    }
}