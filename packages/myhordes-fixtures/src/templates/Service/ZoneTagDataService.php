<?php

namespace MyHordes\Fixtures\Service;

use App\Entity\ZoneTag;
use MyHordes\Plugins\Interfaces\FixtureProcessorInterface;

class ZoneTagDataService implements FixtureProcessorInterface {

    public function process(array &$data, ?string $tag = null): void
    {
        $data = array_replace_recursive($data, [
            'none' => [
                'label' => '[nichts]',
                'icon' => '',
                'ref' => ZoneTag::TagNone,
                'temp' => false],
            'help' => [
                'label' => 'Notruf',
                'icon' => 'tag_1',
                'ref' => ZoneTag::TagHelp,
                'temp' => false],
            'resources' => [
                'label' => 'Rohstoff am Boden (Holz, Metall...)',
                'icon' => 'tag_2',
                'ref' => ZoneTag::TagResource,
                'temp' => false],
            'items' => [
                'label' => 'Verschiedene Gegenstände am Boden',
                'icon' => 'tag_3',
                'ref' => ZoneTag::TagItems,
                'temp' => false],
            'impItem' => [
                'label' => 'Wichtige(r) Gegenstand/-ände!',
                'icon' => 'tag_4',
                'ref' => ZoneTag::TagImportantItems,
                'temp' => false],
            'depleted' => [
                'label' => 'Zone leer',
                'icon' => 'tag_5',
                'ref' => ZoneTag::TagDepleted,
                'temp' => false],
            'tempSecure' => [
                'label' => 'Zone tempörar gesichert',
                'icon' => 'tag_6',
                'ref' => ZoneTag::TagTempSecured,
                'temp' => false],
            'needDig' => [
                'label' => 'Zone muss freigeräumt werden',
                'icon' => 'tag_7',
                'ref' => ZoneTag::TagRuinDig,
                'temp' => false],
            '5to8zeds' => [
                'label' => 'Zwichen 5 und 8 Zombies',
                'icon' => 'tag_8',
                'ref' => ZoneTag::Tag5To8Zombies,
                'temp' => false],
            '9zeds' => [
                'label' => '9 oder mehr Zombies!',
                'icon' => 'tag_9',
                'ref' => ZoneTag::Tag9OrMoreZombies,
                'temp' => false],
            'camping' => [
                'label' => 'Camping geplant',
                'icon' => 'tag_10',
                'ref' => ZoneTag::TagCamping,
                'temp' => true],
            'exploreRuin' => [
                'label' => 'Zu untersuchende Ruine',
                'icon' => 'tag_11',
                'ref' => ZoneTag::TagExploreRuin,
                'temp' => false],
            'soul' => [
                'label' => 'Verlorene Seele',
                'icon' => 'tag_12',
                'ref' => ZoneTag::TagLostSoul,
                'temp' => false],
        ]);
    }
}