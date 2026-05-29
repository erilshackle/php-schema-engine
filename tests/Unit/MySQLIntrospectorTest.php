<?php

use PHPUnit\Framework\TestCase;
use SchemaEngine\Database\Introspection\MySQLIntrospector;

class MySQLIntrospectorTest extends TestCase
{

    /**
     * test_can_read_database_schema
     * @return void
     * @ignore
     */
    public function test_can_read_database_schema(): void
    {

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
