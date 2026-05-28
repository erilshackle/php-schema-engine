<?php

namespace SchemaEngine\Naming;

class NameInflector
{
    public function tableFromForeignKey(
        string $foreignKey
    ): string {

        if (str_ends_with($foreignKey, '_id')) {
            $base = substr($foreignKey, 0, -3);

            return $this->pluralize($base);
        }

        return $this->pluralize($foreignKey);
    }

    public function pluralize(
        string $word
    ): string {

        $word = strtolower($word);

        $irregular = [
            'person' => 'people',
            'man' => 'men',
            'woman' => 'women',
            'child' => 'children',
            'category' => 'categories',
            'company' => 'companies',
            'country' => 'countries',
        ];

        if (isset($irregular[$word])) {
            return $irregular[$word];
        }

        if (str_ends_with($word, 'y')) {
            $beforeY = substr($word, -2, 1);

            if (!in_array($beforeY, ['a', 'e', 'i', 'o', 'u'], true)) {
                return substr($word, 0, -1) . 'ies';
            }
        }

        if (
            str_ends_with($word, 's')
            || str_ends_with($word, 'x')
            || str_ends_with($word, 'z')
            || str_ends_with($word, 'ch')
            || str_ends_with($word, 'sh')
        ) {
            return $word . 'es';
        }

        return $word . 's';
    }

    public function singularize(
        string $word
    ): string {

        $word = strtolower($word);

        $irregular = [
            'people' => 'person',
            'men' => 'man',
            'women' => 'woman',
            'children' => 'child',
            'categories' => 'category',
            'companies' => 'company',
            'countries' => 'country',
        ];

        if (isset($irregular[$word])) {
            return $irregular[$word];
        }

        if (str_ends_with($word, 'ies')) {
            return substr($word, 0, -3) . 'y';
        }

        if (str_ends_with($word, 'es')) {
            return substr($word, 0, -2);
        }

        if (str_ends_with($word, 's')) {
            return substr($word, 0, -1);
        }

        return $word;
    }

    public function classNameFromTable(
        string $table
    ): string {

        $singular = $this->singularize($table);

        return str_replace(
            ' ',
            '',
            ucwords(
                str_replace('_', ' ', $singular)
            )
        );
    }
}