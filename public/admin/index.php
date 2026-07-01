<?php
declare(strict_types=1);

require_once __DIR__ . '/../../lib/db.php';
require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/csrf.php';

$me = require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $action = $_POST['action'] ?? '';
    $id     = (int) ($_POST['id'] ?? 0);
    $msg    = '';

    if ($id > 0 && $action === 'delete') {
        if ($id === (int) $me['id']) {
            $msg = 'self'; // can't delete yourself from here
        } else {
            db()->prepare('DELETE FROM facilitators WHERE id = ?')->execute([$id]);
            $msg = 'deleted';
        }
    } elseif ($id > 0 && $action === 'promote') {
        db()->prepare('UPDATE facilitators SET is_admin = 1 WHERE id = ?')->execute([$id]);
        $msg = 'promoted';
    } elseif ($id > 0 && $action === 'demote') {
        if ($id === (int) $me['id']) {
            $msg = 'self'; // can't revoke your own admin (avoids lockout)
        } else {
            db()->prepare('UPDATE facilitators SET is_admin = 0 WHERE id = ?')->execute([$id]);
            $msg = 'demoted';
        }
    }

    header('Location: index.php' . ($msg !== '' ? '?msg=' . $msg : ''));
    exit;
}

$rows = db()->query(
    'SELECT id, name, email, institution, city, country, lat, lng,
            show_on_map, show_identity, status, is_admin, created_at
       FROM facilitators
      ORDER BY created_at DESC'
)->fetchAll();

$active = 0;
foreach ($rows as $r) {
    if ($r['status'] === 'active') {
        $active++;
    }
}

$messages = [
    'deleted'  => 'Facilitator deleted.',
    'promoted' => 'Admin granted.',
    'demoted'  => 'Admin revoked.',
    'self'     => "You can't change your own account from here.",
];
$notice = $messages[$_GET['msg'] ?? ''] ?? null;

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
  <span class="admin-header__who">
    <?= $h($me['name']) ?>
    <a class="btn btn--ghost" href="../index.php">Return to website</a>
    <form method="post" action="../logout.php" class="inline-form">
      <?= csrf_field() ?>
      <button type="submit" class="btn btn--ghost">Sign out</button>
    </form>
  </span>
</header>

<main class="admin-main">
  <h1 class="admin-title">Facilitators</h1>
  <p class="admin-summary"><?= count($rows) ?> registered · <?= $active ?> active · <?= count($rows) - $active ?> pending</p>

  <?php if ($notice !== null): ?>
    <div class="form-notice" role="status"><p><?= $h($notice) ?></p></div>
  <?php endif; ?>

  <?php if (!$rows): ?>
    <p class="admin-empty">No facilitators registered yet.</p>
  <?php else: ?>
  <div class="admin-table-wrap">
    <table class="admin-table">
      <thead>
        <tr>
          <th>Name</th><th>Email</th><th>Institution</th><th>Location</th>
          <th>Map</th><th>Status</th><th>Role</th><th>Registered</th><th></th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $r): $self = ((int) $r['id'] === (int) $me['id']); ?>
        <tr>
          <td><?= $h($r['name']) ?><?php if ($self): ?> <span class="admin-you">(you)</span><?php endif; ?></td>
          <td><a href="mailto:<?= $h($r['email']) ?>"><?= $h($r['email']) ?></a></td>
          <td><?= $h($r['institution']) ?></td>
          <td>
            <?= $h(trim(($r['city'] ?? '') . ($r['city'] && $r['country'] ? ', ' : '') . ($r['country'] ?? ''))) ?>
            <?php if ($r['lat'] !== null): ?><br><span class="admin-coord"><?= $h($r['lat']) ?>, <?= $h($r['lng']) ?></span><?php endif; ?>
          </td>
          <td><?php if ($r['show_on_map']): ?><?= $r['show_identity'] ? 'named' : 'dot' ?><?php else: ?>—<?php endif; ?></td>
          <td><span class="badge badge--<?= $r['status'] === 'active' ? 'active' : 'pending' ?>"><?= $h($r['status']) ?></span></td>
          <td>
            <?php if ($r['is_admin']): ?>
              <span class="badge badge--admin">admin</span>
              <?php if (!$self): ?>
                <form method="post" action="index.php" class="inline-form">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="demote" />
                  <input type="hidden" name="id" value="<?= (int) $r['id'] ?>" />
                  <button type="submit" class="btn btn--outline btn--small">Revoke</button>
                </form>
              <?php endif; ?>
            <?php else: ?>
              <form method="post" action="index.php" class="inline-form"
                    onsubmit="return confirm('Make <?= $h($r['name']) ?> an admin?');">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="promote" />
                <input type="hidden" name="id" value="<?= (int) $r['id'] ?>" />
                <button type="submit" class="btn btn--outline btn--small">Make admin</button>
              </form>
            <?php endif; ?>
          </td>
          <td><?= $h(substr((string) $r['created_at'], 0, 10)) ?></td>
          <td>
            <?php if (!$self): ?>
            <form method="post" action="index.php" class="inline-form"
                  onsubmit="return confirm('Delete <?= $h($r['name']) ?> (<?= $h($r['email']) ?>)? This cannot be undone.');">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="delete" />
              <input type="hidden" name="id" value="<?= (int) $r['id'] ?>" />
              <button type="submit" class="btn btn--danger btn--small">Delete</button>
            </form>
            <?php endif; ?>
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
