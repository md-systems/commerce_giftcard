<?php

namespace Drupal\commerce_giftcard;

use Drupal\commerce_giftcard\Entity\GiftcardInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the list builder for coupons.
 */
class GiftcardTransactionListBuilder extends EntityListBuilder {

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Constructs a new CouponListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, RouteMatchInterface $route_match, DateFormatterInterface $date_formatter) {
    parent::__construct($entity_type, $storage);
    $this->routeMatch = $route_match;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('current_route_match'),
      $container->get('date.formatter')
    );
  }

  protected function getEntityIds() {
    $query = $this->getStorage()->getQuery()
      ->sort($this->entityType->getKey('id'));

    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $query->pager($this->limit);
    }

    $giftcard = $this->routeMatch->getParameter('commerce_giftcard');
    if ($giftcard instanceof GiftcardInterface) {
      $query->condition('giftcard', $giftcard->id());
    }

    return $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['created'] = $this->t('Created');
    $header['giftcard'] = $this->t('Giftcard');
    $header['amount'] = $this->t('Amount');
    $header['uid'] = $this->t('Owner');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['created'] = $this->dateFormatter->format($entity->getCreatedTime(), 'short');
    $row['giftcard'] = $entity->get('giftcard')->entity->label();
    $row['amount']['data'] = $entity->get('amount')->view(['label' => 'hidden']);
    $row['uid']['data'] = $entity->getOwnerId() ? [
      '#theme' => 'username',
      '#account' => $entity->getOwner(),
    ] : '';

    return $row + parent::buildRow($entity);
  }

}
