<?php

use PHPUnit\Framework\TestCase;
use SchemaEngine\Database\Introspection\MySQLIntrospector;

class MySQLIntrospectorTest extends TestCase
{

    /**
     * test_can_read_database_schema
     * @group integration
     * @return void
     */
    public function test_can_read_database_schema(): void
    {
        $this->markTestSkipped('Database connection not available in test environment');

        $anyLocalhostDbName = "test";

        $pdo = new PDO(
            'mysql:host=127.0.0.1;dbname=' . $anyLocalhostDbName,
            'root',
            ''
        );

        $introspector = new MySQLIntrospector(
            $pdo
        );

        $schema = $introspector->getSchema();

        $this->assertNotEmpty(
            $schema->tables
        );
    }
}
