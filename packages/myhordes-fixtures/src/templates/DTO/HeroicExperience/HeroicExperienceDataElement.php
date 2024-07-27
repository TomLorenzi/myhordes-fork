<?php

namespace MyHordes\Fixtures\DTO\HeroicExperience;

use App\Entity\FeatureUnlockPrototype;
use App\Entity\HeroicActionPrototype;
use App\Entity\HeroSkillPrototype;
use App\Entity\ItemPrototype;
use App\Enum\Configuration\CitizenProperties;
use Doctrine\ORM\EntityManagerInterface;
use MyHordes\Fixtures\DTO\Element;

/**
 * @property string $name
 * @method self name(string $v)
 * @property string $icon
 * @method self icon(string $v)
 * @property string $title
 * @method self title(string $v)
 * @property string $description
 * @method self description(string $v)
 * @property array $bullets
 * @method self bullets(array $v)
 * @property bool $legacy
 * @method self legacy(bool $v)
 * @property int $unlockAt
 * @method self unlockAt(int $v)
 * @property string $unlocksAction
 * @method self unlocksAction(string $v)
 * @property array $grantsItems
 * @method self grantsItems(array $v)
 * @property int $level
 * @method self level(int $v)
 * @property int $sort
 * @method self sort(int $v)
 * @property string $group
 * @method self group(string $v)
 * @property int $chestSpace
 * @method self chestSpace(int $v)
 * @property int $disabled
 * @method self disabled(bool $v)
 * @property string $itemsGrantedAsProfessionItems
 * @method self itemsGrantedAsProfessionItems(bool $v)
 * @property array $itemTypesGrantedAsProfessionItems
 * @method self itemTypesGrantedAsProfessionItems(array $v)
 * @property string $inhibitedByFeatureUnlock
 * @method self inhibitedByFeatureUnlock(string $v)
 * @property array $citizenProperties
 * @method self citizenProperties(array $v)
 *
 * @method HeroicExperienceDataContainer commit()
 * @method HeroicExperienceDataContainer discard()
 */
class HeroicExperienceDataElement extends Element {

    protected function provide_default(string $name): mixed {
        return match ($name) {
            'citizenProperties',
            'itemTypesGrantedAsProfessionItems',
            'grantsItems' => [],
            default => parent::provide_default($name),
        };
    }

    public function addCitizenProperty(CitizenProperties $prop, mixed $value): self {
        $this->citizenProperties = [
            ...$this->citizenProperties,
            $prop->value => $value,
        ];
        return $this;
    }

    public function addItemGrant(string $item, ?string $key = null, $as_essential = false): self {
        $this->grantsItems = [
            ...$this->grantsItems,
            ...($key === null ? [$item] : [$key => $item]),
        ];
        if ($as_essential)
            $this->itemTypesGrantedAsProfessionItems = array_unique([
                ...$this->itemTypesGrantedAsProfessionItems,
                $item,
            ]);
        return $this;
    }

    /**
     * @throws \Exception
     */
    public function toEntity(EntityManagerInterface $em, HeroSkillPrototype $entity): void {
        $protoAction = null;
        if ($this->unlocksAction) {
            $protoAction = $em->getRepository(HeroicActionPrototype::class)->findOneBy(['name' => $this->unlocksAction]);
            if ($protoAction === null)
                throw new \Exception("Invalid unlock action '{$this->unlocksAction}' defined for skill '{$this->name}'.");

            if ($protoAction->getReplacedAction() !== null) {
                $replacedProto = $em->getRepository(HeroicActionPrototype::class)->findOneBy(['name' => $protoAction->getReplacedAction()]);
                if (!$replacedProto)
                    throw new \Exception("Invalid replaced unlock action '{$protoAction->getReplacedAction()}' from '{$this->unlocksAction}' defined for skill '{$this->name}'.");
            }
        }

        $grantedItems = [];
        if (!empty($this->grantsItems)) {
            foreach ($this->grantsItems as $item) {
                $proto = $em->getRepository(ItemPrototype::class)->findOneBy(['name' => $item]);
                if (!$proto)
                    throw new \Exception("Invalid granted item '{$item}' defined for skill '{$this->name}'.");
                $grantedItems[] = $proto;
            }
        }

        $blockedByFeature = null;
        if (!empty($this->inhibitedByFeatureUnlock)) {
            $blockedByFeature = $em->getRepository(FeatureUnlockPrototype::class)->findOneBy(['name' => $this->inhibitedByFeatureUnlock]);
            if (!$blockedByFeature)
                throw new \Exception("Invalid feature unlock inhibitor '{$this->inhibitedByFeatureUnlock}' defined for skill '{$this->name}'.");
        }

        try {
            $entity
                ->setName( $this->name )
                ->setIcon( $this->icon )
                ->setTitle( $this->title )
                ->setDescription( $this->description ?? '' )
                ->setBullets( $this->bullets ?? [] )
                ->setDaysNeeded( $this->unlockAt )
                ->setUnlockedAction( $protoAction )
                ->setLevel( $this->legacy ? null : $this->level )
                ->setSort( $this->legacy ? $this->unlockAt : ($this->sort ?? 0) )
                ->setEnabled( !$this->disabled )
                ->setGroupIdentifier( $this->legacy ? null : $this->group )
                ->setInhibitedBy( $blockedByFeature )
                ->setProfessionItems( $this->itemsGrantedAsProfessionItems ?? false )
                ->setEssentialItemTypes( $this->itemTypesGrantedAsProfessionItems ?? [] )
                ->setGrantsChestSpace( $this->chestSpace ?? 0 )
                ->setCitizenProperties( $this->citizenProperties )
                ->setLegacy( $this->legacy );

            $entity->getStartItems()->clear();
            foreach ($grantedItems as $item)
                $entity->addStartItem( $item );

        } catch (\Throwable $t) {
            throw new \Exception(
                "Exception when persisting hero skill prototype to database: {$t->getMessage()} \n\nOccurred when processing the following skill:\n" . print_r($this->toArray(), true),
                previous: $t
            );
        }


    }

}