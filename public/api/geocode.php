<?php
declare(strict_types=1);

/*
 * First-party, offline city geocoding. Given a city + country, return the
 * best-matching city-centre coordinates from the bundled GeoNames dataset.
 * No third-party calls. Returns {} when there's no match.
 */

require_once __DIR__ . '/../../lib/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=86400');

$city    = trim((string) ($_GET['city'] ?? ''));
$country = trim((string) ($_GET['country'] ?? ''));

if ($city === '' || $country === '' || mb_strlen($city) > 120 || mb_strlen($country) > 100) {
    echo '{}';
    exit;
}

$stmt = db()->prepare(
    'SELECT lat, lng FROM cities
      WHERE country = ? AND (name = ? OR asciiname = ?)
      ORDER BY population DESC
      LIMIT 1'
);
$stmt->execute([$country, $city, $city]);
$row = $stmt->fetch();

echo $row
    ? json_encode(['lat' => (float) $row['lat'], 'lng' => (float) $row['lng']])
    : '{}';
