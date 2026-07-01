<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/session.php';

/** Create a single-use token, store its hash, return the raw token. */
function auth_create_token(int $facilitatorId, string $purpose, int $ttlSeconds): string
{
    $raw  = bin2hex(random_bytes(32));
    $hash = hash('sha256', $raw);
    db()->prepare(
        'INSERT INTO auth_tokens (facilitator_id, token_hash, purpose, expires_at)
         VALUES (?, ?, ?, ?)'
    )->execute([$facilitatorId, $hash, $purpose, date('Y-m-d H:i:s', time() + $ttlSeconds)]);
    return $raw;
}

/**
 * Atomically consume a token. Returns the facilitator id if the token is
 * valid, unused and unexpired for the given purpose; otherwise null.
 */
function auth_consume_token(string $raw, string $purpose): ?int
{
    if ($raw === '' || !ctype_xdigit($raw)) {
        return null;
    }
    $pdo  = db();
    $stmt = $pdo->prepare(
        'SELECT id, facilitator_id FROM auth_tokens
         WHERE token_hash = ? AND purpose = ? AND used_at IS NULL AND expires_at > NOW()
         LIMIT 1'
    );
    $stmt->execute([hash('sha256', $raw), $purpose]);
    $row = $stmt->fetch();
    if (!$row) {
        return null;
    }
    // Mark used; rowCount guards against a concurrent double-spend.
    $upd = $pdo->prepare('UPDATE auth_tokens SET used_at = NOW() WHERE id = ? AND used_at IS NULL');
    $upd->execute([$row['id']]);
    return $upd->rowCount() === 1 ? (int) $row['facilitator_id'] : null;
}

function auth_login(int $facilitatorId): void
{
    df_session();
    session_regenerate_id(true);
    $_SESSION['fid'] = $facilitatorId;
}

function auth_logout(): void
{
    df_session();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/** The logged-in, active facilitator row, or null. */
function current_facilitator(): ?array
{
    df_session();
    if (empty($_SESSION['fid'])) {
        return null;
    }
    $stmt = db()->prepare('SELECT * FROM facilitators WHERE id = ? AND status = "active" LIMIT 1');
    $stmt->execute([(int) $_SESSION['fid']]);
    return $stmt->fetch() ?: null;
}

/**
 * Like current_facilitator(), but never *starts* a session: if the visitor has
 * no session cookie they're anonymous, so we avoid setting one (keeps the
 * landing page cookie-free for logged-out visitors). Use for nav/chrome.
 */
function optional_facilitator(): ?array
{
    if (empty($_COOKIE[DF_SESSION_NAME])) {
        return null;
    }
    return current_facilitator();
}

/** Require an active session; redirect to login otherwise. */
function require_login(): array
{
    $f = current_facilitator();
    if (!$f) {
        header('Location: login.php');
        exit;
    }
    return $f;
}

/* ---- Admin accounts (password-based) ------------------------------------ */

/** Verify admin credentials; return the admin id or null. */
function admin_authenticate(string $username, string $password): ?int
{
    $stmt = db()->prepare('SELECT id, password_hash FROM admins WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $row = $stmt->fetch();
    if (!$row || !password_verify($password, (string) $row['password_hash'])) {
        return null;
    }
    return (int) $row['id'];
}

function admin_login(int $adminId): void
{
    df_session();
    session_regenerate_id(true);
    $_SESSION['admin_id'] = $adminId;
}

function admin_logout(): void
{
    df_session();
    unset($_SESSION['admin_id']);
}

/** The logged-in admin (id + username), or null. */
function current_admin(): ?array
{
    df_session();
    if (empty($_SESSION['admin_id'])) {
        return null;
    }
    $stmt = db()->prepare('SELECT id, username FROM admins WHERE id = ? LIMIT 1');
    $stmt->execute([(int) $_SESSION['admin_id']]);
    return $stmt->fetch() ?: null;
}

/** Require an admin session; redirect to the admin login otherwise. */
function require_admin(): array
{
    $a = current_admin();
    if (!$a) {
        header('Location: login.php');
        exit;
    }
    return $a;
}

/** Absolute origin (scheme + host) for building links in emails. */
function base_url(): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    return $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
}
