<?php

namespace Drupal\commerce_giftcard;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\AdminHtmlRouteProvider;

/**
 * Customizing entity routes.
 */
class GiftcardRouteProvider extends AdminHtmlRouteProvider {

  /**
   * {@inheritdoc}
   */
  protected function getCollectionRoute(EntityTypeInterface $entity_type) {
    return parent::getCollectionRoute($entity_type)
      ->setRequirement('_permission', 'access giftcard overview');
  }

}
