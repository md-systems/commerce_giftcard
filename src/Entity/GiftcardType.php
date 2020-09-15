<?php

namespace Drupal\commerce_giftcard\Entity;

use Drupal\commerce\Entity\CommerceBundleEntityBase;
use Drupal\commerce\Entity\CommerceBundleEntityInterface;
use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Commerce Gift Card type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "commerce_giftcard_type",
 *   label = @Translation("Gift card type"),
 *   label_collection = @Translation("Gift card types"),
 *   label_singular = @Translation("gift card type"),
 *   label_plural = @Translation("gift card types"),
 *   label_count = @PluralTranslation(
 *     singular = "@count gift card type",
 *     plural = "@count gift card types",
 *   ),
 *   handlers = {
 *     "access" = "Drupal\commerce\CommerceBundleAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\commerce_giftcard\Form\GiftcardTypeForm",
 *       "edit" = "Drupal\commerce_giftcard\Form\GiftcardTypeForm",
 *       "duplicate" = "Drupal\commerce_giftcard\Form\GiftcardTypeForm",
 *       "delete" = "Drupal\commerce\Form\CommerceBundleEntityDeleteFormBase"
 *     },
 *     "list_builder" = "Drupal\commerce_giftcard\GiftcardTypeListBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "local_task_provider" = {
 *       "default" = "Drupal\entity\Menu\DefaultEntityLocalTaskProvider",
 *     },
 *   },
 *   admin_permission = "administer commerce_gift_card",
 *   bundle_of = "commerce_giftcard",
 *   config_prefix = "giftcard_type",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "add-form" = "/admin/commerce/config/giftcard_types/add",
 *     "edit-form" = "/admin/commerce/config/giftcard_types/manage/{commerce_giftcard_type}",
 *     "delete-form" = "/admin/commerce/config/giftcard_types/manage/{commerce_giftcard_type}/delete",
 *     "collection" = "/admin/commerce/config/giftcard_types"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "uuid",
 *   }
 * )
 */
class GiftcardType extends ConfigEntityBundleBase implements GiftcardTypeInterface {

  /**
   * The machine name of this commerce gift card type.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the commerce gift card type.
   *
   * @var string
   */
  protected $label;

}