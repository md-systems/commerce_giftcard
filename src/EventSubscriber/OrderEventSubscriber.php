<?php

namespace Drupal\commerce_giftcard\EventSubscriber;

use Drupal\commerce_giftcard\Entity\GiftcardInterface;
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
      'commerce_order.place.pre_transition' => 'registerUsage',
    ];
    return $events;
  }

  /**
   * Registers giftcard usage when the order is placed.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The workflow transition event.
   */
  public function registerUsage(WorkflowTransitionEvent $event) {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $event->getEntity();

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

}
