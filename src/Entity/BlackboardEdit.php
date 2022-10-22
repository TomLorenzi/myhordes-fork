<?php

namespace App\Entity;

use App\Repository\BlackboardEditRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BlackboardEditRepository::class)]
class BlackboardEdit
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;
    #[ORM\Column(type: 'text', nullable: true)]
    private $text;
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private $user;
    #[ORM\Column(type: 'datetime')]
    private $time;
    #[ORM\ManyToOne(targetEntity: Town::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private $town;
    public function getId(): ?int
    {
        return $this->id;
    }
    public function getText(): ?string
    {
        return $this->text;
    }
    public function setText(?string $text): self
    {
        $this->text = $text;

        return $this;
    }
    public function getUser(): ?User
    {
        return $this->user;
    }
    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }
    public function getTime(): ?\DateTimeInterface
    {
        return $this->time;
    }
    public function setTime(\DateTimeInterface $time): self
    {
        $this->time = $time;

        return $this;
    }
    public function getTown(): ?Town
    {
        return $this->town;
    }
    public function setTown(?Town $town): self
    {
        $this->town = $town;

        return $this;
    }
}
