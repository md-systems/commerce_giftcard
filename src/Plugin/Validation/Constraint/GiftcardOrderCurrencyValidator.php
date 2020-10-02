<?php

namespace Drupal\commerce_giftcard\Plugin\Validation\Constraint;

use Drupal\commerce_giftcard\Entity\GiftcardInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the GiftcardOrderCurrency constraint.
 */
class GiftcardOrderCurrencyValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    assert($value instanceof EntityReferenceFieldItemListInterface);
    $order = $value->getEntity();
    assert($order instanceof OrderInterface);


    foreach ($value as $delta => $item) {
      $giftcard = $item->entity;
      if ($giftcard instanceof GiftcardInterface) {
        if ($giftcard->getBalance()->getCurrencyCode() != $order->getTotalPrice()->getCurrencyCode()) {
          $this->context->buildViolation($constraint->message, [
            '%order_currency' => $order->getTotalPrice()->getCurrencyCode(),
            '%giftcard_currency' => $giftcard->getBalance()->getCurrencyCode(),
            ])
            ->atPath($delta)
            ->setInvalidValue($giftcard->getBalance()->getCurrencyCode())
            ->addViolation();
        }
      }

    }

  }

}
