<?php

namespace App\DataFixtures;

use App\Entity\ItemGroup;
use App\Entity\ItemGroupEntry;
use App\Entity\ItemPrototype;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

class FixtureHelper extends Fixture
{

    public static function createItemGroup( ObjectManager $manager, string $name, array $data ): ItemGroup {

        $group = $manager->getRepository(ItemGroup::class)->findOneBy(['name' => $name]);
        if (!$group) {
            $group = new ItemGroup();
            $group->setName( $name );
        }
        else $group->getEntries()->clear();

        foreach ($data as $entry) {
            $pt = $manager->getRepository(ItemPrototype::class)->findOneBy(['name' => $entry['item']]);
            $group->addEntry(
                (new ItemGroupEntry())
                    ->setChance( (int)$entry['count'] )
                    ->setPrototype( $pt )
            );
        }

        return $group;
    }


    public function load(ObjectManager $manager) {}
}
