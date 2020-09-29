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

    $data['commerce_giftcard__stores']['stores_target_id']['field']['id'] = 'commerce_store';

    return $data;
  }

}
