<?php
declare(strict_types=1);

/**
 * Forward-only migration runner.
 *
 * Applies every db/migrations/NNNN_*.sql that has not yet run, in filename
 * order, and records applied versions in schema_migrations. Safe to re-run.
 *
 * Usage (from a container or host with PHP + DB access):
 *   php db/migrate.php
 */

require_once __DIR__ . '/../lib/db.php';

$pdo = db();

$pdo->exec(
    'CREATE TABLE IF NOT EXISTS schema_migrations (
        version    VARCHAR(255) PRIMARY KEY,
        applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
);

$applied = array_flip(
    $pdo->query('SELECT version FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN)
);

$files = glob(__DIR__ . '/migrations/*.sql') ?: [];
sort($files);

$ran = 0;
foreach ($files as $file) {
    $version = basename($file, '.sql');
    if (isset($applied[$version])) {
        continue;
    }

    fwrite(STDOUT, "Applying {$version} ... ");
    try {
        $pdo->exec((string) file_get_contents($file));
        $pdo->prepare('INSERT INTO schema_migrations (version) VALUES (?)')
            ->execute([$version]);
        fwrite(STDOUT, "ok\n");
        $ran++;
    } catch (Throwable $e) {
        fwrite(STDOUT, "FAILED\n");
        fwrite(STDERR, $e->getMessage() . "\n");
        exit(1);
    }
}

fwrite(STDOUT, $ran === 0 ? "Already up to date.\n" : "Applied {$ran} migration(s).\n");
