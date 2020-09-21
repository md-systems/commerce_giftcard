<?php

namespace Drupal\commerce_giftcard\Form;

use CommerceGuys\Intl\Formatter\CurrencyFormatterInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for the commerce gift card transaction add form.
 */
class GiftcardTransactionForm extends ContentEntityForm {

  /**
   * The currency formatter.
   *
   * @var \CommerceGuys\Intl\Formatter\CurrencyFormatterInterface
   */
  protected $currencyFormatter;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $form = parent::create($container);
    $form->currencyFormatter = $container->get('commerce_price.currency_formatter');
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareEntity() {
    parent::prepareEntity();

    if ($this->getRequest()->query->has('commerce_giftcard') && $this->entity->get('giftcard')->isEmpty()) {
      $this->entity->set('giftcard', $this->getRequest()->query->get('commerce_giftcard'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    // Allow negative amounts.
    if (isset($form['amount']['widget'][0])) {
      $form['amount']['widget'][0]['#allow_negative'] = TRUE;
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {

    /** @var \Drupal\commerce_giftcard\Entity\GiftcardTransactionInterface $entity */
    $entity = $this->getEntity();
    $entity->save();

    $giftcard = $entity->getGiftCard();

    $this->messenger()->addStatus($this->t('%amount transaction for giftcard %code has been created, new balance: %balance.', [
      '%amount' => $this->currencyFormatter->format($entity->getAmount()->getNumber(), $entity->getAmount()->getCurrencyCode()),
      '%code' => $giftcard->getCode(),
      '%balance' => $this->currencyFormatter->format($giftcard->getBalance()->getNumber(), $giftcard->getBalance()->getCurrencyCode()),
    ]));

    $form_state->setRedirect('entity.commerce_giftcard.collection');
  }

}
