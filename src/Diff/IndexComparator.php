<?php

namespace SchemaEngine\Diff;

use SchemaEngine\Metadata\IndexDefinition;

class IndexComparator
{
    public function equals(
        IndexDefinition $a,
        IndexDefinition $b
    ): bool {

        return (
            $a->columns === $b->columns
            && $a->unique === $b->unique
            && $a->primary === $b->primary
        );
    }
}