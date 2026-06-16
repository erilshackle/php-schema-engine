<?php

namespace SchemaEngine\Diff;

use SchemaEngine\Metadata\ColumnDefinition;
use SchemaEngine\SQL\Expression\Expression;

class ColumnComparator
{
    public function equals(
        ColumnDefinition $a,
        ColumnDefinition $b
    ): bool {

        return (
            $this->normalizeType($a->type) === $this->normalizeType($b->type)
            && $a->nullable === $b->nullable
            && $a->autoIncrement === $b->autoIncrement
            && $this->normalizeDefault($a->default)
            === $this->normalizeDefault($b->default)
            && $this->normalizeLength($a) === $this->normalizeLength($b)
            && $this->normalizePrecision($a) === $this->normalizePrecision($b)
            && $this->normalizeScale($a) === $this->normalizeScale($b)
            && $this->normalizeComment($a->comment) === $this->normalizeComment($b->comment)
            && $this->normalizeOnUpdate($a->onUpdate) === $this->normalizeOnUpdate($b->onUpdate)
            && $a->allowed === $b->allowed
        );
    }

    protected function normalizeType(
        string $type
    ): string {
        return strtolower($type);
    }

    protected function normalizeDefault(
        mixed $value
    ): mixed {

        if ($value instanceof Expression) {
            $value = $value->getValue();
        }

        if (is_string($value)) {
            $value = trim(strtolower($value));

            if ($value === 'current_timestamp()') {
                return 'current_timestamp';
            }

            if ($value === 'null') {
                return null;
            }
        }

        if (is_numeric($value)) {
            return $value + 0;
        }

        return $value;
    }

    protected function normalizeLength(
        ColumnDefinition $column
    ): ?int {

        if (!in_array($column->type, [
            'varchar',
            'char',
        ], true)) {
            return null;
        }

        return $column->length;
    }

    protected function normalizePrecision(
        ColumnDefinition $column
    ): ?int {

        if ($column->type !== 'decimal') {
            return null;
        }

        return $column->precision;
    }

    protected function normalizeScale(
        ColumnDefinition $column
    ): ?int {

        if ($column->type !== 'decimal') {
            return null;
        }

        return $column->scale;
    }

    protected function normalizeComment(
        ?string $comment
    ): ?string {
        return $comment === '' ? null : $comment;
    }

    protected function normalizeOnUpdate(
        ?string $value
    ): ?string {
        if ($value === null) {
            return null;
        }

        $value = strtolower(trim($value));

        if ($value === 'current_timestamp()') {
            return 'current_timestamp';
        }

        return $value;
    }
}
