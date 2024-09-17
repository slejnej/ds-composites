<?php

namespace Drupal\remora_core\Twig\Extension;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Twig extension.
 */
class ModuleCheckExtension extends AbstractExtension
{

  /**
   * @param ModuleHandlerInterface $moduleHandler
   */
  public function __construct(private ModuleHandlerInterface $moduleHandler) {
  }

  public function getFunctions(): array
  {
    return [
      new TwigFunction('is_module_installed', [$this, 'isModuleInstalled']),
    ];
  }

  public function isModuleInstalled(string $module_name): bool
  {
    return $this->moduleHandler->moduleExists($module_name);
  }

}
