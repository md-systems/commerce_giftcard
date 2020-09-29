<?php

namespace Drupal\commerce_giftcard\Entity;

use Drupal\commerce\Entity\CommerceContentEntityBase;
use Drupal\commerce\EntityOwnerTrait;
use Drupal\commerce_price\Price;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the commerce gift card entity class.
 *
 * @ContentEntityType(
 *   id = "commerce_giftcard",
 *   label = @Translation("Gift card"),
 *   label_collection = @Translation("Gift cards"),
 *   label_singular = @Translation("Gift card"),
 *   label_plural = @Translation("Gift cards"),
 *   label_count = @PluralTranslation(
 *     singular = "@count gift card",
 *     plural = "@count gift cards",
 *   ),
 *   bundle_label = @Translation("Gift card type"),
 *   handlers = {
 *     "list_builder" = "Drupal\commerce_giftcard\GiftcardListBuilder",
 *     "access" = "Drupal\commerce_giftcard\GiftcardAccessControlHandler",
 *     "storage_schema" = "Drupal\commerce_giftcard\GiftcardStorageSchema",
 *     "views_data" = "Drupal\commerce_giftcard\GiftcardViewsData",
 *     "form" = {
 *       "add" = "Drupal\commerce_giftcard\Form\GiftcardForm",
 *       "edit" = "Drupal\commerce_giftcard\Form\GiftcardForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *       "delete-multiple-confirm" = "\Drupal\Core\Entity\Form\DeleteMultipleForm"
 *     },
 *     "route_provider" = {
 *       "html" = "\Drupal\commerce_giftcard\GiftcardRouteProvider",
 *       "delete-multiple" = "Drupal\entity\Routing\DeleteMultipleRouteProvider",
 *     },
 *     "local_task_provider" = {
 *       "default" = "Drupal\entity\Menu\DefaultEntityLocalTaskProvider",
 *     },
 *   },
 *   base_table = "commerce_giftcard",
 *   admin_permission = "administer commerce_giftcard",
 *   entity_keys = {
 *     "id" = "id",
 *     "bundle" = "type",
 *     "label" = "code",
 *     "uuid" = "uuid",
 *     "owner" = "uid"
 *   },
 *   links = {
 *     "add-form" = "/admin/commerce/giftcards/add/{commerce_giftcard_type}",
 *     "add-page" = "/admin/commerce/giftcards/add",
 *     "edit-form" = "/admin/commerce/giftcards/{commerce_giftcard}/edit",
 *     "delete-form" = "/admin/commerce/giftcards/{commerce_giftcard}/delete",
 *     "delete-multiple-form" = "/admin/commerce/giftcards/delete",
 *     "collection" = "/admin/commerce/giftcards"
 *   },
 *   bundle_entity_type = "commerce_giftcard_type",
 *   field_ui_base_route = "entity.commerce_giftcard_type.edit_form"
 * )
 */
class Giftcard extends CommerceContentEntityBase implements GiftcardInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    return (bool) $this->get('status')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setStatus($status) {
    $this->set('status', $status);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCode() {
    return $this->get('code')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCode($code) {
    $this->set('code', $code);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getBalance() {
    if (!$this->get('balance')->isEmpty()) {
      return $this->get('balance')->first()->toPrice();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setBalance(Price $price) {
    $this->set('balance', $price);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getStores() {
    return $this->getTranslatedReferencedEntities('stores');
  }

  /**
   * {@inheritdoc}
   */
  public function setStores(array $stores) {
    $this->set('stores', $stores);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getStoreIds() {
    $store_ids = [];
    foreach ($this->get('stores') as $store_item) {
      $store_ids[] = $store_item->target_id;
    }
    return $store_ids;
  }

  /**
   * {@inheritdoc}
   */
  public function setStoreIds(array $store_ids) {
    $this->set('stores', $store_ids);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Status'))
      ->setDefaultValue(TRUE)
      ->setSettings([
        'on_label' => t('Enabled'),
        'off_label' => t('Disabled'),
      ])
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'settings' => [
          'display_label' => FALSE,
        ],
        'weight' => 30,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['uid']
      ->setLabel(t('Owner'))
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      // Giftcards do not automatically belong to the current user.
      ->setDefaultValueCallback(NULL);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created on'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'));

    $fields['code'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Code'))
      ->setRequired(TRUE)
      ->setDescription(t('The unique, machine-readable identifier for a gift card.'))
      ->addConstraint('GiftcardCode')
      ->setSettings([
        'max_length' => 50,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -10,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['balance'] = BaseFieldDefinition::create('commerce_price')
      ->setLabel(t('Balance'))
      ->setDescription(t('Current balance'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'commerce_price_default',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['stores'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Stores'))
      ->setDescription(t('Limits the gift card to the selected stores'))
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setSetting('target_type', 'commerce_store')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('form', [
        'type' => 'commerce_entity_select',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}
