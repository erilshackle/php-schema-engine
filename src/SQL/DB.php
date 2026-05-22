<?php

namespace SchemaEngine\SQL;

use SchemaEngine\SQL\Expression\Expression;

class DB
{
    public static function raw(string $expression): Expression
    {
        return new Expression(
            $expression
        );
    }
}
