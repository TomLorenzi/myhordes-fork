<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\CitizenRepository")
 */
class Citizen
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="boolean")
     */
    private $alive = true;

    /**
     * @ORM\Column(type="smallint")
     */
    private $ap = 6;

    /**
     * @ORM\Column(type="boolean")
     */
    private $active = true;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="citizens")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\CitizenStatus")
     */
    private $status;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\CitizenProfession")
     * @ORM\JoinColumn(nullable=false)
     */
    private $profession;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\Inventory", inversedBy="citizen", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $inventory;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Town", inversedBy="citizens")
     * @ORM\JoinColumn(nullable=false)
     */
    private $town;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\CitizenHome", inversedBy="citizen", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $home;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\WellCounter", mappedBy="citizen", cascade={"persist", "remove"})
     */
    private $wellCounter;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Zone", inversedBy="citizens")
     */
    private $zone;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\DigTimer", mappedBy="citizen", orphanRemoval=true)
     */
    private $digTimers;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\DailyUpgradeVote", mappedBy="citizen", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=true)
     */
    private $dailyUpgradeVote;

    /**
     * @ORM\Column(type="integer")
     */
    private $walkingDistance = 0;

    /**
     * @ORM\Column(type="integer")
     */
    private $survivedDays = 0;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\CauseOfDeath")
     * @ORM\JoinColumn(nullable=false)
     */
    private $causeOfDeath;

    /**
     * @ORM\Column(type="integer")
     */
    private $Bp;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\ExpeditionRoute", mappedBy="owner", orphanRemoval=true, cascade={"persist", "remove"})
     */
    private $expeditionRoutes;

    /**
     * @ORM\Column(type="boolean")
     */
    private $banished = false;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Complaint", mappedBy="culprit", orphanRemoval=true)
     */
    private $complaints;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\TrashCounter", inversedBy="citizen", cascade={"persist", "remove"})
     */
    private $trashCounter;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\HeroicActionPrototype")
     */
    private $heroicActions;

    public function __construct()
    {
        $this->status = new ArrayCollection();
        $this->digTimers = new ArrayCollection();
        $this->expeditionRoutes = new ArrayCollection();
        $this->complaints = new ArrayCollection();
        $this->heroicActions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAlive(): ?bool
    {
        return $this->alive;
    }

    public function setAlive(bool $alive): self
    {
        $this->alive = $alive;

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

    public function getActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return Collection|CitizenStatus[]
     */
    public function getStatus(): Collection
    {
        return $this->status;
    }

    public function addStatus(CitizenStatus $status): self
    {
        if (!$this->status->contains($status)) {
            $this->status[] = $status;
        }

        return $this;
    }

    public function removeStatus(CitizenStatus $status): self
    {
        if ($this->status->contains($status)) {
            $this->status->removeElement($status);
        }

        return $this;
    }

    public function getProfession(): ?CitizenProfession
    {
        return $this->profession;
    }

    public function setProfession(?CitizenProfession $profession): self
    {
        $this->profession = $profession;

        return $this;
    }

    public function getInventory(): ?Inventory
    {
        return $this->inventory;
    }

    public function setInventory(Inventory $inventory): self
    {
        $this->inventory = $inventory;

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

    public function getHome(): ?CitizenHome
    {
        return $this->home;
    }

    public function setHome(CitizenHome $home): self
    {
        $this->home = $home;

        return $this;
    }

    public function getWellCounter(): ?WellCounter
    {
        return $this->wellCounter;
    }

    public function setWellCounter(WellCounter $wellCounter): self
    {
        $this->wellCounter = $wellCounter;

        // set the owning side of the relation if necessary
        if ($wellCounter->getCitizen() !== $this) {
            $wellCounter->setCitizen($this);
        }

        return $this;
    }

    public function getZone(): ?Zone
    {
        return $this->zone;
    }

    public function setZone(?Zone $zone): self
    {
        $this->zone = $zone;

        return $this;
    }

    /**
     * @return Collection|DigTimer[]
     */
    public function getDigTimers(): Collection
    {
        return $this->digTimers;
    }

    public function addDigTimer(DigTimer $digTimer): self
    {
        if (!$this->digTimers->contains($digTimer)) {
            $this->digTimers[] = $digTimer;
            $digTimer->setCitizen($this);
        }

        return $this;
    }

    public function removeDigTimer(DigTimer $digTimer): self
    {
        if ($this->digTimers->contains($digTimer)) {
            $this->digTimers->removeElement($digTimer);
            // set the owning side to null (unless already changed)
            if ($digTimer->getCitizen() === $this) {
                $digTimer->setCitizen(null);
            }
        }

        return $this;
    }

    public function getDailyUpgradeVote(): ?DailyUpgradeVote
    {
        return $this->dailyUpgradeVote;
    }

    public function setDailyUpgradeVote(?DailyUpgradeVote $dailyUpgradeVote): self
    {
        $this->dailyUpgradeVote = $dailyUpgradeVote;

        // set the owning side of the relation if necessary
        if ($dailyUpgradeVote !== null && $dailyUpgradeVote->getCitizen() !== $this) {
            $dailyUpgradeVote->setCitizen($this);
        }

        return $this;
    }

    public function getWalkingDistance(): ?int
    {
        return $this->walkingDistance;
    }

    public function setWalkingDistance(int $walkingDistance): self
    {
        $this->walkingDistance = $walkingDistance;

        return $this;
    }

    public function getSurvivedDays(): ?int
    {
        return $this->survivedDays;
    }

    public function setSurvivedDays(int $survivedDays): self
    {
        $this->survivedDays = $survivedDays;

        return $this;
    }

    public function getCauseOfDeath(): ?CauseOfDeath
    {
        return $this->causeOfDeath;
    }

    public function setCauseOfDeath(?CauseOfDeath $causeOfDeath): self
    {
        $this->causeOfDeath = $causeOfDeath;

        return $this;
    }

    public function getBp(): ?int
    {
        return $this->Bp;
    }

    public function setBp(int $Bp): self
    {
        $this->Bp = $Bp;

        return $this;
    }

    /**
     * @return Collection|ExpeditionRoute[]
     */
    public function getExpeditionRoutes(): Collection
    {
        return $this->expeditionRoutes;
    }

    public function addExpeditionRoute(ExpeditionRoute $expeditionRoute): self
    {
        if (!$this->expeditionRoutes->contains($expeditionRoute)) {
            $this->expeditionRoutes[] = $expeditionRoute;
            $expeditionRoute->setOwner($this);
        }

        return $this;
    }

    public function removeExpeditionRoute(ExpeditionRoute $expeditionRoute): self
    {
        if ($this->expeditionRoutes->contains($expeditionRoute)) {
            $this->expeditionRoutes->removeElement($expeditionRoute);
            // set the owning side to null (unless already changed)
            if ($expeditionRoute->getOwner() === $this) {
                $expeditionRoute->setOwner(null);
            }
        }

        return $this;
    }

    public function getBanished(): ?bool
    {
        return $this->banished;
    }

    public function setBanished(bool $banished): self
    {
        $this->banished = $banished;

        return $this;
    }

    /**
     * @return Collection|Complaint[]
     */
    public function getComplaints(): Collection
    {
        return $this->complaints;
    }

    public function addComplaint(Complaint $complaint): self
    {
        if (!$this->complaints->contains($complaint)) {
            $this->complaints[] = $complaint;
            $complaint->setCulprit($this);
        }

        return $this;
    }

    public function removeComplaint(Complaint $complaint): self
    {
        if ($this->complaints->contains($complaint)) {
            $this->complaints->removeElement($complaint);
            // set the owning side to null (unless already changed)
            if ($complaint->getCulprit() === $this) {
                $complaint->setCulprit(null);
            }
        }

        return $this;
    }

    public function getTrashCounter(): ?TrashCounter
    {
        return $this->trashCounter;
    }

    public function setTrashCounter(?TrashCounter $trashCounter): self
    {
        $this->trashCounter = $trashCounter;

        return $this;
    }

    /**
     * @return Collection|HeroicActionPrototype[]
     */
    public function getHeroicActions(): Collection
    {
        return $this->heroicActions;
    }

    public function addHeroicAction(HeroicActionPrototype $heroicAction): self
    {
        if (!$this->heroicActions->contains($heroicAction)) {
            $this->heroicActions[] = $heroicAction;
        }

        return $this;
    }

    public function removeHeroicAction(HeroicActionPrototype $heroicAction): self
    {
        if ($this->heroicActions->contains($heroicAction)) {
            $this->heroicActions->removeElement($heroicAction);
        }

        return $this;
    }
}
