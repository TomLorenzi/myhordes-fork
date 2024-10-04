<?php

namespace MyHordes\Fixtures\DTO\Items;

use App\Entity\ItemCategory;
use App\Entity\ItemPrototype;
use Doctrine\ORM\EntityManagerInterface;
use MyHordes\Fixtures\DTO\Element;
use MyHordes\Fixtures\DTO\LabeledIconElementInterface;

/**
 * @property string $label
 * @method self label(string $v)
 * @property string $icon
 * @method self icon(string $v)
 * @property string $description
 * @method self description(string $v)
 * @property string $category
 * @method self category(string $v)
 * @property int $deco
 * @method self deco(int $v)
 * @property bool $heavy
 * @method self heavy(bool $v)
 * @property int $watchpoint
 * @method self watchpoint(int $v)
 * @property bool $fragile
 * @method self fragile(bool $v)
 * @property string $deco_text
 * @method self deco_text(string $v)
 * @property int $sort
 * @method self sort(int $v)
 * @property bool $hideInForeignChest
 * @method self hideInForeignChest(bool $v)
 * @property bool $unstackable
 * @method self unstackable(bool $v)
 * @property int $watchimpact
 * @method self watchimpact(int $v)
 * @property bool $isPersistentWhenEssential
 * @method self isPersistentWhenEssential(bool $v)
 *
 * @method ItemPrototypeDataContainer commit()
 * @method ItemPrototypeDataContainer discard()
 */
class ItemPrototypeDataElement extends Element implements LabeledIconElementInterface {

    /**
     * @throws \Exception
     */
    public function toEntity(EntityManagerInterface $em, ItemPrototype $entity): void {

        try {
            // Check the category
            $category = $em->getRepository(ItemCategory::class)->findOneBy( ['name' => $this->category ?? 'Misc'] );
            if ($category === null)
                throw new \Exception("Invalid category '{$this->category}' defined for item '{$this->label}'.");

            $entity
                ->setLabel( $this->label )
                ->setIcon( $this->icon )
                ->setDeco( $this->deco ?? 0 )
                ->setHeavy( $this->heavy ?? false )
                ->setCategory( $category )
                ->setSort( $this->sort ?? 0 )
                ->setDescription( $this->description )
                ->setHideInForeignChest( $this->hideInForeignChest ?? false )
                ->setDecoText($this->deco_text)
                ->setIndividual( $this->unstackable ?? false )
                ->setWatchpoint($this->watchpoint ?? 0)
                ->setFragile( $this->fragile ?? false )
				->setWatchimpact($this->watchimpact ?? 0)
                ->setPersistentEssential( $this->isPersistentWhenEssential ?? false );
        } catch (\Throwable $t) {
            throw new \Exception(
                "Exception when persisting item prototype to database: {$t->getMessage()} \n\nOccurred when processing the following item:\n" . print_r($this->toArray(), true),
                previous: $t
            );
        }


    }

}