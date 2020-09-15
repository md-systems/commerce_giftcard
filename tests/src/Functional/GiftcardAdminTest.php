<?php

namespace Drupal\Tests\Functional\commerce_giftcard;

use Drupal\commerce_giftcard\Entity\GiftcardInterface;
use Drupal\Tests\commerce\Functional\CommerceBrowserTestBase;

class GiftcardAdminTest extends CommerceBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['commerce_giftcard'];

  /**
   * {@inheritdoc}
   */
  protected function getAdministratorPermissions() {
    return array_merge(parent::getAdministratorPermissions(), [
      'administer commerce_gift_card',
      'administer commerce_gift_card_type',
    ]);
  }

  /**
   * Tests creating a giftcard in the UI.
   */
  public function testCreateGiftcard() {

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

    $this->drupalLogout();
    $this->drupalGet('admin/commerce/giftcards');
    $this->assertSession()->statusCodeEquals(403);
  }

}
