<?php
declare(strict_types=1);

/**
 * Grant (or revoke) the admin role on a facilitator by email (CLI only).
 * Used to bootstrap the first admin; afterwards admins promote others in the UI.
 *
 *   php db/set_admin.php <email>            # grant
 *   php db/set_admin.php <email> --revoke   # revoke
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../lib/db.php';

$email = strtolower(trim($argv[1] ?? ''));
$grant = (($argv[2] ?? '') === '--revoke') ? 0 : 1;

if ($email === '') {
    fwrite(STDERR, "Usage: php db/set_admin.php <email> [--revoke]\n");
    exit(1);
}

$stmt = db()->prepare('UPDATE facilitators SET is_admin = ? WHERE email = ?');
$stmt->execute([$grant, $email]);

if ($stmt->rowCount() === 0) {
    fwrite(STDERR, "No facilitator with email {$email} (or role unchanged).\n");
    exit(1);
}

fwrite(STDOUT, ($grant ? 'Granted' : 'Revoked') . " admin for {$email}.\n");
