<?php

namespace MyHordes\Fixtures\DependencyInjection\Compiler;

use MyHordes\Fixtures\Fixtures\AwardFeature;
use MyHordes\Fixtures\Fixtures\AwardIcon;
use MyHordes\Fixtures\Fixtures\AwardTitle;
use MyHordes\Fixtures\Fixtures\RolePlayText;
use MyHordes\Fixtures\Fixtures\Ruin;
use MyHordes\Fixtures\Fixtures\RuinRoom;
use MyHordes\Fixtures\Fixtures\Town;
use MyHordes\Fixtures\Fixtures\ZoneTag;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class FixtureChain implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        // List of interfaces
        $interfaces = [
            Town::class             => 'myhordes.fixtures.town',
            RolePlayText::class     => 'myhordes.fixtures.rp_texts',
            AwardTitle::class       => 'myhordes.fixtures.awards.titles',
            AwardIcon::class        => 'myhordes.fixtures.awards.icons',
            AwardFeature::class     => 'myhordes.fixtures.awards.features',
            Ruin::class             => 'myhordes.fixtures.ruins.data',
            RuinRoom::class         => 'myhordes.fixtures.ruins.rooms',
            ZoneTag::class          => 'myhordes.fixtures.zones.tags',
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