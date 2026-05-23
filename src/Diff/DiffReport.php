<?php

namespace SchemaEngine\Diff;

class DiffReport
{
    public array $warnings = [];

    public function warn(
        string $message
    ): void {
        $this->warnings[] = $message;
    }

    public function hasWarnings(): bool
    {
        return !empty($this->warnings);
    }
}