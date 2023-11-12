<?php

namespace App\Entity;

use App\Enum\TownRevisionType;
use App\Enum\ZoneActivityMarkerType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Doctrine\ORM\PersistentCollection;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: 'App\Repository\ZoneRepository')]
#[UniqueEntity('gps')]
#[Table]
#[UniqueConstraint(name: 'gps_unique_zone', columns: ['x', 'y', 'town_id'])]
class Zone
{
    const DiscoveryStateNone    = 0;
    const DiscoveryStatePast    = 1;
    const DiscoveryStateCurrent = 2;
    const ZombieStateUnknown  = 0;
    const ZombieStateEstimate = 1;
    const ZombieStateExact    = 2;
    const DirectionNorthWest = 1;
    const DirectionNorth     = 2;
    const DirectionNorthEast = 3;
    const DirectionWest      = 4;
    const DirectionCenter    = 5;
    const DirectionEast      = 6;
    const DirectionSouthWest = 7;
    const DirectionSouth     = 8;
    const DirectionSouthEast = 9;
    const BluePrintNone      = 0;
    const BlueprintAvailable = 1;
    const BlueprintFound     = 2;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;
    #[ORM\Column(type: 'integer')]
    private $x;
    #[ORM\Column(type: 'integer')]
    private $y;
    #[ORM\Column(type: 'integer')]
    private $zombies;
    #[ORM\OneToOne(targetEntity: 'App\Entity\Inventory', inversedBy: 'zone', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private $floor;
    #[ORM\ManyToOne(targetEntity: 'App\Entity\Town', inversedBy: 'zones', cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false)]
    private $town;
    #[ORM\OneToMany(targetEntity: 'App\Entity\Citizen', mappedBy: 'zone', fetch: 'LAZY')]
    private $citizens;
    #[ORM\Column(type: 'integer')]
    private $initialZombies;
    #[ORM\ManyToOne(targetEntity: 'App\Entity\ZonePrototype')]
    private $prototype;
    #[ORM\Column(type: 'integer')]
    private $digs = 10;
    #[ORM\OneToMany(targetEntity: 'App\Entity\DigTimer', mappedBy: 'zone', orphanRemoval: true, cascade: ['persist', 'remove'])]
    private $digTimers;
    #[ORM\OneToMany(targetEntity: 'App\Entity\EscapeTimer', mappedBy: 'zone', orphanRemoval: true, cascade: ['persist', 'remove'])]
    private $escapeTimers;
    #[ORM\Column(type: 'integer')]
    private $ruinDigs = 10;
    #[ORM\Column(type: 'integer')]
    private $discoveryStatus = self::DiscoveryStateNone;
    #[ORM\Column(type: 'integer')]
    private $zombieStatus = self::ZombieStateUnknown;
    #[ORM\Column(type: 'integer')]
    private $buryCount = 0;
    #[ORM\Column(type: 'integer')]
    private $scoutEstimationOffset;
    #[ORM\Column(type: 'float')]
    private $improvementLevel = 0;
    #[ORM\Column(type: 'integer', nullable: true)]
    private $blueprint = self::BluePrintNone;
    #[ORM\ManyToOne(targetEntity: 'App\Entity\ZoneTag')]
    private $tag;
    #[ORM\OneToMany(targetEntity: 'App\Entity\RuinZone', mappedBy: 'zone', orphanRemoval: true, cascade: ['persist', 'remove'])]
    private $ruinZones;
    #[ORM\OneToMany(targetEntity: RuinExplorerStats::class, mappedBy: 'zone', orphanRemoval: true, cascade: ['persist', 'remove'])]
    private $explorerStats;
    #[ORM\OneToMany(targetEntity: ChatSilenceTimer::class, mappedBy: 'zone', orphanRemoval: true, cascade: ['persist', 'remove'])]
    private $chatSilenceTimers;
    #[ORM\Column(type: 'integer')]
    private $startZombies = 0;
    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private $itemsHiddenAt;

    #[ORM\OneToMany(mappedBy: 'zone', targetEntity: ZoneActivityMarker::class, cascade: ['persist', 'remove'], fetch: 'EXTRA_LAZY', orphanRemoval: true)]
    private Collection $activityMarkers;

    #[ORM\Column]
    private int $explorableFloors = 1;

    #[ORM\Column]
    private int $playerDeaths = 0;

    public function __construct()
    {
        $this->citizens = new ArrayCollection();
        $this->digTimers = new ArrayCollection();
        $this->escapeTimers = new ArrayCollection();
        $this->ruinZones = new ArrayCollection();
        $this->explorerStats = new ArrayCollection();
        $this->chatSilenceTimers = new ArrayCollection();
        $this->activityMarkers = new ArrayCollection();
    }
    public function getId(): ?int
    {
        return $this->id;
    }
    public function getX(): ?int
    {
        return $this->x;
    }
    public function setX(int $x): self
    {
        $this->x = $x;

        return $this;
    }
    public function getY(): ?int
    {
        return $this->y;
    }
    public function setY(int $y): self
    {
        $this->y = $y;

        return $this;
    }
    public function isTownZone(): bool {
        return $this->x === 0 && $this->y === 0;
    }
    public function getDistance(): int {
        return round(sqrt( pow($this->getX(),2) + pow($this->getY(),2) ));
    }
    public function getApDistance(): int {
        return abs($this->getX()) + abs($this->getY());
    }
    public function getZombies(): ?int
    {
        return $this->zombies;
    }
    public function setZombies(int $zombies): self
    {
        $this->zombies = $zombies;
        $this->getTown()?->getRevision( TownRevisionType::MapOverall )?->touch();

        return $this;
    }
    public function getFloor(): ?Inventory
    {
        return $this->floor;
    }
    public function setFloor(Inventory $floor): self
    {
        $this->floor = $floor;

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
            $citizen->setZone($this);
        }

        $this->getTown()?->getRevision( TownRevisionType::MapOverall )?->touch();

        return $this;
    }
    public function removeCitizen(Citizen $citizen): self
    {
        if ($this->citizens->contains($citizen)) {
            $this->citizens->removeElement($citizen);
            // set the owning side to null (unless already changed)
            if ($citizen->getZone() === $this) {
                $citizen->setZone(null);
            }
        }

        $this->getTown()?->getRevision( TownRevisionType::MapOverall )?->touch();

        return $this;
    }
    public function getCampers(): array {
        // No citizens = no campers.
        if ($this->citizens->isEmpty()) {
            return [];
        }
        $campers = [];
        foreach ($this->citizens as $citizen) {
            if ($citizen->getCampingTimestamp() > 0) {
                $campers[$citizen->getCampingTimestamp()] = $citizen;
            }
        }
        ksort($campers);
        return $campers;
    }
    /**
     * Amount of campers before the specified citizen
     * If the citizen is not currently hidden,
     * corresponds to the total amount of campers
     */
    public function getPreviousCampers(Citizen $citizen): int {
        $previous_campers = 0;
        $zone_campers = $this->getCampers();
        foreach ($zone_campers as $camper) {
            if ($camper !== $citizen) {
                $previous_campers++;
            }
            else {
                break;
            }
        }
        return $previous_campers;
    }
    public function getBuildingCampingCapacity(): int {
        // No building
        $ruin = $this->getPrototype();
        if(!$ruin) return 0;

        // Look for a spot inside the building, or through the debris
        if ($this->getBuryCount() == 0) {
            return $ruin->getCapacity();
        } else {
            // Add 1 slot for every 3 remaining debris over the building
            return max(0, min(3, floor($this->getBuryCount() / 3)));
        }
    }
    public function getInitialZombies(): ?int
    {
        return $this->initialZombies;
    }
    public function setInitialZombies(int $initialZombies): self
    {
        $this->initialZombies = $initialZombies;

        return $this;
    }
    public function getPrototype(): ?ZonePrototype
    {
        return $this->prototype;
    }
    public function setPrototype(?ZonePrototype $prototype): self
    {
        $this->prototype = $prototype;

        return $this;
    }
    public function getDigs(): ?int
    {
        return $this->digs;
    }
    public function setDigs(int $digs): self
    {
        $this->digs = $digs;

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
            $digTimer->setZone($this);
        }

        return $this;
    }
    public function removeDigTimer(DigTimer $digTimer): self
    {
        if ($this->digTimers->contains($digTimer)) {
            $this->digTimers->removeElement($digTimer);
            // set the owning side to null (unless already changed)
            if ($digTimer->getZone() === $this) {
                $digTimer->setZone(null);
            }
        }

        return $this;
    }
    /**
     * @return Collection|EscapeTimer[]
     */
    public function getEscapeTimers(): Collection
    {
        return $this->escapeTimers;
    }
    public function addEscapeTimer(EscapeTimer $escapeTimer): self
    {
        if (!$this->escapeTimers->contains($escapeTimer)) {
            $this->escapeTimers[] = $escapeTimer;
            $escapeTimer->setZone($this);
        }

        return $this;
    }
    public function removeEscapeTimer(EscapeTimer $escapeTimer): self
    {
        if ($this->escapeTimers->contains($escapeTimer)) {
            $this->escapeTimers->removeElement($escapeTimer);
            // set the owning side to null (unless already changed)
            if ($escapeTimer->getZone() === $this) {
                $escapeTimer->setZone(null);
            }
        }

        return $this;
    }

    public function getRuinDigs(): ?int
    {
        return $this->ruinDigs;
    }
    public function setRuinDigs(int $ruinDigs): self
    {
        $this->ruinDigs = $ruinDigs;

        return $this;
    }
    public function getDiscoveryStatus(): ?int
    {
        return $this->discoveryStatus;
    }
    public function setDiscoveryStatus(int $discoveryStatus): self
    {
        $this->discoveryStatus = $discoveryStatus;
        $this->getTown()?->getRevision( TownRevisionType::MapOverall )?->touch();

        return $this;
    }
    public function getZombieStatus(): ?int
    {
        return $this->zombieStatus;
    }
    public function setZombieStatus(int $zombieStatus): self
    {
        $this->zombieStatus = $zombieStatus;
        $this->getTown()?->getRevision( TownRevisionType::MapOverall )?->touch();

        return $this;
    }
    public function getDirection(): int {
        if ($this->getX() === 0 && $this->getY() === 0) return self::DirectionCenter;
        elseif ($this->getX() != 0 && $this->getY() != 0 && (abs(abs($this->getX())-abs($this->getY())) < min(abs($this->getX()),abs($this->getY())))) {
            if ($this->getX() < 0 && $this->getY() < 0) return self::DirectionSouthWest;
            if ($this->getX() < 0 && $this->getY() > 0) return self::DirectionNorthWest;
            if ($this->getX() > 0 && $this->getY() < 0) return self::DirectionSouthEast;
            if ($this->getX() > 0 && $this->getY() > 0) return self::DirectionNorthEast;
        } else {
            if (abs($this->getX()) > abs($this->getY()) && $this->getX() < 0) return self::DirectionWest;
            if (abs($this->getX()) > abs($this->getY()) && $this->getX() > 0) return self::DirectionEast;
            if (abs($this->getX()) < abs($this->getY()) && $this->getY() < 0) return self::DirectionSouth;
            if (abs($this->getX()) < abs($this->getY()) && $this->getY() > 0) return self::DirectionNorth;
        }

        return self::DirectionCenter;
    }
    public function getBuryCount(): ?int
    {
        return $this->buryCount;
    }
    public function setBuryCount(int $buryCount): self
    {
        $this->buryCount = $buryCount;
        $this->getTown()?->getRevision( TownRevisionType::MapOverall )?->touch();

        return $this;
    }

    public function getScoutLevel(): int
    {
        if ($this->isTownZone()) return 0;
        return min(3, max(0,
                          floor($this->getActivityMarkersFor( ZoneActivityMarkerType::ScoutVisit )->count()/5) +
                          $this->getActivityMarkersFor( ZoneActivityMarkerType::ScoutMarker )->count()
        ));
    }

    public function getScoutLevelFor(?Citizen $citizen, ?int &$raw = null): int
    {
        if ($this->isTownZone()) return 0;
        return min(3, max(0,
                          floor(($raw = $this->getActivityMarkersFor( ZoneActivityMarkerType::ScoutVisit, $citizen ?? false )->count())/5) +
                          ($citizen === null ? $this->getActivityMarkersFor( ZoneActivityMarkerType::ScoutMarker )->count() : 0)
        ));
    }

    public function getScoutEstimationOffset(): ?int
    {
        return $this->scoutEstimationOffset;
    }
    public function getPersonalScoutEstimation(Citizen $c): ?int
    {
        if($this->getZombies() === 0){
            return 0;
        }
        mt_srand($c->getId()+$this->getId());
        $offset = mt_rand((3 - $this->getScoutLevel())*-1, 3 - $this->getScoutLevel());
        mt_srand();
        return max(0, $this->getZombies() + $offset);
    }

    public function setScoutEstimationOffset(int $scoutEstimationOffset): self
    {
        $this->scoutEstimationOffset = $scoutEstimationOffset;

        return $this;
    }
    public function getImprovementLevel(): ?float
    {
      return $this->improvementLevel;
    }
    public function setImprovementLevel(float $improvementLevel): self
    {
      $this->improvementLevel = $improvementLevel;

      return $this;
    }
    public function getBlueprint(): ?int
    {
        return $this->blueprint;
    }
    public function setBlueprint(int $blueprint): self
    {
        $this->blueprint = $blueprint;

        return $this;
    }
    public function getTag(): ?ZoneTag
    {
        return $this->tag;
    }
    public function setTag(?ZoneTag $tag): self
    {
        $this->tag = $tag;
        $this->getTown()?->getRevision( TownRevisionType::MapOverall )?->touch();

        return $this;
    }
    /**
     * @return ArrayCollection<int, RuinZone>|PersistentCollection<int, RuinZone>
     */
    public function getRuinZones(): ArrayCollection|PersistentCollection
    {
        return $this->ruinZones;
    }

    /**
     * @return ArrayCollection<int, RuinZone>|PersistentCollection<int, RuinZone>
     */
    public function getRuinZonesOnLevel(int $level): ArrayCollection|PersistentCollection
    {
        return $this->getRuinZones()->matching( (new Criteria())->andWhere( new Comparison( 'z', Comparison::EQ, $level ) ) );
    }

    public function addRuinZone(RuinZone $ruinZone): self
    {
        if (!$this->ruinZones->contains($ruinZone)) {
            $this->ruinZones[] = $ruinZone;
            $ruinZone->setZone($this);
        }

        return $this;
    }
    public function removeRuinZone(RuinZone $ruinZone): self
    {
        if ($this->ruinZones->contains($ruinZone)) {
            $this->ruinZones->removeElement($ruinZone);
            // set the owning side to null (unless already changed)
            if ($ruinZone->getZone() === $this) {
                $ruinZone->setZone(null);
            }
        }

        return $this;
    }
    /**
     * If a citizen is currently exploring the ruin in this zone, the relevant RuinExplorerStats object will be
     * returned. Otherwise, null is returned.
     * @return RuinExplorerStats|null
     */
    public function activeExplorerStats(): ?RuinExplorerStats {
        if ($this->getPrototype() && $this->getPrototype()->getExplorable())
            foreach ($this->getExplorerStats() as $explorerStat)
                if ($explorerStat->getActive() && $this->citizens->contains( $explorerStat->getCitizen() ))
                    return $explorerStat;
        return null;
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
            $explorerStat->setZone($this);
        }

        return $this;
    }
    public function removeExplorerStat(RuinExplorerStats $explorerStat): self
    {
        if ($this->explorerStats->contains($explorerStat)) {
            $this->explorerStats->removeElement($explorerStat);
            // set the owning side to null (unless already changed)
            if ($explorerStat->getZone() === $this) {
                $explorerStat->setZone(null);
            }
        }

        return $this;
    }
    /**
     * @return Collection|ChatSilenceTimer[]
     */
    public function getChatSilenceTimers(): Collection
    {
        return $this->chatSilenceTimers;
    }
    public function addChatSilenceTimer(ChatSilenceTimer $chatSilenceTimer): self
    {
        if (!$this->chatSilenceTimers->contains($chatSilenceTimer)) {
            $this->chatSilenceTimers[] = $chatSilenceTimer;
            $chatSilenceTimer->setZone($this);
        }

        return $this;
    }
    public function removeChatSilenceTimer(ChatSilenceTimer $chatSilenceTimer): self
    {
        if ($this->chatSilenceTimers->removeElement($chatSilenceTimer)) {
            // set the owning side to null (unless already changed)
            if ($chatSilenceTimer->getZone() === $this) {
                $chatSilenceTimer->setZone(null);
            }
        }

        return $this;
    }
    public function getStartZombies(): ?int
    {
        return $this->startZombies;
    }
    public function setStartZombies(int $startZombies): self
    {
        $this->startZombies = $startZombies;

        return $this;
    }
    public function getItemsHiddenAt(): ?\DateTimeImmutable
    {
        return $this->itemsHiddenAt;
    }
    public function setItemsHiddenAt(?\DateTimeImmutable $itemsHiddenAt): self
    {
        $this->itemsHiddenAt = $itemsHiddenAt;

        return $this;
    }

    /**
     * @return ArrayCollection<int, ZoneActivityMarker>|PersistentCollection<int, ZoneActivityMarker>
     */
    public function getActivityMarkers(): ArrayCollection|PersistentCollection
    {
        return $this->activityMarkers;
    }

    /**
     * @param ZoneActivityMarkerType|null $type
     * @param Citizen|null|bool $citizen
     * @return Collection
     */
    public function getActivityMarkersFor(?ZoneActivityMarkerType $type = null, Citizen|bool|null $citizen = null): Collection
    {
        $criteria = new Criteria();
        if ($type !== null)    $criteria->andWhere( new Comparison( 'type', Comparison::EQ, $type->value ) );
        if ($citizen === false)    $criteria->andWhere( new Comparison( 'citizen', Comparison::EQ, null ) );
        elseif ($citizen === true) $criteria->andWhere( new Comparison( 'citizen', Comparison::NEQ, null ) );
        elseif ($citizen !== null) $criteria->andWhere( new Comparison( 'citizen', Comparison::EQ, $citizen ) );
        return $this->getActivityMarkers()->matching( $criteria );
    }

    /**
     * @param ZoneActivityMarkerType $type
     * @param Citizen $citizen
     * @return ?ZoneActivityMarker
     */
    public function getActivityMarkerFor(ZoneActivityMarkerType $type, Citizen $citizen): ?ZoneActivityMarker
    {
        return $this->getActivityMarkers()->matching( (new Criteria())
                                                          ->andWhere( new Comparison( 'type', Comparison::EQ, $type->value ) )
                                                          ->andWhere( new Comparison( 'citizen', Comparison::EQ, $citizen ) )
        )->first() ?: null;
    }

    public function addActivityMarker(ZoneActivityMarker $activityMarker): self
    {
        if (!$this->activityMarkers->contains($activityMarker)) {
            $this->activityMarkers->add($activityMarker);
            $activityMarker->setZone($this);
        }

        return $this;
    }

    public function removeActivityMarker(ZoneActivityMarker $activityMarker): self
    {
        if ($this->activityMarkers->removeElement($activityMarker)) {
            // set the owning side to null (unless already changed)
            if ($activityMarker->getZone() === $this) {
                $activityMarker->setZone(null);
            }
        }

        return $this;
    }

    public function getExplorableFloors(): ?int
    {
        return $this->explorableFloors;
    }

    public function getExplorableFloorFactor(): ?int
    {
        return min(1, $this->getExplorableFloors());
    }

    public function setExplorableFloors(int $explorableFloors): self
    {
        $this->explorableFloors = $explorableFloors;

        return $this;
    }

    public function getPlayerDeaths(): ?int
    {
        return $this->playerDeaths;
    }

    public function setPlayerDeaths(int $playerDeaths): self
    {
        $this->playerDeaths = $playerDeaths;

        return $this;
    }
}
