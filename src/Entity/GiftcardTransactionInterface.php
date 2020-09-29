<?php

namespace Drupal\commerce_giftcard\Entity;

use Drupal\commerce_price\Price;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a commerce gift card entity type.
 */
interface GiftcardTransactionInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

  /**
   * Gets the gift card ID of this transaction.
   *
   * @return int
   *   ID of the gift card.
   */
  public function getGiftcardId();

  /**
   * Returns the gift card of this transaction.
   *
   * @return \Drupal\commerce_giftcard\Entity\GiftcardInterface
   *   The gift card of this transaction.
   */
  public function getGiftCard();

  /**
   * Gets the amount.
   *
   * @return \Drupal\commerce_price\Price
   *   The amount.
   */
  public function getAmount();

  /**
   * Sets the amount.
   *
   * @param \Drupal\commerce_price\Price $amount
   *   The amount.
   *
   * @return $this
   */
  public function setAmount(Price $amount);

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
   * Returns the translated message.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|null
   *   The message if any, or NULL.
   */
  public function getComment();

  /**
   * Returns the referenced entity of this transaction, if any.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The referenced entity or NULL.
   */
  public function getReferencedEntity();

}
