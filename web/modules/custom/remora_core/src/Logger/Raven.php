<?php

namespace Drupal\remora_core\Logger;

use Drupal\Core\Site\Settings;
use Drupal\raven\Logger\Raven as RavenBase;
use Drupal\remora_core\Cache\RemoraSettingsCache;
use Sentry\ClientInterface;

/**
 * Decorates the Raven logger to add the DSN, environment, and release version to the superglobals.
 * We can't do it anywhere else cause the logger is initialized pretty early in the process, our own services are never going to be initialized before then.
 * We do require this class tho, cause we want 1 central place which retrieves the release version
 */
class Raven extends RavenBase
{
  /**
   * Whether the superglobals have been initialized already this request
   *
   * @var bool
   */
  private bool $hasInitialized = false;

  public function getClient(bool $force_new = false, bool $force_throw = false): ?ClientInterface
  {
    // adds the raven required superglobals before the sentry client is initialized
    $this->addRavenSuperglobals();

    return parent::getClient($force_new, $force_throw);
  }

  /**
   * Add the DSN, environment, and release version to the superglobals.
   *
   * @return void
   */
  private function addRavenSuperglobals(): void
  {
    if($this->hasInitialized) {
      return;
    }

    $this->hasInitialized = true;

    // grab the DSN and environment from the remora_core settings
    // and the release version from the current Drupal release using the web path
    $remoraCoreSettings = RemoraSettingsCache::get('remora_core.settings', fn(): array => Settings::get('remora_core') ?? []);
    $releaseVersion = RemoraSettingsCache::get('remora_core.release', function(): string {
      preg_match('/.+\/releases\/([^\/]+)\/.*/', DRUPAL_ROOT, $pathMatches);
      return $pathMatches[1] ?? '';
    });

    $_SERVER['SENTRY_DSN'] = $remoraCoreSettings['sentry']['dsn'] ?? '';
    $_SERVER['SENTRY_ENVIRONMENT'] = $remoraCoreSettings['sentry']['env'] ?? '';
    $_SERVER['SENTRY_RELEASE'] = $releaseVersion;
  }
}
