<?php

declare(strict_types=1);

namespace SwadhinSikder\LaravelRowIn;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Arr;
use Illuminate\Support\ServiceProvider;

class RowInServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if (! Builder::hasMacro('whereNotRowIn')) {
            Builder::macro('whereRowIn', function (
                array $columns,
                mixed $values,
                string $boolean = 'and',
                bool $not = false,
            ) {
                $type = $not ? 'NotRowIn' : 'RowIn';

                if ($this->isQueryable($values)) {
                    [$query, $bindings] = $this->createSub($values);

                    $values = [new Expression($query)];

                    $this->addBinding($bindings, 'where');
                } else {
                    $this->addBinding(
                        $this->cleanBindings(Arr::flatten($values, 1)),
                    );
                }

                $this->wheres[] = compact(
                    'type',
                    'columns',
                    'values',
                    'boolean',
                );

                return $this;
            });
        }

        if (! Builder::hasMacro('orWhereRowIn')) {
            Builder::macro('orWhereRowIn', function (
                array $columns,
                mixed $values,
            ) {
                return $this->whereRowIn($columns, $values, 'or');
            });
        }

        if (! Builder::hasMacro('whereNotRowIn')) {
            Builder::macro('whereNotRowIn', function (
                array $columns,
                mixed $values,
                string $boolean = 'and',
            ) {
                return $this->whereRowIn($columns, $values, $boolean, true);
            });
        }

        if (! Builder::hasMacro('orWhereNotRowIn')) {
            Builder::macro('orWhereNotRowIn', function (
                array $columns,
                mixed $values,
            ) {
                return $this->whereNotRowIn($columns, $values, 'or');
            });
        }

        RowInGrammar::register();
    }
}
