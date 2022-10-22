<?php

namespace App\Entity;

use App\Repository\UserReferLinkRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;

#[ORM\Entity(repositoryClass: UserReferLinkRepository::class)]
#[Table]
#[UniqueConstraint(name: 'user_refer_link_name_unique', columns: ['name'])]
#[UniqueConstraint(name: 'user_refer_link_user_unique', columns: ['user_id'])]
class UserReferLink
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;
    #[ORM\OneToOne(targetEntity: User::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private $user;
    #[ORM\Column(type: 'string', length: 32)]
    private $name;
    #[ORM\Column(type: 'boolean')]
    private $active = true;
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
    public function getName(): ?string
    {
        return $this->name;
    }
    public function setName(string $name): self
    {
        $this->name = $name;

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
