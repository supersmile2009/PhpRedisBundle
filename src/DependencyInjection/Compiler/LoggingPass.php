<?php

namespace WebonautePhpredisBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class LoggingPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        foreach ($container->findTaggedServiceIds('webonaute_phpredis.connection_parameters') as $id => $attr) {
            $clientAlias = $attr[0]['clientAlias'];
            $parameterDefinition = $container->getDefinition($id);
            $parameters = $parameterDefinition->getArgument(0);
            if ($parameters['logging']) {
                $optionId = sprintf('webonaute_phpredis.client.%s_options', $clientAlias);
                $option = $container->getDefinition($optionId);
                if (1 < \count($option->getArguments())) {
                    throw new \RuntimeException('Please check the phpredis option arguments.');
                }
                $arguments = $option->getArgument(0);

                $connectionFactoryId = sprintf('webonaute_phpredis.%s_connectionfactory', $clientAlias);
                $connectionFactoryDef = new Definition($container->getParameter('webonaute_phpredis.connection_factory.class'));
                $connectionFactoryDef->setPublic(false);
                $connectionFactoryDef->addArgument(new Reference(sprintf('webonaute_phpredis.client.%s_profile', $clientAlias)));
                $connectionFactoryDef->addMethodCall('setConnectionWrapperClass', array($container->getParameter('webonaute_phpredis.connection_wrapper.class')));
                $connectionFactoryDef->addMethodCall('setLogger', array(new Reference('webonaute_phpredis.logger')));
                $container->setDefinition($connectionFactoryId, $connectionFactoryDef);

                $arguments['connections'] = new Reference($connectionFactoryId);
                $option->replaceArgument(0, $arguments);
            }
        }
    }
}
