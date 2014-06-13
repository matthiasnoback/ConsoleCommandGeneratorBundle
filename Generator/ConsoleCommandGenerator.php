<?php

namespace Matthias\Bundle\ConsoleCommandGeneratorBundle\Generator;

use Sensio\Bundle\GeneratorBundle\Generator\Generator;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

class ConsoleCommandGenerator extends Generator
{
    public function generate(BundleInterface $bundle, $command, $class, array $arguments)
    {
        $target = $bundle->getPath() . '/Command/' . $class . '.php';

        $this->renderFile(
            'Command.php.twig',
            $target,
            array(
                'namespace' => $bundle->getNamespace() . '\Command',
                'command' => $command,
                'class' => $class,
                'arguments' => $arguments
            )
        );
    }
}
