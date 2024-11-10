<?php

namespace App\Entity;

use App\Interfaces\NamedEntity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: 'App\Repository\ItemPrototypeRepository')]
#[UniqueEntity('name')]
#[Table]
#[UniqueConstraint(name: 'item_prototype_name_unique', columns: ['name'])]
class ItemPrototype implements NamedEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;
    #[ORM\Column(type: 'string', length: 190)]
    private $label;
    #[ORM\Column(type: 'string', length: 32)]
    private $icon;
    #[ORM\Column(type: 'integer')]
    private $deco;
    #[ORM\ManyToOne(targetEntity: 'App\Entity\ItemCategory', inversedBy: 'itemPrototypes')]
    #[ORM\JoinColumn(nullable: false)]
    private $category;
    #[ORM\ManyToMany(targetEntity: 'App\Entity\ItemProperty', inversedBy: 'itemPrototypes')]
    private $properties;
    #[ORM\Column(type: 'string', length: 64)]
    private $name;
    #[ORM\Column(type: 'boolean')]
    private $heavy;
    #[ORM\ManyToMany(targetEntity: 'App\Entity\ItemAction')]
    private $actions;
    #[ORM\Column(type: 'text')]
    private $description;
    #[ORM\Column(type: 'integer')]
    private $watchpoint = 0;
    #[ORM\ManyToOne(targetEntity: ItemAction::class)]
    private $nightWatchAction;
    #[ORM\Column(type: 'boolean')]
    private $hideInForeignChest;
    #[ORM\Column(type: 'boolean', nullable: true)]
    private $fragile;
    #[ORM\Column(type: 'integer', nullable: true)]
    private $sort;
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $deco_text;
    #[ORM\Column(type: 'boolean')]
    private $individual = false;

    #[ORM\Column(type: 'integer')]
    private ?int $watchimpact = 0;

    #[ORM\Column]
    private bool $persistentEssential = false;

    public function __construct()
    {
        $this->properties = new ArrayCollection();
        $this->actions = new ArrayCollection();
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
    public function getIcon(): ?string
    {
        return $this->icon;
    }
    public function setIcon(string $icon): self
    {
        $this->icon = $icon;

        return $this;
    }
    public function getDeco(): ?int
    {
        return $this->deco;
    }
    public function setDeco(int $deco): self
    {
        $this->deco = $deco;

        return $this;
    }
    public function getCategory(): ?ItemCategory
    {
        return $this->category;
    }
    public function setCategory(?ItemCategory $category): self
    {
        $this->category = $category;

        return $this;
    }
    /**
     * @return Collection|ItemProperty[]
     */
    public function getProperties(): Collection
    {
        return $this->properties;
    }
    public function addProperty(ItemProperty $property): self
    {
        if (!$this->properties->contains($property)) {
            $this->properties[] = $property;
        }

        return $this;
    }
    public function removeProperty(ItemProperty $property): self
    {
        if ($this->properties->contains($property)) {
            $this->properties->removeElement($property);
        }

        return $this;
    }
    public function hasProperty(string $prop): bool {
        foreach ($this->getProperties() as $p)
            if ($p->getName() === $prop) return true;
        return false;
    }
    public function getName(): ?string
    {
        return $this->name;
    }
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }
    public function getHeavy(): ?bool
    {
        return $this->heavy;
    }
    public function setHeavy(bool $heavy): self
    {
        $this->heavy = $heavy;

        return $this;
    }
    /**
     * @return Collection|ItemAction[]
     */
    public function getActions(): Collection
    {
        return $this->actions;
    }
    public function addAction(ItemAction $action): self
    {
        if (!$this->actions->contains($action)) {
            $this->actions[] = $action;
        }

        return $this;
    }
    public function removeAction(ItemAction $action): self
    {
        if ($this->actions->contains($action)) {
            $this->actions->removeElement($action);
        }

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
    public function getWatchpoint(): ?int
    {
        return $this->watchpoint;
    }
    public function setWatchpoint(int $watchpoint): self
    {
        $this->watchpoint = $watchpoint;

        return $this;
    }
    public function getNightWatchAction(): ?ItemAction
    {
        return $this->nightWatchAction;
    }
    public function setNightWatchAction(?ItemAction $nightWatchAction): self
    {
        $this->nightWatchAction = $nightWatchAction;

        return $this;
    }
    public function getHideInForeignChest(): ?bool
    {
        return $this->hideInForeignChest;
    }
    public function setHideInForeignChest(bool $hideInForeignChest): self
    {
        $this->hideInForeignChest = $hideInForeignChest;

        return $this;
    }
    public function getFragile(): ?bool
    {
        return $this->fragile;
    }
    public function setFragile(?bool $fragile): self
    {
        $this->fragile = $fragile;

        return $this;
    }
    public function getSort(): ?int
    {
        return $this->sort;
    }
    public function setSort(?int $sort): self
    {
        $this->sort = $sort;

        return $this;
    }
    public function getDecoText(): ?string
    {
        return $this->deco_text;
    }
    public function setDecoText(?string $deco_text): self
    {
        $this->deco_text = $deco_text;

        return $this;
    }
    public function getIndividual(): ?bool
    {
        return $this->individual;
    }
    public function setIndividual(bool $individual): self
    {
        $this->individual = $individual;

        return $this;
    }
    public static function getTranslationDomain(): ?string
    {
        return 'items';
    }

    public function getWatchimpact(): ?int
    {
        return $this->watchimpact;
    }

    public function setWatchimpact(?int $watchimpact): static
    {
        $this->watchimpact = $watchimpact;

        return $this;
    }

    public function isPersistentEssential(): bool
    {
        return $this->persistentEssential;
    }

    public function setPersistentEssential(bool $persistentEssential): static
    {
        $this->persistentEssential = $persistentEssential;

        return $this;
    }
}
