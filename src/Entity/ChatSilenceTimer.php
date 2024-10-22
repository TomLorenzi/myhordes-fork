<?php

namespace App\Entity;

use App\Repository\ChatSilenceTimerRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ChatSilenceTimerRepository::class)]
class ChatSilenceTimer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;
    #[ORM\ManyToOne(targetEntity: Zone::class, inversedBy: 'chatSilenceTimers')]
    #[ORM\JoinColumn(nullable: false)]
    private $zone;
    #[ORM\Column(type: 'datetime')]
    private $time;
    #[ORM\ManyToOne(targetEntity: Citizen::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private $citizen;
    public function getId(): ?int
    {
        return $this->id;
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
    public function getTime(): ?\DateTimeInterface
    {
        return $this->time;
    }
    public function setTime(\DateTimeInterface $time): self
    {
        $this->time = $time;

        return $this;
    }
    public function getCitizen(): ?Citizen
    {
        return $this->citizen;
    }
    public function setCitizen(?Citizen $citizen): self
    {
        $this->citizen = $citizen;

        return $this;
    }
}
