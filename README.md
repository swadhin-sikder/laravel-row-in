<div align="center">
    <h1>Laravel Row In</h1>
</div>

<p align="center">
    <a href="https://packagist.org/packages/swadhin-sikder/laravel-row-in"><img src="https://img.shields.io/packagist/v/swadhin-sikder/laravel-row-in.svg?style=flat-square" alt="Packagist"></a>
    <a href="https://packagist.org/packages/swadhin-sikder/laravel-row-in"><img src="https://img.shields.io/packagist/php-v/swadhin-sikder/laravel-row-in.svg?style=flat-square" alt="PHP from Packagist"></a>
    <a href="https://packagist.org/packages/swadhin-sikder/laravel-row-in"><img src="https://badge.laravel.cloud/badge/swadhin-sikder/laravel-row-in?style=flat" alt="Laravel versions"></a>
    <a href="https://github.com/swadhin-sikder/laravel-row-in/actions"><img alt="GitHub Workflow Status (main)" src="https://img.shields.io/github/actions/workflow/status/swadhin-sikder/laravel-row-in/tests.yml?branch=main&label=Tests&style=flat-square"></a>
    <a href="https://packagist.org/packages/swadhin-sikder/laravel-row-in"><img src="https://img.shields.io/packagist/dt/swadhin-sikder/laravel-row-in.svg?style=flat-square" alt="Total Downloads"></a>
</p>

Adds row value `IN` and `NOT IN` support to Laravel's query builder — the composite-key equivalent of `whereIn`, so you can match multiple columns against a list of tuples in a single clause instead of chaining `orWhere` calls.

```php
// Instead of this...
$query->where(function ($query) {
    $query->where('user_id', 1)->where('role_id', 2);
})->orWhere(function ($query) {
    $query->where('user_id', 3)->where('role_id', 4);
});

// ...write this:
$query->whereRowIn(['user_id', 'role_id'], [
    [1, 2],
    [3, 4],
]);
```

## Installation

You can install the package via Composer:

```bash
composer require swadhin-sikder/laravel-row-in
```

The package registers its service provider automatically via Laravel's package discovery — no configuration, config files, or migrations to publish. Once installed, `whereRowIn`, `whereNotRowIn`, `orWhereRowIn`, and `orWhereNotRowIn` are available on every query builder instance.

PhpStorm support is included through an IDE helper file, so these methods are available to autocomplete and navigation after Composer has indexed the package.

## Usage

### Basic usage

`whereRowIn` and `whereNotRowIn` take an array of columns and an array of value tuples, one tuple per row:

```php
use Illuminate\Support\Facades\DB;

DB::table('user_roles')
    ->whereRowIn(['user_id', 'role_id'], [
        [1, 2],
        [3, 4],
    ])
    ->get();
```

```sql
select * from "user_roles" where ("user_id", "role_id") in ((1, 2), (3, 4))
```

`whereNotRowIn` compiles the negated form:

```php
DB::table('user_roles')
    ->whereNotRowIn(['user_id', 'role_id'], [
        [1, 2],
        [3, 4],
    ])
    ->get();
```

```sql
select * from "user_roles" where ("user_id", "role_id") not in ((1, 2), (3, 4))
```

Any number of columns is supported — two is the common case (composite foreign keys, tenant-scoped IDs), but there's no upper limit:

```php
DB::table('inventory')->whereRowIn(['warehouse_id', 'sku', 'batch'], [
    [1, 'ABC-123', 'B01'],
    [2, 'ABC-123', 'B02'],
]);
```

### Combining with other clauses

Both methods behave like any other `where*` call and chain normally:

```php
DB::table('users')
    ->where('active', true)
    ->whereRowIn(['user_id', 'role_id'], [[1, 2], [3, 4]])
    ->orderBy('id')
    ->get();
```

### `or` variants

Use `orWhereRowIn` / `orWhereNotRowIn` to combine with the preceding clause using `or` instead of `and`:

```php
DB::table('users')
    ->where('is_admin', true)
    ->orWhereRowIn(['user_id', 'role_id'], [[1, 2], [3, 4]])
    ->get();
```

### Subqueries

Pass a `Closure`, a `Builder` instance, or anything else Laravel's query builder considers "queryable" instead of an array of values to match against a subquery:

```php
DB::table('users')->whereRowIn(
    ['user_id', 'role_id'],
    DB::table('user_roles')->select(['user_id', 'role_id']),
);
```

```sql
select * from "users" where ("user_id", "role_id") in (
    select "user_id", "role_id" from "user_roles"
)
```

### Collections

`$values`, and any individual row within it, may be an `Illuminate\Support\Collection` (or anything implementing `Arrayable`) instead of a plain array:

```php
DB::table('users')->whereRowIn(
    ['user_id', 'role_id'],
    collect([[1, 2], [3, 4]]),
);
```

### Raw expressions

Individual values within a row can be raw expressions via `DB::raw()` or `Illuminate\Database\Query\Expression`:

```php
use Illuminate\Support\Facades\DB;

DB::table('users')->whereRowIn(['user_id', 'role_id'], [
    [DB::raw('current_user_id()'), 2],
]);
```

### Database support

`whereRowIn` / `whereNotRowIn` compile to native row-value `IN` syntax on MySQL, PostgreSQL, and SQLite:

```sql
where ("user_id", "role_id") in ((1, 2), (3, 4))
```

SQL Server has no native row-value `IN` syntax, so on that grammar the same call is automatically compiled to an equivalent `OR`-of-`AND` expression instead:

```sql
where (([user_id] = 1 and [role_id] = 2) or ([user_id] = 3 and [role_id] = 4))
```

You don't need to do anything differently — the correct SQL is chosen automatically based on the connection's grammar.

Subqueries are rejected for row-in clauses on SQL Server because SQL Server does not support the row-value `IN` syntax required for this form. An `InvalidArgumentException` is thrown; use an array of rows with SQL Server, or use a database engine with native row-value `IN` support for subqueries.

### Edge cases

- **Empty value lists.** `whereRowIn($columns, [])` compiles to `0 = 1` (never matches) and `whereNotRowIn($columns, [])` compiles to `1 = 1` (always matches) — the same convention Laravel's own `whereIn`/`whereNotIn` use for an empty array, rather than producing invalid SQL.
- **Row/column count mismatches.** Every row must have exactly as many values as there are columns. A row with too many or too few values throws an `InvalidArgumentException` naming the offending row index, before any query bindings are added.
- **Associative rows.** Rows may be associative arrays (e.g. `['user_id' => 1, 'role_id' => 2]`) — they're normalized to positional order internally, so the column order you pass is always what's matched against.
- **Non-array rows.** Passing a flat list instead of a list of tuples (e.g. `[1, 2]` instead of `[[1, 2]]`) throws a clear `InvalidArgumentException` rather than an error deep inside the SQL grammar.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Thank you for considering contributing to Laravel Row In! Please review our [contributing guide](.github/CONTRIBUTING.md) to get started.

## Security Vulnerabilities

Please review [our security policy](.github/SECURITY.md) on how to report security vulnerabilities.

## Credits

- [Swadhin Sikder](https://github.com/swadhin-sikder)
- [All Contributors](../../contributors)

## License

Laravel Row In is open-sourced software licensed under the [MIT license](LICENSE.md).
