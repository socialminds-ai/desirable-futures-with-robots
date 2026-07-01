<?php
declare(strict_types=1);

/**
 * Load the bundled GeoNames city dataset into the `cities` table.
 * Idempotent: truncates and reloads. Run after migrations:
 *   php db/seed_cities.php
 *
 * Data: GeoNames cities15000 (CC BY 4.0) — db/seeds/cities15000.tsv,
 * columns: name, asciiname, country, lat, lng, population.
 */

require_once __DIR__ . '/../lib/db.php';

$file = __DIR__ . '/seeds/cities15000.tsv';
$fh = fopen($file, 'r');
if (!$fh) {
    fwrite(STDERR, "Cannot open {$file}\n");
    exit(1);
}

$pdo = db();
$pdo->exec('TRUNCATE TABLE cities');

$cols   = 'INSERT INTO cities (name, asciiname, country, lat, lng, population) VALUES ';
$params = [];
$rows   = 0;
$total  = 0;

$flush = static function () use (&$params, &$rows, $pdo, $cols): void {
    if ($rows === 0) {
        return;
    }
    $placeholders = implode(',', array_fill(0, $rows, '(?,?,?,?,?,?)'));
    $pdo->prepare($cols . $placeholders)->execute($params);
    $params = [];
    $rows = 0;
};

$pdo->beginTransaction();
while (($line = fgets($fh)) !== false) {
    $line = rtrim($line, "\r\n");
    if ($line === '') {
        continue;
    }
    $c = explode("\t", $line);
    if (count($c) < 6) {
        continue;
    }
    array_push($params, $c[0], $c[1], $c[2], $c[3], $c[4], (int) $c[5]);
    $rows++;
    $total++;
    if ($rows >= 500) {
        $flush();
    }
}
$flush();
$pdo->commit();
fclose($fh);

fwrite(STDOUT, "Seeded {$total} cities.\n");
