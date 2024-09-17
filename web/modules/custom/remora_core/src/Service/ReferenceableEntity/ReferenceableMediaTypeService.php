<?php

namespace Drupal\remora_core\Service\ReferenceableEntity;

use Drupal;
use Drupal\remora_core\Enum\EventName;
use Drupal\remora_core\Event\ReferenceableEntityEvent;
use MantaRayMedia\CacheService\CacheService;

class ReferenceableMediaTypeService implements ReferenceableEntityServiceInterface
{
  protected EventName $eventName;

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
    return CacheService::get('remora_referenceable_media_types_' . $this->eventName->value, function() {
      $pts = [];

      if ($this->eventName === EventName::REFERENCEABLE_MEDIA_TYPE_IMAGE_INDEX || $this->eventName === EventName::REFERENCEABLE_MEDIA_TYPE_IMAGE_VIDEO_INDEX) {
        $pts['image'] = 'image';
      }

      if ($this->eventName === EventName::REFERENCEABLE_MEDIA_TYPE_VIDEO_INDEX || $this->eventName === EventName::REFERENCEABLE_MEDIA_TYPE_IMAGE_VIDEO_INDEX) {
        $pts['remote_video'] = 'remote_video';
      }

      $event = new ReferenceableEntityEvent($pts);
      Drupal::service('event_dispatcher')->dispatch($event, $this->eventName->value);

      return $event->getContentTypes();
    });
  }

}
