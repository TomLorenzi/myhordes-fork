<?php

namespace App\DataFixtures;

use App\Entity\ItemGroup;
use App\Entity\ItemGroupEntry;
use App\Entity\ItemPrototype;
use App\Enum\DropMod;
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
            if (is_array($entry) && isset($entry['item']))
                list($name,$count,$mod) = [$entry['item'],$entry['count'],$entry['mod'] ?? DropMod::None];
            elseif (is_array($entry))
                list($name,$count,$mod) = [$key,$entry[0],$entry[1]];
            else list($name,$count,$mod) = [$key,$entry,DropMod::None];

            if (is_int($mod)) $mod = DropMod::from( $mod );

            $pt = $manager->getRepository(ItemPrototype::class)->findOneBy(['name' => $name]);
            if ($pt === null) throw new \Exception("Cannot locate item prototype '$name'!");
            $group->addEntry(
                (new ItemGroupEntry())
                    ->setChance( (int)$count )
                    ->setModContent($mod)
                    ->setPrototype( $pt )
            );
        }

        return $group;
    }


    public function load(ObjectManager $manager) {}
}
