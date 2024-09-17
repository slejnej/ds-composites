<?php

namespace Drupal\remora_core\Controller\Permission;

use Drupal;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Drupal\remora_core\Manager\ModulePermissionsManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ReimportController extends ControllerBase
{

  public function __construct(
    private readonly ModulePermissionsManager $modulePermissionsManager
  )
  {
  }

  public static function create(ContainerInterface $container): self
  {
    return new self(
      $container->get('remora_core.manager.module_permissions')
    );
  }

  public function update(Request $request): Response
  {
    $allModules = $this->moduleHandler()->getModuleList();
    $modules = array_keys($allModules);
    $importCount = 0;

    foreach($modules as $module) {
      $res = $this->modulePermissionsManager->handleModulePermissions($module);
      if($res) {
        $importCount++;
      }
    }

    Drupal::messenger()->addMessage(Drupal::translation()->formatPlural(
      $importCount,
      'One module permissions updated.',
      '@count modules permissions updated.'
    ));

    return new RedirectResponse($request->headers->get('referer') ?? Url::fromRoute('<front>')->toString());
  }

}
