<?php

use PHPUnit\Framework\TestCase;
use SchemaEngine\Diff\SchemaTableSorter;
use SchemaEngine\DSL\Schema;

class SchemaTableSorterTest extends TestCase
{
    public function test_sorts_tables_by_foreign_key_dependencies(): void
    {
        $schema = new Schema();

        $schema->table('comments', function ($t) {
            $t->id();
            $t->foreign('post_id')->constrained();
        });

        $schema->table('posts', function ($t) {
            $t->id();
            $t->foreign('user_id')->constrained();
        });

        $schema->table('users', function ($t) {
            $t->id();
        });

        $sorter = new SchemaTableSorter();

        $tables = $sorter->sortForCreation(
            $schema->toDefinition()
        );

        $names = array_map(
            fn ($table) => $table->name,
            $tables
        );

        $this->assertSame(
            ['users', 'posts', 'comments'],
            $names
        );
    }

    public function test_sorts_tables_for_deletion_in_reverse_order(): void
    {
        $schema = new Schema();

        $schema->table('posts', function ($t) {
            $t->id();
            $t->foreign('user_id')->constrained();
        });

        $schema->table('users', function ($t) {
            $t->id();
        });

        $sorter = new SchemaTableSorter();

        $tables = $sorter->sortForDeletion(
            $schema->toDefinition()
        );

        $names = array_map(
            fn ($table) => $table->name,
            $tables
        );

        $this->assertSame(
            ['posts', 'users'],
            $names
        );
    }
}