<?php

namespace Drupal\commerce_giftcard;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Session\AccountInterface;

/**
 * Access control handler for giftcard transactions.
 */
class GiftcardTransactionAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return parent::checkCreateAccess($account, $context, $entity_bundle)
      ->orIf(AccessResult::allowedIfHasPermission($account, 'create giftcard transaction'));
  }

}
