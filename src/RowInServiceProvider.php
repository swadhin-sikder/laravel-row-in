<?php

declare(strict_types=1);

namespace SwadhinSikder\LaravelRowIn;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Arr;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

/**
 * @phpstan-type Row array<int, mixed>|Arrayable<array-key, mixed>
 * @phpstan-type Values list<Row>|Arrayable<array-key, mixed>|Builder|\Illuminate\Database\Eloquent\Builder<*>|\Illuminate\Database\Eloquent\Relations\Relation<*, *, * >|\Closure|string
 */
class RowInServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if (! Builder::hasMacro('whereRowIn')) {
            Builder::macro('whereRowIn', /** @param list<string> $columns
             * @param  Values  $values
             */ function (
                array $columns,
                mixed $values,
                string $boolean = 'and',
                bool $not = false,
            ): Builder {
                $type = $not ? 'NotRowIn' : 'RowIn';

                if ($this->isQueryable($values)) {
                    [$query, $bindings] = $this->createSub($values);

                    $values = [new Expression($query)];

                    $this->addBinding($bindings, 'where');
                } else {
                    if ($values instanceof Arrayable) {
                        $values = $values->toArray();
                    }

                    $columnCount = count($columns);

                    foreach ($values as $index => $row) {
                        if ($row instanceof Arrayable) {
                            $row = $row->toArray();
                        }

                        if (! is_array($row)) {
                            throw new InvalidArgumentException(sprintf(
                                'Row at index %d must be an array of values, %s given.',
                                $index,
                                get_debug_type($row),
                            ));
                        }

                        if (count($row) !== $columnCount) {
                            throw new InvalidArgumentException(sprintf(
                                'Row at index %d must have exactly %d value(s) to match the given columns, %d given.',
                                $index,
                                $columnCount,
                                count($row),
                            ));
                        }

                        if (array_keys($row) !== range(0, count($row) - 1)) {
                            $normalizedRow = [];

                            foreach ($columns as $column) {
                                if (! array_key_exists($column, $row)) {
                                    throw new InvalidArgumentException(sprintf(
                                        'Row at index %d must contain a value for column %s.',
                                        $index,
                                        $column,
                                    ));
                                }

                                $normalizedRow[] = $row[$column];
                            }

                            $values[$index] = $normalizedRow;
                        } else {
                            $values[$index] = array_values($row);
                        }
                    }

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
            Builder::macro('orWhereRowIn', /** @param list<string> $columns
             * @param  Values  $values
             */ function (
                array $columns,
                mixed $values,
            ): Builder {
                return $this->__call('whereRowIn', [$columns, $values, 'or']);
            });
        }

        if (! Builder::hasMacro('whereNotRowIn')) {
            Builder::macro('whereNotRowIn', /** @param list<string> $columns
             * @param  Values  $values
             */ function (
                array $columns,
                mixed $values,
                string $boolean = 'and',
            ): Builder {
                return $this->__call('whereRowIn', [$columns, $values, $boolean, true]);
            });
        }

        if (! Builder::hasMacro('orWhereNotRowIn')) {
            Builder::macro('orWhereNotRowIn', /** @param list<string> $columns
             * @param  Values  $values
             */ function (
                array $columns,
                mixed $values,
            ): Builder {
                return $this->__call('whereNotRowIn', [$columns, $values, 'or']);
            });
        }

        RowInGrammar::register();
    }
}
