<?php

namespace Drupal\helper_module\Controller;

use Drupal\Core\Controller\ControllerBase;

class NoDataController extends ControllerBase
{
  public function displayList(): array
  {
    return [
      '#theme' => 'help__components',
      '#selected_template' => 'all'
    ];
  }

  public function display(string $component): array
  {
    return [
      '#theme' => 'help__components',
      '#selected_template' => $component
    ];
  }
}