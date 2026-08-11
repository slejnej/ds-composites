<?php

namespace Drupal\remora_core\Twig\Node;

use Twig\Attribute\YieldReady;
use Twig\Compiler;
use Twig\Node\IncludeNode;

#[YieldReady]
class RemoraIncludeNode extends IncludeNode
{

  /**
   * Compiles the page template
   * Adds a comment saying which template is being used before the parent does all the smart stuff
   *
   * @param Compiler $compiler
   * @return void
   */
  public function compile(Compiler $compiler): void
  {
    $isDebug = $compiler->getEnvironment()->isDebug();
    if($isDebug) {
      // add a comment saying which template is being used
      $compiler
        ->write('yield from ["<!-- BEGIN REMORA INCLUDE: " . $this->env->resolveTemplate(')
        ->subcompile($this->getNode('expr'))
        ->write(
          ')->getTemplateName() . " -->"];' . PHP_EOL
        );
    }

    parent::compile($compiler);

    if($isDebug) {
      // add a comment saying which template is being used
      $compiler
        ->write('yield from ["<!-- END REMORA INCLUDE: " . $this->env->resolveTemplate(')
        ->subcompile($this->getNode('expr'))
        ->write(
          ')->getTemplateName() . " -->"];' . PHP_EOL
        );
    }
  }

}
