<?php
declare(strict_types=1);

require_once __DIR__ . '/../../lib/db.php';
require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/csrf.php';

$admin  = require_admin();
$notice = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && csrf_verify()
    && ($_POST['action'] ?? '') === 'delete') {
    $id = (int) ($_POST['id'] ?? 0);
    if ($id > 0) {
        db()->prepare('DELETE FROM facilitators WHERE id = ?')->execute([$id]);
    }
    header('Location: index.php?deleted=1');
    exit;
}

$rows = db()->query(
    'SELECT id, name, email, institution, city, country, lat, lng,
            show_on_map, show_identity, status, created_at
       FROM facilitators
      ORDER BY created_at DESC'
)->fetchAll();

$active  = 0;
foreach ($rows as $r) {
    if ($r['status'] === 'active') {
        $active++;
    }
}

$cssVer = (string) @filemtime(__DIR__ . '/../styles.css');
$h = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="robots" content="noindex" />
  <title>Facilitators — Admin — Desirable Futures</title>
  <link rel="icon" type="image/svg+xml" href="../assets/favicon.svg" />
  <link rel="stylesheet" href="../styles.css?v=<?= $h($cssVer) ?>" />
</head>
<body>
<header class="admin-header">
  <span class="admin-header__mark">Desirable Futures <em>· admin</em></span>
  <form method="post" action="logout.php" class="inline-form">
    <?= csrf_field() ?>
    <button type="submit" class="btn btn--ghost">Sign out</button>
  </form>
</header>

<main class="admin-main">
  <h1 class="admin-title">Facilitators</h1>
  <p class="admin-summary"><?= count($rows) ?> registered · <?= $active ?> active · <?= count($rows) - $active ?> pending</p>

  <?php if (!empty($_GET['deleted'])): ?>
    <div class="form-notice" role="status"><p>Facilitator deleted.</p></div>
  <?php endif; ?>

  <?php if (!$rows): ?>
    <p class="admin-empty">No facilitators registered yet.</p>
  <?php else: ?>
  <div class="admin-table-wrap">
    <table class="admin-table">
      <thead>
        <tr>
          <th>Name</th><th>Email</th><th>Institution</th><th>Location</th>
          <th>Map</th><th>Status</th><th>Registered</th><th></th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= $h($r['name']) ?></td>
          <td><a href="mailto:<?= $h($r['email']) ?>"><?= $h($r['email']) ?></a></td>
          <td><?= $h($r['institution']) ?></td>
          <td>
            <?= $h(trim(($r['city'] ?? '') . ($r['city'] && $r['country'] ? ', ' : '') . ($r['country'] ?? ''))) ?>
            <?php if ($r['lat'] !== null): ?><br><span class="admin-coord"><?= $h($r['lat']) ?>, <?= $h($r['lng']) ?></span><?php endif; ?>
          </td>
          <td>
            <?php if ($r['show_on_map']): ?><?= $r['show_identity'] ? 'named' : 'dot' ?><?php else: ?>—<?php endif; ?>
          </td>
          <td><span class="badge badge--<?= $r['status'] === 'active' ? 'active' : 'pending' ?>"><?= $h($r['status']) ?></span></td>
          <td><?= $h(substr((string) $r['created_at'], 0, 10)) ?></td>
          <td>
            <form method="post" action="index.php" class="inline-form"
                  onsubmit="return confirm('Delete <?= $h($r['name']) ?> (<?= $h($r['email']) ?>)? This cannot be undone.');">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="delete" />
              <input type="hidden" name="id" value="<?= (int) $r['id'] ?>" />
              <button type="submit" class="btn btn--danger btn--small">Delete</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</main>
</body>
</html>
