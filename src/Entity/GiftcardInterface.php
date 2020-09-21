<?php

namespace Drupal\commerce_giftcard\Entity;

use Drupal\commerce_price\Price;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a commerce gift card entity type.
 */
interface GiftcardInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

  /**
   * Gets the gift card code.
   *
   * @return string
   *   Code for the gift card.
   */
  public function getCode();

  /**
   * Sets the gift card code.
   *
   * @param string $code
   *   The gift card code.
   *
   * @return $this
   */
  public function setCode($code);

  /**
   * Gets the balance.
   *
   * @return \Drupal\commerce_price\Price
   *   The balance.
   */
  public function getBalance();

  /**
   * Sets the balance.
   *
   * @param \Drupal\commerce_price\Price $balance
   *   The balance.
   *
   * @return $this
   */
  public function setBalance(Price $balance);

  /**
   * Gets the commerce gift card creation timestamp.
   *
   * @return int
   *   Creation timestamp of the commerce gift card.
   */
  public function getCreatedTime();

  /**
   * Sets the commerce gift card creation timestamp.
   *
   * @param int $timestamp
   *   The commerce gift card creation timestamp.
   *
   * @return $this
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the commerce gift card status.
   *
   * @return bool
   *   TRUE if the commerce gift card is enabled, FALSE otherwise.
   */
  public function isEnabled();

  /**
   * Sets the commerce gift card status.
   *
   * @param bool $status
   *   TRUE to enable this commerce gift card, FALSE to disable.
   *
   * @return $this
   */
  public function setStatus($status);

}
