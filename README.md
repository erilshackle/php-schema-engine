# PHP Schema Engine

A modern schema-first migration engine for PHP.

PHP Schema Engine lets you define your database structure using a clean PHP DSL, compare it against the current database schema, generate SQL automatically, and apply changes through a lightweight CLI.

> Current version: `0.3.0`

---

## Features

- Schema-first database definition
- Fluent PHP DSL
- MySQL/MariaDB support
- Automatic schema introspection
- Table and column diffing
- Partial index diffing
- SQL generation
- Migration execution
- Migration history
- Model generation
- Dry-run mode
- Destructive operation protection
- Database reset support
- Snapshot generation
- Foreign key support
- Debug trace mode

---

## Installation

```bash
composer require erilshackle/php-schema-engine
```

For local development:

```bash
composer install
```

---

## Initialization

Generate the default configuration file:

```bash
php bin/migrate --init
```

This creates:

```txt
schema-engine.php
database/schema.php
database/bootstrap.sql
database/snapshots/
```

---

## Configuration

Generated config example:

```php
<?php

return [

    // Schema file path
    'schema' => '/database/schema.php',

    // Database connection configuration
    'database' => [
        'driver' => 'mysql',
        'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
        'port' => $_ENV['DB_PORT'] ?? 3306,
        'database' => $_ENV['DB_NAME'] ?? 'app',
        'username' => $_ENV['DB_USER'] ?? 'root',
        'password' => $_ENV['DB_PASS'] ?? '',
    ],

    // Model generator setup
    'generator' => [
        'models' => [
            'enabled' => false,
            'namespace' => 'App\\Models',
            'path' => '/app/Models',
            'extends' => null,
        ],
    ],
];
```

Optional bootstrap file:

```txt
bootstrap.php
```

Useful for:

* loading `.env`
* bootstrapping frameworks
* loading helpers
* custom runtime setup

---

## Basic Usage

Create a schema file:

```php
<?php

use SchemaEngine\DSL\Schema;
use SchemaEngine\DSL\Table;

return function (Schema $schema) {

    $schema->table('users', function (Table $t) {

        $t->id();

        $t->string('name');

        $t->string('email')
            ->unique();

        $t->timestamps();
    });

};
```

Run a dry-run first:

```bash
php bin/migrate --dry-run
```

Apply the migration:

```bash
php bin/migrate --yes
```

---

## Defining Tables

```php
$schema->table('users', function ($t) {

    $t->id();

    $t->string('name');

    $t->string('email')
        ->unique();

    $t->int('age')
        ->default(0);

    $t->timestamps();
});
```

---

## Column Types

```php
$t->id();

$t->string('name');

$t->char('code', 2);

$t->text('body');

$t->longText('content');

$t->int('age');

$t->bigInt('views');

$t->float('rating');

$t->double('score');

$t->decimal('price', 10, 2);

$t->boolean('active');

$t->datetime('published_at');

$t->timestamp('created_at');

$t->json('meta');

$t->uuid('uuid');
```

---

## Column Modifiers

```php
$t->string('email')
    ->unique();

$t->string('slug')
    ->index();

$t->string('name')
    ->nullable();

$t->int('age')
    ->default(0);

$t->timestamp('created_at')
    ->defaultCurrentTimestamp();

$t->timestamp('updated_at')
    ->defaultRaw('CURRENT_TIMESTAMP');
```

---

## Shortcuts

```php
$t->id();

$t->timestamps();

$t->createdAt();

$t->updatedAt();

$t->softDeletes();

$t->rememberToken();
```

---

## Indexes

Single-column indexes:

```php
$t->string('email')
    ->unique();

$t->string('slug')
    ->index();
```

Composite indexes:

```php
$t->unique(['email', 'tenant_id']);

$t->index(['first_name', 'last_name']);
```

Indexes are automatically introspected and partially diffed.

Current V1 behavior:

* missing indexes are automatically added
* removed indexes are automatically dropped
* changed indexes are ignored intentionally with warnings

Example warning:

```txt
Index 'email_unique' on table 'users' differs from desired schema and was ignored.
```

---

## Foreign Keys

Simple inferred relation:

```php
$t->foreign('user_id');
```

Explicit references:

```php
$t->foreign('user_id')
    ->constrained() // or references('users')
    ->cascadeOnDelete();
```

Explicit references:

```php
$t->foreign('user_id')
    ->constrained() // or references('users')
    ->cascadeOnDelete();
```

ou:

```php
$t->foreign('author_id')
    ->references('users');
```

Custom referenced column:

```php
$t->uuid('author_id')
    ->references('users', 'uuid');
```

Available relation actions:

```php
->cascadeOnDelete()

->cascadeOnUpdate()

->restrictOnDelete()

->restrictOnUpdate()

->nullOnDelete()

->nullOnUpdate()
```

> In V1, foreign keys are generated when creating new tables only. Foreign key changes after table creation are not automatically migrated yet.

---

## Model Generation

Generate models from your schema:

```bash
php bin/migrate --generate-models
```

Example generated model:

```php
<?php

namespace App\Models;

class User
{
    protected string $table = 'users';
}
```

Generator configuration:

```php
'generator' => [
    'models' => [
        'enabled' => true,
        'namespace' => 'App\\Models',
        'path' => '/app/Models',
        'extends' => 'App\\Core\\Model',
    ],
],
```

---

## CLI

Dry-run:

```bash
php bin/migrate --dry-run
```

Apply migrations without confirmation:

```bash
php bin/migrate --yes
```

Allow destructive operations:

```bash
php bin/migrate --force
```

Show migration history:

```bash
php bin/migrate --status
```

Reset database:

```bash
php bin/migrate --fresh --force --yes
```

Reset database and clear history:

```bash
php bin/migrate --fresh --force --yes --clear-history
```

Generate models:

```bash
php bin/migrate --generate-models
```

Show stack trace on exceptions:

```bash
php bin/migrate --trace
```

---

## Migration History

Executed operations are stored in:

```txt
schema_migrations
```

This table is managed internally by the engine and ignored during schema diffing.

---

## Safety

By default, destructive operations are blocked.

This includes:

* dropping tables
* dropping columns
* dropping indexes

To allow destructive operations:

```bash
php bin/migrate --force
```

---

## Debugging

Enable stack traces during execution:

```bash
php bin/migrate --trace
```

Example output:

```txt
[ERROR] Table users already exists
File: .../Migrator.php:62

Stack trace:

#0 .../Migrator.php:62 run()
#1 .../MigrateCommand.php:88 printPlannedSql()
#2 .../bin/migrate:24 run()
```

---

## Current Limitations

PHP Schema Engine `0.1.0-alpha` is intentionally conservative.

Current V1 limitations:

* MySQL/MariaDB only
* Index diffing is partial and intentionally conservative
* Existing indexes are not automatically modified
* Foreign key changes after table creation are not automatically migrated
* Rename detection is disabled by default
* Table recreation is not implemented yet
* No PostgreSQL grammar yet
* No SQLite grammar yet
* No ORM/query builder layer yet

---

## Philosophy

PHP Schema Engine follows a schema-first approach.

The schema file is the source of truth.

```txt
Schema DSL
    ↓
Schema Metadata
    ↓
Database Introspection
    ↓
Diff Engine
    ↓
SQL Generation
    ↓
Migration Execution
```

The DSL is designed to stay expressive, while the internal architecture keeps metadata, diffing, SQL generation, and execution separated.

---

## Roadmap

### V0.1

* Schema DSL
* MySQL introspection
* Column diffing
* Partial index diffing
* SQL generator
* CLI
* Migration history
* Model generation
* Foreign keys on create

### V0.2

* migrate:fresh
* migrate:reset
* better CLI output
* schema snapshots
* explicit rename operations

### V0.3

* advanced index diffing
* foreign key diffing
* table recreation mode
* rollback support

### V1.0

* stable public API
* stronger type system
* advanced MySQL grammar
* multi-driver foundation

---

## License

MIT
