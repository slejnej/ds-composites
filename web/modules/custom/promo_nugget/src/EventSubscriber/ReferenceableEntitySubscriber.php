<?php

namespace Drupal\promo_nugget\EventSubscriber;

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
      EventName::REFERENCEABLE_PARAGRAPH_TYPE_MAIN_CONTENT_INDEX->value => 'removePromoNugget',
      EventName::REFERENCEABLE_PARAGRAPH_TYPE_SIDEBAR_INDEX->value => 'removePromoNugget',
      EventName::REFERENCEABLE_PARAGRAPH_TYPE_FOOTER_INDEX->value => 'removePromoNugget',
    ];
  }

  /**
   * Remove promo from referenceable paragraph field.
   *
   * @param ReferenceableEntityEvent $event  Our custom event object.
   */
  public function removePromoNugget(ReferenceableEntityEvent $event): void
  {
    $event->remove('promo');
  }
}
