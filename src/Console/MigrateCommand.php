<?php

namespace SchemaEngine\Console;

use PDO;
use SchemaEngine\Config\ConfigLoader;
use SchemaEngine\Database\ConnectionFactory;
use SchemaEngine\Database\Introspection\MySQLIntrospector;
use SchemaEngine\Diff\SchemaDiffer;
use SchemaEngine\Execution\DatabaseResetter;
use SchemaEngine\Execution\MigrationRepository;
use SchemaEngine\Execution\Migrator;
use SchemaEngine\Generator\ModelGenerator;
use SchemaEngine\Loader\SchemaLoader;
use SchemaEngine\Metadata\SchemaDefinition;
use SchemaEngine\Operations\Operation;
use SchemaEngine\Snapshot\SnapshotManager;

class MigrateCommand
{
    protected string $root;

    protected ConfigLoader $configLoader;

    protected array $options = [];

    protected array $config = [];

    protected ?PDO $pdo = null;

    public function __construct(
        ?string $root = null
    ) {
        $this->root = $root ?? getcwd();

        $this->configLoader =
            new ConfigLoader($this->root);
    }

    public function run(
        array $options = []
    ): int {
        $this->options = $options;

        $this->handleInit();

        $this->loadBootstrap();

        $this->config =
            $this->configLoader->load();

        $this->pdo = ConnectionFactory::make(
            $this->config['database']
        );

        $repository =
            new MigrationRepository($this->pdo);

        $this->handleStatus($repository);

        $this->handleRollback();

        $this->handleFresh();

        $desired =
            $this->loadDesiredSchema();

        $this->handleGenerateModels($desired);

        $current =
            $this->introspectCurrentSchema();

        $differ = new SchemaDiffer();

        $operations = $differ->diff(
            $current,
            $desired
        );

        $this->printWarnings(
            $differ->report()->warnings
        );

        if (empty($operations)) {
            echo "Database is up to date.\n";
            return 0;
        }

        $migrator =
            new Migrator($this->pdo);

        $this->printPlannedSql(
            $migrator,
            $operations
        );

        if ($this->isDryRun()) {
            return 0;
        }

        $this->confirmExecution();

        $migrator->run(
            $operations,
            dryRun: false,
            force: $this->isForce()
        );

        $this->saveSnapshot($desired);

        echo "Migration completed.\n";

        return 0;
    }

    protected function handleInit(): void
    {
        if (!$this->hasOption('init')) {
            return;
        }

        if (!$this->configLoader->create()) {
            echo "schema-engine.php already exists.\n";
            exit(0);
        }

        echo "schema-engine.php created.\n";
        exit(0);
    }

    protected function loadBootstrap(): void
    {
        $bootstrap = $this->root
            . DIRECTORY_SEPARATOR
            . 'bootstrap.php';

        if (file_exists($bootstrap)) {
            require $bootstrap;
        }
    }

    protected function handleStatus(
        MigrationRepository $repository
    ): void {
        if (!$this->hasOption('status')) {
            return;
        }

        $rows = $repository->all();

        if (empty($rows)) {
            echo "No migrations executed yet.\n";
            exit(0);
        }

        foreach ($rows as $row) {
            echo "[Batch {$row['batch']}] {$row['migration']} - {$row['executed_at']}\n";
        }

        exit(0);
    }

    protected function handleRollback(): void
    {
        if (!$this->hasOption('rollback')) {
            return;
        }

        $migrator = new Migrator($this->pdo);

        $sqlList = $migrator->rollback(
            dryRun: $this->isDryRun()
        );

        if (empty($sqlList)) {
            echo "Nothing to rollback.\n";
            exit(0);
        }

        echo "\nRollback SQL:\n\n";

        foreach ($sqlList as $sql) {
            echo $sql . "\n\n";
        }

        if ($this->isDryRun()) {
            exit(0);
        }

        echo "Rollback completed.\n";

        exit(0);
    }

    protected function handleFresh(): void
    {
        if (!$this->hasOption('fresh')) {
            return;
        }

        if (!$this->isForce()) {
            echo "--fresh requires --force.\n";
            exit(1);
        }

        $resetter =
            new DatabaseResetter($this->pdo);

        $resetter->fresh();

        if ($this->hasOption('clear-history')) {
            $resetter->clearHistory();

            echo "Migration history cleared.\n";
        }

        echo "Database dropped.\n";
    }

    protected function loadDesiredSchema(): SchemaDefinition
    {
        $loader = new SchemaLoader();

        return $loader->load(
            $this->configLoader->path(
                $this->config['schema']
            )
        );
    }

    protected function handleGenerateModels(
        SchemaDefinition $schema
    ): void {
        if (!$this->hasOption('generate-models')) {
            return;
        }

        $modelsConfig =
            $this->config['generator']['models'] ?? [];

        $modelsConfig['path'] =
            $this->configLoader->path(
                $modelsConfig['path'] ?? 'app/Models'
            );

        $generator = new ModelGenerator();

        $files = $generator->generate(
            $schema,
            $modelsConfig
        );

        echo "Generated models:\n\n";

        foreach ($files as $file) {
            echo "- {$file}\n";
        }

        exit(0);
    }

    protected function introspectCurrentSchema(): SchemaDefinition
    {
        $introspector = new MySQLIntrospector(
            $this->pdo,
            database: $this->config['database']['database']
        );

        return $introspector->getSchema();
    }

    protected function printWarnings(
        array $warnings
    ): void {
        if (empty($warnings)) {
            return;
        }

        echo "\nWarnings:\n\n";

        foreach ($warnings as $warning) {
            echo "- {$warning}\n";
        }

        echo "\n";
    }

    /**
     * @param Operation[] $operations
     */
    protected function printPlannedSql(
        Migrator $migrator,
        array $operations
    ): void {
        $preview = $migrator->run(
            $operations,
            dryRun: true,
            force: $this->isForce()
        );

        echo "\nPlanned SQL:\n\n";

        foreach ($preview as $sql) {
            echo $sql . "\n\n";
        }
    }

    protected function confirmExecution(): void
    {
        if ($this->hasOption('yes')) {
            return;
        }

        echo "Apply changes? [y/N]: ";

        $confirm = trim(
            strtolower(
                fgets(STDIN)
            )
        );

        if ($confirm !== 'y') {
            echo "Migration cancelled.\n";
            exit(0);
        }
    }

    protected function saveSnapshot(
        SchemaDefinition $schema
    ): void {
        $snapshots = new SnapshotManager();

        $snapshotPath =
            $this->configLoader->path(
                'storage/schema.snapshot.json'
            );

        $snapshotDir = dirname($snapshotPath);

        if (!is_dir($snapshotDir)) {
            mkdir($snapshotDir, 0777, true);
        }

        $snapshots->save(
            $schema,
            $snapshotPath
        );
    }

    protected function hasOption(
        string $name
    ): bool {
        return isset($this->options[$name]);
    }

    protected function isDryRun(): bool
    {
        return $this->hasOption('dry-run');
    }

    protected function isForce(): bool
    {
        return $this->hasOption('force');
    }
}
