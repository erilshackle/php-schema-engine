<?php

namespace SchemaEngine\Diff;

use SchemaEngine\Metadata\ForeignKeyDefinition;

class ForeignKeyComparator
{
    public function equals(
        ForeignKeyDefinition $a,
        ForeignKeyDefinition $b
    ): bool {

        return (
            $a->columns === $b->columns
            && $a->referencedTable === $b->referencedTable
            && $a->referencedColumns === $b->referencedColumns
            && $this->normalizeAction($a->onDelete) === $this->normalizeAction($b->onDelete)
            && $this->normalizeAction($a->onUpdate) === $this->normalizeAction($b->onUpdate)
        );
    }

    protected function normalizeAction(
        ?string $action
    ): ?string {

        if ($action === null) {
            return null;
        }

        return strtoupper($action);
    }
}