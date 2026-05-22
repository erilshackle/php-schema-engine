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
        ];
    }

    public static function fromArray(
        array $data
    ): static {

        $column = new static(
            $data['name'],
            $data['type']
        );

        $column->nullable =
            $data['nullable'];

        $column->autoIncrement =
            $data['autoIncrement'];

        $column->default =
            $data['default'];

        $column->length =
            $data['length'];

        $column->precision =
            $data['precision'];

        $column->scale =
            $data['scale'];

        return $column;
    }
}
