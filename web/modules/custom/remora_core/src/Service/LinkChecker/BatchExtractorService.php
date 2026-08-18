<?php

namespace Drupal\remora_core\Service\LinkChecker;

use Drupal\linkchecker\LinkExtractorBatch;

/**
 * Overwrites the LinkExtractorBatch service to always add nodes.
 */
class BatchExtractorService extends LinkExtractorBatch
{

  /**
   * {@inheritDoc}
   */
  public function getEntityTypesToProcess(): array
  {
    $fieldConfigs = $this->entityTypeManager
      ->getStorage('field_config')
      ->loadMultiple(NULL);
    $entityTypes = [];

    /** @var \Drupal\Core\Field\FieldConfigInterface $config */
    foreach ($fieldConfigs as $config) {
      $scan = $config->getThirdPartySetting('linkchecker', 'scan', FALSE);
      $entityTypeId = $config->getTargetEntityTypeId();

      // always add nodes, otherwise use the scan setting
      if ($scan || $entityTypeId === 'node') {
        $bundle = $config->getTargetBundle();
        if (!isset($entityTypes[$entityTypeId . '-' . $bundle])) {
          $entityType = $this->entityTypeManager->getDefinition($entityTypeId);
          $entityTypes[$entityTypeId . '-' . $bundle] = [
            'entity_type' => $entityType,
            'bundle' => $bundle,
          ];
        }
      }
    }

    return $entityTypes;
  }

}
