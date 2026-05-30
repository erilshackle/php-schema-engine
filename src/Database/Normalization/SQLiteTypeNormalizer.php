<?php

namespace SchemaEngine\Database\Normalization;

class SQLiteTypeNormalizer
{
    public function normalize(string $type): string
    {
        $type = strtolower($type);

        if (str_contains($type, 'int')) {
            return 'int';
        }

        if (
            str_contains($type, 'char')
            || str_contains($type, 'varchar')
            || str_contains($type, 'text')
            || $type === 'clob'
        ) {
            return 'varchar';
        }

        if (
            str_contains($type, 'real')
            || str_contains($type, 'float')
            || str_contains($type, 'double')
        ) {
            return 'float';
        }

        if (
            str_contains($type, 'decimal')
            || str_contains($type, 'numeric')
        ) {
            return 'decimal';
        }

        if (str_contains($type, 'bool')) {
            return 'boolean';
        }

        if (str_contains($type, 'json')) {
            return 'json';
        }

        if (str_contains($type, 'datetime')) {
            return 'datetime';
        }

        if (str_contains($type, 'timestamp')) {
            return 'timestamp';
        }

        return $type ?: 'varchar';
    }
}