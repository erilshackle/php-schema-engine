<?php

use PHPUnit\Framework\TestCase;
use SchemaEngine\DSL\Schema;

class SerializationTest extends TestCase
{
    public function test_can_serialize_schema(): void
    {
        $schema = new Schema();

        $schema->table('users', function ($t) {

            $t->id();

            $t->string('email')
                ->unique();
        });

        $array = $schema
            ->toDefinition()
            ->toArray();

        $this->assertEquals(
            'varchar',
            $array['tables']['users']['columns']['email']['type']
        );

        $this->assertTrue(
            $schema->toDefinition()->getTable('users')?->hasIndex('email_unique')
        );
    }
}
