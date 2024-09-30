<?php

namespace Drupal\dc_custom\Twig;

use Drupal\Core\Url;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class Custom extends AbstractExtension
{
  /**
   * {@inheritdoc}
   */
  public function getName(): string
  {
    return 'dc_custom';
  }

  public function getFunctions(): array
  {
    return [
      new TwigFunction('current_language', [$this, 'getCurrentLanguage']),
      new TwigFunction('get_node_language_url', [$this, 'getNodeLanguageUrl']),
    ];
  }

  /**
   * Get the current language.
   *
   * @return string The ID of the current language.
   */
  public function getCurrentLanguage(): string
  {
    return \Drupal::languageManager()->getCurrentLanguage()->getId();
  }

  /**
   * Get the language-specific URL for a given Url object.
   *
   * @param Url $url The Url object for which to generate the language-specific URL.
   * @param string $language The language code for the desired language.
   *
   * @return string The language-specific URL.
   */
  public function getNodeLanguageUrl(Url $url, $language): string
  {
    // Convert the Url object to a plain system path string.
    $system_path = $url->getInternalPath();
    $system_path = empty($system_path) ? '/node/1' : $system_path;

    list($entity_type, $nid) = explode('/', $system_path);

    if ($entity_type == 'node' && is_numeric($nid)) {
      $alias = \Drupal::service('path_alias.manager')->getAliasByPath(sprintf('/%s', $system_path), $language);
      if ($language === 'en') {
        return sprintf('%s', $alias);
      }
      return sprintf('/%s%s', $language, $alias);
    }

    return sprintf('/%s%s', $language, $url->toString());
  }
}