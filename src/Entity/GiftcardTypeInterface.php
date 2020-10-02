<?php

namespace Drupal\commerce_giftcard\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Gift card type entity interface.
 */
interface GiftcardTypeInterface extends ConfigEntityInterface {

  /**
   * Returns the code generate settings.
   *
   * @return array
   *   The settings for code generation:
   *     - length: Length of the generated code.
   */
  public function getGenerateSettings();

  /**
   * Returns a generate code setting.
   *
   * @param string $name
   *   The setting key, currently supported:
   *      - length: Length of the generated code.
   *
   * @return $this
   */
  public function getGenerateSetting($name);


  /**
   * Sets a generate code setting.
   *
   * @param string $name
   *   The setting key, currently supported:
   *      - length: Length of the generated code.
   * @param mixed $value
   *   The value for the setting.
   *
   * @return $this
   */
  public function setGenerateSetting($name, $value);

  /**
   * Returns the configured display label.
   *
   * @return string
   *   The display label.
   */
  public function getDisplayLabel();

  /**
   * Sets the display label.
   *
   * @param string $display_label
   *   The new display label.
   *
   * @return $this
   */
  public function setDisplayLabel($display_label);

}
