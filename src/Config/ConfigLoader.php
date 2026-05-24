<?php

namespace SchemaEngine\Config;

use RuntimeException;

class ConfigLoader
{
    public function __construct(
        protected string $rootPath
    ) {}

    public function load(
        string $file = 'schema-engine.php'
    ): array {
        $path = $this->path($file);

        if (!file_exists($path)) {
            throw new RuntimeException(
                "Config file not found: {$file}. Run: php bin/migrate --init"
            );
        }

        $config = require $path;

        if (!is_array($config)) {
            throw new RuntimeException(
                'Config file must return an array.'
            );
        }

        return $config;
    }

    public function create(
        string $file = 'schema-engine.php'
    ): bool {
        $path = $this->path($file);

        if (file_exists($path)) {
            return false;
        }

        file_put_contents(
            $path,
            $this->template()
        );

        return true;
    }

    public function path(
        string $path
    ): string {
        if ($this->isAbsolutePath($path)) {
            return $path;
        }

        return $this->rootPath
            . DIRECTORY_SEPARATOR
            . ltrim($path, '/\\');
    }

    protected function isAbsolutePath(
        string $path
    ): bool {
        return str_starts_with($path, '/')
            || preg_match('/^[A-Z]:\\\\/i', $path) === 1;
    }

    protected function template(): string
    {
        return <<<'PHP'
<?php

return [
    'schema' => 'database/schema.php',

    'database' => [
        'driver' => 'mysql',
        'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
        'port' => $_ENV['DB_PORT'] ?? 3306,
        'database' => $_ENV['DB_NAME'] ?? 'app',
        'username' => $_ENV['DB_USER'] ?? 'root',
        'password' => $_ENV['DB_PASS'] ?? '',
        'charset' => $_ENV['DB_CHARSET'] ?? 'utf8mb4',
    ],

    'generator' => [
        'models' => [
            'namespace' => 'App\\Models',
            'path' => 'app/Models',
            'extends' => null,
        ],
    ],
];

PHP;
    }
}