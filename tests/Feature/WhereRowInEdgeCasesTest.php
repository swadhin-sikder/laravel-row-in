<?php

declare(strict_types=1);

namespace SwadhinSikder\LaravelRowIn\Tests\Feature;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use SwadhinSikder\LaravelRowIn\Tests\TestCase;

class WhereRowInEdgeCasesTest extends TestCase
{
    private function queryWithGrammar(string $grammarClass): Builder
    {
        $connection = $this->app['db']->connection();

        return new Builder(
            $connection,
            new $grammarClass($connection),
        );
    }

    // -----------------------------------------------------------------
    // Empty values
    // -----------------------------------------------------------------

    public function test_where_row_in_with_empty_values_compiles_to_always_false(): void
    {
        $query = $this->app['db']
            ->connection()
            ->table('users')
            ->whereRowIn(['user_id', 'role_id'], []);

        expect($query->toSql())
            ->toBe('select * from "users" where 0 = 1')
            ->and($query->getBindings())
            ->toBe([]);
    }

    public function test_where_not_row_in_with_empty_values_compiles_to_always_true(): void
    {
        $query = $this->app['db']
            ->connection()
            ->table('users')
            ->whereNotRowIn(['user_id', 'role_id'], []);

        expect($query->toSql())
            ->toBe('select * from "users" where 1 = 1')
            ->and($query->getBindings())
            ->toBe([]);
    }

    public function test_where_row_in_with_empty_values_compiles_alongside_other_wheres(): void
    {
        $query = $this->app['db']
            ->connection()
            ->table('users')
            ->where('active', true)
            ->whereRowIn(['user_id', 'role_id'], []);

        expect($query->toSql())
            ->toBe('select * from "users" where "active" = ? and 0 = 1')
            ->and($query->getBindings())
            ->toBe([true]);
    }

    // -----------------------------------------------------------------
    // Row / column count mismatches
    // -----------------------------------------------------------------

    public function test_where_row_in_throws_when_row_has_too_many_values(): void
    {
        $builder = $this->app['db']
            ->connection()
            ->query()
            ->from('users');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Row at index 0 must have exactly 2 value(s) to match the given columns, 3 given.',
        );

        $builder->whereRowIn(
            ['id', 'name'],
            [[1, 'Alice', 'extra']],
        );
    }

    public function test_where_row_in_throws_when_row_has_too_few_values(): void
    {
        $builder = $this->app['db']
            ->connection()
            ->query()
            ->from('users');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Row at index 1 must have exactly 2 value(s) to match the given columns, 1 given.',
        );

        $builder->whereRowIn(
            ['id', 'name'],
            [
                [1, 'Alice'],
                [2],
            ],
        );
    }

    public function test_where_row_in_count_mismatch_does_not_add_partial_bindings(): void
    {
        $builder = $this->app['db']
            ->connection()
            ->query()
            ->from('users');

        try {
            $builder->whereRowIn(
                ['id', 'name'],
                [[1, 'Alice', 'extra']],
            );
        } catch (InvalidArgumentException) {
            // Expected — assert no bindings leaked before the exception.
        }

        $this->assertSame([], $builder->getBindings());
    }

    // -----------------------------------------------------------------
    // Non-array / flat rows
    // -----------------------------------------------------------------

    public function test_where_row_in_throws_when_row_is_not_an_array(): void
    {
        $builder = $this->app['db']
            ->connection()
            ->query()
            ->from('users');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Row at index 0 must be an array of values, int given.',
        );

        // A common mistake: passing a flat pair instead of [[1, 'Alice']].
        $builder->whereRowIn(['id', 'name'], [1, 'Alice']);
    }

    public function test_where_row_in_throws_with_correct_type_name_for_string_row(): void
    {
        $builder = $this->app['db']
            ->connection()
            ->query()
            ->from('users');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Row at index 1 must be an array of values, string given.',
        );

        $builder->whereRowIn(['id', 'name'], [[1, 'Alice'], 'oops']);
    }

    // -----------------------------------------------------------------
    // Associative rows get normalized to sequential
    // -----------------------------------------------------------------

    public function test_where_row_in_normalizes_associative_row_arrays(): void
    {
        $query = $this->app['db']
            ->connection()
            ->table('users')
            ->whereRowIn(
                ['user_id', 'role_id'],
                [
                    ['user_id' => 1, 'role_id' => 2],
                    ['user_id' => 3, 'role_id' => 4],
                ],
            );

        expect($query->toSql())
            ->toBe('select * from "users" where ("user_id", "role_id") in ((?, ?), (?, ?))')
            ->and($query->getBindings())
            ->toBe([1, 2, 3, 4]);
    }

    // -----------------------------------------------------------------
    // Arrayable input (Collections)
    // -----------------------------------------------------------------

    public function test_where_row_in_accepts_a_collection_of_rows(): void
    {
        $query = $this->app['db']
            ->connection()
            ->table('users')
            ->whereRowIn(
                ['user_id', 'role_id'],
                collect([
                    [1, 2],
                    [3, 4],
                ]),
            );

        expect($query->toSql())
            ->toBe('select * from "users" where ("user_id", "role_id") in ((?, ?), (?, ?))')
            ->and($query->getBindings())
            ->toBe([1, 2, 3, 4]);
    }

    public function test_where_row_in_accepts_a_collection_as_an_individual_row(): void
    {
        $query = $this->app['db']
            ->connection()
            ->table('users')
            ->whereRowIn(
                ['user_id', 'role_id'],
                [
                    new Collection([1, 2]),
                    [3, 4],
                ],
            );

        expect($query->toSql())
            ->toBe('select * from "users" where ("user_id", "role_id") in ((?, ?), (?, ?))')
            ->and($query->getBindings())
            ->toBe([1, 2, 3, 4]);
    }

    public function test_where_row_in_validates_rows_inside_a_collection_of_values(): void
    {
        $builder = $this->app['db']
            ->connection()
            ->query()
            ->from('users');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Row at index 0 must have exactly 2 value(s) to match the given columns, 3 given.',
        );

        $builder->whereRowIn(
            ['id', 'name'],
            collect([[1, 'Alice', 'extra']]),
        );
    }
}
