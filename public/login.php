<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/csrf.php';
require_once __DIR__ . '/../lib/validate.php';
require_once __DIR__ . '/../lib/mail.php';

// Already signed in?
if (current_facilitator()) {
    header('Location: account.php');
    exit;
}

// Consume a magic-link token.
if (isset($_GET['token'])) {
    $fid = auth_consume_token((string) $_GET['token'], 'login');
    if ($fid !== null) {
        auth_login($fid);
        header('Location: account.php');
        exit;
    }
    $tokenError = true;
}

$errors = [];
$sent   = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $errors[] = 'Your session expired. Please try again.';
    } else {
        $email = v_email($_POST['email'] ?? '');
        if ($email === null) {
            $errors[] = 'Please enter a valid email address.';
        } else {
            $stmt = db()->prepare('SELECT id FROM facilitators WHERE email = ? AND status = "active" LIMIT 1');
            $stmt->execute([$email]);
            if ($row = $stmt->fetch()) {
                $token = auth_create_token((int) $row['id'], 'login', 1800);
                send_mail(
                    $email,
                    'Your Desirable Futures sign-in link',
                    "Hello,\n\nUse this link to sign in (valid for 30 minutes):\n\n"
                    . base_url() . '/login.php?token=' . $token
                    . "\n\nIf you didn't request this, you can ignore this email.\n\n"
                    . '— Desirable Futures with Robots'
                );
            }
            // Always the same response — no account enumeration.
            $sent = true;
        }
    }
}

$page_title = 'Sign in — Desirable Futures with robots';
require dirname(__DIR__) . '/templates/header.php';
?>
<section class="section section--form">
  <div class="section__marker"><span class="numeral">→</span><span class="label">Facilitators</span></div>

  <?php if ($sent): ?>
    <h1 class="section__title">Check your inbox.</h1>
    <div class="prose prose--narrow">
      <p>If that address has an active account, we've emailed a sign-in link. It's valid for 30 minutes.</p>
      <p><a href="index.php">← Back to the site</a></p>
    </div>
  <?php else: ?>
    <h1 class="section__title">Sign in.</h1>
    <div class="prose prose--narrow">
      <p>Enter your email and we'll send a one-time sign-in link. No password needed.</p>
    </div>

    <?php if (!empty($tokenError)): ?>
      <div class="form-errors" role="alert"><p>That sign-in link is invalid or expired. Request a new one below.</p></div>
    <?php endif; ?>
    <?php if ($errors): ?>
      <div class="form-errors" role="alert"><ul>
        <?php foreach ($errors as $err): ?><li><?= htmlspecialchars($err) ?></li><?php endforeach; ?>
      </ul></div>
    <?php endif; ?>

    <form class="form" method="post" action="login.php" novalidate>
      <?= csrf_field() ?>
      <p class="form-row">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" required maxlength="320" autocomplete="email" />
      </p>
      <p class="form-actions">
        <button type="submit" class="btn btn--primary"><span>Email me a link</span><span class="arrow" aria-hidden="true">→</span></button>
      </p>
    </form>
    <p class="prose prose--narrow">New here? <a href="register.php">Register as a facilitator</a>.</p>
  <?php endif; ?>
</section>
<?php require dirname(__DIR__) . '/templates/footer.php';
