<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/csrf.php';
require_once __DIR__ . '/../lib/validate.php';

$f       = require_login();
$notice  = [];
$errors  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $errors[] = 'Your session expired. Please try again.';
    } elseif (($_POST['action'] ?? '') === 'delete') {
        db()->prepare('DELETE FROM facilitators WHERE id = ?')->execute([(int) $f['id']]);
        auth_logout();
        header('Location: index.php?deleted=1');
        exit;
    } elseif (($_POST['action'] ?? '') === 'update') {
        $name        = v_string($_POST['name'] ?? '', 200, 1);
        $institution = v_string($_POST['institution'] ?? '', 200);
        $country     = v_string($_POST['country'] ?? '', 100);
        $continentIn = trim((string) ($_POST['continent'] ?? ''));
        $continent   = $continentIn === '' ? null : v_continent($continentIn);
        $label       = v_string($_POST['location_label'] ?? '', 200);
        $lat         = v_lat($_POST['lat'] ?? '');
        $lng         = v_lng($_POST['lng'] ?? '');
        $showMap     = isset($_POST['show_on_map']) ? 1 : 0;
        $showId      = isset($_POST['show_identity']) ? 1 : 0;

        if ($name === null)  $errors[] = 'Please enter your name.';
        if ($continentIn !== '' && $continent === null) $errors[] = 'Please choose a valid continent.';
        if (($lat === null) !== ($lng === null)) { $lat = $lng = null; }

        if (!$errors) {
            db()->prepare(
                'UPDATE facilitators SET name=?, institution=?, country=?, continent=?,
                 lat=?, lng=?, location_label=?, show_on_map=?, show_identity=? WHERE id=?'
            )->execute([$name, $institution, $country, $continent, $lat, $lng, $label,
                $showMap, $showId, (int) $f['id']]);
            $notice[] = 'Your details were updated.';
            // Refresh in-memory copy.
            $stmt = db()->prepare('SELECT * FROM facilitators WHERE id = ?');
            $stmt->execute([(int) $f['id']]);
            $f = $stmt->fetch();
        }
    }
}

$page_title = 'Your account — Desirable Futures with robots';
$page_head  = '<link rel="stylesheet" href="assets/leaflet/leaflet.css" />';
$e = static fn (string $k): string => htmlspecialchars((string) ($f[$k] ?? ''), ENT_QUOTES);

require dirname(__DIR__) . '/templates/header.php';
?>
<section class="section section--form">
  <div class="section__marker"><span class="numeral">◈</span><span class="label">Your account</span></div>
  <h1 class="section__title">Hello, <?= htmlspecialchars((string) $f['name']) ?>.</h1>

  <?php if (!empty($_GET['verified'])): ?>
    <div class="form-notice" role="status"><p>Your email is confirmed and your account is active. Welcome!</p></div>
  <?php endif; ?>
  <?php foreach ($notice as $n): ?><div class="form-notice" role="status"><p><?= htmlspecialchars($n) ?></p></div><?php endforeach; ?>
  <?php if ($errors): ?>
    <div class="form-errors" role="alert"><ul>
      <?php foreach ($errors as $err): ?><li><?= htmlspecialchars($err) ?></li><?php endforeach; ?>
    </ul></div>
  <?php endif; ?>

  <div class="prose prose--narrow">
    <p>Signed in as <strong><?= htmlspecialchars((string) $f['email']) ?></strong>.</p>
  </div>

  <form class="form" method="post" action="account.php" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="update" />

    <p class="form-row">
      <label for="name">Your name <span class="req">*</span></label>
      <input type="text" id="name" name="name" required maxlength="200" value="<?= $e('name') ?>" autocomplete="name" />
    </p>
    <p class="form-row">
      <label for="institution">Institution</label>
      <input type="text" id="institution" name="institution" maxlength="200" value="<?= $e('institution') ?>" autocomplete="organization" />
    </p>
    <div class="form-row form-row--split">
      <span>
        <label for="country">Country</label>
        <input type="text" id="country" name="country" maxlength="100" value="<?= $e('country') ?>" autocomplete="country-name" />
      </span>
      <span>
        <label for="continent">Continent</label>
        <select id="continent" name="continent">
          <?php foreach (['', 'Africa', 'Asia', 'Europe', 'North America', 'South America', 'Oceania', 'Antarctica'] as $c):
            $sel = ((string) ($f['continent'] ?? '') === $c) ? ' selected' : ''; ?>
            <option value="<?= htmlspecialchars($c, ENT_QUOTES) ?>"<?= $sel ?>><?= $c === '' ? '—' : htmlspecialchars($c) ?></option>
          <?php endforeach; ?>
        </select>
      </span>
    </div>

    <fieldset class="form-fieldset">
      <legend>Where are you based?</legend>
      <p class="form-hint">Click the map to move your pin (stored at city level, ~1&nbsp;km).</p>
      <p class="form-row">
        <label for="location_label">Location label</label>
        <input type="text" id="location_label" name="location_label" maxlength="200" placeholder="e.g. Barcelona, Spain" value="<?= $e('location_label') ?>" />
      </p>
      <div id="map-picker" class="map-picker" aria-hidden="true"></div>
      <p class="form-hint" id="coord-readout" aria-live="polite">
        <?php if ($f['lat'] !== null && $f['lng'] !== null): ?>Selected: <?= $e('lat') ?>, <?= $e('lng') ?><?php else: ?>No pin placed.<?php endif; ?>
        <button type="button" id="clear-pin" class="btn-inline">Clear pin</button>
      </p>
      <input type="hidden" id="lat" name="lat" value="<?= $e('lat') ?>" />
      <input type="hidden" id="lng" name="lng" value="<?= $e('lng') ?>" />

      <p class="form-check">
        <label><input type="checkbox" name="show_on_map" value="1" <?= $f['show_on_map'] ? 'checked' : '' ?> />
          Show my location on the public map (anonymous dot).</label>
      </p>
      <p class="form-check">
        <label><input type="checkbox" name="show_identity" value="1" <?= $f['show_identity'] ? 'checked' : '' ?> />
          Also show my name and institution on my pin.</label>
      </p>
    </fieldset>

    <p class="form-actions">
      <button type="submit" class="btn btn--primary"><span>Save changes</span><span class="arrow" aria-hidden="true">→</span></button>
    </p>
  </form>

  <div class="account-secondary">
    <form method="post" action="logout.php" class="inline-form">
      <?= csrf_field() ?>
      <button type="submit" class="btn btn--ghost">Sign out</button>
    </form>

    <form method="post" action="account.php" class="inline-form" onsubmit="return confirm('Permanently delete your account and all your data? This cannot be undone.');">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="delete" />
      <button type="submit" class="btn btn--danger">Delete my account</button>
    </form>
  </div>
</section>

<?php
$page_scripts = <<<'HTML'
<script src="assets/leaflet/leaflet.js"></script>
<script>
(function () {
  if (!window.L) return;
  L.Icon.Default.imagePath = 'assets/leaflet/images/';
  var el = document.getElementById('map-picker'); el.removeAttribute('aria-hidden');
  var latEl = document.getElementById('lat'), lngEl = document.getElementById('lng');
  var readout = document.getElementById('coord-readout');
  var map = L.map('map-picker').setView([20, 0], 1);
  L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 18, attribution: '&copy; OpenStreetMap contributors'
  }).addTo(map);
  var marker = null;
  function round2(n) { return Math.round(n * 100) / 100; }
  function place(lat, lng) {
    latEl.value = lat; lngEl.value = lng;
    if (marker) marker.setLatLng([lat, lng]); else marker = L.marker([lat, lng]).addTo(map);
    readout.firstChild.nodeValue = 'Selected: ' + lat + ', ' + lng + ' ';
  }
  if (latEl.value && lngEl.value) { place(+latEl.value, +lngEl.value); map.setView([+latEl.value, +lngEl.value], 6); }
  map.on('click', function (e) { place(round2(e.latlng.lat), round2(e.latlng.lng)); });
  document.getElementById('clear-pin').addEventListener('click', function () {
    latEl.value = ''; lngEl.value = '';
    if (marker) { map.removeLayer(marker); marker = null; }
    readout.firstChild.nodeValue = 'No pin placed. ';
  });
})();
</script>
HTML;
require dirname(__DIR__) . '/templates/footer.php';
