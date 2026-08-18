# Event Subscriber

your_module/src/EventSubscriber/ReferenceableEntitySubscriber.php

```php
<?php

namespace Drupal\your_module\EventSubscriber;

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
      ReferenceableEntityEvent::NAME => 'onFieldRender',
    ];
  }

  /**
   * Subscribe to the user login event dispatched.
   *
   * @param ReferenceableEntityEvent $event  Our custom event object.
   */
  public function onFieldRender(ReferenceableEntityEvent $event) {
    $event->remove('country');
  }
}
```

your_module/your_module.services.yml

```yaml

# Name of this service.
your_module.event_subscriber.referenceable_entity:
  # Event subscriber class that will listen for the events.
  class: '\Drupal\your_module\EventSubscriber\ReferenceableEntitySubscriber'
  # Tagged as an event_subscriber to register this subscriber with the event_dispatch service.
  tags:
    - { name: 'event_subscriber' }
```
