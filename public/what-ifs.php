<?php
declare(strict_types=1);
/*
 * The community "what-if" bank. Publicly readable; voting and proposing require
 * a facilitator login. New questions appear immediately and are moderated
 * post-hoc from the admin panel. Facilitators can edit/delete their own.
 */
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/csrf.php';
require_once __DIR__ . '/../lib/validate.php';
require_once __DIR__ . '/../lib/view.php';

$me       = optional_facilitator();
$errors   = [];
$propose  = '';
$editId   = 0;      // item currently rendered as an edit form
$editText = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$me) {
        header('Location: login.php');
        exit;
    }
    if (!csrf_verify()) {
        $errors[] = 'Your session expired. Please try again.';
    } else {
        $action = $_POST['action'] ?? '';
        $meId   = (int) $me['id'];

        if ($action === 'vote') {
            $wid = (int) ($_POST['whatif_id'] ?? 0);
            $ok  = db()->prepare('SELECT 1 FROM whatifs WHERE id = ? AND status = "visible"');
            $ok->execute([$wid]);
            if ($wid > 0 && $ok->fetchColumn()) {
                $has = db()->prepare('SELECT id FROM whatif_votes WHERE whatif_id = ? AND facilitator_id = ?');
                $has->execute([$wid, $meId]);
                if ($row = $has->fetch()) {
                    db()->prepare('DELETE FROM whatif_votes WHERE id = ?')->execute([$row['id']]);
                } else {
                    db()->prepare('INSERT IGNORE INTO whatif_votes (whatif_id, facilitator_id) VALUES (?, ?)')
                        ->execute([$wid, $meId]);
                }
            }
            header('Location: what-ifs.php');
            exit;
        }

        if ($action === 'propose') {
            $prompt = v_string($_POST['prompt'] ?? '', 280, 10);
            if ($prompt === null) {
                $errors[]  = 'Please enter a question between 10 and 280 characters.';
                $propose   = (string) ($_POST['prompt'] ?? '');
            } else {
                db()->prepare('INSERT INTO whatifs (prompt, author_facilitator_id) VALUES (?, ?)')
                    ->execute([$prompt, $meId]);
                header('Location: what-ifs.php?proposed=1');
                exit;
            }
        }

        if ($action === 'edit') {
            $wid    = (int) ($_POST['whatif_id'] ?? 0);
            $prompt = v_string($_POST['prompt'] ?? '', 280, 10);
            if ($prompt === null) {
                $errors[]  = 'Please enter a question between 10 and 280 characters.';
                $editId    = $wid;                                   // stay in edit mode
                $editText  = (string) ($_POST['prompt'] ?? '');
            } else {
                // Ownership enforced in the WHERE clause.
                db()->prepare('UPDATE whatifs SET prompt = ? WHERE id = ? AND author_facilitator_id = ?')
                    ->execute([$prompt, $wid, $meId]);
                header('Location: what-ifs.php');
                exit;
            }
        }

        if ($action === 'delete_own') {
            $wid = (int) ($_POST['whatif_id'] ?? 0);
            db()->prepare('DELETE FROM whatifs WHERE id = ? AND author_facilitator_id = ?')
                ->execute([$wid, $meId]);
            header('Location: what-ifs.php?removed=1');
            exit;
        }
    }
}

if ($editId === 0 && isset($_GET['edit'])) {
    $editId = (int) $_GET['edit'];
}

$meId = $me ? (int) $me['id'] : 0;
$stmt = db()->prepare(
    'SELECT w.id, w.prompt, w.author_facilitator_id,
            COUNT(v.id) AS votes,
            MAX(CASE WHEN v.facilitator_id = ? THEN 1 ELSE 0 END) AS voted
       FROM whatifs w
       LEFT JOIN whatif_votes v ON v.whatif_id = w.id
      WHERE w.status = "visible"
      GROUP BY w.id, w.prompt, w.author_facilitator_id
      ORDER BY votes DESC, w.created_at DESC'
);
$stmt->execute([$meId]);
$items = $stmt->fetchAll();

$navUser    = $me;
$page_title = 'The what-if bank — Desirable Futures with robots';
$page_desc  = 'A growing bank of inversion prompts for the Desirable Futures with Robots workshop series — vote for your favourites and propose your own.';
require dirname(__DIR__) . '/templates/header.php';
?>
<section class="section section--whatif" id="whatifs">
  <div class="section__marker"><span class="numeral">?</span><span class="label">The What-if Bank</span></div>
  <h2 class="section__title">Reversing the narratives, together.</h2>

  <div class="prose prose--narrow">
    <p>Each workshop opens with a <em>what if</em> that reverses one element of the dominant
      framing, then asks participants to imagine a desirable future growing from it. This is
      the shared, growing bank — free to use and remix.</p>
  </div>

  <?php if (!empty($_GET['proposed'])): ?>
    <div class="form-notice" role="status"><p>Thanks — your what-if has been added to the bank.</p></div>
  <?php elseif (!empty($_GET['removed'])): ?>
    <div class="form-notice" role="status"><p>Your what-if was removed.</p></div>
  <?php endif; ?>
  <?php if ($errors): ?>
    <div class="form-errors" role="alert"><ul>
      <?php foreach ($errors as $err): ?><li><?= htmlspecialchars($err) ?></li><?php endforeach; ?>
    </ul></div>
  <?php endif; ?>

  <?php if ($me): ?>
    <form class="whatif-propose" method="post" action="what-ifs.php">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="propose" />
      <label for="prompt">Propose a <em>what if</em></label>
      <div class="whatif-propose__row">
        <textarea id="prompt" name="prompt" maxlength="280" rows="2" placeholder="What if …?"><?= htmlspecialchars($propose) ?></textarea>
        <button type="submit" class="btn btn--primary"><span>Add it</span></button>
      </div>
      <p class="form-hint">Visible to everyone straight away. Keep it a single, open “what if”.</p>
    </form>
  <?php else: ?>
    <p class="whatif-signin"><a href="login.php">Sign in</a> as a facilitator to vote for your favourites or propose your own. New here? <a href="register.php">Register</a>.</p>
  <?php endif; ?>

  <ol class="whatifs whatifs--bank">
    <?php foreach ($items as $w):
      $voted = (int) $w['voted'] === 1;
      $own   = $me && (int) $w['author_facilitator_id'] === (int) $me['id'];
      $editing = $own && (int) $w['id'] === $editId;
    ?>
      <li class="whatif whatif--voteable" id="w<?= (int) $w['id'] ?>">
        <div class="whatif__vote">
          <?php if ($me): ?>
            <form method="post" action="what-ifs.php">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="vote" />
              <input type="hidden" name="whatif_id" value="<?= (int) $w['id'] ?>" />
              <button type="submit" class="votebtn<?= $voted ? ' is-voted' : '' ?>"
                      aria-pressed="<?= $voted ? 'true' : 'false' ?>"
                      aria-label="<?= $voted ? 'Remove your vote' : 'Vote for this what-if' ?>">
                <span class="votebtn__icon" aria-hidden="true">&#9733;</span>
                <span class="votebtn__count"><?= (int) $w['votes'] ?></span>
              </button>
            </form>
          <?php else: ?>
            <span class="votebtn votebtn--static">
              <span class="votebtn__icon" aria-hidden="true">&#9733;</span>
              <span class="votebtn__count"><?= (int) $w['votes'] ?></span>
            </span>
          <?php endif; ?>
        </div>

        <?php if ($editing): ?>
          <form class="whatif-edit" method="post" action="what-ifs.php">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="edit" />
            <input type="hidden" name="whatif_id" value="<?= (int) $w['id'] ?>" />
            <label class="visually-hidden" for="edit-<?= (int) $w['id'] ?>">Edit your what-if</label>
            <textarea id="edit-<?= (int) $w['id'] ?>" name="prompt" maxlength="280" rows="2"><?= htmlspecialchars($editText !== '' ? $editText : $w['prompt']) ?></textarea>
            <div class="whatif-edit__actions">
              <button type="submit" class="btn btn--primary btn--small"><span>Save</span></button>
              <a href="what-ifs.php" class="btn-inline">Cancel</a>
            </div>
          </form>
        <?php else: ?>
          <p class="whatif__text"><?= htmlspecialchars($w['prompt']) ?></p>
          <?php if ($own): ?>
            <div class="whatif__owner">
              <a href="what-ifs.php?edit=<?= (int) $w['id'] ?>#w<?= (int) $w['id'] ?>">Edit</a>
              <form method="post" action="what-ifs.php" class="inline-form"
                    onsubmit="return confirm('Delete your what-if?');">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="delete_own" />
                <input type="hidden" name="whatif_id" value="<?= (int) $w['id'] ?>" />
                <button type="submit" class="linkbtn">Delete</button>
              </form>
            </div>
          <?php endif; ?>
        <?php endif; ?>
      </li>
    <?php endforeach; ?>
  </ol>
</section>
<?php require dirname(__DIR__) . '/templates/footer.php';
