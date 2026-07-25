<?php

namespace Drupal\istst_core\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Returns the ISTST home page content.
 *
 * NOTE: hero slides and committee data are hardcoded here for now.
 * Once the "Hero Slide" and "Committee Member" content types exist
 * (see project task 4), swap the arrays below for entity queries /
 * a Views result so editors can manage this from the CMS.
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

    $committee = [
      ['name' => 'Dr. SV Srikrishna', 'role' => 'President', 'initials' => 'SV'],
      ['name' => 'Dr Rajan Santosham', 'role' => 'Past President', 'initials' => 'RS'],
      ['name' => 'RS Reddy', 'role' => 'Joint Secretary', 'initials' => 'RR'],
      ['name' => 'Dr. Ravindra Kumar Dewan', 'role' => 'Vice President', 'initials' => 'RD'],
      ['name' => 'Dr Bhabatosh Biswas', 'role' => 'Secretary', 'initials' => 'BB'],
    ];

    return [
      '#theme' => 'istst_home',
      '#hero_slides' => $hero_slides,
      '#committee' => $committee,
      '#attached' => [
        'library' => ['istst_theme/global-styling'],
      ],
      '#cache' => [
        'max-age' => 3600,
      ],
    ];
  }

}
