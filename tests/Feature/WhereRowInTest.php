<?php

declare(strict_types=1);

namespace SwadhinSikder\LaravelRowIn\Tests\Feature;

use Illuminate\Database\Query\Builder;
use ReflectionClass;
use SwadhinSikder\LaravelRowIn\Tests\TestCase;

class WhereRowInTest extends TestCase
{
    public function test_where_row_in_macro_is_registered(): void
    {
        $this->assertTrue(
            Builder::hasMacro('whereRowIn'),
        );
    }

    public function test_where_row_in_macro_adds_bindings(): void
    {
        $builder = $this->app['db']
            ->connection()
            ->query()
            ->from('users');

        $result = $builder->whereRowIn(
            ['id', 'name'],
            [
                [1, 'Alice'],
                [2, 'Bob'],
            ],
        );

        $this->assertSame($builder, $result);

        $this->assertSame(
            [1, 'Alice', 2, 'Bob'],
            $builder->getBindings(),
        );
    }

    public function test_where_row_in_preserves_binding_order(): void
    {
        $builder = $this->app['db']
            ->connection()
            ->query()
            ->from('users');

        $builder
            ->where('status', 'active')
            ->whereRowIn(
                ['id', 'name'],
                [
                    [1, 'Alice'],
                    [2, 'Bob'],
                ],
            );

        $this->assertSame(
            ['active', 1, 'Alice', 2, 'Bob'],
            $builder->getBindings(),
        );
    }

    public function test_where_row_in_stores_row_in_where_clause(): void
    {
        $builder = $this->app['db']
            ->connection()
            ->query()
            ->from('users');

        $builder->whereRowIn(
            ['id', 'name'],
            [
                [1, 'Alice'],
                [2, 'Bob'],
            ],
        );

        $reflection = new ReflectionClass($builder);

        $property = $reflection->getProperty('wheres');
        $wheres = $property->getValue($builder);

        $this->assertSame(
            [
                'type' => 'RowIn',
                'columns' => ['id', 'name'],
                'values' => [
                    [1, 'Alice'],
                    [2, 'Bob'],
                ],
                'boolean' => 'and',
            ],
            $wheres[0],
        );
    }
}
