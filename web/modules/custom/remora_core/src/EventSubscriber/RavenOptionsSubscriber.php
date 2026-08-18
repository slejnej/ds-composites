<?php

namespace Drupal\remora_core\EventSubscriber;

use Drupal\raven\Event\OptionsAlter;
use Sentry\Breadcrumb;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Listens to the OptionsAlter event and removes any user breadcrumbs from being spilled.
 */
class RavenOptionsSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array
  {
    return [
      OptionsAlter::class => 'onOptionsAlter',
    ];
  }

  /**
   * Filters out user category breadcrumbs.
   *
   * @param OptionsAlter $event
   * @return void
   */
  public function onOptionsAlter(OptionsAlter $event): void
  {
    $event->options['before_breadcrumb'] = function(Breadcrumb $breadcrumb): ?Breadcrumb {
      if($breadcrumb->getCategory() === 'user') {
        return null;
      }

      return $breadcrumb;
    };
  }
}
