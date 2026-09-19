<?php

declare(strict_types=1);

namespace SwadhinSikder\LaravelRowIn;

use Illuminate\Database\Query\Expression;
use Illuminate\Database\Query\Grammars\SqlServerGrammar;
use InvalidArgumentException;

final class SqlServerRowInGrammar
{
    /**
     * @param  array<string, mixed>  $where
     */
    public static function compile(
        SqlServerGrammar $grammar,
        array $where,
        bool $not,
    ): string {
        $values = $where['values'];

        // Same convention as the base grammar: an empty set can never
        // match, and NOT of an empty set always matches.
        if (empty($values)) {
            return $not ? '1 = 1' : '0 = 1';
        }

        $columns = $where['columns'];

        if (
            count($values) === 1
            && $values[0] instanceof Expression
        ) {
            throw new InvalidArgumentException(
                'SQL Server does not support subqueries in row-in clauses.',
            );
        }

        $conditions = [];

        foreach ($values as $row) {
            if (! is_array($row)) {
                throw new InvalidArgumentException(
                    'Row values must be arrays when compiling a SQL Server row-in clause.',
                );
            }

            $rowConditions = [];

            foreach ($columns as $index => $column) {
                $value = $row[$index];

                $rowConditions[] = $grammar->wrap($column)
                    .' = '
                    .(
                        $value instanceof Expression
                            ? $value->getValue($grammar)
                            : $grammar->parameter($value)
                    );
            }

            $conditions[] = '('.implode(' and ', $rowConditions).')';
        }

        $compiled = implode(' or ', $conditions);

        return $not
            ? 'not ('.$compiled.')'
            : '('.$compiled.')';
    }
}
