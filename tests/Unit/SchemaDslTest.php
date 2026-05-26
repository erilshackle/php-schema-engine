<?php

use PHPUnit\Framework\TestCase;
use SchemaEngine\DSL\Schema;

class SchemaDslTest extends TestCase
{
    public function test_can_build_table_with_unique_and_foreign_key(): void
    {
        $schema = new Schema();

        $schema->table('users', function ($t) {
            $t->id();
            $t->string('email')->unique();
        });

        $schema->table('posts', function ($t) {
            $t->id();

            $t->foreignId('user_id')
                ->references()
                ->cascadeOnDelete();

            $t->string('title');
        });

        $definition = $schema->toDefinition();

        $users = $definition->getTable('users');
        $posts = $definition->getTable('posts');

        $this->assertNotNull($users);
        $this->assertNotNull($posts);

        $this->assertTrue($users->hasIndex('email_unique'));
        $this->assertTrue($posts->hasIndex('user_id_index'));
        $this->assertTrue($posts->hasForeignKey('user_id_foreign'));

        $fk = $posts->getForeignKey('user_id_foreign');

        $this->assertSame('users', $fk->referencedTable);
        $this->assertSame(['id'], $fk->referencedColumns);
        $this->assertSame('CASCADE', $fk->onDelete);
    }
}
