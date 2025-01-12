<?php

namespace App\Entity;

use App\Enum\ActionHandler\PointType;
use App\Enum\Game\CitizenPersistentCache;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;

#[ORM\Entity(repositoryClass: 'App\Repository\CitizenRepository')]
#[ORM\HasLifecycleCallbacks]
#[ORM\UniqueConstraint(
    name: "citizen_user",fields: ["user", "town"]
)]
class Citizen
{
    const Thrown = 1;
    const Watered = 2;
    const Cooked = 3;
    const Ghoul = 4;
    const Burned = 5;
    const BurnedUseless = 6;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;
    #[ORM\Column(type: 'boolean')]
    private bool $alive = true;
    #[ORM\Column(type: 'smallint')]
    private int $ap = 6;
    #[ORM\Column(type: 'boolean')]
    private bool $active = true;
    #[ORM\ManyToOne(targetEntity: 'App\Entity\User', inversedBy: 'citizens')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user;
    #[ORM\ManyToMany(targetEntity: 'App\Entity\CitizenStatus')]
    private Collection $status;
    #[ORM\ManyToOne(targetEntity: 'App\Entity\CitizenProfession')]
    #[ORM\JoinColumn(nullable: false)]
    private ?CitizenProfession $profession = null;
    #[ORM\ManyToMany(targetEntity: 'App\Entity\CitizenRole')]
    private Collection $roles;
    #[ORM\ManyToMany(targetEntity: 'App\Entity\CitizenVote', orphanRemoval: true, cascade: ['persist', 'remove'])]
    private Collection $votes;
    #[ORM\OneToOne(targetEntity: 'App\Entity\Inventory', inversedBy: 'citizen', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?Inventory $inventory = null;
    #[ORM\ManyToOne(targetEntity: 'App\Entity\Town', inversedBy: 'citizens')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Town $town = null;
    #[ORM\OneToOne(targetEntity: 'App\Entity\CitizenHome', inversedBy: 'citizen', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?CitizenHome $home = null;
    #[ORM\ManyToOne(targetEntity: 'App\Entity\Zone', inversedBy: 'citizens', fetch: 'EXTRA_LAZY')]
    private ?Zone $zone = null;
    #[ORM\OneToMany(targetEntity: 'App\Entity\DigTimer', mappedBy: 'citizen', orphanRemoval: true)]
    private Collection $digTimers;
    #[ORM\OneToOne(targetEntity: 'App\Entity\DailyUpgradeVote', mappedBy: 'citizen', cascade: ['persist', 'remove'])]
    private ?DailyUpgradeVote $dailyUpgradeVote = null;
    #[ORM\Column(type: 'integer')]
    private int $walkingDistance = 0;
    #[ORM\Column(type: 'integer')]
    private int $survivedDays = 0;
    #[ORM\ManyToOne(targetEntity: 'App\Entity\CauseOfDeath')]
    #[ORM\JoinColumn(nullable: false)]
    private ?CauseOfDeath $causeOfDeath;
    #[ORM\Column(type: 'integer')]
    private int $Bp = 0;
    #[ORM\OneToMany(targetEntity: 'App\Entity\ExpeditionRoute', mappedBy: 'owner', orphanRemoval: true, cascade: ['persist', 'remove'])]
    private Collection $expeditionRoutes;
    #[ORM\Column(type: 'boolean')]
    private bool $banished = false;
    #[ORM\OneToMany(targetEntity: 'App\Entity\Complaint', mappedBy: 'culprit', orphanRemoval: true)]
    private Collection $complaints;
    #[ORM\ManyToMany(targetEntity: 'App\Entity\HeroicActionPrototype')]
    private Collection $heroicActions;
    #[ORM\Column(type: 'integer')]
    private int $campingCounter = 0;
    #[ORM\Column(type: 'integer')]
    private int $campingTimestamp = 0;
    #[ORM\Column(type: 'float')]
    private float $campingChance = 0;
    #[ORM\OneToMany(targetEntity: 'App\Entity\ActionCounter', mappedBy: 'citizen', orphanRemoval: true, cascade: ['persist', 'remove'])]
    private Collection $actionCounters;
    #[ORM\Column(type: 'integer')]
    private int $lastActionTimestamp = 0;
    #[ORM\Column(type: 'integer')]
    private int $pm = 0;
    #[ORM\OneToOne(targetEntity: 'App\Entity\CitizenEscortSettings', inversedBy: 'citizen', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?CitizenEscortSettings $escortSettings = null;
    #[ORM\OneToMany(targetEntity: 'App\Entity\CitizenEscortSettings', mappedBy: 'leader')]
    private Collection $leadingEscorts;
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $lastWords = null;
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $comment = null;
    #[ORM\Column(type: 'text', nullable: true, length: 24)]
    private ?string $alias = null;
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $disposed = null;
    #[ORM\ManyToMany(targetEntity: 'App\Entity\Citizen')]
    #[ORM\JoinTable(name: 'citizen_disposed')]
    #[ORM\JoinColumn(name: 'id', referencedColumnName: 'id', unique: true)]
    #[ORM\InverseJoinColumn(name: 'disposed_by_id', referencedColumnName: 'id')]
    private Collection $disposedBy;
    #[ORM\OneToMany(targetEntity: 'App\Entity\CitizenWatch', mappedBy: 'citizen', orphanRemoval: true)]
    private Collection $citizenWatch;
    #[ORM\Column(type: 'integer')]
    private int $ghulHunger = 0;
    #[ORM\OneToMany(targetEntity: PrivateMessageThread::class, mappedBy: 'recipient', orphanRemoval: true)]
    private Collection $privateMessageThreads;
    #[ORM\OneToOne(targetEntity: CitizenRankingProxy::class, mappedBy: 'citizen', cascade: ['persist'])]
    private ?CitizenRankingProxy $rankingEntry = null;
    #[ORM\OneToMany(targetEntity: RuinExplorerStats::class, mappedBy: 'citizen', orphanRemoval: true, cascade: ['persist', 'remove'])]
    private Collection $explorerStats;
    #[ORM\OneToOne(targetEntity: BuildingVote::class, mappedBy: 'citizen', cascade: ['persist', 'remove'])]
    private ?BuildingVote $buildingVote = null;
    #[ORM\ManyToMany(targetEntity: HelpNotificationMarker::class)]
    private Collection $helpNotifications;
    #[ORM\Column(type: 'boolean')]
    private bool $hasSeenGazette = false;
    #[ORM\Column(type: 'boolean')]
    private bool $hasEaten = false;
    #[ORM\ManyToMany(targetEntity: SpecialActionPrototype::class)]
    private Collection $specialActions;
    #[ORM\Column(type: 'integer')]
    private int $dayOfDeath = 1;
    #[ORM\ManyToMany(targetEntity: HeroicActionPrototype::class)]
    #[ORM\JoinTable(name: 'citizen_used_heroic_action_prototype')]
    private Collection $usedHeroicActions;
    #[ORM\ManyToMany(targetEntity: Zone::class, orphanRemoval: true, cascade: ['persist'])]
    #[ORM\JoinTable(name: 'citizen_visited_zones')]
    private Collection $visitedZones;
    #[ORM\Column(type: 'boolean')]
    private bool $coalized = false;

    #[ORM\OneToMany(mappedBy: 'citizen', targetEntity: ZoneActivityMarker::class, cascade: ['persist', 'remove'], fetch: 'EXTRA_LAZY', orphanRemoval: true)]
    private Collection $zoneActivityMarkers;

    #[ORM\Column]
    private int $sp = 0;

    #[ORM\OneToOne(cascade: ['persist', 'remove'], fetch: 'LAZY', orphanRemoval: true)]
    #[ORM\JoinColumn(nullable: true)]
    private ?CitizenProperties $properties = null;

    public function __construct()
    {
        $this->status = new ArrayCollection();
        $this->digTimers = new ArrayCollection();
        $this->expeditionRoutes = new ArrayCollection();
        $this->complaints = new ArrayCollection();
        $this->heroicActions = new ArrayCollection();
        $this->actionCounters = new ArrayCollection();
        $this->roles = new ArrayCollection();
        $this->votes = new ArrayCollection();
        $this->leadingEscorts = new ArrayCollection();
        $this->disposedBy = new ArrayCollection();
        $this->citizenWatch = new ArrayCollection();
        $this->privateMessageThreads = new ArrayCollection();
        $this->explorerStats = new ArrayCollection();
        $this->helpNotifications = new ArrayCollection();
        $this->specialActions = new ArrayCollection();
        $this->usedHeroicActions = new ArrayCollection();
        $this->visitedZones = new ArrayCollection();
        $this->zoneActivityMarkers = new ArrayCollection();
    }
    public function __toString()
    {
        return $this->getId() ? "Citizen#{$this->getId()}" : "Citizen##{$this->getUser()->getId()}#{$this->getTown()->getId()}";
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
    public function hasStatus(string $status_name): bool
    {
        foreach ($this->getStatus() as $status)
            if ($status->getName() === $status_name) return true;
        return false;
    }

    public function hasAnyStatus(string ...$status_names): bool
    {
        return !empty(array_intersect( $status_names, $this->getStatus()->map( fn(CitizenStatus $s) => $s->getName() )->toArray() ));
    }

    public function hasAllStatus(string ...$status_names): bool
    {
        return count(array_intersect( $status_names, $this->getStatus()->map( fn(CitizenStatus $s) => $s->getName() )->toArray() )) === count($status_names);
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
    /**
     * @return Collection|CitizenRole[]
     */
    public function getRoles(): Collection
    {
        return $this->roles;
    }
    /**
     * @return Collection|CitizenRole[]
     */
    public function getVisibleRoles(): Collection
    {
        return $this->getRoles()->filter(fn(CitizenRole $r) => !$r->getHidden());
    }
    public function addRole(CitizenRole $role): self
    {
        if (!$this->roles->contains($role)) {
            $this->roles[] = $role;
        }

        return $this;
    }
    public function removeRole(CitizenRole $role): self
    {
        if ($this->roles->contains($role)) {
            $this->roles->removeElement($role);
        }

        return $this;
    }
    public function hasRole(string $name): bool {
        foreach ($this->getRoles() as $role)
            if ($role->getName() === $name) return true;
        return false;
    }
    /**
     * @return Collection|CitizenVote[]
     */
    public function getVotes(): Collection
    {
        return $this->votes;
    }
    public function addVote(CitizenVote $vote): self
    {
        if (!$this->votes->contains($vote)) {
            $this->votes[] = $vote;
        }

        return $this;
    }
    public function removeVote(CitizenVote $vote): self
    {
        if ($this->votes->contains($vote)) {
            $this->votes->removeElement($vote);
        }

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
    public function getAlias(): ?string
    {
        return $this->alias;
    }
    public function setAlias(?string $alias): self
    {
        $this->alias = $alias;

        return $this;
    }
    public function getName(): string
    {
        return $this->getAlias() ?? $this->getUser()->getName();
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
    public function getZone(): ?Zone
    {
        return $this->zone;
    }
    public function setZone(?Zone $zone): self
    {
        $this->zone = $zone;
        if($zone) $this->addVisitedZone($zone);

        return $this;
    }
    /**
     * @return ArrayCollection<DigTimer>|PersistentCollection<DigTimer>
     */
    public function getDigTimers(): ArrayCollection|PersistentCollection
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
    public function getCurrentDigTimer(): ?DigTimer {
        if (!$this->getZone()) return null;

        return
            $this->getDigTimers()->matching(
                (new Criteria())
                    ->where(Criteria::expr()->eq( 'zone', $this->getZone() ))
            )->first() ?: null;
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
    public function getCampingCounter(): int
    {
      return $this->campingCounter;
    }
    public function setCampingCounter(int $campingCounter): self
    {
      $this->campingCounter = $campingCounter;

      return $this;
    }
    public function getCampingTimestamp(): int
    {
        return $this->campingTimestamp;
    }
    public function setCampingTimestamp(int $campingTimestamp): self
    {
        $this->campingTimestamp = $campingTimestamp;

        return $this;
    }
    public function getCampingChance(): float
    {
        return $this->campingChance;
    }
    public function setCampingChance(float $campingChance): self
    {
        $this->campingChance = $campingChance;

        return $this;
    }
    /**
     * @return Collection|ActionCounter[]
     */
    public function getActionCounters(): Collection
    {
        return $this->actionCounters;
    }
    public function addActionCounter(ActionCounter $actionCounter): self
    {
        if (!$this->actionCounters->contains($actionCounter)) {
            $this->actionCounters[] = $actionCounter;
            $actionCounter->setCitizen($this);
        }

        return $this;
    }
    public function removeActionCounter(ActionCounter $actionCounter): self
    {
        if ($this->actionCounters->contains($actionCounter)) {
            $this->actionCounters->removeElement($actionCounter);
            // set the owning side to null (unless already changed)
            if ($actionCounter->getCitizen() === $this) {
                $actionCounter->setCitizen(null);
            }
        }

        return $this;
    }
    public function getSpecificActionCounterValue( int $type, ?int $ref = null ): int {
        foreach ($this->getActionCounters() as $c)
            if ($c->getType() === $type && ($ref === null || $c->getReferenceID() === $ref)) return $c->getCount();
        return 0;
    }
    public function getSpecificActionCounter( int $type, ?int $ref = null ): ActionCounter {
        foreach ($this->getActionCounters() as $c)
            if ($c->getType() === $type && ($ref === null || $c->getReferenceID() === $ref)) return $c;
        $a = (new ActionCounter())->setType($type);
        if ($ref !== null) $a->setReferenceID( $ref );

        $this->addActionCounter($a);
        return $a;
    }
    public function isOnline(): bool {
        $ts = $this->getLastActionTimestamp();
        return $ts ? (time() - $ts) < 300 : false;
    }
    public function isDigging(): bool {
        $zone = $this->getZone();
        if (!$zone) return false;
        foreach ($this->getDigTimers() as $digTimer)
            if ($digTimer->getZone()->getId() === $zone->getId())
                return !$digTimer->getPassive();
        return false;
    }
    public function hasDigTimer(): bool {
        $zone = $this->getZone();
        if (!$zone) return false;
        foreach ($this->getDigTimers() as $digTimer)
            if ($digTimer->getZone()->getId() === $zone->getId())
                return true;
        return false;
    }
    public function hasPassiveDigTimer(): bool {
        $zone = $this->getZone();
        if (!$zone) return false;
        foreach ($this->getDigTimers() as $digTimer)
            if ($digTimer->getZone()->getId() === $zone->getId())
                return $digTimer->getPassive();
        return false;
    }
    public function getDigTimeout(): int {
        $zone = $this->getZone();
        if (!$zone) return -1;
        foreach ($this->getDigTimers() as $digTimer)
            if ($digTimer->getZone()->getId() === $zone->getId())
                return $digTimer->getTimestamp()->getTimestamp() - (new DateTime())->getTimestamp();
        return -1;
    }
    public function isCamping(): bool {
        foreach ($this->getStatus() as $status)
            if (in_array( $status->getName(), ['tg_tomb','tg_hide'] ))
                return true;
        return false;
    }
    public function getEscortSettings(): ?CitizenEscortSettings
    {
        return $this->escortSettings;
    }
    public function setEscortSettings(?CitizenEscortSettings $escortSettings): self
    {
        $this->escortSettings = $escortSettings;

        return $this;
    }
    public function getLastActionTimestamp(): int
    {
        return $this->lastActionTimestamp;
    }
    public function setLastActionTimestamp(int $lastActionTimestamp): self
    {
        $this->lastActionTimestamp = $lastActionTimestamp;

        return $this;
    }
    public function getPm(): ?int
    {
        return $this->pm;
    }
    public function setPm(int $pm): self
    {
        $this->pm = $pm;

        return $this;
    }
    /**
     * @return Collection|CitizenEscortSettings[]
     */
    public function getLeadingEscorts(): Collection
    {
        return $this->leadingEscorts;
    }
    public function addLeadingEscort(CitizenEscortSettings $leadingEscort): self
    {
        if (!$this->leadingEscorts->contains($leadingEscort)) {
            $this->leadingEscorts[] = $leadingEscort;
            $leadingEscort->setLeader($this);
        }

        return $this;
    }
    /**
     * @return CitizenEscortSettings[]
     */
    public function getValidLeadingEscorts(): array {
        return array_filter( $this->getLeadingEscorts()->getValues(), function(CitizenEscortSettings $s) {
            return $s->getCitizen()->getZone() !== null && $s->getCitizen()->getZone() === $s->getLeader()->getZone();
        } );
    }

    public function removeLeadingEscort(CitizenEscortSettings $leadingEscort): self
    {
        if ($this->leadingEscorts->contains($leadingEscort)) {
            $this->leadingEscorts->removeElement($leadingEscort);
            // set the owning side to null (unless already changed)
            if ($leadingEscort->getLeader() === $this) {
                $leadingEscort->setLeader(null);
            }
        }

        return $this;
    }
    public function getLastWords(): ?string
    {
        return $this->lastWords;
    }
    public function setLastWords(string $lastWords): self
    {
        $this->lastWords = $lastWords;

        return $this;
    }
    public function getComment(): ?string
    {
        return $this->comment;
    }
    public function setComment(string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }
    public function getDisposed(): ?int
    {
        return $this->disposed;
    }
    public function setDisposed(int $disposed): self
    {
        $this->disposed = $disposed;

        return $this;
    }
    /**
     * @return Collection|Citizen[]
     */
    public function getDisposedBy(): Collection
    {
        return $this->disposedBy;
    }
    public function addDisposedBy(Citizen $citizen): self
    {
        if (!$this->disposedBy->contains($citizen)) {
            $this->disposedBy[] = $citizen;
        }

        return $this;
    }
    public function removeDisposedBy(Citizen $citizen): self
    {
        if ($this->disposedBy->contains($citizen)) {
            $this->disposedBy->removeElement($citizen);
        }

        return $this;
    }
    /**
     * @return Collection|CitizenWatch[]
     */
    public function getCitizenWatch()
    {
        return $this->citizenWatch;
    }
    public function addCitizenWatch(?CitizenWatch $citizenWatch): self
    {
        if(!$this->citizenWatch->contains($citizenWatch))
            $this->citizenWatch[] = $citizenWatch;

        return $this;
    }
    public function removeCitizenWatch(?CitizenWatch $citizenWatch): self
    {
        if($this->citizenWatch->contains($citizenWatch))
        $this->citizenWatch->removeElement($citizenWatch);

        return $this;
    }
    public function getGhulHunger(): ?int
    {
        return $this->ghulHunger;
    }
    public function setGhulHunger(int $ghulHunger): self
    {
        $this->ghulHunger = $ghulHunger;

        return $this;
    }
    /**
     * @return Collection|PrivateMessageThread[]
     */
    public function getPrivateMessageThreads(): Collection
    {
        return $this->privateMessageThreads;
    }
    public function addPrivateMessageThread(PrivateMessageThread $privateMessageThread): self
    {
        if (!$this->privateMessageThreads->contains($privateMessageThread)) {
            $this->privateMessageThreads[] = $privateMessageThread;
            $privateMessageThread->setRecipient($this);
        }

        return $this;
    }
    public function removePrivateMessageThread(PrivateMessageThread $privateMessageThread): self
    {
        if ($this->privateMessageThreads->contains($privateMessageThread)) {
            $this->privateMessageThreads->removeElement($privateMessageThread);
            // set the owning side to null (unless already changed)
            if ($privateMessageThread->getRecipient() === $this) {
                $privateMessageThread->setRecipient(null);
            }
        }

        return $this;
    }
    public function getRankingEntry(): ?CitizenRankingProxy
    {
        return $this->rankingEntry;
    }
    public function setRankingEntry(?CitizenRankingProxy $rankingEntry): self
    {
        $this->rankingEntry = $rankingEntry;

        return $this;
    }
    /**
     * @param PostPersistEventArgs $args
     */
    #[ORM\PostPersist]
    public function lifeCycle_createCitizenRankingProxy(PostPersistEventArgs $args): void
    {
        $args->getObjectManager()->persist( CitizenRankingProxy::fromCitizen($this) );
        $args->getObjectManager()->flush();
    }
    /**
     * If the citizen is currently exploring a ruin or has explored a ruin at this location today, the relevant
     * RuinExplorerStats object will be returned. Otherwise, null is returned.
     * @return RuinExplorerStats|null
     */
    public function currentExplorerStats(): ?RuinExplorerStats {
        if ($this->getZone())
            foreach ($this->getExplorerStats() as $explorerStat)
                if ($explorerStat->getZone()->getId() === $this->getZone()->getId())
                    return $explorerStat;
        return null;
    }
    /**
     * If the citizen is currently exploring a ruin, the relevant RuinExplorerStats object will be returned. Otherwise,
     * null is returned.
     * @return RuinExplorerStats|null
     */
    public function activeExplorerStats(): ?RuinExplorerStats {
        return (($ex = $this->currentExplorerStats()) && $ex->getActive()) ? $ex : null;
    }
    /**
     * @return Collection|RuinExplorerStats[]
     */
    public function getExplorerStats(): Collection
    {
        return $this->explorerStats;
    }
    public function addExplorerStat(RuinExplorerStats $explorerStat): self
    {
        if (!$this->explorerStats->contains($explorerStat)) {
            $this->explorerStats[] = $explorerStat;
            $explorerStat->setCitizen($this);
        }

        return $this;
    }
    public function removeExplorerStat(RuinExplorerStats $explorerStat): self
    {
        if ($this->explorerStats->contains($explorerStat)) {
            $this->explorerStats->removeElement($explorerStat);
            $explorerStat->getZone()->getExplorerStats()->removeElement($explorerStat);

            // set the owning side to null (unless already changed)
            if ($explorerStat->getCitizen() === $this) {
                $explorerStat->setCitizen(null);
            }
        }

        return $this;
    }
    public function getBuildingVote(): ?BuildingVote
    {
        return $this->buildingVote;
    }
    public function setBuildingVote(?BuildingVote $buildingVote): self
    {
        $this->buildingVote = $buildingVote;

        // set the owning side of the relation if necessary
        if ($buildingVote !== null && $buildingVote->getCitizen() !== $this) {
            $buildingVote->setCitizen($this);
        }

        return $this;
    }
    /**
     * @return Collection|HelpNotificationMarker[]
     */
    public function getHelpNotifications(): Collection
    {
        return $this->helpNotifications;
    }
    public function hasSeenHelpNotification(string $name): bool {
        foreach ($this->getHelpNotifications() as $notification)
            if ($notification->getName() === $name) return true;
        return false;
    }
    public function addHelpNotification(HelpNotificationMarker $helpNotification): self
    {
        if (!$this->helpNotifications->contains($helpNotification)) {
            $this->helpNotifications[] = $helpNotification;
        }

        return $this;
    }
    public function removeHelpNotification(HelpNotificationMarker $helpNotification): self
    {
        if ($this->helpNotifications->contains($helpNotification)) {
            $this->helpNotifications->removeElement($helpNotification);
        }

        return $this;
    }
    public function getHasSeenGazette(): ?bool
    {
        return $this->hasSeenGazette;
    }
    public function setHasSeenGazette(bool $hasSeenGazette): self
    {
        $this->hasSeenGazette = $hasSeenGazette;

        return $this;
    }
    public function getHasEaten(): ?bool
    {
        return $this->hasEaten;
    }
    public function setHasEaten(bool $hasEaten): self
    {
        $this->hasEaten = $hasEaten;

        return $this;
    }
    /**
     * @return Collection|SpecialActionPrototype[]
     */
    public function getSpecialActions(): Collection
    {
        return $this->specialActions;
    }
    public function addSpecialAction(SpecialActionPrototype $specialAction): self
    {
        if (!$this->specialActions->contains($specialAction)) {
            $this->specialActions[] = $specialAction;
        }

        return $this;
    }
    public function removeSpecialAction(SpecialActionPrototype $specialAction): self
    {
        $this->specialActions->removeElement($specialAction);

        return $this;
    }
    public function getDayOfDeath(): ?int
    {
        return $this->dayOfDeath;
    }
    public function setDayOfDeath(int $dayOfDeath): self
    {
        $this->dayOfDeath = $dayOfDeath;

        return $this;
    }
    /**
     * @return Collection|HeroicActionPrototype[]
     */
    public function getUsedHeroicActions(): Collection
    {
        return $this->usedHeroicActions;
    }
    public function addUsedHeroicAction(HeroicActionPrototype $usedHeroicAction): self
    {
        if (!$this->usedHeroicActions->contains($usedHeroicAction)) {
            $this->usedHeroicActions[] = $usedHeroicAction;
        }

        return $this;
    }
    public function removeUsedHeroicAction(HeroicActionPrototype $usedHeroicAction): self
    {
        $this->usedHeroicActions->removeElement($usedHeroicAction);

        return $this;
    }
    /**
     * @return Collection|Zone[]
     */
    public function getVisitedZones(): Collection
    {
        return $this->visitedZones;
    }
    public function addVisitedZone(Zone $visitedZone): self
    {
        if (!$this->visitedZones->contains($visitedZone)) {
            $this->visitedZones[] = $visitedZone;
        }

        return $this;
    }
    public function removeVisitedZone(Zone $visitedZone): self
    {
        $this->visitedZones->removeElement($visitedZone);

        return $this;
    }
    public function getCoalized(): ?bool
    {
        return $this->coalized;
    }
    public function setCoalized(bool $coalized): self
    {
        $this->coalized = $coalized;

        return $this;
    }

    /**
     * @return Collection<int, ZoneActivityMarker>
     */
    public function getZoneActivityMarkers(): Collection
    {
        return $this->zoneActivityMarkers;
    }

    public function addZoneActivityMarker(ZoneActivityMarker $zoneActivityMarker): self
    {
        if (!$this->zoneActivityMarkers->contains($zoneActivityMarker)) {
            $this->zoneActivityMarkers->add($zoneActivityMarker);
            $zoneActivityMarker->setCitizen($this);
        }

        return $this;
    }

    public function removeZoneActivityMarker(ZoneActivityMarker $zoneActivityMarker): self
    {
        if ($this->zoneActivityMarkers->removeElement($zoneActivityMarker)) {
            // set the owning side to null (unless already changed)
            if ($zoneActivityMarker->getCitizen() === $this) {
                $zoneActivityMarker->setCitizen(null);
            }
        }

        return $this;
    }

    public function giveGenerosityBonus(int $number): self {
        $this->getRankingEntry()->setGenerosityBonus( $this->getRankingEntry()->getGenerosityBonus() + $number );
        return $this;
    }

    public function registerPropInPersistentCache(CitizenPersistentCache|string $cache, int $value = 1): self {
        $this->getRankingEntry()?->registerProperty( $cache, $value );
        return $this;
    }

    public function getPropFromPersistentCache(CitizenPersistentCache|string $cache, int $default = 0): int {
        return $this->getRankingEntry()?->getProperty( $cache ) ?? $default;
    }

    public function getPoints(PointType $t): int {
        return match ($t) {
            PointType::AP => $this->getAp(),
            PointType::CP => $this->getBp(),
            PointType::MP => $this->getPm(),
            PointType::SP => $this->getSp(),
        };
    }

    public function getSp(): ?int
    {
        return $this->sp;
    }

    public function setSp(int $sp): static
    {
        $this->sp = $sp;

        return $this;
    }

    public function getProperties(): ?CitizenProperties
    {
        return $this->properties;
    }

    public function setProperties(CitizenProperties $properties): static
    {
        $this->properties = $properties;

        return $this;
    }

    public function property(\App\Enum\Configuration\CitizenProperties $v): mixed {
        $props = $this->getProperties();
        return $props ? $props->get( $v ) : $v->default();
    }

    private ?array $fullPropertySet = null;

    public function fullPropertySet(): array {
        return $this->fullPropertySet ?? ($this->fullPropertySet = array_combine(
            array_map( fn(\App\Enum\Configuration\CitizenProperties $s) => $s->translationKey(), \App\Enum\Configuration\CitizenProperties::validCases() ),
            array_map( fn(\App\Enum\Configuration\CitizenProperties $s) => $this->property($s), \App\Enum\Configuration\CitizenProperties::validCases() ),
        ));
    }

    public function mapStatusName(string $n): string {
        $map = $this->property(\App\Enum\Configuration\CitizenProperties::StatusOverrideMap) ?? [];
        return $map[$n] ?? $n;
    }
}
