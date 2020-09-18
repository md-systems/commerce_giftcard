<?php

namespace Drupal\commerce_giftcard;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Defines the user schema handler.
 */
class GiftcardStorageSchema extends SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   */
  protected function getEntitySchema(ContentEntityTypeInterface $entity_type, $reset = FALSE) {
    $schema = parent::getEntitySchema($entity_type, $reset);

    if ($base_table = $this->storage->getBaseTable()) {
      // Add a unique key on the code.
      $schema[$base_table]['unique keys'] += [
        'code' => ['code'],
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
