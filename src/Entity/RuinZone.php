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
    const CORRIDOR_NONE =  0;

    const CORRIDOR_E    =  1;
    const CORRIDOR_N    =  2;
    const CORRIDOR_S    =  4;
    const CORRIDOR_W    =  8;

    const CORRIDOR_EN   =  3;
    const CORRIDOR_ES   =  5;
    const CORRIDOR_EW   =  9;
    const CORRIDOR_NS   =  6;
    const CORRIDOR_NW   = 10;
    const CORRIDOR_SW   = 12;

    const CORRIDOR_ENS  =  7;
    const CORRIDOR_ENW  = 11;
    const CORRIDOR_ESW  = 13;
    const CORRIDOR_NSW  = 14;

    const CORRIDOR_ENSW = 15;

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
    private $corridor = 0;

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
     * @ORM\ManyToOne(targetEntity="App\Entity\Zone", inversedBy="ruinZones")
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

    public function getCorridor(): ?int
    {
        return $this->corridor;
    }

    public function setCorridor(int $corridor): self
    {
        $this->corridor = $corridor;

        return $this;
    }

    public function hasCorridor(int $corridor): bool
    {
        if ($corridor == self::CORRIDOR_NONE) {
            return false;
        }
        $check = [
            self::CORRIDOR_W => 1,
            self::CORRIDOR_S => 2,
            self::CORRIDOR_N => 3,
            self::CORRIDOR_E => 4,
        ];
        if (!array_key_exists($corridor, $check)) {
            return false;
        }
        $bin = sprintf( "%05d", decbin( $this->corridor ));
        return $bin[$check[$corridor]] == 1;
    }

    public function addCorridor(int $corridor): self
    {
        if (!$this->hasCorridor($corridor)) {
            $this->corridor += $corridor;
        }

        return $this;
    }

    public function removeCorridor(int $corridor): self
    {
        if ($this->hasCorridor($corridor)) {
            $this->corridor -= $corridor;
        }

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
}
