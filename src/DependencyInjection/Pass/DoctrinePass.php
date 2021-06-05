<?php

/*
 * This file is part of the AutoTracerBundle package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Mindbreak\AutoTracerBundle\DependencyInjection\Pass;

use Doctrine\DBAL\Logging\LoggerChain;
use Mindbreak\AutoTracerBundle\DependencyInjection\Configuration;
use Mindbreak\AutoTracerBundle\Doctrine\DoctrineTracer;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class DoctrinePass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $bundleConfig   = $this->getConfig($container);
        $configurations = $this->getConnectionConfigurations($container);
        $loggers        = $this->getLoggers($bundleConfig);

        foreach ($configurations as $name => $configuration) {
            // Check if loggers is already set and merge with those.
            /** @psalm-suppress MixedArgumentTypeCoercion */
            if (null !== ($methodsCall = $this->getSqlLoggerMethodCall($configuration->getMethodCalls()))) {
                $newLoggers = array_merge([], $loggers, $methodsCall[1]);
                $configuration->removeMethodCall('setSQLLogger');
            }

            $chainLogger   = new Definition(LoggerChain::class, ['$loggers' => $newLoggers ?? $loggers]);
            $chainLoggerId = 'mindbreak.doctrine.dbal.logger.chain.' . $name;
            $container->setDefinition($chainLoggerId, $chainLogger);

            $configuration->addMethodCall('setSQLLogger', [new Reference($chainLoggerId)]);
        }
    }

    /**
     * @param ContainerBuilder $container
     *
     * @return array<Definition>
     */
    private function getConnectionConfigurations(ContainerBuilder $container): array
    {
        $configurations = [];

        /** @var string $name */
        foreach ($container->getDefinitions() as $name => $definition) {
            if (null !== ($connectionName = $this->getConnectionName($name, $definition))) {
                $configurations[$connectionName] = $definition;
            }
        }

        return $configurations;
    }

    private function getConnectionName(string $name, Definition $definition): ?string
    {
        if ($definition instanceof ChildDefinition && $definition->getParent() === 'doctrine.dbal.connection.configuration') {
            preg_match('/^doctrine\.dbal\.(.+)_connection\.configuration$/', $name, $m);

            return $m[1] ?? null;
        }

        return null;
    }

    /**
     * @param array{0: string, 1: array}[] $methodCalls
     *
     * @return array{0: string, 1: array}|null
     */
    private function getSqlLoggerMethodCall(array $methodCalls): ?array
    {
        foreach ($methodCalls as $methodCall) {
            if ($methodCall[0] === 'setSQLLogger') {
                return $methodCall;
            }
        }

        return null;
    }

    private function getLoggers(array $config): array
    {
        $loggers = [];
        $tracer  = (new Definition(DoctrineTracer::class))
            ->setAutoconfigured(true)
            ->setAutowired(true)
        ;

        if ($config['doctrine']['traceArgs'] ?? false) {
            $tracer->setArgument('$traceArguments', true);
        }

        $loggers[] = $tracer;

        return $loggers;
    }

    private function getConfig(ContainerBuilder $container): array
    {
        /** @var array $resolvedExtensionConfig */
        $resolvedExtensionConfig = $container->resolveEnvPlaceholders(
            $container->getExtensionConfig(Configuration::ROOT_NAME),
            true
        );

        return $this->processConfiguration(new Configuration(), $resolvedExtensionConfig);
    }

    private function processConfiguration(ConfigurationInterface $configuration, array $configs): array
    {
        return (new Processor())->processConfiguration($configuration, $configs);
    }
}
