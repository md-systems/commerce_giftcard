<?php

namespace Drupal\commerce_giftcard\Plugin\Commerce\CheckoutPane;

use Drupal\commerce\InlineFormManager;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowInterface;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the giftcard redemption pane.
 *
 * @CommerceCheckoutPane(
 *   id = "commerce_giftcard_redemption",
 *   label = @Translation("Giftcard redemption"),
 *   default_step = "_sidebar",
 *   wrapper_element = "container",
 * )
 */
class GiftcardRedemption extends CheckoutPaneBase {

  /**
   * The inline form manager.
   *
   * @var \Drupal\commerce\InlineFormManager
   */
  protected $inlineFormManager;

  /**
   * Constructs a new GiftcardRedemption object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowInterface $checkout_flow
   *   The parent checkout flow.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\commerce\InlineFormManager $inline_form_manager
   *   The inline form manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CheckoutFlowInterface $checkout_flow, EntityTypeManagerInterface $entity_type_manager, InlineFormManager $inline_form_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $checkout_flow, $entity_type_manager);

    $this->inlineFormManager = $inline_form_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, CheckoutFlowInterface $checkout_flow = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $checkout_flow,
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.commerce_inline_form')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'allow_multiple' => FALSE,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationSummary() {
    if ($this->configuration['allow_multiple']) {
      $summary = $this->t('Allows multiple giftcards: Yes');
    }
    else {
      $summary = $this->t('Allows multiple giftcards: No');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['allow_multiple'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow multiple giftcards to be redeemed'),
      '#default_value' => $this->configuration['allow_multiple'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['allow_multiple'] = $values['allow_multiple'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form) {
    $inline_form = $this->inlineFormManager->createInstance('commerce_giftcard_redemption', [
      'order_id' => $this->order->id(),
      'max_giftcards' => $this->configuration['allow_multiple'] ? NULL : 1,
    ]);

    $pane_form['form'] = [
      '#parents' => array_merge($pane_form['#parents'], ['form']),
    ];
    $pane_form['form'] = $inline_form->buildInlineForm($pane_form['form'], $form_state);

    return $pane_form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitPaneForm(array &$pane_form, FormStateInterface $form_state, array &$complete_form) {
    // The form was submitted with a non-applied giftcard in the input field,
    // mapped to a giftcard ID in GiftcardRedemptionForm::validateForm().
    if (!empty($pane_form['form']['code']['#giftcard_id'])) {
      $this->order->get('commerce_giftcards')->appendItem($pane_form['form']['code']['#giftcard_id']);
    }
  }

}
