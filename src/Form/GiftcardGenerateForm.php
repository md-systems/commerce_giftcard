<?php

namespace Drupal\commerce_giftcard\Form;

use Drupal\commerce_giftcard\Entity\GiftcardType;
use Drupal\Core\Batch\BatchBuilder;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form for bulk generating gift cards.
 */
class GiftcardGenerateForm extends FormBase {

  /**
   * The number of gift cards to generate in each batch.
   *
   * @var int
   */
  const BATCH_SIZE = 25;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_giftcard_generate_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $options = [];
    foreach (GiftcardType::loadMultiple() as $giftcard_type) {
      $options[$giftcard_type->id()] = $giftcard_type->label();
    }

    $form['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Gift card type'),
      '#required' => TRUE,
      '#options' => $options,
    ];

    $form['balance'] = [
      '#type' => 'commerce_price',
      '#title' => $this->t('Initial balance'),
      '#required' => TRUE,
    ];

    $form['quantity'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of gift cards'),
      '#required' => TRUE,
      '#default_value' => '10',
      '#min' => 1,
      '#step' => 1,
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Generate'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $giftcard_values = [
      'type' => $form_state->getValue(['type']),
      'balance' => $form_state->getValue('balance'),
    ];

    $batch_builder = (new BatchBuilder())
      ->setTitle($this->t('Generating gift cards'))
      ->setProgressMessage('')
      ->setFinishCallback([$this, 'finishBatch'])
      ->addOperation([get_class($this), 'processBatch'], [$form_state->getValue('quantity'), $giftcard_values]);
    batch_set($batch_builder->toArray());

    $form_state->setRedirect('entity.commerce_giftcard.collection');
  }

  /**
   * Processes the batch and generates the gift cards.
   *
   * @param int $quantity
   *   The number of gift cards to generate.
   * @param string[] $giftcard_values
   *   The initial gift card entity values.
   * @param array $context
   *   The batch context information.
   */
  public static function processBatch($quantity, array $giftcard_values, array &$context) {
    if (empty($context['sandbox'])) {
      $context['sandbox']['total_quantity'] = (int) $quantity;
      $context['sandbox']['created'] = 0;
      $context['results']['codes'] = [];
      $context['results']['total_quantity'] = $quantity;
    }

    $total_quantity = $context['sandbox']['total_quantity'];
    $created = &$context['sandbox']['created'];
    $remaining = $total_quantity - $created;

    $type = GiftcardType::load($giftcard_values['type']);

    $giftcard_storage = \Drupal::entityTypeManager()->getStorage('commerce_giftcard');
    $limit = ($remaining < self::BATCH_SIZE) ? $remaining : self::BATCH_SIZE;
    $giftcard_code_generator = \Drupal::service('commerce_giftcard.code_generator');
    $codes = $giftcard_code_generator->generateCodes($type, $limit);
    if (!empty($codes)) {
      foreach ($codes as $code) {
        $giftcard = $giftcard_storage->create([
          'code' => $code,
        ] + $giftcard_values);
        $giftcard->save();
        $context['results']['codes'][] = $code;
        $created++;
      }
      $context['message'] = t('Creating gift card @created of @total_quantity', [
        '@created' => $created,
        '@total_quantity' => $total_quantity,
      ]);
      $context['finished'] = $created / $total_quantity;
    }
    else {
      $context['finished'] = 1;
    }
  }

  /**
   * Batch finished callback: display batch statistics.
   *
   * @param bool $success
   *   Indicates whether the batch has completed successfully.
   * @param mixed[] $results
   *   The array of results gathered by the batch processing.
   * @param string[] $operations
   *   If $success is FALSE, contains the operations that remained unprocessed.
   */
  public static function finishBatch($success, array $results, array $operations) {
    if ($success) {
      $created = count($results['codes']);
      // An incomplete set of gift cards was generated.
      if ($created != $results['total_quantity']) {
        \Drupal::messenger()->addWarning(t('Generated %created out of %total requested gift cards.', [
          '%created' => $created,
          '%total' => $results['total_quantity'],
        ]));
      }
      else {
        \Drupal::messenger()->addMessage(\Drupal::translation()->formatPlural(
          $created,
          'Generated 1 gift card.',
          'Generated @count gift cards.'
        ));
      }
    }
    else {
      \Drupal::messenger()->addError(t('An error occurred while generating gift cards.'));
    }
  }

}
