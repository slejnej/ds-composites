<?php

namespace Drupal\remora_core\Manager;

use AppendIterator;
use DOMDocument;
use Drupal;
use Drupal\Core\File\FileSystemInterface;
use Exception;
use Generator;
use Psr\Log\LoggerInterface;
use RuntimeException;
use SimpleXMLElement;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Combines a submodules config/remora/build.xml with the project's build.xml
 */
class AntManager
{

  public function __construct(
    #[Autowire(service: 'file_system')] private readonly FileSystemInterface $fileSystem,
    #[Autowire(service: 'remora_core.logger')] private readonly LoggerInterface $logger
  )
  {
  }

  /**
   * Adds a build step to `build.xml` for the given module.
   *
   * @param string $moduleName
   * @return bool True if the step was added even if an error occurred, false otherwise
   * @throws Exception
   */
  public function addSteps(string $moduleName): bool
  {
    $projectXmlPath = $this->fileSystem->realpath(Drupal::root() . '/../build.xml');
    $moduleXmlPath = $this->fileSystem->realpath("$moduleName://config/remora/build.xml");

    if(Drupal::isConfigSyncing()) {
      return true;
    }

    if(!$projectXmlPath || !$moduleXmlPath) {
      $this->logger->error("Missing build.xml. Project: @project - Module: @module", [
        '@project' => $projectXmlPath,
        '@module' => $moduleXmlPath,
      ]);
      return false;
    }

    try {
      $projectXml = (new SimpleXMLElement($projectXmlPath, dataIsURL: true))->xpath('/project')[0];;
      $moduleXml = (new SimpleXMLElement($moduleXmlPath,  dataIsURL: true))->xpath('/module')[0];

      $this->combineXMLs($projectXml, $moduleXml);
    } catch(RuntimeException $e) {
      $this->logger->error($e);
      return false;
    }

    $dom = new DOMDocument('1.0');
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;
    $dom->loadXML($projectXml->asXML());
    $dom->save($projectXmlPath);

    return true;
  }

  /**
   * Returns all tags with the given selector (name) that contain a name attribute and aren't already present in the project's XML
   *
   * @param SimpleXMLElement $projectXml The project's build.xml
   * @param SimpleXMLElement $moduleXml The module's build.xml
   * @param string $selector The tag name to find all valid instances of
   * @return Generator<SimpleXMLElement>
   */
  private function yieldValidTags(SimpleXMLElement $projectXml, SimpleXMLElement $moduleXml, string $selector): Generator
  {
    foreach($moduleXml->xpath("/module/$selector") as $tag) {
      $name = (string) ($tag->attributes()['name'] ?? '');

      if(!isset($name)) {
        $this->logger->error('Tag property in build.xml: @tag', [
          '@tag' => $tag->asXML(),
        ]);
        continue;
      }

      $isInProjectXml = count($projectXml->xpath(sprintf('//%s[@name="%s"]', $selector, $name))) > 0;
      if($isInProjectXml) {
        $this->logger->error('Tag is already present in build.xml: @tag', [
          '@tag' => $tag->asXML(),
        ]);
        continue;
      }

      yield $tag;
    }

  }

  /**
   * Adds the child SimpleXMLElement recursively to the parent SimpleXMLElement
   *
   * @param SimpleXMLElement $parent
   * @param SimpleXMLElement $child
   * @return void
   */
  private function addChild(SimpleXMLElement $parent, SimpleXMLElement $child): void
  {
    $newChild = $parent->addChild($child->getName());

    foreach($child->attributes() as $attribute) {
      $newChild->addAttribute($attribute->getName(), (string) $attribute);
    }

    foreach($child->children() as $childsChild) {
      $this->addChild($newChild, $childsChild);
    }
  }

  /**
   * Adds the module's properties and targets to the project's build.xml
   *
   * @param mixed $projectXml
   * @param mixed $moduleXml
   * @return void
   */
  private function combineXMLs(mixed $projectXml, mixed $moduleXml): void
  {

    $tagGenerator = (function() use($projectXml, $moduleXml) {
      yield from $this->yieldValidTags($projectXml, $moduleXml, 'property');
      yield from $this->yieldValidTags($projectXml, $moduleXml, 'target');
    })();

    /** @var SimpleXMLElement $tag */
    foreach($tagGenerator as $tag) {
      $this->addChild($projectXml, $tag);

      // add targets to "assets" step
      if($tag->getName() === 'target') {
        $projectXml->xpath('//target[@name="assets"]')[0]->attributes()['depends'] .= ", " . $tag->attributes()['name'];
      }
    }
  }
}
