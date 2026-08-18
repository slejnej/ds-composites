<?php

namespace Drupal\webform_pod\EventSubscriber;

use Drupal\remora_core\Enum\EventName;
use Drupal\remora_core\Event\ReferenceableEntityEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ReferenceableEntitySubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array
  {
    return [
      // Static class constant => method on this class.
      EventName::REFERENCEABLE_PARAGRAPH_TYPE_FOOTER_INDEX->value => 'removeWebform',
      EventName::REFERENCEABLE_PARAGRAPH_TYPE_HERO_CONTENT_INDEX->value => 'removeWebform'    ];
  }

  /**
   * Remove webform from referenceable paragraph field.
   *
   * @param ReferenceableEntityEvent $event
   */
  public function removeWebform(ReferenceableEntityEvent $event): void
  {
    $event->remove('webform_pod');
  }
}
