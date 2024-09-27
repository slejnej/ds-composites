<?php

namespace Drupal\remora_core\Service\ReferenceableEntity;

use Drupal;
use Drupal\remora_core\Enum\EventName;
use Drupal\remora_core\Event\ReferenceableEntityEvent;
use MantaRayMedia\CacheService\CacheService;

/**
 * This interface describes the behaviour for ReferenceableEntityServices.
 * All projects wishing to override `Drupal::service('remora_core.service.referenceable_content_type')` need to implement this Interface
 */
interface ReferenceableEntityServiceInterface
{

  /**
   * Returns all Content Types that can be referenced in cards and the like
   * Fires an event so modules can unregister their CT as they see fit
   * Results are cached after initial generation
   *
   * @return string[] An array of machine names that can be reference, the key being the machine name and the value being the Label
   */
  public function getAllReferenceableEntityTypes(): array;

  /**
   * Set the event name so we can differentiate between main content and sidebar paragraph
   *
   * @param EventName $eventName
   * @return $this
   */
  public function setEventName(EventName $eventName): self;
}
