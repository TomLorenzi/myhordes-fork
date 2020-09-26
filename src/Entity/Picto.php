<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;

/**
 * @ORM\Entity(repositoryClass="App\Repository\PictoRepository")
 * @Table(uniqueConstraints={
 *     @UniqueConstraint(name="picto_unique",columns={"prototype_id","town_entry_id", "user_id", "persisted", "imported"})
 * })
 */
class Picto
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\PictoPrototype")
     * @ORM\JoinColumn(nullable=false)
     */
    private $prototype;

    /**
     * @ORM\Column(type="smallint")
     * Has a value between 0 and 2
     * 0 = Picto got today
     * 1 = Picto got yerderday or before
     * 2 = Picto saved (citizen has died)
     */
    private $persisted = 0;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Town")
     * @ORM\JoinColumn(nullable=true, onDelete="CASCADE")
     */
    private $town;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="pictos")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @ORM\Column(type="integer")
     */
    private $count = 0;

    /**
     * @ORM\ManyToOne(targetEntity=TownRankingProxy::class, inversedBy="distributedPictos")
     * @ORM\JoinColumn(nullable=true, onDelete="CASCADE")
     */
    private $townEntry;

    /**
     * @ORM\Column(type="boolean")
     */
    private $imported = false;

    public function __construct() {}

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPrototype(): PictoPrototype
    {
        return $this->prototype;
    }

    public function setPrototype(?PictoPrototype $prototype): self
    {
        $this->prototype = $prototype;

        return $this;
    }

    public function getPersisted(): ?int
    {
        return $this->persisted;
    }

    public function setPersisted(int $persisted): self
    {
        $this->persisted = $persisted;

        return $this;
    }

    public function getTown(): ?Town
    {
        return $this->town;
    }

    public function setTown(?Town $town): self
    {
        $this->town = $town;
        $this->setTownEntry($town ? $town->getRankingEntry() : null);

        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getCount(): ?int
    {
        return $this->count;
    }

    public function setCount(int $count): self
    {
        $this->count = $count;

        return $this;
    }

    public function getTownEntry(): ?TownRankingProxy
    {
        return $this->townEntry;
    }

    public function setTownEntry(?TownRankingProxy $townEntry): self
    {
        $this->townEntry = $townEntry;

        return $this;
    }

    public function getImported(): ?bool
    {
        return $this->imported;
    }

    public function setImported(bool $imported): self
    {
        $this->imported = $imported;

        return $this;
    }
}
