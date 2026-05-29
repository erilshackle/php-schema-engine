<?php

use PHPUnit\Framework\TestCase;
use SchemaEngine\Metadata\ColumnDefinition;
use SchemaEngine\Operations\Column\AddColumn;
use SchemaEngine\SQL\SQLGenerator;

class SQLGeneratorTest extends TestCase
{
    public function test_generates_add_column_sql(): void
    {
        $column = new ColumnDefinition(
            'email',
            'varchar'
        );

        $column->length = 255;

        $operation = new AddColumn(
            'users',
            $column
        );

        $generator = new SQLGenerator();

        $sql = $generator->generate(
            $operation
        );

        $this->assertStringContainsString(
            'ALTER TABLE',
            $sql
        );

        $this->assertStringContainsString(
            'ADD COLUMN',
            $sql
        );
    }
}