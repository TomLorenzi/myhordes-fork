<?php

namespace App\Entity;

use App\Enum\ArrayMergeDirective;
use App\Interfaces\NamedEntity;
use App\Interfaces\RandomEntry;
use App\Interfaces\RandomGroup;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;

#[ORM\Entity(repositoryClass: 'App\Repository\ZonePrototypeRepository')]
#[Table]
#[UniqueConstraint(name: 'zone_prototype_unique', columns: ['icon'])]
class ZonePrototype implements RandomEntry
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 190)]
    private ?string $label;

    #[ORM\Column(type: 'string', length: 500)]
    private ?string $description;

    #[ORM\Column(type: 'integer')]
    private ?int $campingLevel;

    #[ORM\ManyToOne(targetEntity: 'App\Entity\ItemGroup', fetch: 'EXTRA_LAZY', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?ItemGroup $drops;

    #[ORM\Column(type: 'integer')]
    private ?int $minDistance;

    #[ORM\Column(type: 'integer')]
    private ?int $maxDistance;

    #[ORM\Column(type: 'integer')]
    private ?int $chance;

    #[ORM\Column(type: 'string', length: 32)]
    private ?string $icon;

    #[ORM\Column(type: 'boolean')]
    private bool $explorable = false;

    #[ORM\Column(type: 'string', length: 32, nullable: true)]
    private ?string $explorableSkin;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $explorableDescription;

    #[ORM\ManyToMany(targetEntity: NamedItemGroup::class, fetch: 'EXTRA_LAZY')]
    private Collection $namedDrops;

    #[ORM\Column]
    private ?float $emptyDropChance = null;

    public function __construct()
    {
        $this->namedDrops = new ArrayCollection();
    }
    public function getId(): ?int
    {
        return $this->id;
    }
    public function getLabel(): ?string
    {
        return $this->label;
    }
    public function setLabel(string $label): self
    {
        $this->label = $label;

        return $this;
    }
    public function getDescription(): ?string
    {
        return $this->description;
    }
    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }
    public function getCampingLevel(): ?int
    {
        return $this->campingLevel;
    }
    public function setCampingLevel(int $campingLevel): self
    {
        $this->campingLevel = $campingLevel;

        return $this;
    }
    public function getDrops(): ?ItemGroup
    {
        return $this->drops;
    }
    public function setDrops(?ItemGroup $drops): self
    {
        $this->drops = $drops;

        return $this;
    }
    public function getMinDistance(): ?int
    {
        return $this->minDistance;
    }
    public function setMinDistance(int $minDistance): self
    {
        $this->minDistance = $minDistance;

        return $this;
    }
    public function getMaxDistance(): ?int
    {
        return $this->maxDistance;
    }
    public function setMaxDistance(int $maxDistance): self
    {
        $this->maxDistance = $maxDistance;

        return $this;
    }
    public function getChance(): ?int
    {
        return $this->chance;
    }
    public function setChance(int $chance): self
    {
        $this->chance = $chance;

        return $this;
    }
    public function getIcon(): ?string
    {
        return $this->icon;
    }
    public function setIcon(string $icon): self
    {
        $this->icon = $icon;

        return $this;
    }
    public function getExplorable(): ?bool
    {
        return $this->explorable;
    }
    public function setExplorable(bool $explorable): self
    {
        $this->explorable = $explorable;

        return $this;
    }
    public function getExplorableSkin(): ?string
    {
        return $this->explorableSkin;
    }
    public function setExplorableSkin(?string $explorableSkin): self
    {
        $this->explorableSkin = $explorableSkin;

        return $this;
    }
    public function getExplorableDescription(): ?string
    {
        return $this->explorableDescription;
    }
    public function setExplorableDescription(?string $explorableDescription): self
    {
        $this->explorableDescription = $explorableDescription;

        return $this;
    }
    /**
     * @return Collection<int, NamedItemGroup>
     */
    public function getNamedDrops(): Collection
    {
        return $this->namedDrops;
    }
    public function addNamedDrop(NamedItemGroup $namedDrop): self
    {
        if (!$this->namedDrops->contains($namedDrop)) {
            $this->namedDrops[] = $namedDrop;
        }

        return $this;
    }
    public function removeNamedDrop(NamedItemGroup $namedDrop): self
    {
        $this->namedDrops->removeElement($namedDrop);

        return $this;
    }
    public function getDropByName( string $name ): ?ItemGroup {
        return $this->getDropByNames( [$name] );
    }
    public function getDropByNames( array $names ): ?ItemGroup {
        $base_drop = $this->getDrops();
        $matched = [];
        foreach ( $names as $name)
            foreach ( $this->getNamedDrops() as $drop )
                if ($drop->getName() === $name) $matched[] = $drop;

        if (empty($matched)) return $base_drop;
        $live = (clone $base_drop)->toArray();

        foreach ($matched as $match)
            $live = $match->getOperator()->apply( $live, (clone $match->getItemGroup())->toArray() );

        $return = new ItemGroup();
        foreach ( $live as $entry ) $return->addEntry( $entry );

        return $return;
    }

    public function getEmptyDropChance(): ?float
    {
        return $this->emptyDropChance;
    }

    public function setEmptyDropChance(float $emptyDropChance): self
    {
        $this->emptyDropChance = $emptyDropChance;

        return $this;
    }

	public static function getTranslationDomain(): ?string
	{
		return 'game';
	}
}
