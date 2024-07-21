<?php

namespace App\Entity;

use App\Repository\HeroSkillPrototypeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: HeroSkillPrototypeRepository::class)]
#[UniqueEntity('name')]
#[UniqueConstraint(name: 'hero_skill_prototype_unique', columns: ['name'])]
class HeroSkillPrototype
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;
    #[ORM\Column(type: 'string', length: 64)]
    private $title;
    #[ORM\Column(type: 'text')]
    private $description;
    #[ORM\Column(type: 'string', length: 64)]
    private $icon;
    #[ORM\Column(type: 'string', length: 64)]
    private $name;
    #[ORM\Column(type: 'integer')]
    private $daysNeeded;

    #[ORM\ManyToMany(targetEntity: ItemPrototype::class)]
    private Collection $start_items;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    private ?HeroicActionPrototype $unlocked_action = null;

    #[ORM\Column]
    private ?bool $legacy = null;

    #[ORM\Column(length: 16, nullable: true)]
    private ?string $groupIdentifier = null;

    #[ORM\Column(nullable: true)]
    private ?int $level = null;

    #[ORM\Column]
    private ?bool $enabled = null;

    #[ORM\Column]
    private ?bool $professionItems = null;

    #[ORM\ManyToOne]
    private ?FeatureUnlockPrototype $inhibitedBy = null;

    #[ORM\Column(nullable: true)]
    private ?int $grantsChestSpace = null;

    #[ORM\Column]
    private int $sort = 0;

    public function __construct()
    {
        $this->start_items = new ArrayCollection();
    }
    public function getId(): ?int
    {
        return $this->id;
    }
    public function getTitle(): ?string
    {
        return $this->title;
    }
    public function setTitle(string $title): self
    {
        $this->title = $title;

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
    public function getIcon(): ?string
    {
        return $this->icon;
    }
    public function setIcon(string $icon): self
    {
        $this->icon = $icon;

        return $this;
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
    public function getDaysNeeded(): ?int
    {
        return $this->daysNeeded;
    }
    public function setDaysNeeded(int $daysNeeded): self
    {
        $this->daysNeeded = $daysNeeded;

        return $this;
    }

    /**
     * @return Collection<int, ItemPrototype>
     */
    public function getStartItems(): Collection
    {
        return $this->start_items;
    }

    public function addStartItem(ItemPrototype $startItem): static
    {
        if (!$this->start_items->contains($startItem)) {
            $this->start_items->add($startItem);
        }

        return $this;
    }

    public function removeStartItem(ItemPrototype $startItem): static
    {
        $this->start_items->removeElement($startItem);

        return $this;
    }

    public function getUnlockedAction(): ?HeroicActionPrototype
    {
        return $this->unlocked_action;
    }

    public function setUnlockedAction(?HeroicActionPrototype $unlocked_action): static
    {
        $this->unlocked_action = $unlocked_action;

        return $this;
    }

    public function isLegacy(): ?bool
    {
        return $this->legacy;
    }

    public function setLegacy(bool $legacy): static
    {
        $this->legacy = $legacy;

        return $this;
    }

    public function getGroupIdentifier(): ?string
    {
        return $this->groupIdentifier;
    }

    public function setGroupIdentifier(?string $groupIdentifier): static
    {
        $this->groupIdentifier = $groupIdentifier;

        return $this;
    }

    public function getLevel(): ?int
    {
        return $this->level;
    }

    public function setLevel(?int $level): static
    {
        $this->level = $level;

        return $this;
    }

    public function isEnabled(): ?bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): static
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function isProfessionItems(): ?bool
    {
        return $this->professionItems;
    }

    public function setProfessionItems(bool $professionItems): static
    {
        $this->professionItems = $professionItems;

        return $this;
    }

    public function getInhibitedBy(): ?FeatureUnlockPrototype
    {
        return $this->inhibitedBy;
    }

    public function setInhibitedBy(?FeatureUnlockPrototype $inhibitedBy): static
    {
        $this->inhibitedBy = $inhibitedBy;

        return $this;
    }

    public function getGrantsChestSpace(): ?int
    {
        return $this->grantsChestSpace;
    }

    public function setGrantsChestSpace(?int $grantsChestSpace): static
    {
        $this->grantsChestSpace = $grantsChestSpace;

        return $this;
    }

    public function getSort(): int
    {
        return $this->sort;
    }

    public function setSort(int $sort): static
    {
        $this->sort = $sort;

        return $this;
    }
}
