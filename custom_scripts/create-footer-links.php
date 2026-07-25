<?php

/**
 * @file
 * Drush script: populates the built-in "Footer" menu (machine name:
 * footer) with the "Quick Links" shown in the site footer, matching
 * the original static markup exactly.
 *
 * Run with:
 *   PATH="/opt/alt/php83/usr/bin:$PATH" drush scr create-footer-links.php
 *
 * Idempotent: matches by title, so re-running updates in place.
 *
 * NOTE on targets: the original static file pointed "About ISTST"
 * and "Become a Member" at same-page anchors (#about, #member-cta),
 * which only work on the home page itself. Since this menu can
 * render in the footer on EVERY page, anchor-only links would break
 * on non-home pages. I've pointed them at /home#about and
 * /home#member-cta instead so they work site-wide (Drupal will load
 * /home then jump to the anchor). "Annual Congress" is kept as a
 * blank/no-destination link (route:<nolink>), matching the original
 * static file's bare "#" placeholder -- update the uri below once
 * there's a real Events landing page to point it at. "Gallery"
 * points at /gallery (pending the year-filter page rebuild).
 */

use Drupal\menu_link_content\Entity\MenuLinkContent;

$menu_name = 'footer';

function istst_footer_delete_existing(string $menu_name, string $title): void {
  $storage = \Drupal::entityTypeManager()->getStorage('menu_link_content');
  $ids = $storage->getQuery()
    ->condition('menu_name', $menu_name)
    ->condition('title', $title)
    ->accessCheck(FALSE)
    ->execute();
  if ($ids) {
    $storage->delete($storage->loadMultiple($ids));
    print "Removed existing link: $title\n";
  }
}

function istst_footer_create_link(string $menu_name, string $title, string $uri, int $weight): void {
  istst_footer_delete_existing($menu_name, $title);

  $link = MenuLinkContent::create([
    'title' => $title,
    'link' => ['uri' => $uri],
    'menu_name' => $menu_name,
    'weight' => $weight,
    'expanded' => FALSE,
  ]);
  $link->save();

  print "Created: $title (uri: $uri)\n";
}

// -----------------------------------------------------------------
// Quick Links -- matches the original static footer exactly (see
// note above re: anchor targets). Edit titles/paths/order here if
// you want something different, then re-run the script.
// -----------------------------------------------------------------
$footer_links = [
  'About ISTST' => 'internal:/home#about',
  'Become a Member' => 'internal:/home#member-cta',
  'Annual Congress (ISTSCON)' => 'route:<nolink>',
  'Gallery' => 'internal:/gallery',
];

$weight = -10;
foreach ($footer_links as $title => $uri) {
  istst_footer_create_link($menu_name, $title, $uri, $weight);
  $weight++;
}

print "\nDone. Run `drush cr` and check the footer's Quick Links.\n";
