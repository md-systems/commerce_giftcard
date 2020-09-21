<?php

namespace Drupal\commerce_giftcard\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Ensures gift card code uniqueness.
 *
 * @Constraint(
 *   id = "GiftcardTransactionValidAmount",
 *   label = @Translation("Gift transaction amount", context = "Validation")
 * )
 */
class GiftcardTransactionValidAmount extends Constraint {

  public $message = 'The transaction amount must not result in a negative giftcard balance.';

  public $currencyMessage = 'The transaction amount currency (%transaction_currency) does not match the giftcard currency (%giftcard_currency).';

}
