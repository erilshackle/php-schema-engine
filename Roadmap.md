# Roadmap

## V0.1 — Foundation ✅

### DSL

- Schema DSL
- Column builders
- Index metadata
- Foreign key metadata

### Database

- MySQL/MariaDB introspection
- Type normalization

### Diff Engine

- Table creation
- Table deletion
- Column creation
- Column modification
- Column deletion

### Execution

- SQL generator
- Migration executor
- Migration history

### CLI

- `--init`
- `--dry-run`
- `--yes`
- `--force`
- `--status`

---

## V0.2 — Developer Experience ✅

### CLI

- `--fresh`
- `--rollback`
- `--generate-models`
- `--clear-history`
- `--trace`

### Project Bootstrap

- `schema-engine.php`
- optional `bootstrap.php`
- project initialization

### Generation

- Model generator
- Schema snapshots

### DSL

- Foreign key inference
- `foreign()->references()`
- `column()->constrained()`

---

## V0.3 — Advanced Diffing ✅

### Diff Engine

- Index diffing
- Foreign key diffing
- Dependency-aware table creation

### Recovery

- Partial rollback infrastructure

### Quality

- MySQL integration tests

---

## V0.4 — Schema Safety

### Planned

- Explicit rename operations
- Table recreation mode
- Destructive operation planner
- Safer column recreation workflow
- Migration preview improvements

---

## V0.5 — Database Features

### Planned


- Composite foreign keys
- Composite primary keys
- Check constraints
- Generated columns
- Enum support
- Spatial types

---

## V0.6 — Multi-Driver Foundation

### Planned

- PostgreSQL grammar
- PostgreSQL introspection
- Driver abstraction improvements
- SQLite support investigation

---

## V0.7 — Snapshots & History

### Planned

- Snapshot versioning
- Snapshot comparison
- Schema history browser
- Restore from snapshot

---

## V1.0 — Stable Release

### Goals

- Stable public API
- Stable DSL
- Stable migration workflow
- Full documentation
- GitHub Actions CI
- High test coverage
- Production readiness