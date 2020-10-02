<?php

namespace Drupal\commerce_giftcard\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Ensures gift card code order currency.
 *
 * @Constraint(
 *   id = "GiftcardOrderCurrency",
 *   label = @Translation("Giftcard order currency", context = "Validation")
 * )
 */
class GiftcardOrderCurrency extends Constraint {

  public $message = 'The order currency (%order_currency) does not match the giftcard currency (%giftcard_currency).';

}
