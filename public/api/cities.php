<?php
declare(strict_types=1);

/*
 * City-name autocomplete suggestions (non-binding — the field stays free text).
 * Returns up to 10 city names in the given country matching the prefix,
 * most-populous first. First-party, offline (bundled GeoNames data).
 */

require_once __DIR__ . '/../../lib/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=86400');

$country = trim((string) ($_GET['country'] ?? ''));
$q       = trim((string) ($_GET['q'] ?? ''));

if ($country === '' || mb_strlen($q) < 2 || mb_strlen($q) > 120) {
    echo '[]';
    exit;
}

// Escape LIKE wildcards in user input; match as a prefix.
$prefix = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q) . '%';

$stmt = db()->prepare(
    'SELECT DISTINCT name FROM cities
      WHERE country = ? AND (name LIKE ? OR asciiname LIKE ?)
      ORDER BY population DESC
      LIMIT 10'
);
$stmt->execute([$country, $prefix, $prefix]);

echo json_encode($stmt->fetchAll(PDO::FETCH_COLUMN), JSON_UNESCAPED_UNICODE);
