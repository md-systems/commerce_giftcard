<?php

namespace Drupal\commerce_giftcard;

use Drupal\commerce_giftcard\Entity\GiftcardTypeInterface;
use Drupal\Core\Database\Connection;

/**
 * Gift card code generation service.
 */
class GiftcardCodeGenerator {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs a new CouponCodeGenerator object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   */
  public function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  /**
   * Generate codes for a given gift card type.
   *
   * @param \Drupal\commerce_giftcard\Entity\GiftcardTypeInterface $giftcard_type
   *   The gift card type.
   * @param int $quantity
   *   Number of codes to generate.
   *
   * @return string[]
   *   List of codes.
   */
  public function generateCodes(GiftcardTypeInterface $giftcard_type, $quantity) {
    // Generate twice the requested quantity, to improve chances of having
    // the needed quantity after removing non-unique/existing codes.
    $codes = [];
    for ($i = 0; $i < ($quantity * 2); $i++) {
      $code = \user_password($giftcard_type->getGenerateSetting('length'));
      $codes[strtolower($code)] = $code;
    }

    // Remove codes which already exist in the database.
    $result = $this->connection->select('commerce_giftcard', 'c')
      ->fields('c', ['code'])
      ->condition('code', $codes, 'IN')
      ->execute();
    $existing_codes = $result->fetchCol();
    // Codes are case insensitive, ensure that codes with different case are
    // removed as well.
    $codes = array_udiff($codes, $existing_codes, 'strcasecmp');

    return array_values(array_slice($codes, 0, $quantity));
  }
}
