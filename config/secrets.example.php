<?php
/**
 * Copy this to config/secrets.php on the host and fill in real values.
 * config/secrets.php is gitignored.
 *
 * Only needed where DB settings are NOT supplied via environment variables
 * (e.g. shared hosting). The docker dev stack sets env vars instead, so this
 * file is not required for local development.
 */
return [
    'DB_HOST' => 'localhost',
    'DB_NAME' => 'desirabl',
    'DB_USER' => 'desirabl',
    'DB_PASS' => '',
];
