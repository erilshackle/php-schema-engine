<?php

namespace SchemaEngine\Diff;

use SchemaEngine\Metadata\ColumnDefinition;

class ColumnComparator
{
    public function equals(
        ColumnDefinition $a,
        ColumnDefinition $b
    ): bool {

        return (
            $a->type === $b->type
            && $a->nullable === $b->nullable
            // && $a->primary === $b->primary
            // && $a->unique === $b->unique
            && $a->autoIncrement === $b->autoIncrement

            && $this->normalizeDefault($a->default)
            ===
            $this->normalizeDefault($b->default)

            && $a->length === $b->length
            && $a->precision === $b->precision
            && $a->scale === $b->scale
        );
    }

    protected function normalizeDefault(
        mixed $value
    ): mixed {

        if (is_numeric($value)) {
            return $value + 0;
        }

        return $value;
    }
}
