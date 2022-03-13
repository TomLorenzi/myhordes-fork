<?php

namespace MyHordes\Fixtures\DependencyInjection\Compiler;

use MyHordes\Fixtures\Fixtures\AwardFeature;
use MyHordes\Fixtures\Fixtures\AwardIcon;
use MyHordes\Fixtures\Fixtures\AwardTitle;
use MyHordes\Fixtures\Fixtures\CitizenComplaint;
use MyHordes\Fixtures\Fixtures\CitizenDeath;
use MyHordes\Fixtures\Fixtures\CitizenHomeLevel;
use MyHordes\Fixtures\Fixtures\CitizenHomeUpgrade;
use MyHordes\Fixtures\Fixtures\CitizenNotificationMarker;
use MyHordes\Fixtures\Fixtures\CitizenProfession;
use MyHordes\Fixtures\Fixtures\CitizenRole;
use MyHordes\Fixtures\Fixtures\CitizenStatus;
use MyHordes\Fixtures\Fixtures\CouncilEntry;
use MyHordes\Fixtures\Fixtures\Emote;
use MyHordes\Fixtures\Fixtures\ForumThreadTag;
use MyHordes\Fixtures\Fixtures\GazetteEntry;
use MyHordes\Fixtures\Fixtures\HeroSkill;
use MyHordes\Fixtures\Fixtures\HordesFact;
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
            Town::class                      => 'myhordes.fixtures.town',
            RolePlayText::class              => 'myhordes.fixtures.rp_texts',
            AwardTitle::class                => 'myhordes.fixtures.awards.titles',
            AwardIcon::class                 => 'myhordes.fixtures.awards.icons',
            AwardFeature::class              => 'myhordes.fixtures.awards.features',
            Ruin::class                      => 'myhordes.fixtures.ruins.data',
            RuinRoom::class                  => 'myhordes.fixtures.ruins.rooms',
            ZoneTag::class                   => 'myhordes.fixtures.zones.tags',
            Emote::class                     => 'myhordes.fixtures.emotes',
            ForumThreadTag::class            => 'myhordes.fixtures.forum.thread.tags',
            CitizenProfession::class         => 'myhordes.fixtures.citizen.professions',
            CitizenStatus::class             => 'myhordes.fixtures.citizen.status',
            CitizenRole::class               => 'myhordes.fixtures.citizen.roles',
            CitizenNotificationMarker::class => 'myhordes.fixtures.citizen.notifications',
            CitizenDeath::class              => 'myhordes.fixtures.citizen.deaths',
            CitizenHomeLevel::class          => 'myhordes.fixtures.citizen.home.levels',
            CitizenHomeUpgrade::class        => 'myhordes.fixtures.citizen.home.upgrades',
            CitizenComplaint::class          => 'myhordes.fixtures.citizen.complaints',
            GazetteEntry::class              => 'myhordes.fixtures.gazettes',
            CouncilEntry::class              => 'myhordes.fixtures.councils',
            HeroSkill::class                 => 'myhordes.fixtures.heroskills',
            HordesFact::class                => 'myhordes.fixtures.hordesfact',
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