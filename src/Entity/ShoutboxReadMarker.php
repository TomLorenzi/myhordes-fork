<?php

namespace App\Entity;

use App\Repository\ShoutboxReadMarkerRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;

/**
 * @ORM\Entity(repositoryClass=ShoutboxReadMarkerRepository::class)
 * @Table(uniqueConstraints={
 *     @UniqueConstraint(name="shoutbox_read_marker_owner_unique",columns={"user_id"})
 * })
 */
class ShoutboxReadMarker
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\OneToOne(targetEntity=User::class, cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @ORM\ManyToOne(targetEntity=ShoutboxEntry::class)
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    private $entry;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getEntry(): ?ShoutboxEntry
    {
        return $this->entry;
    }

    public function setEntry(?ShoutboxEntry $entry): self
    {
        $this->entry = $entry;

        return $this;
    }
}
