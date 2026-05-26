# Changelog

*Changelog created using the [Simple Changelog](https://marketplace.visualstudio.com/items?itemName=tobiaswaelde.vscode-simple-changelog) extension for VS Code.*

## [0.3.0] - 2026-05-26
### Added
- Added MySQL integration test coverage
- Added partial rollback infrastructure
- Added foreign key diffing for missing FKs
- Added dependency ordering for table creation

## [0.2.0] - 2026-05-26
- Schema diff engine for:
- adding index
- drop index
- add foreign Keys
- drop foreign keys
- Added CLI command:
- `fresh`,
- `rollback`,
- `generate-models`,
- `clear-history`,
- `trace`,
- Model generator.
- Config file support through `schema-engine.php`.
- Optional project bootstrap file support via `bootstrap.php`.

## [0.1.0]
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
- Schema diff engine for:
- creating tables
- adding columns
- modifying columns
- dropping columns
- dropping tables
- SQL generator with MySQL grammar.
- Migration executor.
- CLI command:
- `--init`
- `--dry-run`
- `--yes`
- `--force`
- `--status`
- Migration history table:
- `schema_migrations`
- Index support during table creation.
- Foreign key support during table creation.
- Expression/default raw support.
- Config file support through `config/database.php`.

### Changed
- Indexes and foreign keys are metadata-driven in V1.
- Rename detection is disabled by default for safety.