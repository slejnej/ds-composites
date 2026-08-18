<?php

namespace Drupal\remora_core\Service\ReferenceableEntity;

use Drupal;
use Drupal\remora_core\Enum\EventName;
use Drupal\remora_core\Event\ReferenceableEntityEvent;
use MantaRayMedia\CacheService\CacheService;

class ReferenceableContentTypeService implements ReferenceableEntityServiceInterface
{
  protected EventName $eventName = EventName::REFERENCEABLE_CONTENT_TYPE_INDEX;

  /**
   * @param EventName $eventName
   * @return $this
   */
  public function setEventName(EventName $eventName): self
  {
    $this->eventName = $eventName;
    return $this;
  }

  /**
   * {@inheritDoc}
   */
  public function getAllReferenceableEntityTypes(): array
  {
    return CacheService::get('remora_referenceable_content_types', function() {
      $cts = $this->getAllContentTypes();

      $event = new ReferenceableEntityEvent($cts);
      Drupal::service('event_dispatcher')->dispatch($event, $this->eventName->value);

      return $event->getContentTypes();
    });
  }

  /**
   * Returns a list of Content Types that are known to the system
   *
   * @return string[] An array of Content Types, the key being the machine name and the value being the Label
   */
  private function getAllContentTypes(): array
  {
    $entityTypeManager = Drupal::service('entity_type.manager');

    $types = [];
    $contentTypes = $entityTypeManager->getStorage('node_type')->loadMultiple();
    foreach($contentTypes as $contentType) {
      $types[$contentType->id()] = $contentType->label();
    }

    return $types;
  }

}
