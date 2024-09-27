<?php

namespace Drupal\remora_core\Twig\Extension;

use Drupal;
use Drupal\breakpoint\BreakpointManager;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\remora_core\Builder\RemoraImageBuilder;
use Drupal\remora_core\Repository\MediaRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Twig extension.
 */
class ImageExtension extends AbstractExtension {

  public function __construct(private readonly RemoraImageBuilder $imageBuilder, private readonly MediaRepository $mediaRepository)
  {
  }

  public function getFilters(): array
  {
    return [
      'remora_image' => new TwigFilter('remora_image', [$this, 'remoraImage']),
    ];
  }

  /**
   * Will load the appropriate responsive image style for each breakpoint and return a render array for remora_image
   * Will silently skip breakpoints that don't have a mapping
   * Uses the base theme's breakpoint definitions
   * Note: Because the sm breakpoint doesn't have a min-width, 'sm' and 'all' are synonymous
   *
   * @param string $image The image's URI
   * @param array $stylesByBreakpoint An array of breakpoints and their image style. E.g.
   * [ 'default' => 'portrait', 'md' => 'card', 'xl' => 'landscape' ]
   * @return string[] A render array for remora_image containing:
   *  - media: The media item for this image URI
   *  - sources: An array of sources for each breakpoint
   *  - default_image: The default image for this image URI
   */
  public function remoraImage(string $image, array $stylesByBreakpoint, bool $doAccessCheck = true): array
  {
    $media = $this->mediaRepository->findByUri($image);
    $accessResult = $doAccessCheck ? $media->get('field_media_image')->entity->access('view', NULL, TRUE) : AccessResult::allowed();

    // if we don't have access, simply remove all imagestyles
    if(!$accessResult->isAllowed()) {
      $stylesByBreakpoint = [];
    }

    return $this->imageBuilder->build($media, $image, $stylesByBreakpoint, $accessResult);
  }
}
