<?php

namespace Drupal\commerce_giftcard\Plugin\Validation\Constraint;

use Drupal\Core\Validation\Plugin\Validation\Constraint\UniqueFieldConstraint;

/**
 * Ensures gift card code uniqueness.
 *
 * @Constraint(
 *   id = "GiftcardCode",
 *   label = @Translation("Gift card code", context = "Validation")
 * )
 */
class GiftcardCodeConstraint extends UniqueFieldConstraint {

  public $message = 'The gift card code %value is already in use and must be unique.';

}
