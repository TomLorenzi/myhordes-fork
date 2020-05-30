<?php

namespace App\Entity;

use App\Repository\RuinExplorerStatsRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;

/**
 * @ORM\Entity(repositoryClass=RuinExplorerStatsRepository::class)
 * @Table(uniqueConstraints={
 *     @UniqueConstraint(name="explorer_unique",columns={"citizen_id", "zone_id"})
 * })
 */
class RuinExplorerStats
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Citizen::class, inversedBy="explorerStats", cascade={"persist"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $citizen;

    /**
     * @ORM\ManyToOne(targetEntity=Zone::class, inversedBy="explorerStats", cascade={"persist"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $zone;

    /**
     * @ORM\Column(type="integer")
     */
    private $x = 0;

    /**
     * @ORM\Column(type="integer")
     */
    private $y = 0;

    /**
     * @ORM\Column(type="boolean")
     */
    private $active;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCitizen(): ?Citizen
    {
        return $this->citizen;
    }

    public function setCitizen(?Citizen $citizen): self
    {
        $this->citizen = $citizen;
        $this->setZone( $citizen !== null ? $citizen->getZone() : null );

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

    public function getActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;

        return $this;
    }
}
