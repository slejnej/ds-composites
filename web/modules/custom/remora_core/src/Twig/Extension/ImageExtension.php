<?php

namespace Drupal\remora_core\Twig\Extension;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\File\FileSystemInterface;
use Drupal\image\Entity\ImageStyle;
use Drupal\remora_core\Builder\RemoraImageBuilder;
use Drupal\remora_core\Repository\MediaRepository;
use JetBrains\PhpStorm\ArrayShape;
use Psr\Log\LoggerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Twig extension.
 */
class ImageExtension extends AbstractExtension {

  public function __construct(private readonly RemoraImageBuilder $imageBuilder,
                              private readonly MediaRepository $mediaRepository,
                              private readonly LoggerInterface $logger,
                              private readonly FileSystemInterface $fileSystem,

  )
  {
  }

  public function getFilters(): array
  {
    return [
      'remora_image' => new TwigFilter('remora_image', [$this, 'remoraImage']),
      'image_size' => new TwigFilter('image_size', [$this, 'imageSize']),
      'file_contents' => new TwigFilter('file_contents', [$this, 'fileContents']),
    ];
  }

  /**
   * Gets the image size of an image style
   *
   * @param string $image The image URI
   * @param string|null $style The image style to get the size for original if null
   * @return array An array containing the width and height of the image
   */
  #[ArrayShape(['width' => 'number', 'height' => 'number'])]
  public function imageSize(string $image, ?string $style = null): array
  {
    // If no style requested, use original
    if ($style === null) {
      return $this->getOriginalImageSize($image);
    }

    // Try styled version first
    $imageStyle = ImageStyle::load($style);
    $imageUri = $imageStyle->buildUri($image);

    // Check if derivative exists
    if (file_exists($imageUri)) {
      $imageStylePath = $this->fileSystem->realpath($imageUri);
      $imageSize = @getimagesize($imageStylePath);
      if ($imageSize) {
        return [
          'width' => $imageSize[0],
          'height' => $imageSize[1],
        ];
      }
    }

    // Fall back to original
    return $this->getOriginalImageSize($image);
  }

  /**
   * Get original image size without generating derivatives
   */
  private function getOriginalImageSize(string $image): array
  {
    $originalUri = $this->fileSystem->realpath($image);
    if (file_exists($originalUri)) {
      $originalSize = @getimagesize($originalUri);
      if ($originalSize) {
        return [
          'width' => $originalSize[0],
          'height' => $originalSize[1],
        ];
      }
    }

    return ['width' => 0, 'height' => 0];
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
  public function remoraImage(string $image, array $stylesByBreakpoint, bool $doAccessCheck = true, array $attributes = [], ?bool $shrinkImage = false): array
  {
    $shrinkImage = $shrinkImage ?? false;
    $media = $this->mediaRepository->findByUri($image);

    // If media not found, log and return empty or fallback render array.
    if ($media === null) {
      $this->logger->warning('No media found for image URI: @image', ['@image' => $image]);
      // Return an empty array or a fallback to avoid errors downstream.
      return [];
    }

    $accessResult = AccessResult::allowed();

    if ($doAccessCheck) {
      // Check if field_media_image exists and entity is set before calling access.
      $fieldMediaImage = $media->get('field_media_image');
      $entity = $fieldMediaImage ? $fieldMediaImage->entity : null;

      if ($entity) {
        $accessResult = $entity->access('view', NULL, TRUE);
      }
      else {
        // No entity to check access on — deny access for safety.
        $accessResult = AccessResult::forbidden();
      }
    }

    // If we don't have access, remove all image styles.
    if (!$accessResult->isAllowed()) {
      $stylesByBreakpoint = [];
    }

    return $this->imageBuilder->build($media, $image, $stylesByBreakpoint, $accessResult, $attributes, $shrinkImage);
  }

  /**
   * Retrieves the contents of a file from the specified path.
   *
   * @param string $path The file path, which may use the "public://" stream wrapper or a direct filesystem path.
   * @return string|bool The contents of the file if it exists, or FALSE if the file does not exist.
   */
  public function fileContents(string $path): string|false
  {
    // Handle Drupal stream wrappers (public://, private://, etc.)
    if (strpos($path, '://') !== false) {
      $realpath = \Drupal::service('file_system')->realpath($path);
    }
    // Handle theme relative paths
    elseif (str_starts_with($path, '/themes/') || str_starts_with($path, 'themes/')) {
      // If it starts with /themes/, it's already an absolute path from Drupal root
      if (str_starts_with($path, '/themes/')) {
        $realpath = DRUPAL_ROOT . $path;
      } else {
        $realpath = DRUPAL_ROOT . '/' . $path;
      }
    }
    // Handle relative paths from Drupal root
    elseif (!str_starts_with($path, '/')) {
      $realpath = DRUPAL_ROOT . '/' . $path;
    }
    // Already an absolute path
    else {
      $realpath = $path;
    }

    if ($realpath && file_exists($realpath)) {
      return file_get_contents($realpath);
    }

    return false;
  }
}
