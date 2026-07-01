<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/csrf.php';

/**
 * The site navigation, aware of login state.
 *
 * @param array|null $user  The logged-in facilitator, or null.
 * @param string     $home  Prefix for in-page anchors: '' on the landing page,
 *                          'index.php' on sub-pages.
 */
function render_site_nav(?array $user, string $home = ''): string
{
    $links =
        '<a href="' . $home . '#proposition">Manifesto</a>'
      . '<a href="' . $home . '#series">Series</a>'
      . '<a href="' . $home . '#coordinators">Coordinators</a>';

    if ($user === null) {
        return '<nav class="site-nav">' . $links
             . '<a class="site-nav__signin" href="login.php">Sign in</a>'
             . '<a class="cta" href="' . $home . '#join">Join&nbsp;&rarr;</a></nav>';
    }

    $name = htmlspecialchars((string) $user['name'], ENT_QUOTES);
    $menu =
        '<details class="user-menu">'
      . '<summary>' . $name . ' <span class="user-menu__caret" aria-hidden="true">&#9662;</span></summary>'
      . '<div class="user-menu__panel">'
      . '<a href="account.php">Edit details</a>'
      . '<form method="post" action="logout.php">' . csrf_field()
      . '<button type="submit">Sign out</button></form>'
      . '</div></details>';

    return '<nav class="site-nav">' . $links . $menu . '</nav>';
}
