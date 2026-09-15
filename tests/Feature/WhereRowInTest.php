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

    public function test_where_not_row_in_macro_is_registered(): void
    {
        $this->assertTrue(
            Builder::hasMacro('whereNotRowIn'),
        );
    }

    public function test_or_where_row_in_macro_is_registered(): void
    {
        $this->assertTrue(
            Builder::hasMacro('orWhereRowIn'),
        );
    }

    public function test_or_where_not_row_in_macro_is_registered(): void
    {
        $this->assertTrue(
            Builder::hasMacro('orWhereNotRowIn'),
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

    public function test_where_not_row_in_preserves_binding_order(): void
    {
        $builder = $this->app['db']
            ->connection()
            ->query()
            ->from('users');

        $builder
            ->where('status', 'active')
            ->whereNotRowIn(
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

    public function test_or_where_not_row_in_preserves_binding_order(): void
    {
        $builder = $this->app['db']
            ->connection()
            ->query()
            ->from('users');

        $builder
            ->where('status', 'active')
            ->orWhereNotRowIn(
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

    public function test_where_not_row_in_stores_not_row_in_where_clause(): void
    {
        $builder = $this->app['db']
            ->connection()
            ->query()
            ->from('users');

        $builder->whereNotRowIn(
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
                'type' => 'NotRowIn',
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

    public function test_or_where_not_row_in_uses_or_boolean(): void
    {
        $builder = $this->app['db']
            ->connection()
            ->query()
            ->from('users');

        $builder->orWhereNotRowIn(
            ['id', 'name'],
            [
                [1, 'Alice'],
                [2, 'Bob'],
            ],
        );

        $reflection = new \ReflectionClass($builder);

        $property = $reflection->getProperty('wheres');
        $wheres = $property->getValue($builder);

        $this->assertSame('NotRowIn', $wheres[0]['type']);
        $this->assertSame('or', $wheres[0]['boolean']);

        $this->assertSame(
            [1, 'Alice', 2, 'Bob'],
            $builder->getBindings(),
        );
    }

    public function test_compiles_where_row_in_to_sql(): void
    {
        $query = $this->app['db']
            ->connection()
            ->table('users')
            ->whereRowIn(
                ['user_id', 'role_id'],
                [
                    [1, 2],
                    [3, 4],
                ],
            );

        expect($query->toSql())
            ->toBe('select * from "users" where ("user_id", "role_id") in ((?, ?), (?, ?))')
            ->and($query->getBindings())
            ->toBe([1, 2, 3, 4]);

    }

    public function test_compiles_where_not_row_in_to_sql(): void
    {
        $query = $this->app['db']
            ->connection()
            ->table('users')
            ->whereNotRowIn(
                ['user_id', 'role_id'],
                [
                    [1, 2],
                    [3, 4],
                ],
            );

        expect($query->toSql())
            ->toBe('select * from "users" where ("user_id", "role_id") not in ((?, ?), (?, ?))')
            ->and($query->getBindings())
            ->toBe([1, 2, 3, 4]);
    }

    public function test_where_row_in_compiles_alongside_normal_where_clauses(): void
    {
        $query = $this->app['db']
            ->connection()
            ->table('users')
            ->where('active', true)
            ->whereRowIn(
                ['user_id', 'role_id'],
                [
                    [1, 2],
                    [3, 4],
                ],
            );

        expect($query->toSql())
            ->toBe('select * from "users" where "active" = ? and ("user_id", "role_id") in ((?, ?), (?, ?))')
            ->and($query->getBindings())
            ->toBe([true, 1, 2, 3, 4]);
    }
}
