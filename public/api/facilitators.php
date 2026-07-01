<?php
declare(strict_types=1);

require_once __DIR__ . '/../../lib/db.php';

/*
 * Public map data: active facilitators who opted in, with a placed pin.
 * Anonymous by default (lat/lng only); name + institution are included only
 * where the facilitator additionally opted in to show their identity.
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=300');

$rows = db()->query(
    'SELECT lat, lng, show_identity, name, institution
       FROM facilitators
      WHERE status = "active" AND show_on_map = 1
        AND lat IS NOT NULL AND lng IS NOT NULL'
)->fetchAll();

$points = [];
foreach ($rows as $r) {
    $p = ['lat' => (float) $r['lat'], 'lng' => (float) $r['lng']];
    if ((int) $r['show_identity'] === 1) {
        $p['name'] = $r['name'];
        if ($r['institution'] !== null && $r['institution'] !== '') {
            $p['institution'] = $r['institution'];
        }
    }
    $points[] = $p;
}

echo json_encode($points, JSON_UNESCAPED_UNICODE);
