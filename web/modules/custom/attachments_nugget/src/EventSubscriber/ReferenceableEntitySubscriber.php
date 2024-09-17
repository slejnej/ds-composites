<?php

namespace Drupal\attachments_nugget\EventSubscriber;

use Drupal\remora_core\Enum\EventName;
use Drupal\remora_core\Event\ReferenceableEntityEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 *
 * @package Drupal\custom_events\EventSubscriber
 */
class ReferenceableEntitySubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      EventName::REFERENCEABLE_PARAGRAPH_TYPE_FOOTER_INDEX->value => 'removeAttachmentNugget'
    ];
  }

  /**
   * Remove attachment from referenceable paragraph field.
   *
   * @param ReferenceableEntityEvent $event  Our custom event object.
   */
  public function removeAttachmentNugget(ReferenceableEntityEvent $event): void
  {
    $event->remove('attachments');
  }
}
