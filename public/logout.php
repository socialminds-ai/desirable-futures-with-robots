<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/csrf.php';

// Only act on a valid POST so a stray GET can't log people out (CSRF-safe).
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    auth_logout();
}
header('Location: index.php');
exit;
