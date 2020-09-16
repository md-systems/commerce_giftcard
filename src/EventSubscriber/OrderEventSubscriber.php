<?php

namespace Drupal\commerce_giftcard\EventSubscriber;

use Drupal\commerce_giftcard\Entity\GiftcardInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\state_machine\Event\WorkflowTransitionEvent;

/**
 * Order event subscriber.
 */
class OrderEventSubscriber implements EventSubscriberInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * OrderEventSubscriber constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *  The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [
      'commerce_order.place.pre_transition' => 'orderPlaced',
    ];
    return $events;
  }

  /**
   * Listens on the order placed event.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The workflow transition event.
   */
  public function orderPlaced(WorkflowTransitionEvent $event) {
    $this->registerUsage($event->getEntity());
    $this->giftcardPurchase($event->getEntity());
  }

  /**
   * Registers giftcard usage when the order is placed.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   */
  protected function registerUsage(OrderInterface $order) {
    $adjustments = $order->collectAdjustments();
    foreach ($adjustments as $adjustment) {
      if ($adjustment->getType() != 'commerce_giftcard' || !$adjustment->getSourceId() || $adjustment->getAmount()->isZero()) {
        continue;
      }

      $giftcard = $this->entityTypeManager->getStorage('commerce_giftcard')->load($adjustment->getSourceId());

      // Create a transaction for each used giftcard.
      if ($giftcard instanceof GiftcardInterface) {

        /** @var \Drupal\commerce_giftcard\Entity\GiftcardTransactionInterface $transaction */
        $transaction = $this->entityTypeManager->getStorage('commerce_giftcard_transaction')->create([
          'giftcard' => $giftcard->id(),
          'amount' => $adjustment->getAmount(),
        ]);
        $transaction->save();
      }
    }
  }

  /**
   * Creates giftcard when entity with giftcard amount is purchased.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   */
  protected function giftcardPurchase(OrderInterface $order) {
    $items = $order->getItems();

    foreach ($items as $item) {
      $purchased_entity = $item->getPurchasedEntity();
      if ($purchased_entity && $purchased_entity->hasField('commerce_giftcard_amount') && $purchased_entity->hasField('commerce_giftcard_type') && !$purchased_entity->get('commerce_giftcard_amount')->isEmpty()) {

        for ($i = 0; $i < $item->getQuantity(); $i++) {
          // @todo Add a connection between order/line item/variation and
          //   created giftcard to be able to disable if the order is canceled.
          $giftcard = $this->entityTypeManager->getStorage('commerce_giftcard')->create([
            'type' => $purchased_entity->get('commerce_giftcard_type')->target_id,
            // @todo: Make this configurable. Settings on the coupon type?
            'code' => \user_password(),
            'balance' => $purchased_entity->get('commerce_giftcard_amount')->first()->toPrice(),
            'uid' => $order->getCustomerId(),
          ]);
          $giftcard->save();
        }
      }
    }
  }

}
