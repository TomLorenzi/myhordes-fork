<?php

namespace MyHordes\Fixtures\DependencyInjection\Compiler;

use MyHordes\Fixtures\Fixtures\Town;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class FixtureChain implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        // List of interfaces
        $interfaces = [
            Town::class => 'myhordes.fixtures.town',
        ];


        foreach ($interfaces as $class => $tag) {
            // always first check if the service is defined
            if (!$container->has($class)) return;

            // Load definition
            $definition = $container->findDefinition($class);

            // find all service IDs with the tag
            $taggedServices = $container->findTaggedServiceIds($tag);

            foreach ($taggedServices as $id => $tags)
                // add the transport service to the TransportChain service
                $definition->addMethodCall('addProcessor', [new Reference($id)]);
        }
    }
}