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
use MyHordes\Plugins\Management\ConfigurationStorage;
use MyHordes\Plugins\Management\FixtureSourceLookup;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ConfigAssembly implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        // Load definition
        $definition = $container->findDefinition(ConfigurationStorage::class);

        $sections = [
            'rules',
            'myhordes',
            'events'
        ];

        // find all service IDs with the tag
        foreach ($sections as $section) {
            $taggedServices = $container->findTaggedServiceIds("myhordes.configuration.$section");
            foreach ($taggedServices as $id => $tags)
                $definition->addMethodCall('addSegment', [$section,new Reference($id)]);
        }
    }
}