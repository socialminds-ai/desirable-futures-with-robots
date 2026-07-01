<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/csrf.php';
require_once __DIR__ . '/../lib/validate.php';
require_once __DIR__ . '/../lib/countries.php';

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
        $city        = v_string($_POST['city'] ?? '', 120);
        $country     = v_country($_POST['country'] ?? '');
        $lat         = v_lat($_POST['lat'] ?? '');
        $lng         = v_lng($_POST['lng'] ?? '');
        $showMap     = isset($_POST['show_on_map']) ? 1 : 0;
        $showId      = isset($_POST['show_identity']) ? 1 : 0;

        if ($name === null)  $errors[] = 'Please enter your name.';
        if (($lat === null) !== ($lng === null)) { $lat = $lng = null; }

        if (!$errors) {
            db()->prepare(
                'UPDATE facilitators SET name=?, institution=?, city=?, country=?,
                 lat=?, lng=?, show_on_map=?, show_identity=? WHERE id=?'
            )->execute([$name, $institution, $city, $country, $lat, $lng,
                $showMap, $showId, (int) $f['id']]);
            $notice[] = 'Your details were updated.';
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

    <fieldset class="form-fieldset">
      <legend>Where are you based?</legend>
      <p class="form-hint">Update your city and country to move the pin, or click the map (stored at city level, ~1&nbsp;km).</p>

      <div class="form-row form-row--split">
        <span>
          <label for="city">City</label>
          <input type="text" id="city" name="city" maxlength="120" value="<?= $e('city') ?>" autocomplete="address-level2" list="city-list" />
          <datalist id="city-list"></datalist>
        </span>
        <span>
          <label for="country">Country</label>
          <select id="country" name="country">
            <option value="">—</option>
            <?php foreach (df_countries() as $c):
              $sel = ((string) ($f['country'] ?? '') === $c) ? ' selected' : ''; ?>
              <option value="<?= htmlspecialchars($c, ENT_QUOTES) ?>"<?= $sel ?>><?= htmlspecialchars($c) ?></option>
            <?php endforeach; ?>
          </select>
        </span>
      </div>

      <div id="map-picker" class="map-picker" aria-hidden="true" style="height:340px"></div>
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
  var cityEl = document.getElementById('city'), countryEl = document.getElementById('country');
  var readout = document.getElementById('coord-readout');
  var map = L.map('map-picker').setView([20, 0], 1);
  L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 18, attribution: '&copy; OpenStreetMap contributors'
  }).addTo(map);
  var marker = null;
  function round2(n) { return Math.round(n * 100) / 100; }
  function place(lat, lng, zoom) {
    latEl.value = lat; lngEl.value = lng;
    if (marker) marker.setLatLng([lat, lng]); else marker = L.marker([lat, lng]).addTo(map);
    readout.firstChild.nodeValue = 'Selected: ' + lat + ', ' + lng + ' ';
    if (zoom) map.setView([lat, lng], zoom);
  }
  if (latEl.value && lngEl.value) { place(+latEl.value, +lngEl.value); map.setView([+latEl.value, +lngEl.value], 6); }
  map.on('click', function (e) { place(round2(e.latlng.lat), round2(e.latlng.lng)); });
  document.getElementById('clear-pin').addEventListener('click', function () {
    latEl.value = ''; lngEl.value = '';
    if (marker) { map.removeLayer(marker); marker = null; }
    readout.firstChild.nodeValue = 'No pin placed. ';
  });
  function geocode() {
    var city = cityEl.value.trim(), country = countryEl.value;
    if (!city || !country) return;
    fetch('api/geocode.php?city=' + encodeURIComponent(city) + '&country=' + encodeURIComponent(country))
      .then(function (r) { return r.json(); })
      .then(function (d) { if (d && typeof d.lat === 'number') place(round2(d.lat), round2(d.lng), 10); })
      .catch(function () {});
  }
  cityEl.addEventListener('change', geocode);
  countryEl.addEventListener('change', geocode);
  // City-name autocomplete (suggestions only — any city name is accepted).
  var dataList = document.getElementById('city-list');
  var acTimer;
  cityEl.addEventListener('input', function () {
    clearTimeout(acTimer);
    var q = cityEl.value.trim(), country = countryEl.value;
    if (!dataList || !country || q.length < 2) return;
    acTimer = setTimeout(function () {
      fetch('api/cities.php?country=' + encodeURIComponent(country) + '&q=' + encodeURIComponent(q))
        .then(function (r) { return r.json(); })
        .then(function (list) {
          dataList.innerHTML = '';
          list.forEach(function (n) { var o = document.createElement('option'); o.value = n; dataList.appendChild(o); });
        }).catch(function () {});
    }, 220);
  });
})();
</script>
HTML;
require dirname(__DIR__) . '/templates/footer.php';
