<?php

namespace Drupal\cards_nugget\EventSubscriber;

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
  public static function getSubscribedEvents() {
    return [
      // Static class constant => method on this class.
      EventName::REFERENCEABLE_PARAGRAPH_TYPE_MAIN_CONTENT_INDEX->value => 'removeCardNugglet',
      EventName::REFERENCEABLE_PARAGRAPH_TYPE_SIDEBAR_INDEX->value => 'removeCardNugglet',
      EventName::REFERENCEABLE_PARAGRAPH_TYPE_FOOTER_INDEX->value => 'removeCardsAndNugglets',
      EventName::REFERENCEABLE_PARAGRAPH_TYPE_HERO_CONTENT_INDEX->value => 'removeCardsAndNugglets',
      EventName::REFERENCEABLE_PARAGRAPH_TYPE_POSTSCRIPT_INDEX->value => 'removeCardNugglet',
    ];
  }

  /**
   * Remove card_nugglet from referenceable paragraph field.
   *
   * @param ReferenceableEntityEvent $event  Our custom event object.
   */
  public function removeCardNugglet(ReferenceableEntityEvent $event): void
  {
    $event->remove('card_nugglet');
  }

  public function removeCardsAndNugglets(ReferenceableEntityEvent $event): void
  {
    $event->remove('card_nugglet');
    $event->remove('cards');
  }


}
