<?php

declare(strict_types=1);

namespace SwadhinSikder\LaravelRowIn;

use Illuminate\Database\Query\Expression;
use Illuminate\Database\Query\Grammars\SqlServerGrammar;

final class SqlServerRowInGrammar
{
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
            $columnList = $grammar->columnize($columns);
            $operator = $not ? 'not in' : 'in';

            return '('.$columnList.') '.$operator.' ('
                .$values[0]->getValue($grammar)
                .')';
        }

        $conditions = [];

        foreach ($values as $row) {
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
