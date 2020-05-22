<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;

/**
 * @ORM\Entity(repositoryClass="App\Repository\CitizenHomeUpgradeCostsRepository")
 * @Table(uniqueConstraints={
 *     @UniqueConstraint(name="citizen_home_upgrade_costs_assoc_unique",columns={"prototype_id","level"})
 * })
 */
class CitizenHomeUpgradeCosts
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\CitizenHomeUpgradePrototype")
     * @ORM\JoinColumn(nullable=false)
     */
    private $prototype;

    /**
     * @ORM\Column(type="integer")
     */
    private $level;

    /**
     * @ORM\Column(type="integer")
     */
    private $ap;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\ItemGroup", cascade={"persist"})
     */
    private $resources;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPrototype(): ?CitizenHomeUpgradePrototype
    {
        return $this->prototype;
    }

    public function setPrototype(?CitizenHomeUpgradePrototype $prototype): self
    {
        $this->prototype = $prototype;

        return $this;
    }

    public function getLevel(): ?int
    {
        return $this->level;
    }

    public function setLevel(int $level): self
    {
        $this->level = $level;

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

    public function getResources(): ?ItemGroup
    {
        return $this->resources;
    }

    public function setResources(?ItemGroup $resources): self
    {
        $this->resources = $resources;

        return $this;
    }
}
