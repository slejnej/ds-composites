<?php

namespace Drupal\cards_pod\EventSubscriber;

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
      EventName::REFERENCEABLE_PARAGRAPH_TYPE_MAIN_CONTENT_INDEX->value => 'removeCardPea',
      EventName::REFERENCEABLE_PARAGRAPH_TYPE_SIDEBAR_INDEX->value => 'removeCardPea',
      EventName::REFERENCEABLE_PARAGRAPH_TYPE_FOOTER_INDEX->value => 'removeCardsAndPeas',
      EventName::REFERENCEABLE_PARAGRAPH_TYPE_HERO_CONTENT_INDEX->value => 'removeCardsAndPeas',
      EventName::REFERENCEABLE_PARAGRAPH_TYPE_POSTSCRIPT_INDEX->value => 'removeCardPea',
    ];
  }

  /**
   * Remove card_pea from referenceable paragraph field.
   *
   * @param ReferenceableEntityEvent $event  Our custom event object.
   */
  public function removeCardPea(ReferenceableEntityEvent $event): void
  {
    $event->remove('card_pea');
  }

  public function removeCardsAndPeas(ReferenceableEntityEvent $event): void
  {
    $event->remove('card_pea');
    $event->remove('cards');
  }


}
