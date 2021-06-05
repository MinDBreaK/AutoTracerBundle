<?php

/*
 * This file is part of the AutoTracerBundle package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Mindbreak\AutoTracerBundle;

use Mindbreak\AutoTracerBundle\DependencyInjection\Pass\DoctrinePass;
use Mindbreak\AutoTracerBundle\DependencyInjection\SymfonyAutoTracerExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class SymfonyAutoTracerBundle extends Bundle
{
    /**
     * @var ExtensionInterface|null
     *
     * @psalm-suppress NonInvariantDocblockPropertyType
     */
    protected $extension;

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new DoctrinePass());
    }

    public function getContainerExtension(): ExtensionInterface
    {
        if (null === $this->extension) {
            $this->extension = new SymfonyAutoTracerExtension();
        }

        return $this->extension;
    }
}
