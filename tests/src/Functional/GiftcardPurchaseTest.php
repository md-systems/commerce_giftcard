<?php

namespace Drupal\Tests\Functional\commerce_giftcard;

use Drupal\commerce_giftcard\Entity\Giftcard;
use Drupal\commerce_giftcard\Entity\GiftcardType;
use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_price\Price;
use Drupal\commerce_product\Entity\ProductVariationType;
use Drupal\Tests\commerce\Functional\CommerceBrowserTestBase;

/**
 * Tests purchasing giftcards.
 *
 * @group commerce_giftcard
 */
class GiftcardPurchaseTest extends CommerceBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'commerce_giftcard',
    'commerce_product',
    'commerce_order',
    'commerce_cart',
    'commerce_checkout',
  ];

  /**
   * The product.
   *
   * @var \Drupal\commerce_product\Entity\ProductInterface
   */
  protected $product;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Enble the trait.
    $trait_id = 'commerce_giftcard_purchaseable';
    /** @var \Drupal\commerce_product\Entity\ProductVariationTypeInterface $variation_type */
    $variation_type = ProductVariationType::load('default');
    $variation_type->setTraits([$trait_id]);
    $variation_type->save();

    $trait_manager = \Drupal::service('plugin.manager.commerce_entity_trait');
    $trait = $trait_manager->createInstance($trait_id);
    $trait_manager->installTrait($trait, 'commerce_product_variation', $variation_type->id());

    /** @var \Drupal\commerce_giftcard\Entity\GiftcardTypeInterface $giftcard_type */
    $giftcard_type = GiftcardType::create([
      'id' => 'example',
      'label' => 'Example',
    ]);
    $giftcard_type->setGenerateSetting('length', 12);
    $giftcard_type->save();

    $variation = $this->createEntity('commerce_product_variation', [
      'type' => 'default',
      'sku' => strtolower($this->randomMachineName()),
      'price' => [
        'number' => 9.99,
        'currency_code' => 'USD',
      ],
      'commerce_giftcard_amount' => [
        'number' => 19.99,
        'currency_code' => 'USD',
      ],
      'commerce_giftcard_type' => 'example',
    ]);

    /** @var \Drupal\commerce_product\Entity\ProductInterface $product */
    $this->product = $this->createEntity('commerce_product', [
      'type' => 'default',
      'title' => 'Giftcard 19.99',
      'variations' => [$variation],
      'stores' => [$this->store],
    ]);
  }

  /**
   * Tests purchasing a variation with the giftcard trait.
   */
  public function testGiftcardPurchase() {
    $giftcard_user = $this->createUser();
    $this->drupalLogin($giftcard_user);

    $this->drupalGet($this->product->toUrl());
    $this->submitForm([], 'Add to cart');
    $page = $this->getSession()->getPage();
    $cart_link = $page->findLink('your cart');
    $cart_link->click();
    $this->submitForm(['edit_quantity[0]' => 5], 'Checkout');

    $page->fillField('First name', 'Frederick');
    $page->fillField('Last name', 'Pabst');
    $page->fillField('Street address', 'Pabst Blue Ribbon Dr');
    $page->fillField('City', 'Milwaukee');
    $page->fillField('Zip code', '53177');
    $page->selectFieldOption('State', 'WI');

    $this->submitForm([], 'Continue to review');
    $this->submitForm([], 'Complete checkout');
    $this->assertSession()->pageTextContains('Your order number is 1. You can view your order on your account page when logged in.');

    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = Order::load(1);
    $items = $order->getItems();
    $item = reset($items);

    /** @var \Drupal\commerce_giftcard\Entity\GiftcardInterface[] $giftcards */
    $giftcards = Giftcard::loadMultiple();
    $this->assertCount(5, $giftcards);
    foreach ($giftcards as $giftcard) {
      $this->assertEquals('example', $giftcard->bundle());
      $this->assertEquals(new Price(19.99, 'USD'), $giftcard->getBalance());
      $this->assertRegExp('/^[0-9a-zA-Z]{12}$/', $giftcard->getCode());
      $this->assertEquals(\Drupal::currentUser()->id(), $giftcard->getOwnerId());
      $this->assertEquals([$order->getStoreId()], $giftcard->getStoreIds());

      $transactions = \Drupal::entityTypeManager()->getStorage('commerce_giftcard_transaction')->loadByProperties(['giftcard' => $giftcard->id()]);
      $this->assertCount(1, $transactions);
      /** @var \Drupal\commerce_giftcard\Entity\GiftcardTransactionInterface $transaction */
      $transaction = reset($transactions);
      $this->assertEquals(new Price(19.99, 'USD'), $transaction->getAmount());
      $this->assertEquals($item->id(), $transaction->getReferencedEntity()->id());
      $this->assertEquals('Bought Giftcard 19.99.', $transaction->getComment());
    }
  }

}
