<?php


namespace App\Service;


use App\Entity\Item;
use App\Entity\ItemPrototype;
use Doctrine\ORM\EntityManagerInterface;

class ItemFactory
{
    private $entity_manager;

    public function __construct(EntityManagerInterface $em)
    {
        $this->entity_manager = $em;
    }

    /**
     * @param string|ItemPrototype $prototype
     * @param bool $broken
     * @param bool $poison
     * @return Item|null
     */
    public function createItem( $prototype, bool $broken = false, bool $poison = false ) {

        $prototype = is_string( $prototype )
            ? $this->entity_manager->getRepository( ItemPrototype::class )->findOneByName( $prototype )
            : ( is_a( $prototype, ItemPrototype::class ) ? $prototype : null );
        if (!$prototype) return null;

        $item = new Item();
        $item
            ->setPrototype( $prototype )
            ->setBroken( $broken )
            ->setPoison( $poison );
        return $item;

    }

    /**
     * @param Item $item
     * @return Item
     */
    public function createBaseItemCopy( Item $item ): Item {
        return (new Item())
            ->setPrototype( $item->getPrototype() )
            ->setBroken( $item->getBroken() )
            ->setPoison( $item->getPoison() )
            ->setEssential( $item->getEssential() );
    }

}