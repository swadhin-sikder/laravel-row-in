<?php

declare(strict_types=1);

namespace SwadhinSikder\LaravelRowIn;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Query\Grammars\Grammar;

final class RowInGrammar
{
    public static function register(): void
    {
        if (! Grammar::hasMacro('whereRowIn')) {
            Grammar::macro('whereRowIn', function (
                Builder $query,
                array $where,
            ): string {
                /** @var Grammar $this */

                return RowInGrammar::compile($this, $where, false);
            });
        }

        if (! Grammar::hasMacro('whereNotRowIn')) {
            Grammar::macro('whereNotRowIn', function (
                Builder $query,
                array $where,
            ): string {
                /** @var Grammar $this */

                return RowInGrammar::compile($this, $where, true);
            });
        }
    }

    public static function compile(
        Grammar $grammar,
        array $where,
        bool $not,
    ): string {
        $columns = $grammar->columnize($where['columns']);

        $operator = $not ? 'not in' : 'in';

        $values = $where['values'];

        if (
            count($values) === 1
            && $values[0] instanceof Expression
        ) {
            return '('.$columns.') '.$operator.' ('
                .$values[0]->getValue($grammar)
                .')';
        }

        $rows = array_map(
            fn (array $row): string => '('.$grammar->parameterize($row).')',
            $values,
        );

        return '('.$columns.') '.$operator.' ('
            .implode(', ', $rows)
            .')';
    }
}
