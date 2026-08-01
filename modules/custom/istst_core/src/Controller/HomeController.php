<?php

namespace Drupal\istst_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\NodeInterface;

/**
 * Returns the ISTST home page content.
 *
 * NOTE: hero slides are still hardcoded here for now (see the
 * "Hero Slide" content type -- not yet wired up). Committee data
 * comes straight from Committee Member nodes via a direct entity
 * query (no View involved) -- see getCommitteeMembers() below.
 */
class HomeController extends ControllerBase {

  /**
   * Builds the home page render array.
   */
  public function content() {
    $hero_slides = [
      [
        'eyebrow' => 'Together,',
        'title' => 'Advancing Thoracic Care Empowering Health',
        'text' => 'Leading the future of thoracic surgery in India through continuous education, research, and innovation.',
        'image' => 'https://images.unsplash.com/photo-1551076805-e1869033e561?q=80&w=2070&auto=format&fit=crop',
        'cta_text' => 'Read More',
        'cta_url' => '#about',
        'gradient' => 'from-primary/80 to-secondary/40',
      ],
      [
        'eyebrow' => 'Global Knowledge',
        'title' => 'Fostering Excellence & Collaboration',
        'text' => 'Join our annual congress to engage with global experts and stay updated on the latest advancements.',
        'image' => 'https://images.unsplash.com/photo-1579684385127-1ef15d508118?q=80&w=2080&auto=format&fit=crop',
        'cta_text' => 'Explore Events',
        'cta_url' => '#events',
        'gradient' => 'from-dark/90 to-primary/60',
      ],
      [
        'eyebrow' => 'Impactful Achievements',
        'title' => 'Improving Patient Outcomes Nationwide',
        'text' => 'Dedicated to advancing surgical standards and providing innovative solutions to complex thoracic conditions.',
        'image' => 'https://images.unsplash.com/photo-1581056771107-24ca5f033842?q=80&w=2070&auto=format&fit=crop',
        'cta_text' => 'Join ISTST',
        'cta_url' => '#member-cta',
        'gradient' => 'from-accent/90 to-primary/80',
      ],
    ];

    return [
      '#theme' => 'istst_home',
      '#hero_slides' => $hero_slides,
      '#committee' => $this->getCommitteeMembers(),
      '#attached' => [
        'library' => ['istst_theme/global-styling'],
      ],
      '#cache' => [
        // Invalidated whenever any committee_member node is
        // added/edited/deleted/unpublished.
        'tags' => ['node_list:committee_member'],
      ],
    ];
  }

  /**
   * Loads published Committee Member nodes directly (no View),
   * ordered by node ID ascending -- so whatever order you create
   * the 5 nodes in (President first, then Past President, etc.) is
   * the order they display in here.
   *
   * Reshapes each into the same {name, role, initials, photo_url}
   * array the istst-home.html.twig template already expects, so the
   * template's markup/design doesn't change -- only the image src
   * falls back to a real photo when one exists.
   */
  private function getCommitteeMembers(): array {
    $fallback = [
      ['name' => 'Dr. SV Srikrishna', 'role' => 'President', 'initials' => 'SV', 'photo_url' => '', 'url' => ''],
      ['name' => 'Dr Rajan Santosham', 'role' => 'Past President', 'initials' => 'RS', 'photo_url' => '', 'url' => ''],
      ['name' => 'RS Reddy', 'role' => 'Joint Secretary', 'initials' => 'RR', 'photo_url' => '', 'url' => ''],
      ['name' => 'Dr. Ravindra Kumar Dewan', 'role' => 'Vice President', 'initials' => 'RD', 'photo_url' => '', 'url' => ''],
      ['name' => 'Dr Bhabatosh Biswas', 'role' => 'Secretary', 'initials' => 'BB', 'photo_url' => '', 'url' => ''],
    ];

    $storage = $this->entityTypeManager()->getStorage('node');

    $nids = $storage->getQuery()
      ->condition('type', 'committee_member')
      ->condition('status', NodeInterface::PUBLISHED)
      ->sort('nid', 'ASC')
      ->accessCheck(TRUE)
      ->execute();

    if (empty($nids)) {
      return $fallback;
    }

    $role_labels = [
      'president' => 'President',
      'past_president' => 'Past President',
      'vice_president' => 'Vice President',
      'secretary' => 'Secretary',
      'joint_secretary' => 'Joint Secretary',
    ];

    $committee = [];
    foreach ($storage->loadMultiple($nids) as $node) {
      /** @var \Drupal\node\NodeInterface $node */
      $name = $node->label();

      $role_key = $node->hasField('field_designation_role') ? $node->get('field_designation_role')->value : '';
      $role = $role_labels[$role_key] ?? ucwords(str_replace('_', ' ', $role_key));

      $photo_url = '';
      if ($node->hasField('field_photo') && !$node->get('field_photo')->isEmpty()) {
        $file = $node->get('field_photo')->entity;
        if ($file) {
          $photo_url = \Drupal::service('file_url_generator')->generateAbsoluteString($file->getFileUri());
        }
      }

      $committee[] = [
        'name' => $name,
        'role' => $role,
        'initials' => $this->initialsFromName($name),
        'photo_url' => $photo_url,
        // Links to the node's own canonical page for now (/node/N).
        // TODO: once dedicated /committee/[role] routes exist, swap
        // this to build that path instead -- only this one line
        // needs to change, template/controller shape stays the same.
        'url' => $node->toUrl()->toString(),
      ];
    }

    return $committee ?: $fallback;
  }

  /**
   * Builds a 1-2 letter initials fallback from a name, used when a
   * Committee Member node has no photo uploaded yet.
   */
  private function initialsFromName(string $name): string {
    $name = preg_replace('/^(Dr\.?|Prof\.?)\s+/i', '', trim($name));
    $parts = preg_split('/\s+/', $name);
    $parts = array_slice($parts, 0, 2);
    $initials = '';
    foreach ($parts as $part) {
      $initials .= mb_strtoupper(mb_substr($part, 0, 1));
    }
    return $initials ?: '?';
  }

}
