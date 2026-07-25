<?php

/**
 * @file
 * Drush script: builds the full "Main navigation" menu tree in one run.
 *
 * Run with:
 *   PATH="/opt/alt/php83/usr/bin:$PATH" drush scr create-menu-links.php
 * (from the directory where this file lives, or pass the full path).
 *
 * Safe to re-run: it deletes and recreates its own links each time
 * (matched by title + menu), so you won't get duplicates if you run
 * it twice. It also removes the old placeholder "Committee" link
 * if present, per our discussion.
 *
 * ASSUMPTION (flagged, not yet confirmed by you): each Organization
 * role links to its own dedicated profile page at
 * /committee/[role-slug]. If you decide instead on a single
 * /committee page with anchors, change the $organization_children
 * array below (uri values) and re-run -- nothing else changes.
 */

use Drupal\menu_link_content\Entity\MenuLinkContent;

$menu_name = 'main';

/**
 * Deletes any existing menu link in $menu_name with the given title,
 * so re-running this script updates in place instead of duplicating.
 */
function istst_delete_existing_link(string $menu_name, string $title): void {
  $storage = \Drupal::entityTypeManager()->getStorage('menu_link_content');
  $ids = $storage->getQuery()
    ->condition('menu_name', $menu_name)
    ->condition('title', $title)
    ->accessCheck(FALSE)
    ->execute();
  if ($ids) {
    $entities = $storage->loadMultiple($ids);
    $storage->delete($entities);
    print "Removed existing link(s): $title\n";
  }
}

/**
 * Creates a menu link and returns its plugin ID string, so children
 * can reference it as their parent.
 *
 * @param string $title
 * @param string $uri
 *   e.g. 'internal:/committee/president', 'route:<nolink>',
 *   or an absolute external URL like 'https://example.com/file.pdf'.
 * @param int $weight
 * @param string $parent_plugin_id
 *   Empty string for top-level links.
 * @param bool $expanded
 *   TRUE for parents that should always show their children (dropdowns).
 */
function istst_create_link(string $menu_name, string $title, string $uri, int $weight, string $parent_plugin_id = '', bool $expanded = FALSE): string {
  istst_delete_existing_link($menu_name, $title);

  $link = MenuLinkContent::create([
    'title' => $title,
    'link' => ['uri' => $uri],
    'menu_name' => $menu_name,
    'weight' => $weight,
    'expanded' => $expanded,
    'parent' => $parent_plugin_id,
  ]);
  $link->save();

  print "Created: $title (uri: $uri, parent: " . ($parent_plugin_id ?: '[top level]') . ")\n";

  return 'menu_link_content:' . $link->uuid();
}

// -----------------------------------------------------------------
// Clean up the placeholder link we no longer need.
// -----------------------------------------------------------------
istst_delete_existing_link($menu_name, 'Committee');

// -----------------------------------------------------------------
// ORGANIZATION (parent dropdown + 5 role children)
// -----------------------------------------------------------------
$organization_parent = istst_create_link($menu_name, 'Organization', 'route:<nolink>', -10, '', TRUE);

$organization_children = [
  'President' => '/committee/president',
  'Past President' => '/committee/past-president',
  'Vice Presidents' => '/committee/vice-presidents',
  'Secretary' => '/committee/secretary',
  'Joint Secretary' => '/committee/joint-secretary',
];
$weight = -5;
foreach ($organization_children as $title => $path) {
  istst_create_link($menu_name, $title, 'internal:' . $path, $weight, $organization_parent);
  $weight++;
}

// -----------------------------------------------------------------
// GALLERY (parent dropdown + year children)
// ASSUMPTION: gallery pages are year-filtered at /gallery/{year}.
// If Gallery should instead be one flat page with no year split,
// just remove the loop below and create a single top-level link.
// -----------------------------------------------------------------
$gallery_parent = istst_create_link($menu_name, 'Gallery', 'route:<nolink>', -9, '', TRUE);

$gallery_children = ['2020', '2022', '2023', '2024'];
$weight = -5;
foreach ($gallery_children as $year) {
  istst_create_link($menu_name, "ISTSCON $year", 'internal:/gallery/' . $year, $weight, $gallery_parent);
  $weight++;
}

// -----------------------------------------------------------------
// EVENTS (parent dropdown + year children, linking straight to PDFs)
// NOTE: these are the old WordPress-hosted PDF URLs for now. Swap to
// Drupal Media URLs later once the files are migrated -- only the
// $events_children uri values change, nothing structural.
// -----------------------------------------------------------------
$events_parent = istst_create_link($menu_name, 'Events', 'route:<nolink>', -8, '', TRUE);

$events_children = [
  'ISTSCON 2020' => 'https://istst.org/wp-content/uploads/2024/09/2020.pdf',
  'ISTSCON 2022' => 'https://istst.org/wp-content/uploads/2024/09/2022.pdf',
  'ISTSCON 2023' => 'https://istst.org/wp-content/uploads/2024/09/2023.pdf',
  'ISTSCON 2024' => 'https://istst.org/wp-content/uploads/2024/11/istscon2024-combine-pdf.pdf',
  'ISTSCON 2025' => 'https://istst.org/wp-content/uploads/2025/05/ISTSCON-2025.pdf',
];
$weight = -5;
foreach ($events_children as $title => $pdf_url) {
  istst_create_link($menu_name, $title, $pdf_url, $weight, $events_parent);
  $weight++;
}

// -----------------------------------------------------------------
// MEMBER (parent dropdown + 3 children)
// -----------------------------------------------------------------
$member_parent = istst_create_link($menu_name, 'Member', 'route:<nolink>', -7, '', TRUE);

$member_children = [
  'How to become a Member' => '/how-to-become-a-member',
  'Member Enrollment' => '/member-registration',
  'Members List' => '/members-list',
];
$weight = -5;
foreach ($member_children as $title => $path) {
  istst_create_link($menu_name, $title, 'internal:' . $path, $weight, $member_parent);
  $weight++;
}

print "\nDone. Run `drush cr` and check Structure > Menus > Main navigation.\n";
