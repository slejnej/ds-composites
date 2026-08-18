<?php

namespace Drupal\remora_core\Service\ReferenceableEntity;

use Drupal;
use Drupal\paragraphs\Entity\ParagraphsType;
use Drupal\remora_core\Enum\EventName;
use Drupal\remora_core\Event\ReferenceableEntityEvent;
use MantaRayMedia\CacheService\CacheService;

class ReferenceableParagraphTypeService implements ReferenceableEntityServiceInterface
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
    return CacheService::get('remora_referenceable_paragraph_types_' . $this->eventName->value, function() {
      $pts = $this->getAllParagraphTypes();

      $event = new ReferenceableEntityEvent($pts);
      Drupal::service('event_dispatcher')->dispatch($event, $this->eventName->value);

      return $event->getContentTypes();
    });
  }

  /**
   * Returns a list of Paragraph Types that are known to the system
   *
   * @return string[] An array of Content Types, the key being the machine name and the value being the Label
   */
  private function getAllParagraphTypes(): array
  {
    $types = [];
    $paragraphTypes = ParagraphsType::loadMultiple();

    foreach($paragraphTypes as $paragraphType) {
      $types[$paragraphType->id()] = $paragraphType->id();
    }

    return $types;
  }

}
