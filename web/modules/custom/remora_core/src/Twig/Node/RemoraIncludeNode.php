<?php

namespace Drupal\remora_core\Twig\Node;

use Twig\Compiler;
use Twig\Node\IncludeNode;

class RemoraIncludeNode extends IncludeNode
{

  /**
   * Compiles the page template
   * Adds a comment saying which template is being used before the parent does all the smart stuff
   *
   * @param Compiler $compiler
   * @return void
   */
  protected function addGetTemplate(Compiler $compiler): void
  {
    if($compiler->getEnvironment()->isDebug()) {
      // add a comment saying which template is being used
      $compiler
        ->write('echo "<!-- REMORA INCLUDE: " . $this->env->resolveTemplate(')
        ->subcompile($this->getNode('expr'))
        ->raw(')->getTemplateName() . " -->";')
      ;
    }

    parent::addGetTemplate($compiler);
  }

}
