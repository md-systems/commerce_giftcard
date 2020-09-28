<?php

namespace Drupal\commerce_giftcard;

use Drupal\commerce\CommerceEntityViewsData;

/**
 * Views integration for the giftcard entity type.
 */
class GiftcardViewsData extends CommerceEntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['commerce_giftcard']['transactions'] = array(
      'title' => $this->t('Transaction count'),
      'help' => 'Displays a transaction count',
      'real field' => 'id',
      'field' => array(
        'id' => 'commerce_giftcard_transaction_count',
      ),
    );

    return $data;
  }

}
