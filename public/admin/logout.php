<?php
declare(strict_types=1);

require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/csrf.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    admin_logout();
}
header('Location: login.php');
exit;
