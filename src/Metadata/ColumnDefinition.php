<?php

namespace SchemaEngine\Metadata;

class ColumnDefinition
{
    public string $name;

    public string $type;

    public bool $nullable = false;

    public bool $autoIncrement = false;

    public mixed $default = null;

    public ?int $length = null;

    public ?int $precision = null;

    public ?int $scale = null;

    public ?string $comment = null;

    public ?string $onUpdate = null;

    public array $allowed = [];

    public array $meta = [];

    public function __construct(
        string $name,
        string $type
    ) {
        $this->name = $name;
        $this->type = $type;
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'type' => $this->type,
            'nullable' => $this->nullable,
            'autoIncrement' => $this->autoIncrement,
            'default' => $this->default,
            'length' => $this->length,
            'precision' => $this->precision,
            'scale' => $this->scale,
            'comment' => $this->comment,
            'onUpdate' => $this->onUpdate,
            'allowed' => $this->allowed,
            'meta' => $this->meta,
        ];
    }

    public static function fromArray(
        array $data
    ): static {

        $column = new static(
            $data['name'],
            $data['type']
        );

        $column->nullable = $data['nullable'];

        $column->autoIncrement = $data['autoIncrement'];

        $column->default = $data['default'];

        $column->length = $data['length'];

        $column->precision = $data['precision'];

        $column->scale = $data['scale'];

        $column->comment = $data['comment'] ?? null;
        $column->onUpdate = $data['onUpdate'] ?? null;
        $column->allowed = $data['allowed'] ?? [];

        $column->meta =
            $data['meta'] ?? [];

        return $column;
    }
}
