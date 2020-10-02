<?php

namespace Drupal\Tests\commerce_promotion\FunctionalJavascript;

use Drupal\commerce_checkout\Entity\CheckoutFlow;
use Drupal\commerce_giftcard\Entity\GiftcardType;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_order\Entity\OrderItemType;
use Drupal\commerce_payment\Entity\Payment;
use Drupal\commerce_payment\Entity\PaymentGateway;
use Drupal\commerce_price\Price;
use Drupal\commerce_tax\Entity\TaxType;
use Drupal\Core\Url;
use Drupal\Tests\commerce\FunctionalJavascript\CommerceWebDriverTestBase;

/**
 * Tests the gift card redemption checkout pane.
 *
 * @group commerce_giftcard
 */
class GiftcardRedemptionPaneTest extends CommerceWebDriverTestBase {

  /**
   * The cart order to test against.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $cart;

  /**
   * The cart manager.
   *
   * @var \Drupal\commerce_cart\CartManagerInterface
   */
  protected $cartManager;

  /**
   * The gift card.
   *
   * @var \Drupal\commerce_giftcard\Entity\GiftcardInterface
   */
  protected $giftcard;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce_cart',
    'commerce_giftcard',
    'commerce_checkout',
    'commerce_payment',
    'commerce_payment_example',
    'commerce_tax',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->cart = $this->container->get('commerce_cart.cart_provider')->createCart('default', $this->store, $this->adminUser);
    $this->cartManager = $this->container->get('commerce_cart.cart_manager');

    OrderItemType::create([
      'id' => 'test',
      'label' => 'Test',
      'orderType' => 'default',
    ])->save();
    $order_item = OrderItem::create([
      'type' => 'test',
      'quantity' => 1,
      'unit_price' => new Price('999.00', 'USD'),
    ]);
    $order_item->save();
    $this->cartManager->addOrderItem($this->cart, $order_item);

    // Create a gift card and gift card type.
    $giftcard_type = GiftcardType::create([
      'id' => 'example',
      'label' => 'Example',
    ]);
    $giftcard_type->save();
    $this->giftcard = $this->createEntity('commerce_giftcard', [
      'code' => 'ABC',
      'type' => 'example',
      'balance' => new Price('100.00', 'USD'),
      'status' => TRUE,
    ]);
    $this->giftcard->save();

    /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface $offsite_gateway */
    $offsite_gateway = PaymentGateway::create([
      'id' => 'offsite',
      'label' => 'Off-site',
      'plugin' => 'example_offsite_redirect',
      'configuration' => [
        'redirect_method' => 'post',
        'payment_method_types' => ['credit_card'],
      ],
    ]);
    $offsite_gateway->save();

    /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface $onsite_gateway */
    $onsite_gateway = PaymentGateway::create([
      'id' => 'onsite',
      'label' => 'On-site',
      'plugin' => 'example_onsite',
      'configuration' => [
        'api_key' => '2342fewfsfs',
        'payment_method_types' => ['credit_card'],
      ],
    ]);
    $onsite_gateway->save();

    $profile = $this->createEntity('profile', [
      'type' => 'customer',
      'address' => [
        'country_code' => 'US',
        'postal_code' => '53177',
        'locality' => 'Milwaukee',
        'address_line1' => 'Pabst Blue Ribbon Dr',
        'administrative_area' => 'WI',
        'given_name' => 'Frederick',
        'family_name' => 'Pabst',
      ],
      'uid' => $this->adminUser->id(),
    ]);
    $payment_method1 = $this->createEntity('commerce_payment_method', [
      'uid' => $this->adminUser->id(),
      'type' => 'credit_card',
      'payment_gateway' => 'onsite',
      'card_type' => 'visa',
      'card_number' => '1111',
      'billing_profile' => $profile,
      'reusable' => TRUE,
      'expires' => strtotime('2028/03/24'),
    ]);
    $payment_method1->setBillingProfile($profile);
    $payment_method1->save();
    $payment_method2 = $this->createEntity('commerce_payment_method', [
      'type' => 'credit_card',
      'payment_gateway' => 'onsite',
      'card_type' => 'visa',
      'card_number' => '9999',
      'billing_profile' => $profile,
      'reusable' => TRUE,
      'expires' => strtotime('2028/03/24'),
    ]);
    $payment_method2->setBillingProfile($profile);
    $payment_method2->save();

    // Set up tax.
    $this->store->set('prices_include_tax', TRUE);
    $this->store->save();

    // The default store is US-WI, so imagine that the US has VAT.
    TaxType::create([
      'id' => 'us_vat',
      'label' => 'US VAT',
      'plugin' => 'custom',
      'configuration' => [
        'display_inclusive' => TRUE,
        'rates' => [
          [
            'id' => 'standard',
            'label' => 'Standard',
            'percentage' => '0.2',
          ],
        ],
        'territories' => [
          ['country_code' => 'US', 'administrative_area' => 'WI'],
          ['country_code' => 'US', 'administrative_area' => 'SC'],
        ],
      ],
    ])->save();

  }

  /**
   * Tests redeeming a gift card using the gift card redemption pane.
   */
  public function testGiftcardRedemption() {

    $currency_importer = $this->container->get('commerce_price.currency_importer');
    $currency_importer->import('CHF');

    $checkout_url = Url::fromRoute('commerce_checkout.form', [
      'commerce_order' => $this->cart->id(),
    ]);

    $this->drupalGet($checkout_url);

    // Assert that the total price and tax show up as expected.
    $this->assertSession()->elementContains('css', '.order-total-line__subtotal', '$999.00');
    $this->assertSession()->elementContains('css', '.order-total-line__adjustment--tax', '$166.50');
    $this->assertSession()->elementContains('css', '.order-total-line__total', '$999.00');

    // Confirm that validation errors set by the form element are visible.
    $this->getSession()->getPage()->pressButton('Apply gift card');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->pageTextContains('Please provide a gift card code');

    // Valid gift card.
    $this->getSession()->getPage()->fillField('Giftcard code', $this->giftcard->getCode());
    $this->getSession()->getPage()->pressButton('Apply gift card');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->pageTextContains($this->giftcard->getCode());
    $this->assertSession()->fieldNotExists('Giftcard code');
    $this->assertSession()->buttonNotExists('Apply gift card');
    $this->assertSession()->elementContains('css', '.order-total-line__adjustment--commerce-giftcard .order-total-line-label', 'Giftcard');
    // Assert that the tax and subtotal remains unchanged but the total is
    // reduced.
    $this->assertSession()->elementContains('css', '.order-total-line__adjustment--commerce-giftcard .order-total-line-value', '-$100.00');
    $this->assertSession()->elementContains('css', '.order-total-line__subtotal', '$999.00');
    $this->assertSession()->elementContains('css', '.order-total-line__adjustment--tax', '$166.50');
    $this->assertSession()->elementContains('css', '.order-total-line__total', '$899.00');

    // Giftcard removal.
    $this->getSession()->getPage()->pressButton('Remove gift card');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->pageTextNotContains($this->giftcard->getCode());
    $this->assertSession()->fieldExists('Giftcard code');
    $this->assertSession()->buttonExists('Apply gift card');
    $this->assertSession()->pageTextNotContains('-$100.00');
    $this->assertSession()->elementNotExists('css', '.order-total-line__adjustment--commerce-giftcard');
    $this->assertSession()->pageTextContains('$999');

    // Invalid gift card code.
    $this->getSession()->getPage()->fillField('Giftcard code', 'XYZ');
    $this->getSession()->getPage()->pressButton('Apply gift card');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->pageTextContains('The provided gift card code is invalid.');
    $this->assertSession()->fieldExists('Giftcard code');

    // A gift card with no balance.
    $giftcard2 = $this->createEntity('commerce_giftcard', [
      'code' => 'DEF',
      'type' => 'example',
      'balance' => new Price('0.00', 'USD'),
      'status' => TRUE,
    ]);
    $giftcard2->save();

    $this->getSession()->getPage()->fillField('Giftcard code', $giftcard2->getCode());
    $this->getSession()->getPage()->pressButton('Apply gift card');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->pageTextContains('The provided gift card has no balance.');
    $this->assertSession()->fieldExists('Giftcard code');

    // A giftcard for the wrong store.
    $store2 = $this->createStore();
    $giftcard3 = $this->createEntity('commerce_giftcard', [
      'code' => 'XYZ',
      'type' => 'example',
      'balance' => new Price('50.00', 'USD'),
      'status' => TRUE,
      'stores' => [$store2->id()],
    ]);
    $giftcard3->save();
    $this->getSession()->getPage()->fillField('Giftcard code', $giftcard3->getCode());
    $this->getSession()->getPage()->pressButton('Apply gift card');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->saveHtmlOutput();
    $this->assertSession()->pageTextContains('The provided gift card can not be used for this store.');
    $this->assertSession()->fieldExists('Giftcard code');

    // A disabled giftcard.
    $giftcard4 = $this->createEntity('commerce_giftcard', [
      'code' => 'CBA',
      'type' => 'example',
      'balance' => new Price('50.00', 'USD'),
      'status' => FALSE,
    ]);
    $giftcard4->save();
    $this->getSession()->getPage()->fillField('Giftcard code', $giftcard4->getCode());
    $this->getSession()->getPage()->pressButton('Apply gift card');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->pageTextContains('The provided gift card code is invalid.');
    $this->assertSession()->fieldExists('Giftcard code');

    // A CHF giftcard.
    $giftcard5 = $this->createEntity('commerce_giftcard', [
      'code' => 'CH',
      'type' => 'example',
      'balance' => new Price('50.00', 'CHF'),
    ]);
    $giftcard5->save();
    $this->getSession()->getPage()->fillField('Giftcard code', $giftcard5->getCode());
    $this->getSession()->getPage()->pressButton('Apply gift card');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->pageTextContains('The order currency (USD) does not match the giftcard currency (CHF).');
    $this->assertSession()->fieldExists('Giftcard code');

    // Confirm that the order summary is refreshed when outside of the sidebar.
    $checkout_flow = CheckoutFlow::load('default');
    $configuration = $checkout_flow->get('configuration');
    $configuration['panes']['order_summary']['step'] = 'order_information';
    $checkout_flow->set('configuration', $configuration);
    $checkout_flow->save();

    // Assign the giftcard to the first store to test a matching store.
    $this->giftcard->setStores([$this->store]);
    $this->giftcard->save();

    $this->drupalGet($checkout_url);
    $this->getSession()->getPage()->fillField('Giftcard code', $this->giftcard->getCode());
    $this->getSession()->getPage()->pressButton('Apply gift card');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->pageTextContains($this->giftcard->getCode());
    $this->assertSession()->fieldNotExists('Giftcard code');
    $this->assertSession()->buttonNotExists('Apply gift card');
    $this->assertSession()->elementContains('css', '.order-total-line__adjustment--commerce-giftcard .order-total-line-label', 'Giftcard');
    $this->assertSession()->elementContains('css', '.order-total-line__adjustment--commerce-giftcard .order-total-line-value', '-$100.00');
  }

  /**
   * Tests redeeming gift card on the cart form, with multiple gift cards allowed.
   */
  public function testMultipleGiftcardRedemption() {
    $config = \Drupal::configFactory()->getEditable('commerce_checkout.commerce_checkout_flow.default');
    $config->set('configuration.panes.commerce_giftcard_redemption.allow_multiple', TRUE);
    $config->save();

    $this->drupalGet(Url::fromRoute('commerce_checkout.form', ['commerce_order' => $this->cart->id()]));
    $this->getSession()->getPage()->fillField('Giftcard code', $this->giftcard->getCode());
    $this->getSession()->getPage()->pressButton('Apply gift card');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->pageTextContains($this->giftcard->getCode());
    $this->assertSession()->fieldExists('Giftcard code');
    $this->assertSession()->elementContains('css', '.order-total-line__adjustment--commerce-giftcard .order-total-line-label', 'Giftcard');
    $this->assertSession()->elementContains('css', '.order-total-line__adjustment--commerce-giftcard .order-total-line-value', '-$100.00');
    $this->assertSession()->elementContains('css', '.order-total-line__total', '$899.00');

    // Create and use a second gift card.
    $giftcard2 = $this->createEntity('commerce_giftcard', [
      'code' => 'DEF',
      'type' => 'example',
      'balance' => new Price('150.00', 'USD'),
      'status' => TRUE,
    ]);
    $giftcard2->save();

    $this->getSession()->getPage()->fillField('Giftcard code', $giftcard2->getCode());
    $this->getSession()->getPage()->pressButton('Apply gift card');
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Both gift cards are applied now to the total, the nth index includes
    // all order line items, so the gift cards are 3 and 4.
    $this->assertSession()->elementContains('css', '.order-total-line__adjustment--commerce-giftcard:nth-of-type(3) .order-total-line-label', 'Giftcard');
    $this->assertSession()->elementContains('css', '.order-total-line__adjustment--commerce-giftcard:nth-of-type(3) .order-total-line-value', '-$100.00');
    $this->assertSession()->elementContains('css', '.order-total-line__adjustment--commerce-giftcard:nth-of-type(4) .order-total-line-label', 'Giftcard');
    $this->assertSession()->elementContains('css', '.order-total-line__adjustment--commerce-giftcard:nth-of-type(4) .order-total-line-value', '-$150.00');
    $this->assertSession()->elementContains('css', '.order-total-line__total', '$749.00');

    $this->assertSession()->pageTextContains($giftcard2->getCode());

    // Remove the first gift card, asser the updated total.
    $this->getSession()->getPage()->pressButton('Remove gift card');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->elementContains('css', '.order-total-line__adjustment--commerce-giftcard .order-total-line-value', '-$150.00');
    $this->assertSession()->elementContains('css', '.order-total-line__total', '$849.00');
  }

  /**
   * Tests checkout partially paid by a gift card.
   */
  public function testCheckoutPartial() {
    $this->drupalGet(Url::fromRoute('commerce_checkout.form', ['commerce_order' => $this->cart->id()]));

    $this->getSession()->getPage()->fillField('Giftcard code', $this->giftcard->getCode());
    $this->getSession()->getPage()->pressButton('Apply gift card');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->pageTextContains($this->giftcard->getCode());
    $this->assertSession()->elementContains('css', '.order-total-line__adjustment--commerce-giftcard .order-total-line-value', '-$100.00');
    $this->assertSession()->elementContains('css', '.order-total-line__total', '$899.00');

    // Ensure that the payment method ajax works with the gift card ajax.
    $radio_button = $this->getSession()->getPage()->findField('Visa ending in 9999');
    $radio_button->click();
    $this->assertSession()->assertWaitOnAjaxRequest();

    $this->submitForm([], 'Continue to review');
    $this->assertSession()->pageTextContains('Visa ending in 9999');
    $this->assertSession()->pageTextContains($this->giftcard->getCode());
    $this->assertSession()->elementContains('css', '.order-total-line__adjustment--commerce-giftcard .order-total-line-value', '-$100.00');
    $this->assertSession()->elementContains('css', '.order-total-line__total', '$899.00');

    $this->getSession()->getPage()->pressButton('Pay and complete purchase');
    $this->assertSession()->pageTextContains('Your order number is 1. You can view your order on your account page when logged in.');

    $order_storage = $this->container->get('entity_type.manager')->getStorage('commerce_order');
    $order_storage->resetCache([$this->cart->id()]);
    $this->cart = $order_storage->load($this->cart->id());
    $this->assertEqual($this->giftcard->id(), $this->cart->get('commerce_giftcards')->target_id);
    $this->assertEquals(new Price('899.00', 'USD'), $this->cart->getTotalPrice());
    $this->assertEquals(new Price('899.00', 'USD'), $this->cart->getTotalPaid());
    $this->assertCount(1, Payment::loadMultiple());

    // Assert the updated gift card and created transaction.
    $this->giftcard = $this->reloadEntity($this->giftcard);
    $this->assertEquals(new Price('0.00', 'USD'), $this->giftcard->getBalance());
    $transactions = \Drupal::entityTypeManager()->getStorage('commerce_giftcard_transaction')->loadByProperties(['giftcard' => $this->giftcard->id()]);
    $this->assertCount(1, $transactions);
    /** @var \Drupal\commerce_giftcard\Entity\GiftcardTransactionInterface $transaction */
    $transaction = reset($transactions);
    $this->assertEquals(new Price('-100.00', 'USD'), $transaction->getAmount());
  }

  /**
   * Tests checkout fully paid by gift card.
   */
  public function testCheckoutOnlyGiftcard() {
    $this->drupalGet(Url::fromRoute('commerce_checkout.form', ['commerce_order' => $this->cart->id()]));

    $this->giftcard->setBalance(new Price(5000, 'USD'));
    $this->giftcard->save();

    $this->getSession()->getPage()->fillField('Giftcard code', $this->giftcard->getCode());
    $this->getSession()->getPage()->pressButton('Apply gift card');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->pageTextContains($this->giftcard->getCode());
    $this->assertSession()->elementContains('css', '.order-total-line__adjustment--commerce-giftcard .order-total-line-value', '-$999.00');
    $this->assertSession()->elementContains('css', '.order-total-line__total', '$0.00');

    // Payment options are no longer shown, profile fields need to be filled
    // out.
    $this->assertSession()->pageTextNotContains('Visa');
    $this->getSession()->getPage()->fillField('First name', 'Frederick');
    $this->getSession()->getPage()->fillField('Last name', 'Pabst');
    $this->getSession()->getPage()->fillField('Street address', 'Pabst Blue Ribbon Dr');
    $this->getSession()->getPage()->fillField('City', 'Milwaukee');
    $this->getSession()->getPage()->fillField('Zip code', '53177');
    $this->getSession()->getPage()->selectFieldOption('State', 'WI');

    $this->submitForm([], 'Continue to review');
    $this->assertSession()->pageTextNotContains('Visa');
    $this->assertSession()->pageTextContains($this->giftcard->getCode());
    $this->assertSession()->elementContains('css', '.order-total-line__adjustment--commerce-giftcard .order-total-line-value', '-$999.00');
    $this->assertSession()->elementContains('css', '.order-total-line__total', '$0.00');

    $this->getSession()->getPage()->pressButton('Complete checkout');
    $this->assertSession()->pageTextContains('Your order number is 1. You can view your order on your account page when logged in.');

    $order_storage = $this->container->get('entity_type.manager')->getStorage('commerce_order');
    $order_storage->resetCache([$this->cart->id()]);
    $this->cart = $order_storage->load($this->cart->id());
    $this->assertEqual($this->giftcard->id(), $this->cart->get('commerce_giftcards')->target_id);
    $this->assertEquals(new Price('0.00', 'USD'), $this->cart->getTotalPrice());
    $this->assertEquals(new Price('0.00', 'USD'), $this->cart->getTotalPaid());
    $this->assertCount(0, Payment::loadMultiple());

    // Assert the updated gift card and created transaction.
    $this->giftcard = $this->reloadEntity($this->giftcard);
    $this->assertEquals(new Price('4001.00', 'USD'), $this->giftcard->getBalance());
    $transactions = \Drupal::entityTypeManager()->getStorage('commerce_giftcard_transaction')->loadByProperties(['giftcard' => $this->giftcard->id()]);
    $this->assertCount(1, $transactions);
    /** @var \Drupal\commerce_giftcard\Entity\GiftcardTransactionInterface $transaction */
    $transaction = reset($transactions);
    $this->assertEquals(new Price('-999.00', 'USD'), $transaction->getAmount());
  }

  /**
   * Tests checkout using the main submit button instead of 'Apply gift card'.
   */
  public function testCheckoutWithMainSubmit() {
    $this->drupalGet(Url::fromRoute('commerce_checkout.form', ['commerce_order' => $this->cart->id()]));

    $this->getSession()->getPage()->fillField('Giftcard code', $this->giftcard->getCode());
    $this->submitForm([], 'Continue to review');
    $this->assertSession()->pageTextContains('Visa ending in 9999');
    $this->assertSession()->pageTextContains($this->giftcard->getCode());
    $this->assertSession()->elementContains('css', '.order-total-line__adjustment--commerce-giftcard .order-total-line-value', '-$100.00');
    $this->assertSession()->elementContains('css', '.order-total-line__total', '$899.00');

    $this->submitForm([], 'Pay and complete purchase');
    $this->assertSession()->pageTextContains('Your order number is 1. You can view your order on your account page when logged in.');

    $order_storage = $this->container->get('entity_type.manager')->getStorage('commerce_order');
    $order_storage->resetCache([$this->cart->id()]);
    $this->cart = $order_storage->load($this->cart->id());
    $this->assertEquals(new Price('899.00', 'USD'), $this->cart->getTotalPrice());
  }

  /**
   * Tests that adding/removing gift cards does not submit other panes.
   */
  public function testCheckoutSubmit() {
    // Start checkout, and enter billing information.
    $this->drupalGet(Url::fromRoute('commerce_checkout.form', ['commerce_order' => $this->cart->id()]));

    $this->getSession()->getPage()->findField('Example')->click();
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->submitForm([
      'payment_information[billing_information][address][0][address][given_name]' => 'Johnny',
      'payment_information[billing_information][address][0][address][family_name]' => 'Appleseed',
      'payment_information[billing_information][address][0][address][address_line1]' => '123 New York Drive',
      'payment_information[billing_information][address][0][address][locality]' => 'New York City',
      'payment_information[billing_information][address][0][address][administrative_area]' => 'NY',
      'payment_information[billing_information][address][0][address][postal_code]' => '10001',
    ], 'Continue to review');

    // Go back and edit the billing information, but don't submit it.
    $this->getSession()->getPage()->clickLink('Go back');
    $this->getSession()->getPage()->pressButton('billing_edit');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $address_prefix = 'payment_information[billing_information][address][0][address]';
    $this->getSession()->getPage()->fillField($address_prefix . '[given_name]', 'John');
    $this->getSession()->getPage()->fillField($address_prefix . '[family_name]', 'Smith');

    // Add a gift card.
    $page = $this->getSession()->getPage();
    $page->fillField('Giftcard code', $this->giftcard->getCode());
    $page->pressButton('Apply gift card');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->pageTextContains($this->giftcard->getCode());
    $this->assertSession()->fieldNotExists('Giftcard code');
    $this->assertSession()->buttonNotExists('Apply gift card');

    // Refresh the page and ensure the billing information hasn't been modified.
    $this->drupalGet(Url::fromRoute('commerce_checkout.form', ['commerce_order' => $this->cart->id(), 'step' => 'order_information']));
    $page = $this->getSession()->getPage();
    $this->assertStringContainsString('Johnny Appleseed', $page->find('css', 'p.address')->getText());
  }

}
