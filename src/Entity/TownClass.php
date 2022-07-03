<?php

namespace App\Entity;

use App\Interfaces\NamedEntity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass="App\Repository\TownClassRepository")
 * @UniqueEntity("name")
 * @Table(uniqueConstraints={
 *     @UniqueConstraint(name="town_class_name_unique",columns={"name"})
 * })
 */
class TownClass implements NamedEntity
{

    const EASY    = 'small';
    const DEFAULT = 'remote';
    const HARD    = 'panda';

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=16)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=64)
     */
    private $label;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Town", mappedBy="type", orphanRemoval=true)
     */
    private $towns;

    /**
     * @ORM\OneToMany(targetEntity=TownRankingProxy::class, mappedBy="type", orphanRemoval=true)
     */
    private $rankedTowns;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $hasPreset = true;

    /**
     * @ORM\Column(type="boolean")
     */
    private $ranked;

    /**
     * @ORM\Column(type="integer")
     */
    private $orderBy;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $help;

    public function __construct()
    {
        $this->towns = new ArrayCollection();
        $this->rankedTowns = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    /**
     * @return Collection|Town[]
     */
    public function getTowns(): Collection
    {
        return $this->towns;
    }

    public function addTown(Town $town): self
    {
        if (!$this->towns->contains($town)) {
            $this->towns[] = $town;
            $town->setType($this);
        }

        return $this;
    }

    public function removeTown(Town $town): self
    {
        if ($this->towns->contains($town)) {
            $this->towns->removeElement($town);
            // set the owning side to null (unless already changed)
            if ($town->getType() === $this) {
                $town->setType(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|TownRankingProxy[]
     */
    public function getRankedTowns(): Collection
    {
        return $this->rankedTowns;
    }

    public function addRankedTown(TownRankingProxy $rankedTown): self
    {
        if (!$this->rankedTowns->contains($rankedTown)) {
            $this->rankedTowns[] = $rankedTown;
            $rankedTown->setType($this);
        }

        return $this;
    }

    public function removeRankedTown(TownRankingProxy $rankedTown): self
    {
        if ($this->rankedTowns->contains($rankedTown)) {
            $this->rankedTowns->removeElement($rankedTown);
            // set the owning side to null (unless already changed)
            if ($rankedTown->getType() === $this) {
                $rankedTown->setType(null);
            }
        }

        return $this;
    }

    public function getHasPreset(): ?bool
    {
        return $this->hasPreset;
    }

    public function setHasPreset(?bool $hasPreset): self
    {
        $this->hasPreset = $hasPreset;

        return $this;
    }

    public function getRanked(): ?bool
    {
        return $this->ranked;
    }

    public function setRanked(bool $ranked): self
    {
        $this->ranked = $ranked;

        return $this;
    }

    public function getOrderBy(): ?int
    {
        return $this->orderBy;
    }

    public function setOrderBy(int $orderBy): self
    {
        $this->orderBy = $orderBy;

        return $this;
    }

    public function getHelp(): ?string
    {
        return $this->help;
    }

    public function setHelp(?string $help): self
    {
        $this->help = $help;

        return $this;
    }

    public static function getTranslationDomain(): ?string
    {
        return 'game';
    }
}
