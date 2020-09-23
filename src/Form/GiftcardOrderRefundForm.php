<?php

namespace Drupal\commerce_giftcard\Form;

use CommerceGuys\Intl\Formatter\CurrencyFormatterInterface;
use Drupal\commerce_giftcard\GiftcardOrderManager;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_price\Price;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for bulk generating gift cards.
 */
class GiftcardOrderRefundForm extends FormBase {

  /**
   * The gift card order manager.
   *
   * @var \Drupal\commerce_giftcard\GiftcardOrderManager
   */
  protected $giftcardOrderManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The currency formatter.
   *
   * @var \CommerceGuys\Intl\Formatter\CurrencyFormatterInterface
   */
  protected $currencyFormatter;

  /**
   * The order.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $order;

  /**
   * GiftcardOrderRefundForm constructor.
   *
   * @param \Drupal\commerce_giftcard\GiftcardOrderManager $giftcard_order_manager
   */
  public function __construct(GiftcardOrderManager $giftcard_order_manager, EntityTypeManagerInterface $entity_type_manager, CurrencyFormatterInterface $currency_formatter) {
    $this->giftcardOrderManager = $giftcard_order_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->currencyFormatter =$currency_formatter;
  }

  public static function create(ContainerInterface $container) {
    return new static($container->get('commerce_giftcard.order_manager'), $container->get('entity_type.manager'), $container->get('commerce_price.currency_formatter'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_giftcard_order_refund_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, OrderInterface $commerce_order = NULL) {
    $this->order = $commerce_order;

    $adjustments = $this->giftcardOrderManager->getAdjustments($commerce_order);
    $options = [];
    foreach ($adjustments as $adjustment) {
      $giftcard = $this->entityTypeManager->getStorage('commerce_giftcard')->load($adjustment->getSourceId());
      if ($giftcard) {
        $amount = $adjustment->getAmount()->multiply('-1');
        $options[$giftcard->id()] = $this->t('@label (@amount)', [
          '@label' => $giftcard->label(),
          '@amount' => $this->currencyFormatter->format($amount->getNumber(), $amount->getCurrencyCode()),
        ]);
      }
    }

    $form['giftcard'] = [
      '#type' => 'select',
      '#title' => $this->t('Gift card'),
      '#required' => TRUE,
      '#options' => $options,
      '#default_value' => key($options)
    ];

    $form['amount'] = [
      '#type' => 'commerce_price',
      '#title' => $this->t('Refund amount'),
      '#required' => TRUE,
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Refund'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    $giftcard = $this->entityTypeManager->getStorage('commerce_giftcard')->load($form_state->getValue('giftcard'));
    if ($giftcard) {
      $adjustment = $this->giftcardOrderManager->getAdjustmentForGiftcard($this->order, $giftcard);
      if (!$adjustment) {
        $form_state->setErrorByName('giftcard', $this->t('Adjustment not found.'));
        return;
      }
      $amount = Price::fromArray($form_state->getValue('amount'));
      $max = $adjustment->getAmount()->multiply('-1');
      if ($adjustment && $amount->greaterThan($max)) {
        $form_state->setErrorByName('amount', $this->t('Amount must not be larger than remaining adjustment amount (@amount)', [
          '@amount' => $this->currencyFormatter->format($max->getNumber(), $max->getCurrencyCode()),
        ]));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $giftcard = $this->entityTypeManager->getStorage('commerce_giftcard')->load($form_state->getValue('giftcard'));
    $adjustment = $this->giftcardOrderManager->getAdjustmentForGiftcard($this->order, $giftcard);
    $amount = Price::fromArray($form_state->getValue('amount'));
    $this->giftcardOrderManager->refundAdjustment($this->order, $adjustment, $amount);

    $this->messenger()->addMessage($this->t('Refunded %amount for giftcard %code', [
      '%amount' => $this->currencyFormatter->format($amount->getNumber(), $amount->getCurrencyCode()),
      '%code' => $giftcard->getCode(),
    ]));
    $form_state->setRedirectUrl($this->order->toUrl());
  }

}
