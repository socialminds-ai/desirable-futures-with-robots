<?php
declare(strict_types=1);

require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/csrf.php';

df_session();
if (current_admin()) {
    header('Location: index.php');
    exit;
}

$error = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $error = true;
    } else {
        $u = trim((string) ($_POST['username'] ?? ''));
        $p = (string) ($_POST['password'] ?? '');
        $id = ($u !== '' && $p !== '') ? admin_authenticate($u, $p) : null;
        if ($id !== null) {
            admin_login($id);
            header('Location: index.php');
            exit;
        }
        usleep(300000); // small delay to blunt brute force
        $error = true;
    }
}

$cssVer = (string) @filemtime(__DIR__ . '/../styles.css');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="robots" content="noindex" />
  <title>Admin sign in — Desirable Futures</title>
  <link rel="icon" type="image/svg+xml" href="../assets/favicon.svg" />
  <link rel="stylesheet" href="../styles.css?v=<?= htmlspecialchars($cssVer, ENT_QUOTES) ?>" />
</head>
<body>
<main class="admin-main">
  <div class="admin-login">
    <h1 class="section__title">Admin sign in</h1>
    <?php if ($error): ?>
      <div class="form-errors" role="alert"><p>Invalid username or password.</p></div>
    <?php endif; ?>
    <form class="form" method="post" action="login.php" novalidate>
      <?= csrf_field() ?>
      <p class="form-row">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" required autocomplete="username" />
      </p>
      <p class="form-row">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required autocomplete="current-password" />
      </p>
      <p class="form-actions">
        <button type="submit" class="btn btn--primary"><span>Sign in</span></button>
      </p>
    </form>
  </div>
</main>
</body>
</html>
