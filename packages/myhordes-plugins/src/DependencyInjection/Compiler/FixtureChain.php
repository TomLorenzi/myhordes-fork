<?php

namespace MyHordes\Plugins\DependencyInjection\Compiler;

use App\Entity\Hook;
use MyHordes\Plugins\Fixtures\Action;
use MyHordes\Plugins\Fixtures\AwardFeature;
use MyHordes\Plugins\Fixtures\AwardIcon;
use MyHordes\Plugins\Fixtures\AwardTitle;
use MyHordes\Plugins\Fixtures\Building;
use MyHordes\Plugins\Fixtures\CitizenComplaint;
use MyHordes\Plugins\Fixtures\CitizenDeath;
use MyHordes\Plugins\Fixtures\CitizenHomeLevel;
use MyHordes\Plugins\Fixtures\CitizenHomeUpgrade;
use MyHordes\Plugins\Fixtures\CitizenNotificationMarker;
use MyHordes\Plugins\Fixtures\CitizenProfession;
use MyHordes\Plugins\Fixtures\CitizenRole;
use MyHordes\Plugins\Fixtures\CitizenStatus;
use MyHordes\Plugins\Fixtures\CouncilEntry;
use MyHordes\Plugins\Fixtures\Emote;
use MyHordes\Plugins\Fixtures\ForumThreadTag;
use MyHordes\Plugins\Fixtures\GazetteEntry;
use MyHordes\Plugins\Fixtures\HeroSkill;
use MyHordes\Plugins\Fixtures\HookData;
use MyHordes\Plugins\Fixtures\HordesFact;
use MyHordes\Plugins\Fixtures\Item;
use MyHordes\Plugins\Fixtures\ItemCategory;
use MyHordes\Plugins\Fixtures\ItemGroup;
use MyHordes\Plugins\Fixtures\ItemProperty;
use MyHordes\Plugins\Fixtures\Log;
use MyHordes\Plugins\Fixtures\Permission;
use MyHordes\Plugins\Fixtures\Picto;
use MyHordes\Plugins\Fixtures\Quote;
use MyHordes\Plugins\Fixtures\Recipe;
use MyHordes\Plugins\Fixtures\RolePlayText;
use MyHordes\Plugins\Fixtures\Ruin;
use MyHordes\Plugins\Fixtures\RuinRoom;
use MyHordes\Plugins\Fixtures\Town;
use MyHordes\Plugins\Fixtures\ZoneTag;
use MyHordes\Plugins\Management\FixtureSourceLookup;
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
            Building::class                  => 'myhordes.fixtures.buildings',
			HookData::class					 => 'myhordes.fixtures.hooks',
            Recipe::class                    => 'myhordes.fixtures.recipes',
            Quote::class                     => 'myhordes.fixtures.quotes',
            Picto::class                     => 'myhordes.fixtures.pictos',
            Permission::class                => 'myhordes.fixtures.permissions',
            Log::class                       => 'myhordes.fixtures.logs',
            Action::class                    => 'myhordes.fixtures.actions',
            Item::class                      => 'myhordes.fixtures.items.list',
            ItemCategory::class              => 'myhordes.fixtures.items.categories',
            ItemGroup::class                 => 'myhordes.fixtures.items.groups',
            ItemProperty::class              => 'myhordes.fixtures.items.properties',
        ];

        // Compendium
        $compendium = $container->findDefinition( FixtureSourceLookup::class );

        foreach ($interfaces as $class => $tag) {
            // always first check if the service is defined
            if (!$container->has($class)) return;

            // Load definition
            $definition = $container->findDefinition($class);

            // find all service IDs with the tag
            $taggedServices = $container->findTaggedServiceIds($tag);

            foreach ($taggedServices as $id => $tags) {
                // add the transport service to the TransportChain service
                $definition->addMethodCall('addProcessor', [new Reference($id), $id, $tag]);
                $compendium->addMethodCall( 'addEntry', [ $class, $id, $tag ] );
            }
        }
    }
}