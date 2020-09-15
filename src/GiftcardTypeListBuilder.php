<?php

namespace Drupal\commerce_giftcard;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of commerce gift card type entities.
 *
 * @see \Drupal\commerce_giftcard\Entity\GiftcardType
 */
class GiftcardTypeListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['title'] = $this->t('Label');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['title'] = [
      'data' => $entity->label(),
      'class' => ['menu-label'],
    ];

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();

    $build['table']['#empty'] = $this->t(
      'No commerce gift card types available. <a href=":link">Add commerce gift card type</a>.',
      [':link' => Url::fromRoute('entity.commerce_giftcard_type.add_form')->toString()]
    );

    return $build;
  }

}
