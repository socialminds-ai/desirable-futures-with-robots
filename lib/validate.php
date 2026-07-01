<?php
declare(strict_types=1);

/** Trimmed string within [min,max] chars, or null if invalid/empty. */
function v_string($v, int $max, int $min = 0): ?string
{
    if (!is_string($v)) {
        return null;
    }
    $v = trim($v);
    $len = mb_strlen($v);
    if ($len < max($min, 1) || $len > $max) {
        return null;
    }
    return $v;
}

/** Normalised, valid email (lowercased) or null. */
function v_email($v): ?string
{
    if (!is_string($v)) {
        return null;
    }
    $v = trim($v);
    if ($v === '' || mb_strlen($v) > 320) {
        return null;
    }
    $email = filter_var($v, FILTER_VALIDATE_EMAIL);
    return $email === false ? null : strtolower($email);
}

/** Latitude rounded to city-level (~1 km) or null. */
function v_lat($v): ?float
{
    if ($v === '' || $v === null || !is_numeric($v)) {
        return null;
    }
    $f = (float) $v;
    return ($f < -90 || $f > 90) ? null : round($f, 2);
}

/** Longitude rounded to city-level (~1 km) or null. */
function v_lng($v): ?float
{
    if ($v === '' || $v === null || !is_numeric($v)) {
        return null;
    }
    $f = (float) $v;
    return ($f < -180 || $f > 180) ? null : round($f, 2);
}

/** A known country name (from lib/countries.php), or null. */
function v_country($v): ?string
{
    if (!is_string($v)) {
        return null;
    }
    $v = trim($v);
    if ($v === '') {
        return null;
    }
    require_once __DIR__ . '/countries.php';
    return in_array($v, df_countries(), true) ? $v : null;
}
