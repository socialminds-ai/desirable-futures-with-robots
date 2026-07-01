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

    if ($id > 0 && $action === 'hide') {
        db()->prepare('UPDATE whatifs SET status = "hidden", hidden_at = NOW(), hidden_by = ? WHERE id = ?')
            ->execute([(int) $me['id'], $id]);
        $msg = 'hidden';
    } elseif ($id > 0 && $action === 'unhide') {
        db()->prepare('UPDATE whatifs SET status = "visible", hidden_at = NULL, hidden_by = NULL WHERE id = ?')
            ->execute([$id]);
        $msg = 'restored';
    } elseif ($id > 0 && $action === 'delete') {
        db()->prepare('DELETE FROM whatifs WHERE id = ?')->execute([$id]);
        $msg = 'deleted';
    }

    header('Location: whatifs.php' . ($msg !== '' ? '?msg=' . $msg : ''));
    exit;
}

$rows = db()->query(
    'SELECT w.id, w.prompt, w.status, w.created_at,
            f.name AS author_name,
            COUNT(v.id) AS votes
       FROM whatifs w
       LEFT JOIN whatif_votes v ON v.whatif_id = w.id
       LEFT JOIN facilitators f ON f.id = w.author_facilitator_id
      GROUP BY w.id, w.prompt, w.status, w.created_at, f.name
      ORDER BY (w.status = "hidden") ASC, votes DESC, w.created_at DESC'
)->fetchAll();

$messages = ['hidden' => 'Question hidden.', 'restored' => 'Question restored.', 'deleted' => 'Question deleted.'];
$notice   = $messages[$_GET['msg'] ?? ''] ?? null;

$cssVer = (string) @filemtime(__DIR__ . '/../styles.css');
$h = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="robots" content="noindex" />
  <title>What-ifs — Admin — Desirable Futures</title>
  <link rel="icon" type="image/svg+xml" href="../assets/favicon.svg" />
  <link rel="stylesheet" href="../styles.css?v=<?= $h($cssVer) ?>" />
</head>
<body>
<header class="admin-header">
  <span class="admin-header__mark">Desirable Futures <em>· admin</em></span>
  <nav class="admin-nav">
    <a href="index.php">Facilitators</a>
    <a href="whatifs.php" class="is-active">What-ifs</a>
  </nav>
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
  <h1 class="admin-title">What-ifs</h1>
  <p class="admin-summary"><?= count($rows) ?> total · questions are visible immediately; hide or delete anything off-brief.</p>

  <?php if ($notice !== null): ?>
    <div class="form-notice" role="status"><p><?= $h($notice) ?></p></div>
  <?php endif; ?>

  <?php if (!$rows): ?>
    <p class="admin-empty">No what-ifs yet.</p>
  <?php else: ?>
  <div class="admin-table-wrap">
    <table class="admin-table">
      <thead>
        <tr><th>Question</th><th>Author</th><th>Votes</th><th>Status</th><th>Added</th><th></th></tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $r): $hidden = $r['status'] === 'hidden'; ?>
        <tr>
          <td class="admin-whatif<?= $hidden ? ' is-hidden' : '' ?>"><?= $h($r['prompt']) ?></td>
          <td><?= $r['author_name'] !== null ? $h($r['author_name']) : '<span class="admin-coord">canonical</span>' ?></td>
          <td><?= (int) $r['votes'] ?></td>
          <td><span class="badge badge--<?= $hidden ? 'pending' : 'active' ?>"><?= $h($r['status']) ?></span></td>
          <td><?= $h(substr((string) $r['created_at'], 0, 10)) ?></td>
          <td class="admin-actions">
            <form method="post" action="whatifs.php" class="inline-form">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="<?= $hidden ? 'unhide' : 'hide' ?>" />
              <input type="hidden" name="id" value="<?= (int) $r['id'] ?>" />
              <button type="submit" class="btn btn--outline btn--small"><?= $hidden ? 'Unhide' : 'Hide' ?></button>
            </form>
            <form method="post" action="whatifs.php" class="inline-form"
                  onsubmit="return confirm('Delete this what-if permanently?');">
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
