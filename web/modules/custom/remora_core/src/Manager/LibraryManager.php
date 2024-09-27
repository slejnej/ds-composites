<?php

namespace Drupal\remora_core\Manager;

use Drupal;

class LibraryManager
{

  /**
   * Attaches a library to a render array only if the current route is an admin route.
   *
   * @param array $variables The render array to add the libraries to.
   * @param string ...$libraries One or multiple libraries to attach if the current route is an admin route.
   * @return void
   */
  public static function addAdminLibrary(array &$variables, string ...$libraries): void
  {
    if (!Drupal::service('router.admin_context')->isAdminRoute()) {
      return;
    }

    foreach ($libraries as $library) {
      $variables['#attached']['library'][] = $library;
    }
  }
}
