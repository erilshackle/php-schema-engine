<?php

use PHPUnit\Framework\TestCase;
use SchemaEngine\DSL\Schema;
use SchemaEngine\Operations\Table\CreateTable;
use SchemaEngine\SQL\SQLGenerator;

class MySQLGrammarTest extends TestCase
{
    public function test_generates_create_table_with_foreign_key(): void
    {
        $schema = new Schema();

        $schema->table('posts', function ($t) {
            $t->id();

            $t->foreignId('user_id')
                ->references('users')
                ->cascadeOnDelete();

            $t->string('title');
        });

        $posts = $schema
            ->toDefinition()
            ->getTable('posts');

        $sql = (new SQLGenerator())->generate(
            new CreateTable($posts)
        );

        $this->assertStringContainsString(
            'CREATE TABLE `posts`',
            $sql
        );

        $this->assertStringContainsString(
            'KEY `user_id_index` (`user_id`)',
            $sql
        );

        $this->assertStringContainsString(
            'CONSTRAINT `user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE',
            $sql
        );
    }
}