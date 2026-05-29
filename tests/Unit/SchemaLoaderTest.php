<?php

use PHPUnit\Framework\TestCase;
use SchemaEngine\Loader\SchemaLoader;

class SchemaLoaderTest extends TestCase
{
    public function test_can_load_schema_file(): void
    {
        $loader = new SchemaLoader();

        $schema = $loader->load(
            __DIR__ . '/../../database/schema.php'
        );

        $this->assertTrue(
            $schema->hasTable('users')
        );

        $users = $schema->getTable('users');

        $this->assertTrue(
            $users->hasColumn('email')
        );

        $email = $users->getColumn('email');

        $this->assertTrue(
            $email->meta['unique']
        );
    }
}