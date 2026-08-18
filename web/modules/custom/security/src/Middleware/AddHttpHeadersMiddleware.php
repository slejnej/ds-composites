<?php

namespace Drupal\security\Middleware;

use Drupal;
use Drupal\security\Cache\ConfigCache;
use Drupal\security\Form\HeadersConfigForm;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Removes the configured HTTP headers from the response.
 * @see HeadersConfigForm
 */
class AddHttpHeadersMiddleware implements HttpKernelInterface
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
      $this->addConfiguredHeaders($response);
      $this->addUserEventHeaders($request, $response);
    }

    return $response;
  }

  /**
   * Remove configured HTTP headers.
   *
   * @param Response $response
   *   The response object.
   */
  protected function addConfiguredHeaders(Response $response): void
  {
    // getting from cache is a fair bit faster, and since this runs on every request... Thanks to remove_http_headers module for inspo
    $headersToAdd = ConfigCache::get(
      'security.settings.headers.add',
      function(): array {

        $configuredHeaders = Drupal::config(HeadersConfigForm::CONFIG_ID)->get('add_http_headers');
        preg_match_all('/^(([^: ]+): *([^ \n\r]+))/m', $configuredHeaders ?? '', $matches);

        $result = [];
        foreach($matches[2] as $i => $header) {
          $result[$header] = $matches[3][$i];
        }

        return $result;
      }
    );

    foreach($headersToAdd as $header => $value) {
      $response->headers->set($header, $value);
    }
  }

  /**
   * Adds Clear-Site-Data header on user login/logout
   *
   * @param Request $request
   * @param Response $response
   * @return void
   */
  private function addUserEventHeaders(Request $request, Response $response): void
  {
    if($request->attributes->get('_security.user_logged_out') === true) {
      $this->addLogoutHeaders($response);
    } else if($request->attributes->get('_security.user_logged_in') === true) {
      $this->addLoginHeaders($response);
    }
  }

  private function addLogoutHeaders(Response $response): void
  {
    $response->headers->set('Clear-Site-Data', '"cache", "storage", "executionContexts"');
  }

  private function addLoginHeaders(Response $response): void
  {
    $response->headers->set('Clear-Site-Data', '"cache", "storage", "executionContexts"');
  }
}
