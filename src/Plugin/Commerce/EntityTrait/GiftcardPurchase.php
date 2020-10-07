<?php

namespace Drupal\commerce_giftcard\Plugin\Commerce\EntityTrait;

use Drupal\commerce\Plugin\Commerce\EntityTrait\EntityTraitBase;
use Drupal\entity\BundleFieldDefinition;

/**
 * Provides the "commerce_giftcard_purchaseable" trait.
 *
 * @CommerceEntityTrait(
 *   id = "commerce_giftcard_purchaseable",
 *   label = @Translation("Allows to buy giftcards"),
 *   entity_types = {"commerce_product_variation"}
 * )
 */
class GiftcardPurchase extends EntityTraitBase {

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    $fields = [];
    $fields['commerce_giftcard_amount'] = BundleFieldDefinition::create('commerce_price')
      ->setDescription(t('If this field is left empty, the price of the product will be used as the giftcard amount'))
      ->setLabel(t('Giftcard amount'))
      ->setDisplayOptions('form', [
        'type' => 'commerce_price_default',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['commerce_giftcard_type'] = BundleFieldDefinition::create('entity_reference')
      ->setLabel(t('Giftcard type'))
      // @todo Add validation to make it required when there is an amount.
      ->setSetting('target_type', 'commerce_giftcard_type')
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -2,
      ])
      ->setDisplayConfigurable('form', TRUE);

    return $fields;
  }

}
