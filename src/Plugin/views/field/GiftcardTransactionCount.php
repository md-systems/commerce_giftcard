<?php

namespace Drupal\commerce_giftcard\Plugin\views\field;

use Drupal\Core\Link;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Giftcard transaction count field plugin.
 *
 * @ViewsField("commerce_giftcard_transaction_count")
 */
class GiftcardTransactionCount extends FieldPluginBase {

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
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $plugin = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $plugin->entityTypeManager = $container->get('entity_type.manager');
    return $plugin;
  }

  /**
   * {@inheritdoc}
   */
  public function preRender(&$values) {
    parent::preRender($values);

    // Prefetch counts for all displayed giftcard.

    $ids = array();
    foreach ($values as $value) {
      $ids[] = $this->getValue($value);
    }

    if ($ids) {
      $results = $this->entityTypeManager->getStorage('commerce_giftcard_transaction')
        ->getAggregateQuery()
        ->condition('giftcard', $ids, 'IN')
        ->groupBy('giftcard')
        ->aggregate('id', 'COUNT')
        ->execute();
      foreach ($results as $result) {
        $this->transactionsCounts[$result['giftcard']] = $result['id_count'];
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $id = $this->getValue($values);
    $count = $this->transactionsCounts[$id] ?? 0;

    if ($count) {
      return Link::createFromRoute($count, 'entity.commerce_giftcard_transaction.giftcard_collection', ['commerce_giftcard' => $id])->toRenderable();
    }
    else {
      return $count;
    }

  }
}
