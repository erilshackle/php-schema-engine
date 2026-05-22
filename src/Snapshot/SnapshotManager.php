<?php

namespace SchemaEngine\Snapshot;

use SchemaEngine\Metadata\SchemaDefinition;

class SnapshotManager
{
    public function save(
        SchemaDefinition $schema,
        string $path
    ): void {

        file_put_contents(
            $path,
            json_encode(
                $schema->toArray(),
                JSON_PRETTY_PRINT
            )
        );
    }

    public function exists(
        string $path
    ): bool {

        return file_exists($path);
    }

    public function load(
        string $path
    ): SchemaDefinition {

        return SchemaDefinition::fromArray(
            json_decode(
                file_get_contents($path),
                true
            )
        );
    }
}
