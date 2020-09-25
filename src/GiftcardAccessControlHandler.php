<?php

namespace Drupal\commerce_giftcard;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access control handler for giftcards.
 */
class GiftcardAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {

    if ($operation == 'view') {
      return AccessResult::allowedIfHasPermissions($account, [$this->entityType->getAdminPermission(), 'access giftcard overview'], 'OR');
    }

    return parent::checkAccess($entity, $operation, $account);
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return parent::checkCreateAccess($account, $context, $entity_bundle)
      ->orIf(AccessResult::allowedIfHasPermission($account, 'create giftcard'));
  }

}
