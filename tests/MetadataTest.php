<?php

use PHPUnit\Framework\TestCase;
use SchemaEngine\Metadata\ColumnDefinition;
use SchemaEngine\Metadata\SchemaDefinition;
use SchemaEngine\Metadata\TableDefinition;

class MetadataTest extends TestCase
{
    public function test_can_build_schema_definition(): void
    {
        $schema = new SchemaDefinition();

        $table = new TableDefinition('users');

        $table->addColumn(
            new ColumnDefinition('id', 'int')
        );

        $schema->addTable($table);

        $this->assertTrue(
            $schema->hasTable('users')
        );

        $this->assertTrue(
            $schema
                ->getTable('users')
                ->hasColumn('id')
        );
    }
}