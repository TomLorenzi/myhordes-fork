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
 *     @UniqueConstraint(name="town_unique",columns={"prototype_id","town_id", "user_id"})
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
     * @ORM\Column(type="boolean")
     */
    private $persisted = false;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Town")
     * @ORM\JoinColumn(nullable=true)
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

    public function __construct()
    {
        $this->town = new ArrayCollection();
        $this->user = new ArrayCollection();
    }

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

    public function getPersisted(): ?bool
    {
        return $this->persisted;
    }

    public function setPersisted(bool $persisted): self
    {
        $this->persisted = $persisted;

        return $this;
    }

    public function getTown(): Town
    {
        return $this->town;
    }

    public function setTown(Town $town): self
    {
        $this->town = $town;

        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
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
}
