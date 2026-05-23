<?php

use PHPUnit\Framework\TestCase;
use SchemaEngine\Diff\SchemaDiffer;
use SchemaEngine\DSL\Schema;
use SchemaEngine\Metadata\SchemaDefinition;
use SchemaEngine\Operations\Column\AddColumn;

class SchemaDifferTest extends TestCase
{
    public function test_detects_new_column(): void
    {
        $current = new Schema();

        $current->table('users', function ($t) {
            $t->id();
        });

        $desired = new Schema();

        $desired->table('users', function ($t) {

            $t->id();

            $t->string('email');

        });

        $differ = new SchemaDiffer();

        $operations = $differ->diff(
            $current->toDefinition(),
            $desired->toDefinition()
        );

        $this->assertCount(1, $operations);

        $this->assertInstanceOf(
            AddColumn::class,
            $operations[0]
        );
    }
}