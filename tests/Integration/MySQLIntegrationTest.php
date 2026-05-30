<?php

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use SchemaEngine\Database\Introspection\MySQLIntrospector;
use SchemaEngine\Diff\SchemaDiffer;
use SchemaEngine\DSL\Schema;
use SchemaEngine\Execution\Migrator;
use SchemaEngine\Metadata\SchemaDefinition;


#[Group('integration')]
class MySQLIntegrationTest extends TestCase
{
    protected ?PDO $pdo = null;


    protected function env(
        string $key,
        mixed $default = null
    ): mixed {
        $value = getenv($key);

        return $value !== false
            ? $value
            : $default;
    }

    protected function setUp(): void
    {
        if (!$this->env('DB_NAME', 'schema_engine_test')) {
            $this->markTestSkipped('Set SCHEMA_ENGINE_TEST_DB=1 to run integration tests.');
        }

        $this->pdo = new PDO(
            sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                $this->env('DB_HOST', '127.0.0.1'),
                $this->env('DB_PORT', 3306),
                $this->env('DB_NAME', 'schema_engine_test')
            ),
            $this->env('DB_USER') ?? 'root',
            $this->env('DB_PASS') ?? '',
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );

        $this->resetDatabase();
    }

    protected function tearDown(): void
    {
        if ($this->pdo instanceof PDO) {
            $this->resetDatabase();
        }
    }

    #[Group('integration')]
    public function test_can_create_related_tables_and_introspect_them(): void
    {
        $desired = $this->schema(function (Schema $schema) {
            $schema->table('users', function ($t) {
                $t->id();
                $t->string('email')->unique();
                $t->timestamps();
            });

            $schema->table('posts', function ($t) {
                $t->id();

                $t->foreign('user_id')
                    ->constrained()
                    ->cascadeOnDelete();

                $t->string('title');
                $t->timestamps();
            });
        });

        $this->migrate($desired);

        $current = $this->introspect();

        $this->assertTrue($current->hasTable('users'));
        $this->assertTrue($current->hasTable('posts'));

        $posts = $current->getTable('posts');

        $this->assertTrue($posts->hasColumn('user_id'));
        $this->assertTrue($posts->hasIndex('user_id_index'));
        $this->assertTrue($posts->hasForeignKey('user_id_foreign'));
    }

    #[Group('integration')]
    public function test_unique_index_is_enforced_by_database(): void
    {
        $desired = $this->schema(function ($schema) {
            $schema->table('users', function ($t) {
                $t->id();
                $t->string('email')->unique();
            });
        });

        $this->migrate($desired);

        $this->pdo->exec("
            INSERT INTO users (email)
            VALUES ('a@test.com')
        ");

        $this->expectException(PDOException::class);

        $this->pdo->exec("
            INSERT INTO users (email)
            VALUES ('a@test.com')
        ");
    }

    #[Group('integration')]
    public function test_foreign_key_cascade_delete_works(): void
    {
        $desired = $this->schema(function ($schema) {
            $schema->table('users', function ($t) {
                $t->id();
                $t->string('email')->unique();
            });

            $schema->table('posts', function ($t) {
                $t->id();

                $t->foreign('user_id')
                    ->constrained()
                    ->cascadeOnDelete();

                $t->string('title');
            });
        });

        $this->migrate($desired);

        $this->pdo->exec("
            INSERT INTO users (email)
            VALUES ('a@test.com')
        ");

        $userId = (int) $this->pdo->lastInsertId();

        $this->pdo->exec("
            INSERT INTO posts (user_id, title)
            VALUES ({$userId}, 'Hello')
        ");

        $this->pdo->exec("
            DELETE FROM users
            WHERE id = {$userId}
        ");

        $count = $this->pdo
            ->query("SELECT COUNT(*) FROM posts")
            ->fetchColumn();

        $this->assertSame(0, (int) $count);
    }

    #[Group('integration')]
    public function test_can_add_column_to_existing_table(): void
    {
        $initial = $this->schema(function ($schema) {
            $schema->table('users', function ($t) {
                $t->id();
                $t->string('email');
            });
        });

        $this->migrate($initial);

        $desired = $this->schema(function ($schema) {
            $schema->table('users', function ($t) {
                $t->id();
                $t->string('email');
                $t->string('name')->nullable();
            });
        });

        $this->migrate($desired);

        $current = $this->introspect();

        $this->assertTrue(
            $current
                ->getTable('users')
                ->hasColumn('name')
        );
    }

    #[Group('integration')]
    public function test_can_add_foreign_key_to_existing_table(): void
    {
        $initial = $this->schema(function ($schema) {
            $schema->table('users', function ($t) {
                $t->id();
                $t->string('email');
            });

            $schema->table('posts', function ($t) {
                $t->id();
                $t->bigInt('user_id')->index();
                $t->string('title');
            });
        });

        $this->migrate($initial);

        $desired = $this->schema(function ($schema) {
            $schema->table('users', function ($t) {
                $t->id();
                $t->string('email');
            });

            $schema->table('posts', function ($t) {
                $t->id();

                $t->foreign('user_id')
                    ->constrained()
                    ->cascadeOnDelete();

                $t->string('title');
            });
        });

        $this->migrate($desired);

        $current = $this->introspect();

        $this->assertTrue(
            $current
                ->getTable('posts')
                ->hasForeignKey('user_id_foreign')
        );
    }

    protected function schema(callable $callback): SchemaDefinition
    {
        $schema = new Schema();

        $callback($schema);

        return $schema->toDefinition();
    }

    protected function migrate(SchemaDefinition $desired): void
    {
        $current = $this->introspect();

        $operations = (new SchemaDiffer())->diff(
            $current,
            $desired
        );

        (new Migrator($this->pdo))->run(
            $operations,
            dryRun: false,
            force: true
        );
    }

    protected function introspect(): SchemaDefinition
    {
        return (new MySQLIntrospector(
            $this->pdo,
            database: $this->env('DB_NAME') ?? 'schema_engine_test'
        ))->getSchema();
    }

    protected function resetDatabase(): void
    {
        if (!$this->pdo instanceof PDO) {
            return;
        }

        $this->pdo->exec('SET FOREIGN_KEY_CHECKS=0');

        $tables = $this->pdo
            ->query('SHOW TABLES')
            ->fetchAll(PDO::FETCH_COLUMN);

        foreach ($tables as $table) {
            $this->pdo->exec("DROP TABLE IF EXISTS `{$table}`");
        }

        $this->pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    }
}
