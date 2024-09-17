<?php


namespace Drupal\remora_core\Twig\Parser;

use Drupal\remora_core\Twig\Node\RemoraIncludeNode;
use Twig\Error\SyntaxError;
use Twig\Node\Expression\ArrayExpression;
use Twig\Node\Expression\Binary\AbstractBinary;
use Twig\Node\Expression\Binary\ConcatBinary;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\GetAttrExpression;
use Twig\Node\Expression\NameExpression;
use Twig\Node\Expression\NullCoalesceExpression;
use Twig\Node\IncludeNode;
use Twig\Node\Node;
use Twig\Token;
use Twig\TokenParser\IncludeTokenParser;

/**
 * Adds the {% remora_include %} tag to Twig.
 * Will automatically try to include the following templates:
 *   - directory ~ path ~ '--' ~ bundle ~ '.html.twig'
 *   - '@module_name' ~ path ~ '.html.twig'
 *   - directory ~ path ~ '.html.twig'
 *   - '@remora_base_theme' ~ path ~ '.html.twig'
 *
 */
class RemoraIncludeNodeParser extends IncludeTokenParser
{
  private const FILE_EXT = '.html.twig';
  private const FALLBACK_THEME = '@remora_base_theme';

  /**
   * Gets the tag name associated with this token parser.
   *
   * @return string The tag name
   */
  public function getTag(): string
  {
    return 'remora_include';
  }

  /**
   * Parses a token and returns a node.
   *
   * @throws SyntaxError
   */
  public function parse(Token $token): Node
  {
    $expr = $this->parser->getExpressionParser()->parseExpression();
    [$variables, $only, $ignoreMissing] = $this->parseArguments();

    // only support string includes
    if (!$expr instanceof ConstantExpression) {
      // use the regular include, we don't want anything to do with non-string includes
      return new IncludeNode($expr, $variables, $only, $ignoreMissing, $token->getLine(), $this->getTag());
    }

    // get the template name being included
    $lineNo = $expr->getTemplateLine();
    $template = $expr->getAttribute('value');
    $path = explode('/', $template);
    $file = array_pop($path);
    $path = implode('/', $path);
    $filename = substr($file, 0, -strlen(self::FILE_EXT));

    // Determine the current template path to infer the module name
    $currentTemplate = $this->parser->getStream()->getSourceContext()->getPath();

    // Extract module name from path
    $moduleName = 'unknown_module'; // Default value if not found
    if (str_contains($currentTemplate, 'web/modules')) {
      $parts = explode('/templates/', $currentTemplate);
      if (isset($parts[0])) {
        $modulePath = $parts[0];
        $moduleParts = explode('/', $modulePath);
        $moduleName = end($moduleParts); // Get the last part before /templates
      }
    }

    $moduleNameExpr = new ConstantExpression($moduleName, $lineNo);

    // automatically try to include
    // directory ~ path ~ '--' ~ bundle ~ '.html.twig'
    // ct ~ '/templates/' ~ path ~ '/' ~ filename ~ '.html.twig'
    // '@module_name' ~ path ~ '.html.twig'
    // directory ~ path ~ '.html.twig'
    // '@remora_base_theme' ~ path ~ '.html.twig'
    $directoryExpr = new NameExpression('directory', $lineNo);
    $objectBeingRendered = new NullCoalesceExpression(
      new GetAttrExpression(new NameExpression('element', $lineNo), new ConstantExpression('#object', $lineNo), new ArrayExpression([], $lineNo), 'property', $lineNo),
      new GetAttrExpression(new NameExpression('elements', $lineNo), new ConstantExpression('#node', $lineNo), new ArrayExpression([], $lineNo), 'property', $lineNo),
      $lineNo
    );
    $objectBundle = new GetAttrExpression($objectBeingRendered, new ConstantExpression('bundle', $lineNo), new ArrayExpression([], $lineNo), 'method', $lineNo);

    $possibleNames = [
      // the subtheme's content type specific template
      new ConstantExpression(0, $lineNo),
      new ConcatBinary($directoryExpr,
        new ConcatBinary(
          new ConcatBinary(
            new ConstantExpression('/templates/' . $path . '/' . $filename . '--', $lineNo),
            $objectBundle,
            $lineNo),
          new ConstantExpression(self::FILE_EXT, $lineNo), $lineNo
        ),
        $lineNo),
      // the content type's template
      new ConstantExpression(1, $lineNo),
      new ConcatBinary(
        new ConstantExpression('@', $lineNo),
        new ConcatBinary(
          $objectBundle,
          new ConstantExpression('_content_type/templates/' . $path . '/' . $filename . self::FILE_EXT, $lineNo),
          $lineNo),
        $lineNo
      ),
      // the nugget's template
      new ConstantExpression(2, $lineNo),
      new ConcatBinary(
        new ConstantExpression('@', $lineNo),
        new ConcatBinary(
          $objectBundle,
          new ConstantExpression('_nugget/templates/' . $path . '/' . $filename . self::FILE_EXT, $lineNo),
          $lineNo),
        $lineNo
      ),
      // the module's template
      new ConstantExpression(5, $lineNo),
      new ConcatBinary(
        new ConcatBinary(
          new ConstantExpression('@', $lineNo),
          $moduleNameExpr,
          $lineNo
        ),
        new ConstantExpression('/' . $path . '/' . $filename . self::FILE_EXT, $lineNo),
        $lineNo
      ),
      // the subtheme's generic template
      new ConstantExpression(3, $lineNo),
      new ConcatBinary($directoryExpr, new ConstantExpression('/templates/' . $path . '/' . $filename . self::FILE_EXT, $lineNo), $lineNo),
      // the base theme's template
      new ConstantExpression(4, $lineNo),
      new ConstantExpression(self::FALLBACK_THEME . '/' . $path . '/' . $filename . self::FILE_EXT, $lineNo),
    ];

    return new RemoraIncludeNode(
      new ArrayExpression($possibleNames, $lineNo),
      $variables,
      $only,
      $ignoreMissing,
      $token->getLine(),
      $this->getTag(),
    );
  }
}

