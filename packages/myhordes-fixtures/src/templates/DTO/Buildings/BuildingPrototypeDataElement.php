<?php

namespace MyHordes\Fixtures\DTO\Buildings;

use App\Entity\BuildingPrototype;
use App\Entity\ItemCategory;
use App\Entity\ItemGroup;
use App\Entity\ItemGroupEntry;
use App\Entity\ItemPrototype;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use MyHordes\Fixtures\DTO\Element;
use MyHordes\Fixtures\DTO\LabeledIconElementInterface;

/**
 * @property string $label
 * @method self label(string $v)
 * @property string $icon
 * @method self icon(string $v)
 * @property ?string $parentBuilding
 * @method self parentBuilding(string $v)
 * @property string $description
 * @method self description(string $v)
 * @property bool $isTemporary
 * @method self isTemporary(bool $v)
 * @property bool $isImpervious
 * @method self isImpervious(bool $v)
 * @property int $orderBy
 * @method self orderBy(int $v)
 * @property int $defense
 * @method self defense(int $v)
 * @property int $health
 * @method self health(int $v)
 * @property int $ap
 * @method self ap(int $v)
 * @property int $blueprintLevel
 * @method self blueprintLevel(int $v)
 * @property array $resources
 * @method self resources(array $v)
 * @method self resource(string $key, int $value)
 * @property int $voteLevel
 * @method self voteLevel(int $v)
 * @property string $baseVoteText
 * @method self baseVoteText(string $v)
 * @property string[] $upgradeTexts
 * @method self upgradeTexts(array $v)
 *
 * @method BuildingPrototypeDataContainer commit(string &$id = null)
 * @method BuildingPrototypeDataContainer discard()
 */
class BuildingPrototypeDataElement extends Element implements LabeledIconElementInterface {

    /**
     * @throws Exception
     */
    public function toEntity(EntityManagerInterface $em, string $id, BuildingPrototype $entity): void {
        try {
            $entity
                ->setLabel( $this->label )
                ->setDescription( $this->description )
                ->setTemp( $this->isTemporary )
                ->setAp( $this->ap ?? 0 )
                ->setBlueprint( $this->blueprintLevel ?? 0 )
                ->setDefense( $this->defense ?? 0 )
                ->setIcon( $this->icon )
                ->setHp( $this->health ?: $this->ap ?: 0 )
                ->setImpervious( $this->isImpervious ?? false )
                ->setOrderBy( $this->orderBy ?? 0 );

            if ($this->voteLevel > 0)
                $entity
                    ->setMaxLevel( $this->voteLevel )
                    ->setZeroLevelText( $this->baseVoteText ?? "")
                    ->setUpgradeTexts( array_slice( array_pad($this->upgradeTexts ?? [], $this->voteLevel, "???" ), 0, $this->voteLevel ) );
            else $entity->setMaxLevel( 0 )->setZeroLevelText( null )->setUpgradeTexts( null );

            if ($this->resources) {
                $group = $em->getRepository(ItemGroup::class)->findOneByName("{$id}_rsc") ?? (new ItemGroup())->setName( "{$id}_rsc" );
                $group->getEntries()->clear();

                foreach ($this->resources as $item_name => $count) {

                    $item = $em->getRepository(ItemPrototype::class)->findOneBy( ['name' => $item_name] );
                    if (!$item) throw new Exception( "Item class not found: " . $item_name );

                    $group->addEntry( (new ItemGroupEntry())->setPrototype( $item )->setChance( $count ) );
                }

                $entity->setResources( $group );
            } else $entity->setResources( null );

        } catch (\Throwable $t) {
            throw new Exception(
                "Exception when persisting building prototype to database: {$t->getMessage()} \n\nOccurred when processing the following item:\n" . print_r($this->toArray(), true),
                previous: $t
            );
        }


    }

    public function __call(string $name, array $arguments): Element
    {
        if ($name === 'resource' && count($arguments) === 2) {
            [$key, $value] = $arguments;
            $r = $this->resources ?? [];
            if ($value <= 0) unset( $r[$key] );
            else $r[$key] = $value;
            return $this->resources($r);
        } else return parent::__call($name, $arguments);
    }

}