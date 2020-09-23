<?php

namespace Drupal\commerce_giftcard;

use Drupal\commerce_giftcard\Entity\GiftcardInterface;
use Drupal\commerce_order\Adjustment;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_price\Price;
use Drupal\Core\Entity\EntityTypeManagerInterface;

class GiftcardOrderManager {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * GiftcardOrderManager constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Returns gift card adjustments of an order.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   *
   * @return \Drupal\commerce_order\Adjustment[]
   *   Adjustments of type giftcard.
   */
  public function getAdjustments(OrderInterface $order) {
    $adjustments = [];
    foreach ($order->getAdjustments() as $adjustment) {
      if ($adjustment->getType() == 'commerce_giftcard') {
        $adjustments[] = $adjustment;
      }
    }
    return $adjustments;
  }

  /**
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   * @param \Drupal\commerce_giftcard\Entity\GiftcardInterface $giftcard
   *
   * @return \Drupal\commerce_order\Adjustment
   */
  public function getAdjustmentForGiftcard(OrderInterface $order, GiftcardInterface $giftcard) {
    foreach ($this->getAdjustments($order) as $adjustment) {
      if ($adjustment->getSourceId() == $giftcard->id()) {
        return $adjustment;
      }
    }
  }

  /**
   * Refund a giftcard adjustment partially or fully.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   * @param \Drupal\commerce_order\Adjustment $adjustment
   *   The adjustment to refund for.
   * @param \Drupal\commerce_price\Price|null $amount
   *   The amount to refund, defaults to the full adjustment amount.
   */
  public function refundAdjustment(OrderInterface $order, Adjustment $adjustment, Price $amount = NULL) {
    $amount = $amount ?: $adjustment->getAmount()->multiply('-1');

    // If the adjustment is fully refunded, remove it, otherwise replace it.
    // @todo Use a reverse giftcard refund adjustment instead? Or ensure that
    // @todo use a transaction.
    if ($amount->equals($adjustment->getAmount()->multiply('-1'))) {
      $order->removeAdjustment($adjustment);
    }
    else {
      // Create a replacement adjustment with the updated amount,
      // then get the list of adjustments, replace the old one and set them
      // back.
      $adjustment_values = $adjustment->toArray();
      $adjustment_values['amount'] = $adjustment->getAmount()->add($amount);
      $updated_adjustment = new Adjustment($adjustment_values);

      $adjustments = $order->getAdjustments();
      foreach ($adjustments as $key => $existing_adjustment) {
        if ($updated_adjustment->getType() == $existing_adjustment->getType() && $updated_adjustment->getSourceId() == $existing_adjustment->getSourceId()) {
          $adjustments[$key] = $updated_adjustment;
        }
      }
      $order->setAdjustments($adjustments);
    }
    $order->save();

    // Add a transaction for this refund.
    /** @var \Drupal\commerce_giftcard\Entity\GiftcardTransactionInterface $transaction */
    $transaction = $this->entityTypeManager->getStorage('commerce_giftcard_transaction')->create([
      'giftcard' => $adjustment->getSourceId(),
      'amount' => $amount,
    ]);
    $transaction->save();
  }

}
