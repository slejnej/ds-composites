<?php

namespace Drupal\remora_core;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\Core\DependencyInjection\ServiceProviderInterface;
use Drupal\remora_core\FileSystem\FileSystem;
use Drupal\remora_core\Logger\Raven;
use Drupal\remora_core\Service\LinkChecker\BatchExtractorService;
use Drupal\remora_core\Service\LinkChecker\ExtractorService;
use Symfony\Component\DependencyInjection\Definition;

class RemoraCoreServiceProvider extends ServiceProviderBase implements ServiceProviderInterface
{

  /**
   * Overwrites the default Drupal FileSystem with our own extended version
   *
   * @param ContainerBuilder $container
   * @return void
   */
  public function alter(ContainerBuilder $container): void
  {
    $def = $container->getDefinition('file_system');
    $def->setClass(FileSystem::class);
  }

  /**
   * Registers ta raven decorator to supply Sentry's DSN to the logger
   * Only registers if the logger.raven service is present
   * Not using a service in the traditional way, because this feature was added later, and the service is registered before raven is installed causing drupal issues
   *
   * @param ContainerBuilder $container
   * @return void
   */
  private function registerRavenDecorator(ContainerBuilder $container): void
  {
    $this->overwriteServiceClass($container, 'logger.raven', Raven::class);
  }

  /**
   * Overwrites the LinkExtractor services with our own extended version
   * This version basically will scan nodes for their HTML, and leaves the rest to the default implementation
   *
   * @param ContainerBuilder $container
   * @return void
   */
  private function registerLinkExtractors(ContainerBuilder $container): void
  {
    $this->overwriteServiceClass($container, 'linkchecker.extractor_batch', BatchExtractorService::class);

    $this->overwriteServiceClass($container, 'linkchecker.extractor', ExtractorService::class)
      ?->addArgument($container->getDefinition('remora_core.repository.search_api.node'));
  }

  /**
   * Overwrites the class of a service and then returns the definition
   *
   * @param ContainerBuilder $containerBuilder
   * @param string $serviceId
   * @param string $newClass
   * @return Definition|null
   */
  private function overwriteServiceClass(ContainerBuilder $containerBuilder, string $serviceId, string $newClass): ?Definition
  {
    if (!$containerBuilder->hasDefinition($serviceId)) {
      return null;
    }

    return $containerBuilder->getDefinition($serviceId)
      ->setClass($newClass);
  }
}
