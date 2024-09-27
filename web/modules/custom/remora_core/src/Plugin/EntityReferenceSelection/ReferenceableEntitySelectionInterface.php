<?php

namespace Drupal\remora_core\Plugin\EntityReferenceSelection;

use Drupal\remora_core\Service\ReferenceableEntity\ReferenceableEntityServiceInterface;

interface ReferenceableEntitySelectionInterface
{

  /**
   * Returns the ReferenceableEntityServiceInterface, listing which entities are referenceable
   *
   * @return ReferenceableEntityServiceInterface
   */
  public function getReferenceableEntityService(): ReferenceableEntityServiceInterface;

}
