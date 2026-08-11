<?php

namespace Drupal\remora_core\Builder;

use Drupal\breakpoint\Breakpoint;
use Drupal\breakpoint\BreakpointManagerInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Template\Attribute;
use Drupal\image\Entity\ImageStyle;
use Drupal\image\ImageStyleInterface;
use Drupal\media\MediaInterface;
use Drupal\responsive_image\Entity\ResponsiveImageStyle;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;

class RemoraImageBuilder
{
  private const BREAKPOINT_DEFINITION_THEME = 'barrio_base_theme';
  private const DEFAULT_BREAKPOINT = 'default';
  private const BREAKPOINTS_ORDER = ['default' => null, 'xs' => null, 'sm' => null, 'md' => null, 'lg' => null, 'xl' => null, 'xxl' => null];
  private const BREAKPOINT_SIZES_34 = [
    'xs' => 300,
    'sm' => 400,
    'md' => 500,
    'lg' => 600,
    'xl' => 700,
    'xxl' => 800,
  ];
  private const SIZES_MASONRY = [
    'xs' => '100vw',
    'sm' => '(min-width: 400px) 50vw',
    'md' => '(min-width: 600px) 50vw',
    'lg' => '(min-width: 900px) 33vw',
    'xl' => '(min-width: 1200px) 25vw',
  ];

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
   * @param array $attributes Additional attributes to add to the image tag
   * @param bool $shrinkImage Whether to shrink the image to fit in a container or not
   * @return array
   */
  public function build(MediaInterface $media, string $imageUri, array $stylesByBreakpoint, AccessResult $accessResult, array $attributes = [], bool $shrinkImage = false): array
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

    $response = $this->generateSources($imageUri, $paddedStyles, $shrinkImage);

    $sources = $response['sources'];
    $sizes = $response['sizes'];
    $default_image = $sources[self::DEFAULT_BREAKPOINT] ?? '';
    unset($sources[self::DEFAULT_BREAKPOINT]);

    $attributesObj = count($attributes) > 0 ? new Attribute($attributes) : null;

    $build = [
      '#theme' => 'remora_image',
      '#sources' => $sources,
      '#default_image' => $default_image,
      '#media' => $media,
      '#shrink_image' => $shrinkImage,
      '#sizes' => $sizes,
      '#image_attributes' => $attributesObj,
    ];

//    dd($build);
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
   * @param bool $shrink Whether to shrink the image to fit in a sidebar or not
   * @return array An array of image URIs by breakpoint media query, e.g. ['default' => 'https://example.com/image.jpg', '(min-width: 768px)' => 'https://example.com/image2.jpg']
   */
  private function generateSources(string $imageUri, array $stylesByBreakpoint, bool $shrink = false): array
  {
    /** @var Breakpoint[] $themeBreakpoints */
    $themeBreakpoints = $this->breakpointManager->getBreakpointsByGroup(self::BREAKPOINT_DEFINITION_THEME);
    $result = [];
    $sizes = [];
    $imageStyle = null;

    foreach ($stylesByBreakpoint as $breakpoint => $style) {
      $imageStyle = $style;

      $responsiveStyle = ResponsiveImageStyle::load($style);
      $themeBreakpoint = sprintf('%s.%s', self::BREAKPOINT_DEFINITION_THEME, $breakpoint);

      if ($breakpoint === self::DEFAULT_BREAKPOINT) {
        $imageStyle = ImageStyle::load($responsiveStyle->getFallbackImageStyle());
        $result[$breakpoint] = ['uri' => $imageStyle->buildUrl($imageUri)] + $this->getStyleSize($imageStyle, $imageUri);
        continue;
      } elseif ($responsiveStyle === null) {
        $this->logger->error('No responsive image style "@style" found for breakpoint @breakpoint', [
          '@breakpoint' => $breakpoint,
          '@style' => $style,
        ]);
        continue;
      }

      foreach ($responsiveStyle->getImageStyleMappings() as $mapping) {
        if (($mapping['breakpoint_id'] ?? '') !== $themeBreakpoint) {
          continue;
        }

        $imageStyleObj = ImageStyle::load($mapping['image_mapping']);
        $imageUrl = $imageStyleObj->buildUrl($imageUri);

        if ($shrink) {
          $width = self::BREAKPOINT_SIZES_34[$breakpoint] ?? null;

          if ($width !== null) {
            $result[$width] = $imageUrl;
            if ($style === 'masonry' && isset(self::SIZES_MASONRY[$breakpoint])) {
              $sizes[] = self::SIZES_MASONRY[$breakpoint];
            } else {
              $sizes[] = $width . 'px';
            }
          }
        } else {
          $imageDimensions = $this->getStyleSize($imageStyleObj, $imageUri);
          $mediaQuery = $themeBreakpoints[$themeBreakpoint]->getMediaQuery();
          $result[$mediaQuery] ??= ['uri' => $imageUrl] + $imageDimensions;
        }
      }
    }

    if ($imageStyle === 'masonry') {
      $sizes = array_reverse($sizes);
    }

    return [
      'sources' => $result,
      'sizes' => $shrink ? implode(', ', $sizes) : '100vw',
    ];
  }

  /**
   * Returns the width and height of an imageURI for the given style
   *
   * @param ImageStyleInterface|null $imageStyle
   * @param string $imageUri
   * @return array
   */
  #[ArrayShape(['width' => 'integer', 'height' => 'integer'])]
  private function getStyleSize(?ImageStyleInterface $imageStyle, string $imageUri): array
  {
    $imageDimensions = @getimagesize($imageUri);
    if(!is_array($imageDimensions)) {
      return [
        'width' => '',
        'height' => '',
      ];
    }

    $imageDimensions = [
      'width' => $imageDimensions[0],
      'height' => $imageDimensions[1]
    ];

    $imageStyle->transformDimensions($imageDimensions, $imageUri);

    return $imageDimensions;
  }
}
