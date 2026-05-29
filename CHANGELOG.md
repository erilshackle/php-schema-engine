# Changelog

## [0.3.0] - 2026-05-29

### Added

- Added MySQL integration test coverage.
- Added partial rollback infrastructure.
- Added foreign key diffing for missing foreign keys.
- Added dependency ordering for table creation.
- Added snapshot support.
- Added project initialization workflow (`--init`).
- Added configurable project bootstrap support.
- Added `ForeignColumn` builder.
- Added `Table::foreign()` DSL method.
- Added `Column::constrained()` DSL method.
- Added foreign key inference from column names.
- Added project configuration loader.
- Added `schema-engine.php` configuration file.
- Added generated project structure:
  - `database/schema.php`
  - `database/bootstrap.sql`
  - `storage/`

### Changed

- Reworked foreign key DSL completely.
- Replaced `foreignId()` approach with `foreign()`.
- Foreign key declarations are now centered around:
  - `foreign()->references()`
  - `column()->constrained()`
- Improved schema initialization workflow.
- Improved migration command architecture.
- Improved configuration handling.
- Improved introspection stability.

### Removed

- Removed `ForeignIdColumn`.
- Removed legacy foreign key builder flow.

---

## [0.2.0] - 2026-05-26

### Added

- Schema diff engine support for:
  - adding indexes
  - dropping indexes
  - adding foreign keys
  - dropping foreign keys

- Added CLI commands:
  - `fresh`
  - `rollback`
  - `generate-models`
  - `clear-history`
  - `trace`

- Added model generator.
- Added configuration file support through `schema-engine.php`.
- Added optional project bootstrap support through `bootstrap.php`.

---

## [0.1.0] - 2026-05-26

### Added

- Initial PHP schema DSL.
- Schema metadata layer:
  - `SchemaDefinition`
  - `TableDefinition`
  - `ColumnDefinition`
  - `IndexDefinition`
  - `ForeignKeyDefinition`

- Schema file loader.
- MySQL/MariaDB database introspection.
- Type normalization for MySQL/MariaDB.

- Schema diff engine support for:
  - creating tables
  - adding columns
  - modifying columns
  - dropping columns
  - dropping tables

- SQL generator with MySQL grammar.
- Migration executor.

- CLI commands:
  - `--init`
  - `--dry-run`
  - `--yes`
  - `--force`
  - `--status`

- Migration history table:
  - `schema_migrations`

- Index support during table creation.
- Foreign key support during table creation.
- Raw SQL expression support.
- Default expression support.

### Changed

- Indexes and foreign keys became metadata-driven.
- Rename detection disabled by default for safety.