<?php

namespace Drupal\commerce_giftcard\Event;

use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_price\Price;
use Symfony\Component\EventDispatcher\Event;

/**
 * Gift card amount calculation event.
 */
class GiftcardAmountCalculateEvent extends Event {

  /**
   * Order item.
   *
   * @var \Drupal\commerce_order\Entity\OrderItemInterface
   */
  private $orderItem;

  /**
   * The amount.
   *
   * @var \Drupal\commerce_price\Price|null
   */
  private $amount;

  /**
   * GiftcardAmountCalculateEvent constructor.
   */
  public function __construct(OrderItemInterface $order_item, $amount = NULL) {
    $this->orderItem = $order_item;
    $this->amount = $amount;
  }

  /**
   * Returns the order item.
   *
   * @return \Drupal\commerce_order\Entity\OrderItemInterface
   *   The order item.
   */
  public function getOrderItem() {
    return $this->orderItem;
  }

  /**
   * Returns the amount.
   *
   * @return \Drupal\commerce_price\Price|null
   *   The gift card amount.
   */
  public function getAmount() {
    return $this->amount;
  }

  /**
   * Set a new amount.
   *
   * @param \Drupal\commerce_price\Price|null $amount
   *   The new price or NULL to not create a gift card.
   */
  public function setAmount(Price $amount = NULL) {
    $this->amount = $amount;
  }

}
