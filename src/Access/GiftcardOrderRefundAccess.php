<?php

namespace Drupal\commerce_giftcard\Access;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;

class GiftcardOrderRefundAccess {

  /**
   * Checks that the user has access to refund giftcards on an order.
   *
   * The order must be placed and have giftcards.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   */
  public function checkAccess(RouteMatchInterface $route_match, AccountInterface $account) {
    $order = $route_match->getParameter('commerce_order');
    if (!$order instanceof OrderInterface) {
      return AccessResult::neutral();
    }
    if ($order->getState()->getId() === 'draft') {
      return AccessResult::forbidden()->addCacheableDependency($order);
    }
    if ($order->get('commerce_giftcards')->isEmpty()) {
      return AccessResult::forbidden()->addCacheableDependency($order);
    }

    // @todo extend access checks.
    return $order->access('update', $account, TRUE)->andIf(AccessResult::allowedIfHasPermission($account, 'administer commerce_giftcard'));
  }

}
