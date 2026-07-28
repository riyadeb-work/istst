<?php

/**
 * @file
 * Drush script: creates all content types + fields needed for the
 * ISTST site (Committee Member, Gallery Image, Member, Hero Slide).
 *
 * Run with:
 *   PATH="/opt/alt/php83/usr/bin:$PATH" drush scr create-content-types.php
 *
 * Idempotent: checks if a content type/field already exists before
 * creating it, so it's safe to re-run after editing this file to
 * add/adjust fields.
 *
 * NOTE: newly created fields are automatically added to the default
 * form and view displays by Drupal core -- no extra display config
 * needed for them to show up on Add/Edit content forms right away.
 * We'll refine display ordering/formatters later via the UI once
 * views/templates consume these fields.
 */

use Drupal\node\Entity\NodeType;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;

/**
 * Creates a content type (bundle) if it doesn't already exist.
 */
function istst_create_content_type(string $id, string $label, string $description, string $title_label = 'Title'): void {
  if (NodeType::load($id)) {
    print "Content type '$id' already exists, skipping.\n";
    return;
  }

  $type = NodeType::create([
    'type' => $id,
    'name' => $label,
    'description' => $description,
  ]);
  $type->save();

  // Rename the default "Title" base field label for clarity in the
  // node edit form (e.g. "Full Name" instead of "Title").
  if ($title_label !== 'Title') {
    $fields = \Drupal::service('entity_field.manager')->getFieldDefinitions('node', $id);
    if (isset($fields['title'])) {
      /** @var \Drupal\Core\Field\Entity\BaseFieldOverride|null $override */
      $override = \Drupal\Core\Field\Entity\BaseFieldOverride::loadByName('node', $id, 'title');
      if (!$override) {
        $override = \Drupal\Core\Field\Entity\BaseFieldOverride::createFromBaseFieldDefinition($fields['title'], $id);
      }
      $override->setLabel($title_label);
      $override->save();
    }
  }

  print "Created content type: $label ($id)\n";
}

/**
 * Creates a field storage + field instance on a bundle, if it
 * doesn't already exist.
 *
 * @param string $entity_type
 *   e.g. 'node'.
 * @param string $bundle
 *   Content type machine name.
 * @param string $field_name
 *   Must be prefixed 'field_' by convention.
 * @param string $type
 *   Field type: 'string', 'text_long', 'image', 'list_string',
 *   'integer', 'link', etc.
 * @param string $label
 * @param array $storage_settings
 *   e.g. ['allowed_values' => [...]] for list_string fields.
 * @param array $field_settings
 * @param int $cardinality
 * @param bool $required
 */
function istst_add_field(
  string $entity_type,
  string $bundle,
  string $field_name,
  string $type,
  string $label,
  array $storage_settings = [],
  array $field_settings = [],
  int $cardinality = 1,
  bool $required = FALSE
): void {
  $storage = FieldStorageConfig::loadByName($entity_type, $field_name);
  if (!$storage) {
    $storage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => $entity_type,
      'type' => $type,
      'cardinality' => $cardinality,
      'settings' => $storage_settings,
    ]);
    $storage->save();
    print "  Created field storage: $field_name ($type)\n";
  }

  $existing_field = FieldConfig::loadByName($entity_type, $bundle, $field_name);
  if ($existing_field) {
    print "  Field '$field_name' already attached to $bundle, skipping.\n";
    return;
  }

  $field = FieldConfig::create([
    'field_storage' => $storage,
    'bundle' => $bundle,
    'label' => $label,
    'required' => $required,
    'settings' => $field_settings,
  ]);
  $field->save();

  print "  Attached field: $label ($field_name) to $bundle\n";
}

// ===================================================================
// 1. COMMITTEE MEMBER
// Profile pages for Organization dropdown (President, Secretary etc)
// ===================================================================
istst_create_content_type(
  'committee_member',
  'Committee Member',
  'A leadership/committee member profile (President, Secretary, etc.), shown on individual /committee/[role] pages.',
  'Full Name'
);

istst_add_field('node', 'committee_member', 'field_photo', 'image', 'Photo', [], [
  'file_directory' => 'committee/[date:custom:Y]',
  'alt_field_required' => FALSE,
], 1, FALSE);

istst_add_field('node', 'committee_member', 'field_designation_role', 'list_string', 'Designation (Role)', [
  'allowed_values' => [
    'president' => 'President',
    'past_president' => 'Past President',
    'vice_president' => 'Vice President',
    'secretary' => 'Secretary',
    'joint_secretary' => 'Joint Secretary',
  ],
], [], 1, TRUE);

istst_add_field('node', 'committee_member', 'field_qualification_line', 'string', 'Qualification (short line)', [
  'max_length' => 255,
], [], 1, FALSE);
// e.g. "M.Ch – Cardiothoracic & Vascular Surgery, FRCS"

istst_add_field('node', 'committee_member', 'field_current_position', 'string', 'Current Position', [
  'max_length' => 255,
], [], 1, FALSE);
// e.g. "Director, CTVS"

istst_add_field('node', 'committee_member', 'field_degrees_training', 'text_long', 'Degrees / Training', [], [], 1, FALSE);
istst_add_field('node', 'committee_member', 'field_clinical_expertise', 'text_long', 'Clinical Expertise / Interests', [], [], 1, FALSE);
istst_add_field('node', 'committee_member', 'field_academic_experience', 'text_long', 'Academic Experience', [], [], 1, FALSE);

// ===================================================================
// 2. GALLERY IMAGE
// Single gallery page, exposed year filter, lightbox
// ===================================================================
istst_create_content_type(
  'gallery_image',
  'Gallery Image',
  'One image + caption for the Gallery page, tagged by event year for filtering.',
  'Caption'
);

istst_add_field('node', 'gallery_image', 'field_image', 'image', 'Image', [], [
  'alt_field_required' => TRUE,
  'file_directory' => 'gallery/[date:custom:Y]',
], 1, TRUE);

istst_add_field('node', 'gallery_image', 'field_event_year', 'list_string', 'Event Year', [
  'allowed_values' => [
    '2020' => '2020',
    '2022' => '2022',
    '2023' => '2023',
    '2024' => '2024',
    '2025' => '2025',
  ],
], [], 1, TRUE);

// ===================================================================
// 3. MEMBER
// Backs the Members' List page (name, membership type, member no.)
// ===================================================================
istst_create_content_type(
  'member',
  'Member',
  'A registered ISTST member, listed on the Members List page with search/filter by name and member ID.',
  'Name'
);

istst_add_field('node', 'member', 'field_membership_type', 'list_string', 'Membership Type', [
  'allowed_values' => [
    'life' => 'Life',
    'annual' => 'Annual',
    'honorary' => 'Honorary',
  ],
], [], 1, TRUE);

istst_add_field('node', 'member', 'field_membership_no', 'string', 'Membership No.', [
  'max_length' => 64,
], [], 1, TRUE);
// e.g. "ISTS/L/01"

// ===================================================================
// 4. HERO SLIDE
// For migrating HomeController's hardcoded slider data later --
// no rush to wire this up, just creating the type now while we're
// already in here. Controller/template changes come in a later step.
// ===================================================================
istst_create_content_type(
  'hero_slide',
  'Hero Slide',
  'One slide in the home page hero slider. Not yet wired up -- HomeController still uses hardcoded data for now.',
  'Slide Title'
);

istst_add_field('node', 'hero_slide', 'field_eyebrow_text', 'string', 'Eyebrow Text', [
  'max_length' => 128,
], [], 1, FALSE);
// e.g. "Global Knowledge"

istst_add_field('node', 'hero_slide', 'field_slide_text', 'text_long', 'Body Text', [], [], 1, FALSE);

istst_add_field('node', 'hero_slide', 'field_slide_image', 'image', 'Background Image', [], [
  'file_directory' => 'hero/[date:custom:Y]',
], 1, TRUE);

istst_add_field('node', 'hero_slide', 'field_cta_text', 'string', 'Button Text', [
  'max_length' => 64,
], [], 1, FALSE);
// e.g. "Explore Events"

istst_add_field('node', 'hero_slide', 'field_cta_url', 'link', 'Button Link', [], [
  'link_type' => \Drupal\link\LinkItemInterface::LINK_GENERIC,
  'title' => DRUPAL_DISABLED,
], 1, FALSE);

istst_add_field('node', 'hero_slide', 'field_gradient_style', 'list_string', 'Overlay Gradient', [
  'allowed_values' => [
    'primary-secondary' => 'Primary to Secondary',
    'dark-primary' => 'Dark to Primary',
    'accent-primary' => 'Accent to Primary',
  ],
], [], 1, FALSE);

print "\nAll content types and fields created.\n";
print "Run `drush cr` next, then check Structure > Content types.\n";
