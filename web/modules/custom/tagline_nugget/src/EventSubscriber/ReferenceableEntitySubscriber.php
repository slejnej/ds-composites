<?php

namespace Drupal\tagline_nugget\EventSubscriber;

use Drupal\remora_core\Enum\EventName;
use Drupal\remora_core\Event\ReferenceableEntityEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ReferenceableEntitySubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      // Static class constant => method on this class.
      EventName::REFERENCEABLE_PARAGRAPH_TYPE_SIDEBAR_INDEX->value => 'removeTaglineNugget',
      EventName::REFERENCEABLE_PARAGRAPH_TYPE_FOOTER_INDEX->value => 'removeTaglineNugget',
      EventName::REFERENCEABLE_PARAGRAPH_TYPE_MAIN_CONTENT_INDEX->value => 'removeTaglineNugget'
    ];
  }

  public function removeTaglineNugget(ReferenceableEntityEvent $event): void
  {
    $event->remove('tagline');
    $event->remove('tagline');
  }


}
