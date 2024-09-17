<?php

namespace Drupal\installation_profile\Manager;

use Drupal;
use Drupal\Core\Serialization\Yaml;
use Drupal\installation_profile\Util\ArrayUtil;
use JetBrains\PhpStorm\ArrayShape;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Inflector\EnglishInflector;
use Symfony\Component\String\Inflector\InflectorInterface;

class FigmaSassManager
{

  /** @var string The default palette name */
  private const DEFAULT_PALETTE = 'global';
  private const DEFAULT_UNIT = 'px';
  /** @var array Contains all the SCSS variables by palette */
  private array $resultingVariables = [];
  /** @var array Config loaded from the YAML file */
  private readonly array $config;
  /** @var InflectorInterface Inflector used to singularize words */
  private readonly InflectorInterface $inflector;

  /**
   * Processes the provided Figma JSON files
   *
   * @param array $files
   */
  public function __construct(
    #[ArrayShape(['string' => 'mixed[]'])]
    private readonly array $files)
  {
    /** @var Drupal\Core\File\FileSystemInterface $fs */
    $fs = Drupal::service('file_system');
    $this->inflector = new EnglishInflector();

    // read the config from private folder, or from this module if private one doesn't exist
    $configPath = $fs->realpath('private://figma_config.yml');
    if(!file_exists($configPath)) {
      $configPath = $fs->realpath('installation_profile://config/figma_config.yml');
    }

    $configContents = file_get_contents($configPath);
    $this->config = Yaml::decode($configContents)['figma_prefixes'];

    $this->process();
  }

  /**
   * Returns an array of SCSS files, indexed by their filename (without suffix)
   *
   * @return string[] Formatted as ['alt-1' => '...', ...]
   */
  public function toScss(): array
  {
    $result = [];
    foreach($this->resultingVariables as $key => $variables) {
      $scss = (in_array($key, ['components', 'global'])) ? $this->generateScssRecursively($variables) : $this->generateScssRecursively($variables, $key.'-');
      if($key !== 'global') {
        $scss = "@import 'global';" . $scss;
      }

      $result[$key] = ltrim($scss, PHP_EOL);
    }

    return $result;
  }

  /**
   * Create a new FigmaSassManager from an array of uploaded files
   *
   * @param UploadedFile[] $figmaFiles
   * @return self
   */
  public static function fromUploadedFile(array $figmaFiles): self
  {
    $files = [];

    foreach($figmaFiles as $file) {
      $files[$file->getClientOriginalName()] = json_decode(file_get_contents($file->getRealPath()), true);
    }

    return new self($files);
  }

  /**
   * Generates the SCSS for a palette recursively
   *
   * @param array $variables The SCSS variables to process
   * @param string $prefix The group's prefix, e.g. buttons-, accordion-
   * @return string An SCSS file
   */
  private function generateScssRecursively(array $variables, string $prefix = ''): string
  {
    $result = '';

    // loop over all the variables
    foreach($variables as $key => $variable) {

      // if this group contains more variables, add a comment to denote we are entering a group and then process the group
      if(is_array($variable)) {
        $result .= sprintf('%2$s// %s%2$s', $key, PHP_EOL);
        $key = $prefix . $this->sanitizeVariableName($key);
        if(!empty($key)) {
          $key .= '-';
        }

        $result .= $this->generateScssRecursively($variable, $key);
        // else, parse the variable and add it to the SCSS
      } else {
        // covert the entire variable name to lowercase and replace any non-alphanumeric values
        $key = strtolower($prefix . $key);
        $key = preg_replace('/[^\w-]/', '-', $key);
        $key = $this->dedupeVariable($key);

        $result .= sprintf('$%s: %s;%s', $key, $variable, PHP_EOL);
      }
    }

    return $result;
  }

  /**
   * Processes the Figma JSON files and parses them into a semi-accessible array
   *
   * @return void All changes are made in-place and written to self::$resultingVariables
   * @see self::$resultingVariables
   */
  private function process(): void
  {
    foreach($this->files as $filename => $contents) {
      $palette = self::DEFAULT_PALETTE;

      if(preg_match('/([^.]*)\.[^.]*\.json/', $filename, $matches) === 1) {
        $palette = $matches[1];
      }

      // add overrides if they're any exist for this palette
      if(isset($this->config['overrides'][$palette])) {
        $contents = array_merge($contents, $this->config['overrides'][$palette]);
      }

      $resultArr = &$this->resultingVariables[$palette];
      $this->processFile($contents);
      $resultArr = array_merge($resultArr ?? [], $contents);
    }

    // replace all variable keywords with their value and sort them usage-wise
    foreach($this->resultingVariables as &$vars) {
      $this->replaceVariables($vars);
      $this->sortDependencies($vars);
    }

  }

  /**
   * Parses a Figma file, extracts all the variables but keeps the structure intact
   *
   * @param array $variables The figma file to parse
   * @return void Values are replaced in-place
   */
  private function processFile(array &$variables): void
  {
    foreach($variables as &$group) {
      foreach($group as &$variable) {
        if(isset($variable['$type'])) {
          $variable = $this->processVariable($variable);
        } else {
          // need to wrap this in an array so the group is processed properly
          $arr = [&$variable];
          $this->processFile($arr);
        }
      }
    }
  }

  /**
   * Process a single variable and returns its value
   *
   * @param array $variableContents
   * @return string|float|int
   */
  private function processVariable(array $variableContents): string|float|int
  {
    return $variableContents['$value'];
  }

  /**
   * Recursively replace all the variables for the given palette with their actual values
   *
   * @param array $values The values for this palette
   * @return void Values are replaced in-place
   */
  private function replaceVariables(array &$values): void
  {
    foreach($values as &$value) {
      if(is_array($value)) {
        $this->replaceVariables($value);
      } else {
        $value = $this->resolveVariable($value);
      }
    }
  }

  /**
   * Returns a variable's function from its path and the palette
   * If the palette does not contain the variable, the default palette will be used
   *
   * @param string $variable The variable key (e.g. global-color.primary.primary-100)
   * @return string|float|int The variable's value
   */
  private function resolveVariable(string $variable): string|float|int
  {
    // if the value doesn't match {xxx} format, return the value assuming it's a number/color
    if(preg_match('/^\{([a-z0-9.-]+)}$/i', $variable, $matches) !== 1) {
      // add the unit to numeric values
      if(is_numeric($variable)) {
        $variable .= self::DEFAULT_UNIT;
      }

      return $variable;
    }

    // a variable can reference another variable 🤯
    // essentially turns {global-color.primary.primary-100} into $global-color-primary-primary-100; for SCSS
    // split the variable into its parts
    $variable = explode('.', $matches[1]);
    $variable = array_map([$this, 'sanitizeVariableName'], $variable);

    // remove any chunks that are now empty through mapping or whatevs
    $variable = array_filter($variable, fn(string $x) => $x !== '');

    // join all the parts together again
    $variable = implode('-', $variable);
    $variable = $this->dedupeVariable($variable);

    return sprintf('$%s', $variable);
  }

  /**
   * Dedupes a variable name if enabled in config and replacing -- with -
   * Else only replacing -- with -
   *
   * @param string $variable The variable name to dedupe
   * @return string The dedupe variable
   */
  private function dedupeVariable(string $variable): string
  {
    // if we want to dedupe variables, e.g. prevent $card-card-border, run it
    if($this->config['config']['dedupe'] === true) {
      $variable = preg_replace('/^([^-]+?-)\1+/', '$1', $variable);
    }

    return preg_replace('/--+/', '-', $variable);
  }

  /**
   * Sanitizes a variable name by mapping it if applicable
   * Singularizing the variable if enabled in config
   *
   * @param string $x The string to sanitize
   * @return string The sanitized string
   */
  private function sanitizeVariableName(string $x): string
  {
    if(isset($this->config['mapping'][$x])) {
      return $this->config['mapping'][$x];
    } else if($this->config['config']['singularize']) {
      return $this->inflector->singularize($x)[0];
    }

    return $x;
  }

  /**
   * Sorts the variable groups from most to least used
   *
   * @param array $vars
   * @return void
   */
  private function sortDependencies(array &$vars): void
  {
    // sort the variables from most mentioned by peers to least mentioned
    uksort($vars, function(string $a, string $b) use($vars) {
      // get all the keys and value flattened for both entries
      $flatA = ArrayUtil::flattenKeysValues($vars[$a]);
      $flatB = ArrayUtil::flattenkeysValues($vars[$b]);

      // create a regex with every key
      $aRegex = "/^\\\$.*(".implode('|', $flatA['keys']).')$/';
      $bRegex = "/^\\\$.*(".implode('|', $flatB['keys']).')$/';

      // how often does A mentioned by B
      $variablesMentionedA = count(array_filter($flatA['values'], fn(string $x) => preg_match($bRegex, $x) === 1));
      // how often does B mentioned by A
      $variablesMentionedB = count(array_filter($flatB['values'], fn(string $x) => preg_match($aRegex, $x) === 1));
      $cmp = $variablesMentionedA <=> $variablesMentionedB;

      // if both groups mention each other equally as little (probs 0), promote global to the top
      if($cmp === 0) {
        $cmp = str_starts_with($b, 'global-') <=> str_starts_with($a, 'global-');
      }

      // sort whoever mentions whom more
      return $cmp;
    });
  }
}
