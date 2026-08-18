<?php

namespace Drupal\remora_core\Service\LinkChecker;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\linkchecker\Entity\LinkCheckerLink;
use Drupal\linkchecker\LinkExtractorService as BaseExtractor;
use Drupal\linkchecker\Plugin\LinkExtractorManager;
use Drupal\node\Entity\Node;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class ExtractorService extends BaseExtractor {

  protected LoggerInterface $logger;

  public function __construct(
    LinkExtractorManager $extractorManager,
    EntityTypeManagerInterface $entityTypeManager,
    ConfigFactory $configFactory,
    RequestStack $requestStack,
    Connection $dbConnection,
    TimeInterface $time,
    LoggerInterface $logger
  ) {
    parent::__construct($extractorManager, $entityTypeManager, $configFactory, $requestStack, $dbConnection, $time);
    $this->logger = $logger;
  }

  /**
   * {@inheritDoc}
   */
  public function extractFromEntity(FieldableEntityInterface $entity) {
    // If not a node, fallback to parent extraction.
    if (!$entity instanceof Node) {
      return parent::extractFromEntity($entity);
    }

    // Create the HTML link extractor plugin.
    $extractor = $this->extractorManager->createInstance('html_link_extractor');
    $links = [];
    $this->pos = 0;

    // We'll extract links from all text-based fields (e.g., body, field_text) of the node.
    // Adjust the field names as per your content type fields.
    $field_names = array_filter($entity->getFieldDefinitions(), function ($field_definition) {
      return $field_definition->getType() === 'text_with_summary' || $field_definition->getType() === 'text_long' || $field_definition->getType() === 'text';
    });

    $texts_to_extract = [];

    foreach ($field_names as $field_name => $definition) {
      if ($entity->hasField($field_name) && !$entity->get($field_name)->isEmpty()) {
        // Collect all field values as strings for extraction.
        foreach ($entity->get($field_name) as $item) {
          $value = $item->value ?? '';
          if (!empty($value)) {
            $texts_to_extract[] = ['value' => $value];
          }
        }
      }
    }

    // Extract URLs from the collected field values.
    $urls = $extractor->extract($texts_to_extract);

    try {
      $baseContentUrl = $entity
        ->toUrl()
        ->setAbsolute()
        ->toString();
    }
    catch (\Exception $e) {
      $baseContentUrl = NULL;
      $this->logger->warning('Could not generate absolute URL for entity ID ' . $entity->id() . ': ' . $e->getMessage());
    }

    // Clean up URLs.
    $urls = array_filter($urls);
    $urls = array_unique($urls);
    $urls = $this->getLinks($urls, $baseContentUrl);

    foreach ($urls as $url) {
      foreach ($field_names as $field_name => $definition) {
        if (!$entity->hasField($field_name) || $entity->get($field_name)->isEmpty()) {
          continue;
        }

        foreach ($entity->get($field_name) as $item) {
          $value = $item->value ?? '';
          if (empty($value)) {
            continue;
          }

          $linkObject = LinkCheckerLink::create([
            'url' => $url,
            'entity_id' => [
              'target_id' => $entity->id(),
              'target_type' => $entity->getEntityTypeId(),
            ],
            'entity_field' => $field_name,
            'entity_langcode' => $entity->language()->getId(),
          ]);

          $linkObject->setParentEntity($entity);
          $links[$this->pos++] = $linkObject;
        }
      }
    }

    return $links;
  }

}
