<?php

namespace Drupal\commerce_giftcard\Event;


use Drupal\commerce_price\Price;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class GiftcardAmountCalculateEvent.
 */
class GiftcardAmountCalculateEvent extends Event {

  /**
   * The amount.
   *
   * @var \Drupal\commerce_price\Price|null
   */
  private $amount;

  /**
   * GiftcardAmountCalculateEvent constructor.
   */
  public function __construct($amount = NULL) {
    $this->amount = $amount;
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
  public function setAmount(Price $amount) {
    $this->amount = $amount;
  }

}
