<?php

namespace Drupal\commerce_giftcard;

use Drupal\commerce_order\Adjustment;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\OrderProcessorInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Utility\Token;

/**
 * Applies promotions to orders during the order refresh process.
 */
class GiftcardOrderProcessor implements OrderProcessorInterface {

  use StringTranslationTrait;

  /**
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * GiftcardOrderProcessor constructor.
   *
   * @param \Drupal\Core\Utility\Token $token
   *   The token service.
   */
  public function __construct(Token $token) {
    $this->token = $token;
  }

  /**
   * {@inheritdoc}
   */
  public function process(OrderInterface $order) {
    /** @var \Drupal\commerce_giftcard\Entity\GiftcardInterface[] $giftcards */
    $giftcards = $order->get('commerce_giftcards')->referencedEntities();

    $order_total = $order->getTotalPrice();
    foreach ($giftcards as $index => $giftcard) {

      // If order total is zero, skip any remaining giftcard.
      if ($order_total->isZero()) {
        break;
      }

      // Ignore giftcards with the wrong currency, validation should prevent
      // this from happening.
      if ($giftcard->getBalance()->getCurrencyCode() !== $order_total->getCurrencyCode()) {
        continue;
      }

      // Decide how much of the balance is added as an adjustment.
      if ($order_total->greaterThan($giftcard->getBalance())) {
        $amount = $giftcard->getBalance();
      }
      else {
        $amount = $order_total;
      }

      if ($giftcard->get('type')->entity->getDisplayLabel()) {
        $label = $this->token->replace($giftcard->get('type')->entity->getDisplayLabel(), ['commerce_giftcard' => $giftcard]);
      }
      else {
        $label = $this->t('Gift card');
      }

      $order->addAdjustment(new Adjustment([
        'type' => 'commerce_giftcard',
        'label' => $label,
        'amount' => $amount->multiply('-1'),
        'source_id' => $giftcard->id(),
      ]));

      $order_total = $order_total->subtract($amount);
    }
  }

}
