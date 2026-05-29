<?php

namespace SchemaEngine\Console;

class ProjectInitializer
{
    public function __construct(
        protected string $root
    ) {}

    public function run(
        bool $force = false
    ): array {
        $created = [];

        $this->ensureDirectory('database', $created);
        $this->ensureDirectory('storage', $created);

        $this->write(
            'database/schema.php',
            $this->schemaTemplate(),
            $created,
            $force
        );

        $this->write(
            'database/bootstrap.sql',
            $this->bootstrapTemplate(),
            $created,
            $force
        );

        $this->write(
            'storage/.gitkeep',
            '',
            $created,
            $force
        );

        return $created;
    }

    protected function ensureDirectory(
        string $path,
        array &$created
    ): void {
        $full = $this->path($path);

        if (!is_dir($full)) {
            mkdir($full, 0777, true);
            $created[] = $path . '/';
        }
    }

    protected function write(
        string $path,
        string $content,
        array &$created,
        bool $force
    ): void {
        $full = $this->path($path);

        if (file_exists($full) && !$force) {
            return;
        }

        file_put_contents($full, $content);

        $created[] = $path;
    }

    protected function path(
        string $path
    ): string {
        return $this->root
            . DIRECTORY_SEPARATOR
            . ltrim($path, '/\\');
    }

    protected function schemaTemplate(): string
    {
        return <<<'PHP'
<?php

return function ($schema) {

    $schema->table('users', function ($t) {
        $t->id();

        $t->string('name');

        $t->string('email')
            ->unique();

        $t->timestamps();
    });

};

PHP;
    }

    protected function bootstrapTemplate(): string
    {
        return <<<'SQL'
-- Bootstrap data
--
-- This file is optional and is not executed automatically by PHP Schema Engine.
--
-- Example:
--
-- INSERT INTO users (
--     name,
--     email
-- ) VALUES (
--     'Administrator',
--     'admin@example.com'
-- );

SQL;
    }
}