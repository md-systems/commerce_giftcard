<?php

namespace Drupal\commerce_giftcard\Event;

use Drupal\commerce_order\Entity\OrderItemInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class GiftcardAmountCalculateEvent.
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
   * Order item getter.
   *
   * @return \Drupal\commerce_order\Entity\OrderItemInterface
   *   The order item.
   */
  public function getOrderItem() {
    return $this->orderItem;
  }

  /**
   * Get the amount.
   */
  public function getAmount() {
    return $this->amount;
  }

  /**
   * Amount setter.
   */
  public function setAmount($amount) {
    $this->amount = $amount;
  }

}
