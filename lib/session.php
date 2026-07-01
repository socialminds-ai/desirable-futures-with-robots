<?php
declare(strict_types=1);

const DF_SESSION_NAME = 'df_session';

/**
 * Start the first-party, strictly-necessary session cookie exactly once.
 * HttpOnly + SameSite=Lax; Secure whenever the request is over HTTPS.
 */
function df_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

    session_name(DF_SESSION_NAME);
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => $secure,
    ]);
    session_start();
}
