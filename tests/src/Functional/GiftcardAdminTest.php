<?php

namespace Drupal\Tests\Functional\commerce_giftcard;

use Drupal\commerce_giftcard\Entity\Giftcard;
use Drupal\commerce_giftcard\Entity\GiftcardInterface;
use Drupal\commerce_giftcard\Entity\GiftcardType;
use Drupal\commerce_price\Price;
use Drupal\Tests\commerce\Functional\CommerceBrowserTestBase;
use Drupal\Tests\UiHelperTrait;

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
  public static $modules = ['commerce_giftcard'];

  /**
   * {@inheritdoc}
   */
  protected function getAdministratorPermissions() {
    return array_merge(parent::getAdministratorPermissions(), [
      'administer commerce_giftcard',
      'administer commerce_giftcard_type',
    ]);
  }

  /**
   * Tests creating a giftcard with a transaction in the UI.
   */
  public function testCreateGiftcardAndTransaction() {

    $giftcard_user = $this->createUser();

    $this->drupalGet('admin/commerce/giftcards');
    $this->assertSession()->pageTextContains('There are no Gift cards yet.');

    $this->clickLink('Add gift card');
    $this->assertSession()->pageTextContains('There is no gift card type yet.');

    $this->clickLink('Add a new gift card type.');
    $page = $this->getSession()->getPage();
    $page->fillField('Label', 'Example');
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

    $this->drupalGet('admin/commerce/giftcards');
    $this->clickLink('Generate gift cards');

    $page = $this->getSession()->getPage();
    $page->selectFieldOption('Gift card type', 'example');
    $page->fillField('Initial balance', '50.25');
    $page->fillField('Number of gift cards', '32');
    $page->pressButton('Generate');
    $this->checkForMetaRefresh();
    $this->assertSession()->pageTextContains('Generated 32 gift cards.');

    $giftcards = Giftcard::loadMultiple();
    $this->assertCount(32, $giftcards);
    foreach ($giftcards as $giftcard) {
      $this->assertEquals('example', $giftcard->bundle());
      $this->assertEquals(new Price(50.25, 'USD'), $giftcard->getBalance());
      $this->assertRegExp('/^[0-9a-zA-Z]{9}$/', $giftcard->getCode());
      $this->assertNull($giftcard->getOwnerId());
    }
  }

}
