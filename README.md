# PHP Schema Engine

A modern schema-first migration engine for PHP.

PHP Schema Engine lets you define your database structure using a clean PHP DSL, compare it against the current database schema, generate SQL automatically, and apply changes through a lightweight CLI.

> Current version: `0.1.0`

---

## Features

- Schema-first database definition
- Fluent PHP DSL
- MySQL/MariaDB support
- Automatic schema introspection
- Table and column diffing
- SQL generation
- Migration execution
- Migration history
- Index support on table creation
- Foreign key support on table creation
- Dry-run mode
- Destructive operation protection

---

## Installation

```bash
composer require eril/schema-engine
```

For local development:

```bash
composer install
```

---

## Basic Usage

Create a schema file:

```php
<?php

use SchemaEngine\DSL\Schema;
use SchemaEngine\DSL\Table;
use SchemaEngine\SQL\DB;

return function (Schema $schema) {

    $schema->table('users', function (Table $t) {
        $t->id();

        $t->string('name');
        $t->string('email')->unique();

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

Check migration history:

```bash
php bin/migrate --status
```

---

## Configuration

Create:

```txt
config/database.php
```

Example:

```php
<?php

return [
    'driver' => 'mysql',
    'host' => '127.0.0.1',
    'port' => 3306,
    'database' => 'your_database',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4',
];
```

---

## Defining Tables

```php
$schema->table('users', function ($t) {
    $t->id();

    $t->string('name');
    $t->string('email')->unique();
    $t->int('age')->default(0);

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

$t->date('birth_date');
$t->datetime('published_at');
$t->timestamp('created_at');

$t->json('meta');

$t->uuid('uuid');
```

---

## Column Modifiers

```php
$t->string('email')->unique();

$t->string('slug')->index();

$t->string('name')->nullable();

$t->int('age')->default(0);

$t->timestamp('created_at')->defaultCurrentTimestamp();

$t->timestamp('updated_at')->defaultRaw('CURRENT_TIMESTAMP');
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
$t->string('email')->unique();

$t->string('slug')->index();
```

Composite indexes:

```php
$t->unique(['email', 'tenant_id']);

$t->index(['first_name', 'last_name']);
```

> In V1, indexes are generated when creating new tables. Index changes after table creation are not automatically migrated yet.

---

## Foreign Keys

High-level relationship syntax:

```php
$t->foreignId('user_id')
    ->constrained()
    ->cascadeOnDelete();
```

Explicit referenced table:

```php
$t->foreignId('author_id')
    ->constrained('users');
```

Custom referenced column:

```php
$t->uuid('author_id')
    ->foreign('users', 'uuid');
```

Other relation actions:

```php
$t->foreignId('user_id')
    ->constrained()
    ->cascadeOnDelete()
    ->cascadeOnUpdate();
```

Available actions:

```php
->cascadeOnDelete()
->cascadeOnUpdate()
->restrictOnDelete()
->restrictOnUpdate()
->nullOnDelete()
->nullOnUpdate()
```

> In V1, foreign keys are generated when creating new tables. Foreign key changes after table creation are not automatically migrated yet.

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

To allow destructive operations:

```bash
php bin/migrate --force
```

---

## Current Limitations

PHP Schema Engine `0.1.0` is intentionally conservative.

Current V1 limitations:

* MySQL/MariaDB only
* Index changes are not diffed after table creation
* Foreign key changes are not diffed after table creation
* Rename detection is disabled by default
* Rollbacks are not implemented yet
* Table recreation is not implemented yet
* No PostgreSQL or SQLite grammar yet
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
* SQL generator
* CLI
* Migration history
* Indexes on create
* Foreign keys on create

### V0.2

* `migrate:fresh`
* `migrate:reset`
* better CLI output
* schema snapshots
* explicit rename operations

### V0.3

* index diffing
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

