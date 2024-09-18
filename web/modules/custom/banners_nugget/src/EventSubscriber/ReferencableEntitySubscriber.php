<?php

namespace Drupal\banners_nugget\EventSubscriber;

use Drupal\remora_core\Enum\EventName;
use Drupal\remora_core\Event\ReferenceableEntityEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class ReferencableEntitySubscriber.
 *
 * @package Drupal\custom_events\EventSubscriber
 */
class ReferencableEntitySubscriber implements EventSubscriberInterface {

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents() {
        return [
            // Static class constant => method on this class.
            EventName::REFERENCEABLE_CONTENT_TYPE_INDEX->value => 'removeBannerNugglet',
            EventName::REFERENCEABLE_PARAGRAPH_TYPE_MAIN_CONTENT_INDEX->value => 'removeBannerBoth',
            EventName::REFERENCEABLE_PARAGRAPH_TYPE_FOOTER_INDEX->value => 'removeBannerBoth',
            EventName::REFERENCEABLE_PARAGRAPH_TYPE_HERO_CONTENT_INDEX->value => 'removeBannerNugglet',
            EventName::REFERENCEABLE_PARAGRAPH_TYPE_SIDEBAR_INDEX->value => 'removeBannerBoth',
            EventName::REFERENCEABLE_PARAGRAPH_TYPE_POSTSCRIPT_INDEX->value => 'removeBannerNugglet',
        ];
    }

    /**
     * Remove banners from referencable paragraph field.
     *
     * @param ReferenceableEntityEvent $event  Our custom event object.
     */
    public function removeBannersNugget(ReferenceableEntityEvent $event): void
    {
        $event->remove('banners');
    }

    /**
     * Remove banner_nugglet from referenceable paragraph field.
     *
     * @param ReferenceableEntityEvent $event  Our custom event object.
     */
    public function removeBannerNugglet(ReferenceableEntityEvent $event): void
    {
        $event->remove('banner_nugglet');
    }

    /**
     * Remove banner_nugget from referenceable paragraph field.
     *
     * @param ReferenceableEntityEvent $event  Our custom event object.
     */
    public function removeBannerBoth(ReferenceableEntityEvent $event): void
    {
        $event->remove('banner_nugglet');
        $event->remove('banners');
    }
}
