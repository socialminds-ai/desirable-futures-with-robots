<?php
/**
 * Shared page chrome. Set $page_title / $page_desc / $page_head before include.
 * Used by the auth/account/what-ifs pages (index.php has its own <head>).
 */
require_once dirname(__DIR__) . '/lib/view.php';
$page_title = $page_title ?? 'Desirable Futures with robots';
$page_desc  = $page_desc  ?? '';
$page_head  = $page_head  ?? '';
$navUser    = $navUser    ?? optional_facilitator();
$cssVer     = (string) @filemtime(dirname(__DIR__) . '/public/styles.css');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= htmlspecialchars($page_title) ?></title>
  <?php if ($page_desc !== ''): ?>
  <meta name="description" content="<?= htmlspecialchars($page_desc) ?>" />
  <?php endif; ?>
  <link rel="icon" type="image/svg+xml" href="assets/favicon.svg" />
  <link rel="icon" type="image/png" sizes="32x32" href="assets/favicon-32.png" />
  <link rel="apple-touch-icon" sizes="180x180" href="assets/favicon-180.png" />
  <link rel="stylesheet" href="styles.css?v=<?= htmlspecialchars($cssVer, ENT_QUOTES) ?>" />
  <?= $page_head ?>
</head>
<body>

<a class="skip-link" href="#top">Skip to main content</a>

<header class="site-header">
  <a class="wordmark" href="index.php">
    <span>Desirable Futures</span><em>&nbsp;with robots</em>
  </a>
  <?= render_site_nav($navUser, 'index.php') ?>
</header>

<main id="top" tabindex="-1">
