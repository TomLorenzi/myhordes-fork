<?php

namespace App\Entity;

use App\Enum\GameProfileEntryType;
use App\Enum\TownRevisionType;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\OrderBy;
use Doctrine\ORM\PersistentCollection;

#[ORM\Entity(repositoryClass: 'App\Repository\TownRepository')]
#[ORM\HasLifecycleCallbacks]
class Town
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;
    #[ORM\Column(type: 'string', length: 190)]
    private ?string $name = null;
    #[ORM\Column(type: 'string', length: 8)]
    private ?string $language = null;
    #[ORM\Column(type: 'integer')]
    private ?int $population = null;
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $wordsOfHeroes = null;
    #[ORM\OneToMany(targetEntity: 'App\Entity\Citizen', mappedBy: 'town', orphanRemoval: true, cascade: ['persist', 'remove'])]
    #[OrderBy(['alive' => 'DESC', 'id' => 'ASC'])]
    private $citizens;
    #[ORM\OneToOne(targetEntity: 'App\Entity\Inventory', inversedBy: 'town', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?Inventory $bank = null;
    #[ORM\ManyToOne(targetEntity: 'App\Entity\TownClass', inversedBy: 'towns')]
    #[ORM\JoinColumn(nullable: false)]
    private ?TownClass $type = null;
    #[ORM\Column(type: 'integer')]
    private int $day = 1;
    #[ORM\Column(type: 'integer')]
    private ?int $well = 0;
    #[ORM\OneToMany(targetEntity: 'App\Entity\Zone', mappedBy: 'town', orphanRemoval: true, cascade: ['persist', 'remove'])]
    private $zones;
    #[ORM\Column(type: 'boolean')]
    private bool $door = false;
    #[ORM\Column(type: 'boolean')]
    private bool $chaos = false;
    #[ORM\Column(type: 'boolean')]
    private bool $devastated = false;
    #[ORM\OneToMany(targetEntity: 'App\Entity\Building', mappedBy: 'town', orphanRemoval: true, cascade: ['persist', 'remove'], fetch: 'EXTRA_LAZY')]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private $buildings;
    #[ORM\OneToMany(targetEntity: 'App\Entity\ZombieEstimation', mappedBy: 'town', orphanRemoval: true, cascade: ['persist'])]
    private $zombieEstimations;
    #[ORM\OneToMany(targetEntity: 'App\Entity\Gazette', mappedBy: 'town', orphanRemoval: true, cascade: ['persist'])]
    private $gazettes;
    #[ORM\OneToOne(targetEntity: 'App\Entity\Forum', mappedBy: 'town', cascade: ['persist', 'remove'])]
    private ?Forum $forum = null;
    #[ORM\Column(type: 'array', nullable: true)]
    private array $conf = [];
    #[ORM\Column(type: 'integer')]
    private int $soulDefense = 0;
    #[ORM\ManyToOne(targetEntity: 'App\Entity\Season', inversedBy: 'towns')]
    private ?Season $season = null;
    #[ORM\OneToMany(targetEntity: 'App\Entity\CitizenWatch', mappedBy: 'town', orphanRemoval: true)]
    private $citizenWatches;
    #[ORM\OneToOne(targetEntity: TownRankingProxy::class, mappedBy: 'town', cascade: ['persist'])]
    private ?TownRankingProxy $rankingEntry = null;
    #[ORM\ManyToOne(targetEntity: AttackSchedule::class)]
    private $lastAttack;
    #[ORM\Column(type: 'integer')]
    private int $attackFails = 0;
    #[ORM\Column(type: 'string', length: 90, nullable: true)]
    private ?string $password = null;
    #[ORM\Column(type: 'integer')]
    private int $dayWithoutAttack = 0;
    #[ORM\ManyToOne(targetEntity: User::class)]
    private ?User $creator = null;
    #[ORM\OneToMany(mappedBy: 'town', targetEntity: 'App\Entity\TownLogEntry', cascade: ['remove'])]
    private $_townLogEntries;
    #[ORM\Column(type: 'string', length: 24, nullable: true)]
    private ?string $deriveConfigFrom = null;
    #[ORM\Column(type: 'integer')]
    private int $insurrectionProgress = 0;
    #[ORM\Column(type: 'boolean')]
    private bool $managedEvents = false;
    #[ORM\Column(type: 'integer')]
    private int $profilerVersion = 0;
    #[ORM\Column(type: 'integer')]
    private $strangerPower = 0;
    #[ORM\Column(type: 'boolean')]
    private $strangerEnabled = false;
    #[ORM\Column(type: 'boolean')]
    private $forceStartAhead = false;
    #[ORM\Column(type: 'integer')]
    private $tempDefenseBonus = 0;
    #[ORM\Column(type: 'integer')]
    private $baseDefense = 10;
    #[ORM\Column(type: 'boolean')]
    private bool $lockdown = false;
    #[ORM\Column(type: 'boolean')]
    private bool $brokenDoor = false;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $scheduledFor = null;

    #[ORM\Column(length: 128, nullable: true)]
    private ?string $nameSchema = null;

    #[ORM\OneToMany(mappedBy: 'town', targetEntity: TownAspectRevision::class, cascade: ['persist','remove'], fetch: 'EXTRA_LAZY', orphanRemoval: true)]
    private Collection $revisions;

    #[ORM\OneToOne(mappedBy: 'town', cascade: ['persist'])]
    private ?CommunityEventTownPreset $communityEventTownPreset = null;

    #[ORM\Column]
    private int $bonusScore = 0;

    #[ORM\Column(length: 128, nullable: true)]
    private ?string $prime = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastAttackProcessedAt = null;

    #[ORM\Column]
    private ?bool $mayor = false;

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
        $this->revisions = new ArrayCollection();
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
     * @return Collection<int, Zone>
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

    /**
     * @param int $x0
     * @param int $x1
     * @param int $y0
     * @param int $y1
     * @return Collection<int, Zone>
     */
    public function getZoneRect(int $x0, int $x1, int $y0, int $y1): Collection
    {
        return $this->zones->matching(
            (new Criteria())
                ->andWhere( new Comparison( 'x', Comparison::GTE, $x0 ) )
                ->andWhere( new Comparison( 'x', Comparison::LTE, $x1 ) )
                ->andWhere( new Comparison( 'y', Comparison::GTE, $y0 ) )
                ->andWhere( new Comparison( 'y', Comparison::LTE, $y1 ) )
        );
    }

    /**
     * @param int $x
     * @param int $y
     * @return ?Zone
     */
    public function getZone(int $x, int $y): ?Zone
    {
        $criteria = new Criteria();
        $criteria->andWhere( new Comparison( 'x', Comparison::EQ, $x ) );
        $criteria->andWhere( new Comparison( 'y', Comparison::EQ, $y ) );
        return $this->zones->matching( $criteria )->first() ?: null;
    }

    public function getTownZone(): Zone {
        return $this->getZone(0,0);
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
     * @return ArrayCollection<Building>|PersistentCollection<Building>
     */
    public function getBuildings(): ArrayCollection|PersistentCollection
    {
        return $this->buildings;
    }

    public function getBuilding(BuildingPrototype $prototype): ?Building
    {
        return $this->buildings->matching( (new Criteria())
            ->where( new Comparison( 'prototype', Comparison::EQ, $prototype )  )
        )->first() ?: null;
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

    public function getCoalizedCitizenCount(): int {
        return $this->citizens->filter( fn(Citizen $c) => $c->getCoalized() )->count();
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
     * @param PostPersistEventArgs $args */
    #[ORM\PostPersist]
    public function lifeCycle_postPersist(PostPersistEventArgs $args) : void
    {
        $args->getObjectManager()->persist( TownRankingProxy::fromTown( $this ) );
        if ($this->getForum()) {
            $args->getObjectManager()->persist( $g = (new UserGroup())->setName("[town:{$this->getId()}]")->setType(UserGroup::GroupTownInhabitants)->setRef1($this->getId()) );
            $args->getObjectManager()->persist( (new ForumUsagePermissions())->setForum($this->getForum())->setPrincipalGroup($g)->setPermissionsGranted(ForumUsagePermissions::PermissionReadWrite)->setPermissionsDenied(ForumUsagePermissions::PermissionNone) );
        }
        $args->getObjectManager()->flush();
    }
    /**
     * @param PreRemoveEventArgs $args
     */
    #[ORM\PreRemove]
    public function lifeCycle_preRemove(PreRemoveEventArgs $args): void
    {
        $g = $args->getObjectManager()->getRepository(UserGroup::class)->findOneBy( ['type' => UserGroup::GroupTownInhabitants, 'ref1' => $this->getId()] );
        if ($g) $args->getObjectManager()->remove($g);

        $ga = $args->getObjectManager()->getRepository(UserGroup::class)->findOneBy( ['type' => UserGroup::GroupTownAnimaction, 'ref1' => $this->getId()] );
        if ($ga) $args->getObjectManager()->remove($ga);
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
    public function getTempDefenseBonus(): ?int
    {
        return $this->tempDefenseBonus;
    }
    public function setTempDefenseBonus(int $tempDefenseBonus): self
    {
        $this->tempDefenseBonus = $tempDefenseBonus;

        return $this;
    }
    public function getBaseDefense(): ?int
    {
        return $this->baseDefense;
    }
    public function setBaseDefense(int $baseDefense): self
    {
        $this->baseDefense = $baseDefense;

        return $this;
    }

    public function getScheduledFor(): ?\DateTimeInterface
    {
        return $this->scheduledFor;
    }

    public function setScheduledFor(?\DateTimeInterface $scheduledFor): self
    {
        $this->scheduledFor = $scheduledFor;

        return $this;
    }

    public function getNameSchema(): ?string
    {
        return $this->nameSchema;
    }

    public function setNameSchema(?string $nameSchema): self
    {
        $this->nameSchema = $nameSchema;

        return $this;
    }

    public function getCommunityEventTownPreset(): ?CommunityEventTownPreset
    {
        return $this->communityEventTownPreset;
    }

    public function setCommunityEventTownPreset(?CommunityEventTownPreset $communityEventTownPreset): self
    {
        // unset the owning side of the relation if necessary
        if ($communityEventTownPreset === null && $this->communityEventTownPreset !== null) {
            $this->communityEventTownPreset->setTown(null);
        }

        // set the owning side of the relation if necessary
        if ($communityEventTownPreset !== null && $communityEventTownPreset->getTown() !== $this) {
            $communityEventTownPreset->setTown($this);
        }

        $this->communityEventTownPreset = $communityEventTownPreset;

        return $this;
    }

    /**
     * @return Collection<int, TownAspectRevision>
     */
    public function getRevisions(): Collection
    {
        return $this->revisions;
    }

    public function addRevision(TownAspectRevision $revision): self
    {
        if (!$this->revisions->contains($revision)) {
            $this->revisions->add($revision);
            $revision->setTown($this);
        }

        return $this;
    }

    public function removeRevision(TownAspectRevision $revision): self
    {
        if ($this->revisions->removeElement($revision)) {
            // set the owning side to null (unless already changed)
            if ($revision->getTown() === $this) {
                $revision->setTown(null);
            }
        }

        return $this;
    }

    public function getRevision( TownRevisionType $type, ?int $identifier = null ): TownAspectRevision {
        $revision = $this->getRevisions()->matching( (new Criteria())
            ->andWhere( new Comparison( 'type', Comparison::EQ, $type ) )
            ->andWhere( new Comparison( 'identifier', Comparison::EQ, $identifier ) )
        )->first() ?: null;

        if ($revision === null)
            $this->addRevision( $revision = (new TownAspectRevision())
                ->setType( $type )
                ->setIdentifier( $identifier )
            );

        return $revision;
    }

    public function getBonusScore(): int
    {
        return $this->bonusScore ?? 0;
    }

    public function setBonusScore(int $bonusScore): static
    {
        $this->bonusScore = $bonusScore;

        return $this;
    }

    public function getPrime(): ?string
    {
        return $this->prime;
    }

    public function setPrime(?string $prime): static
    {
        $this->prime = $prime;

        return $this;
    }

    public function getLockdown(): bool
    {
        return $this->lockdown ?? false;
    }
    public function setLockdown(bool $lockdown): self
    {
        $this->lockdown = $lockdown;

        return $this;
    }

    public function getBrokenDoor(): bool
    {
        return $this->brokenDoor ?? false;
    }
    public function setBrokenDoor(bool $brokenDoor): self
    {
        $this->brokenDoor = $brokenDoor;

        return $this;
    }

    public function getLastAttackProcessedAt(): ?\DateTimeImmutable
    {
        return $this->lastAttackProcessedAt;
    }

    public function setLastAttackProcessedAt(\DateTimeImmutable $lastAttackProcessedAt): static
    {
        $this->lastAttackProcessedAt = $lastAttackProcessedAt;

        return $this;
    }

    public function isMayor(): bool
    {
        return $this->mayor;
    }

    public function setMayor(bool $mayor): static
    {
        $this->mayor = $mayor;

        return $this;
    }
}
