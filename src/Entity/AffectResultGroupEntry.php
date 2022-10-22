<?php

namespace App\Entity;

use App\Interfaces\RandomEntry;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: 'App\Repository\AffectResultGroupEntryRepository')]
class AffectResultGroupEntry implements RandomEntry
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;
    #[ORM\Column(type: 'integer')]
    private $count;
    #[ORM\ManyToOne(targetEntity: 'App\Entity\AffectResultGroup', inversedBy: 'entries')]
    #[ORM\JoinColumn(nullable: false)]
    private $affectResultGroup;
    #[ORM\ManyToMany(targetEntity: 'App\Entity\Result')]
    private $results;
    public function __construct()
    {
        $this->results = new ArrayCollection();
    }
    public function getId(): ?int
    {
        return $this->id;
    }
    public function getCount(): ?int
    {
        return $this->count;
    }
    public function getChance(): int
    {
        return $this->getCount();
    }
    public function setCount(int $count): self
    {
        $this->count = $count;

        return $this;
    }
    public function getAffectResultGroup(): ?AffectResultGroup
    {
        return $this->affectResultGroup;
    }
    public function setAffectResultGroup(?AffectResultGroup $affectResultGroup): self
    {
        $this->affectResultGroup = $affectResultGroup;

        return $this;
    }
    /**
     * @return Collection|Result[]
     */
    public function getResults(): Collection
    {
        return $this->results;
    }
    public function addResult(Result $result): self
    {
        if (!$this->results->contains($result)) {
            $this->results[] = $result;
        }

        return $this;
    }
    public function removeResult(Result $result): self
    {
        if ($this->results->contains($result)) {
            $this->results->removeElement($result);
        }

        return $this;
    }
}
