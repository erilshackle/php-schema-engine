# Full Example Usage




```php
<?php

use SchemaEngine\DSL\Schema;
use SchemaEngine\DSL\Table;

return function (Schema $schema) {

    /*
    |--------------------------------------------------------------------------
    | Auth / Users
    |--------------------------------------------------------------------------
    */

    $schema->table('users', function (Table $t) {
        $t->id();

        $t->uuid('uuid')->unique();

        $t->string('name');
        $t->string('email')->unique();
        $t->timestamp('email_verified_at')->nullable();

        $t->string('password');

        $t->string('status')->default('active')->index();

        $t->rememberToken();

        $t->timestamps();
        $t->softDeletes();
    });

    $schema->table('user_profiles', function (Table $t) {
        $t->id();

        $t->foreignId('user_id')
            ->constrained()
            ->cascadeOnDelete();

        $t->string('avatar')->nullable();
        $t->string('phone')->nullable();
        $t->string('country')->nullable();
        $t->string('city')->nullable();

        $t->text('bio')->nullable();

        $t->json('pconstrained')->nullable();

        $t->timestamps();
    });

    /*
    |--------------------------------------------------------------------------
    | RBAC
    |--------------------------------------------------------------------------
    */

    $schema->table('roles', function (Table $t) {
        $t->id();

        $t->string('name')->unique();
        $t->string('label')->nullable();
        $t->text('description')->nullable();

        $t->timestamps();
    });

    $schema->table('permissions', function (Table $t) {
        $t->id();

        $t->string('name')->unique();
        $t->string('group')->index();
        $t->string('label')->nullable();

        $t->timestamps();
    });

    $schema->table('role_user', function (Table $t) {
        $t->id();

        $t->foreignId('role_id')
            ->constrained('roles')
            ->cascadeOnDelete();

        $t->foreignId('user_id')
            ->constrained('users')
            ->cascadeOnDelete();

        $t->unique(['role_id', 'user_id']);

        $t->timestamps();
    });

    $schema->table('permission_role', function (Table $t) {
        $t->id();

        $t->foreignId('permission_id')
            ->constrained('permissions')
            ->cascadeOnDelete();

        $t->foreignId('role_id')
            ->constrained('roles')
            ->cascadeOnDelete();

        $t->unique(['permission_id', 'role_id']);

        $t->timestamps();
    });

    /*
    |--------------------------------------------------------------------------
    | Security / Sessions / Tokens
    |--------------------------------------------------------------------------
    */

    $schema->table('sessions', function (Table $t) {
        $t->string('id', 128)->primary();

        $t->foreignId('user_id')
            ->nullable()
            ->constrained('users')
            ->nullOnDelete();

        $t->string('ip_address', 45)->nullable();
        $t->text('user_agent')->nullable();

        $t->longText('payload');
        $t->int('last_activity')->index();

        $t->timestamps();
    });

    $schema->table('password_reset_tokens', function (Table $t) {
        $t->id();

        $t->string('email')->index();
        $t->string('token');
        $t->timestamp('expires_at');
        $t->timestamp('used_at')->nullable();

        $t->timestamps();
    });

    $schema->table('personal_access_tokens', function (Table $t) {
        $t->id();

        $t->foreignId('user_id')
            ->constrained()
            ->cascadeOnDelete();

        $t->string('name');
        $t->string('token')->unique();

        $t->json('abilities')->nullable();

        $t->timestamp('last_used_at')->nullable();
        $t->timestamp('expires_at')->nullable();

        $t->timestamps();
    });

    $schema->table('login_attempts', function (Table $t) {
        $t->id();

        $t->string('email')->index();
        $t->string('ip_address', 45)->index();

        $t->boolean('successful')->default(false);
        $t->text('user_agent')->nullable();

        $t->timestamp('attempted_at')->defaultCurrentTimestamp();

        $t->index(['email', 'ip_address']);
    });

    /*
    |--------------------------------------------------------------------------
    | Blog / CMS
    |--------------------------------------------------------------------------
    */

    $schema->table('categories', function (Table $t) {
        $t->id();

        $t->string('name');
        $t->slug();

        $t->text('description')->nullable();

        $t->timestamps();
    });

    $schema->table('tags', function (Table $t) {
        $t->id();

        $t->string('name');
        $t->slug();

        $t->timestamps();
    });

    $schema->table('posts', function (Table $t) {
        $t->id();

        $t->foreignId('author_id')
            ->constrained('users')
            ->cascadeOnDelete();

        $t->foreignId('category_id')
            ->nullable()
            ->constrained('categories')
            ->nullOnDelete();

        $t->string('title');
        $t->slug();

        $t->text('excerpt')->nullable();
        $t->longText('content');

        $t->string('cover_image')->nullable();

        $t->string('status')->default('draft')->index();

        $t->timestamp('published_at')->nullable();

        $t->int('views')->default(0);

        $t->timestamps();
        $t->softDeletes();
    });

    $schema->table('post_tag', function (Table $t) {
        $t->id();

        $t->foreignId('post_id')
            ->constrained('posts')
            ->cascadeOnDelete();

        $t->foreignId('tag_id')
            ->constrained('tags')
            ->cascadeOnDelete();

        $t->unique(['post_id', 'tag_id']);

        $t->timestamps();
    });

    $schema->table('comments', function (Table $t) {
        $t->id();

        $t->foreignId('post_id')
            ->constrained('posts')
            ->cascadeOnDelete();

        $t->foreignId('user_id')
            ->nullable()
            ->constrained('users')
            ->nullOnDelete();

        $t->foreignId('parent_id')
            ->nullable()
            ->constrained('comments')
            ->cascadeOnDelete();

        $t->string('author_name')->nullable();
        $t->string('author_email')->nullable();

        $t->text('body');

        $t->string('status')->default('pending')->index();

        $t->timestamps();
        $t->softDeletes();
    });

    /*
    |--------------------------------------------------------------------------
    | Media / Files
    |--------------------------------------------------------------------------
    */

    $schema->table('media', function (Table $t) {
        $t->id();

        $t->foreignId('user_id')
            ->nullable()
            ->constrained('users')
            ->nullOnDelete();

        $t->string('disk')->default('local');
        $t->string('path');
        $t->string('filename');
        $t->string('mime_type')->nullable();

        $t->bigInt('size')->default(0);

        $t->json('meta')->nullable();

        $t->timestamps();
    });

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    */

    $schema->table('notifications', function (Table $t) {
        $t->id();

        $t->foreignId('user_id')
            ->constrained()
            ->cascadeOnDelete();

        $t->string('type');
        $t->string('title');
        $t->text('message')->nullable();

        $t->json('data')->nullable();

        $t->timestamp('read_at')->nullable();

        $t->timestamps();

        $t->index(['user_id', 'read_at']);
    });

    /*
    |--------------------------------------------------------------------------
    | Audit / Activity Logs
    |--------------------------------------------------------------------------
    */

    $schema->table('activity_logs', function (Table $t) {
        $t->id();

        $t->foreignId('user_id')
            ->nullable()
            ->constrained('users')
            ->nullOnDelete();

        $t->string('event')->index();
        $t->string('subject_type')->nullable();
        $t->bigInt('subject_id')->nullable();

        $t->json('properties')->nullable();

        $t->string('ip_address', 45)->nullable();
        $t->text('user_agent')->nullable();

        $t->timestamp('created_at')->defaultCurrentTimestamp();

        $t->index(['subject_type', 'subject_id']);
    });

    /*
    |--------------------------------------------------------------------------
    | Settings
    |--------------------------------------------------------------------------
    */

    $schema->table('settings', function (Table $t) {
        $t->id();

        $t->string('key')->unique();
        $t->text('value')->nullable();
        $t->string('type')->default('string');

        $t->timestamps();
    });
};
```