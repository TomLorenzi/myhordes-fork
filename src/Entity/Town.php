<?php

namespace App\Entity;

use App\Enum\GameProfileEntryType;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\OrderBy;
use Doctrine\ORM\ORMException;

/**
 * @ORM\Entity(repositoryClass="App\Repository\TownRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Town
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private ?int $id = null;

    /**
     * @ORM\Column(type="string", length=190)
     */
    private ?string $name = null;

    /**
     * @ORM\Column(type="string", length=8)
     */
    private ?string $language = null;

    /**
     * @ORM\Column(type="integer")
     */
    private ?int $population = null;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $wordsOfHeroes = null;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Citizen", mappedBy="town", orphanRemoval=true, cascade={"persist", "remove"})
     * @OrderBy({"alive" = "DESC", "id" = "ASC"})
     */
    private $citizens;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\Inventory", inversedBy="town", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private ?Inventory $bank = null;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\TownClass", inversedBy="towns")
     * @ORM\JoinColumn(nullable=false)
     */
    private ?TownClass $type = null;

    /**
     * @ORM\Column(type="integer")
     */
    private int $day = 1;

    /**
     * @ORM\Column(type="integer")
     */
    private ?int $well = 0;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Zone", mappedBy="town", orphanRemoval=true, cascade={"persist", "remove"})
     */
    private $zones;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $door = false;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $chaos = false;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $devastated = false;

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
     * @ORM\OneToMany(targetEntity="App\Entity\Gazette", mappedBy="town", orphanRemoval=true, cascade={"persist"})
     */
    private $gazettes;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\Forum", mappedBy="town", cascade={"persist", "remove"})
     */
    private ?Forum $forum = null;

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    private array $conf = [];

    /**
     * @ORM\Column(type="integer")
     */
    private int $soulDefense = 0;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Season", inversedBy="towns")
     */
    private ?Season $season = null;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\CitizenWatch", mappedBy="town", orphanRemoval=true)
     */
    private $citizenWatches;

    /**
     * @ORM\OneToOne(targetEntity=TownRankingProxy::class, mappedBy="town", cascade={"persist"})
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     */
    private ?TownRankingProxy $rankingEntry = null;

    /**
     * @ORM\ManyToOne(targetEntity=AttackSchedule::class)
     */
    private $lastAttack;

    /**
     * @ORM\Column(type="integer")
     */
    private int $attackFails = 0;

    /**
     * @ORM\Column(type="string", length=90, nullable=true)
     */
    private ?string $password = null;

    /**
     * @ORM\Column(type="integer")
     */
    private int $dayWithoutAttack = 0;

    /**
     * @ORM\ManyToOne(targetEntity=User::class)
     */
    private ?User $creator = null;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\TownLogEntry", mappedBy="town", cascade={"remove"})
     */
    private $_townLogEntries;

    /**
     * @ORM\Column(type="string", length=24, nullable=true)
     */
    private ?string $deriveConfigFrom = null;

    /**
     * @ORM\Column(type="integer")
     */
    private int $insurrectionProgress = 0;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $managedEvents = false;

    /**
     * @ORM\Column(type="integer")
     */
    private int $profilerVersion = 0;

    /**
     * @ORM\Column(type="integer")
     */
    private $strangerPower = 0;

    /**
     * @ORM\Column(type="boolean")
     */
    private $strangerEnabled = false;

    /**
     * @ORM\Column(type="boolean")
     */
    private $forceStartAhead = false;

    public function __construct()
    {
        $this->citizens = new ArrayCollection();
        $this->zones = new ArrayCollection();
        $this->buildings = new ArrayCollection();
        $this->zombieEstimations = new ArrayCollection();
        $this->citizenWatches = new ArrayCollection();
        $this->_townLogEntries = new ArrayCollection();
        $this->gazettes = new ArrayCollection();
        $this->profilerVersion = GameProfileEntryType::latest_version();
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

    public function getRealLanguage(array $allowedLangs): ?string {
        return in_array($this->getLanguage(), $allowedLangs) ? $this->getLanguage() : null;
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
        return $this->getCitizens()->count();
    }

    public function getAliveCitizenCount(): int {
        $cc = 0;
        foreach ($this->getCitizens() as $c)
            if ($c->getAlive()) $cc++;
            return $cc;
    }

    public function getActiveCitizenCount(): int {
        $cc = 0;
        foreach ($this->getCitizens() as $c)
            if ($c->getActive()) $cc++;
        return $cc;
    }

    public function isOpen(): bool {
        return $this->getDay() === 1 && $this->getCitizenCount() < $this->getPopulation();
    }

    public function userInTown(User $user): bool {
        foreach ($this->getCitizens() as $citizen) {
            if($citizen->getUser() === $user)
                return true;
        }
        return false;
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

    /**
     * @return Collection|Gazette[]
     */
    public function getGazettes(): Collection
    {
        return $this->gazettes;
    }

    public function addGazette(Gazette $gazette): self
    {
        if (!$this->gazettes->contains($gazette)) {
            $this->gazettes[] = $gazette;
            $gazette->setTown($this);
        }

        return $this;
    }

    public function removeGazette(Gazette $gazette): self
    {
        if ($this->gazettes->contains($gazette)) {
            $this->gazettes->removeElement($gazette);
            // set the owning side to null (unless already changed)
            if ($gazette->getTown() === $this) {
                $gazette->setTown(null);
            }
        }

        return $this;
    }

    public function findGazette( int $day, bool $make = false ): ?Gazette {
        foreach ($this->getGazettes() as $gazette)
            if ($gazette->getDay() === $day)
                return $gazette;
        if ($make) {
            $this->addGazette(
                ($gazette = new Gazette())
                    ->setTown($this)->setDay($day)
            );
            return $gazette;
        }
        return null;
    }

    public function getConf(): ?array
    {
        return $this->conf;
    }

    public function setConf(?array $conf): self
    {
        $this->conf = $conf ?? [];

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

    public function getCoalizedCitizenCount(): int {
        $c = 0;
        foreach ($this->getCitizens() as $citizen)
            if ($citizen->getCoalized()) $c++;
        return $c;
    }

    /**
     * @return Collection|CitizenWatch[]
     */
    public function getCitizenWatches(): Collection
    {
        return $this->citizenWatches;
    }

    public function addCitizenWatch(CitizenWatch $citizenWatch): self
    {
        if (!$this->citizenWatches->contains($citizenWatch)) {
            $this->citizenWatches[] = $citizenWatch;
            $citizenWatch->setTown($this);
        }

        return $this;
    }

    public function removeCitizenWatch(CitizenWatch $citizenWatch): self
    {
        if ($this->citizenWatches->contains($citizenWatch)) {
            $this->citizenWatches->removeElement($citizenWatch);
            // set the owning side to null (unless already changed)
            if ($citizenWatch->getTown() === $this) {
                $citizenWatch->setTown(null);
            }
        }

        return $this;
    }

    public function getRankingEntry(): ?TownRankingProxy
    {
        return $this->rankingEntry;
    }

    public function setRankingEntry(?TownRankingProxy $rankingEntry): self
    {
        $this->rankingEntry = $rankingEntry;

        return $this;
    }

    /**
     * @ORM\PostPersist()
     * @param LifecycleEventArgs $args
     * @throws ORMException
     */
    public function lifeCycle_postPersist(LifecycleEventArgs $args): void
    {
        $args->getEntityManager()->persist( TownRankingProxy::fromTown( $this ) );
        if ($this->getForum()) {
            $args->getEntityManager()->persist( $g = (new UserGroup())->setName("[town:{$this->getId()}]")->setType(UserGroup::GroupTownInhabitants)->setRef1($this->getId()) );
            $args->getEntityManager()->persist( (new ForumUsagePermissions())->setForum($this->getForum())->setPrincipalGroup($g)->setPermissionsGranted(ForumUsagePermissions::PermissionReadWrite)->setPermissionsDenied(ForumUsagePermissions::PermissionNone) );
        }
        $args->getEntityManager()->flush();
    }

    /**
     * @ORM\PreRemove()
     * @param LifecycleEventArgs $args
     * @throws ORMException
     */
    public function lifeCycle_preRemove(LifecycleEventArgs $args) {
        $g = $args->getEntityManager()->getRepository(UserGroup::class)->findOneBy( ['type' => UserGroup::GroupTownInhabitants, 'ref1' => $this->getId()] );
        if ($g) $args->getEntityManager()->remove($g);
    }

    public function getLastAttack(): ?AttackSchedule
    {
        return $this->lastAttack;
    }

    public function setLastAttack(?AttackSchedule $lastAttack): self
    {
        $this->lastAttack = $lastAttack;

        return $this;
    }

    public function getAttackFails(): ?int
    {
        return $this->attackFails;
    }

    public function setAttackFails(int $attackFails): self
    {
        $this->attackFails = $attackFails;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getDayWithoutAttack(): ?int
    {
        return $this->dayWithoutAttack;
    }

    public function setDayWithoutAttack(int $dayWithoutAttack): self
    {
        $this->dayWithoutAttack = $dayWithoutAttack;

        return $this;
    }

    public function getCreator(): ?User
    {
        return $this->creator;
    }

    public function setCreator(?User $creator): self
    {
        $this->creator = $creator;

        return $this;
    }

    public function getDeriveConfigFrom(): ?string
    {
        return $this->deriveConfigFrom;
    }

    public function setDeriveConfigFrom(?string $deriveConfigFrom): self
    {
        $this->deriveConfigFrom = $deriveConfigFrom;

        return $this;
    }

    public function getMapOffset(): array {
        $xOffset = 0;
        $yOffset = 0;
        foreach($this->getZones() as $zone) {
            $xOffset = min($xOffset, $zone->getX());
            $yOffset = max($yOffset, $zone->getY());
        }

        return ['x' => abs($xOffset), 'y' => abs($yOffset)];
    }

    public function getMapSize(?int &$x = null, ?int &$y = null): int {
        $max_x = $min_x = $max_y = $min_y = 0;
        foreach($this->getZones() as $zone) {
            $min_x = min($min_x, $zone->getX());
            $max_x = max($max_x, $zone->getX());
            $min_y = min($min_y, $zone->getY());
            $max_y = max($max_y, $zone->getY());
        }

        $x = abs($max_x) + abs($min_x) + 1;
        $y = abs($max_y) + abs($min_y) + 1;
        return max($x,$y);
    }

    public function getInsurrectionProgress(): ?int
    {
        return $this->insurrectionProgress;
    }

    public function setInsurrectionProgress(int $insurrectionProgress): self
    {
        $this->insurrectionProgress = $insurrectionProgress;

        return $this;
    }

    public function getQuarantine(): bool
    {
        return $this->getAttackFails() >= 3;
    }

    public function getManagedEvents(): ?bool
    {
        return $this->managedEvents;
    }

    public function setManagedEvents(bool $managedEvents): self
    {
        $this->managedEvents = $managedEvents;

        return $this;
    }

    public function getProfilerVersion(): ?int
    {
        return $this->profilerVersion;
    }

    public function setProfilerVersion(int $profilerVersion): self
    {
        $this->profilerVersion = $profilerVersion;

        return $this;
    }

    public function getStrangerPower(): ?int
    {
        return $this->strangerPower;
    }

    public function setStrangerPower(int $strangerPower): self
    {
        $this->strangerPower = max(0, $strangerPower);
        if ($strangerPower > 0) $this->setStrangerEnabled(true);

        return $this;
    }

    public function getStrangers(): int {
        return $this->getStrangerPower() > 0 ? 1 : 0;
    }

    public function getStrangerEnabled(): ?bool
    {
        return $this->strangerEnabled;
    }

    public function setStrangerEnabled(bool $strangerEnabled): self
    {
        $this->strangerEnabled = $strangerEnabled;

        return $this;
    }

    public function getForceStartAhead(): ?bool
    {
        return $this->forceStartAhead;
    }

    public function setForceStartAhead(bool $forceStartAhead): self
    {
        $this->forceStartAhead = $forceStartAhead;

        return $this;
    }
}
