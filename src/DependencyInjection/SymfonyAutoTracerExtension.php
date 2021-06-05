<?php

/*
 * This file is part of the AutoTracerBundle package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Mindbreak\AutoTracerBundle\DependencyInjection;

use Exception;
use Mindbreak\AutoTracerBundle\Tracing\Tracer;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

class SymfonyAutoTracerExtension extends Extension
{
    /**
     * @throws Exception
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        /** @var Configuration $configuration */
        $configuration = $this->getConfiguration($configs, $container);
        $config        = $this->processConfiguration($configuration, $configs);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.xml');

        $container
            ->getDefinition(Tracer::class)
            ->setArgument('$serverName', $config['serverName'])
            ->setArgument('$agentHostPort', $config['agentHostPort'])
        ;
    }

    public function getAlias(): string
    {
        return 'symfony_auto_tracer';
    }
}
