<?php

declare(strict_types=1);

namespace SwadhinSikder\LaravelRowIn;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\ServiceProvider;

class RowInServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if (Builder::hasMacro('whereRowIn')) {
            return;
        }

        Builder::macro('whereRowIn', function (
            array $columns,
            array $values,
            string $boolean = 'and',
            bool $not = false,
        ) {
            $type = $not ? 'NotRowIn' : 'RowIn';

            $this->wheres[] = compact(
                'type',
                'columns',
                'values',
                'boolean',
            );

            $this->addBinding(
                Arr::flatten($values, 1),
                'where',
            );

            return $this;
        });

        Builder::macro('orWhereRowIn', function (
            array $columns,
            array $values,
        ) {
            return $this->whereRowIn($columns, $values, 'or');
        });

        Builder::macro('whereNotRowIn', function (
            array $columns,
            array $values,
            string $boolean = 'and',
        ) {
            return $this->whereRowIn($columns, $values, $boolean, true);
        });

        Builder::macro('orWhereNotRowIn', function (
            array $columns,
            array $values,
        ) {
            return $this->whereNotRowIn($columns, $values, 'or');
        });
    }
}
