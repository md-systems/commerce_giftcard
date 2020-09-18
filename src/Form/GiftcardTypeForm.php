<?php

namespace Drupal\commerce_giftcard\Form;

use Drupal\Core\Entity\BundleEntityFormBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form handler for commerce gift card type forms.
 */
class GiftcardTypeForm extends BundleEntityFormBase {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $entity_type = $this->entity;
    if ($this->operation == 'add') {
      $form['#title'] = $this->t('Add gift card type');
    }
    else {
      $form['#title'] = $this->t(
        'Edit %label gift card type',
        ['%label' => $entity_type->label()]
      );
    }

    $form['label'] = [
      '#title' => $this->t('Label'),
      '#type' => 'textfield',
      '#default_value' => $entity_type->label(),
      '#description' => $this->t('The human-readable name of this gift card type.'),
      '#required' => TRUE,
      '#size' => 30,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $entity_type->id(),
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
      '#machine_name' => [
        'exists' => ['Drupal\commerce_giftcard\Entity\GiftcardType', 'load'],
        'source' => ['label'],
      ],
      '#description' => $this->t('A unique machine-readable name for this gift card type. It must only contain lowercase letters, numbers, and underscores.'),
    ];

    $form['generate'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Code pattern'),
      '#tree' => TRUE,
    ];
    $form['generate']['length'] = [
      '#type' => 'number',
      '#title' => $this->t('Length'),
      '#required' => TRUE,
      '#default_value' => $this->entity->getGenerateSetting('length'),
      '#min' => 1,
    ];

    return $this->protectBundleIdElement($form);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;

    $entity->set('id', trim($entity->id()));
    $entity->set('label', trim($entity->label()));

    $status = $entity->save();

    $t_args = ['%name' => $entity->label()];
    if ($status == SAVED_UPDATED) {
      $message = $this->t('The gift card type %name has been updated.', $t_args);
    }
    elseif ($status == SAVED_NEW) {
      $message = $this->t('The gift card type %name has been added.', $t_args);
    }
    $this->messenger()->addStatus($message);

    $form_state->setRedirectUrl($entity->toUrl('collection'));
  }

}
