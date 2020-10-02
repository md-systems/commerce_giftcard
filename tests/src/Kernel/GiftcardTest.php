<?php

namespace Drupal\Tests\Kernel\commerce_giftcard;

use Drupal\commerce_giftcard\Entity\Giftcard;
use Drupal\commerce_giftcard\Entity\GiftcardTransaction;
use Drupal\commerce_giftcard\Entity\GiftcardType;
use Drupal\commerce_price\Price;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Tests basic giftcard functionality.
 *
 * @group commerce_giftcard
 */
class GiftcardTest extends CommerceKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['commerce_giftcard'];

  /**
   * @var \Drupal\commerce_giftcard\Entity\GiftcardTypeInterface
   */
  protected $giftcardType;

  protected function setUp() {
    parent::setUp();

    $currency_importer = $this->container->get('commerce_price.currency_importer');
    $currency_importer->import('CHF');

    $this->installEntitySchema('commerce_giftcard');
    $this->installEntitySchema('commerce_giftcard_transaction');

    $this->giftcardType = GiftcardType::create([
      'id' => 'example',
      'label' => 'Example',
    ]);
    $this->giftcardType->save();
  }

  /**
   * {@inheritdoc}
   */
  protected function installConfig($modules) {
    // The parent class installs the commerce_store config by default,
    // that ends up also installing the giftcard admin view which uses a
    // currency formatter that in turn requires the default country of the site
    // to be set, so import the system.module configuration first.
    if ($modules == ['commerce_store']) {
      parent::installConfig(['system']);
    }
    parent::installConfig($modules);
  }

  /**
   * Tests creating giftcards.
   */
  public function testSaving() {
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

    $this->assertNull($giftcard->getOwnerId());

    // Create a second giftcard with the same code.
    $giftcard2 = Giftcard::create([
      'type' => 'example',
      'code' => 'ABC',
    ]);
    $giftcard2->setBalance(new Price('50', 'USD'));

    $violations = $giftcard2->validate();
    $this->assertEquals(1, count($violations));
    $this->assertEquals('code', $violations[0]->getPropertyPath());
    $this->assertEquals('The gift card code <em class="placeholder">ABC</em> is already in use and must be unique.', $violations[0]->getMessage());

    $giftcard2->setCode('DEF');
    $violations = $giftcard2->validate();
    $this->assertEquals(0, count($violations));
    $giftcard2->save();
  }

  /**
   * Tests creating giftcard transactions.
   */
  public function testTransactions() {
    /** @var \Drupal\commerce_giftcard\Entity\GiftcardInterface $giftcard */
    $giftcard = Giftcard::create([
      'type' => 'example',
      'code' => 'ABC',
      'balance' => new Price(50, 'USD'),
    ]);
    $giftcard->save();

    $transaction1 = GiftcardTransaction::create([
      'giftcard' => $giftcard,
      'amount' => new Price(-10, 'USD')
    ]);
    $transaction1->save();

    $this->assertEquals(40, $giftcard->getBalance()->getNumber());

    $transaction2 = GiftcardTransaction::create([
      'giftcard' => $giftcard,
      'amount' => new Price(-30, 'USD')
    ]);
    $transaction2->save();

    $this->assertEquals(10, $giftcard->getBalance()->getNumber());

    $transaction3 = GiftcardTransaction::create([
      'giftcard' => $giftcard,
      'amount' => new Price('15.50', 'USD')
    ]);
    $transaction3->save();

    $this->assertEquals('25.50', $giftcard->getBalance()->getNumber());

    $transaction3 = GiftcardTransaction::create([
      'giftcard' => $giftcard,
      'amount' => new Price('-25.50', 'USD')
    ]);
    $transaction3->save();

    $this->assertTrue($giftcard->getBalance()->isZero());
  }

  /**
   * Tests a transaction that would result in negative balance.
   */
  public function testTransactionsNegative() {
    /** @var \Drupal\commerce_giftcard\Entity\GiftcardInterface $giftcard */
    $giftcard = Giftcard::create([
      'type' => 'example',
      'code' => 'ABC',
      'balance' => new Price(50, 'USD'),
    ]);
    $giftcard->save();

    $this->expectException(EntityStorageException::class);
    $this->expectExceptionMessage('Giftcard balance must not be negative');

    $transaction1 = GiftcardTransaction::create([
      'giftcard' => $giftcard,
      'amount' => new Price(-60, 'USD')
    ]);
    $violations = $transaction1->validate();
    $this->assertEquals(1, count($violations));
    $this->assertEquals('amount.0.number', $violations[0]->getPropertyPath());
    $this->assertEquals('The transaction amount must not result in a negative giftcard balance.', $violations[0]->getMessage());

    $transaction1->save();
  }

  /**
   * Tests a transaction with a wrong currency.
   */
  public function testTransactionWrongCurrency() {
    /** @var \Drupal\commerce_giftcard\Entity\GiftcardInterface $giftcard */
    $giftcard = Giftcard::create([
      'type' => 'example',
      'code' => 'ABC',
      'balance' => new Price(50, 'USD'),
    ]);
    $giftcard->save();

    $this->expectException(EntityStorageException::class);
    $this->expectExceptionMessage('The provided prices have mismatched currencies: 50 USD, -50 CHF.');

    $transaction1 = GiftcardTransaction::create([
      'giftcard' => $giftcard,
      'amount' => new Price(-50, 'CHF')
    ]);
    $violations = $transaction1->validate();
    $this->assertEquals(1, count($violations));
    $this->assertEquals('amount.0.currency_code', $violations[0]->getPropertyPath());
    $this->assertEquals('The transaction amount currency (<em class="placeholder">CHF</em>) does not match the giftcard currency (<em class="placeholder">USD</em>).', $violations[0]->getMessage());

    $transaction1->save();
  }

  /**
   * Ensure tha giftcards have a unique index on the code.
   */
  public function testUniqueIndex() {

    $this->expectException(EntityStorageException::class);

    /** @var \Drupal\commerce_giftcard\Entity\GiftcardInterface $giftcard */
    $giftcard = Giftcard::create(
      [
        'type' => 'example',
        'code' => 'ABC',
        'balance' => new Price(50, 'USD'),
      ]
    );
    $giftcard->save();
    /** @var \Drupal\commerce_giftcard\Entity\GiftcardInterface $giftcard */
    $giftcard = Giftcard::create(
      [
        'type' => 'example',
        'code' => 'ABC',
        'balance' => new Price(50, 'USD'),
      ]
    );
    $giftcard->save();
  }

  /**
   * Tests creating giftcard transactions.
   */
  public function testGenerate() {
    $this->assertEquals(8, $this->giftcardType->getGenerateSetting('length'));

    /** @var \Drupal\commerce_giftcard\GiftcardCodeGenerator $code_generator */
    $code_generator = \Drupal::service('commerce_giftcard.code_generator');

    $codes = $code_generator->generateCodes($this->giftcardType, 10);
    $this->assertCount(10, $codes);
    foreach ($codes as $code) {
      $this->assertRegExp('/[0-9a-zA-Z]{8}/', $code);
    }

    $this->giftcardType->setGenerateSetting('length', 4);

    $codes = $code_generator->generateCodes($this->giftcardType, 5);
    $this->assertCount(5, $codes);
    foreach ($codes as $code) {
      $this->assertRegExp('/[0-9a-zA-Z]{4}$/', $code);
    }
  }

}
