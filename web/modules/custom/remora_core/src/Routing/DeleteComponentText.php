<?php

namespace Drupal\remora_core\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\remora_core\Form\ExtendDeleteComponentForm;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class DeleteComponentText extends RouteSubscriberBase {

  protected function alterRoutes(RouteCollection $collection): void
  {
    // change the route to overridden form
    if ($route = $collection->get('layout_paragraphs.builder.delete_item')) {
      $route->addDefaults([
        "_form" => ExtendDeleteComponentForm::class,
        "operation" => 'delete',
      ]);
    }
  }

}
