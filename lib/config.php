<?php
declare(strict_types=1);

/**
 * Central configuration.
 *
 * DB settings are read from environment variables first (the docker dev stack
 * provides them), falling back to config/secrets.php on hosts where env vars
 * are inconvenient (e.g. shared hosting).
 */
function df_config(): array
{
    static $config = null;
    if ($config !== null) {
        return $config;
    }

    $secretsFile = dirname(__DIR__) . '/config/secrets.php';
    $file = is_file($secretsFile) ? (require $secretsFile) : [];

    $val = static function (string $key, $default = null) use ($file) {
        $env = getenv($key);
        return $env !== false ? $env : ($file[$key] ?? $default);
    };

    $config = [
        'db' => [
            'host'    => $val('DB_HOST', '127.0.0.1'),
            'name'    => $val('DB_NAME', 'desirabl'),
            'user'    => $val('DB_USER', 'desirabl'),
            'pass'    => $val('DB_PASS', ''),
            'charset' => 'utf8mb4',
        ],
    ];

    return $config;
}
