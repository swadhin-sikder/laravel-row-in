<?php

namespace SwadhinSikder\LaravelRowIn\Tests\Feature\SqlServer;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Query\Grammars\SqlServerGrammar;
use InvalidArgumentException;
use SwadhinSikder\LaravelRowIn\Tests\TestCase;

class WhereRowInTest extends TestCase
{
    private function sqlServerQuery(): Builder
    {
        $connection = $this->app['db']->connection();

        return new Builder(
            $connection,
            new SqlServerGrammar($connection),
        );
    }

    public function test_sql_server_rejects_subqueries(): void
    {
        $subquery = $this->app['db']
            ->connection()
            ->table('user_roles')
            ->select(['user_id', 'role_id']);

        expect(fn () => $this->sqlServerQuery()
            ->from('users')
            ->whereRowIn(
                ['user_id', 'role_id'],
                $subquery,
            )
            ->toSql())
            ->toThrow(
                InvalidArgumentException::class,
                'SQL Server does not support subqueries in row-in clauses.',
            );
    }

    public function test_sql_server_compiles_where_row_in_as_or_conditions(): void
    {
        $query = $this->sqlServerQuery()
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
        $query = $this->sqlServerQuery()
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
        $query = $this->sqlServerQuery()
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
        $query = $this->sqlServerQuery()
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
        $query = $this->sqlServerQuery()
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

    public function test_sql_server_compiles_raw_expressions_in_where_row_in(): void
    {
        $query = $this->sqlServerQuery()
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

    public function test_sql_server_compiles_raw_expressions_in_where_not_row_in(): void
    {
        $query = $this->sqlServerQuery()
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

    public function test_sql_server_where_row_in_with_empty_values_compiles_to_always_false(): void
    {
        $query = $this->sqlServerQuery()
            ->from('users')
            ->whereRowIn(['user_id', 'role_id'], []);

        expect($query->toSql())
            ->toBe('select * from [users] where 0 = 1')
            ->and($query->getBindings())
            ->toBe([]);
    }

    public function test_sql_server_where_not_row_in_with_empty_values_compiles_to_always_true(): void
    {
        $query = $this->sqlServerQuery()
            ->from('users')
            ->whereNotRowIn(['user_id', 'role_id'], []);

        expect($query->toSql())
            ->toBe('select * from [users] where 1 = 1')
            ->and($query->getBindings())
            ->toBe([]);
    }

    public function test_sql_server_normalizes_associative_row_arrays(): void
    {
        $query = $this->sqlServerQuery()
            ->from('users')
            ->whereRowIn(
                ['user_id', 'role_id'],
                [
                    ['user_id' => 1, 'role_id' => 2],
                ],
            );

        expect($query->toSql())
            ->toBe('select * from [users] where (([user_id] = ? and [role_id] = ?))')
            ->and($query->getBindings())
            ->toBe([1, 2]);
    }
}
