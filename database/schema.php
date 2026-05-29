<?php // example

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

    $schema->table('posts', function ($t) {
        $t->id();

        $t->foreign('user_id')
            ->constrained()
            ->cascadeOnDelete();

        $t->string('title');
        $t->text('body');

        $t->string('slug')->unique();

        $t->timestamps();
        $t->softDeletes();
    });

    $schema->table('comments', function ($t) {
        $t->id();

        $t->foreign('post_id')->references('users')->cascadeOnDelete();

        $t->text('body');

        $t->timestamps();
    });
};
