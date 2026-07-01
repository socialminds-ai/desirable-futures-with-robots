<?php
declare(strict_types=1);

/**
 * Create or update an admin account (CLI only).
 *   php db/create_admin.php <username> <password>
 *
 * There is no admin self-signup; run this once on the host after migrations.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../lib/db.php';

$username = $argv[1] ?? '';
$password = $argv[2] ?? '';

if ($username === '' || $password === '') {
    fwrite(STDERR, "Usage: php db/create_admin.php <username> <password>\n");
    exit(1);
}
if (strlen($password) < 10) {
    fwrite(STDERR, "Password must be at least 10 characters.\n");
    exit(1);
}

db()->prepare(
    'INSERT INTO admins (username, password_hash) VALUES (?, ?)
     ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash)'
)->execute([$username, password_hash($password, PASSWORD_DEFAULT)]);

fwrite(STDOUT, "Admin '{$username}' created/updated.\n");
