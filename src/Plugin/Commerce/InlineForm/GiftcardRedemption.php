<?php

namespace Drupal\commerce_giftcard\Plugin\Commerce\InlineForm;

use Drupal\commerce\Plugin\Commerce\InlineForm\InlineFormBase;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an inline form for redeeming a giftcard.
 *
 * @CommerceInlineForm(
 *   id = "commerce_giftcard_redemption",
 *   label = @Translation("Giftcard redemption"),
 * )
 */
class GiftcardRedemption extends InlineFormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new GiftcardRedemption object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      // The order_id is passed via configuration to avoid serializing the
      // order, which is loaded from scratch in the submit handler to minimize
      // chances of a conflicting save.
      'order_id' => '',
      // NULL for unlimited.
      'max_giftcards' => NULL,
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function requiredConfiguration() {
    return ['order_id'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildInlineForm(array $inline_form, FormStateInterface $form_state) {
    $inline_form = parent::buildInlineForm($inline_form, $form_state);

    $order = $this->entityTypeManager->getStorage('commerce_order')->load($this->configuration['order_id']);
    if (!$order) {
      throw new \RuntimeException('Invalid order_id given to the giftcard_redemption inline form.');
    }
    assert($order instanceof OrderInterface);
    /** @var \Drupal\commerce_giftcard\Entity\GiftcardInterface[] $giftcards */
    $giftcards = $order->get('commerce_giftcards')->referencedEntities();

    $inline_form = [
      '#tree' => TRUE,
      '#attached' => [
        // @todo Copy JS library.
        // 'library' => ['commerce_promotion/giftcard_redemption_form'],
      ],
      '#theme' => 'commerce_giftcard_redemption_form',
      '#configuration' => $this->getConfiguration(),
    ] + $inline_form;
    $inline_form['code'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Giftcard code'),
      // Chrome autofills this field with the address line 1, and ignores
      // autocomplete => 'off', but respects 'new-password'.
      '#attributes' => [
        'autocomplete' => 'new-password',
      ],
    ];
    $inline_form['apply'] = [
      '#type' => 'submit',
      '#value' => t('Apply giftcard'),
      '#name' => 'apply_giftcard',
      '#limit_validation_errors' => [
        $inline_form['#parents'],
      ],
      '#submit' => [
        [get_called_class(), 'applyGiftcard'],
      ],
      '#ajax' => [
        'callback' => [get_called_class(), 'ajaxRefreshForm'],
        'element' => $inline_form['#parents'],
      ],
    ];
    $max_giftcards = $this->configuration['max_giftcards'];
    if ($max_giftcards && count($giftcards) >= $max_giftcards) {
      // Don't allow additional giftcards to be added.
      $inline_form['code']['#access'] = FALSE;
      $inline_form['apply']['#access'] = FALSE;
    }

    foreach ($giftcards as $index => $giftcard) {
      $inline_form['giftcards'][$index]['code'] = [
        '#plain_text' => $giftcard->getCode(),
      ];
      $inline_form['giftcards'][$index]['remove_button'] = [
        '#type' => 'submit',
        '#value' => t('Remove giftcard'),
        '#name' => 'remove_giftcard_' . $index,
        '#ajax' => [
          'callback' => [get_called_class(), 'ajaxRefreshForm'],
          'element' => $inline_form['#parents'],
        ],
        '#weight' => 50,
        '#limit_validation_errors' => [
          $inline_form['#parents'],
        ],
        '#giftcard_id' => $giftcard->id(),
        '#submit' => [
          [get_called_class(), 'removeGiftcard'],
        ],
        // Simplify ajaxRefresh() by having all triggering elements
        // on the same level.
        '#parents' => array_merge($inline_form['#parents'], ['remove_giftcard_' . $index]),
      ];
    }

    return $inline_form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateInlineForm(array &$inline_form, FormStateInterface $form_state) {
    parent::validateInlineForm($inline_form, $form_state);

    // Runs if the 'Apply giftcard' button was clicked, or the main form
    // was submitted by the user clicking the primary submit button.
    $triggering_element = $form_state->getTriggeringElement();
    $button_type = isset($triggering_element['#button_type']) ? $triggering_element['#button_type'] : NULL;
    if ($triggering_element['#name'] != 'apply_giftcard' && $button_type != 'primary') {
      return;
    }

    $giftcard_code_parents = array_merge($inline_form['#parents'], ['code']);
    $giftcard_code = $form_state->getValue($giftcard_code_parents);
    $giftcard_code_path = implode('][', $giftcard_code_parents);
    if (empty($giftcard_code)) {
      if ($triggering_element['#name'] == 'apply_giftcard') {
        $form_state->setErrorByName($giftcard_code_path, t('Please provide a giftcard code.'));
      }
      return;
    }
    $giftcard_storage = $this->entityTypeManager->getStorage('commerce_giftcard');
    $giftcards = $giftcard_storage->loadByProperties(['code' => $giftcard_code]);
    if (empty($giftcards)) {
      $form_state->setErrorByName($giftcard_code_path, t('The provided giftcard code is invalid.'));
      return;
    }
    /** @var \Drupal\commerce_giftcard\Entity\GiftcardInterface $giftcard */
    $giftcard = reset($giftcards);

    $order_storage = $this->entityTypeManager->getStorage('commerce_order');
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $order_storage->load($this->configuration['order_id']);
    foreach ($order->get('commerce_giftcards') as $item) {
      if ($item->target_id == $giftcard->id()) {
        // Giftcard already applied. Error message not set for UX reasons.
        return;
      }
    }
    // @todo Support conditions for the order.
    if ($giftcard->getBalance()->isZero()) {
      $form_state->setErrorByName($giftcard_code_path, t('The provided giftcard has no balance.'));
      return;
    }

    // Save the giftcard ID for applyGiftcard.
    $inline_form['code']['#giftcard_id'] = $giftcard->id();
  }

  /**
   * Submit callback for the "Apply giftcard" button.
   */
  public static function applyGiftcard(array $form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $parents = array_slice($triggering_element['#parents'], 0, -1);
    $inline_form = NestedArray::getValue($form, $parents);
    // Clear the giftcard code input.
    $user_input = &$form_state->getUserInput();
    NestedArray::setValue($user_input, array_merge($parents, ['code']), '');

    if (isset($inline_form['code']['#giftcard_id'])) {
      $order_storage = \Drupal::entityTypeManager()->getStorage('commerce_order');
      /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
      $order = $order_storage->load($inline_form['#configuration']['order_id']);
      $order->get('commerce_giftcards')->appendItem($inline_form['code']['#giftcard_id']);
      $order->save();
    }
    $form_state->setRebuild();
  }

  /**
   * Submit callback for the "Remove giftcard" button.
   */
  public static function removeGiftcard(array $form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $parents = array_slice($triggering_element['#parents'], 0, -1);
    $inline_form = NestedArray::getValue($form, $parents);

    $order_storage = \Drupal::entityTypeManager()->getStorage('commerce_order');
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $order_storage->load($inline_form['#configuration']['order_id']);
    $giftcard_ids = array_column($order->get('commerce_giftcards')->getValue(), 'target_id');
    $giftcard_index = array_search($triggering_element['#giftcard_id'], $giftcard_ids);
    $order->get('commerce_giftcards')->removeItem($giftcard_index);
    $order->save();
    $form_state->setRebuild();
  }

}
