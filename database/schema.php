<?php

use SchemaEngine\SQL\DB;

return function ($schema) {

    $schema->table('users', function ($t) {
        $t->id();

        $t->string('name');
        $t->string('email')->unique();

        $t->timestamps();
    });

    $schema->table('posts', function ($t) {
        $t->id();

        $t->foreignId('user_id')
            ->constrained()
            ->cascadeOnDelete();

        $t->string('title');
        $t->text('body');

        $t->timestamps();
        $t->softDeletes();
    });
};