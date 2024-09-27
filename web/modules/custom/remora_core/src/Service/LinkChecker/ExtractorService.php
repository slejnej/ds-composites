<?php

namespace Drupal\remora_core\Service\LinkChecker;

use Drupal;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\linkchecker\Entity\LinkCheckerLink;
use Drupal\linkchecker\LinkExtractorService as BaseExtractor;
use Drupal\linkchecker\Plugin\LinkExtractorManager;
use Drupal\node\Entity\Node;
use Drupal\remora_core\Repository\SearchAPI\SolrNodeRepository;
use Drupal\search_api\Plugin\search_api\data_type\value\TextValue;
use Symfony\Component\HttpFoundation\RequestStack;

class ExtractorService extends BaseExtractor
{

  public function __construct(LinkExtractorManager $extractorManager, EntityTypeManagerInterface $entityTypeManager, ConfigFactory $configFactory, RequestStack $requestStack, Connection $dbConnection, TimeInterface $time, private readonly SolrNodeRepository $solrRepository)
  {
    parent::__construct($extractorManager, $entityTypeManager, $configFactory, $requestStack, $dbConnection, $time);
  }


  /**
   * {@inheritDoc}
   */
  public function extractFromEntity(FieldableEntityInterface $entity) {
    if(!$entity instanceof Node) {
      return parent::extractFromEntity($entity);
    }

    $extractor = $this->extractorManager->createInstance('html_link_extractor');
    $links = [];
    $this->pos = 0;

    // get the node from SOLR
    // can't use find because multi-lingual
    $result = $this->solrRepository->findBy(['nid' => $entity->id()]);


    /** @var Drupal\search_api\Item\Item $item */
    foreach($result as $item) {
      $values = $item->getField('rendered_item')->getValues();
      if(count($values) === 0) {
        continue;
      }

      // Extract links from the rendered item.
      $urls = $extractor->extract(
        array_map(fn(TextValue $textValue) => ['value' => $textValue->getText()], $values)
      );

      try {
        $baseContentUrl = $entity
          ->toUrl()
          ->setAbsolute()
          ->toString();
      }
      catch (\Exception $e) {
        $baseContentUrl = NULL;
      }

      // Remove empty values.
      $urls = array_filter($urls);
      // Remove duplicate urls.
      $urls = array_unique($urls);
      $urls = $this->getLinks($urls, $baseContentUrl);


      foreach ($urls as $link) {
        $links[$this->pos++] = LinkCheckerLink::create([
          'url' => $link,
          'entity_id' => [
            'target_id' => $entity->id(),
            'target_type' => $entity->getEntityTypeId(),
          ],
          'entity_field' => 'nid', // this has to be anything non-empty, otherwise results will get duplicated
          'entity_langcode' => $item->getLanguage(),
        ]);
      }
    }

    return $links;
  }

}
