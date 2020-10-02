<?php

namespace Drupal\commerce_giftcard\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Validates gift card code transactions.
 *
 * @Constraint(
 *   id = "GiftcardTransactionValidAmount",
 *   label = @Translation("Gift card transaction amount", context = "Validation")
 * )
 */
class GiftcardTransactionValidAmount extends Constraint {

  public $message = 'The transaction amount must not result in a negative giftcard balance.';

  public $currencyMessage = 'The transaction amount currency (%transaction_currency) does not match the giftcard currency (%giftcard_currency).';

}
