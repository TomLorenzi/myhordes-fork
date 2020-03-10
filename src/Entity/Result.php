<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ResultRepository")
 * @UniqueEntity("name")
 * @Table(uniqueConstraints={
 *     @UniqueConstraint(name="name_unique",columns={"name"})
 * })
 */
class Result
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=32)
     */
    private $name;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\AffectAP")
     */
    private $ap;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\AffectStatus")
     */
    private $status;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\AffectOriginalItem")
     */
    private $item;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\AffectItemSpawn")
     */
    private $spawn;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\AffectItemConsume")
     */
    private $consume;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\AffectResultGroup")
     */
    private $resultGroup;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\AffectZombies")
     */
    private $zombies;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\AffectBlueprint")
     */
    private $blueprint;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $rolePlayerText;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $casino;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\AffectWell")
     */
    private $well;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\AffectHome")
     */
    private $home;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\AffectDeath")
     */
    private $death;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\AffectOriginalItem")
     */
    private $target;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\AffectZone")
     */
    private $zone;

    public function __construct()
    {
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

    public function getAp(): ?AffectAP
    {
        return $this->ap;
    }

    public function setAp(?AffectAP $ap): self
    {
        $this->ap = $ap;

        return $this;
    }

    public function getStatus(): ?AffectStatus
    {
        return $this->status;
    }

    public function setStatus(?AffectStatus $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getItem(): ?AffectOriginalItem
    {
        return $this->item;
    }

    public function setItem(?AffectOriginalItem $item): self
    {
        $this->item = $item;

        return $this;
    }

    public function getSpawn(): ?AffectItemSpawn
    {
        return $this->spawn;
    }

    public function setSpawn(?AffectItemSpawn $spawn): self
    {
        $this->spawn = $spawn;

        return $this;
    }

    public function getConsume(): ?AffectItemConsume
    {
        return $this->consume;
    }

    public function setConsume(?AffectItemConsume $consume): self
    {
        $this->consume = $consume;

        return $this;
    }

    public function getResultGroup(): ?AffectResultGroup
    {
        return $this->resultGroup;
    }

    public function setResultGroup(?AffectResultGroup $resultGroup): self
    {
        $this->resultGroup = $resultGroup;

        return $this;
    }

    public function getZombies(): ?AffectZombies
    {
        return $this->zombies;
    }

    public function setZombies(?AffectZombies $zombies): self
    {
        $this->zombies = $zombies;

        return $this;
    }

    public function getBlueprint(): ?AffectBlueprint
    {
        return $this->blueprint;
    }

    public function setBlueprint(?AffectBlueprint $blueprint): self
    {
        $this->blueprint = $blueprint;

        return $this;
    }

    public function getRolePlayerText(): ?bool
    {
        return $this->rolePlayerText;
    }

    public function setRolePlayerText(?bool $rolePlayerText): self
    {
        $this->rolePlayerText = $rolePlayerText;

        return $this;
    }

    public function getCasino(): ?int
    {
        return $this->casino;
    }

    public function setCasino(?int $casino): self
    {
        $this->casino = $casino;

        return $this;
    }

    public function getWell(): ?AffectWell
    {
        return $this->well;
    }

    public function setWell(?AffectWell $well): self
    {
        $this->well = $well;

        return $this;
    }

    public function getHome(): ?AffectHome
    {
        return $this->home;
    }

    public function setHome(?AffectHome $home): self
    {
        $this->home = $home;

        return $this;
    }

    public function getDeath(): ?AffectDeath
    {
        return $this->death;
    }

    public function setDeath(?AffectDeath $death): self
    {
        $this->death = $death;

        return $this;
    }

    public function getTarget(): ?AffectOriginalItem
    {
        return $this->target;
    }

    public function setTarget(?AffectOriginalItem $target): self
    {
        $this->target = $target;

        return $this;
    }

    public function getZone(): ?AffectZone
    {
        return $this->zone;
    }

    public function setZone(?AffectZone $zone): self
    {
        $this->zone = $zone;

        return $this;
    }
}
