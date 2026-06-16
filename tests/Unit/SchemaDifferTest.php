<?php

use PHPUnit\Framework\TestCase;
use SchemaEngine\Diff\SchemaDiffer;
use SchemaEngine\DSL\Schema;
use SchemaEngine\Operations\ForeignKey\AddForeignKey;
use SchemaEngine\Operations\Index\AddIndex;

class SchemaDifferTest extends TestCase
{
    public function test_detects_missing_index(): void
    {
        $current = new Schema();

        $current->table('users', function ($t) {
            $t->id();
            $t->string('email');
        });

        $desired = new Schema();

        $desired->table('users', function ($t) {
            $t->id();
            $t->string('email')->unique();
        });

        $differ = new SchemaDiffer();

        $operations = $differ->diff(
            $current->toDefinition(),
            $desired->toDefinition()
        );

        $this->assertContainsOnlyInstancesOf(
            AddIndex::class,
            $operations
        );
    }

    public function test_detects_missing_foreign_key(): void
    {
        $current = new Schema();

        $current->table('users', function ($t) {
            $t->id();
        });

        $current->table('posts', function ($t) {
            $t->id();
            $t->bigInt('user_id')->index();
        });

        $desired = new Schema();

        $desired->table('users', function ($t) {
            $t->id();
        });

        $desired->table('posts', function ($t) {
            $t->id();

            $t->foreign('user_id')
                ->references()
                ->cascadeOnDelete();
        });

        $differ = new SchemaDiffer();

        $operations = $differ->diff(
            $current->toDefinition(),
            $desired->toDefinition()
        );

        $hasAddForeignKey = false;

        foreach ($operations as $operation) {
            if ($operation instanceof AddForeignKey) {
                $hasAddForeignKey = true;
                break;
            }
        }

        $this->assertTrue($hasAddForeignKey);
    }
}
