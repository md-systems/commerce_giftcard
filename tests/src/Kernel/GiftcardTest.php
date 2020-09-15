<?php

namespace Drupal\Tests\Kernel\commerce_giftcard;

use Drupal\commerce_giftcard\Entity\Giftcard;
use Drupal\commerce_giftcard\Entity\GiftcardType;
use Drupal\commerce_price\Price;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Tests basic giftcard functionality.
 */
class GiftcardTest extends CommerceKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['commerce_giftcard'];

  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('commerce_giftcard');
  }

  /**
   * Tests creating giftcards.
   */
  public function testCrud() {
    $giftcard_type = GiftcardType::create([
      'id' => 'example',
      'label' => 'Example',
    ]);
    $giftcard_type->save();

    /** @var \Drupal\commerce_giftcard\Entity\GiftcardInterface $giftcard */
    $giftcard = Giftcard::create([
      'type' => 'example',
    ]);

    $violations = $giftcard->validate();
    $this->assertEquals(2, count($violations));
    $this->assertEquals('code', $violations[0]->getPropertyPath());
    $this->assertEquals('This value should not be null.', $violations[0]->getMessage());

    $this->assertEquals('balance', $violations[1]->getPropertyPath());
    $this->assertEquals('This value should not be null.', $violations[1]->getMessage());

    $giftcard->setCode('ABC');
    $giftcard->setBalance(new Price('50', 'USD'));

    $violations = $giftcard->validate();
    $this->assertEquals(0, count($violations));
    $giftcard->save();

    // Create a second giftcard with the same code.
    $giftcard = Giftcard::create([
      'type' => 'example',
      'code' => 'ABC',
    ]);
    $giftcard->setBalance(new Price('50', 'USD'));

    $violations = $giftcard->validate();
    $this->assertEquals(1, count($violations));
    $this->assertEquals('code', $violations[0]->getPropertyPath());
    $this->assertEquals('The gift card code <em class="placeholder">ABC</em> is already in use and must be unique.', $violations[0]->getMessage());

    $giftcard->setCode('DEF');
    $violations = $giftcard->validate();
    $this->assertEquals(0, count($violations));
    $giftcard->save();
  }

}
