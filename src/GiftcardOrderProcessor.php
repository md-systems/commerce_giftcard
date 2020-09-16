<?php

namespace Drupal\commerce_giftcard;

use Drupal\commerce_order\Adjustment;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\OrderProcessorInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Applies promotions to orders during the order refresh process.
 */
class GiftcardOrderProcessor implements OrderProcessorInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function process(OrderInterface $order) {
    /** @var \Drupal\commerce_giftcard\Entity\GiftcardInterface[] $giftcards */
    $giftcards = $order->get('commerce_giftcards')->referencedEntities();

    $order_total = $order->getTotalPrice();
    foreach ($giftcards as $index => $giftcard) {

      // If order total is zero, skip any remaining giftcard.
      if ($order_total->isZero()) {
        break;
      }

      // Decide how much of the balance is added as an adjustment.
      if ($order_total->greaterThan($giftcard->getBalance())) {
        $amount = $giftcard->getBalance();
      }
      else {
        $amount = $order_total;
      }

      // @todo Add this to order items instead?
      $order->addAdjustment(new Adjustment([
        'type' => 'commerce_giftcard',
        // @todo show giftcard code? Might be too long.
        'label' => $this->t('Giftcard'),
        'amount' => $amount->multiply('-1'),
        'source_id' => $giftcard->id(),
      ]));

      $order_total = $order_total->subtract($amount);
    }
  }

}
