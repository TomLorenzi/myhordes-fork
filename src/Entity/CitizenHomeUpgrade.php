<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;

/**
 * @ORM\Entity(repositoryClass="App\Repository\CitizenHomeUpgradeRepository")
 * @Table(uniqueConstraints={
 *     @UniqueConstraint(name="citizen_home_upgrade_assoc_unique",columns={"prototype_id","home_id"})
 * })
 */
class CitizenHomeUpgrade
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
    private $level;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\CitizenHomeUpgradePrototype")
     * @ORM\JoinColumn(nullable=false)
     */
    private $prototype;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\CitizenHome", inversedBy="citizenHomeUpgrades")
     * @ORM\JoinColumn(nullable=false)
     */
    private $home;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getPrototype(): ?CitizenHomeUpgradePrototype
    {
        return $this->prototype;
    }

    public function setPrototype(?CitizenHomeUpgradePrototype $prototype): self
    {
        $this->prototype = $prototype;

        return $this;
    }

    public function getHome(): ?CitizenHome
    {
        return $this->home;
    }

    public function setHome(?CitizenHome $home): self
    {
        $this->home = $home;

        return $this;
    }
}
