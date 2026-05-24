<?php

return [
    'schema' => 'database/schema.php',

    'database' => [
        'driver' => 'mysql',
        'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
        'port' => $_ENV['DB_PORT'] ?? 3306,
        'database' => $_ENV['DB_NAME'] ?? 'test',
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