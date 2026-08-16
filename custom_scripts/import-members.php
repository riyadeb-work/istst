<?php

/**
 * @file
 * Drush script: imports Member nodes from ISTST_membership_data.json.
 *
 * Place ISTST_membership_data.json in the SAME directory as this
 * script (e.g. custom_scripts/), then run:
 *
 *   lando drush scr custom_scripts/import-members.php
 *
 * Idempotent + order-preserving: matches existing Member nodes by
 * "Membership No." (field_membership_no) and UPDATES them in place
 * rather than deleting/recreating -- this keeps node IDs stable
 * across re-runs, which matters because the Members List page
 * displays rows in node-ID order (see the "View result counter"
 * field discussion) to mirror the original sl_no ordering from the
 * JSON. New members not yet in Drupal are created fresh, appended
 * at the end in the JSON's sl_no order.
 *
 * "sl_no" itself is intentionally NOT stored as a field -- the
 * Members List View computes row numbers live via Views' built-in
 * "Global: View result counter", so there's no serial-number data
 * to keep in sync by hand.
 */

use Drupal\node\Entity\Node;

$json_path = __DIR__ . '/ISTST_membership_data.json';

if (!file_exists($json_path)) {
  print "ERROR: JSON file not found at: $json_path\n";
  print "Place ISTST_membership_data.json in the same directory as this script and re-run.\n";
  return;
}

$raw = file_get_contents($json_path);
$records = json_decode($raw, TRUE);

if (json_last_error() !== JSON_ERROR_NONE) {
  print "ERROR: Invalid JSON -- " . json_last_error_msg() . "\n";
  return;
}

if (empty($records) || !is_array($records)) {
  print "ERROR: No records found in JSON file.\n";
  return;
}

/**
 * Maps the JSON's "membership_type" label (e.g. "Life") to the
 * field's allowed_values machine key (e.g. "life"). Falls back to a
 * lowercased/slugified version of whatever string is given, so an
 * unexpected type (e.g. "Annual") still gets a sane key rather than
 * silently failing.
 */
function istst_membership_type_key(string $label): string {
  $known = [
    'life' => 'life',
    'annual' => 'annual',
    'honorary' => 'honorary',
  ];
  $key = strtolower(trim($label));
  return $known[$key] ?? preg_replace('/[^a-z0-9_]+/', '_', $key);
}

$storage = \Drupal::entityTypeManager()->getStorage('node');

$created = 0;
$updated = 0;
$skipped = 0;

foreach ($records as $record) {
  $name = trim($record['name'] ?? '');
  $membership_no = trim($record['membership_no'] ?? '');
  $membership_type_label = trim($record['membership_type'] ?? '');

  if ($name === '' || $membership_no === '') {
    print "SKIPPED: record missing name or membership_no: " . json_encode($record) . "\n";
    $skipped++;
    continue;
  }

  $type_key = istst_membership_type_key($membership_type_label);

  // Find an existing Member node with this membership number.
  $nids = $storage->getQuery()
    ->condition('type', 'member')
    ->condition('field_membership_no', $membership_no)
    ->accessCheck(FALSE)
    ->execute();

  if ($nids) {
    // Update in place -- preserves node ID / display order.
    $node = $storage->load(reset($nids));
    $node->setTitle($name);
    $node->set('field_membership_type', $type_key);
    $node->set('field_membership_no', $membership_no);
    $node->save();
    $updated++;
  }
  else {
    // Create new.
    $node = Node::create([
      'type' => 'member',
      'title' => $name,
      'field_membership_type' => $type_key,
      'field_membership_no' => $membership_no,
      'status' => 1,
    ]);
    $node->save();
    $created++;
  }
}

print "\nDone.\n";
print "Created: $created\n";
print "Updated: $updated\n";
print "Skipped: $skipped\n";
print "Run `drush cr` next, then check Content > Member (filter by type).\n";
