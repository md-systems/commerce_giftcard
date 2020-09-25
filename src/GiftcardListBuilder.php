<?php

namespace Drupal\commerce_giftcard;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
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
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * List of preloaded transaction counts per giftcard.
   *
   * @var int[]
   */
  protected $transactionsCounts = [];

  /**
   * Constructs a new GiftcardListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityTypeManagerInterface $entity_type_manager, DateFormatterInterface $date_formatter) {
    parent::__construct($entity_type, $entity_type_manager->getStorage($entity_type->id()));
    $this->entityTypeManager = $entity_type_manager;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager'),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function load() {
    $entities = parent::load();

    if ($entities) {
      $results = $this->entityTypeManager->getStorage('commerce_giftcard_transaction')->getAggregateQuery()
        ->condition('giftcard', array_keys($entities), 'IN')
        ->groupBy('giftcard')
        ->aggregate('id', 'COUNT')
        ->execute();
      foreach ($results as $result) {
        $this->transactionsCounts[$result['giftcard']] = $result['id_count'];
      }
    }

    return $entities;
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
    $header['transactions'] = $this->t('Transactions');
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

    if (!empty($this->transactionsCounts[$entity->id()])) {
      $row['transactions']['data'] = Link::createFromRoute($this->transactionsCounts[$entity->id()], 'entity.commerce_giftcard_transaction.giftcard_collection', ['commerce_giftcard' => $entity->id()])->toRenderable();
    }
    else {
      $row['transactions'] = 0;
    }

    $row['created'] = $this->dateFormatter->format($entity->getCreatedTime(), 'short');
    $row['changed'] = $this->dateFormatter->format($entity->getChangedTime(), 'short');
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);
    if ($this->entityTypeManager->getAccessControlHandler('commerce_giftcard_transaction')->createAccess()) {
      $operations['add_transaction'] = [
        'title' => $this->t('Add transaction'),
        'weight' => 0,
        'url' => $this->ensureDestination(Url::fromRoute('entity.commerce_giftcard_transaction.add_form', [], ['query' => ['commerce_giftcard' => $entity->id()]])),
      ];
    }
    return $operations;
  }

}
