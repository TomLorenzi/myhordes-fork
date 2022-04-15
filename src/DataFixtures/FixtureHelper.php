<?php

namespace App\DataFixtures;

use App\Entity\ItemGroup;
use App\Entity\ItemGroupEntry;
use App\Entity\ItemPrototype;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class FixtureHelper extends Fixture
{

    public static function createItemGroup( ObjectManager $manager, string $name, array $data ): ItemGroup {

        $group = $manager->getRepository(ItemGroup::class)->findOneBy(['name' => $name]);
        if (!$group) {
            $group = new ItemGroup();
            $group->setName( $name );
        }
        else $group->getEntries()->clear();

        foreach ($data as $key => $entry) {
            if (is_array($entry))
                list($name,$count) = [$entry['item'],$entry['count']];
            else list($name,$count) = [$key,$entry];

            $pt = $manager->getRepository(ItemPrototype::class)->findOneBy(['name' => $name]);
            if ($pt === null) throw new \Exception("Cannot locate item prototype '$name'!");
            $group->addEntry(
                (new ItemGroupEntry())
                    ->setChance( (int)$count )
                    ->setPrototype( $pt )
            );
        }

        return $group;
    }


    public function load(ObjectManager $manager) {}
}
