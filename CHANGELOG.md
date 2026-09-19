# Changelog

All notable changes to this project will be documented in this file.

## [Unreleased](https://github.com/swadhin-sikder/laravel-row-in/compare/v1.0.0...master)

### Fixed

- SQL Server row-in subqueries are now rejected with an `InvalidArgumentException` instead of compiling to unsupported row-value `IN` syntax.

## [v1.0.0](https://github.com/swadhin-sikder/laravel-row-in/releases/tag/v1.0.0) - 2026-09-18

### Added

- Added `whereRowIn()` query builder macro.
- Added `orWhereRowIn()` query builder macro.
- Added `whereNotRowIn()` query builder macro.
- Added `orWhereNotRowIn()` query builder macro.
- Added support for row-value `IN` and `NOT IN` queries.
- Added support for MySQL, PostgreSQL, SQLite, and SQL Server.
- Added SQL Server fallback using `OR`/`AND` conditions.
- Added support for subqueries and raw expressions.
- Added support for `Collection` and `Arrayable` values.
- Added validation for invalid row/column combinations and row values.
