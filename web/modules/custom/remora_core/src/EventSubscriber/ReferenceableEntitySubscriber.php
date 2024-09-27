<?php

namespace Drupal\remora_core\EventSubscriber;

use Drupal\remora_core\Enum\EventName;
use Drupal\remora_core\Event\ReferenceableEntityEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class UserLoginSubscriber.
 *
 * @package Drupal\custom_events\EventSubscriber
 */
class ReferenceableEntitySubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array
  {
    return [
      // Static class constant => method on this class.
      EventName::REFERENCEABLE_PARAGRAPH_TYPE_SIDEBAR_INDEX->value => 'onSingleColumnIndex',
      EventName::REFERENCEABLE_PARAGRAPH_TYPE_MAIN_CONTENT_INDEX->value => 'onSingleColumnIndex',
      EventName::REFERENCEABLE_PARAGRAPH_TYPE_FOOTER_INDEX->value => 'onSiteSettingsIndex',
      EventName::REFERENCEABLE_PARAGRAPH_TYPE_HERO_CONTENT_INDEX->value => 'onSingleColumnIndex',
      EventName::REFERENCEABLE_PARAGRAPH_TYPE_POSTSCRIPT_INDEX->value => 'onMultiColumnIndex',
    ];
  }

  public function onSingleColumnIndex(ReferenceableEntityEvent $event): void
  {
    $event->remove('flexible_layout');
    $event->remove('footer_section');
  }

  public function onMultiColumnIndex(ReferenceableEntityEvent $event): void
  {
    $event->remove('section');
    $event->remove('footer_section');
  }
  public function onSiteSettingsIndex(ReferenceableEntityEvent $event): void
  {
    $event->remove('flexible_layout');
    $event->remove('section');
  }
}
