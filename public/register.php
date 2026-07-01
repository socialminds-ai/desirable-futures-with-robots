<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/csrf.php';
require_once __DIR__ . '/../lib/validate.php';
require_once __DIR__ . '/../lib/mail.php';

df_session(); // start the session before any output so CSRF persists on GET

$cfg    = df_config();
$errors = [];
$done   = false;

$old = [
    'name' => '', 'email' => '', 'institution' => '', 'country' => '',
    'continent' => '', 'location_label' => '', 'lat' => '', 'lng' => '',
    'show_on_map' => true, 'show_identity' => false,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $errors[] = 'Your session expired. Please try again.';
    } elseif (!empty($_POST['website'])) {
        // Honeypot tripped — pretend success, store nothing.
        $done = true;
    } else {
        $name        = v_string($_POST['name'] ?? '', 200, 1);
        $email       = v_email($_POST['email'] ?? '');
        $institution = v_string($_POST['institution'] ?? '', 200);
        $country     = v_string($_POST['country'] ?? '', 100);
        $continentIn = trim((string) ($_POST['continent'] ?? ''));
        $continent   = $continentIn === '' ? null : v_continent($continentIn);
        $label       = v_string($_POST['location_label'] ?? '', 200);
        $lat         = v_lat($_POST['lat'] ?? '');
        $lng         = v_lng($_POST['lng'] ?? '');
        $showMap     = isset($_POST['show_on_map']) ? 1 : 0;
        $showId      = isset($_POST['show_identity']) ? 1 : 0;

        $old = [
            'name' => (string) ($_POST['name'] ?? ''),
            'email' => (string) ($_POST['email'] ?? ''),
            'institution' => (string) ($_POST['institution'] ?? ''),
            'country' => (string) ($_POST['country'] ?? ''),
            'continent' => $continentIn,
            'location_label' => (string) ($_POST['location_label'] ?? ''),
            'lat' => $lat ?? '', 'lng' => $lng ?? '',
            'show_on_map' => $showMap === 1, 'show_identity' => $showId === 1,
        ];

        if ($name === null)   $errors[] = 'Please enter your name.';
        if ($email === null)  $errors[] = 'Please enter a valid email address.';
        if ($continentIn !== '' && $continent === null) $errors[] = 'Please choose a valid continent.';
        // Coordinates only count as a pair.
        if (($lat === null) !== ($lng === null)) {
            $lat = $lng = null;
        }

        if (!$errors) {
            $pdo = db();
            $sel = $pdo->prepare('SELECT id, status FROM facilitators WHERE email = ? LIMIT 1');
            $sel->execute([$email]);
            $existing = $sel->fetch();

            if ($existing && $existing['status'] === 'active') {
                // Already registered — send a login link instead of leaking status.
                $token = auth_create_token((int) $existing['id'], 'login', 1800);
                send_mail(
                    $email,
                    'You are already registered — sign-in link',
                    "Hello,\n\nYou already have a facilitator account for Desirable "
                    . "Futures with Robots. Use this link to sign in (valid 30 minutes):\n\n"
                    . base_url() . '/login.php?token=' . $token
                    . "\n\n— Desirable Futures with Robots"
                );
            } else {
                if ($existing) {
                    $fid = (int) $existing['id'];
                    $pdo->prepare(
                        'UPDATE facilitators SET name=?, institution=?, country=?, continent=?,
                         lat=?, lng=?, location_label=?, show_on_map=?, show_identity=?,
                         consent_at=NOW(), consent_version=? WHERE id=?'
                    )->execute([$name, $institution, $country, $continent, $lat, $lng,
                        $label, $showMap, $showId, $cfg['consent_version'], $fid]);
                } else {
                    $pdo->prepare(
                        'INSERT INTO facilitators
                         (name, email, institution, country, continent, lat, lng,
                          location_label, show_on_map, show_identity, status,
                          consent_at, consent_version)
                         VALUES (?,?,?,?,?,?,?,?,?,?,"pending",NOW(),?)'
                    )->execute([$name, $email, $institution, $country, $continent, $lat,
                        $lng, $label, $showMap, $showId, $cfg['consent_version']]);
                    $fid = (int) $pdo->lastInsertId();
                }

                $token = auth_create_token($fid, 'verify', 48 * 3600);
                send_mail(
                    $email,
                    'Confirm your Desirable Futures registration',
                    "Hello {$name},\n\nThank you for registering as a facilitator for "
                    . "Desirable Futures with Robots.\n\nPlease confirm your email address "
                    . "and your consent by opening this link (valid for 48 hours):\n\n"
                    . base_url() . '/verify.php?token=' . $token
                    . "\n\nIf you did not request this, ignore this email and no account "
                    . "will be activated.\n\n— Desirable Futures with Robots"
                );
            }
            $done = true;
        }
    }
}

$page_title = 'Register as a facilitator — Desirable Futures with robots';
$page_desc  = 'Register to run a Desirable Futures with Robots workshop and appear on the facilitator map.';
$page_head  = '<link rel="stylesheet" href="assets/leaflet/leaflet.css" />';
$e = static fn (string $k): string => htmlspecialchars((string) $old[$k], ENT_QUOTES);

require dirname(__DIR__) . '/templates/header.php';
?>

<section class="section section--form">
  <div class="section__marker"><span class="numeral">✎</span><span class="label">Join Us</span></div>

  <?php if ($done): ?>
    <h1 class="section__title">Check your inbox.</h1>
    <div class="prose prose--narrow">
      <p>If that email address is eligible, we've sent a confirmation link. Open it to
        activate your account — the link is valid for 48 hours.</p>
      <p><a href="index.php">← Back to the site</a></p>
    </div>
  <?php else: ?>
    <h1 class="section__title">Run a workshop.</h1>
    <div class="prose prose--narrow">
      <p>Register as a facilitator. We'll email you a confirmation link — no password
        to remember. You can mark where you're based to appear on the map.</p>
    </div>

    <?php if ($errors): ?>
      <div class="form-errors" role="alert">
        <p>Please fix the following:</p>
        <ul>
          <?php foreach ($errors as $err): ?><li><?= htmlspecialchars($err) ?></li><?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form class="form" method="post" action="register.php" novalidate>
      <?= csrf_field() ?>

      <p class="form-row">
        <label for="name">Your name <span class="req">*</span></label>
        <input type="text" id="name" name="name" required maxlength="200" value="<?= $e('name') ?>" autocomplete="name" />
      </p>

      <p class="form-row">
        <label for="email">Email <span class="req">*</span></label>
        <input type="email" id="email" name="email" required maxlength="320" value="<?= $e('email') ?>" autocomplete="email" />
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
            <?php
            $continents = ['', 'Africa', 'Asia', 'Europe', 'North America', 'South America', 'Oceania', 'Antarctica'];
            foreach ($continents as $c):
                $sel = ($old['continent'] === $c) ? ' selected' : '';
            ?>
              <option value="<?= htmlspecialchars($c, ENT_QUOTES) ?>"<?= $sel ?>><?= $c === '' ? '—' : htmlspecialchars($c) ?></option>
            <?php endforeach; ?>
          </select>
        </span>
      </div>

      <fieldset class="form-fieldset">
        <legend>Where are you based?</legend>
        <p class="form-hint">Optional. Click the map to drop a pin (stored at city level, ~1&nbsp;km). Or just type your location below.</p>

        <p class="form-row">
          <label for="location_label">Location label</label>
          <input type="text" id="location_label" name="location_label" maxlength="200" placeholder="e.g. Barcelona, Spain" value="<?= $e('location_label') ?>" />
        </p>

        <div id="map-picker" class="map-picker" aria-hidden="true" style="height:340px"></div>
        <p class="form-hint" id="coord-readout" aria-live="polite">
          <?php if ($old['lat'] !== '' && $old['lng'] !== ''): ?>
            Selected: <?= $e('lat') ?>, <?= $e('lng') ?>
          <?php else: ?>No pin placed.<?php endif; ?>
          <button type="button" id="clear-pin" class="btn-inline">Clear pin</button>
        </p>
        <input type="hidden" id="lat" name="lat" value="<?= $e('lat') ?>" />
        <input type="hidden" id="lng" name="lng" value="<?= $e('lng') ?>" />

        <p class="form-check">
          <label><input type="checkbox" name="show_on_map" value="1" <?= $old['show_on_map'] ? 'checked' : '' ?> />
            Show my location on the public map (as an anonymous dot).</label>
        </p>
        <p class="form-check">
          <label><input type="checkbox" name="show_identity" value="1" <?= $old['show_identity'] ? 'checked' : '' ?> />
            Also show my name and institution on my map pin.</label>
        </p>
      </fieldset>

      <!-- anti-spam honeypot: hidden from people, must stay empty -->
      <div class="hp" aria-hidden="true" style="position:absolute!important;left:-9999px!important;width:1px;height:1px;overflow:hidden;">
        <label>Do not fill this in<input type="text" name="website" tabindex="-1" autocomplete="off" /></label>
      </div>

      <p class="form-consent">
        By registering, you agree that the details above are stored and used to coordinate the
        workshop series. We set no third-party trackers and never share your raw contact details,
        and you can view or delete your data at any time from your account.
      </p>

      <p class="form-actions">
        <button type="submit" class="btn btn--primary"><span>Register</span><span class="arrow" aria-hidden="true">→</span></button>
      </p>
    </form>
  <?php endif; ?>
</section>

<?php
$page_scripts = <<<'HTML'
<script src="assets/leaflet/leaflet.js"></script>
<script>
(function () {
  if (!window.L) return;
  L.Icon.Default.imagePath = 'assets/leaflet/images/';
  var el = document.getElementById('map-picker');
  el.removeAttribute('aria-hidden');
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
