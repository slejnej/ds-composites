<?php

namespace Drupal\remora_core\Builder;

use Drupal\breakpoint\Breakpoint;
use Drupal\breakpoint\BreakpointManagerInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\image\Entity\ImageStyle;
use Drupal\media\MediaInterface;
use Drupal\responsive_image\Entity\ResponsiveImageStyle;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;

class RemoraImageBuilder
{
  private const BREAKPOINT_DEFINITION_THEME = 'remora_base_theme';
  private const DEFAULT_BREAKPOINT = 'default';
  private const BREAKPOINTS_ORDER = ['default' => null, 'xs' => null, 'sm' => null, 'md' => null, 'lg' => null, 'xl' => null, 'xxl' => null];

  public function __construct(private readonly BreakpointManagerInterface $breakpointManager, private readonly LoggerInterface $logger)
  {
  }

  /**
   * Builds a render array for a media entity with responsive image sources, includes caching metadata
   * 'default' is a required breakpoint
   *
   * @param MediaInterface $media The media entity being rendered
   * @param string $imageUri The image URI being rendered
   * @param array $stylesByBreakpoint An array of image styles by breakpoint, e.g. ['default' => 'responsive_image_style_id', 'md' => 'responsive_image_style_id']
   * @param AccessResult $accessResult Used for caching purposes only
   * @return array
   */
  public function build(MediaInterface $media, string $imageUri, array $stylesByBreakpoint, AccessResult $accessResult): array
  {
    if(!isset($stylesByBreakpoint[self::DEFAULT_BREAKPOINT])) {
      throw new InvalidArgumentException(sprintf('You must provide a default image style using the "%s" breakpoint', self::DEFAULT_BREAKPOINT));
    }

    // make sure we have a style for each breakpoint to optimize performance
    // so if user only provides styles for sm and md, we will make sure that the md style has a style for lg and xl as well
    $paddedStyles = self::BREAKPOINTS_ORDER;
    $paddedStyles[self::DEFAULT_BREAKPOINT] = $lastStyle = $stylesByBreakpoint[self::DEFAULT_BREAKPOINT];
    foreach($paddedStyles as $key => &$style) {
      $style = $stylesByBreakpoint[$key] ?? $lastStyle;
      $lastStyle = $style;
    }

    $sources = $this->generateSources($imageUri, $paddedStyles);
    $default_image = $sources[self::DEFAULT_BREAKPOINT] ?? '';
    unset($sources[self::DEFAULT_BREAKPOINT]);

    $build = [
      '#theme' => 'remora_image',
      '#sources' => $sources,
      '#default_image' => $default_image,
      '#media' => $media
    ];

    // cache the render array, we can keep it as long as the media is valid for
    CacheableMetadata::createFromRenderArray($build)
      ->setCacheTags(['media:' . $media->id()])
      ->addCacheableDependency($accessResult)
      ->addCacheableDependency($media)
      ->applyTo($build);

    return $build;
  }

  /**
   * Returns an array of image URIs by breakpoint media query
   * Media query is determined by grabbing the min-width from the theme's breakpoints, default if no min-width is set
   *
   * @param string $imageUri The image URI being rendered
   * @param array $stylesByBreakpoint The image style to use for each breakpoint
   * @return array An array of image URIs by breakpoint media query, e.g. ['default' => 'https://example.com/image.jpg', '(min-width: 768px)' => 'https://example.com/image2.jpg']
   */
  private function generateSources(string $imageUri, array $stylesByBreakpoint): array
  {
    /** @var Breakpoint[] $themeBreakpoints */
    $themeBreakpoints = $this->breakpointManager->getBreakpointsByGroup(self::BREAKPOINT_DEFINITION_THEME);
    $result = [];

    // loop over all the breakpoints
    foreach($stylesByBreakpoint as $breakpoint => $style) {
      $responsiveStyle = ResponsiveImageStyle::load($style);
      $themeBreakpoint = sprintf('%s.%s', self::BREAKPOINT_DEFINITION_THEME, $breakpoint);


      if($breakpoint === self::DEFAULT_BREAKPOINT) {
        // if this style is incorrect, just break cause we should always have a default image
        // load using the responsive name so we can use the same names for all breakpoints instead of having to remember default is different
        $result[$breakpoint] = ImageStyle::load($responsiveStyle->getFallbackImageStyle())->buildUrl($imageUri);
        continue;
      } else if($responsiveStyle === null) {
        // fail quietly if the repsonsive style couldnt be found
        $this->logger->error('No responsive image style "@style" found for breakpoint @breakpoint', ['@breakpoint' => $breakpoint, '@style' => $style]);
        continue;
      }

      // loop over all the image style mappings for this image style
      // they contain the image style id for each breakpoint
      foreach($responsiveStyle->getImageStyleMappings() as $mapping) {
        // if the breakpoint doesn't match, move on
        if(($mapping['breakpoint_id'] ?? '') !== $themeBreakpoint) {
          continue;
        }

        // get the min-width from the media query
        $mediaQuery = $themeBreakpoints[$themeBreakpoint]->getMediaQuery();

        // if no min-width is set, assume we can display it on all breakpoints and there will be another breakpoint taking over at some point
        $result[$mediaQuery] ??= ImageStyle::load($mapping['image_mapping'])->buildUrl($imageUri);
      }
    }

    return $result;
  }
}
