<?php

namespace Drupal\commerce_giftcard\Plugin\Validation\Constraint;

use Drupal\commerce_giftcard\Entity\GiftcardTransactionInterface;
use Drupal\commerce_price\Plugin\Field\FieldType\PriceItem;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the GiftcardTransactionValidAmount constraint.
 */
class GiftcardTransactionValidAmountValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    $item = $value->get(0);
    assert($item instanceof PriceItem);
    $transaction = $value->getEntity();
    assert($transaction instanceof GiftcardTransactionInterface);

    // Do not validate an already saved transaction.
    if (!$transaction->isNew() && !$item->isEmpty()) {
      return;
    }

    $price = $item->toPrice();
    if ($price) {
      if ($price->getCurrencyCode() != $transaction->getGiftCard()->getBalance()->getCurrencyCode()) {
        $this->context->buildViolation($constraint->currencyMessage, [
          '%transaction_currency' => $price->getCurrencyCode(),
          '%giftcard_currency' => $transaction->getGiftCard()->getBalance()->getCurrencyCode(),
          ])
          ->atPath('0.currency_code')
          ->setInvalidValue($price->getNumber())
          ->addViolation();
      }
      elseif ($price->isNegative() && $price->multiply('-1')->greaterThan($transaction->getGiftCard()->getBalance())) {
        $this->context->buildViolation($constraint->message)
          ->atPath('0.number')
          ->setInvalidValue($price->getNumber())
          ->addViolation();
      }
    }
  }

}
