<?php

declare(strict_types=1);

namespace SwadhinSikder\LaravelRowIn\Tests\Feature;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Query\Grammars\MySqlGrammar;
use Illuminate\Database\Query\Grammars\PostgresGrammar;
use Illuminate\Database\Query\Grammars\SQLiteGrammar;
use Illuminate\Database\Query\Grammars\SqlServerGrammar;
use ReflectionClass;
use SwadhinSikder\LaravelRowIn\Tests\TestCase;

class WhereRowInTest extends TestCase
{
    private function queryWithGrammar(string $grammarClass): Builder
    {
        $connection = $this->app['db']->connection();

        return new Builder(
            $connection,
            new $grammarClass($connection),
        );
    }

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

        $reflection = new ReflectionClass($builder);

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

    public function test_compiles_where_row_in_with_subquery(): void
    {
        $subquery = $this->app['db']
            ->connection()
            ->table('user_roles')
            ->select(['user_id', 'role_id']);

        $query = $this->app['db']
            ->connection()
            ->table('users')
            ->whereRowIn(
                ['user_id', 'role_id'],
                $subquery,
            );

        expect($query->toSql())
            ->toBe(
                'select * from "users" where ("user_id", "role_id") in (select "user_id", "role_id" from "user_roles")',
            )
            ->and($query->getBindings())
            ->toBe([]);
    }

    public function test_compiles_where_not_row_in_with_subquery(): void
    {
        $subquery = $this->app['db']
            ->connection()
            ->table('user_roles')
            ->select(['user_id', 'role_id']);

        $query = $this->app['db']
            ->connection()
            ->table('users')
            ->whereNotRowIn(
                ['user_id', 'role_id'],
                $subquery,
            );

        expect($query->toSql())
            ->toBe(
                'select * from "users" where ("user_id", "role_id") not in (select "user_id", "role_id" from "user_roles")',
            )
            ->and($query->getBindings())
            ->toBe([]);
    }

    public function test_where_row_in_subquery_preserves_bindings(): void
    {
        $subquery = $this->app['db']
            ->connection()
            ->table('user_roles')
            ->select(['user_id', 'role_id'])
            ->where('active', true);

        $query = $this->app['db']
            ->connection()
            ->table('users')
            ->whereRowIn(
                ['user_id', 'role_id'],
                $subquery,
            );

        expect($query->getBindings())
            ->toBe([true]);
    }

    public function test_where_row_in_subquery_preserves_binding_order(): void
    {
        $subquery = $this->app['db']
            ->connection()
            ->table('user_roles')
            ->select(['user_id', 'role_id'])
            ->where('active', true);

        $query = $this->app['db']
            ->connection()
            ->table('users')
            ->where('status', 'active')
            ->whereRowIn(
                ['user_id', 'role_id'],
                $subquery,
            );

        expect($query->getBindings())
            ->toBe(['active', true]);
    }

    public function test_compiles_where_row_in_with_mysql_grammar(): void
    {
        $query = $this->queryWithGrammar(MySqlGrammar::class)
            ->from('users')
            ->whereRowIn(
                ['user_id', 'role_id'],
                [
                    [1, 2],
                    [3, 4],
                ],
            );

        expect($query->toSql())
            ->toBe('select * from `users` where (`user_id`, `role_id`) in ((?, ?), (?, ?))')
            ->and($query->getBindings())
            ->toBe([1, 2, 3, 4]);
    }

    public function test_compiles_where_row_in_with_postgres_grammar(): void
    {
        $query = $this->queryWithGrammar(PostgresGrammar::class)
            ->from('users')
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

    public function test_compiles_where_row_in_with_sqlite_grammar(): void
    {
        $query = $this->queryWithGrammar(SQLiteGrammar::class)
            ->from('users')
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

    public function test_sql_server_compiles_where_row_in_as_or_conditions(): void
    {
        $query = $this->queryWithGrammar(SqlServerGrammar::class)
            ->from('users')
            ->whereRowIn(
                ['user_id', 'role_id'],
                [
                    [1, 2],
                    [3, 4],
                ],
            );

        expect($query->toSql())
            ->toBe(
                'select * from [users] where (([user_id] = ? and [role_id] = ?) or ([user_id] = ? and [role_id] = ?))',
            )
            ->and($query->getBindings())
            ->toBe([1, 2, 3, 4]);
    }

    public function test_sql_server_compiles_where_not_row_in_as_negated_or_conditions(): void
    {
        $query = $this->queryWithGrammar(SqlServerGrammar::class)
            ->from('users')
            ->whereNotRowIn(
                ['user_id', 'role_id'],
                [
                    [1, 2],
                    [3, 4],
                ],
            );

        expect($query->toSql())
            ->toBe(
                'select * from [users] where not (([user_id] = ? and [role_id] = ?) or ([user_id] = ? and [role_id] = ?))',
            )
            ->and($query->getBindings())
            ->toBe([1, 2, 3, 4]);
    }

    public function test_sql_server_compiles_single_row_where_row_in(): void
    {
        $query = $this->queryWithGrammar(SqlServerGrammar::class)
            ->from('users')
            ->whereRowIn(
                ['user_id', 'role_id'],
                [
                    [1, 2],
                ],
            );

        expect($query->toSql())
            ->toBe(
                'select * from [users] where (([user_id] = ? and [role_id] = ?))',
            )
            ->and($query->getBindings())
            ->toBe([1, 2]);
    }

    public function test_sql_server_compiles_three_column_where_row_in(): void
    {
        $query = $this->queryWithGrammar(SqlServerGrammar::class)
            ->from('users')
            ->whereRowIn(
                ['user_id', 'role_id', 'tenant_id'],
                [
                    [1, 2, 10],
                    [3, 4, 20],
                ],
            );

        expect($query->toSql())
            ->toBe(
                'select * from [users] where (([user_id] = ? and [role_id] = ? and [tenant_id] = ?) or ([user_id] = ? and [role_id] = ? and [tenant_id] = ?))',
            )
            ->and($query->getBindings())
            ->toBe([1, 2, 10, 3, 4, 20]);
    }

    public function test_sql_server_preserves_boolean_combination_with_where_row_in(): void
    {
        $query = $this->queryWithGrammar(SqlServerGrammar::class)
            ->from('users')
            ->where('active', true)
            ->orWhereRowIn(
                ['user_id', 'role_id'],
                [
                    [1, 2],
                    [3, 4],
                ],
            );

        expect($query->toSql())
            ->toBe(
                'select * from [users] where [active] = ? or (([user_id] = ? and [role_id] = ?) or ([user_id] = ? and [role_id] = ?))',
            )
            ->and($query->getBindings())
            ->toBe([true, 1, 2, 3, 4]);
    }

    public function test_compiles_raw_expressions_in_where_row_in(): void
    {
        $query = $this->queryWithGrammar(SQLiteGrammar::class)
            ->from('users')
            ->whereRowIn(
                ['user_id', 'role_id'],
                [
                    [new Expression('1'), 2],
                    [3, new Expression('4')],
                ],
            );

        expect($query->toSql())
            ->toBe(
                'select * from "users" where ("user_id", "role_id") in ((1, ?), (?, 4))',
            )
            ->and($query->getBindings())
            ->toBe([2, 3]);
    }

    public function test_sql_server_compiles_raw_expressions_in_where_row_in(): void
    {
        $query = $this->queryWithGrammar(SqlServerGrammar::class)
            ->from('users')
            ->whereRowIn(
                ['user_id', 'role_id'],
                [
                    [new Expression('1'), 2],
                    [3, new Expression('4')],
                ],
            );

        expect($query->toSql())
            ->toBe(
                'select * from [users] where (([user_id] = 1 and [role_id] = ?) or ([user_id] = ? and [role_id] = 4))',
            )
            ->and($query->getBindings())
            ->toBe([2, 3]);
    }

    public function test_compiles_raw_expressions_in_where_not_row_in(): void
    {
        $query = $this->queryWithGrammar(SQLiteGrammar::class)
            ->from('users')
            ->whereNotRowIn(
                ['user_id', 'role_id'],
                [
                    [new Expression('1'), 2],
                    [3, new Expression('4')],
                ],
            );

        expect($query->toSql())
            ->toBe(
                'select * from "users" where ("user_id", "role_id") not in ((1, ?), (?, 4))',
            )
            ->and($query->getBindings())
            ->toBe([2, 3]);
    }

    public function test_sql_server_compiles_raw_expressions_in_where_not_row_in(): void
    {
        $query = $this->queryWithGrammar(SqlServerGrammar::class)
            ->from('users')
            ->whereNotRowIn(
                ['user_id', 'role_id'],
                [
                    [new Expression('1'), 2],
                    [3, new Expression('4')],
                ],
            );

        expect($query->toSql())
            ->toBe(
                'select * from [users] where not (([user_id] = 1 and [role_id] = ?) or ([user_id] = ? and [role_id] = 4))',
            )
            ->and($query->getBindings())
            ->toBe([2, 3]);
    }
}
