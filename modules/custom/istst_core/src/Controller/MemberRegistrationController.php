<?php

namespace Drupal\istst_core\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Returns the Member Registration page.
 *
 * Static sidebar content (fees, bank details, eligibility) lives in
 * the twig template directly for now -- flag as a candidate for its
 * own small content type/config later if it needs frequent editing.
 * The actual form is the "member_registration" Webform, embedded as
 * a first-class render element so it keeps Webform's built-in
 * validation, spam protection, and submissions admin UI.
 */
class MemberRegistrationController extends ControllerBase {

  /**
   * Builds the Member Registration page render array.
   */
  public function content() {
    $webform_markup = [
      '#type' => 'webform',
      '#webform' => 'member_registration',
    ];

    return [
      '#theme' => 'istst_member_registration',
      '#webform' => $webform_markup,
      '#attached' => [
        'library' => ['istst_theme/global-styling'],
      ],
    ];
  }

}
