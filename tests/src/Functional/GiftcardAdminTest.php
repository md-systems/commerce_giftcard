<?php

namespace Drupal\Tests\Functional\commerce_giftcard;

use Drupal\commerce_giftcard\Entity\Giftcard;
use Drupal\commerce_giftcard\Entity\GiftcardInterface;
use Drupal\commerce_giftcard\Entity\GiftcardType;
use Drupal\commerce_price\Price;
use Drupal\Tests\commerce\Functional\CommerceBrowserTestBase;
use Drupal\Tests\UiHelperTrait;
use Drupal\user\Entity\Role;

/**
 * Test the admin UI for giftcards.
 *
 * @group commerce_giftcard
 */
class GiftcardAdminTest extends CommerceBrowserTestBase {

  use UiHelperTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['commerce_giftcard', 'commerce_order', 'commerce_product'];

  /**
   * {@inheritdoc}
   */
  protected function getAdministratorPermissions() {
    return array_merge(parent::getAdministratorPermissions(), [
      'administer commerce_giftcard',
      'administer commerce_giftcard_type',
      'administer commerce_order',
      'administer commerce_order_type',
      'access commerce_order overview',
      'access giftcard overview',
    ]);
  }

  /**
   * Tests creating a giftcard with a transaction in the UI.
   */
  public function testCreateGiftcardAndTransaction() {

    $giftcard_user = $this->createUser();

    $this->drupalGet('admin/commerce/giftcards');
    $this->assertSession()->pageTextContains('There are no gift cards yet.');

    $this->clickLink('Add gift card');
    $this->assertSession()->pageTextContains('There is no gift card type yet.');

    $this->clickLink('Add a new gift card type.');
    $page = $this->getSession()->getPage();
    $page->fillField('Label', 'Example');
    $page->fillField('Display label', 'Giftcard [commerce_giftcard:code:value]');
    $page->fillField('id', 'example');
    $page->pressButton('Save');
    $this->assertSession()->pageTextContains('The gift card type Example has been added.');

    $this->drupalGet('admin/commerce/giftcards');
    $this->clickLink('Add gift card');
    $page->fillField('Code', 'ABC');
    $page->fillField('Balance', '50.25');
    $page->fillField('Owner', $giftcard_user->getAccountName());
    $page->pressButton('Save');
    $this->assertSession()->pageTextContains('New gift card ABC has been created.');
    $this->assertSession()->pageTextContains('$50.25');
    $this->assertSession()->pageTextContains($giftcard_user->getDisplayName());

    $entity_list = \Drupal::entityTypeManager()->getStorage('commerce_giftcard')->loadByProperties(['code' => 'ABC']);
    $this->assertCount(1, $entity_list);
    /** @var \Drupal\commerce_giftcard\Entity\GiftcardInterface $giftcard */
    $giftcard = array_shift($entity_list);
    $this->assertInstanceOf(GiftcardInterface::class, $giftcard);

    // Add a transaction.
    $this->clickLink('Add transaction');
    $page->fillField('Amount', '-12.75');
    $page->pressButton('Save');
    $this->saveHtmlOutput();
    $this->assertSession()->pageTextContains('-$12.75 transaction for giftcard ' . $giftcard->getCode() .  ' has been created, new balance: $37.50.');

    // Click on the 1 transaction link.
    $this->clickLink('1');
    $this->saveHtmlOutput();
    $this->assertSession()->pageTextContains('ABC');
    $this->assertSession()->pageTextContains('-$12.75');

    // Try to add a transaction with an invalid balance.
    $this->drupalGet('admin/commerce/giftcards');
    $this->clickLink('Add transaction');
    $page->fillField('Amount', '-37.51');
    $page->pressButton('Save');
    $this->saveHtmlOutput();
    $this->assertSession()->pageTextContains('The transaction amount must not result in a negative giftcard balance.');

    $entity_list = \Drupal::entityTypeManager()->getStorage('commerce_giftcard_transaction')->loadMultiple();
    $this->assertCount(1, $entity_list);

    $this->drupalLogout();
    $this->drupalGet('admin/commerce/giftcards');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests generating giftcards.
   */
  public function testGenerateGiftcard() {

    /** @var \Drupal\commerce_giftcard\Entity\GiftcardTypeInterface $giftcard_type */
    $giftcard_type = GiftcardType::create([
      'id' => 'example',
      'label' => 'Example',
    ]);
    $giftcard_type->setGenerateSetting('length', 9);
    $giftcard_type->save();

    /** @var \Drupal\commerce_giftcard\Entity\GiftcardTypeInterface $giftcard_type */
    $giftcard_type = GiftcardType::create([
      'id' => 'example2',
      'label' => 'Example 2',
    ]);
    $giftcard_type->setGenerateSetting('length', 7);
    $giftcard_type->save();

    $this->drupalGet('admin/commerce/giftcards');
    $this->clickLink('Generate gift cards');

    $page = $this->getSession()->getPage();
    $page->selectFieldOption('Gift card type', 'example');
    $page->fillField('Initial balance', '50.25');
    $page->fillField('Number of gift cards', '32');
    $page->pressButton('Generate');
    $this->checkForMetaRefresh();
    $this->assertSession()->pageTextContains('Generated 32 gift cards.');

    /** @var \Drupal\commerce_giftcard\Entity\GiftcardInterface[] $giftcards */
    $giftcards = Giftcard::loadMultiple();
    $this->assertCount(32, $giftcards);
    foreach ($giftcards as $giftcard) {
      $this->assertEquals('example', $giftcard->bundle());
      $this->assertEquals(new Price(50.25, 'USD'), $giftcard->getBalance());
      $this->assertRegExp('/^[0-9a-zA-Z]{9}$/', $giftcard->getCode());
      $this->assertNull($giftcard->getOwnerId());
      $this->assertEquals([], $giftcard->getStoreIds());
    }

    // Generate with a store selection.
    $this->drupalGet('admin/commerce/giftcards');
    $this->clickLink('Generate gift cards');

    $page = $this->getSession()->getPage();
    $page->selectFieldOption('Gift card type', 'example2');
    $page->fillField('Initial balance', '25');
    $page->fillField('Number of gift cards', '3');
    $page->checkField('stores[value][' . $this->store->id() . ']');
    $page->pressButton('Generate');
    $this->checkForMetaRefresh();
    $this->assertSession()->pageTextContains('Generated 3 gift cards.');

    /** @var \Drupal\commerce_giftcard\Entity\GiftcardInterface[] $giftcards */
    $giftcards = \Drupal::entityTypeManager()->getStorage('commerce_giftcard')->loadByProperties(['type' => 'example2']);
    $this->assertCount(3, $giftcards);
    foreach ($giftcards as $giftcard) {
      $this->assertEquals('example2', $giftcard->bundle());
      $this->assertEquals(new Price(25, 'USD'), $giftcard->getBalance());
      $this->assertRegExp('/^[0-9a-zA-Z]{7}$/', $giftcard->getCode());
      $this->assertNull($giftcard->getOwnerId());
      $this->assertEquals([$this->store->id()], $giftcard->getStoreIds());
    }
  }

  /**
   * Tests gift card refunds.
   */
  public function testRefund() {
    // Create an order with an item.
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $this->createEntity('commerce_order', [
      'type' => 'default',
      'order_number' => '1',
      'store_id' => $this->store->id(),
      'mail' => $this->loggedInUser->getEmail(),
      'state' => 'draft',
      'uid' => $this->loggedInUser,
    ]);

    $order_item = $this->createEntity('commerce_order_item', [
      'type' => 'default',
      'unit_price' => [
        'number' => '999',
        'currency_code' => 'USD',
      ],
    ]);
    $order->setItems([$order_item]);

    /** @var \Drupal\commerce_giftcard\Entity\GiftcardTypeInterface $giftcard_type */
    $giftcard_type = GiftcardType::create([
      'id' => 'example',
      'label' => 'Example',
    ]);
    $giftcard_type->save();

    /** @var \Drupal\commerce_giftcard\Entity\GiftcardInterface $giftcard */
    $giftcard = Giftcard::create([
      'type' => 'example',
      'code' => 'ABC',
      'balance' => new Price(800, 'USD'),
    ]);
    $giftcard->save();

    /** @var \Drupal\commerce_giftcard\Entity\GiftcardInterface $giftcard */
    $giftcard2 = Giftcard::create([
      'type' => 'example',
      'code' => 'DEF',
      'balance' => new Price(300, 'USD'),
    ]);
    $giftcard2->save();

    $giftcard3 = Giftcard::create([
      'type' => 'example',
      'code' => 'CH',
      'balance' => new Price(300, 'CHF'),
    ]);
    $giftcard3->save();

    $order->set('commerce_giftcards', [$giftcard, $giftcard2, $giftcard3]);
    $violations = $order->validate();
    $this->assertEquals(1, count($violations));
    $this->assertEquals('commerce_giftcards.2', $violations[0]->getPropertyPath());
    $this->assertEquals('The order currency (<em class="placeholder">USD</em>) does not match the giftcard currency (<em class="placeholder">CHF</em>).', $violations[0]->getMessage());

    $order->set('commerce_giftcards', [$giftcard, $giftcard2]);
    $violations = $order->validate();
    $this->assertEquals(0, count($violations));
    $order->save();

    // Refund is only visible after placing the order.
    $this->drupalGet($order->toUrl()->toString());
    $this->assertSession()->pageTextNotContains('Refund gift card');
    $this->assertSession()->elementContains('css', '.order-total-line__adjustment--commerce-giftcard:nth-of-type(2) .order-total-line-value', '-$800.00');
    $this->assertSession()->elementContains('css', '.order-total-line__adjustment--commerce-giftcard:nth-of-type(3) .order-total-line-value', '-$199.00');
    $this->assertSession()->elementContains('css', '.order-total-line__total .order-total-line-value', '$0.00');

    // Balance is still on the giftcard.
    $giftcard = $this->reloadEntity($giftcard);
    $this->assertEquals(new Price('800.00', 'USD'), $giftcard->getBalance());

    $page = $this->getSession()->getPage();
    $page->pressButton('Place order');
    $this->assertSession()->pageTextContains('Refund gift card');

    $giftcard = $this->reloadEntity($giftcard);
    $this->assertEquals(new Price('0.00', 'USD'), $giftcard->getBalance());

    $this->clickLink('Refund gift card');
    $this->assertSession()->optionExists('Gift card', 'ABC ($800.00)');
    $this->assertSession()->optionExists('Gift card', 'DEF ($199.00)');

    // Validate max refund amount.
    $page->fillField('Refund amount', 801);
    $page->pressButton('Refund');
    $this->assertSession()->pageTextContains('Amount must not be larger than remaining adjustment amount ($800.00)');

    $page->fillField('Refund amount', 200);
    $page->selectFieldOption('Gift card', $giftcard2->id());
    $page->pressButton('Refund');
    $this->assertSession()->pageTextContains('Amount must not be larger than remaining adjustment amount ($199.00)');

    // Refund a partial amount of giftcard DEF, the adjustment is still there
    // but updated.
    $page->fillField('Refund amount', 198);
    $page->pressButton('Refund');
    $this->assertSession()->pageTextContains('Refunded $198.00 for giftcard DEF');
    $this->assertSession()->elementContains('css', '.order-total-line__adjustment--commerce-giftcard:nth-of-type(2) .order-total-line-value', '-$800.00');
    $this->assertSession()->elementContains('css', '.order-total-line__adjustment--commerce-giftcard:nth-of-type(3) .order-total-line-value', '-$1.00');
    $this->assertSession()->elementContains('css', '.order-total-line__total .order-total-line-value', '$198.00');

    // Assert the giftcard balance and transactions.
    $giftcard2 = $this->reloadEntity($giftcard2);
    $this->assertEquals(new Price('299.00', 'USD'), $giftcard2->getBalance());

    $transactions = \Drupal::entityTypeManager()->getStorage('commerce_giftcard_transaction')->loadByProperties(['giftcard' => $giftcard2->id()]);
    ksort($transactions);
    $this->assertCount(2, $transactions);
    /** @var \Drupal\commerce_giftcard\Entity\GiftcardTransactionInterface $transaction */
    $transaction = \array_shift($transactions);
    $this->assertEquals(new Price('-199.00', 'USD'), $transaction->getAmount());
    $transaction = \array_shift($transactions);
    $this->assertEquals(new Price('198.00', 'USD'), $transaction->getAmount());

    // Refund the full amount of giftcard ABC, only one adjustment should
    // remain.
    $this->clickLink('Refund gift card');
    $page->fillField('Refund amount', 800);
    $page->pressButton('Refund');
    $this->assertSession()->pageTextContains('Refunded $800.00 for giftcard ABC');
    $this->assertSession()->elementContains('css', '.order-total-line__adjustment--commerce-giftcard .order-total-line-value', '-$1.00');
    $this->assertSession()->elementContains('css', '.order-total-line__total .order-total-line-value', '$998.00');

    $giftcard = $this->reloadEntity($giftcard);
    $this->assertEquals(new Price('800.00', 'USD'), $giftcard->getBalance());

    $this->saveHtmlOutput();
  }

  /**
   * Verifies giftcard management without admin permissions.
   */
  public function testLimitedAccess() {
    /** @var \Drupal\commerce_giftcard\Entity\GiftcardTypeInterface $giftcard_type */
    $giftcard_type = GiftcardType::create([
      'id' => 'example',
      'label' => 'Example',
    ]);
    $giftcard_type->save();

    /** @var \Drupal\commerce_giftcard\Entity\GiftcardInterface $giftcard */
    $giftcard = Giftcard::create([
      'type' => 'example',
      'code' => 'ABC',
      'balance' => new Price(100, 'USD'),
    ]);
    $giftcard->save();

    $limited_access_user = $this->drupalCreateUser(['access giftcard overview']);
    $this->drupalLogin($limited_access_user);

    $this->drupalGet('admin/commerce/giftcards');
    $this->assertSession()->statusCodeEquals(200);

    // Giftcard is visible but no links to edit, delete, create or add a
    // transaction are.
    $this->assertSession()->pageTextContains('ABC');
    $this->assertSession()->pageTextContains('$100.00');
    $this->assertSession()->pageTextNotContains('Add gift card');
    $this->assertSession()->pageTextNotContains('Add transaction');
    $this->assertSession()->pageTextNotContains('Edit');
    $this->assertSession()->pageTextNotContains('Delete');

    $roles = $limited_access_user->getRoles(TRUE);
    /** @var \Drupal\user\RoleInterface $role */
    $role = Role::load(reset($roles));
    $role->grantPermission('create giftcard');
    $role->save();

    $this->drupalGet('admin/commerce/giftcards');
    $this->assertSession()->pageTextContains('Add gift card');
    $this->assertSession()->pageTextNotContains('Add transaction');
    $this->assertSession()->pageTextNotContains('Edit');
    $this->assertSession()->pageTextNotContains('Delete');

    $role->grantPermission('create giftcard transaction');
    $role->save();
    $this->drupalGet('admin/commerce/giftcards');
    $this->assertSession()->pageTextContains('Add gift card');
    $this->assertSession()->pageTextContains('Add transaction');
    $this->assertSession()->pageTextNotContains('Edit');
    $this->assertSession()->pageTextNotContains('Delete');

    $this->clickLink('Add transaction');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->fieldValueEquals('Gift card', 'ABC (' . $giftcard->id() . ')');

    $page = $this->getSession()->getPage();
    $page->fillField('Amount', '-12.75');
    $page->pressButton('Save');
    $this->saveHtmlOutput();
    $this->assertSession()->pageTextContains('-$12.75 transaction for giftcard ' . $giftcard->getCode() .  ' has been created, new balance: $87.25.');

    $this->clickLink('Add gift card');

    $page->fillField('Code', 'DEF');
    $page->fillField('Balance', '50.25');
    $page->pressButton('Save');
    $this->assertSession()->pageTextContains('New gift card DEF has been created.');

    // View transactions.
    $this->clickLink('1');
  }

}
