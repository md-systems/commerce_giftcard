<?php

namespace Drupal\commerce_giftcard\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the commerce gift card entity edit forms.
 */
class GiftcardForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {

    $entity = $this->getEntity();
    $result = $entity->save();

    $message_arguments = ['%label' => $this->entity->label()];

    if ($result == SAVED_NEW) {
      $this->messenger()->addStatus($this->t('New gift card %label has been created.', $message_arguments));
      $this->logger('commerce_giftcard')->notice('Created new gift card %label', $message_arguments);
    }
    else {
      $this->messenger()->addStatus($this->t('The gift card %label has been updated.', $message_arguments));
      $this->logger('commerce_giftcard')->notice('Updated gift card %label.', $message_arguments);
    }

    $form_state->setRedirect('entity.commerce_giftcard.collection');
  }

}
