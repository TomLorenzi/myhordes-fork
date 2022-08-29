<?php

namespace App\Entity;

use App\Repository\ConnectionWhitelistRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ConnectionWhitelistRepository::class)]
class ConnectionWhitelist
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;
    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'connectionWhitelists')]
    private $users;
    #[ORM\ManyToOne(targetEntity: User::class)]
    private $creator;
    #[ORM\Column(type: 'text', nullable: true)]
    private $reason;
    public function __construct()
    {
        $this->users = new ArrayCollection();
    }
    public function getId(): ?int
    {
        return $this->id;
    }
    /**
     * @return Collection|User[]
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }
    public function addUser(User $user): self
    {
        if (!$this->users->contains($user)) {
            $this->users[] = $user;
        }

        return $this;
    }
    public function removeUser(User $user): self
    {
        if ($this->users->contains($user)) {
            $this->users->removeElement($user);
        }

        return $this;
    }
    public function getCreator(): ?User
    {
        return $this->creator;
    }
    public function setCreator(?User $creator): self
    {
        $this->creator = $creator;

        return $this;
    }
    public function getReason(): ?string
    {
        return $this->reason;
    }
    public function setReason(?string $reason): self
    {
        $this->reason = $reason;

        return $this;
    }
}
