<?php

namespace Drupal\commerce_giftcard\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Provides a field handler that renders a translated comment with variables.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("commerce_giftcard_transaction_comment")
 */
class TransactionComment extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    /** @var \Drupal\commerce_giftcard\Entity\GiftcardTransactionInterface $transaction */
    $transaction = $this->getEntity($values);
    return $transaction->getComment();
  }

}

