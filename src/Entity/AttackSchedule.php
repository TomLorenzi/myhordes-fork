<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: 'App\Repository\AttackScheduleRepository')]
class AttackSchedule
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;
    #[ORM\Column(type: 'datetime')]
    private $timestamp;
    #[ORM\Column(type: 'boolean')]
    private $completed = false;
    public function getId(): ?int
    {
        return $this->id;
    }
    public function getTimestamp(): ?\DateTimeInterface
    {
        return $this->timestamp;
    }
    public function setTimestamp(\DateTimeInterface $timestamp): self
    {
        $this->timestamp = $timestamp;

        return $this;
    }
    public function getCompleted(): ?bool
    {
        return $this->completed;
    }
    public function setCompleted(bool $completed): self
    {
        $this->completed = $completed;

        return $this;
    }
}
