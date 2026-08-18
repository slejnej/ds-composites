<?php

namespace Drupal\remora_core\Controller\Styles;

use Drupal;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\installation_profile\Manager\ModuleScssManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class LinkController extends ControllerBase
{

  public function __construct(
    private readonly ModuleScssManager $moduleScssManager
  )
  {
  }

  public static function create(ContainerInterface $container): self
  {
    return new self(
      $container->get('installation_profile.manager.module_scss')
    );
  }

  public function update(Request $request): Response
  {
    $allModules = $this->moduleHandler()->getModuleList();
    $modules = array_keys($allModules);
    $importCount = 0;

    foreach($modules as $module) {
      $res = $this->moduleScssManager->linkStyles($module);
      if($res) {
        $importCount++;
      }
    }

    Drupal::messenger()->addMessage(Drupal::translation()->formatPlural(
      $importCount,
      'One module\'s styles linked.',
      '@count modules styles linked.'
    ));

    return new RedirectResponse($request->headers->get('referer') ?? Url::fromRoute('<front>')->toString());
  }

}
