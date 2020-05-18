<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass="App\Repository\RuinZoneRepository")
 * @UniqueEntity("gps")
 * @Table(uniqueConstraints={
 *     @UniqueConstraint(name="gps_unique",columns={"x","y","zone_id"})
 * })
 */
class RuinZone
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="integer")
     */
    private $x;

    /**
     * @ORM\Column(type="integer")
     */
    private $y;

    /**
     * @ORM\Column(type="integer")
     */
    private $zombies;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\Inventory", inversedBy="zone", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $floor;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Zone", inversedBy="ruin_zones")
     * @ORM\JoinColumn(nullable=false)
     */
    private $zone;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\RuinZonePrototype")
     */
    private $prototype;

    /**
     * @ORM\Column(type="integer")
     */
    private $digs = 0;

    public function __construct()
    {
        $this->citizens = new ArrayCollection();
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

    public function getZombies(): ?int
    {
        return $this->zombies;
    }

    public function setZombies(int $zombies): self
    {
        $this->zombies = $zombies;

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

        return $this;
    }

    public function getPrototype(): ?RuinZonePrototype
    {
        return $this->prototype;
    }

    public function setPrototype(?RuinZonePrototype $prototype): self
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

    /**
     * @return Collection|DigRuinMarker[]
     */
    public function getDigRuinMarkers(): Collection
    {
        return $this->digRuinMarkers;
    }

    public function addDigRuinMarker(DigRuinMarker $digRuinMarker): self
    {
        if (!$this->digRuinMarkers->contains($digRuinMarker)) {
            $this->digRuinMarkers[] = $digRuinMarker;
            $digRuinMarker->setZone($this);
        }

        return $this;
    }

    public function removeDigRuinMarker(DigRuinMarker $digRuinMarker): self
    {
        if ($this->digRuinMarkers->contains($digRuinMarker)) {
            $this->digRuinMarkers->removeElement($digRuinMarker);
            // set the owning side to null (unless already changed)
            if ($digRuinMarker->getZone() === $this) {
                $digRuinMarker->setZone(null);
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

        return $this;
    }

    public function getZombieStatus(): ?int
    {
        return $this->zombieStatus;
    }

    public function setZombieStatus(int $zombieStatus): self
    {
        $this->zombieStatus = $zombieStatus;

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

    }

    public function getBuryCount(): ?int
    {
        return $this->buryCount;
    }

    public function setBuryCount(int $buryCount): self
    {
        $this->buryCount = $buryCount;

        return $this;
    }

    /**
     * @return Collection|ScoutVisit[]
     */
    public function getScoutVisits(): Collection
    {
        return $this->scoutVisits;
    }

    public function addScoutVisit(ScoutVisit $scoutVisit): self
    {
        if (!$this->scoutVisits->contains($scoutVisit)) {
            $this->scoutVisits[] = $scoutVisit;
            $scoutVisit->setZone($this);
        }

        return $this;
    }

    public function removeScoutVisit(ScoutVisit $scoutVisit): self
    {
        if ($this->scoutVisits->contains($scoutVisit)) {
            $this->scoutVisits->removeElement($scoutVisit);
            // set the owning side to null (unless already changed)
            if ($scoutVisit->getZone() === $this) {
                $scoutVisit->setZone(null);
            }
        }

        return $this;
    }

    public function getScoutLevel(): int
    {
        return max(0,$this->getScoutVisits()->count() - 1);
    }

    public function getScoutEstimationOffset(): ?int
    {
        return $this->scoutEstimationOffset;
    }

    public function getPersonalScoutEstimation(Citizen $c): ?int
    {
        return ($this->getZombies() === 0) ? 0 : max(0, $this->getZombies() + (($c->getId() + $this->scoutEstimationOffset) % 5) - 2);
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

        return $this;
    }
}
