<?php

namespace Drupal\commerce_giftcard;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * The giftcard transaction storage schema.
 */
class GiftcardTransactionStorageSchema extends SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   */
  protected function getEntitySchema(ContentEntityTypeInterface $entity_type, $reset = FALSE) {
    $schema = parent::getEntitySchema($entity_type, $reset);

    if ($base_table = $this->storage->getBaseTable()) {
      $schema[$base_table]['indexes'] += [
        'giftcard' => ['giftcard'],
        'reference_entity' => ['reference_type', 'reference_id'],
      ];
    }

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  protected function getSharedTableFieldSchema(FieldStorageDefinitionInterface $storage_definition, $table_name, array $column_mapping) {
    $schema = parent::getSharedTableFieldSchema($storage_definition, $table_name, $column_mapping);
    $field_name = $storage_definition->getName();

    if ($table_name == $this->storage->getBaseTable()) {
      switch ($field_name) {
        case 'uid':
          // Owner can be null.
          $schema['fields'][$field_name]['not null'] = FALSE;
      }
    }

    return $schema;
  }

}
