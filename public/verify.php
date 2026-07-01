<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/auth.php';

$fid = auth_consume_token((string) ($_GET['token'] ?? ''), 'verify');

if ($fid !== null) {
    db()->prepare('UPDATE facilitators SET status = "active" WHERE id = ?')->execute([$fid]);
    auth_login($fid);
    header('Location: account.php?verified=1');
    exit;
}

$page_title = 'Link expired — Desirable Futures with robots';
require dirname(__DIR__) . '/templates/header.php';
?>
<section class="section section--form">
  <div class="section__marker"><span class="numeral">!</span><span class="label">Verification</span></div>
  <h1 class="section__title">That link didn't work.</h1>
  <div class="prose prose--narrow">
    <p>The confirmation link is invalid, already used, or expired. You can register again
      to receive a fresh link — if your details were already saved, they'll be kept.</p>
    <p><a class="btn btn--primary" href="register.php"><span>Register again</span><span class="arrow" aria-hidden="true">→</span></a></p>
  </div>
</section>
<?php require dirname(__DIR__) . '/templates/footer.php';
