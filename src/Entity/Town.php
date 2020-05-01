<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\OrderBy;

/**
 * @ORM\Entity(repositoryClass="App\Repository\TownRepository")
 */
class Town
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=190)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=8)
     */
    private $language;

    /**
     * @ORM\Column(type="integer")
     */
    private $population;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $wordsOfHeroes;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Citizen", mappedBy="town", orphanRemoval=true, cascade={"persist", "remove"})
     * @OrderBy({"alive" = "DESC", "id" = "ASC"})
     */
    private $citizens;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\Inventory", inversedBy="town", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $bank;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\TownClass", inversedBy="towns")
     * @ORM\JoinColumn(nullable=false)
     */
    private $type;

    /**
     * @ORM\Column(type="integer")
     */
    private $day = 1;

    /**
     * @ORM\Column(type="integer")
     */
    private $well;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Zone", mappedBy="town", orphanRemoval=true, cascade={"persist", "remove"})
     */
    private $zones;

    /**
     * @ORM\Column(type="boolean")
     */
    private $door = false;

    /**
     * @ORM\Column(type="boolean")
     */
    private $chaos = false;

    /**
     * @ORM\Column(type="boolean")
     */
    private $devastated = false;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Building", mappedBy="town", orphanRemoval=true, cascade={"persist", "remove"})
     * @ORM\OrderBy({"position" = "ASC"})
     */
    private $buildings;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\ZombieEstimation", mappedBy="town", orphanRemoval=true, cascade={"persist"})
     */
    private $zombieEstimations;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\Forum", mappedBy="town", cascade={"persist", "remove"})
     */
    private $forum;

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    private $conf = [];

    /**
     * @ORM\Column(type="integer")
     */
    private $soulDefense = 0;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Season", inversedBy="towns")
     */
    private $season;

    public function __construct()
    {
        $this->citizens = new ArrayCollection();
        $this->zones = new ArrayCollection();
        $this->buildings = new ArrayCollection();
        $this->zombieEstimations = new ArrayCollection();
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

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function setLanguage(string $language): self
    {
        $this->language = $language;

        return $this;
    }

    public function getWordsOfHeroes(): ?string
    {
        return $this->wordsOfHeroes;
    }

    public function setWordsOfHeroes(string $wordsOfHeroes): self
    {
        $this->wordsOfHeroes = $wordsOfHeroes;

        return $this;
    }

    public function getPopulation(): ?int
    {
        return $this->population;
    }

    public function setPopulation(int $population): self
    {
        $this->population = $population;

        return $this;
    }

    public function getCitizenCount(): int {
        return count($this->getCitizens());
    }

    public function isOpen(): bool {
        return $this->getDay() === 1 && $this->getCitizenCount() < $this->getPopulation();
    }

    /**
     * @return Collection|Citizen[]
     */
    public function getCitizens(): Collection
    {
        return $this->citizens;
    }

    public function addCitizen(Citizen $citizen): self
    {
        if (!$this->citizens->contains($citizen)) {
            $this->citizens[] = $citizen;
            $citizen->setTown($this);
        }

        return $this;
    }

    public function removeCitizen(Citizen $citizen): self
    {
        if ($this->citizens->contains($citizen)) {
            $this->citizens->removeElement($citizen);
            // set the owning side to null (unless already changed)
            if ($citizen->getTown() === $this) {
                $citizen->setTown(null);
            }
        }

        return $this;
    }

    public function getBank(): ?Inventory
    {
        return $this->bank;
    }

    public function setBank(Inventory $bank): self
    {
        $this->bank = $bank;

        return $this;
    }

    public function getType(): ?TownClass
    {
        return $this->type;
    }

    public function setType(?TownClass $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getDay(): ?int
    {
        return $this->day;
    }

    public function setDay(int $day): self
    {
        $this->day = $day;

        return $this;
    }

    public function getWell(): ?int
    {
        return $this->well;
    }

    public function setWell(int $well): self
    {
        $this->well = $well;

        return $this;
    }

    /**
     * @return Collection|Zone[]
     */
    public function getZones(): Collection
    {
        return $this->zones;
    }

    public function addZone(Zone $zone): self
    {
        if (!$this->zones->contains($zone)) {
            $this->zones[] = $zone;
            $zone->setTown($this);
        }

        return $this;
    }

    public function removeZone(Zone $zone): self
    {
        if ($this->zones->contains($zone)) {
            $this->zones->removeElement($zone);
            // set the owning side to null (unless already changed)
            if ($zone->getTown() === $this) {
                $zone->setTown(null);
            }
        }

        return $this;
    }

    public function getDoor(): ?bool
    {
        return $this->door;
    }

    public function setDoor(bool $door): self
    {
        $this->door = $door;

        return $this;
    }

    public function getChaos(): ?bool
    {
        return $this->chaos;
    }

    public function setChaos(bool $chaos): self
    {
        $this->chaos = $chaos;

        return $this;
    }


    public function getForum(): ?Forum
    {
        return $this->forum;
    }

    public function setForum(?Forum $forum): self
    {
        $this->forum = $forum;

        // set (or unset) the owning side of the relation if necessary
        $newTown = null === $forum ? null : $this;
        if ($forum->getTown() !== $newTown) {
            $forum->setTown($newTown);
        }

        return $this;
    }

    public function getDevastated(): ?bool
    {
        return $this->devastated;
    }

    public function setDevastated(bool $devastated): self
    {
        $this->devastated = $devastated;

        return $this;
    }

    /**
     * @return Collection|Building[]
     */
    public function getBuildings(): Collection
    {
        return $this->buildings;
    }

    public function addBuilding(Building $building): self
    {
        if (!$this->buildings->contains($building)) {
            $this->buildings[] = $building;
            $building->setTown($this);
        }

        return $this;
    }

    public function removeBuilding(Building $building): self
    {
        if ($this->buildings->contains($building)) {
            $this->buildings->removeElement($building);
            // set the owning side to null (unless already changed)
            if ($building->getTown() === $this) {
                $building->setTown(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|ZombieEstimation[]
     */
    public function getZombieEstimations(): Collection
    {
        return $this->zombieEstimations;
    }

    public function addZombieEstimation(ZombieEstimation $zombieEstimation): self
    {
        if (!$this->zombieEstimations->contains($zombieEstimation)) {
            $this->zombieEstimations[] = $zombieEstimation;
            $zombieEstimation->setTown($this);
        }

        return $this;
    }

    public function removeZombieEstimation(ZombieEstimation $zombieEstimation): self
    {
        if ($this->zombieEstimations->contains($zombieEstimation)) {
            $this->zombieEstimations->removeElement($zombieEstimation);
            // set the owning side to null (unless already changed)
            if ($zombieEstimation->getTown() === $this) {
                $zombieEstimation->setTown(null);
            }
        }

        return $this;
    }

    public function getConf(): ?array
    {
        return $this->conf;
    }

    public function setConf(?array $conf): self
    {
        $this->conf = $conf;

        return $this;
    }

    public function getSoulDefense(): ?int
    {
        return $this->soulDefense;
    }

    public function setSoulDefense(int $soulDefense): self
    {
        $this->soulDefense = $soulDefense;

        return $this;
    }

    public function getSeason(): ?Season
    {
        return $this->season;
    }

    public function setSeason(?Season $season): self
    {
        $this->season = $season;

        return $this;
    }

    public function hasAliveCitizen(): bool
    {
        foreach ($this->getCitizens() as $citizen) {
            if($citizen->getAlive())
                return true;
        }
        return false;
    }

}
