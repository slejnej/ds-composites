<?php

namespace Drupal\security\Middleware;

use Drupal;
use Drupal\security\Cache\ConfigCache;
use Drupal\security\Form\ObfuscateVersionsForm;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Removes the configured HTTP headers from the response.
 * @see ObfuscateVersionsForm
 */
class RemoveHttpHeadersMiddleware implements HttpKernelInterface
{
  /**
   * Constructs a RemoveHttpHeadersMiddleware object.
   *
   * @param HttpKernelInterface $httpKernel
   */
  public function __construct(private readonly HttpKernelInterface $httpKernel)
  {

  }

  /**
   * @inheritDoc
   */
  public function handle(Request $request, int $type = self::MAIN_REQUEST, bool $catch = true): Response
  {
    $response = $this->httpKernel->handle($request, $type, $catch);

    // Only allow removal of HTTP headers on master request.
    if($type === static::MAIN_REQUEST) {
      $response = $this->removeConfiguredHttpHeaders($response);
    }

    return $response;
  }

  /**
   * Remove configured HTTP headers.
   *
   * @param Response $response
   *   The response object.
   *
   * @return Response
   *   The given response object
   *   without the HTTP response headers that should be removed.
   */
  protected function removeConfiguredHttpHeaders(Response $response): Response
  {
    // getting from cache is a fair bit faster, and since this runs on every request... Thanks to remove_http_headers module for inspo
    $headersToRemove = ConfigCache::get(
      'security.settings.obfuscate_versions.http_headers',
      function(): array {

        $configuredHeaders = Drupal::config(ObfuscateVersionsForm::CONFIG_ID)->get('remove_http_headers');
        return preg_split('/\r\n|\r|\n/', $configuredHeaders ?? '');
      }
    );

    foreach($headersToRemove as $header) {
      $response->headers->remove($header);
    }

    return $response;
  }
}
