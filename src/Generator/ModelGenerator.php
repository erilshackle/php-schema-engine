<?php

namespace SchemaEngine\Generator;

use SchemaEngine\Metadata\SchemaDefinition;
use SchemaEngine\Metadata\TableDefinition;

class ModelGenerator
{
    public function generate(
        SchemaDefinition $schema,
        array $config
    ): array {

        $namespace = $config['namespace'] ?? 'App\\Models';
        $path = $config['path'] ?? 'app/Models';
        $extends = $config['extends'] ?? null;

        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }

        $generated = [];

        foreach ($schema->tables as $table) {

            $class = $this->className($table->name);

            $content = $this->buildModel(
                table: $table,
                class: $class,
                namespace: $namespace,
                extends: $extends
            );

            $file = rtrim($path, '/\\') . DIRECTORY_SEPARATOR . $class . '.php';

            file_put_contents($file, $content);

            $generated[] = $file;
        }

        return $generated;
    }

    protected function buildModel(
        TableDefinition $table,
        string $class,
        string $namespace,
        ?string $extends
    ): string {

        $use = '';
        $extendsPart = '';

        if ($extends) {
            $baseClass = basename(str_replace('\\', '/', $extends));
            $use = "use {$extends};\n\n";
            $extendsPart = " extends {$baseClass}";
        }

        $properties = $this->buildProperties($table);

        return <<<PHP
<?php

namespace {$namespace};

{$use}/**
 * @property string|int|null \$id
{$properties}
 */
class {$class}{$extendsPart}
{
    protected string \$table = '{$table->name}';
}

PHP;
    }

    protected function buildProperties(
        TableDefinition $table
    ): string {

        $lines = [];

        foreach ($table->columns as $column) {

            if ($column->name === 'id') {
                continue;
            }

            $type = $this->phpType($column->type);

            if ($column->nullable) {
                $type .= '|null';
            }

            $lines[] = " * @property {$type} \${$column->name}";
        }

        return implode("\n", $lines);
    }

    protected function phpType(
        string $type
    ): string {

        return match ($type) {
            'int',
            'bigint',
            'tinyint',
            'smallint' => 'int',

            'float',
            'double',
            'decimal' => 'float',

            'boolean' => 'bool',

            'json' => 'array',

            'datetime',
            'timestamp',
            'date',
            'time' => '\\DateTimeInterface|string',

            default => 'string',
        };
    }

    protected function className(
        string $table
    ): string {

        $singular = str_ends_with($table, 'ies')
            ? substr($table, 0, -3) . 'y'
            : rtrim($table, 's');

        return str_replace(
            ' ',
            '',
            ucwords(
                str_replace('_', ' ', $singular)
            )
        );
    }
}