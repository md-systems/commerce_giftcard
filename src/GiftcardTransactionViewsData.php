<?php

namespace Drupal\commerce_giftcard;

use Drupal\commerce\CommerceEntityViewsData;

/**
 * Views integration for the gift card transaction type.
 */
class GiftcardTransactionViewsData extends CommerceEntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();
    $data['commerce_giftcard_transaction']['comment']['field']['id']= 'commerce_giftcard_transaction_comment';
    return $data;
  }

}
