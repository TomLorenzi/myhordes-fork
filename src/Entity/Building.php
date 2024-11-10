<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;

#[ORM\Entity(repositoryClass: 'App\Repository\BuildingRepository')]
#[Table]
#[UniqueConstraint(name: 'town_unique', columns: ['prototype_id', 'town_id'])]
class Building
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;
    #[ORM\ManyToOne(targetEntity: 'App\Entity\BuildingPrototype', fetch: 'EAGER')]
    #[ORM\JoinColumn(nullable: false)]
    private $prototype;
    #[ORM\Column(type: 'boolean')]
    private $complete = false;
    #[ORM\Column(type: 'integer')]
    private $ap = 0;
    #[ORM\ManyToOne(targetEntity: 'App\Entity\Town', fetch: 'EXTRA_LAZY', inversedBy: 'buildings')]
    #[ORM\JoinColumn(nullable: false)]
    private $town;
    #[ORM\OneToMany(targetEntity: 'App\Entity\DailyUpgradeVote', mappedBy: 'building', orphanRemoval: true, fetch: 'EXTRA_LAZY')]
    private $dailyUpgradeVotes;
    #[ORM\Column(type: 'integer')]
    private $level = 0;
    #[ORM\Column(type: 'integer')]
    private $defenseBonus = 0;
    #[ORM\Column(type: 'integer')]
    private $tempDefenseBonus = 0;
    #[ORM\Column(type: 'integer')]
    private $position = 0;
    #[ORM\Column(type: 'integer')]
    private $hp = 0;
    #[ORM\Column(type: 'integer')]
    private $defense = 0;
    #[ORM\OneToMany(mappedBy: 'building', targetEntity: BuildingVote::class, cascade: ['persist', 'remove'], fetch: 'EXTRA_LAZY', orphanRemoval: true)]
    private $buildingVotes;
    #[ORM\OneToOne(inversedBy: 'building', targetEntity: Inventory::class, cascade: ['persist', 'remove'])]
    private ?Inventory $inventory = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $constructionDate = null;

    public function __construct()
    {
        $this->dailyUpgradeVotes = new ArrayCollection();
        $this->buildingVotes = new ArrayCollection();
    }
    public function getId(): ?int
    {
        return $this->id;
    }
    public function getPrototype(): BuildingPrototype
    {
        return $this->prototype;
    }
    public function setPrototype(?BuildingPrototype $prototype): self
    {
        $this->prototype = $prototype;

        return $this;
    }
    public function getComplete(): ?bool
    {
        return $this->complete;
    }
    public function setComplete(bool $complete): self
    {
        $this->complete = $complete;

        return $this;
    }
    public function getAp(): ?int
    {
        return $this->ap;
    }
    public function setAp(int $ap): self
    {
        $this->ap = $ap;

        return $this;
    }
    public function getTown(): ?Town
    {
        return $this->town;
    }
    public function setTown(?Town $town): self
    {
        $this->town = $town;

        return $this;
    }
    /**
     * @return Collection|DailyUpgradeVote[]
     */
    public function getDailyUpgradeVotes(): Collection
    {
        return $this->dailyUpgradeVotes;
    }
    public function addDailyUpgradeVote(DailyUpgradeVote $dailyUpgradeVote): self
    {
        if (!$this->dailyUpgradeVotes->contains($dailyUpgradeVote)) {
            $this->dailyUpgradeVotes[] = $dailyUpgradeVote;
            $dailyUpgradeVote->setBuilding($this);
        }

        return $this;
    }
    public function removeDailyUpgradeVote(DailyUpgradeVote $dailyUpgradeVote): self
    {
        if ($this->dailyUpgradeVotes->contains($dailyUpgradeVote)) {
            $this->dailyUpgradeVotes->removeElement($dailyUpgradeVote);
            // set the owning side to null (unless already changed)
            if ($dailyUpgradeVote->getBuilding() === $this) {
                $dailyUpgradeVote->setBuilding(null);
            }
        }

        return $this;
    }
    public function getLevel(): ?int
    {
        return $this->level;
    }
    public function setLevel(int $level): self
    {
        $this->level = $level;

        return $this;
    }
    public function getDefenseBonus(): ?int
    {
        return $this->defenseBonus;
    }
    public function setDefenseBonus(int $defenseBonus): self
    {
        $this->defenseBonus = $defenseBonus;

        return $this;
    }
    public function getTempDefenseBonus(): ?int
    {
        return $this->tempDefenseBonus;
    }
    public function setTempDefenseBonus(int $tempDefenseBonus): self
    {
        $this->tempDefenseBonus = $tempDefenseBonus;

        return $this;
    }
    public function getPosition(): ?int
    {
        return $this->position;
    }
    public function setPosition(int $position): self
    {
        $this->position = $position;

        return $this;
    }
    public function getHp(): ?int
    {
        return $this->hp;
    }
    public function setHp(int $hp): self
    {
        $this->hp = $hp;

        return $this;
    }
    public function getDefense(): ?int
    {
        return $this->defense;
    }
    public function setDefense(int $defense): self
    {
        $this->defense = $defense;

        return $this;
    }
    /**
     * @return Collection|BuildingVote[]
     */
    public function getBuildingVotes(): Collection
    {
        return $this->buildingVotes;
    }
    public function addBuildingVote(BuildingVote $buildingVote): self
    {
        if (!$this->buildingVotes->contains($buildingVote)) {
            $this->buildingVotes[] = $buildingVote;
            $buildingVote->setBuilding($this);
        }

        return $this;
    }
    public function removeBuildingVote(BuildingVote $buildingVote): self
    {
        if ($this->buildingVotes->contains($buildingVote)) {
            $this->buildingVotes->removeElement($buildingVote);
            // set the owning side to null (unless already changed)
            if ($buildingVote->getBuilding() === $this) {
                $buildingVote->setBuilding(null);
            }
        }

        return $this;
    }

    public function getInventory(): ?Inventory
    {
        return $this->inventory;
    }

    public function setInventory(?Inventory $inventory): static
    {
        $this->inventory = $inventory;

        return $this;
    }

    public function getConstructionDate(): ?\DateTimeInterface
    {
        return $this->constructionDate;
    }

    public function setConstructionDate(?\DateTimeInterface $constructionDate): static
    {
        $this->constructionDate = $constructionDate;

        return $this;
    }
}
