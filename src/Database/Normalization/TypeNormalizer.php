<?php

namespace SchemaEngine\Database\Normalization;

class TypeNormalizer
{
    public function normalize(
        string $type,
        ?string $columnType = null
    ): string {

        $type = strtolower($type);

        return match ($type) {

            'integer' => 'int',

            'tinyint' =>
            $columnType === 'tinyint(1)'
                ? 'boolean'
                : 'tinyint',

            'bool',
            'boolean' => 'boolean',

            'character varying' => 'varchar',

            default => $type
        };
    }
}
