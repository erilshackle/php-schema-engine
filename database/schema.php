<?php

return function (SchemaEngine\DSL\Schema $schema) {

    $schema->table('users', function (SchemaEngine\DSL\Table $t) {
        $t->id();
        $t->string('name');
        $t->string('email')->unique();
        $t->int('age')->default(1);
        $t->datetime('last_activity');
        $t->timestamps();
    });

    $schema->table("products", function (SchemaEngine\DSL\Table $t) {});
};
