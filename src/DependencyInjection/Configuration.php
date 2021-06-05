<?php

/*
 * This file is part of the AutoTracerBundle package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Mindbreak\AutoTracerBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public const ROOT_NAME = 'mindbreak_auto_tracer';

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder(self::ROOT_NAME);
        $root        = $treeBuilder->getRootNode();
        /**
         * @psalm-suppress PossiblyUndefinedMethod
         * @psalm-suppress MixedMethodCall
         */
        $root
            ->children()
                ->scalarNode('serverName')
                    ->isRequired()
                    ->cannotBeEmpty()
                    ->example('php-application')
                ->end()
                ->scalarNode('agentHostPort')
                    ->isRequired()
                    ->example('jaeger:5775')
                    ->cannotBeEmpty()
                ->end()
                ->arrayNode('doctrine')
                    ->children()
                        ->booleanNode('traceArgs')
                            ->defaultFalse()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
        return $treeBuilder;
    }
}
