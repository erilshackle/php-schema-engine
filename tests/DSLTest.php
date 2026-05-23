<?php

use PHPUnit\Framework\TestCase;
use SchemaEngine\DSL\Schema;

class DSLTest extends TestCase
{
    public function test_can_build_schema_using_dsl(): void
    {
        $schema = new Schema();

        $schema->table('users', function ($t) {

            $t->id();

            $t->string('name')
                ->nullable();

            $t->string('email')
                ->unique();

            $t->int('age')
                ->default(0);

        });

        $definition = $schema->toDefinition();

        $this->assertTrue(
            $definition->hasTable('users')
        );

        $users = $definition->getTable('users');

        $this->assertTrue(
            $users->hasColumn('email')
        );

        $email = $users->getColumn('email');

        $this->assertTrue(
            $email->meta['unique']
        );
    }
}