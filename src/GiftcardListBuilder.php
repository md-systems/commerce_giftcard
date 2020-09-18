<?php

namespace Drupal\commerce_giftcard;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a list controller for the commerce gift card entity type.
 */
class GiftcardListBuilder extends EntityListBuilder {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The redirect destination service.
   *
   * @var \Drupal\Core\Routing\RedirectDestinationInterface
   */
  protected $redirectDestination;

  /**
   * Constructs a new GiftcardListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $redirect_destination
   *   The redirect destination service.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, DateFormatterInterface $date_formatter, RedirectDestinationInterface $redirect_destination) {
    parent::__construct($entity_type, $storage);
    $this->dateFormatter = $date_formatter;
    $this->redirectDestination = $redirect_destination;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('date.formatter'),
      $container->get('redirect.destination')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['code'] = $this->t('Code');
    $header['type'] = $this->t('Type');
    $header['status'] = $this->t('Status');
    $header['balance'] = $this->t('Balance');
    $header['uid'] = $this->t('Owner');
    $header['created'] = $this->t('Created');
    $header['changed'] = $this->t('Updated');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\commerce_giftcard\Entity\GiftcardInterface */
    $row['code'] = $entity->getCode();
    $row['type'] = $entity->get('type')->entity->label();
    $row['status'] = $entity->isEnabled() ? $this->t('Enabled') : $this->t('Disabled');
    $row['balance']['data'] = $entity->get('balance')->view(['label' => 'hidden']);
    $row['uid']['data'] = $entity->getOwnerId() ? [
      '#theme' => 'username',
      '#account' => $entity->getOwner(),
    ] : '';
    $row['created'] = $this->dateFormatter->format($entity->getCreatedTime(), 'short');
    $row['changed'] = $this->dateFormatter->format($entity->getChangedTime(), 'short');
    return $row + parent::buildRow($entity);
  }

}
